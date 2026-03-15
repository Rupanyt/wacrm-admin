<?php
// modals/announcement_modal.php
include '../include/config.php';

$viewer_options = [
    'NOTIFY'        => ['icon'=>'fas fa-bell',             'color'=>'blue',   'desc'=>'Appears in Announcement tab'],
    'MODAL'         => ['icon'=>'fas fa-window-restore',    'color'=>'purple', 'desc'=>'Pops up on screen'],
    'INBOX'         => ['icon'=>'fas fa-inbox',             'color'=>'green',  'desc'=>'Appears in Inbox tab'],
    'EXTERNAL_PAGE' => ['icon'=>'fas fa-external-link-alt', 'color'=>'orange', 'desc'=>'Opens external link after 30s'],
];
?>
<div class="p-6 space-y-5">

    <!-- Title (internal label) -->
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1.5">
            Internal Title <span class="text-red-400">*</span>
            <span class="text-gray-400 font-normal ml-1">(not shown to users)</span>
        </label>
        <input type="text" id="ann_title" placeholder="e.g. March promo announcement"
               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
    </div>

    <!-- Viewer type -->
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-2">Viewer Type <span class="text-red-400">*</span></label>
        <div class="grid grid-cols-2 gap-2" id="viewerPicker">
            <?php foreach ($viewer_options as $vk => $vm): ?>
            <label class="viewer-opt cursor-pointer">
                <input type="radio" name="ann_viewer" value="<?= $vk ?>" class="sr-only" <?= $vk === 'NOTIFY' ? 'checked' : '' ?>>
                <div class="flex items-center gap-2.5 px-3 py-2.5 border-2 border-gray-200 rounded-xl transition-all hover:border-indigo-300
                            ring-offset-0 ring-transparent
                            viewer-opt-box <?= $vk === 'NOTIFY' ? 'border-indigo-500 bg-indigo-50' : '' ?>">
                    <div class="w-8 h-8 bg-<?= $vm['color'] ?>-50 text-<?= $vm['color'] ?>-500 rounded-lg flex items-center justify-center flex-shrink-0 text-xs">
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
        <label class="block text-xs font-bold text-gray-600 mb-1.5">Audience <span class="text-red-400">*</span></label>
        <div class="flex gap-2">
            <?php foreach (['all'=>'Everyone','premium'=>'★ Premium Only','free'=>'Free Only'] as $av => $al): ?>
            <label class="flex-1 cursor-pointer">
                <input type="radio" name="ann_audience" value="<?= $av ?>" class="sr-only" <?= $av==='all' ? 'checked' : '' ?>>
                <div class="audience-opt text-center px-3 py-2.5 border-2 border-gray-200 rounded-xl transition-all text-xs font-bold text-gray-600 hover:border-indigo-300
                            <?= $av==='all' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : '' ?>">
                    <?= $al ?>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Statement -->
    <div id="statementWrap">
        <label class="block text-xs font-bold text-gray-600 mb-1.5">
            Message / Statement
            <span class="text-gray-400 font-normal ml-1">Supports *bold* and _italic_</span>
        </label>
        <textarea id="ann_statement" rows="3" placeholder="Write your announcement text here..."
                  class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all resize-none"></textarea>
        <div class="flex gap-2 mt-1.5">
            <button type="button" onclick="wrapText('ann_statement','*')"
                    class="text-[10px] px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded font-bold transition-all"><b>B</b></button>
            <button type="button" onclick="wrapText('ann_statement','_')"
                    class="text-[10px] px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded font-bold transition-all"><i>I</i></button>
        </div>
    </div>

    <!-- Link & Button name -->
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Link URL</label>
            <input type="url" id="ann_link" placeholder="https://..."
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
        </div>
        <div id="btnNameWrap">
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Button Label</label>
            <input type="text" id="ann_btn_name" placeholder="Click here"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
        </div>
    </div>

    <!-- Data & Sort -->
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">
                Data <span class="text-gray-400 font-normal">(numeric, e.g. timestamp)</span>
            </label>
            <input type="number" id="ann_data" placeholder="1234567890"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">
                Sort Order <span class="text-gray-400 font-normal">(higher = first)</span>
            </label>
            <input type="number" id="ann_sort_order" value="0"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
        </div>
    </div>

    <!-- Schedule -->
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1.5">Schedule <span class="text-gray-400 font-normal">(optional — leave blank = always active)</span></label>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-[10px] text-gray-400 font-semibold block mb-1">Start Date/Time</label>
                <input type="datetime-local" id="ann_start_at"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
            </div>
            <div>
                <label class="text-[10px] text-gray-400 font-semibold block mb-1">End Date/Time</label>
                <input type="datetime-local" id="ann_end_at"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-indigo-400 transition-all">
            </div>
        </div>
    </div>

    <!-- Active toggle -->
    <div class="flex items-center justify-between py-3 px-4 bg-gray-50 rounded-xl">
        <div>
            <p class="text-sm font-bold text-gray-700">Activate immediately</p>
            <p class="text-[11px] text-gray-400">Toggle off to save as draft</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" id="ann_is_active" class="sr-only peer" checked>
            <div class="w-11 h-6 bg-gray-300 peer-checked:bg-indigo-600 rounded-full transition-all"></div>
            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow peer-checked:translate-x-5 transition-all"></div>
        </label>
    </div>

    <!-- Submit -->
    <button id="saveAnnBtn" onclick="saveAnnouncement()"
            class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm">
        <i class="fas fa-bullhorn text-xs"></i> Create Announcement
    </button>
</div>

<style>
.viewer-opt input:checked ~ .viewer-opt-box { border-color: #6366f1; background-color: #eef2ff; }
.audience-opt { transition: all .15s; }
</style>

<script>
// ── Viewer & audience radio visual ────────────────────────────
document.querySelectorAll('input[name="ann_viewer"]').forEach(r => {
    r.addEventListener('change', function() {
        document.querySelectorAll('.viewer-opt-box').forEach(b => {
            b.classList.remove('border-indigo-500','bg-indigo-50');
        });
        this.nextElementSibling.classList.add('border-indigo-500','bg-indigo-50');
        // EXTERNAL_PAGE: hide statement & btn fields
        const isExt = this.value === 'EXTERNAL_PAGE';
        document.getElementById('statementWrap').style.display = isExt ? 'none' : '';
        document.getElementById('btnNameWrap').style.display   = isExt ? 'none' : '';
    });
});

document.querySelectorAll('input[name="ann_audience"]').forEach(r => {
    r.addEventListener('change', function() {
        document.querySelectorAll('.audience-opt').forEach(b => {
            b.classList.remove('border-indigo-500','bg-indigo-50','text-indigo-700');
        });
        this.nextElementSibling.classList.add('border-indigo-500','bg-indigo-50','text-indigo-700');
    });
});

// ── Bold / Italic wrap ────────────────────────────────────────
function wrapText(id, char) {
    const el = document.getElementById(id);
    const s = el.selectionStart, e = el.selectionEnd;
    const sel = el.value.substring(s, e) || 'text';
    el.value = el.value.substring(0,s) + char + sel + char + el.value.substring(e);
    el.focus(); el.selectionStart = s + 1; el.selectionEnd = s + 1 + sel.length;
}

// ── Save ──────────────────────────────────────────────────────
function saveAnnouncement() {
    const btn = document.getElementById('saveAnnBtn');
    const title = document.getElementById('ann_title').value.trim();
    const viewer = document.querySelector('input[name="ann_viewer"]:checked')?.value;
    if (!title) { showToast('Title is required.', 'error'); return; }
    if (!viewer) { showToast('Select a viewer type.', 'error'); return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Saving...';

    $.post('api/announcement_api.php', {
        action:       'create',
        title:        title,
        viewer:       viewer,
        audience:     document.querySelector('input[name="ann_audience"]:checked')?.value || 'all',
        statement:    document.getElementById('ann_statement').value.trim(),
        link:         document.getElementById('ann_link').value.trim(),
        btn_name:     document.getElementById('ann_btn_name').value.trim(),
        data:         document.getElementById('ann_data').value.trim(),
        sort_order:   document.getElementById('ann_sort_order').value || 0,
        start_at:     document.getElementById('ann_start_at').value || '',
        end_at:       document.getElementById('ann_end_at').value   || '',
        is_active:    document.getElementById('ann_is_active').checked ? 1 : 0,
    }, function(res) {
        showToast(res.message, res.status);
        if (res.status === 'success') setTimeout(() => location.reload(), 700);
        else { btn.disabled = false; btn.innerHTML = '<i class="fas fa-bullhorn text-xs"></i> Create Announcement'; }
    }, 'json');
}
</script>
