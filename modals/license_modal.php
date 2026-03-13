<?php include '../include/config.php'; ?>

<form id="licenseForm" class="space-y-4">
    <input type="hidden" name="action" value="save_license">
    
    <div>
        <label class="block text-[12px] font-semibold text-gray-500 mb-1">Software Tool</label>
        <select name="software_name" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
            <option value="<?php echo get_config('default_software'); ?>" readonly><?php echo get_config('default_software'); ?></option>
        </select>
    </div>

    <div>
        <label class="block text-[12px] font-semibold text-gray-500 mb-1">Client Name</label>
        <input type="text" name="client_name" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-[12px] font-semibold text-gray-500 mb-1">Mobile</label>
            <input type="text" name="client_mobile" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
        </div>
        <div>
            <label class="block text-[12px] font-semibold text-gray-500 mb-1">Expiry Date</label>
            <input type="date" name="expiry_date" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-<?= get_config('theme_color'); ?>-300">
        </div>
    </div>

    <div class="pt-4">
        <button type="submit" id="saveBtn" class="w-full py-3 bg-<?= get_config('theme_color'); ?>-300 hover:bg-<?= get_config('theme_color'); ?>-400 text-<?= get_config('theme_color'); ?>-900 font-bold rounded-xl transition-all shadow-sm">
            Generate License Key
        </button>
    </div>
</form>

<script>
$('#licenseForm').on('submit', function(e) {
    e.preventDefault();
    const btn = $('#saveBtn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

    $.ajax({
        url: 'api/license_api.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                closeModal();
                setTimeout(() => { location.reload(); }, 2000);
            } else {
                showToast(res.message, 'error');
                btn.prop('disabled', false).html('Generate License Key');
                if (res.redirect) {
                    closeModal();
                    setTimeout(() => { window.location.href = res.redirect; }, 2500);
                }
            }
        },
        error: function() {
            showToast('Server error occurred.', 'error');
            btn.prop('disabled', false).html('Generate License Key');
        }
    });
});
</script>
