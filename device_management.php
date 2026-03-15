<?php
// device_management.php
include 'include/config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','admin'])) {
    header('Location: dashboard'); exit();
}
$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

$strict_mode = get_config('ext_device_strict_mode') ?: '0';

// ── Stats ─────────────────────────────────────────────────────
$total_active    = $conn->query("SELECT COUNT(*) FROM licenses WHERE status='active'")->fetch_row()[0];
$device_bound    = $conn->query("SELECT COUNT(*) FROM licenses WHERE status='active' AND device_id IS NOT NULL AND device_id != '' AND device_id NOT REGEXP '^[0-9]+$'")->fetch_row()[0];
$never_logged_in = $conn->query("SELECT COUNT(*) FROM licenses WHERE status='active' AND (device_id IS NULL OR device_id = '' OR device_id REGEXP '^[0-9]+$')")->fetch_row()[0];

// ── Pagination ────────────────────────────────────────────────
$page     = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset   = ($page - 1) * $per_page;

$search  = trim($_GET['search'] ?? '');
$filter  = $_GET['filter'] ?? 'all'; // all | bound | unbound

$where_parts = [];

// role scope
if ($role === 'admin') {
    $where_parts[] = "(l.created_by = $user_id OR l.created_by IN (SELECT id FROM users WHERE parent_id = $user_id))";
}

// search
if (!empty($search)) {
    $s = $conn->real_escape_string($search);
    $where_parts[] = "(l.license_key LIKE '%$s%' OR l.client_name LIKE '%$s%' OR l.client_mobile LIKE '%$s%')";
}

// filter
switch ($filter) {
    case 'bound':
        $where_parts[] = "l.device_id IS NOT NULL AND l.device_id != '' AND l.device_id NOT REGEXP '^[0-9]+\$'";
        break;
    case 'unbound':
        $where_parts[] = "(l.device_id IS NULL OR l.device_id = '' OR l.device_id REGEXP '^[0-9]+\$')";
        break;
}

$where_parts[] = "l.status = 'active'";
$where_sql = 'WHERE ' . implode(' AND ', $where_parts);

$total_count = $conn->query("SELECT COUNT(*) FROM licenses l $where_sql")->fetch_row()[0];
$total_pages = max(1, ceil($total_count / $per_page));

$licenses = $conn->query(
    "SELECT l.*, u.username as owner_username
     FROM licenses l
     LEFT JOIN users u ON l.created_by = u.id
     $where_sql
     ORDER BY l.filed_2 DESC, l.created_at DESC
     LIMIT $per_page OFFSET $offset"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Management | <?= get_config('site_name') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/png" href="<?= get_config('circle_logo_path') ?>">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { background:#f8fafc; font-family:'Inter',sans-serif; overflow:hidden; }
        .nav-item { white-space:nowrap; overflow:hidden; transition:all .2s; display:flex; align-items:center; }
        .active-link-white { background:#f0fdf4!important; border:1px solid #dcfce7; color:#166534!important; }
        .active-link-white i { color:#22c55e!important; }
    </style>
</head>
<body class="flex h-screen">
<?php include 'sections/sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'sections/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-8 antialiased">

<!-- Header -->
<div class="flex items-start justify-between mb-8">
    <div>
        <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-mobile-alt text-indigo-500"></i> Device Management
        </h1>
        <p class="text-sm text-gray-500 mt-1">Control which device each license key is bound to.</p>
    </div>
</div>

<!-- Strict Mode Banner -->
<div class="rounded-2xl border p-5 mb-6 flex items-center justify-between
            <?= $strict_mode === '1' ? 'bg-indigo-50 border-indigo-200' : 'bg-gray-50 border-gray-200' ?>">
    <div class="flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center text-lg
                    <?= $strict_mode === '1' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-200 text-gray-500' ?>">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div>
            <h3 class="font-black text-gray-800 text-sm">Device Strict Mode</h3>
            <p class="text-[12px] text-gray-500 mt-0.5">
                <?php if ($strict_mode === '1'): ?>
                    <span class="text-indigo-600 font-bold">ENABLED</span> — Each license can only be used on the device it first logged in from. Login attempts from other devices are rejected.
                <?php else: ?>
                    <span class="text-gray-500 font-bold">DISABLED</span> — Licenses can log in from any device freely. Enable to lock each license to one device.
                <?php endif; ?>
            </p>
        </div>
    </div>
    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
        <input type="checkbox" id="strictModeToggle" class="sr-only peer" <?= $strict_mode === '1' ? 'checked' : '' ?>>
        <div class="w-14 h-7 bg-gray-300 peer-checked:bg-indigo-600 rounded-full peer transition-all duration-200"></div>
        <div class="absolute left-0.5 top-0.5 w-6 h-6 bg-white rounded-full shadow transition-all peer-checked:translate-x-7"></div>
    </label>
</div>

<!-- Stats -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <?php
    $stats = [
        ['Active Licenses',   $total_active,    'fas fa-key',          'blue'],
        ['Device Bound',      $device_bound,    'fas fa-link',         'green'],
        ['Never Logged In',   $never_logged_in, 'fas fa-user-slash',   'gray'],
    ];
    foreach ($stats as [$label, $val, $icon, $c]):
    ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
        <div class="w-9 h-9 bg-<?= $c ?>-50 text-<?= $c ?>-500 rounded-xl flex items-center justify-center text-sm">
            <i class="<?= $icon ?>"></i>
        </div>
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider"><?= $label ?></p>
            <h3 class="text-lg font-black text-gray-800"><?= number_format($val) ?></h3>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Table card -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">

    <!-- Toolbar -->
    <div class="flex items-center gap-3 p-5 border-b border-gray-100">
        <div class="relative flex-1 max-w-sm">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            <input type="text" id="searchInput" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search license key, name, mobile..."
                   class="w-full pl-8 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
        </div>
        <!-- Filter tabs -->
        <div class="flex bg-gray-100 rounded-xl p-1 gap-1 text-xs font-bold">
            <?php foreach (['all'=>'All', 'bound'=>'Bound', 'unbound'=>'Unbound'] as $k => $v): ?>
            <a href="?filter=<?= $k ?>&search=<?= urlencode($search) ?>"
               class="px-3 py-1.5 rounded-lg transition-all <?= $filter === $k ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                <?= $v ?>
            </a>
            <?php endforeach; ?>
        </div>
        <button onclick="unlockAll()" class="ml-auto px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-xl transition-all border border-red-100 flex items-center gap-1.5">
            <i class="fas fa-unlink text-[10px]"></i> Unlock All Selected
        </button>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="py-3 pl-5 pr-3 text-left"><input type="checkbox" id="selectAll" class="rounded"></th>
                    <th class="py-3 px-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">License Key</th>
                    <th class="py-3 px-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Client</th>
                    <th class="py-3 px-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Device Status</th>
                    <th class="py-3 px-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Bound Device ID</th>
                    <th class="py-3 px-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Last Login</th>
                    <th class="py-3 px-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody id="licenseTableBody" class="divide-y divide-gray-50">
            <?php
            if ($licenses && $licenses->num_rows > 0):
                while ($lic = $licenses->fetch_assoc()):
                    // Determine if device_id looks like an actual device ID or old counter
                    $dev = trim($lic['device_id'] ?? '');
                    $is_bound = !empty($dev) && !preg_match('/^\d+$/', $dev);
                    $last_login = $lic['filed_2'] ?? null;
            ?>
            <tr class="hover:bg-gray-50/50 transition-all group" data-id="<?= $lic['id'] ?>">
                <td class="py-3.5 pl-5 pr-3">
                    <input type="checkbox" class="row-checkbox rounded" value="<?= $lic['id'] ?>">
                </td>
                <td class="py-3.5 px-3">
                    <span class="font-mono text-xs font-bold text-gray-700 bg-gray-100 px-2 py-1 rounded-lg"><?= htmlspecialchars($lic['license_key']) ?></span>
                </td>
                <td class="py-3.5 px-3">
                    <p class="font-semibold text-gray-700 text-xs"><?= htmlspecialchars($lic['client_name'] ?? '—') ?></p>
                    <p class="text-[11px] text-gray-400"><?= htmlspecialchars($lic['client_mobile'] ?? '') ?></p>
                </td>
                <td class="py-3.5 px-3">
                    <?php if ($is_bound): ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-50 text-green-700 border border-green-100 rounded-full text-[11px] font-bold">
                            <i class="fas fa-lock text-[9px]"></i> Bound
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-50 text-gray-500 border border-gray-200 rounded-full text-[11px] font-bold">
                            <i class="fas fa-unlock text-[9px]"></i> Free
                        </span>
                    <?php endif; ?>
                </td>
                <td class="py-3.5 px-3">
                    <?php if ($is_bound): ?>
                        <span class="font-mono text-[10px] text-gray-500 bg-gray-100 px-2 py-1 rounded max-w-[140px] truncate block" title="<?= htmlspecialchars($dev) ?>">
                            <?= htmlspecialchars(substr($dev, 0, 24)) . (strlen($dev) > 24 ? '…' : '') ?>
                        </span>
                    <?php else: ?>
                        <span class="text-gray-300 text-xs">—</span>
                    <?php endif; ?>
                </td>
                <td class="py-3.5 px-3 text-[11px] text-gray-500">
                    <?= $last_login ? date('d M y, h:i A', strtotime($last_login)) : '—' ?>
                </td>
                <td class="py-3.5 px-3 text-right">
                    <?php if ($is_bound): ?>
                    <button onclick="unlockDevice(<?= $lic['id'] ?>, this)"
                            class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-[11px] font-bold rounded-lg transition-all border border-red-100 flex items-center gap-1 ml-auto">
                        <i class="fas fa-unlink text-[10px]"></i> Unlock
                    </button>
                    <?php else: ?>
                    <span class="text-gray-300 text-xs pr-1">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile;
            else: ?>
            <tr>
                <td colspan="7" class="py-16 text-center text-gray-400">
                    <i class="fas fa-mobile-alt text-3xl text-gray-200 mb-3 block"></i>
                    No licenses found.
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="flex items-center justify-between px-5 py-3.5 border-t border-gray-100">
        <p class="text-xs text-gray-500">
            Showing <?= ($offset + 1) ?>–<?= min($offset + $per_page, $total_count) ?> of <?= $total_count ?>
        </p>
        <div class="flex gap-1">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>"
               class="w-8 h-8 flex items-center justify-center text-xs rounded-lg transition-all
                      <?= $i === $page ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-gray-100 text-gray-600' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

</main>
</div>

<script>
// ── Strict mode toggle ────────────────────────────────────────
document.getElementById('strictModeToggle').addEventListener('change', function() {
    const val = this.checked ? '1' : '0';
    $.post('api/device_management_api.php', { action: 'toggle_strict_mode', value: val }, function(res) {
        showToast(res.message, res.status);
        setTimeout(() => location.reload(), 800);
    }, 'json').fail(() => showToast('Failed to save.', 'error'));
});

// ── Unlock single device ──────────────────────────────────────
function unlockDevice(id, btn) {
    if (!confirm('Unlock this device? The user will need to log in again from their device.')) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin text-[10px]"></i>';
    $.post('api/device_management_api.php', { action: 'unlock_device', id: id }, function(res) {
        showToast(res.message, res.status);
        if (res.status === 'success') {
            const row = btn.closest('tr');
            row.querySelector('td:nth-child(4)').innerHTML = `
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-50 text-gray-500 border border-gray-200 rounded-full text-[11px] font-bold">
                    <i class="fas fa-unlock text-[9px]"></i> Free
                </span>`;
            row.querySelector('td:nth-child(5)').innerHTML = '<span class="text-gray-300 text-xs">—</span>';
            row.querySelector('td:nth-child(7)').innerHTML = '<span class="text-gray-300 text-xs pr-1">—</span>';
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-unlink text-[10px]"></i> Unlock';
        }
    }, 'json');
}

// ── Unlock all selected ───────────────────────────────────────
function unlockAll() {
    const ids = [...document.querySelectorAll('.row-checkbox:checked')].map(el => el.value);
    if (!ids.length) { showToast('Select at least one license.', 'error'); return; }
    if (!confirm(`Unlock ${ids.length} device(s)?`)) return;
    $.post('api/device_management_api.php', { action: 'unlock_bulk', ids: ids }, function(res) {
        showToast(res.message, res.status);
        if (res.status === 'success') setTimeout(() => location.reload(), 800);
    }, 'json');
}

// ── Select all checkbox ───────────────────────────────────────
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.row-checkbox').forEach(c => c.checked = this.checked);
});

// ── Search on enter ───────────────────────────────────────────
document.getElementById('searchInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        location.href = `?filter=<?= $filter ?>&search=${encodeURIComponent(this.value)}`;
    }
});
</script>

<?php include 'sections/common_modal.php'; include 'sections/footer.php'; ?>
