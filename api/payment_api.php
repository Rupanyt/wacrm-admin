<?php
ob_start();
include '../include/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired.']); exit;
}

$my_id   = $_SESSION['user_id'];
$my_role = $_SESSION['role'];
$action  = $_POST['action'] ?? '';
$sym     = get_config('currency_symbol') ?: '$';

// ══════════════════════════════════════════════════════════════════════
// RESELLER ACTIONS
// ══════════════════════════════════════════════════════════════════════

// ── Submit Bank Transfer Payment ───────────────────────────────────────────
if ($action === 'submit_bank_transfer' && $my_role === 'reseller') {
    $payment_type = clean_input($_POST['payment_type']); // plan_purchase / plan_upgrade / extra_licenses
    $plan_id      = !empty($_POST['plan_id']) ? (int)$_POST['plan_id'] : null;
    $extra_qty    = (int)($_POST['extra_qty'] ?? 0);
    $bank_ref     = clean_input($_POST['bank_ref']);
    $reseller_note = clean_input($_POST['reseller_note'] ?? '');

    if (!in_array($payment_type, ['plan_purchase', 'plan_upgrade', 'extra_licenses'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid payment type.']); exit;
    }
    if (!$bank_ref) {
        echo json_encode(['status' => 'error', 'message' => 'Bank reference number is required.']); exit;
    }

    // Calculate amount
    $amount = 0;
    if ($payment_type === 'plan_purchase' || $payment_type === 'plan_upgrade') {
        if (!$plan_id) { echo json_encode(['status' => 'error', 'message' => 'Plan required.']); exit; }
        $plan = $conn->query("SELECT * FROM reseller_plans WHERE id='$plan_id' AND status='active'")->fetch_assoc();
        if (!$plan) { echo json_encode(['status' => 'error', 'message' => 'Plan not found.']); exit; }
        $amount = $plan['price'];
    } elseif ($payment_type === 'extra_licenses') {
        if ($extra_qty < 1) { echo json_encode(['status' => 'error', 'message' => 'Quantity must be at least 1.']); exit; }
        $reseller = $conn->query("SELECT u.extra_license_price FROM users u LEFT JOIN reseller_plans rp ON u.plan_id=rp.id WHERE u.id='$my_id'")->fetch_assoc();
        if (!$reseller) { echo json_encode(['status' => 'error', 'message' => 'No active plan found.']); exit; }
        $amount = $extra_qty * (float)($reseller['extra_license_price'] ?? 5);
    }

    // Generate invoice number
    $invoice_no = 'INV-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-' . date('ymd');

    $stmt = $conn->prepare("INSERT INTO payments (invoice_no, reseller_id, payment_type, plan_id, extra_qty, amount, payment_method, payment_status, bank_ref, reseller_note) VALUES (?, ?, ?, ?, ?, ?, 'bank_transfer', 'pending', ?, ?)");
    $stmt->bind_param("sssiddss", $invoice_no, $my_id, $payment_type, $plan_id, $extra_qty, $amount, $bank_ref, $reseller_note);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => "Payment request submitted! Invoice: $invoice_no. Admin will verify and activate your order."]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Could not submit payment: ' . $conn->error]);
    }
    $stmt->close();
}

// ── Initiate Razorpay Order ────────────────────────────────────────────────
if ($action === 'create_razorpay_order' && $my_role === 'reseller') {
    $razorpay_key    = get_config('razorpay_key_id');
    $razorpay_secret = get_config('razorpay_key_secret');
    $razorpay_on     = get_config('razorpay_enabled');

    if (!$razorpay_on || !$razorpay_key || !$razorpay_secret) {
        echo json_encode(['status' => 'error', 'message' => 'Razorpay is not configured. Please use Bank Transfer.']); exit;
    }

    $payment_type = clean_input($_POST['payment_type']);
    $plan_id      = !empty($_POST['plan_id']) ? (int)$_POST['plan_id'] : null;
    $extra_qty    = (int)($_POST['extra_qty'] ?? 0);

    $amount_rupees = 0;
    if ($payment_type === 'plan_purchase' || $payment_type === 'plan_upgrade') {
        $plan = $conn->query("SELECT * FROM reseller_plans WHERE id='$plan_id' AND status='active'")->fetch_assoc();
        if (!$plan) { echo json_encode(['status' => 'error', 'message' => 'Plan not found.']); exit; }
        $amount_rupees = $plan['price'];
    } elseif ($payment_type === 'extra_licenses') {
        $reseller = $conn->query("SELECT rp.extra_license_price FROM users u LEFT JOIN reseller_plans rp ON u.plan_id=rp.id WHERE u.id='$my_id'")->fetch_assoc();
        $amount_rupees = $extra_qty * (float)($reseller['extra_license_price'] ?? 5);
    }

    $amount_paise = (int)($amount_rupees * 100); // Razorpay uses smallest currency unit

    // Call Razorpay Orders API
    $curl = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => "$razorpay_key:$razorpay_secret",
        CURLOPT_POSTFIELDS     => json_encode(['amount' => $amount_paise, 'currency' => 'INR', 'receipt' => 'rcpt_' . time()]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $rz_data = json_decode($response, true);
    if ($http_code === 200 && isset($rz_data['id'])) {
        echo json_encode([
            'status'       => 'success',
            'order_id'     => $rz_data['id'],
            'amount'       => $amount_paise,
            'key'          => $razorpay_key,
            'payment_type' => $payment_type,
            'plan_id'      => $plan_id,
            'extra_qty'    => $extra_qty,
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Razorpay order creation failed. ' . ($rz_data['error']['description'] ?? '')]);
    }
}

// ── Verify & Save Razorpay Payment ────────────────────────────────────────
if ($action === 'verify_razorpay_payment' && $my_role === 'reseller') {
    $rz_payment_id = clean_input($_POST['razorpay_payment_id']);
    $rz_order_id   = clean_input($_POST['razorpay_order_id']);
    $rz_signature  = clean_input($_POST['razorpay_signature']);
    $payment_type  = clean_input($_POST['payment_type']);
    $plan_id       = !empty($_POST['plan_id']) ? (int)$_POST['plan_id'] : null;
    $extra_qty     = (int)($_POST['extra_qty'] ?? 0);

    $secret = get_config('razorpay_key_secret');
    $expected_signature = hash_hmac('sha256', $rz_order_id . '|' . $rz_payment_id, $secret);

    if (!hash_equals($expected_signature, $rz_signature)) {
        echo json_encode(['status' => 'error', 'message' => 'Payment signature verification failed!']); exit;
    }

    // Calculate amount
    $amount = 0;
    if (($payment_type === 'plan_purchase' || $payment_type === 'plan_upgrade') && $plan_id) {
        $plan   = $conn->query("SELECT * FROM reseller_plans WHERE id='$plan_id'")->fetch_assoc();
        $amount = $plan['price'] ?? 0;
    } elseif ($payment_type === 'extra_licenses') {
        $reseller = $conn->query("SELECT rp.extra_license_price FROM users u LEFT JOIN reseller_plans rp ON u.plan_id=rp.id WHERE u.id='$my_id'")->fetch_assoc();
        $amount   = $extra_qty * (float)($reseller['extra_license_price'] ?? 5);
    }

    $invoice_no = 'RZP-' . strtoupper(substr(md5($rz_payment_id), 0, 8)) . '-' . date('ymd');

    // Save as 'paid' (Razorpay auto-captured)
    $stmt = $conn->prepare("INSERT INTO payments (invoice_no, reseller_id, payment_type, plan_id, extra_qty, amount, payment_method, payment_status, razorpay_payment_id, razorpay_order_id) VALUES (?, ?, ?, ?, ?, ?, 'razorpay', 'paid', ?, ?)");
    $stmt->bind_param("sssiddss", $invoice_no, $my_id, $payment_type, $plan_id, $extra_qty, $amount, $rz_payment_id, $rz_order_id);
    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $conn->error]); exit;
    }
    $stmt->close();

    // Auto-activate
    _activate_payment($conn, $my_id, $payment_type, $plan_id, $extra_qty);
    echo json_encode(['status' => 'success', 'message' => "Payment successful! Invoice: $invoice_no. Your plan has been activated."]);
}

// ══════════════════════════════════════════════════════════════════════
// ADMIN ACTIONS
// ══════════════════════════════════════════════════════════════════════

// ── Approve / Reject Payment ───────────────────────────────────────────────
if ($action === 'review_payment' && in_array($my_role, ['super_admin', 'admin'])) {
    $payment_id = (int)$_POST['payment_id'];
    $decision   = clean_input($_POST['decision']); // approved / rejected
    $admin_note = clean_input($_POST['admin_note'] ?? '');

    if (!in_array($decision, ['approved', 'rejected'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid decision.']); exit;
    }

    $payment = $conn->query("SELECT * FROM payments WHERE id='$payment_id' AND payment_status='pending'")->fetch_assoc();
    if (!$payment) { echo json_encode(['status' => 'error', 'message' => 'Payment not found or already reviewed.']); exit; }

    $stmt = $conn->prepare("UPDATE payments SET payment_status=?, admin_note=?, approved_by=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("ssii", $decision, $admin_note, $my_id, $payment_id);
    if (!$stmt->execute()) { echo json_encode(['status' => 'error', 'message' => 'DB error.']); exit; }
    $stmt->close();

    if ($decision === 'approved') {
        _activate_payment($conn, $payment['reseller_id'], $payment['payment_type'], $payment['plan_id'], $payment['extra_qty']);
        echo json_encode(['status' => 'success', 'message' => 'Payment approved and plan/licenses activated!']);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Payment rejected. Reseller has been notified.']);
    }
}

// ══════════════════════════════════════════════════════════════════════
// HELPER – Activate the plan or extra licenses after payment
// ══════════════════════════════════════════════════════════════════════
function _activate_payment($conn, $reseller_id, $payment_type, $plan_id, $extra_qty) {
    if ($payment_type === 'plan_purchase' || $payment_type === 'plan_upgrade') {
        $plan   = $conn->query("SELECT * FROM reseller_plans WHERE id='$plan_id'")->fetch_assoc();
        if (!$plan) return;
        $expiry = date('Y-m-d', strtotime("+{$plan['validity_days']} days"));
        $stmt   = $conn->prepare("UPDATE users SET plan_id=?, plan_expiry=?, extra_licenses=0 WHERE id=?");
        $stmt->bind_param("isi", $plan_id, $expiry, $reseller_id);
        $stmt->execute(); $stmt->close();
    } elseif ($payment_type === 'extra_licenses' && $extra_qty > 0) {
        $stmt = $conn->prepare("UPDATE users SET extra_licenses = extra_licenses + ? WHERE id=?");
        $stmt->bind_param("ii", $extra_qty, $reseller_id);
        $stmt->execute(); $stmt->close();
    }
}

ob_end_flush();
?>
