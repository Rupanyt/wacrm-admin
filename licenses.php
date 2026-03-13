<?php
include 'include/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$role     = $_SESSION['role'];
$username = $_SESSION['username'];
$name     = $_SESSION['name'];
$title    = "License Management";

// ── RESELLER PLAN CHECK ───────────────────────────────────────────────────
$plan_blocked  = false;
$plan_msg      = '';
$plan_msg_type = ''; // 'no_plan' | 'expired' | 'quota'
$quota_used    = 0;
$quota_limit   = 0;

if ($role === 'reseller') {
    $reseller_data = $conn->query("
        SELECT u.plan_id, u.plan_expiry, u.extra_licenses,
               rp.plan_name, rp.license_limit
        FROM users u
        LEFT JOIN reseller_plans rp ON u.plan_id = rp.id
        WHERE u.id = '$user_id'
    ")->fetch_assoc();

    $quota_used  = (int)$conn->query("SELECT COUNT(*) FROM licenses WHERE created_by='$user_id'")->fetch_row()[0];
    $quota_limit = ((int)($reseller_data['license_limit'] ?? 0)) + ((int)($reseller_data['extra_licenses'] ?? 0));

    if (empty($reseller_data['plan_id'])) {
        $plan_blocked  = true;
        $plan_msg_type = 'no_plan';
        $plan_msg      = 'You do not have an active plan. Please subscribe to a plan to start generating licenses.';
    } elseif (!empty($reseller_data['plan_expiry']) && date('Y-m-d') > $reseller_data['plan_expiry']) {
        $plan_blocked  = true;
        $plan_msg_type = 'expired';
        $plan_msg      = 'Your plan <strong>' . htmlspecialchars($reseller_data['plan_name']) . '</strong> expired on <strong>' . date('d M Y', strtotime($reseller_data['plan_expiry'])) . '</strong>. Please renew to generate new licenses.';
    } elseif ($quota_used >= $quota_limit && $quota_limit > 0) {
        $plan_blocked  = true;
        $plan_msg_type = 'quota';
        $plan_msg      = 'License quota reached! You\'ve used <strong>' . $quota_used . ' of ' . $quota_limit . '</strong> licenses on your <strong>' . htmlspecialchars($reseller_data['plan_name']) . '</strong> plan. Buy extra licenses or upgrade.';
    }
}
// ─────────────────────────────────────────────────────────────────────────

$limit  = 10;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

if ($role == 'super_admin') {
    $count_sql = "SELECT COUNT(*) FROM licenses";
    $base_sql  = "SELECT l.*, u.username AS creator FROM licenses l
                  JOIN users u ON l.created_by = u.id";
} elseif ($role == 'admin') {
    $count_sql = "SELECT COUNT(*) FROM licenses WHERE created_by = '$user_id'
                  OR created_by IN (SELECT id FROM users WHERE parent_id = '$user_id')";
    $base_sql  = "SELECT l.*, u.username AS creator FROM licenses l
                  JOIN users u ON l.created_by = u.id
                  WHERE l.created_by = '$user_id'
                  OR l.created_by IN (SELECT id FROM users WHERE parent_id = '$user_id')";
} else {
    $count_sql = "SELECT COUNT(*) FROM licenses WHERE created_by = '$user_id'";
    $base_sql  = "SELECT l.*, u.username AS creator FROM licenses l
                  JOIN users u ON l.created_by = u.id
                  WHERE l.created_by = '$user_id'";
}

$total_results = $conn->query($count_sql)->fetch_row()[0];
$total_pages   = ceil($total_results / $limit);
$final_query   = $base_sql . " ORDER BY l.id DESC LIMIT $limit OFFSET $offset";
$result        = $conn->query($final_query);

$today_date = date('Y-m-d');

if ($role == 'super_admin') {
    $c_where = "1=1";
} elseif ($role == 'admin') {
    $c_where = "(created_by = '$user_id' OR created_by IN (SELECT id FROM users WHERE parent_id = '$user_id'))";
} else {
    $c_where = "created_by = '$user_id'";
}

$stat_total    = $conn->query("SELECT COUNT(*) FROM licenses WHERE $c_where")->fetch_row()[0];
$stat_active   = $conn->query("SELECT COUNT(*) FROM licenses WHERE $c_where AND status = 'active' AND expiry_date >= '$today_date'")->fetch_row()[0];
$stat_disabled = $stat_total - $stat_active;
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
    <link rel="apple-touch-icon" href="<?= get_config('circle_logo_path') ?>">
    <link rel="shortcut icon" href="<?= get_config('circle_logo_path') ?>" type="image/x-icon">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; overflow: hidden; }
        .nav-item { white-space: nowrap; overflow: hidden; transition: all 0.2s ease; display: flex; align-items: center; }
        .active-link-white { background-color: #f0fdf4 !important; border: 1px solid #dcfce7; color: #166534 !important; }
        .active-link-white i { color: #22c55e !important; }
        #fullLogo span { transition: opacity 0.2s ease-in-out; }
    </style>
</head>
<body class="flex h-screen">

<?php include ('sections/sidebar.php'); ?>

<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'sections/navbar.php'; ?>

<main class="flex-1 overflow-y-auto p-8 antialiased">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Software Licenses</h1>
            <p class="text-sm text-gray-500 mt-1">Manage and track all generated tool licenses.</p>
        </div>

        <?php if ($role === 'reseller' && $plan_blocked): ?>
        <!-- Blocked button with tooltip -->
        <button disabled title="<?= strip_tags($plan_msg) ?>"
                class="px-5 py-2.5 bg-gray-200 text-gray-400 font-bold text-sm rounded-xl cursor-not-allowed flex items-center gap-2 opacity-70">
            <i class="fas fa-lock"></i> Generate License
        </button>
        <?php else: ?>
        <button onclick="openModal('Generate New License', 'license_modal')"
                class="px-5 py-2.5 bg-<?= get_config('theme_color') ?>-300 hover:bg-<?= get_config('theme_color') ?>-400 text-<?= get_config('theme_color') ?>-900 font-bold text-sm rounded-xl transition-all shadow-sm flex items-center gap-2">
            <i class="fas fa-plus mr-1"></i> Generate License
        </button>
        <?php endif; ?>
    </div>

    <!-- ── PLAN ALERT BANNER (reseller only) ─────────────────────────────── -->
    <?php if ($role === 'reseller' && $plan_blocked): ?>
    <div class="mb-6 rounded-2xl border overflow-hidden
        <?= $plan_msg_type === 'expired' ? 'border-red-200 bg-red-50' : ($plan_msg_type === 'quota' ? 'border-orange-200 bg-orange-50' : 'border-blue-200 bg-blue-50') ?>">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-4 p-5">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                <?= $plan_msg_type === 'expired' ? 'bg-red-100 text-red-500' : ($plan_msg_type === 'quota' ? 'bg-orange-100 text-orange-500' : 'bg-blue-100 text-blue-500') ?>">
                <i class="fas <?= $plan_msg_type === 'expired' ? 'fa-calendar-times' : ($plan_msg_type === 'quota' ? 'fa-tachometer-alt' : 'fa-box-open') ?> text-xl"></i>
            </div>
            <div class="flex-1">
                <h3 class="font-bold text-sm
                    <?= $plan_msg_type === 'expired' ? 'text-red-700' : ($plan_msg_type === 'quota' ? 'text-orange-700' : 'text-blue-700') ?>">
                    <?php if ($plan_msg_type === 'no_plan'): ?>No Active Plan<?php elseif ($plan_msg_type === 'expired'): ?>Plan Expired<?php else: ?>License Quota Reached<?php endif; ?>
                </h3>
                <p class="text-xs mt-0.5
                    <?= $plan_msg_type === 'expired' ? 'text-red-600' : ($plan_msg_type === 'quota' ? 'text-orange-600' : 'text-blue-600') ?>">
                    <?= $plan_msg ?>
                </p>
            </div>
            <div class="flex gap-2 flex-shrink-0">
                <?php if ($plan_msg_type === 'no_plan'): ?>
                <a href="reseller_plan" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5">
                    <i class="fas fa-shopping-cart"></i> Choose a Plan
                </a>
                <?php elseif ($plan_msg_type === 'expired'): ?>
                <a href="reseller_plan" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5">
                    <i class="fas fa-redo"></i> Renew Plan
                </a>
                <?php else: ?>
                <a href="reseller_plan" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5">
                    <i class="fas fa-plus-circle"></i> Buy More Licenses
                </a>
                <a href="reseller_plan" class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5">
                    <i class="fas fa-arrow-circle-up"></i> Upgrade Plan
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── QUOTA PROGRESS BAR (reseller with active plan) ────────────────── -->
    <?php if ($role === 'reseller' && !$plan_blocked && isset($reseller_data) && !empty($reseller_data['plan_id'])): ?>
    <?php
        $q_pct = ($quota_limit > 0) ? min(100, round(($quota_used / $quota_limit) * 100)) : 0;
        $bar_color = $q_pct >= 90 ? 'bg-red-500' : ($q_pct >= 70 ? 'bg-orange-400' : 'bg-green-500');
    ?>
    <div class="mb-6 bg-white border border-gray-100 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-9 h-9 bg-<?= get_config('theme_color') ?>-50 text-<?= get_config('theme_color') ?>-600 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-id-badge text-sm"></i>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex justify-between items-center mb-1">
                <p class="text-xs font-bold text-gray-600">
                    Plan: <span class="text-<?= get_config('theme_color') ?>-600"><?= htmlspecialchars($reseller_data['plan_name']) ?></span>
                    &nbsp;&bull;&nbsp; Expires: <span class="<?= (strtotime($reseller_data['plan_expiry']) - time()) < 604800 ? 'text-red-500' : 'text-gray-500' ?>"><?= date('d M Y', strtotime($reseller_data['plan_expiry'])) ?></span>
                </p>
                <p class="text-xs font-bold text-gray-600 flex-shrink-0 ml-3"><?= $quota_used ?> / <?= $quota_limit ?> used</p>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all <?= $bar_color ?>" style="width: <?= $q_pct ?>%"></div>
            </div>
        </div>
        <a href="reseller_plan" class="text-[11px] font-bold text-<?= get_config('theme_color') ?>-600 hover:underline flex-shrink-0">Manage</a>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-key"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Licenses</p>
                <h3 class="text-2xl font-black text-gray-800"><?= $stat_total ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="w-12 h-12 bg-green-50 text-green-500 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="flex items-center gap-2">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Active Now</p>
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                </div>
                <h3 class="text-2xl font-black text-gray-800"><?= $stat_active ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="w-12 h-12 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Expired / Disabled</p>
                <h3 class="text-2xl font-black text-gray-800"><?= $stat_disabled ?></h3>
            </div>
        </div>
    </div>

    <!-- License Table -->
    <div class="bg-white border border-gray-100 shadow-sm rounded-xl overflow-hidden">

        <div class="p-5 border-b border-gray-50 bg-gray-50/30">
            <div class="relative w-full md:w-72">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="licenseSearch" onkeyup="filterTable()" placeholder="Search client, key or tool..."
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:border-<?= get_config('theme_color') ?>-300 outline-none transition-all">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="mainLicenseTable">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Client & Software</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">License Key</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Generator</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                    <?php if ($result && $result->num_rows > 0): while ($row = $result->fetch_assoc()):
                        $today           = date('Y-m-d');
                        $expiry_date     = $row['expiry_date'];
                        $current_db_status = $row['status'];

                        if ($current_db_status == 'blocked') {
                            $display_status = 'Disabled';
                            $s_config = ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'dot' => 'bg-red-400', 'pulse' => ''];
                        } elseif ($today > $expiry_date) {
                            $display_status = 'Expired';
                            $s_config = ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'dot' => 'bg-red-500', 'pulse' => ''];
                        } else {
                            $display_status = 'Active';
                            $s_config = ['bg' => 'bg-green-50', 'text' => 'text-green-600', 'dot' => 'bg-green-500', 'pulse' => 'animate-pulse'];
                        }
                    ?>
                    <tr class="hover:bg-<?= get_config('theme_color') ?>-50/30 transition-all group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 group-hover:bg-<?= get_config('theme_color') ?>-100 group-hover:text-<?= get_config('theme_color') ?>-600 transition-colors">
                                    <i class="fas fa-desktop text-xs"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800 tracking-tight"><?= $row['client_name'] ?></div>
                                    <div class="text-[11px] text-gray-400 font-medium"><?= $row['software_name'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <code class="bg-gray-50 px-3 py-1.5 rounded-md text-[12px] font-mono border border-gray-200 text-gray-600 select-all">
                                    <?= $row['license_key'] ?>
                                </code>
                                <button onclick="copyToClipboard('<?= $row['license_key'] ?>')"
                                        class="text-gray-400 hover:text-<?= get_config('theme_color') ?>-600 p-1.5 rounded-md hover:bg-<?= get_config('theme_color') ?>-50 transition-all" title="Copy Key">
                                    <i class="far fa-copy"></i>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-<?= get_config('theme_color') ?>-50 text-<?= get_config('theme_color') ?>-600 flex items-center justify-center text-[10px] font-bold">
                                    <?= strtoupper(substr($row['creator'], 0, 1)) ?>
                                </div>
                                <span class="text-gray-600 font-medium text-xs"><?= $row['creator'] ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4" id="status-container-<?= $row['id'] ?>">
                            <span onclick="toggleStatus(<?= $row['id'] ?>, '<?= $current_db_status ?>')"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 <?= $s_config['bg'] ?> <?= $s_config['text'] ?> text-[10px] font-bold rounded-full uppercase cursor-pointer hover:opacity-80 transition-all shadow-sm border border-transparent hover:border-current">
                                <span class="w-1.5 h-1.5 rounded-full <?= $s_config['dot'] ?> <?= $s_config['pulse'] ?>"></span>
                                <span id="status-text-<?= $row['id'] ?>"><?= $display_status ?></span>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-1">
                                <button onclick="openModal('Edit License', 'edit_license_modal', {id: <?= $row['id'] ?>})"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition-all">
                                    <i class="fas fa-pencil-alt text-xs"></i>
                                </button>
                                <button onclick="openModal('Confirm Delete', 'delete_confirm_modal', {id: <?= $row['id'] ?>})"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-600 transition-all">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center text-gray-300">
                                    <i class="fas fa-folder-open text-xl"></i>
                                </div>
                                <p class="text-gray-400 italic text-sm">No license records found in database.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="p-6 border-t border-gray-50 flex flex-col md:flex-row items-center justify-between gap-4 bg-white rounded-b-xl">
                <p class="text-[12px] font-medium text-gray-500">
                    Showing <span class="text-gray-800"><?= ($offset + 1) ?></span> to
                    <span class="text-gray-800"><?= min($offset + $limit, $total_results) ?></span> of
                    <span class="text-gray-800"><?= $total_results ?></span> licenses
                </p>
                <div class="flex items-center gap-1">
                    <a href="?page=<?= ($page - 1) ?>"
                       class="<?= ($page <= 1) ? 'pointer-events-none opacity-50' : '' ?> w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-400 hover:bg-gray-50 transition-all">
                        <i class="fas fa-chevron-left text-[10px]"></i>
                    </a>
                    <?php
                    $range = 2;
                    for ($i = 1; $i <= $total_pages; $i++) {
                        if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
                            $activeClass = ($i == $page)
                                ? 'bg-'.get_config('theme_color').'-100 text-'.get_config('theme_color').'-700 border-'.get_config('theme_color').'-200 font-bold'
                                : 'border-gray-200 text-gray-600 hover:bg-gray-50';
                            echo '<a href="?page='.$i.'" class="w-8 h-8 flex items-center justify-center border '.$activeClass.' rounded-lg text-xs transition-all">'.$i.'</a>';
                        } elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
                            echo '<span class="px-2 text-gray-400 text-xs">...</span>';
                        }
                    }
                    ?>
                    <a href="?page=<?= ($page + 1) ?>"
                       class="<?= ($page >= $total_pages) ? 'pointer-events-none opacity-50' : '' ?> w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-400 hover:bg-gray-50 transition-all">
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>
</div>

<script>
function toggleStatus(id, currentStatus) {
    const statusText = document.getElementById('status-text-' + id);
    const originalText = statusText.innerText;
    statusText.innerText = "Updating....";

    $.ajax({
        url: 'api/license_api',
        type: 'POST',
        data: { action: 'toggle_status', id: id, status: currentStatus },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(function() { location.reload(); }, 2000);
            } else {
                showToast(res.message, 'error');
                statusText.innerText = originalText;
            }
        },
        error: function() {
            showToast("Server connection error occurred", "error");
            statusText.innerText = originalText;
        }
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast("License key copied to clipboard!", "success");
    }).catch(() => {
        showToast("Failed to copy key", "error");
    });
}
</script>

<?php
    include 'sections/common_modal.php';
    include 'sections/footer.php';
?>
