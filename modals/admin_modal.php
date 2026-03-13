<?php include '../include/config.php'; ?>

<form id="adminForm" class="space-y-4">
    <input type="hidden" name="action" value="save_admin">
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Full Name</label>
            <input type="text" name="name" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Username</label>
            <input type="text" name="username" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Mobile</label>
            <input type="text" name="mobile" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Email</label>
            <input type="email" name="email" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
        </div>
    </div>

    <div>
        <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Password</label>
        <input type="password" name="password" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
    </div>

    <div class="pt-4">
        <button type="submit" id="saveBtn" class="w-full py-3 bg-<?= get_config('theme_color'); ?>-300 hover:bg-<?= get_config('theme_color'); ?>-400 text-<?= get_config('theme_color'); ?>-900 font-bold rounded-xl transition-all shadow-sm">
            Create Admin Account
        </button>
    </div>
</form>

<script>
$('#adminForm').on('submit', function(e){
    e.preventDefault();
    $('#saveBtn').prop('disabled', true).html('Processing...');
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
                $('#saveBtn').prop('disabled', false).text('Create Admin Account');
            }
        }
    });
});
</script>