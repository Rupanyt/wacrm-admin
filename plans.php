<?php
include 'include/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: dashboard"); exit();
}

$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$name     = $_SESSION['name'];
$title    = "Reseller Plans";

$plans        = $conn->query("SELECT * FROM reseller_plans ORDER BY price ASC");
$total_plans  = $conn->query("SELECT COUNT(*) FROM reseller_plans")->fetch_row()[0];
$active_plans = $conn->query("SELECT COUNT(*) FROM reseller_plans WHERE status='active'")->fetch_row()[0];
$sym          = get_config('currency_symbol') ?: '$';
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
            <h1 class="text-xl font-bold text-gray-800">Reseller Plans</h1>
            <p class="text-sm text-gray-500 mt-1">Create and manage subscription plans for resellers.</p>
        </div>
        <button onclick="openModal('Create New Plan', 'plan_modal')"
                class="px-5 py-2.5 bg-<?= get_config('theme_color') ?>-300 hover:bg-<?= get_config('theme_color') ?>-400 text-<?= get_config('theme_color') ?>-900 font-bold text-sm rounded-xl transition-all shadow-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> New Plan
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-xl">
                <i class="fas fa-boxes"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Plans</p>
                <h3 class="text-2xl font-black text-gray-800"><?= $total_plans ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 bg-green-50 text-green-500 rounded-xl flex items-center justify-center text-xl">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Active Plans</p>
                <h3 class="text-2xl font-black text-gray-800"><?= $active_plans ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 bg-purple-50 text-purple-500 rounded-xl flex items-center justify-center text-xl">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Resellers On Plans</p>
                <h3 class="text-2xl font-black text-gray-800"><?= $conn->query("SELECT COUNT(*) FROM users WHERE role='reseller' AND plan_id IS NOT NULL")->fetch_row()[0] ?></h3>
            </div>
        </div>
    </div>

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if ($plans && $plans->num_rows > 0):
            $colors = ['blue','green','purple','orange','pink','indigo'];
            $ci = 0;
            while ($plan = $plans->fetch_assoc()):
                $color    = $colors[$ci % count($colors)]; $ci++;
                $assigned = $conn->query("SELECT COUNT(*) FROM users WHERE role='reseller' AND plan_id='{$plan['id']}'")->fetch_row()[0];
                $is_active = ($plan['status'] == 'active');
        ?>
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden">
            <div class="h-2 bg-<?= $color ?>-400"></div>
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-black text-gray-800"><?= htmlspecialchars($plan['plan_name']) ?></h3>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $is_active ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500' ?>">
                            <span class="w-1.5 h-1.5 rounded-full <?= $is_active ? 'bg-green-500 animate-pulse' : 'bg-red-400' ?>"></span>
                            <?= $is_active ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-black text-gray-800"><?= $sym ?><?= number_format($plan['price'], 2) ?></span>
                        <p class="text-[10px] text-gray-400 font-medium">/<?= $plan['validity_days'] == 365 ? 'year' : $plan['validity_days'].' days' ?></p>
                    </div>
                </div>

                <p class="text-xs text-gray-500 mb-5 leading-relaxed"><?= htmlspecialchars($plan['description'] ?: 'No description.') ?></p>

                <div class="space-y-2.5 mb-6">
                    <div class="flex items-center gap-2.5 text-xs text-gray-600">
                        <i class="fas fa-key w-4 text-<?= $color ?>-400"></i>
                        <span><span class="font-bold text-gray-800"><?= number_format($plan['license_limit']) ?></span> Licenses Included</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-xs text-gray-600">
                        <i class="fas fa-calendar w-4 text-<?= $color ?>-400"></i>
                        <span>Valid for <span class="font-bold text-gray-800"><?= $plan['validity_days'] ?> days</span></span>
                    </div>
                    <div class="flex items-center gap-2.5 text-xs text-gray-600">
                        <i class="fas fa-plus-circle w-4 text-<?= $color ?>-400"></i>
                        <span>Extra license: <span class="font-bold text-gray-800"><?= $sym ?><?= number_format($plan['extra_license_price'], 2) ?></span>/each</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-xs text-gray-600">
                        <i class="fas fa-users w-4 text-<?= $color ?>-400"></i>
                        <span><span class="font-bold text-gray-800"><?= $assigned ?></span> Resellers Using</span>
                    </div>
                </div>

                <div class="flex gap-2 pt-4 border-t border-gray-50">
                    <!-- ✅ FIXED: passing just filename + data object -->
                    <button onclick="openModal('Edit Plan', 'edit_plan_modal', {id: <?= $plan['id'] ?>})"
                            class="flex-1 px-3 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button onclick="togglePlanStatus(<?= $plan['id'] ?>, '<?= $plan['status'] ?>')"
                            class="px-3 py-2 bg-gray-50 text-gray-500 hover:bg-gray-100 text-xs font-bold rounded-lg transition-all"
                            title="Toggle Status">
                        <i class="fas fa-toggle-<?= $is_active ? 'on text-green-500' : 'off text-gray-400' ?>"></i>
                    </button>
                    <?php if ($assigned == 0): ?>
                    <button onclick="deletePlan(<?= $plan['id'] ?>)"
                            class="px-3 py-2 bg-red-50 text-red-500 hover:bg-red-100 text-xs font-bold rounded-lg transition-all"
                            title="Delete Plan">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; else: ?>
        <div class="col-span-3 bg-white rounded-2xl border border-dashed border-gray-200 p-16 text-center">
            <i class="fas fa-boxes text-gray-200 text-4xl mb-4 block"></i>
            <p class="text-gray-400 font-medium">No plans created yet. Click "New Plan" to get started.</p>
        </div>
        <?php endif; ?>
    </div>

</main>
</div>

<script>
function togglePlanStatus(id, currentStatus) {
    if (!confirm('Change this plan status?')) return;
    $.post('api/plan_api.php', { action: 'toggle_plan_status', id: id, status: currentStatus }, function(res) {
        if (res.status === 'success') { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1200); }
        else showToast(res.message, 'error');
    }, 'json');
}

function deletePlan(id) {
    if (!confirm('Delete this plan permanently? This cannot be undone.')) return;
    $.post('api/plan_api.php', { action: 'delete_plan', id: id }, function(res) {
        if (res.status === 'success') { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1200); }
        else showToast(res.message, 'error');
    }, 'json');
}
</script>

<?php include 'sections/common_modal.php'; include 'sections/footer.php'; ?>
