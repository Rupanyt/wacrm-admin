<?php
ob_start();
include '../include/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']); exit;
}

$my_id   = $_SESSION['user_id'];
$my_role = $_SESSION['role'];

if (!in_array($my_role, ['super_admin', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']); exit;
}

$action = $_POST['action'] ?? '';

// ── Save Plan ──────────────────────────────────────────────────────────────
if ($action === 'save_plan') {
    $plan_name           = clean_input($_POST['plan_name']);
    $license_limit       = (int)$_POST['license_limit'];
    $validity_days       = (int)$_POST['validity_days'];
    $price               = (float)$_POST['price'];
    $extra_license_price = (float)$_POST['extra_license_price'];
    $description         = clean_input($_POST['description'] ?? '');

    if (!$plan_name || $license_limit < 1 || $validity_days < 1 || $price < 0 || $extra_license_price < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields correctly.']); exit;
    }

    $stmt = $conn->prepare("INSERT INTO reseller_plans (plan_name, license_limit, validity_days, price, extra_license_price, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siidds", $plan_name, $license_limit, $validity_days, $price, $extra_license_price, $description);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => "Plan '$plan_name' created successfully!"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
}

// ── Update Plan ────────────────────────────────────────────────────────────
if ($action === 'update_plan') {
    $id                  = (int)$_POST['id'];
    $plan_name           = clean_input($_POST['plan_name']);
    $license_limit       = (int)$_POST['license_limit'];
    $validity_days       = (int)$_POST['validity_days'];
    $price               = (float)$_POST['price'];
    $extra_license_price = (float)$_POST['extra_license_price'];
    $description         = clean_input($_POST['description'] ?? '');

    if (!$plan_name || $license_limit < 1 || $validity_days < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields correctly.']); exit;
    }

    $stmt = $conn->prepare("UPDATE reseller_plans SET plan_name=?, license_limit=?, validity_days=?, price=?, extra_license_price=?, description=? WHERE id=?");
    $stmt->bind_param("siiddsi", $plan_name, $license_limit, $validity_days, $price, $extra_license_price, $description, $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Plan updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed.']);
    }
    $stmt->close();
}

// ── Toggle Plan Status ─────────────────────────────────────────────────────
if ($action === 'toggle_plan_status') {
    $id      = (int)$_POST['id'];
    $current = clean_input($_POST['status']);
    $new     = ($current === 'active') ? 'inactive' : 'active';
    $stmt    = $conn->prepare("UPDATE reseller_plans SET status=? WHERE id=?");
    $stmt->bind_param("si", $new, $id);
    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'message' => "Plan set to $new."])
        : json_encode(['status' => 'error', 'message' => 'Update failed.']);
    $stmt->close();
}

// ── Delete Plan ────────────────────────────────────────────────────────────
if ($action === 'delete_plan') {
    $id = (int)$_POST['id'];
    $in_use = $conn->query("SELECT COUNT(*) FROM users WHERE plan_id='$id'")->fetch_row()[0];
    if ($in_use > 0) {
        echo json_encode(['status' => 'error', 'message' => "Cannot delete: $in_use reseller(s) are using this plan."]); exit;
    }
    $stmt = $conn->prepare("DELETE FROM reseller_plans WHERE id=?");
    $stmt->bind_param("i", $id);
    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'message' => 'Plan deleted.'])
        : json_encode(['status' => 'error', 'message' => 'Delete failed.']);
    $stmt->close();
}

// ── Assign Plan to Reseller ────────────────────────────────────────────────
if ($action === 'assign_plan') {
    $reseller_id = (int)$_POST['reseller_id'];
    $plan_id     = (int)$_POST['plan_id'];

    // Ownership check
    if ($my_role === 'admin') {
        $check = $conn->query("SELECT id FROM users WHERE id='$reseller_id' AND parent_id='$my_id' AND role='reseller'");
        if (!$check || $check->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']); exit;
        }
    }

    $plan = $conn->query("SELECT * FROM reseller_plans WHERE id='$plan_id' AND status='active'")->fetch_assoc();
    if (!$plan) { echo json_encode(['status' => 'error', 'message' => 'Plan not found or inactive.']); exit; }

    $start  = date('Y-m-d');
    $expiry = date('Y-m-d', strtotime("+{$plan['validity_days']} days"));

    $stmt = $conn->prepare("UPDATE users SET plan_id=?, plan_expiry=?, extra_licenses=0 WHERE id=?");
    $stmt->bind_param("isi", $plan_id, $expiry, $reseller_id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => "Plan '{$plan['plan_name']}' assigned. Expires: $expiry"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Assignment failed.']);
    }
    $stmt->close();
}

ob_end_flush();
?>
