<?php
$id = $_GET['id'];
$type = isset($_GET['type']) ? $_GET['type'] : 'license';
?>

<div class="text-center">
    <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-exclamation-triangle text-2xl"></i>
    </div>
    <h4 class="text-lg font-bold text-gray-800">Are you sure?</h4>
    <p class="text-sm text-gray-500 mt-2 px-4">Kya aap waqai is record ko delete karna chahte hain? Ye action undo nahi kiya ja sakta.</p>

    <div class="flex gap-3 mt-8">
        <button onclick="closeModal()" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl transition-all">
            No, Cancel
        </button>
        <button id="confirmDeleteBtn" onclick="processDelete('<?php echo $id; ?>')" class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl transition-all shadow-sm">
            Yes, Delete it
        </button>
    </div>
</div>

<script>
function processDelete(id) {
    const btn = $('#confirmDeleteBtn');
    btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...').prop('disabled', true);

    $.ajax({
        url: 'api/license_api.php',
        type: 'POST',
        data: { action: 'delete_license', id: id },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(() => { location.reload(); }, 2000);
            } else {
                showToast(res.message, 'error');
                btn.html('Yes, Delete it').prop('disabled', false);
            }
        }
    });
}
</script>