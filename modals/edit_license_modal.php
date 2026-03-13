<?php
include '../include/config.php';
$id = $_GET['id'];
$res = $conn->query("SELECT * FROM licenses WHERE id = '$id'");
$data = $res->fetch_assoc();
?>

<form id="editLicenseForm" class="space-y-4">
    <input type="hidden" name="action" value="update_license">
    <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
    
    <div>
        <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Software Tool</label>
        <select name="software_name" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
            <option value="<?= get_config('default_software'); ?>" readonly><?= get_config('default_software'); ?></option>
        </select>
    </div>

    <div>
        <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Client Name</label>
        <input type="text" name="client_name" value="<?php echo $data['client_name']; ?>" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Status</label>
            <select name="status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
                <option value="active" <?php if($data['status'] == 'active') echo 'selected'; ?>>Active</option>
                <option value="expired" <?php if($data['status'] == 'expired') echo 'selected'; ?>>Expired</option>
                <option value="blocked" <?php if($data['status'] == 'blocked') echo 'selected'; ?>>Blocked</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Expiry Date</label>
            <input type="date" name="expiry_date" value="<?php echo $data['expiry_date']; ?>" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
        </div>
    </div>

    <div class="pt-4">
        <button type="submit" id="updateBtn" class="w-full py-3 bg-<?= get_config('theme_color'); ?>-300 hover:bg-<?= get_config('theme_color'); ?>-400 text-<?= get_config('theme_color'); ?>-900 font-bold rounded-xl transition-all shadow-sm">
            Update License Details
        </button>
    </div>
</form>

<script>
$('#editLicenseForm').on('submit', function(e){
    e.preventDefault();
    const btn = $('#updateBtn');
    btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Updating...').prop('disabled', true);

    $.ajax({
        url: 'api/license_api.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(() => { location.reload(); }, 2000);
            } else {
                showToast(res.message, 'error');
                btn.html('Update License Details').prop('disabled', false);
            }
        }
    });
});
</script>