<?php
include '../include/config.php';
$id = (int)($_GET['id'] ?? 0);
$v  = $conn->query("SELECT * FROM extension_versions WHERE id='$id'")->fetch_assoc();
if (!$v) { echo '<p class="text-red-500 text-sm text-center py-6">Version not found.</p>'; exit; }
?>
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center font-black text-sm">
                v<?= htmlspecialchars($v['version_name']) ?>
            </div>
            <div>
                <p class="font-bold text-gray-800">Version <?= htmlspecialchars($v['version_name']) ?></p>
                <p class="text-[11px] text-gray-400"><?= date('d M Y, h:i A', strtotime($v['created_at'])) ?></p>
            </div>
        </div>
        <span class="px-2.5 py-1 <?= $v['is_active'] ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500' ?> text-[10px] font-bold rounded-full uppercase">
            <?= $v['is_active'] ? 'Active' : 'Inactive' ?>
        </span>
    </div>

    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 max-h-80 overflow-y-auto">
        <?php if ($v['changelog']): ?>
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">Release Notes</p>
        <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line"><?= nl2br(htmlspecialchars($v['changelog'])) ?></div>
        <?php else: ?>
        <p class="text-sm text-gray-400 italic text-center py-4">No changelog provided for this version.</p>
        <?php endif; ?>
    </div>

    <button onclick="closeModal()"
            class="w-full px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">
        Close
    </button>
</div>
