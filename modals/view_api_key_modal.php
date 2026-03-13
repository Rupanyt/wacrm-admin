<?php
// modals/view_api_key_modal.php
include '../include/config.php';
$id = (int)($_GET['id'] ?? 0);
$k  = $conn->query("SELECT * FROM api_keys WHERE id='$id' AND user_id='{$_SESSION['user_id']}'")->fetch_assoc();
if (!$k) { echo '<p class="text-red-500 text-sm text-center py-6">Key not found.</p>'; exit; }
$perms_arr = explode(',', $k['permissions']);
?>
<div class="space-y-4">
    <div class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-100 rounded-xl">
        <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-key"></i>
        </div>
        <div>
            <p class="font-bold text-gray-800"><?= htmlspecialchars($k['key_name']) ?></p>
            <p class="text-[10px] text-gray-400">Created <?= date('d M Y, h:i A', strtotime($k['created_at'])) ?></p>
        </div>
        <span class="ml-auto px-2 py-1 <?= $k['status']==='active' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600' ?> text-[10px] font-black rounded-full uppercase">
            <?= $k['status'] ?>
        </span>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">API Key</label>
        <div class="flex items-center gap-2">
            <code class="flex-1 px-3 py-2 bg-gray-900 text-green-400 rounded-xl text-xs font-mono break-all"><?= htmlspecialchars($k['api_key']) ?></code>
            <button onclick="navigator.clipboard.writeText('<?= $k['api_key'] ?>').then(()=>showToast('Copied!','success'))"
                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-bold transition-all flex-shrink-0"><i class="fas fa-copy"></i></button>
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">API Secret</label>
            <button onclick="revealSecret(<?= $id ?>)" id="revealBtn"
                    class="text-[10px] text-indigo-500 hover:underline font-bold"><i class="fas fa-eye mr-1"></i>Reveal Secret</button>
        </div>
        <div id="secretBox" class="px-3 py-2 bg-gray-900 text-gray-600 rounded-xl text-xs font-mono">
            ••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••
        </div>
        <p class="text-[10px] text-gray-400 mt-1">Keep this secret. Never expose in client-side code.</p>
    </div>

    <div class="grid grid-cols-2 gap-4 text-xs">
        <div class="p-3 bg-gray-50 rounded-xl">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Permissions</p>
            <div class="space-y-1">
                <?php
                $all_p = ['validate_license','read_license','create_license','update_license','delete_license'];
                foreach ($all_p as $p):
                    $has = in_array($p, $perms_arr);
                ?>
                <div class="flex items-center gap-1.5 <?= $has ? 'text-green-600' : 'text-gray-300' ?>">
                    <i class="fas fa-<?= $has ? 'check' : 'times' ?>-circle text-[10px]"></i>
                    <span class="font-mono text-[10px]"><?= $p ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="p-3 bg-gray-50 rounded-xl space-y-2">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Rate Limit</p>
                <p class="font-bold text-gray-700"><?= $k['rate_limit'] ?> req/min</p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Calls</p>
                <p class="font-bold text-gray-700"><?= number_format($k['total_calls']) ?></p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Last Used</p>
                <p class="font-bold text-gray-700"><?= $k['last_used_at'] ? date('d M Y', strtotime($k['last_used_at'])) : 'Never' ?></p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">IP Whitelist</p>
                <p class="font-bold text-gray-700 text-[10px]"><?= $k['allowed_ips'] ? htmlspecialchars($k['allowed_ips']) : 'Any IP' ?></p>
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button onclick="openModal('Edit API Key','edit_api_key_modal',{id:<?= $id ?>})"
                class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2">
            <i class="fas fa-edit"></i> Edit Key
        </button>
        <button onclick="closeModal()" class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">Close</button>
    </div>
</div>

<script>
function revealSecret(id) {
    const btn = document.getElementById('revealBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Loading...';
    $.post('api/api_key_api.php', { action: 'reveal_key', id }, function(res) {
        if (res.status === 'success') {
            const box = document.getElementById('secretBox');
            box.className = 'flex items-center gap-2 px-3 py-2 bg-gray-900 text-yellow-400 rounded-xl text-xs font-mono break-all';
            box.innerHTML = res.data.api_secret +
                `<button onclick="navigator.clipboard.writeText('${res.data.api_secret}').then(()=>showToast('Copied!','success'))"
                         class="flex-shrink-0 px-2 py-1 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-[10px] transition-all ml-auto">
                    <i class="fas fa-copy"></i>
                 </button>`;
            btn.style.display = 'none';
        } else showToast(res.message, 'error');
    }, 'json');
}
</script>
<?php
// ════════════════════════════════════════════════════════════
// edit_api_key_modal.php — separate include below
// This file serves BOTH modals based on GET param presence
?>
