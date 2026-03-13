<?php
include '../include/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reseller') { echo '<p class="text-red-500 text-sm">Unauthorized.</p>'; exit; }
$user_id = $_SESSION['user_id'];
$reseller = $conn->query("SELECT u.*, rp.extra_license_price, rp.plan_name FROM users u LEFT JOIN reseller_plans rp ON u.plan_id=rp.id WHERE u.id='$user_id'")->fetch_assoc();
if (!$reseller || !$reseller['plan_id']) { echo '<div class="text-center py-6"><i class="fas fa-ban text-gray-300 text-3xl mb-2"></i><p class="text-gray-400 text-sm">You need an active plan to buy extra licenses.</p></div>'; exit; }
$sym = get_config('currency_symbol') ?: '$';
$price_per = (float)($reseller['extra_license_price'] ?? 5);
$bank_on   = get_config('bank_transfer_enabled') == '1';
$rzp_on    = get_config('razorpay_enabled') == '1';
$rzp_key   = get_config('razorpay_key_id');
?>

<div class="mb-5 p-4 bg-<?= get_config('theme_color') ?>-50 border border-<?= get_config('theme_color') ?>-100 rounded-xl">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-xs font-bold text-<?= get_config('theme_color') ?>-600 uppercase tracking-wider">Your Plan</p>
            <p class="font-black text-gray-800"><?= htmlspecialchars($reseller['plan_name']) ?></p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-500">Extra License Price</p>
            <p class="font-black text-gray-800"><?= $sym ?><?= number_format($price_per, 2) ?> <span class="text-xs font-medium text-gray-400">/each</span></p>
        </div>
    </div>
</div>

<!-- Quantity Selector -->
<div class="mb-5">
    <label class="block text-xs font-bold text-gray-600 mb-2">How many extra licenses?</label>
    <div class="flex items-center gap-3">
        <button type="button" onclick="changeQty(-1)" class="w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-xl font-bold text-gray-600 text-lg flex items-center justify-center transition-all">−</button>
        <input type="number" id="extraQty" value="5" min="1" max="500" oninput="updateTotal()"
               class="flex-1 text-center text-xl font-black py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50">
        <button type="button" onclick="changeQty(1)" class="w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-xl font-bold text-gray-600 text-lg flex items-center justify-center transition-all">+</button>
    </div>
    <div class="mt-3 p-3 bg-green-50 border border-green-100 rounded-xl flex justify-between items-center">
        <span class="text-sm font-bold text-gray-700">Total Amount:</span>
        <span id="totalAmount" class="text-xl font-black text-green-700"><?= $sym ?><?= number_format(5 * $price_per, 2) ?></span>
    </div>
</div>

<!-- Payment Method -->
<div class="mb-5">
    <label class="block text-xs font-bold text-gray-600 mb-2">Payment Method</label>
    <div class="grid grid-cols-<?= ($bank_on && $rzp_on) ? '2' : '1' ?> gap-3">
        <?php if ($bank_on): ?>
        <label class="flex items-center gap-2.5 p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-<?= get_config('theme_color') ?>-300 transition-all">
            <input type="radio" name="payment_method" value="bank_transfer" checked class="accent-<?= get_config('theme_color') ?>-500" onchange="togglePaymentMethod()">
            <div>
                <p class="text-xs font-bold text-gray-700">🏦 Bank Transfer</p>
                <p class="text-[10px] text-gray-400">Manual review required</p>
            </div>
        </label>
        <?php endif; ?>
        <?php if ($rzp_on): ?>
        <label class="flex items-center gap-2.5 p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-<?= get_config('theme_color') ?>-300 transition-all">
            <input type="radio" name="payment_method" value="razorpay" class="accent-<?= get_config('theme_color') ?>-500" onchange="togglePaymentMethod()">
            <div>
                <p class="text-xs font-bold text-gray-700">💳 Razorpay</p>
                <p class="text-[10px] text-gray-400">Instant activation</p>
            </div>
        </label>
        <?php endif; ?>
    </div>
</div>

<!-- Bank Transfer Fields -->
<?php if ($bank_on): ?>
<div id="bankFields" class="mb-5 space-y-3">
    <?php
    $bname = get_config('bank_name'); $bacc = get_config('bank_account_no');
    $bifsc = get_config('bank_ifsc'); $bholder = get_config('bank_account_name');
    if ($bname || $bacc): ?>
    <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-700 space-y-1">
        <p class="font-bold">Bank Details:</p>
        <?php if ($bholder): ?><p>Name: <span class="font-bold"><?= htmlspecialchars($bholder) ?></span></p><?php endif; ?>
        <?php if ($bname): ?><p>Bank: <span class="font-bold"><?= htmlspecialchars($bname) ?></span></p><?php endif; ?>
        <?php if ($bacc): ?><p>Account: <span class="font-bold"><?= htmlspecialchars($bacc) ?></span></p><?php endif; ?>
        <?php if ($bifsc): ?><p>IFSC: <span class="font-bold"><?= htmlspecialchars($bifsc) ?></span></p><?php endif; ?>
    </div>
    <?php endif; ?>
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1.5">Your Bank Reference / UTR Number <span class="text-red-400">*</span></label>
        <input type="text" id="bankRef" placeholder="e.g. UTR123456789" 
               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all">
    </div>
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1.5">Note (optional)</label>
        <textarea id="resellerNote" rows="2" placeholder="Any extra info for admin..."
                  class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white transition-all resize-none"></textarea>
    </div>
</div>
<?php endif; ?>

<div class="flex gap-3">
    <button type="button" id="submitBtn" onclick="submitExtraLicenses()"
            class="flex-1 px-4 py-3 bg-<?= get_config('theme_color') ?>-500 hover:bg-<?= get_config('theme_color') ?>-600 text-white font-bold text-sm rounded-xl transition-all shadow-sm">
        <i class="fas fa-shopping-cart mr-1.5"></i> Submit Payment Request
    </button>
    <button type="button" onclick="closeModal()"
            class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">
        Cancel
    </button>
</div>

<script>
const pricePerLicense = <?= $price_per ?>;
const sym = '<?= $sym ?>';

function changeQty(delta) {
    const input = document.getElementById('extraQty');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > 500) val = 500;
    input.value = val;
    updateTotal();
}

function updateTotal() {
    const qty   = parseInt(document.getElementById('extraQty').value) || 1;
    const total = (qty * pricePerLicense).toFixed(2);
    document.getElementById('totalAmount').innerText = sym + total;
}

function togglePaymentMethod() {
    const method = document.querySelector('input[name="payment_method"]:checked')?.value;
    const bankDiv = document.getElementById('bankFields');
    if (bankDiv) bankDiv.style.display = method === 'bank_transfer' ? 'block' : 'none';
    const btn = document.getElementById('submitBtn');
    if (method === 'razorpay') {
        btn.innerHTML = '<i class="fas fa-bolt mr-1.5"></i> Pay Now with Razorpay';
    } else {
        btn.innerHTML = '<i class="fas fa-shopping-cart mr-1.5"></i> Submit Payment Request';
    }
}

function submitExtraLicenses() {
    const method  = document.querySelector('input[name="payment_method"]:checked')?.value || 'bank_transfer';
    const qty     = parseInt(document.getElementById('extraQty').value) || 1;
    const btn     = document.getElementById('submitBtn');
    const origTxt = btn.innerHTML;

    if (method === 'razorpay') {
        handleRazorpay('extra_licenses', null, qty, btn, origTxt);
        return;
    }

    const bankRef = document.getElementById('bankRef')?.value.trim();
    const note    = document.getElementById('resellerNote')?.value.trim();

    if (!bankRef) { showToast('Please enter your bank reference/UTR number.', 'error'); return; }

    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Submitting...';
    btn.disabled  = true;

    $.post('api/payment_api.php', {
        action: 'submit_bank_transfer',
        payment_type: 'extra_licenses',
        extra_qty: qty,
        bank_ref: bankRef,
        reseller_note: note
    }, function(res) {
        if (res.status === 'success') { showToast(res.message, 'success'); closeModal(); setTimeout(() => location.reload(), 2000); }
        else { showToast(res.message, 'error'); btn.innerHTML = origTxt; btn.disabled = false; }
    }, 'json');
}

function handleRazorpay(type, planId, qty, btn, origTxt) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Initializing...';
    btn.disabled  = true;

    $.post('api/payment_api.php', { action: 'create_razorpay_order', payment_type: type, plan_id: planId, extra_qty: qty }, function(res) {
        if (res.status !== 'success') { showToast(res.message, 'error'); btn.innerHTML = origTxt; btn.disabled = false; return; }

        const options = {
            key: res.key,
            amount: res.amount,
            currency: 'INR',
            name: '<?= get_config('site_name') ?>',
            description: type === 'extra_licenses' ? 'Extra Licenses' : 'Plan Purchase',
            order_id: res.order_id,
            handler: function(response) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Verifying...';
                $.post('api/payment_api.php', {
                    action: 'verify_razorpay_payment',
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature,
                    payment_type: type,
                    plan_id: planId,
                    extra_qty: qty
                }, function(vRes) {
                    if (vRes.status === 'success') { showToast(vRes.message, 'success'); closeModal(); setTimeout(() => location.reload(), 2000); }
                    else { showToast(vRes.message, 'error'); btn.innerHTML = origTxt; btn.disabled = false; }
                }, 'json');
            },
            modal: { ondismiss: function() { btn.innerHTML = origTxt; btn.disabled = false; } }
        };
        const rzp = new Razorpay(options);
        rzp.open();
    }, 'json').fail(function() { showToast('Server error.', 'error'); btn.innerHTML = origTxt; btn.disabled = false; });
}
</script>
<?php if ($rzp_on && $rzp_key): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php endif; ?>
