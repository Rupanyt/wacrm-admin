<?php
// modals/edit_api_key_modal.php
include '../include/config.php';
$id = (int)($_GET['id'] ?? 0);
$k  = $conn->query("SELECT * FROM api_keys WHERE id='$id' AND user_id='{$_SESSION['user_id']}'")->fetch_assoc();
if (!$k) { echo '<p class="text-red-500 text-sm text-center py-6">Key not found.</p>'; exit; }
$perms_arr = explode(',', $k['permissions']);
?>
<div class="space-y-4">

    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1.5">Key Name <span class="text-red-400">*</span></label>
        <input type="text" id="ek_name" value="<?= htmlspecialchars($k['key_name']) ?>"
               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
    </div>

    <div>
        <label class="block text-xs font-bold text-gray-600 mb-2">Permissions <span class="text-red-400">*</span></label>
        <div class="grid grid-cols-1 gap-2">
            <?php
            $perms = [
                ['validate_license', 'fas fa-check-double', 'green',  'Validate License', 'Check if a license key is valid and active'],
                ['read_license',     'fas fa-eye',          'blue',   'Read License',     'List and view license details'],
                ['create_license',   'fas fa-plus-circle',  'indigo', 'Create License',   'Generate new license keys (quota applies)'],
                ['update_license',   'fas fa-edit',         'yellow', 'Update License',   'Modify and toggle license status'],
                ['delete_license',   'fas fa-trash',        'red',    'Delete License',   'Permanently remove licenses'],
            ];
            foreach ($perms as [$val, $ico, $color, $label, $desc]):
                $checked = in_array($val, $perms_arr) ? 'checked' : '';
            ?>
            <label class="flex items-center gap-3 p-3 border border-gray-100 rounded-xl cursor-pointer hover:border-<?= $color ?>-200 hover:bg-<?= $color ?>-50/30 transition-all">
                <input type="checkbox" name="ek_permissions[]" value="<?= $val ?>" class="ek-perm-check w-4 h-4 rounded accent-indigo-600" <?= $checked ?>>
                <div class="w-7 h-7 bg-<?= $color ?>-50 text-<?= $color ?>-500 rounded-lg flex items-center justify-center flex-shrink-0 text-xs">
                    <i class="<?= $ico ?>"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-700"><?= $label ?></p>
                    <p class="text-[10px] text-gray-400"><?= $desc ?></p>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Rate Limit <span class="text-gray-400 font-normal">(req/min)</span></label>
            <input type="number" id="ek_rate" value="<?= $k['rate_limit'] ?>" min="1" max="1000"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">IP Whitelist <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="text" id="ek_ips" value="<?= htmlspecialchars($k['allowed_ips'] ?? '') ?>" placeholder="e.g. 192.168.1.1"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
        </div>
    </div>

    <div class="p-3 bg-yellow-50 border border-yellow-100 rounded-xl text-[10px] text-yellow-700">
        <i class="fas fa-info-circle mr-1"></i> Editing a key does not change its API key string or secret. Changes take effect immediately.
    </div>

    <div class="flex gap-3">
        <button onclick="updateApiKey(<?= $id ?>)"
                class="flex-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="fas fa-save"></i> Save Changes
        </button>
        <button onclick="closeModal()" class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">Cancel</button>
    </div>
</div>

<script>
function updateApiKey(id) {
    const name  = document.getElementById('ek_name').value.trim();
    const perms = [...document.querySelectorAll('.ek-perm-check:checked')].map(c => c.value);
    const rate  = document.getElementById('ek_rate').value;
    const ips   = document.getElementById('ek_ips').value.trim();

    if (!name)         { showToast('Key name is required.', 'error'); return; }
    if (!perms.length) { showToast('Select at least one permission.', 'error'); return; }

    const btn  = event.currentTarget;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled  = true;

    const fd = new FormData();
    fd.append('action',      'update_key');
    fd.append('id',          id);
    fd.append('key_name',    name);
    fd.append('rate_limit',  rate);
    fd.append('allowed_ips', ips);
    perms.forEach(p => fd.append('permissions[]', p));

    $.ajax({
        url: 'api/api_key_api.php', type: 'POST',
        data: fd, processData: false, contentType: false, dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                showToast(res.message, 'success'); closeModal(); setTimeout(() => location.reload(), 1200);
            } else {
                showToast(res.message, 'error'); btn.innerHTML = orig; btn.disabled = false;
            }
        },
        error: function() { showToast('Server error.', 'error'); btn.innerHTML = orig; btn.disabled = false; }
    });
}
</script>
