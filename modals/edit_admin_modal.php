<?php
include '../include/config.php';
$id = $_GET['id'];
$res = $conn->query("SELECT * FROM users WHERE id = '$id'");
$admin = $res->fetch_assoc();
?>

<form id="editAdminForm" class="space-y-4">
    <input type="hidden" name="action" value="update_admin">
    <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Full Name</label>
            <input type="text" name="name" value="<?php echo $admin['name']; ?>" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Mobile</label>
            <input type="text" name="mobile" value="<?php echo $admin['mobile']; ?>" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
        </div>
    </div>

    <div>
        <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Email Address</label>
        <input type="email" name="email" value="<?php echo $admin['email']; ?>" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
    </div>

    <div class="p-3 bg-blue-50 rounded-lg border border-blue-100">
        <label class="block text-[11px] font-bold text-blue-500 uppercase mb-1">Reset Password (Keep blank to skip)</label>
        <input type="password" name="password" placeholder="New strong password" class="w-full px-4 py-2 bg-white border border-blue-200 rounded-lg text-sm outline-none focus:border-blue-400">
    </div>

    <div class="pt-4">
        <button type="submit" id="updateBtn" class="w-full py-3 bg-<?= get_config('theme_color'); ?>-300 hover:bg-<?= get_config('theme_color'); ?>-400 text-<?= get_config('theme_color'); ?>-900 font-bold rounded-xl transition-all shadow-sm">
            Update Admin Details
        </button>
    </div>
</form>

<script>
$('#editAdminForm').on('submit', function(e){
    e.preventDefault();
    $('#updateBtn').prop('disabled', true).html('Updating...');
    $.ajax({
        url: 'api/admin_api.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(() => { location.reload(); }, 2000);
            } else {
                showToast(res.message, 'error');
                $('#updateBtn').prop('disabled', false).text('Update Admin Details');
            }
        }
    });
});
</script>