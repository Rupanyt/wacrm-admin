<?php include '../include/config.php'; ?>

<form id="resellerForm" class="space-y-4">
    <input type="hidden" name="action" value="save_reseller">
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Full Name</label>
            <input type="text" name="name" required placeholder="John Doe" 
                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300 transition-all">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Username</label>
            <input type="text" name="username" required placeholder="reseller_01" 
                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300 transition-all">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Mobile Number</label>
            <input type="text" name="mobile" required placeholder="98XXXXXXXX" 
                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300 transition-all">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Email Address</label>
            <input type="email" name="email" required placeholder="john@example.com" 
                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300 transition-all">
        </div>
    </div>

    <div>
        <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Login Password</label>
        <input type="password" name="password" required placeholder="••••••••" 
               class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300 transition-all">
    </div>

    <div class="pt-4">
        <button type="submit" id="saveBtn" class="w-full py-3 bg-<?= get_config('theme_color'); ?>-300 hover:bg-<?= get_config('theme_color'); ?>-400 text-<?= get_config('theme_color'); ?>-900 font-bold rounded-xl transition-all shadow-sm">
            Create Reseller Account
        </button>
    </div>
</form>

<script>
$('#resellerForm').on('submit', function(e){
    e.preventDefault();
    $('#saveBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Processing...');

    $.ajax({
        url: 'api/reseller_api.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(() => { location.reload(); }, 2000);
            } else {
                showToast(res.message, 'error');
                $('#saveBtn').prop('disabled', false).text('Create Reseller Account');
            }
        }
    });
});
</script>