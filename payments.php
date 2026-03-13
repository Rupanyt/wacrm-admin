<?php
include 'include/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: dashboard"); exit();
}

$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$name     = $_SESSION['name'];
$title    = "Payments & Invoices";

$sym    = get_config('currency_symbol') ?: '$';
$limit  = 15;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

$cnt_where = ($role === 'admin')
    ? "WHERE reseller_id IN (SELECT id FROM users WHERE parent_id='$user_id' AND role='reseller')"
    : "WHERE 1=1";

$where_join = ($role === 'admin')
    ? "WHERE p.reseller_id IN (SELECT id FROM users WHERE parent_id='$user_id' AND role='reseller')"
    : "WHERE 1=1";

if ($filter) {
    $cnt_where  .= " AND payment_status='$filter'";
    $where_join .= " AND p.payment_status='$filter'";
}

$total       = $conn->query("SELECT COUNT(*) FROM payments $cnt_where")->fetch_row()[0];
$total_pages = ceil($total / $limit);

$payments = $conn->query("
    SELECT p.*, u.name as reseller_name, u.username as reseller_username,
           rp.plan_name, a.username as approved_by_name
    FROM payments p
    LEFT JOIN users u  ON p.reseller_id = u.id
    LEFT JOIN reseller_plans rp ON p.plan_id = rp.id
    LEFT JOIN users a  ON p.approved_by = a.id
    $where_join
    ORDER BY p.id DESC LIMIT $limit OFFSET $offset
");

$pending_count  = $conn->query("SELECT COUNT(*) FROM payments $cnt_where AND payment_status='pending'")->fetch_row()[0];
$approved_count = $conn->query("SELECT COUNT(*) FROM payments $cnt_where AND payment_status IN ('approved','paid')")->fetch_row()[0];
$total_revenue  = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments $cnt_where AND payment_status IN ('approved','paid')")->fetch_row()[0];

// Rebuild clean base where for filter links
$base_cnt = ($role === 'admin')
    ? "WHERE reseller_id IN (SELECT id FROM users WHERE parent_id='$user_id' AND role='reseller')"
    : "WHERE 1=1";
$pending_badge = $conn->query("SELECT COUNT(*) FROM payments $base_cnt AND payment_status='pending'")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | <?= get_config('site_name') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/png" href="<?= get_config('circle_logo_path') ?>">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; overflow: hidden; }
        .nav-item { white-space: nowrap; overflow: hidden; transition: all 0.2s ease; display: flex; align-items: center; }
        .active-link-white { background-color: #f0fdf4 !important; border: 1px solid #dcfce7; color: #166534 !important; }
        .active-link-white i { color: #22c55e !important; }
    </style>
</head>
<body class="flex h-screen">
<?php include 'sections/sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'sections/navbar.php'; ?>

<main class="flex-1 overflow-y-auto p-8 antialiased">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Payments & Invoices</h1>
            <p class="text-sm text-gray-500 mt-1">Review, approve, or reject reseller payment requests.</p>
        </div>
        <!-- Filter Tabs -->
        <div class="flex gap-2 flex-wrap">
            <a href="payments" class="px-4 py-2 text-xs font-bold rounded-xl border <?= !$filter ? 'bg-'.get_config('theme_color').'-100 text-'.get_config('theme_color').'-700 border-'.get_config('theme_color').'-200' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50' ?>">All</a>
            <a href="payments?status=pending" class="px-4 py-2 text-xs font-bold rounded-xl border flex items-center gap-1.5 <?= $filter=='pending' ? 'bg-yellow-100 text-yellow-700 border-yellow-200' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50' ?>">
                Pending
                <?php if ($pending_badge > 0): ?><span class="bg-red-400 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full"><?= $pending_badge ?></span><?php endif; ?>
            </a>
            <a href="payments?status=approved" class="px-4 py-2 text-xs font-bold rounded-xl border <?= $filter=='approved' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50' ?>">Approved</a>
            <a href="payments?status=rejected" class="px-4 py-2 text-xs font-bold rounded-xl border <?= $filter=='rejected' ? 'bg-red-100 text-red-700 border-red-200' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50' ?>">Rejected</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 bg-yellow-50 text-yellow-500 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-clock"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Pending Approvals</p>
                <h3 class="text-2xl font-black text-gray-800"><?= $pending_count ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 bg-green-50 text-green-500 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-check-double"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Approved Payments</p>
                <h3 class="text-2xl font-black text-gray-800"><?= $approved_count ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 bg-<?= get_config('theme_color') ?>-50 text-<?= get_config('theme_color') ?>-500 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-dollar-sign"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Revenue</p>
                <h3 class="text-2xl font-black text-gray-800"><?= $sym ?><?= number_format($total_revenue, 2) ?></h3>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-100 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Invoice</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Reseller</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Details</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Amount</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-center">Status</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                <?php if ($payments && $payments->num_rows > 0):
                    while ($row = $payments->fetch_assoc()):
                        $type_labels = ['plan_purchase' => 'Plan Purchase', 'plan_upgrade' => 'Plan Upgrade', 'extra_licenses' => 'Extra Licenses'];
                        $type_colors = ['plan_purchase' => 'blue', 'plan_upgrade' => 'purple', 'extra_licenses' => 'orange'];
                        $type_label  = $type_labels[$row['payment_type']] ?? $row['payment_type'];
                        $type_color  = $type_colors[$row['payment_type']] ?? 'gray';
                        $status_map  = [
                            'pending'  => ['bg-yellow-50 text-yellow-700 border-yellow-100', 'bg-yellow-400', 'Pending'],
                            'approved' => ['bg-green-50 text-green-700 border-green-100', 'bg-green-500 animate-pulse', 'Approved'],
                            'rejected' => ['bg-red-50 text-red-600 border-red-100', 'bg-red-400', 'Rejected'],
                            'paid'     => ['bg-blue-50 text-blue-700 border-blue-100', 'bg-blue-500', 'Paid'],
                        ];
                        [$s_class, $s_dot, $s_text] = $status_map[$row['payment_status']] ?? ['bg-gray-50 text-gray-500 border-gray-100', 'bg-gray-400', ucfirst($row['payment_status'])];
                ?>
                <tr class="hover:bg-gray-50/50 transition-all">
                    <td class="px-6 py-4">
                        <div class="font-mono text-xs font-bold text-<?= get_config('theme_color') ?>-600"><?= htmlspecialchars($row['invoice_no']) ?></div>
                        <div class="text-[10px] text-gray-400 mt-0.5"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($row['reseller_name'] ?: $row['reseller_username']) ?></div>
                        <div class="text-[11px] text-gray-400">@<?= htmlspecialchars($row['reseller_username']) ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-0.5 bg-<?= $type_color ?>-50 text-<?= $type_color ?>-700 text-[10px] font-bold rounded-md uppercase"><?= $type_label ?></span>
                        <div class="text-[11px] text-gray-500 mt-1">
                            <?php if ($row['plan_name']): ?>Plan: <?= htmlspecialchars($row['plan_name']) ?><?php endif; ?>
                            <?php if ($row['extra_qty'] > 0): ?> &bull; +<?= $row['extra_qty'] ?> licenses<?php endif; ?>
                        </div>
                        <div class="text-[10px] text-gray-400 mt-0.5">via <span class="font-bold"><?= $row['payment_method'] === 'razorpay' ? '💳 Razorpay' : '🏦 Bank Transfer' ?></span></div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-base font-black text-gray-800"><?= $sym ?><?= number_format($row['amount'], 2) ?></span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 <?= $s_class ?> text-[10px] font-black rounded-full uppercase border">
                            <span class="w-1.5 h-1.5 rounded-full <?= $s_dot ?>"></span>
                            <?= $s_text ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-1">
                            <!-- ✅ FIXED: filename only + data object -->
                            <button onclick="openModal('Invoice #<?= htmlspecialchars($row['invoice_no']) ?>', 'view_invoice_modal', {id: <?= $row['id'] ?>})"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition-all" title="View Invoice">
                                <i class="fas fa-file-invoice text-xs"></i>
                            </button>
                            <?php if ($row['payment_status'] === 'pending'): ?>
                            <button onclick="openModal('Review Payment', 'approve_payment_modal', {id: <?= $row['id'] ?>})"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-green-50 hover:text-green-600 transition-all" title="Approve / Reject">
                                <i class="fas fa-clipboard-check text-xs"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <i class="fas fa-receipt text-gray-200 text-4xl"></i>
                            <p class="text-gray-400 italic text-sm font-medium">No payments found.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-6 border-t border-gray-50 flex flex-col md:flex-row items-center justify-between gap-4 bg-white rounded-b-xl">
            <p class="text-[12px] font-medium text-gray-500">
                Showing <span class="text-gray-800"><?= ($offset + 1) ?></span> to
                <span class="text-gray-800"><?= min($offset + $limit, $total) ?></span> of
                <span class="text-gray-800"><?= $total ?></span> payments
            </p>
            <div class="flex items-center gap-1">
                <a href="?page=<?= $page - 1 ?>&status=<?= $filter ?>" class="<?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?> w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-400 hover:bg-gray-50">
                    <i class="fas fa-chevron-left text-[10px]"></i>
                </a>
                <?php for ($i = 1; $i <= $total_pages; $i++):
                    $ac = ($i == $page) ? 'bg-'.get_config('theme_color').'-100 text-'.get_config('theme_color').'-700 border-'.get_config('theme_color').'-200 font-bold' : 'border-gray-200 text-gray-600 hover:bg-gray-50';
                    echo "<a href='?page=$i&status=$filter' class='w-8 h-8 flex items-center justify-center border $ac rounded-lg text-xs transition-all'>$i</a>";
                endfor; ?>
                <a href="?page=<?= $page + 1 ?>&status=<?= $filter ?>" class="<?= $page >= $total_pages ? 'pointer-events-none opacity-50' : '' ?> w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-400 hover:bg-gray-50">
                    <i class="fas fa-chevron-right text-[10px]"></i>
                </a>
            </div>
        </div>
    </div>

</main>
</div>
<?php include 'sections/common_modal.php'; include 'sections/footer.php'; ?>
