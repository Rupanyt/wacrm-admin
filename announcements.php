<?php
// announcements.php
include 'include/config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','admin'])) {
    header('Location: dashboard'); exit();
}
$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

// ── Stats ─────────────────────────────────────────────────────
$total   = $conn->query("SELECT COUNT(*) FROM announcements")->fetch_row()[0];
$active  = $conn->query("SELECT COUNT(*) FROM announcements WHERE is_active=1")->fetch_row()[0];
$by_type = [];
foreach (['NOTIFY','MODAL','INBOX','EXTERNAL_PAGE'] as $v) {
    $esc = $conn->real_escape_string($v);
    $by_type[$v] = $conn->query("SELECT COUNT(*) FROM announcements WHERE viewer='$esc' AND is_active=1")->fetch_row()[0];
}

// ── Filters & pagination ──────────────────────────────────────
$page     = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset   = ($page - 1) * $per_page;
$filter_viewer   = $_GET['viewer']   ?? 'all';
$filter_audience = $_GET['audience'] ?? 'all';
$search          = trim($_GET['q']   ?? '');

$where = ['1=1'];
if ($filter_viewer !== 'all') {
    $fv = $conn->real_escape_string($filter_viewer);
    $where[] = "viewer='$fv'";
}
if ($filter_audience !== 'all') {
    $fa = $conn->real_escape_string($filter_audience);
    $where[] = "audience='$fa'";
}
if (!empty($search)) {
    $s = $conn->real_escape_string($search);
    $where[] = "(title LIKE '%$s%' OR statement LIKE '%$s%')";
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

$total_rows  = $conn->query("SELECT COUNT(*) FROM announcements $where_sql")->fetch_row()[0];
$total_pages = max(1, ceil($total_rows / $per_page));

$rows = $conn->query(
    "SELECT a.*, u.username as creator FROM announcements a
     LEFT JOIN users u ON a.created_by = u.id
     $where_sql
     ORDER BY a.sort_order DESC, a.created_at DESC
     LIMIT $per_page OFFSET $offset"
);

// Viewer meta
$viewer_meta = [
    'NOTIFY'        => ['icon' => 'fas fa-bell',          'color' => 'blue',   'label' => 'Announce'],
    'MODAL'         => ['icon' => 'fas fa-window-restore', 'color' => 'purple', 'label' => 'Modal'],
    'INBOX'         => ['icon' => 'fas fa-inbox',          'color' => 'green',  'label' => 'Inbox'],
    'EXTERNAL_PAGE' => ['icon' => 'fas fa-external-link-alt','color'=> 'orange','label' => 'External'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | <?= get_config('site_name') ?></title>
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
        .viewer-badge { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700; padding:3px 8px; border-radius:999px; }
    </style>
</head>
<body class="flex h-screen">
<?php include 'sections/sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'sections/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-8 antialiased">

<!-- Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-bullhorn text-indigo-500"></i> Announcements
        </h1>
        <p class="text-sm text-gray-500 mt-1">Broadcast notifications to Chrome extension users. Served via <code class="bg-gray-100 text-indigo-600 px-1.5 py-0.5 rounded text-xs font-mono">GET /api/notify/get/{type}/{id}</code></p>
    </div>
    <button onclick="openModal('New Announcement','announcement_modal',{})"
            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl flex items-center gap-2 shadow-sm transition-all">
        <i class="fas fa-plus text-xs"></i> New Announcement
    </button>
</div>

<!-- Stats row -->
<div class="grid grid-cols-6 gap-3 mb-6">
    <!-- Total / Active -->
    <div class="col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-indigo-50 text-indigo-500 rounded-xl flex items-center justify-center"><i class="fas fa-bullhorn text-sm"></i></div>
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total / Active</p>
            <h3 class="text-lg font-black text-gray-800"><?= $total ?> <span class="text-sm font-medium text-green-500">/ <?= $active ?></span></h3>
        </div>
    </div>
    <!-- Per viewer type -->
    <?php foreach ($viewer_meta as $vk => $vm): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-2.5">
        <div class="w-8 h-8 bg-<?= $vm['color'] ?>-50 text-<?= $vm['color'] ?>-500 rounded-xl flex items-center justify-center text-xs flex-shrink-0">
            <i class="<?= $vm['icon'] ?>"></i>
        </div>
        <div>
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider"><?= $vm['label'] ?></p>
            <h3 class="text-base font-black text-gray-800"><?= $by_type[$vk] ?></h3>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Table card -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm">

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-3 p-5 border-b border-gray-100">
        <!-- Search -->
        <div class="relative flex-1 min-w-48">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            <input id="searchInput" type="text" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search title or message..."
                   class="w-full pl-8 pr-4 py-2 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
        </div>

        <!-- Viewer filter -->
        <div class="flex bg-gray-100 rounded-xl p-1 gap-0.5 text-xs font-bold flex-shrink-0">
            <?php foreach (['all'=>'All'] + array_map(fn($m)=>$m['label'], $viewer_meta) as $k=>$v): ?>
            <a href="?viewer=<?= $k ?>&audience=<?= $filter_audience ?>&q=<?= urlencode($search) ?>"
               class="px-2.5 py-1.5 rounded-lg transition-all <?= $filter_viewer===$k ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                <?= $v ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Audience filter -->
        <div class="flex bg-gray-100 rounded-xl p-1 gap-0.5 text-xs font-bold flex-shrink-0">
            <?php foreach (['all'=>'All Users','premium'=>'★ Premium','free'=>'Free'] as $k=>$v): ?>
            <a href="?viewer=<?= $filter_viewer ?>&audience=<?= $k ?>&q=<?= urlencode($search) ?>"
               class="px-2.5 py-1.5 rounded-lg transition-all <?= $filter_audience===$k ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                <?= $v ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                    <th class="py-3 pl-6 pr-3 text-left">Title / Message</th>
                    <th class="py-3 px-3 text-left">Viewer</th>
                    <th class="py-3 px-3 text-left">Audience</th>
                    <th class="py-3 px-3 text-left">Schedule</th>
                    <th class="py-3 px-3 text-left">Order</th>
                    <th class="py-3 px-3 text-center">Status</th>
                    <th class="py-3 px-3 pr-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
            <?php if ($rows && $rows->num_rows > 0):
                while ($row = $rows->fetch_assoc()):
                    $vm = $viewer_meta[$row['viewer']] ?? ['icon'=>'fas fa-bell','color'=>'gray','label'=>$row['viewer']];
                    $now_ts = time();
                    $started = empty($row['start_at']) || strtotime($row['start_at']) <= $now_ts;
                    $ended   = !empty($row['end_at'])  && strtotime($row['end_at'])   <  $now_ts;
                    $live    = $row['is_active'] && $started && !$ended;
            ?>
            <tr class="hover:bg-gray-50/40 transition-all" data-id="<?= $row['id'] ?>">
                <td class="py-3.5 pl-6 pr-3 max-w-xs">
                    <p class="font-semibold text-gray-800 text-xs truncate"><?= htmlspecialchars($row['title']) ?></p>
                    <?php if (!empty($row['statement'])): ?>
                    <p class="text-[11px] text-gray-400 mt-0.5 truncate max-w-[220px]"><?= htmlspecialchars($row['statement']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($row['link'])): ?>
                    <a href="<?= htmlspecialchars($row['link']) ?>" target="_blank"
                       class="text-[10px] text-indigo-400 hover:text-indigo-600 mt-0.5 block truncate max-w-[220px]">
                        <i class="fas fa-link mr-0.5"></i><?= htmlspecialchars($row['link']) ?>
                    </a>
                    <?php endif; ?>
                </td>
                <td class="py-3.5 px-3">
                    <span class="viewer-badge bg-<?= $vm['color'] ?>-50 text-<?= $vm['color'] ?>-600 border border-<?= $vm['color'] ?>-100">
                        <i class="<?= $vm['icon'] ?> text-[9px]"></i> <?= $vm['label'] ?>
                    </span>
                </td>
                <td class="py-3.5 px-3">
                    <?php if ($row['audience'] === 'all'): ?>
                        <span class="text-xs text-gray-600 font-semibold">Everyone</span>
                    <?php elseif ($row['audience'] === 'premium'): ?>
                        <span class="text-xs text-yellow-600 font-bold">★ Premium</span>
                    <?php else: ?>
                        <span class="text-xs text-gray-500 font-semibold">Free</span>
                    <?php endif; ?>
                </td>
                <td class="py-3.5 px-3 text-[11px] text-gray-500">
                    <?php if (!empty($row['start_at']) || !empty($row['end_at'])): ?>
                        <?= !empty($row['start_at']) ? date('d M y', strtotime($row['start_at'])) : '∞' ?>
                        →
                        <?= !empty($row['end_at'])   ? date('d M y', strtotime($row['end_at']))   : '∞' ?>
                    <?php else: ?>
                        <span class="text-gray-300">Always</span>
                    <?php endif; ?>
                </td>
                <td class="py-3.5 px-3">
                    <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-lg"><?= intval($row['sort_order']) ?></span>
                </td>
                <td class="py-3.5 px-3 text-center">
                    <?php if (!$row['is_active']): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-50 text-gray-400 border border-gray-200 rounded-full text-[10px] font-bold">
                            <i class="fas fa-pause text-[8px]"></i> Off
                        </span>
                    <?php elseif ($ended): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-50 text-red-400 border border-red-100 rounded-full text-[10px] font-bold">
                            <i class="fas fa-clock text-[8px]"></i> Expired
                        </span>
                    <?php elseif (!$started): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-50 text-yellow-600 border border-yellow-100 rounded-full text-[10px] font-bold">
                            <i class="fas fa-hourglass-start text-[8px]"></i> Scheduled
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-50 text-green-600 border border-green-100 rounded-full text-[10px] font-bold">
                            <i class="fas fa-circle text-[8px]"></i> Live
                        </span>
                    <?php endif; ?>
                </td>
                <td class="py-3.5 px-3 pr-6">
                    <div class="flex items-center justify-end gap-1.5">
                        <!-- Toggle active -->
                        <button onclick="toggleAnnouncement(<?= $row['id'] ?>, <?= $row['is_active'] ? 0 : 1 ?>, this)"
                                title="<?= $row['is_active'] ? 'Deactivate' : 'Activate' ?>"
                                class="w-7 h-7 flex items-center justify-center rounded-lg transition-all text-xs
                                       <?= $row['is_active'] ? 'bg-green-50 text-green-600 hover:bg-green-100' : 'bg-gray-100 text-gray-400 hover:bg-gray-200' ?>">
                            <i class="fas fa-<?= $row['is_active'] ? 'eye' : 'eye-slash' ?>"></i>
                        </button>
                        <!-- Edit -->
                        <button onclick="editAnnouncement(<?= $row['id'] ?>)"
                                class="w-7 h-7 flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-100 rounded-lg transition-all text-xs">
                            <i class="fas fa-pen"></i>
                        </button>
                        <!-- Delete -->
                        <button onclick="deleteAnnouncement(<?= $row['id'] ?>, this)"
                                class="w-7 h-7 flex items-center justify-center bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition-all text-xs">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
                <td colspan="7" class="py-20 text-center text-gray-400">
                    <i class="fas fa-bullhorn text-4xl text-gray-200 mb-3 block"></i>
                    No announcements yet. Create one to get started.
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="flex items-center justify-between px-6 py-3.5 border-t border-gray-100">
        <p class="text-xs text-gray-500">Showing <?= $offset+1 ?>–<?= min($offset+$per_page,$total_rows) ?> of <?= $total_rows ?></p>
        <div class="flex gap-1">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&viewer=<?= $filter_viewer ?>&audience=<?= $filter_audience ?>&q=<?= urlencode($search) ?>"
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
// ── Search on Enter ───────────────────────────────────────────
document.getElementById('searchInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        location.href = `?viewer=<?= $filter_viewer ?>&audience=<?= $filter_audience ?>&q=${encodeURIComponent(this.value)}`;
    }
});

// ── Toggle active ─────────────────────────────────────────────
function toggleAnnouncement(id, newVal, btn) {
    $.post('api/announcement_api.php', { action: 'toggle', id, value: newVal }, function(res) {
        showToast(res.message, res.status);
        if (res.status === 'success') setTimeout(() => location.reload(), 700);
    }, 'json');
}

// ── Edit ──────────────────────────────────────────────────────
function editAnnouncement(id) {
    openModal('Edit Announcement', 'edit_announcement_modal', { id });
}

// ── Delete ────────────────────────────────────────────────────
function deleteAnnouncement(id, btn) {
    if (!confirm('Delete this announcement permanently?')) return;
    btn.disabled = true;
    $.post('api/announcement_api.php', { action: 'delete', id }, function(res) {
        showToast(res.message, res.status);
        if (res.status === 'success') {
            btn.closest('tr').style.opacity = '0';
            setTimeout(() => btn.closest('tr').remove(), 300);
        } else { btn.disabled = false; }
    }, 'json');
}
</script>

<?php include 'sections/common_modal.php'; include 'sections/footer.php'; ?>
