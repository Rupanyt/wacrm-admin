<?php
include '../include/config.php';
$reseller_id = (int)($_GET['id'] ?? 0);
$reseller    = $conn->query("
    SELECT u.*, rp.plan_name as current_plan_name
    FROM users u
    LEFT JOIN reseller_plans rp ON u.plan_id = rp.id
    WHERE u.id = '$reseller_id' AND u.role = 'reseller'
")->fetch_assoc();

if (!$reseller) { echo '<p class="text-red-500 text-sm">Reseller not found.</p>'; exit; }

$plans = $conn->query("SELECT * FROM reseller_plans WHERE status='active' ORDER BY price ASC");
$sym   = get_config('currency_symbol') ?: '$';
?>

<!-- Reseller Info -->
<div class="mb-4 p-3 bg-gray-50 rounded-xl border border-gray-100">
    <p class="text-xs text-gray-500 font-medium">Assigning plan to:</p>
    <p class="font-bold text-gray-800"><?= htmlspecialchars($reseller['name'] ?: $reseller['username']) ?></p>
    <?php if ($reseller['current_plan_name']): ?>
    <p class="text-xs text-<?= get_config('theme_color') ?>-600 mt-0.5">
        Current plan: <strong><?= htmlspecialchars($reseller['current_plan_name']) ?></strong>
        (expires: <?= $reseller['plan_expiry'] ?>)
    </p>
    <?php else: ?>
    <p class="text-xs text-gray-400 mt-0.5">No plan assigned yet.</p>
    <?php endif; ?>
</div>

<form onsubmit="assignPlan(event)">
    <input type="hidden" name="reseller_id" value="<?= $reseller_id ?>">
    <div class="space-y-3 mb-4">
        <?php if ($plans && $plans->num_rows > 0): while ($plan = $plans->fetch_assoc()): ?>
        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-<?= get_config('theme_color') ?>-300 hover:bg-<?= get_config('theme_color') ?>-50/50 transition-all">
            <input type="radio" name="plan_id" value="<?= $plan['id'] ?>"
                   <?= $reseller['plan_id'] == $plan['id'] ? 'checked' : '' ?>
                   class="accent-<?= get_config('theme_color') ?>-500">
            <div class="flex-1">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-sm text-gray-800"><?= htmlspecialchars($plan['plan_name']) ?></span>
                    <span class="font-black text-gray-800 text-sm"><?= $sym ?><?= number_format($plan['price'], 2) ?></span>
                </div>
                <p class="text-[11px] text-gray-400 mt-0.5">
                    <?= number_format($plan['license_limit']) ?> licenses &bull;
                    <?= $plan['validity_days'] ?> days &bull;
                    Extra <?= $sym ?><?= number_format($plan['extra_license_price'], 2) ?>/each
                </p>
            </div>
        </label>
        <?php endwhile; else: ?>
        <p class="text-sm text-gray-400 text-center py-4">No active plans. Create plans first from the Plans page.</p>
        <?php endif; ?>
    </div>

    <div class="p-3 bg-yellow-50 border border-yellow-100 rounded-xl text-xs text-yellow-700 mb-4 flex items-start gap-2">
        <i class="fas fa-info-circle mt-0.5 flex-shrink-0"></i>
        Assigning a plan here does <strong>not</strong> charge the reseller. Use this to manually activate or override a plan.
    </div>

    <div class="flex gap-3">
        <button type="submit" id="assignPlanBtn"
                class="flex-1 px-4 py-2.5 bg-<?= get_config('theme_color') ?>-500 hover:bg-<?= get_config('theme_color') ?>-600 text-white font-bold text-sm rounded-xl transition-all">
            <i class="fas fa-check mr-1.5"></i> Assign Plan
        </button>
        <button type="button" onclick="closeModal()"
                class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">
            Cancel
        </button>
    </div>
</form>

<script>
function assignPlan(e) {
    e.preventDefault();
    const btn  = document.getElementById('assignPlanBtn');
    const orig = btn.innerHTML;

    if (!document.querySelector('input[name="plan_id"]:checked')) {
        showToast('Please select a plan.', 'error'); return;
    }

    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Assigning...';
    btn.disabled  = true;

    const data = new FormData(e.target);
    data.append('action', 'assign_plan');

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
