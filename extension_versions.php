<?php
include 'include/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: dashboard"); exit();
}

$role    = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$name    = $_SESSION['name'];
$title   = "Extension Versions";

$versions = $conn->query("
    SELECT ev.*, u.name as uploader_name, u.username as uploader_username,
           (SELECT COUNT(*) FROM reseller_branding WHERE last_version_id = ev.id) as downloads
    FROM extension_versions ev
    LEFT JOIN users u ON ev.created_by = u.id
    ORDER BY ev.id DESC
");

$total_versions = $conn->query("SELECT COUNT(*) FROM extension_versions")->fetch_row()[0];
$active_versions = $conn->query("SELECT COUNT(*) FROM extension_versions WHERE is_active=1")->fetch_row()[0];
$total_downloads = $conn->query("SELECT COUNT(*) FROM reseller_branding WHERE last_version_id IS NOT NULL")->fetch_row()[0];
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
        .drop-zone { border: 2px dashed #d1d5db; transition: all 0.2s; }
        .drop-zone.drag-over { border-color: #6366f1; background: #eef2ff; }
        .changelog-pre { white-space: pre-wrap; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex h-screen">
<?php include 'sections/sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'sections/navbar.php'; ?>

<main class="flex-1 overflow-y-auto p-8 antialiased">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Chrome Extension Versions</h1>
            <p class="text-sm text-gray-500 mt-1">Upload base extension ZIPs. Resellers generate their white-labeled versions from here.</p>
        </div>
        <button onclick="openModal('Upload New Version', 'upload_version_modal')"
                class="px-5 py-2.5 bg-<?= get_config('theme_color') ?>-300 hover:bg-<?= get_config('theme_color') ?>-400 text-<?= get_config('theme_color') ?>-900 font-bold text-sm rounded-xl transition-all shadow-sm flex items-center gap-2">
            <i class="fas fa-upload"></i> Upload Version
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 bg-indigo-50 text-indigo-500 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-puzzle-piece"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Versions</p>
                <h3 class="text-2xl font-black text-gray-800"><?= $total_versions ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 bg-green-50 text-green-500 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-check-circle"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Active Versions</p>
                <h3 class="text-2xl font-black text-gray-800"><?= $active_versions ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-download"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Downloads</p>
                <h3 class="text-2xl font-black text-gray-800"><?= $total_downloads ?></h3>
            </div>
        </div>
    </div>

    <!-- Versions Table -->
    <div class="bg-white border border-gray-100 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Version</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Changelog</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Uploaded By</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-center">Downloads</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-center">Status</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                <?php if ($versions && $versions->num_rows > 0): $i=0; while ($v = $versions->fetch_assoc()): $i++;
                    $is_latest = ($i === 1 && $v['is_active']);
                ?>
                <tr class="hover:bg-gray-50/50 transition-all">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-9 h-9 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-file-archive text-sm"></i>
                            </div>
                            <div>
                                <div class="font-bold text-gray-800 flex items-center gap-1.5">
                                    v<?= htmlspecialchars($v['version_name']) ?>
                                    <?php if ($is_latest): ?>
                                    <span class="px-1.5 py-0.5 bg-indigo-100 text-indigo-600 text-[9px] font-black rounded-full uppercase">Latest</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-[11px] text-gray-400"><?= date('d M Y, h:i A', strtotime($v['created_at'])) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 max-w-xs">
                        <?php if ($v['changelog']): ?>
                        <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed"><?= nl2br(htmlspecialchars(substr($v['changelog'], 0, 120))) ?><?= strlen($v['changelog']) > 120 ? '...' : '' ?></p>
                        <button onclick="openModal('Changelog v<?= htmlspecialchars($v['version_name']) ?>', 'changelog_modal', {id: <?= $v['id'] ?>})"
                                class="text-[10px] text-indigo-600 hover:underline font-bold mt-0.5">Read more</button>
                        <?php else: ?>
                        <span class="text-xs text-gray-300 italic">No changelog</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-<?= get_config('theme_color') ?>-50 text-<?= get_config('theme_color') ?>-600 flex items-center justify-center text-[10px] font-bold">
                                <?= strtoupper(substr($v['uploader_name'] ?: $v['uploader_username'], 0, 1)) ?>
                            </div>
                            <span class="text-xs text-gray-600"><?= htmlspecialchars($v['uploader_name'] ?: $v['uploader_username']) ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-600 text-xs font-bold rounded-full">
                            <i class="fas fa-download text-[9px]"></i> <?= $v['downloads'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button onclick="toggleVersionStatus(<?= $v['id'] ?>, <?= $v['is_active'] ?>)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-black rounded-full uppercase cursor-pointer transition-all border
                                <?= $v['is_active'] ? 'bg-green-50 text-green-700 border-green-100 hover:bg-red-50 hover:text-red-600 hover:border-red-100' : 'bg-red-50 text-red-600 border-red-100 hover:bg-green-50 hover:text-green-700 hover:border-green-100' ?>">
                            <span class="w-1.5 h-1.5 rounded-full <?= $v['is_active'] ? 'bg-green-500 animate-pulse' : 'bg-red-400' ?>"></span>
                            <?= $v['is_active'] ? 'Active' : 'Inactive' ?>
                        </button>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-1">
                            <button onclick="openModal('Edit Version', 'edit_version_modal', {id: <?= $v['id'] ?>})"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition-all" title="Edit">
                                <i class="fas fa-pencil-alt text-xs"></i>
                            </button>
                            <?php if ($v['downloads'] == 0): ?>
                            <button onclick="deleteVersion(<?= $v['id'] ?>)"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-600 transition-all" title="Delete">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-20 text-center">
                        <i class="fas fa-puzzle-piece text-gray-200 text-4xl mb-3 block"></i>
                        <p class="text-gray-400 text-sm">No versions uploaded yet. Click "Upload Version" to get started.</p>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>
</div>

<script>
function toggleVersionStatus(id, current) {
    if (!confirm('Change this version status?')) return;
    $.post('api/extension_api.php', { action: 'toggle_version', id: id, current: current }, function(res) {
        if (res.status === 'success') { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1200); }
        else showToast(res.message, 'error');
    }, 'json');
}
function deleteVersion(id) {
    if (!confirm('Delete this version permanently?')) return;
    $.post('api/extension_api.php', { action: 'delete_version', id: id }, function(res) {
        if (res.status === 'success') { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1200); }
        else showToast(res.message, 'error');
    }, 'json');
}
</script>

<?php include 'sections/common_modal.php'; include 'sections/footer.php'; ?>
