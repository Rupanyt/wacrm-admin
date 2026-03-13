<?php
// edit_version_modal.php
include '../include/config.php';
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) { echo '<p class="text-red-500 text-sm">Unauthorized.</p>'; exit; }
$id = (int)($_GET['id'] ?? 0);
$v  = $conn->query("SELECT * FROM extension_versions WHERE id='$id'")->fetch_assoc();
if (!$v) { echo '<p class="text-red-500 text-sm">Version not found.</p>'; exit; }
?>
<form id="editVersionForm" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $v['id'] ?>">
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Version Name <span class="text-red-400">*</span></label>
            <input type="text" name="version_name" required value="<?= htmlspecialchars($v['version_name']) ?>"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Replace ZIP <span class="text-gray-400 font-normal">(optional — leave empty to keep current)</span></label>
            <div id="editZipZone" onclick="document.getElementById('editZipInput').click()"
                 class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/30 transition-all">
                <p class="text-xs text-gray-500" id="editZipName">Current: <?= basename($v['zip_path']) ?></p>
                <p class="text-[10px] text-gray-400 mt-0.5">Click to upload new .zip (optional)</p>
                <input type="file" id="editZipInput" name="base_zip" accept=".zip" class="hidden"
                       onchange="document.getElementById('editZipName').textContent = '✓ New: ' + (this.files[0]?.name || '')">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Changelog</label>
            <textarea name="changelog" rows="6"
                      class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all resize-none"><?= htmlspecialchars($v['changelog'] ?? '') ?></textarea>
        </div>

        <div class="flex gap-3">
            <button type="submit" id="editVersionBtn"
                    class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <button type="button" onclick="closeModal()"
                    class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">Cancel</button>
        </div>
    </div>
</form>

<script>
$('#editVersionForm').on('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editVersionBtn');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...'; btn.disabled = true;
    const fd = new FormData(this);
    fd.append('action', 'update_version');
    $.ajax({
        url: 'api/extension_api.php', type: 'POST',
        data: fd, processData: false, contentType: false, dataType: 'json',
        success: function(res) {
            if (res.status === 'success') { showToast(res.message, 'success'); closeModal(); setTimeout(() => location.reload(), 1500); }
            else { showToast(res.message, 'error'); btn.innerHTML = orig; btn.disabled = false; }
        },
        error: function() { showToast('Error.', 'error'); btn.innerHTML = orig; btn.disabled = false; }
    });
});
</script>
