<?php
include 'include/config.php';

if (!isset($_SESSION['user_id'])) { header("Location: login"); exit(); }
if ($_SESSION['role'] !== 'reseller') { header("Location: dashboard"); exit(); }

$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$name     = $_SESSION['name'];
$title    = "My Plan";

$sym = get_config('currency_symbol') ?: '$';

$reseller = $conn->query("
    SELECT u.*, rp.plan_name, rp.license_limit, rp.validity_days,
           rp.price, rp.extra_license_price, rp.description as plan_desc
    FROM users u
    LEFT JOIN reseller_plans rp ON u.plan_id = rp.id
    WHERE u.id = '$user_id'
")->fetch_assoc();

$licenses_used = (int)$conn->query("SELECT COUNT(*) FROM licenses WHERE created_by='$user_id'")->fetch_row()[0];
$plan_limit    = ((int)($reseller['license_limit'] ?? 0)) + ((int)($reseller['extra_licenses'] ?? 0));
$plan_pct      = ($plan_limit > 0) ? min(100, round(($licenses_used / $plan_limit) * 100)) : 0;
$has_plan      = !empty($reseller['plan_id']);
$plan_expired  = $has_plan && $reseller['plan_expiry'] && date('Y-m-d') > $reseller['plan_expiry'];
$days_left     = $has_plan && $reseller['plan_expiry'] ? max(0, (strtotime($reseller['plan_expiry']) - strtotime(date('Y-m-d'))) / 86400) : 0;

$all_plans  = $conn->query("SELECT * FROM reseller_plans WHERE status='active' ORDER BY price ASC");
$my_payments = $conn->query("
    SELECT p.*, rp.plan_name
    FROM payments p
    LEFT JOIN reseller_plans rp ON p.plan_id = rp.id
    WHERE p.reseller_id = '$user_id'
    ORDER BY p.id DESC LIMIT 10
");
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
        .progress-bar { transition: width 1s ease-in-out; }
    </style>
</head>
<body class="flex h-screen">
<?php include 'sections/sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'sections/navbar.php'; ?>

<main class="flex-1 overflow-y-auto p-8 antialiased">

    <div class="mb-8">
        <h1 class="text-xl font-bold text-gray-800">My Plan</h1>
        <p class="text-sm text-gray-500 mt-1">Manage your subscription, license quota, and billing history.</p>
    </div>

    <?php if (!$has_plan): ?>
    <!-- No Plan Banner -->
    <div class="bg-white border border-dashed border-gray-200 rounded-2xl p-12 text-center mb-8">
        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-boxes text-gray-300 text-3xl"></i>
        </div>
        <h3 class="font-bold text-gray-700 text-lg mb-2">No Active Plan</h3>
        <p class="text-sm text-gray-400 mb-2 max-w-md mx-auto">You don't have an active plan. Choose a plan below and submit a payment request.</p>
    </div>
    <?php else: ?>

    <!-- Current Plan Card -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 bg-gradient-to-br from-<?= get_config('theme_color') ?>-500 to-<?= get_config('theme_color') ?>-700 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <p class="text-<?= get_config('theme_color') ?>-200 text-xs font-bold uppercase tracking-wider mb-1">Current Plan</p>
                    <h2 class="text-2xl font-black"><?= htmlspecialchars($reseller['plan_name']) ?></h2>
                    <p class="text-<?= get_config('theme_color') ?>-200 text-xs mt-1"><?= htmlspecialchars($reseller['plan_desc'] ?: '') ?></p>
                </div>
                <?php if ($plan_expired): ?>
                    <span class="px-3 py-1 bg-red-500/30 text-red-100 text-xs font-bold rounded-full border border-red-400/30">EXPIRED</span>
                <?php else: ?>
                    <span class="px-3 py-1 bg-white/20 text-white text-xs font-bold rounded-full border border-white/20"><?= round($days_left) ?> days left</span>
                <?php endif; ?>
            </div>

            <!-- Usage Bar -->
            <div class="mb-4">
                <div class="flex justify-between text-xs mb-2">
                    <span class="text-<?= get_config('theme_color') ?>-200">License Usage</span>
                    <span class="font-bold"><?= $licenses_used ?> / <?= $plan_limit ?> used</span>
                </div>
                <div class="h-2.5 bg-white/20 rounded-full overflow-hidden">
                    <div class="progress-bar h-full rounded-full <?= $plan_pct >= 90 ? 'bg-red-400' : 'bg-white' ?>" style="width: <?= $plan_pct ?>%"></div>
                </div>
                <p class="text-[10px] text-<?= get_config('theme_color') ?>-200 mt-1"><?= $plan_pct ?>% used &bull; <?= max(0, $plan_limit - $licenses_used) ?> remaining</p>
            </div>

            <!-- Plan Stats -->
            <div class="grid grid-cols-3 gap-3 mt-6">
                <div class="bg-white/10 rounded-xl p-3 text-center border border-white/10">
                    <p class="text-lg font-black"><?= number_format($reseller['license_limit']) ?></p>
                    <p class="text-[10px] text-<?= get_config('theme_color') ?>-200 mt-0.5">Base Licenses</p>
                </div>
                <div class="bg-white/10 rounded-xl p-3 text-center border border-white/10">
                    <p class="text-lg font-black">+<?= number_format($reseller['extra_licenses'] ?? 0) ?></p>
                    <p class="text-[10px] text-<?= get_config('theme_color') ?>-200 mt-0.5">Extra Added</p>
                </div>
                <div class="bg-white/10 rounded-xl p-3 text-center border border-white/10">
                    <p class="text-base font-black"><?= $reseller['plan_expiry'] ? date('d M Y', strtotime($reseller['plan_expiry'])) : 'N/A' ?></p>
                    <p class="text-[10px] text-<?= get_config('theme_color') ?>-200 mt-0.5">Expires On</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col gap-3">
            <h3 class="font-bold text-gray-800 text-sm mb-1">Quick Actions</h3>

            <?php $remaining = $plan_limit - $licenses_used; ?>
            <?php if ($remaining <= 2 || $plan_pct >= 80): ?>
            <div class="p-3 bg-orange-50 border border-orange-100 rounded-xl text-xs text-orange-700 flex items-start gap-2">
                <i class="fas fa-exclamation-triangle mt-0.5 flex-shrink-0"></i>
                <span>Running low on licenses. Buy extras or upgrade your plan.</span>
            </div>
            <?php endif; ?>

            <!-- ✅ FIXED: filename only, no path, no .php -->
            <button onclick="openModal('Buy Extra Licenses', 'buy_extra_licenses_modal')"
                    class="w-full px-4 py-3 bg-<?= get_config('theme_color') ?>-50 hover:bg-<?= get_config('theme_color') ?>-100 text-<?= get_config('theme_color') ?>-700 font-bold text-sm rounded-xl transition-all flex items-center gap-2.5 border border-<?= get_config('theme_color') ?>-100">
                <i class="fas fa-plus-circle text-<?= get_config('theme_color') ?>-500"></i>
                Buy Extra Licenses
                <span class="ml-auto text-[10px] text-<?= get_config('theme_color') ?>-400"><?= $sym ?><?= number_format($reseller['extra_license_price'] ?? 5, 2) ?>/each</span>
            </button>

            <button onclick="openModal('Upgrade Plan', 'upgrade_plan_modal')"
                    class="w-full px-4 py-3 bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold text-sm rounded-xl transition-all flex items-center gap-2.5 border border-purple-100">
                <i class="fas fa-arrow-circle-up text-purple-500"></i>
                Upgrade Plan
            </button>

            <button onclick="openModal('My Invoice History', 'my_invoices_modal')"
                    class="w-full px-4 py-3 bg-gray-50 hover:bg-gray-100 text-gray-600 font-bold text-sm rounded-xl transition-all flex items-center gap-2.5 border border-gray-100">
                <i class="fas fa-file-invoice text-gray-400"></i>
                View All Invoices
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Available Plans -->
    <div class="mb-8">
        <h2 class="text-base font-bold text-gray-800 mb-4"><?= $has_plan ? 'Available Plans (for upgrade)' : 'Choose a Plan' ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <?php
            $colors = ['blue','green','purple','orange','pink','indigo'];
            $ci     = 0;
            while ($plan = $all_plans->fetch_assoc()):
                $color      = $colors[$ci % count($colors)]; $ci++;
                $is_current = $has_plan && $reseller['plan_id'] == $plan['id'];
            ?>
            <div class="bg-white border <?= $is_current ? 'border-'.get_config('theme_color').'-300 shadow-md' : 'border-gray-100 shadow-sm' ?> rounded-2xl overflow-hidden hover:shadow-md transition-all relative">
                <?php if ($is_current): ?>
                <div class="absolute top-3 right-3">
                    <span class="px-2 py-0.5 bg-<?= get_config('theme_color') ?>-100 text-<?= get_config('theme_color') ?>-700 text-[10px] font-black rounded-full uppercase">Current</span>
                </div>
                <?php endif; ?>
                <div class="h-1.5 bg-<?= $color ?>-400"></div>
                <div class="p-5">
                    <h3 class="font-black text-gray-800 text-base"><?= htmlspecialchars($plan['plan_name']) ?></h3>
                    <div class="mt-2 mb-4">
                        <span class="text-2xl font-black text-gray-800"><?= $sym ?><?= number_format($plan['price'], 2) ?></span>
                        <span class="text-xs text-gray-400"> / <?= $plan['validity_days'] == 365 ? 'year' : $plan['validity_days'].' days' ?></span>
                    </div>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed"><?= htmlspecialchars($plan['description'] ?: '') ?></p>
                    <div class="space-y-2 mb-5 text-xs text-gray-600">
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i><?= number_format($plan['license_limit']) ?> Licenses</div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i><?= $plan['validity_days'] ?> Days Validity</div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i>Extra @ <?= $sym ?><?= number_format($plan['extra_license_price'], 2) ?>/each</div>
                    </div>
                    <?php if (!$is_current): ?>
                    <!-- ✅ FIXED: passing plan_id as data object -->
                    <button onclick="openModal('<?= $has_plan ? 'Upgrade to ' : 'Subscribe: ' ?><?= htmlspecialchars($plan['plan_name']) ?>', 'upgrade_plan_modal', {plan_id: <?= $plan['id'] ?>})"
                            class="w-full py-2.5 bg-<?= $color ?>-100 hover:bg-<?= $color ?>-200 text-<?= $color ?>-700 font-bold text-xs rounded-xl transition-all">
                        <?= $has_plan ? 'Upgrade to this Plan' : 'Choose this Plan' ?>
                    </button>
                    <?php else: ?>
                    <button disabled class="w-full py-2.5 bg-gray-50 text-gray-400 font-bold text-xs rounded-xl cursor-not-allowed">
                        <i class="fas fa-check mr-1"></i> Current Plan
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Recent Payment History -->
    <?php if ($my_payments && $my_payments->num_rows > 0): ?>
    <div class="bg-white border border-gray-100 shadow-sm rounded-xl overflow-hidden">
        <div class="p-5 border-b border-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-sm">Recent Payment History</h3>
            <button onclick="openModal('My Invoice History', 'my_invoices_modal')" class="text-xs font-bold text-<?= get_config('theme_color') ?>-600 hover:underline">View All</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase text-left">Invoice</th>
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase text-left">Type</th>
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase text-left">Amount</th>
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase text-left">Method</th>
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase text-center">Status</th>
                        <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase text-right">View</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                <?php
                    $type_labels = ['plan_purchase' => 'Plan Purchase', 'plan_upgrade' => 'Plan Upgrade', 'extra_licenses' => 'Extra Licenses'];
                    $s_map       = ['pending' => 'bg-yellow-50 text-yellow-600', 'approved' => 'bg-green-50 text-green-600', 'rejected' => 'bg-red-50 text-red-500', 'paid' => 'bg-blue-50 text-blue-600'];
                    while ($pay = $my_payments->fetch_assoc()):
                        $s_cls = $s_map[$pay['payment_status']] ?? 'bg-gray-50 text-gray-500';
                ?>
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-3 font-mono text-xs font-bold text-<?= get_config('theme_color') ?>-600"><?= htmlspecialchars($pay['invoice_no']) ?></td>
                    <td class="px-5 py-3 text-xs text-gray-600"><?= $type_labels[$pay['payment_type']] ?? $pay['payment_type'] ?></td>
                    <td class="px-5 py-3 font-bold text-gray-800"><?= $sym ?><?= number_format($pay['amount'], 2) ?></td>
                    <td class="px-5 py-3 text-xs text-gray-500"><?= $pay['payment_method'] === 'razorpay' ? '💳 Razorpay' : '🏦 Bank' ?></td>
                    <td class="px-5 py-3 text-center">
                        <span class="px-2.5 py-1 <?= $s_cls ?> text-[10px] font-bold rounded-full uppercase"><?= ucfirst($pay['payment_status']) ?></span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <!-- ✅ FIXED -->
                        <button onclick="openModal('Invoice Details', 'view_invoice_modal', {id: <?= $pay['id'] ?>})"
                                class="text-xs text-<?= get_config('theme_color') ?>-600 hover:underline font-bold">View</button>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</main>
</div>
<?php include 'sections/common_modal.php'; include 'sections/footer.php'; ?>
