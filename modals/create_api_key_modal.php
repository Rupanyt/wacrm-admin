<?php
// modals/create_api_key_modal.php
include '../include/config.php';
if (!isset($_SESSION['user_id'])) exit;
?>
<div class="space-y-4">

    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1.5">Key Name <span class="text-red-400">*</span></label>
        <input type="text" id="ck_name" placeholder="e.g. My App Integration, Production Key"
               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
        <p class="text-[10px] text-gray-400 mt-1">A friendly name to identify this key</p>
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
            ?>
            <label class="flex items-center gap-3 p-3 border border-gray-100 rounded-xl cursor-pointer hover:border-<?= $color ?>-200 hover:bg-<?= $color ?>-50/30 transition-all group">
                <input type="checkbox" name="ck_permissions[]" value="<?= $val ?>" class="ck-perm-check w-4 h-4 rounded accent-indigo-600"
                       <?= $val === 'validate_license' ? 'checked' : '' ?>>
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
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Rate Limit <span class="text-gray-400 font-normal">(requests/min)</span></label>
            <input type="number" id="ck_rate" value="60" min="1" max="1000"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
            <p class="text-[10px] text-gray-400 mt-1">Max 1000/min</p>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">IP Whitelist <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="text" id="ck_ips" placeholder="e.g. 192.168.1.1, 10.0.0.1"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
            <p class="text-[10px] text-gray-400 mt-1">Leave empty to allow all IPs</p>
        </div>
    </div>

    <!-- After creation: show key+secret -->
    <div id="ck_result" class="hidden">
        <div class="p-4 bg-green-50 border border-green-200 rounded-xl mb-3">
            <p class="text-xs font-black text-green-700 mb-1"><i class="fas fa-check-circle mr-1"></i> API Key Created!</p>
            <p class="text-[10px] text-green-600">Copy these credentials now. The secret will never be shown again.</p>
        </div>
        <div class="space-y-3">
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">API Key</label>
                <div class="flex items-center gap-2">
                    <code id="ck_show_key" class="flex-1 px-3 py-2 bg-gray-900 text-green-400 rounded-xl text-xs font-mono break-all"></code>
                    <button onclick="copyText(document.getElementById('ck_show_key').textContent)" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-bold transition-all flex-shrink-0"><i class="fas fa-copy"></i></button>
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">API Secret <span class="text-red-400 font-black">— Save this now!</span></label>
                <div class="flex items-center gap-2">
                    <code id="ck_show_secret" class="flex-1 px-3 py-2 bg-gray-900 text-yellow-400 rounded-xl text-xs font-mono break-all"></code>
                    <button onclick="copyText(document.getElementById('ck_show_secret').textContent)" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-bold transition-all flex-shrink-0"><i class="fas fa-copy"></i></button>
                </div>
            </div>
            <div class="p-3 bg-red-50 border border-red-100 rounded-xl text-[10px] text-red-600">
                <i class="fas fa-exclamation-triangle mr-1"></i> <strong>Warning:</strong> The secret key is shown only once. If lost, you will need to delete this key and create a new one.
            </div>
        </div>
    </div>

    <div id="ck_form_btns" class="flex gap-3">
        <button onclick="createApiKey()"
                class="flex-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="fas fa-key"></i> Generate API Key
        </button>
        <button onclick="closeModal()" class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">Cancel</button>
    </div>
    <div id="ck_done_btn" class="hidden">
        <button onclick="closeModal(); location.reload();"
                class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2">
            <i class="fas fa-check"></i> Done — I've saved my credentials
        </button>
    </div>
</div>

<script>
function createApiKey() {
    const name  = document.getElementById('ck_name').value.trim();
    const perms = [...document.querySelectorAll('.ck-perm-check:checked')].map(c => c.value);
    const rate  = document.getElementById('ck_rate').value;
    const ips   = document.getElementById('ck_ips').value.trim();

    if (!name)        { showToast('Key name is required.', 'error'); return; }
    if (!perms.length){ showToast('Select at least one permission.', 'error'); return; }

    const btn  = event.currentTarget;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    btn.disabled  = true;

    const fd = new FormData();
    fd.append('action',      'create_key');
    fd.append('key_name',    name);
    fd.append('rate_limit',  rate);
    fd.append('allowed_ips', ips);
    perms.forEach(p => fd.append('permissions[]', p));

    $.ajax({
        url: 'api/api_key_api.php', type: 'POST',
        data: fd, processData: false, contentType: false, dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                document.getElementById('ck_show_key').textContent    = res.api_key;
                document.getElementById('ck_show_secret').textContent = res.api_secret;
                document.getElementById('ck_result').classList.remove('hidden');
                document.getElementById('ck_form_btns').classList.add('hidden');
                document.getElementById('ck_done_btn').classList.remove('hidden');
                // Hide form fields
                document.querySelectorAll('#ck_name, #ck_rate, #ck_ips').forEach(el => el.closest('div.space-y-4 > div')?.classList.add('hidden'));
            } else {
                showToast(res.message, 'error');
                btn.innerHTML = orig; btn.disabled = false;
            }
        },
        error: function() { showToast('Server error.', 'error'); btn.innerHTML = orig; btn.disabled = false; }
    });
}
function copyText(text) { navigator.clipboard.writeText(text.trim()).then(() => showToast('Copied!','success')); }
</script>
