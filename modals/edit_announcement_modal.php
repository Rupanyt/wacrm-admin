<?php
// modals/edit_announcement_modal.php
include '../include/config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo '<p class="p-6 text-red-500">Invalid ID.</p>'; exit; }

$stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$ann = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ann) { echo '<p class="p-6 text-red-500">Announcement not found.</p>'; exit; }

$viewer_options = [
    'NOTIFY'        => ['icon'=>'fas fa-bell',             'color'=>'blue',   'desc'=>'Announcement tab'],
    'MODAL'         => ['icon'=>'fas fa-window-restore',    'color'=>'purple', 'desc'=>'Popup on screen'],
    'INBOX'         => ['icon'=>'fas fa-inbox',             'color'=>'green',  'desc'=>'Inbox tab'],
    'EXTERNAL_PAGE' => ['icon'=>'fas fa-external-link-alt', 'color'=>'orange', 'desc'=>'Opens link after 30s'],
];
?>
<div class="p-6 space-y-5">
    <input type="hidden" id="edit_ann_id" value="<?= $ann['id'] ?>">

    <!-- Title -->
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1.5">Internal Title <span class="text-red-400">*</span></label>
        <input type="text" id="edit_ann_title" value="<?= htmlspecialchars($ann['title']) ?>"
               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
    </div>

    <!-- Viewer -->
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-2">Viewer Type</label>
        <div class="grid grid-cols-2 gap-2">
            <?php foreach ($viewer_options as $vk => $vm): $sel = $ann['viewer'] === $vk; ?>
            <label class="cursor-pointer">
                <input type="radio" name="edit_ann_viewer" value="<?= $vk ?>" class="sr-only" <?= $sel?'checked':'' ?>>
                <div class="flex items-center gap-2.5 px-3 py-2.5 border-2 rounded-xl transition-all hover:border-indigo-300
                            <?= $sel ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200' ?> edit-viewer-box">
                    <div class="w-8 h-8 bg-<?= $vm['color'] ?>-50 text-<?= $vm['color'] ?>-500 rounded-lg flex items-center justify-center text-xs flex-shrink-0">
                        <i class="<?= $vm['icon'] ?>"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-700"><?= $vk ?></p>
                        <p class="text-[10px] text-gray-400"><?= $vm['desc'] ?></p>
                    </div>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Audience -->
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1.5">Audience</label>
        <div class="flex gap-2">
            <?php foreach (['all'=>'Everyone','premium'=>'★ Premium','free'=>'Free'] as $av=>$al): $sel = $ann['audience']===$av; ?>
            <label class="flex-1 cursor-pointer">
                <input type="radio" name="edit_ann_audience" value="<?= $av ?>" class="sr-only" <?= $sel?'checked':'' ?>>
                <div class="edit-audience-opt text-center px-3 py-2.5 border-2 rounded-xl text-xs font-bold text-gray-600 transition-all hover:border-indigo-300
                            <?= $sel ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200' ?>">
                    <?= $al ?>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Statement -->
    <div id="edit_statementWrap" <?= $ann['viewer']==='EXTERNAL_PAGE'?'style="display:none"':'' ?>>
        <label class="block text-xs font-bold text-gray-600 mb-1.5">Message <span class="text-gray-400 font-normal">(*bold* _italic_)</span></label>
        <textarea id="edit_ann_statement" rows="3"
                  class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all resize-none"><?= htmlspecialchars($ann['statement'] ?? '') ?></textarea>
        <div class="flex gap-2 mt-1.5">
            <button type="button" onclick="editWrap('edit_ann_statement','*')" class="text-[10px] px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded font-bold"><b>B</b></button>
            <button type="button" onclick="editWrap('edit_ann_statement','_')" class="text-[10px] px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded font-bold"><i>I</i></button>
        </div>
    </div>

    <!-- Link & Button -->
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Link URL</label>
            <input type="url" id="edit_ann_link" value="<?= htmlspecialchars($ann['link'] ?? '') ?>"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
        </div>
        <div id="edit_btnNameWrap" <?= $ann['viewer']==='EXTERNAL_PAGE'?'style="display:none"':'' ?>>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Button Label</label>
            <input type="text" id="edit_ann_btn_name" value="<?= htmlspecialchars($ann['btn_name'] ?? '') ?>"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
        </div>
    </div>

    <!-- Data & Sort -->
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Data</label>
            <input type="number" id="edit_ann_data" value="<?= htmlspecialchars($ann['data'] ?? '') ?>"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Sort Order</label>
            <input type="number" id="edit_ann_sort_order" value="<?= intval($ann['sort_order']) ?>"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
        </div>
    </div>

    <!-- Schedule -->
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-xs font-bold text-gray-600 block mb-1">Start Date/Time</label>
            <input type="datetime-local" id="edit_ann_start_at"
                   value="<?= !empty($ann['start_at']) ? date('Y-m-d\TH:i', strtotime($ann['start_at'])) : '' ?>"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-600 block mb-1">End Date/Time</label>
            <input type="datetime-local" id="edit_ann_end_at"
                   value="<?= !empty($ann['end_at']) ? date('Y-m-d\TH:i', strtotime($ann['end_at'])) : '' ?>"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
        </div>
    </div>

    <!-- Active -->
    <div class="flex items-center justify-between py-3 px-4 bg-gray-50 rounded-xl">
        <div>
            <p class="text-sm font-bold text-gray-700">Active</p>
            <p class="text-[11px] text-gray-400">Uncheck to pause without deleting</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" id="edit_ann_is_active" class="sr-only peer" <?= $ann['is_active'] ? 'checked' : '' ?>>
            <div class="w-11 h-6 bg-gray-300 peer-checked:bg-indigo-600 rounded-full transition-all"></div>
            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow peer-checked:translate-x-5 transition-all"></div>
        </label>
    </div>

    <button id="updateAnnBtn" onclick="updateAnnouncement()"
            class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2">
        <i class="fas fa-save text-xs"></i> Save Changes
    </button>
</div>

<script>
document.querySelectorAll('input[name="edit_ann_viewer"]').forEach(r => {
    r.addEventListener('change', function() {
        document.querySelectorAll('.edit-viewer-box').forEach(b => b.classList.remove('border-indigo-500','bg-indigo-50'));
        this.nextElementSibling.classList.add('border-indigo-500','bg-indigo-50');
        const isExt = this.value === 'EXTERNAL_PAGE';
        document.getElementById('edit_statementWrap').style.display = isExt ? 'none' : '';
        document.getElementById('edit_btnNameWrap').style.display   = isExt ? 'none' : '';
    });
});

document.querySelectorAll('input[name="edit_ann_audience"]').forEach(r => {
    r.addEventListener('change', function() {
        document.querySelectorAll('.edit-audience-opt').forEach(b => b.classList.remove('border-indigo-500','bg-indigo-50','text-indigo-700'));
        this.nextElementSibling.classList.add('border-indigo-500','bg-indigo-50','text-indigo-700');
    });
});

function editWrap(id, char) {
    const el = document.getElementById(id);
    const s = el.selectionStart, e = el.selectionEnd;
    const sel = el.value.substring(s, e) || 'text';
    el.value = el.value.substring(0,s) + char + sel + char + el.value.substring(e);
    el.focus(); el.selectionStart = s+1; el.selectionEnd = s+1+sel.length;
}

function updateAnnouncement() {
    const btn = document.getElementById('updateAnnBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Saving...';

    $.post('api/announcement_api.php', {
        action:      'update',
        id:          document.getElementById('edit_ann_id').value,
        title:       document.getElementById('edit_ann_title').value.trim(),
        viewer:      document.querySelector('input[name="edit_ann_viewer"]:checked')?.value,
        audience:    document.querySelector('input[name="edit_ann_audience"]:checked')?.value || 'all',
        statement:   document.getElementById('edit_ann_statement').value.trim(),
        link:        document.getElementById('edit_ann_link').value.trim(),
        btn_name:    document.getElementById('edit_ann_btn_name').value.trim(),
        data:        document.getElementById('edit_ann_data').value.trim(),
        sort_order:  document.getElementById('edit_ann_sort_order').value || 0,
        start_at:    document.getElementById('edit_ann_start_at').value || '',
        end_at:      document.getElementById('edit_ann_end_at').value   || '',
        is_active:   document.getElementById('edit_ann_is_active').checked ? 1 : 0,
    }, function(res) {
        showToast(res.message, res.status);
        if (res.status === 'success') setTimeout(() => location.reload(), 700);
        else { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save text-xs"></i> Save Changes'; }
    }, 'json');
}
</script>
