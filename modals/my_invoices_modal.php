<?php
include '../include/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reseller') { echo '<p class="text-red-500 text-sm">Unauthorized.</p>'; exit; }
$user_id = $_SESSION['user_id'];
$sym     = get_config('currency_symbol') ?: '$';
$payments = $conn->query("SELECT p.*, rp.plan_name FROM payments p LEFT JOIN reseller_plans rp ON p.plan_id=rp.id WHERE p.reseller_id='$user_id' ORDER BY p.id DESC");
$type_labels = ['plan_purchase' => 'Plan Purchase', 'plan_upgrade' => 'Plan Upgrade', 'extra_licenses' => 'Extra Licenses'];
$status_map  = ['pending' => 'bg-yellow-50 text-yellow-600', 'approved' => 'bg-green-50 text-green-600', 'rejected' => 'bg-red-50 text-red-500', 'paid' => 'bg-blue-50 text-blue-600'];
?>
<?php if ($payments && $payments->num_rows > 0): ?>
<div class="space-y-3 max-h-[60vh] overflow-y-auto pr-1">
    <?php while ($pay = $payments->fetch_assoc()):
        $s_cls = $status_map[$pay['payment_status']] ?? 'bg-gray-50 text-gray-500';
    ?>
    <div class="p-3.5 border border-gray-100 rounded-xl hover:border-<?= get_config('theme_color') ?>-200 hover:bg-<?= get_config('theme_color') ?>-50/30 transition-all">
        <div class="flex justify-between items-start">
            <div>
                <p class="font-mono text-xs font-bold text-<?= get_config('theme_color') ?>-600"><?= htmlspecialchars($pay['invoice_no']) ?></p>
                <p class="text-xs text-gray-500 mt-0.5"><?= $type_labels[$pay['payment_type']] ?? $pay['payment_type'] ?>
                    <?php if ($pay['plan_name']): ?> — <?= htmlspecialchars($pay['plan_name']) ?><?php endif; ?>
                </p>
                <p class="text-[10px] text-gray-400 mt-0.5"><?= date('d M Y, h:i A', strtotime($pay['created_at'])) ?></p>
            </div>
            <div class="text-right">
                <p class="font-black text-gray-800"><?= $sym ?><?= number_format($pay['amount'], 2) ?></p>
                <span class="inline-block px-2 py-0.5 <?= $s_cls ?> text-[10px] font-bold rounded-full uppercase mt-1"><?= ucfirst($pay['payment_status']) ?></span>
            </div>
        </div>
        <div class="flex justify-end mt-2">
            <button onclick="closeModal(); setTimeout(() => openModal('Invoice <?= htmlspecialchars($pay['invoice_no']) ?>', 'modals/view_invoice_modal.php?id=<?= $pay['id'] ?>'), 300)"
                    class="text-[11px] font-bold text-<?= get_config('theme_color') ?>-600 hover:underline flex items-center gap-1">
                <i class="fas fa-file-invoice text-[10px]"></i> View Invoice
            </button>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="text-center py-10">
    <i class="fas fa-receipt text-gray-200 text-4xl mb-3"></i>
    <p class="text-gray-400 text-sm font-medium">No payment history yet.</p>
</div>
<?php endif; ?>
