<?php
include '../include/config.php';
$id   = (int)($_GET['id'] ?? 0);
$uid  = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role === 'reseller') {
    $pay = $conn->query("
        SELECT p.*, rp.plan_name, rp.license_limit,
               u.name as reseller_name, u.username, u.email, u.mobile
        FROM payments p
        LEFT JOIN reseller_plans rp ON p.plan_id = rp.id
        LEFT JOIN users u ON p.reseller_id = u.id
        WHERE p.id = '$id' AND p.reseller_id = '$uid'
    ")->fetch_assoc();
} else {
    $pay = $conn->query("
        SELECT p.*, rp.plan_name, rp.license_limit,
               u.name as reseller_name, u.username, u.email, u.mobile
        FROM payments p
        LEFT JOIN reseller_plans rp ON p.plan_id = rp.id
        LEFT JOIN users u ON p.reseller_id = u.id
        WHERE p.id = '$id'
    ")->fetch_assoc();
}

if (!$pay) {
    echo '<p class="text-red-500 text-sm text-center py-6">Invoice not found.</p>'; exit;
}

$sym         = get_config('currency_symbol') ?: '$';
$type_labels = ['plan_purchase' => 'Plan Purchase', 'plan_upgrade' => 'Plan Upgrade', 'extra_licenses' => 'Extra Licenses'];
$status_styles = [
    'pending'  => 'bg-yellow-100 text-yellow-700',
    'approved' => 'bg-green-100 text-green-700',
    'rejected' => 'bg-red-100 text-red-600',
    'paid'     => 'bg-blue-100 text-blue-700'
];
$s_cls = $status_styles[$pay['payment_status']] ?? 'bg-gray-100 text-gray-600';
?>

<div id="invoicePrint">
    <!-- Header -->
    <div class="flex justify-between items-start mb-6 pb-4 border-b border-gray-100">
        <div>
            <img src="<?= get_config('rect_logo_path') ?>" alt="<?= get_config('site_name') ?>"
                 class="h-8 object-contain mb-2" onerror="this.style.display='none'">
            <p class="text-xs text-gray-500"><?= get_config('site_name') ?></p>
            <p class="text-xs text-gray-400"><?= get_config('support_email') ?></p>
        </div>
        <div class="text-right">
            <p class="text-xl font-black text-gray-800">INVOICE</p>
            <p class="font-mono text-xs font-bold text-<?= get_config('theme_color') ?>-600 mt-1"><?= htmlspecialchars($pay['invoice_no']) ?></p>
            <span class="inline-block px-2 py-0.5 <?= $s_cls ?> text-[10px] font-black rounded-full uppercase mt-1"><?= ucfirst($pay['payment_status']) ?></span>
        </div>
    </div>

    <!-- Bill To + Details -->
    <div class="grid grid-cols-2 gap-4 mb-5">
        <div class="p-3 bg-gray-50 rounded-xl">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Billed To</p>
            <p class="font-bold text-gray-800"><?= htmlspecialchars($pay['reseller_name'] ?: $pay['username']) ?></p>
            <p class="text-xs text-gray-500">@<?= htmlspecialchars($pay['username']) ?></p>
            <?php if ($pay['email']): ?><p class="text-xs text-gray-500"><?= htmlspecialchars($pay['email']) ?></p><?php endif; ?>
            <?php if ($pay['mobile']): ?><p class="text-xs text-gray-500"><?= htmlspecialchars($pay['mobile']) ?></p><?php endif; ?>
        </div>
        <div class="p-3 bg-gray-50 rounded-xl">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Invoice Details</p>
            <p class="text-xs text-gray-600">Date: <span class="font-bold"><?= date('d M Y', strtotime($pay['created_at'])) ?></span></p>
            <p class="text-xs text-gray-600 mt-1">Method: <span class="font-bold"><?= $pay['payment_method'] === 'razorpay' ? '💳 Razorpay' : '🏦 Bank Transfer' ?></span></p>
            <?php if ($pay['bank_ref']): ?>
            <p class="text-xs text-gray-600 mt-1">Ref: <span class="font-bold font-mono"><?= htmlspecialchars($pay['bank_ref']) ?></span></p>
            <?php endif; ?>
            <?php if ($pay['razorpay_payment_id']): ?>
            <p class="text-xs text-gray-600 mt-1">RZP ID: <span class="font-mono text-[10px] font-bold"><?= htmlspecialchars($pay['razorpay_payment_id']) ?></span></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Line Items -->
    <table class="w-full text-xs mb-5">
        <thead>
            <tr class="bg-<?= get_config('theme_color') ?>-500 text-white">
                <th class="px-3 py-2.5 text-left font-bold rounded-l-lg">Description</th>
                <th class="px-3 py-2.5 text-center font-bold">Qty</th>
                <th class="px-3 py-2.5 text-right font-bold rounded-r-lg">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-gray-100">
                <td class="px-3 py-3">
                    <p class="font-bold text-gray-800"><?= $type_labels[$pay['payment_type']] ?? ucfirst($pay['payment_type']) ?></p>
                    <?php if ($pay['plan_name']): ?><p class="text-gray-500 mt-0.5">Plan: <?= htmlspecialchars($pay['plan_name']) ?></p><?php endif; ?>
                    <?php if ($pay['extra_qty'] > 0): ?><p class="text-gray-500 mt-0.5"><?= $pay['extra_qty'] ?> × Extra License</p><?php endif; ?>
                </td>
                <td class="px-3 py-3 text-center text-gray-700">1</td>
                <td class="px-3 py-3 text-right font-bold text-gray-800"><?= $sym ?><?= number_format($pay['amount'], 2) ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="px-3 pt-3 text-right font-bold text-gray-600">Total:</td>
                <td class="px-3 pt-3 text-right font-black text-lg text-gray-800"><?= $sym ?><?= number_format($pay['amount'], 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <?php if ($pay['reseller_note']): ?>
    <div class="mb-3 p-2.5 bg-blue-50 border border-blue-100 rounded-lg text-xs text-blue-700">
        <span class="font-bold">Note from reseller:</span> <?= htmlspecialchars($pay['reseller_note']) ?>
    </div>
    <?php endif; ?>
    <?php if ($pay['admin_note']): ?>
    <div class="mb-3 p-2.5 bg-gray-50 border border-gray-100 rounded-lg text-xs text-gray-600">
        <span class="font-bold">Admin note:</span> <?= htmlspecialchars($pay['admin_note']) ?>
    </div>
    <?php endif; ?>

    <p class="text-[10px] text-gray-400 text-center pt-3 border-t border-gray-50">Thank you for your business. — <?= get_config('site_name') ?></p>
</div>

<div class="flex gap-3 mt-5">
    <button onclick="printInvoice()"
            class="flex-1 px-4 py-2.5 bg-<?= get_config('theme_color') ?>-100 text-<?= get_config('theme_color') ?>-700 hover:bg-<?= get_config('theme_color') ?>-200 font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2">
        <i class="fas fa-print"></i> Print Invoice
    </button>
    <button onclick="closeModal()" class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">Close</button>
</div>

<script>
function printInvoice() {
    const content = document.getElementById('invoicePrint').innerHTML;
    const w = window.open('', '_blank');
    w.document.write(`
        <html><head><title>Invoice <?= htmlspecialchars($pay['invoice_no']) ?></title>
        <script src="https://cdn.tailwindcss.com"><\/script>
        <style>body{font-family:sans-serif;padding:30px;max-width:600px;margin:auto;} @media print{button{display:none}}<\/style>
        </head><body>${content}</body></html>
    `);
    w.document.close();
    setTimeout(() => w.print(), 500);
}
</script>
