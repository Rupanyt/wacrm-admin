<?php $id = $_GET['id']; ?>
<div class="text-center">
    <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-shield-virus text-2xl"></i>
    </div>
    <h4 class="text-lg font-bold text-gray-800">Remove Admin?</h4>
<p class="text-sm text-gray-500 mt-2 px-4 italic">
    Warning: Deleting this Admin will revoke their dashboard access immediately. All associated data will be permanently removed from the system.
</p>

    <div class="flex gap-3 mt-8">
        <button onclick="closeModal()" class="flex-1 py-2.5 bg-gray-100 text-gray-600 font-bold rounded-xl">Cancel</button>
        <button id="delBtn" onclick="confirmAdminDelete(<?php echo $id; ?>)" class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl shadow-sm">Confirm Delete</button>
    </div>
</div>

<script>
function confirmAdminDelete(id) {
    $('#delBtn').prop('disabled', true).html('Deleting...');
    $.post('api/admin_api.php', { action: 'delete_admin', id: id }, function(res){
        if(res.status === 'success') {
            showToast(res.message, 'success');
            setTimeout(() => { location.reload(); }, 2000);
        } else {
            showToast(res.message, 'error');
            closeModal();
        }
    }, 'json');
}
</script>