<?php
include 'include/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    header("Location: dashboard"); exit();
}

$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$name     = $_SESSION['name'];
$title    = "Settings";

// Get all config values
$cfg = [];
$result = $conn->query("SELECT config_key, config_value FROM app_config");
while ($row = $result->fetch_assoc()) { $cfg[$row['config_key']] = $row['config_value']; }

// Default values
$cfg_defaults = ['razorpay_key_id'=>'','razorpay_key_secret'=>'','razorpay_enabled'=>'0','bank_name'=>'','bank_account_no'=>'','bank_ifsc'=>'','bank_account_name'=>'','bank_transfer_enabled'=>'1','currency'=>'USD','currency_symbol'=>'$'];
foreach ($cfg_defaults as $k => $v) { if (!isset($cfg[$k])) $cfg[$k] = $v; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | <?= get_config('site_name') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/png" href="<?= get_config('circle_logo_path') ?>">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; overflow: hidden; }
        .nav-item { white-space: nowrap; overflow: hidden; transition: all 0.2s ease; display: flex; align-items: center; }
        .active-link-white { background-color: #f0fdf4 !important; border: 1px solid #dcfce7; color: #166534 !important; }
        .active-link-white i { color: #22c55e !important; }
        .tab-btn.active { background: white; border-color: <?= get_config('theme_color') === 'blue' ? '#3b82f6' : '#22c55e' ?>; color: #1f2937; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="flex h-screen">
<?php include 'sections/sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'sections/navbar.php'; ?>

<main class="flex-1 overflow-y-auto p-8 antialiased">
    <div class="mb-8">
        <h1 class="text-xl font-bold text-gray-800">Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Configure your application, branding, and payment gateways.</p>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 mb-6 p-1 bg-gray-100 rounded-2xl w-fit">
        <button onclick="showTab('general')" id="tab-general" class="tab-btn active px-5 py-2 rounded-xl text-xs font-bold text-gray-500 border border-transparent transition-all">General</button>
        <button onclick="showTab('payment')" id="tab-payment" class="tab-btn px-5 py-2 rounded-xl text-xs font-bold text-gray-500 border border-transparent transition-all">Payment</button>
        <button onclick="showTab('razorpay')" id="tab-razorpay" class="tab-btn px-5 py-2 rounded-xl text-xs font-bold text-gray-500 border border-transparent transition-all">Razorpay</button>
    </div>

    <!-- GENERAL TAB -->
    <div id="tab-content-general" class="tab-content">
        <form onsubmit="saveSettings(event, 'general')">
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 max-w-2xl">
                <h2 class="font-bold text-gray-800 text-sm mb-5 flex items-center gap-2"><i class="fas fa-sliders-h text-<?= get_config('theme_color') ?>-500"></i> General Settings</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Site Name</label>
                        <input type="text" name="site_name" value="<?= htmlspecialchars($cfg['site_name'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Theme Color</label>
                        <select name="theme_color" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50">
                            <?php foreach (['blue','green','purple','orange','pink','indigo','red','teal'] as $c): ?>
                            <option value="<?= $c ?>" <?= ($cfg['theme_color'] ?? '') == $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Default Software</label>
                        <input type="text" name="default_software" value="<?= htmlspecialchars($cfg['default_software'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Support Email</label>
                        <input type="email" name="support_email" value="<?= htmlspecialchars($cfg['support_email'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Support WhatsApp</label>
                        <input type="text" name="support_whatsapp" value="<?= htmlspecialchars($cfg['support_whatsapp'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white">
                    </div>
                </div>
                <button type="submit" class="mt-6 px-6 py-2.5 bg-<?= get_config('theme_color') ?>-500 hover:bg-<?= get_config('theme_color') ?>-600 text-white font-bold text-sm rounded-xl transition-all shadow-sm">
                    <i class="fas fa-save mr-1.5"></i> Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- PAYMENT TAB -->
    <div id="tab-content-payment" class="tab-content hidden">
        <form onsubmit="saveSettings(event, 'payment')">
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 max-w-2xl">
                <h2 class="font-bold text-gray-800 text-sm mb-5 flex items-center gap-2"><i class="fas fa-university text-blue-500"></i> Bank Transfer Settings</h2>

                <div class="flex items-center justify-between mb-5 p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <div>
                        <p class="text-sm font-bold text-gray-700">Bank Transfer</p>
                        <p class="text-xs text-gray-400">Allow resellers to pay via bank transfer</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="bank_transfer_enabled" value="1" <?= ($cfg['bank_transfer_enabled'] ?? '1') == '1' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-<?= get_config('theme_color') ?>-500"></div>
                    </label>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Currency Code</label>
                            <input type="text" name="currency" value="<?= htmlspecialchars($cfg['currency'] ?? 'USD') ?>"
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Currency Symbol</label>
                            <input type="text" name="currency_symbol" value="<?= htmlspecialchars($cfg['currency_symbol'] ?? '$') ?>"
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Account Holder Name</label>
                        <input type="text" name="bank_account_name" value="<?= htmlspecialchars($cfg['bank_account_name'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Bank Name</label>
                        <input type="text" name="bank_name" value="<?= htmlspecialchars($cfg['bank_name'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Account Number</label>
                        <input type="text" name="bank_account_no" value="<?= htmlspecialchars($cfg['bank_account_no'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">IFSC / SWIFT / Routing Code</label>
                        <input type="text" name="bank_ifsc" value="<?= htmlspecialchars($cfg['bank_ifsc'] ?? '') ?>"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-<?= get_config('theme_color') ?>-400 bg-gray-50 focus:bg-white">
                    </div>
                </div>

                <button type="submit" class="mt-6 px-6 py-2.5 bg-<?= get_config('theme_color') ?>-500 hover:bg-<?= get_config('theme_color') ?>-600 text-white font-bold text-sm rounded-xl transition-all shadow-sm">
                    <i class="fas fa-save mr-1.5"></i> Save Bank Settings
                </button>
            </div>
        </form>
    </div>

    <!-- RAZORPAY TAB -->
    <div id="tab-content-razorpay" class="tab-content hidden">
        <form onsubmit="saveSettings(event, 'razorpay')">
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 max-w-2xl">
                <h2 class="font-bold text-gray-800 text-sm mb-2 flex items-center gap-2">
                    <i class="fas fa-credit-card text-blue-500"></i> Razorpay Integration
                </h2>
                <p class="text-xs text-gray-400 mb-5">Connect your Razorpay account to allow instant online payments from resellers.</p>

                <div class="flex items-center justify-between mb-5 p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <div>
                        <p class="text-sm font-bold text-gray-700">Enable Razorpay</p>
                        <p class="text-xs text-gray-400">Show Razorpay payment option to resellers</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="razorpay_enabled" value="1" <?= ($cfg['razorpay_enabled'] ?? '0') == '1' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                    </label>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Razorpay Key ID</label>
                        <input type="text" name="razorpay_key_id" value="<?= htmlspecialchars($cfg['razorpay_key_id'] ?? '') ?>"
                               placeholder="rzp_live_xxxxxxxxxxxx"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-400 bg-gray-50 focus:bg-white font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Razorpay Key Secret <span class="text-red-400">*</span></label>
                        <input type="password" name="razorpay_key_secret" value="<?= htmlspecialchars($cfg['razorpay_key_secret'] ?? '') ?>"
                               placeholder="Secret key (kept server-side)"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-400 bg-gray-50 focus:bg-white font-mono">
                    </div>
                </div>

                <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    Get your API keys from <a href="https://dashboard.razorpay.com/app/keys" target="_blank" class="underline font-bold">Razorpay Dashboard → Settings → API Keys</a>.
                    The secret key is stored securely and never exposed to the browser.
                </div>

                <button type="submit" class="mt-6 px-6 py-2.5 bg-blue-500 hover:bg-blue-600 text-white font-bold text-sm rounded-xl transition-all shadow-sm">
                    <i class="fas fa-save mr-1.5"></i> Save Razorpay Settings
                </button>
            </div>
        </form>
    </div>

</main>
</div>

<script>
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-content-' + tab).classList.remove('hidden');
    document.getElementById('tab-' + tab).classList.add('active');
}

function saveSettings(e, section) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    data.append('action', 'save_settings_section');
    data.append('section', section);

    // Handle unchecked checkboxes
    form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        if (!cb.checked) data.set(cb.name, '0');
    });

    $.ajax({
        url: 'api/settings_api.php',
        type: 'POST',
        data: data,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1500); }
            else showToast(res.message, 'error');
        },
        error: function() { showToast('Server error.', 'error'); }
    });
}
</script>

<?php include 'sections/footer.php'; ?>
