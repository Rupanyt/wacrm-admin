<?php
include '../include/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reseller') {
    echo '<p class="text-red-500 text-sm">Unauthorized.</p>'; exit;
}
$user_id   = $_SESSION['user_id'];
$reseller  = $conn->query("SELECT * FROM users WHERE id='$user_id'")->fetch_assoc();
$preselect = (int)($_GET['plan_id'] ?? 0);
$sym       = get_config('currency_symbol') ?: '$';
$bank_on   = get_config('bank_transfer_enabled') == '1';
$rzp_on    = get_config('razorpay_enabled') == '1';
$rzp_key   = get_config('razorpay_key_id');
$has_plan  = !empty($reseller['plan_id']);
$plans     = $conn->query("SELECT * FROM reseller_plans WHERE status='active' ORDER BY price ASC");

// Build price map for JS
$price_map = [];
$tmp = $conn->query("SELECT id, price FROM reseller_plans WHERE status='active'");
while ($r = $tmp->fetch_assoc()) $price_map[$r['id']] = $r['price'];
?>

<p class="text-xs text-gray-500 mb-4"><?= $has_plan ? 'Select a new plan to upgrade. Your current plan will be replaced immediately after payment is approved.' : 'Choose a plan to get started.' ?></p>

<!-- Plan List -->
<div class="space-y-2 mb-5">
    <?php while ($plan = $plans->fetch_assoc()):
        $is_current = $has_plan && $reseller['plan_id'] == $plan['id'];
        $is_presel  = (!$is_current && $preselect === $plan['id']);
    ?>
    <label class="flex items-center gap-3 p-3.5 border <?= $is_current ? 'border-gray-200 bg-gray-50 opacity-60 cursor-not-allowed' : 'border-gray-200 hover:border-'.get_config('theme_color').'-300 hover:bg-'.get_config('theme_color').'-50/40 cursor-pointer' ?> rounded-xl transition-all">
        <input type="radio" name="plan_id" value="<?= $plan['id'] ?>"
               <?= $is_presel ? 'checked' : '' ?>
               <?= $is_current ? 'disabled' : '' ?>
               class="accent-<?= get_config('theme_color') ?>-500"
               onchange="updatePaymentTotal()">
        <div class="flex-1">
            <div class="flex justify-between items-center">
                <span class="font-bold text-sm text-gray-800"><?= htmlspecialchars($plan['plan_name']) ?></span>
                <div class="text-right">
                    <span class="font-black text-gray-800"><?= $sym ?><?= number_format($plan['price'], 2) ?></span>
                    <span class="text-[10px] text-gray-400 ml-1">/<?= $plan['validity_days'] == 365 ? 'yr' : $plan['validity_days'].'d' ?></span>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 mt-0.5"><?= number_format($plan['license_limit']) ?> licenses &bull; +<?= $sym ?><?= number_format($plan['extra_license_price'], 2) ?>/extra</p>
        </div>
        <?php if ($is_current): ?>
        <span class="text-[10px] font-bold text-gray-400 bg-gray-200 px-2 py-0.5 rounded-full flex-shrink-0">Current</span>
        <?php endif; ?>
    </label>
    <?php endwhile; ?>
</div>

<div id="amountDisplay" class="hidden mb-4 p-3 bg-green-50 border border-green-100 rounded-xl flex justify-between items-center">
    <span class="text-sm font-bold text-gray-700">Amount to Pay:</span>
    <span id="selectedAmount" class="text-xl font-black text-green-700">-</span>
</div>

<!-- Payment Method -->
<div class="mb-5">
    <label class="block text-xs font-bold text-gray-600 mb-2">Payment Method</label>
    <div class="grid grid-cols-<?= ($bank_on && $rzp_on) ? '2' : '1' ?> gap-3">
        <?php if ($bank_on): ?>
        <label class="flex items-center gap-2.5 p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-<?= get_config('theme_color') ?>-300 transition-all">
            <input type="radio" name="payment_method" value="bank_transfer" checked
                   class="accent-<?= get_config('theme_color') ?>-500" onchange="toggleBankFields()">
            <div>
                <p class="text-xs font-bold text-gray-700">🏦 Bank Transfer</p>
                <p class="text-[10px] text-gray-400">Manual review</p>
            </div>
        </label>
        <?php endif; ?>
        <?php if ($rzp_on): ?>
        <label class="flex items-center gap-2.5 p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-<?= get_config('theme_color') ?>-300 transition-all">
            <input type="radio" name="payment_method" value="razorpay"
                   class="accent-<?= get_config('theme_color') ?>-500" onchange="toggleBankFields()">
            <div>
                <p class="text-xs font-bold text-gray-700">💳 Razorpay</p>
                <p class="text-[10px] text-gray-400">Instant activation</p>
            </div>
        </label>
        <?php endif; ?>
    </div>
</div>

<?php if ($bank_on): ?>
<div id="bankFields2" class="space-y-3 mb-4">
    <?php
    $bname    = get_config('bank_name');
    $bacc     = get_config('bank_account_no');
    $bifsc    = get_config('bank_ifsc');
    $bholder  = get_config('bank_account_name');
    if ($bname || $bacc): ?>
    <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-700 space-y-1">
        <p class="font-bold">Transfer to:</p>
        <?php if ($bholder): ?><p>Name: <span class="font-bold"><?= htmlspecialchars($bholder) ?></span></p><?php endif; ?>
        <?php if ($bname): ?><p>Bank: <span class="font-bold"><?= htmlspecialchars($bname) ?></span></p><?php endif; ?>
        <?php if ($bacc): ?><p>Account: <span class="font-bold"><?= htmlspecialchars($bacc) ?></span></p><?php endif; ?>
        <?php if ($bifsc): ?><p>IFSC: <span class="font-bold"><?= htmlspecialchars($bifsc) ?></span></p><?php endif; ?>
    </div>
    <?php endif; ?>
    <input type="text" id="upgBankRef" placeholder="Bank Reference / UTR Number *"
           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all">
    <textarea id="upgNote" rows="2" placeholder="Optional note for admin..."
              class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all resize-none"></textarea>
</div>
<?php endif; ?>

<div class="flex gap-3">
    <button type="button" id="upgBtn" onclick="submitUpgrade()"
            class="flex-1 px-4 py-3 bg-<?= get_config('theme_color') ?>-500 hover:bg-<?= get_config('theme_color') ?>-600 text-white font-bold text-sm rounded-xl transition-all shadow-sm">
        <i class="fas fa-arrow-circle-up mr-1.5"></i> <?= $has_plan ? 'Upgrade Plan' : 'Subscribe Now' ?>
    </button>
    <button type="button" onclick="closeModal()"
            class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">
        Cancel
    </button>
</div>

<script>
const planPrices2 = <?= json_encode($price_map) ?>;
const sym3        = '<?= $sym ?>';
const hasPlan2    = <?= $has_plan ? 'true' : 'false' ?>;

function updatePaymentTotal() {
    const sel  = document.querySelector('input[name="plan_id"]:checked');
    const disp = document.getElementById('amountDisplay');
    if (sel) {
        document.getElementById('selectedAmount').innerText = sym3 + parseFloat(planPrices2[sel.value] || 0).toFixed(2);
        disp.classList.remove('hidden');
    } else {
        disp.classList.add('hidden');
    }
}

function toggleBankFields() {
    const method = document.querySelector('input[name="payment_method"]:checked')?.value;
    const bf     = document.getElementById('bankFields2');
    const btn    = document.getElementById('upgBtn');
    if (bf) bf.style.display = method === 'bank_transfer' ? 'block' : 'none';
    if (method === 'razorpay') {
        btn.innerHTML = '<i class="fas fa-bolt mr-1.5"></i> Pay Now with Razorpay';
    } else {
        btn.innerHTML = '<i class="fas fa-arrow-circle-up mr-1.5"></i> ' + (hasPlan2 ? 'Upgrade Plan' : 'Subscribe Now');
    }
}

function submitUpgrade() {
    const planSel = document.querySelector('input[name="plan_id"]:checked');
    if (!planSel) { showToast('Please select a plan.', 'error'); return; }

    const method  = document.querySelector('input[name="payment_method"]:checked')?.value || 'bank_transfer';
    const btn     = document.getElementById('upgBtn');
    const orig    = btn.innerHTML;
    const type    = hasPlan2 ? 'plan_upgrade' : 'plan_purchase';

    if (method === 'razorpay') {
        handleRazorpay2(type, planSel.value, 0, btn, orig); return;
    }

    const ref  = document.getElementById('upgBankRef')?.value.trim();
    const note = document.getElementById('upgNote')?.value.trim();
    if (!ref) { showToast('Bank reference number is required.', 'error'); return; }

    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Submitting...';
    btn.disabled  = true;

    $.post('api/payment_api.php', {
        action: 'submit_bank_transfer', payment_type: type,
        plan_id: planSel.value, bank_ref: ref, reseller_note: note
    }, function(res) {
        if (res.status === 'success') {
            showToast(res.message, 'success'); closeModal(); setTimeout(() => location.reload(), 2000);
        } else {
            showToast(res.message, 'error'); btn.innerHTML = orig; btn.disabled = false;
        }
    }, 'json');
}

function handleRazorpay2(type, planId, qty, btn, origTxt) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Initializing...';
    btn.disabled  = true;
    $.post('api/payment_api.php', { action: 'create_razorpay_order', payment_type: type, plan_id: planId, extra_qty: qty }, function(res) {
        if (res.status !== 'success') { showToast(res.message, 'error'); btn.innerHTML = origTxt; btn.disabled = false; return; }
        const rzp = new Razorpay({
            key: res.key, amount: res.amount, currency: 'INR',
            name: '<?= addslashes(get_config('site_name')) ?>', order_id: res.order_id,
            handler: function(r) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Verifying...';
                $.post('api/payment_api.php', {
                    action: 'verify_razorpay_payment',
                    razorpay_payment_id: r.razorpay_payment_id,
                    razorpay_order_id: r.razorpay_order_id,
                    razorpay_signature: r.razorpay_signature,
                    payment_type: type, plan_id: planId, extra_qty: qty
                }, function(v) {
                    if (v.status === 'success') { showToast(v.message, 'success'); closeModal(); setTimeout(() => location.reload(), 2000); }
                    else { showToast(v.message, 'error'); btn.innerHTML = origTxt; btn.disabled = false; }
                }, 'json');
            },
            modal: { ondismiss: function() { btn.innerHTML = origTxt; btn.disabled = false; } }
        });
        rzp.open();
    }, 'json');
}

// Auto-select preselected plan
<?php if ($preselect > 0): ?>
const prePlan = document.querySelector('input[name="plan_id"][value="<?= $preselect ?>"]');
if (prePlan && !prePlan.disabled) { prePlan.checked = true; updatePaymentTotal(); }
<?php endif; ?>
</script>
<?php if ($rzp_on && $rzp_key): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php endif; ?>
