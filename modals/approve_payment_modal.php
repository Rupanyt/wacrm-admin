<?php
include '../include/config.php';
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    echo '<p class="text-red-500 text-sm">Unauthorized.</p>'; exit;
}

$id  = (int)($_GET['id'] ?? 0);
$pay = $conn->query("
    SELECT p.*, rp.plan_name,
           u.name as reseller_name, u.username
    FROM payments p
    LEFT JOIN reseller_plans rp ON p.plan_id = rp.id
    LEFT JOIN users u ON p.reseller_id = u.id
    WHERE p.id = '$id' AND p.payment_status = 'pending'
")->fetch_assoc();

if (!$pay) {
    echo '<div class="text-center py-6">
            <i class="fas fa-check-circle text-green-300 text-3xl mb-2 block"></i>
            <p class="text-gray-400 text-sm">Payment already reviewed or not found.</p>
          </div>';
    exit;
}

$sym         = get_config('currency_symbol') ?: '$';
$type_labels = ['plan_purchase' => 'Plan Purchase', 'plan_upgrade' => 'Plan Upgrade', 'extra_licenses' => 'Extra Licenses'];
?>

<!-- Summary Box -->
<div class="p-4 bg-gray-50 rounded-xl border border-gray-100 mb-5">
    <div class="flex justify-between items-start">
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Invoice</p>
            <p class="font-mono font-bold text-<?= get_config('theme_color') ?>-600"><?= htmlspecialchars($pay['invoice_no']) ?></p>
        </div>
        <div class="text-right">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Amount</p>
            <p class="text-xl font-black text-gray-800"><?= $sym ?><?= number_format($pay['amount'], 2) ?></p>
        </div>
    </div>
    <hr class="my-3 border-gray-200">
    <div class="grid grid-cols-2 gap-2 text-xs">
        <div>
            <p class="text-gray-400">Reseller</p>
            <p class="font-bold text-gray-800"><?= htmlspecialchars($pay['reseller_name'] ?: $pay['username']) ?></p>
        </div>
        <div>
            <p class="text-gray-400">Payment Type</p>
            <p class="font-bold text-gray-800"><?= $type_labels[$pay['payment_type']] ?? ucfirst($pay['payment_type']) ?></p>
        </div>
        <?php if ($pay['plan_name']): ?>
        <div>
            <p class="text-gray-400">Plan</p>
            <p class="font-bold text-gray-800"><?= htmlspecialchars($pay['plan_name']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($pay['extra_qty'] > 0): ?>
        <div>
            <p class="text-gray-400">Extra Qty</p>
            <p class="font-bold text-gray-800">+<?= $pay['extra_qty'] ?> licenses</p>
        </div>
        <?php endif; ?>
        <div>
            <p class="text-gray-400">Payment Method</p>
            <p class="font-bold text-gray-800"><?= $pay['payment_method'] === 'razorpay' ? '💳 Razorpay' : '🏦 Bank Transfer' ?></p>
        </div>
        <?php if ($pay['bank_ref']): ?>
        <div class="col-span-2">
            <p class="text-gray-400">Bank Reference / UTR</p>
            <p class="font-mono font-bold text-gray-800 break-all"><?= htmlspecialchars($pay['bank_ref']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($pay['reseller_note']): ?>
        <div class="col-span-2">
            <p class="text-gray-400">Reseller Note</p>
            <p class="text-gray-700 italic"><?= htmlspecialchars($pay['reseller_note']) ?></p>
        </div>
        <?php endif; ?>
        <div class="col-span-2">
            <p class="text-gray-400">Submitted On</p>
            <p class="font-bold text-gray-800"><?= date('d M Y, h:i A', strtotime($pay['created_at'])) ?></p>
        </div>
    </div>
</div>

<!-- Admin Note -->
<div class="mb-4">
    <label class="block text-xs font-bold text-gray-600 mb-1.5">Admin Note (optional)</label>
    <textarea id="adminNote" rows="2" placeholder="Reason for approval/rejection, any reference..."
              class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all resize-none"></textarea>
</div>

<!-- Action Buttons -->
<div class="flex gap-3">
    <button onclick="reviewPayment('approved')" id="approveBtn"
            class="flex-1 px-4 py-3 bg-green-500 hover:bg-green-600 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2">
        <i class="fas fa-check"></i> Approve & Activate
    </button>
    <button onclick="reviewPayment('rejected')" id="rejectBtn"
            class="flex-1 px-4 py-3 bg-red-500 hover:bg-red-600 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2">
        <i class="fas fa-times"></i> Reject
    </button>
</div>
<button onclick="closeModal()" class="w-full mt-2 px-4 py-2 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">Cancel</button>

<script>
function reviewPayment(decision) {
    const approveBtn = document.getElementById('approveBtn');
    const rejectBtn  = document.getElementById('rejectBtn');
    const note       = document.getElementById('adminNote').value.trim();

    approveBtn.disabled = true;
    rejectBtn.disabled  = true;

    if (decision === 'approved') {
        approveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving...';
    } else {
        rejectBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rejecting...';
    }

    $.post('api/payment_api.php', {
        action:     'review_payment',
        payment_id: <?= $pay['id'] ?>,
        decision:   decision,
        admin_note: note
    }, function(res) {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(res.message, 'error');
            approveBtn.disabled = false;
            rejectBtn.disabled  = false;
            approveBtn.innerHTML = '<i class="fas fa-check"></i> Approve & Activate';
            rejectBtn.innerHTML  = '<i class="fas fa-times"></i> Reject';
        }
    }, 'json');
}
</script>
