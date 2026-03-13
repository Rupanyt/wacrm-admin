<?php include '../include/config.php';
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) exit; ?>

<form id="uploadVersionForm" enctype="multipart/form-data">
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Version Name <span class="text-red-400">*</span></label>
                <input type="text" name="version_name" required placeholder="e.g. 7.5.0"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
                <p class="text-[10px] text-gray-400 mt-1">Use semantic versioning: major.minor.patch</p>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Base Extension ZIP <span class="text-red-400">*</span></label>
            <div id="zipDropZone" onclick="document.getElementById('zipFileInput').click()"
                 class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/30 transition-all">
                <i class="fas fa-file-archive text-gray-300 text-3xl mb-2 block" id="zipIcon"></i>
                <p class="text-sm text-gray-500" id="zipFileName">Click to upload or drag & drop</p>
                <p class="text-[10px] text-gray-400 mt-0.5">.zip only — max 100MB</p>
                <input type="file" id="zipFileInput" name="base_zip" accept=".zip" required class="hidden"
                       onchange="document.getElementById('zipFileName').textContent = this.files[0]?.name || 'No file'; document.getElementById('zipIcon').className='fas fa-check-circle text-green-400 text-3xl mb-2 block'">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Changelog / Release Notes</label>
            <textarea name="changelog" rows="5" placeholder="• Fixed: Login issue on first launch&#10;• Added: Bulk messaging feature&#10;• Improved: UI performance"
                      class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all resize-none"></textarea>
            <p class="text-[10px] text-gray-400 mt-1">Resellers will see this in Version History. Use bullet points for clarity.</p>
        </div>

        <div class="flex gap-3 pt-1">
            <button type="submit" id="uploadVersionBtn"
                    class="flex-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm">
                <i class="fas fa-upload"></i> Upload Version
            </button>
            <button type="button" onclick="closeModal()"
                    class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">
                Cancel
            </button>
        </div>
    </div>
</form>

<script>
// Drag & drop
const zz = document.getElementById('zipDropZone');
zz.addEventListener('dragover', e => { e.preventDefault(); zz.classList.add('border-indigo-400','bg-indigo-50'); });
zz.addEventListener('dragleave', () => zz.classList.remove('border-indigo-400','bg-indigo-50'));
zz.addEventListener('drop', e => {
    e.preventDefault(); zz.classList.remove('border-indigo-400','bg-indigo-50');
    document.getElementById('zipFileInput').files = e.dataTransfer.files;
    document.getElementById('zipFileName').textContent = e.dataTransfer.files[0]?.name || '';
    document.getElementById('zipIcon').className = 'fas fa-check-circle text-green-400 text-3xl mb-2 block';
});

$('#uploadVersionForm').on('submit', function(e) {
    e.preventDefault();
    const btn  = document.getElementById('uploadVersionBtn');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    btn.disabled  = true;

    $.ajax({
        url: 'api/extension_api.php', type: 'POST',
        data: new FormData(this), processData: false, contentType: false, dataType: 'json',
        xhr: function() {
            const x = new XMLHttpRequest();
            x.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const pct = Math.round((e.loaded / e.total) * 100);
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading ' + pct + '%...';
                }
            });
            return x;
        },
        success: function(res) {
            if (res.status === 'success') {
                showToast(res.message, 'success'); closeModal(); setTimeout(() => location.reload(), 1500);
            } else {
                showToast(res.message, 'error'); btn.innerHTML = orig; btn.disabled = false;
            }
        },
        error: function() { showToast('Upload failed. Check file size.', 'error'); btn.innerHTML = orig; btn.disabled = false; }
    });
});
</script>
