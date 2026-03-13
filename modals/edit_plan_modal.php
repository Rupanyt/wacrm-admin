<?php
include '../include/config.php';
$id   = (int)($_GET['id'] ?? 0);
$plan = $conn->query("SELECT * FROM reseller_plans WHERE id='$id'")->fetch_assoc();
if (!$plan) { echo '<p class="text-red-500 text-sm">Plan not found.</p>'; exit; }
$sym = get_config('currency_symbol') ?: '$';
?>
<form onsubmit="updatePlan(event)">
    <input type="hidden" name="id" value="<?= $plan['id'] ?>">
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Plan Name <span class="text-red-400">*</span></label>
                <input type="text" name="plan_name" value="<?= htmlspecialchars($plan['plan_name']) ?>" required
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">License Limit <span class="text-red-400">*</span></label>
                <input type="number" name="license_limit" value="<?= $plan['license_limit'] ?>" min="1" required
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Validity (Days)</label>
                <select name="validity_days"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all">
                    <?php foreach ([30, 90, 180, 365, 730] as $d): ?>
                    <option value="<?= $d ?>" <?= $plan['validity_days'] == $d ? 'selected' : '' ?>>
                        <?= $d ?> Days<?= $d == 365 ? ' (1 Year)' : ($d == 730 ? ' (2 Years)' : '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Plan Price (<?= $sym ?>)</label>
                <input type="number" name="price" value="<?= $plan['price'] ?>" min="0" step="0.01" required
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Extra License Price (<?= $sym ?>/each)</label>
                <input type="number" name="extra_license_price" value="<?= $plan['extra_license_price'] ?>" min="0" step="0.01" required
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Description</label>
                <textarea name="description" rows="2"
                          class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all resize-none"><?= htmlspecialchars($plan['description'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" id="updatePlanBtn"
                    class="flex-1 px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white font-bold text-sm rounded-xl transition-all">
                <i class="fas fa-save mr-1.5"></i> Save Changes
            </button>
            <button type="button" onclick="closeModal()"
                    class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">
                Cancel
            </button>
        </div>
    </div>
</form>

<script>
function updatePlan(e) {
    e.preventDefault();
    const btn  = document.getElementById('updatePlanBtn');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Saving...';
    btn.disabled  = true;

    const data = new FormData(e.target);
    data.append('action', 'update_plan');

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
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast(res.message, 'error');
                btn.innerHTML = orig;
                btn.disabled  = false;
            }
        },
        error: function() {
            showToast('Server error.', 'error');
            btn.innerHTML = orig;
            btn.disabled  = false;
        }
    });
}
</script>
