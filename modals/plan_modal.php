<?php include '../include/config.php'; ?>
<form onsubmit="savePlan(event)">
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Plan Name <span class="text-red-400">*</span></label>
                <input type="text" name="plan_name" placeholder="e.g. Starter, Growth, Pro" required
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">License Limit <span class="text-red-400">*</span></label>
                <input type="number" name="license_limit" placeholder="10" min="1" required
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Validity (Days) <span class="text-red-400">*</span></label>
                <select name="validity_days" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all">
                    <option value="30">30 Days</option>
                    <option value="90">90 Days</option>
                    <option value="180">180 Days</option>
                    <option value="365" selected>1 Year (365 Days)</option>
                    <option value="730">2 Years (730 Days)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Plan Price (<?= get_config('currency_symbol') ?: '$' ?>) <span class="text-red-400">*</span></label>
                <input type="number" name="price" placeholder="49.00" min="0" step="0.01" required
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Extra License Price (<?= get_config('currency_symbol') ?: '$' ?>/each) <span class="text-red-400">*</span></label>
                <input type="number" name="extra_license_price" placeholder="5.00" min="0" step="0.01" required
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Description</label>
                <textarea name="description" rows="2" placeholder="Short plan description shown to resellers..."
                          class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all resize-none"></textarea>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" id="savePlanBtn"
                    class="flex-1 px-4 py-2.5 bg-<?= get_config('theme_color') ?>-500 hover:bg-<?= get_config('theme_color') ?>-600 text-white font-bold text-sm rounded-xl transition-all shadow-sm">
                <i class="fas fa-plus mr-1.5"></i> Create Plan
            </button>
            <button type="button" onclick="closeModal()"
                    class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">
                Cancel
            </button>
        </div>
    </div>
</form>

<script>
function savePlan(e) {
    e.preventDefault();
    const btn = document.getElementById('savePlanBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Creating...';
    btn.disabled = true;

    const form = e.target;
    const data = new FormData(form);
    data.append('action', 'save_plan');

    $.ajax({
        url: 'api/plan_api.php',
        type: 'POST',
        data: data,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                closeModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(res.message, 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        },
        error: function() {
            showToast('Server error occurred.', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}
</script>
