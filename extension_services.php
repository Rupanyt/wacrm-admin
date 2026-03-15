<?php
// extension_services.php
// Admin page to configure the /api/services/update endpoint
include 'include/config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','admin'])) {
    header("Location: dashboard"); exit();
}
$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$title    = "Extension Services Config";

// Load all extension config
$cfg = [];
$cfgResult = $conn->query("SELECT config_key, config_value FROM app_config WHERE config_key LIKE 'ext_%' OR config_key='services_cript_key'");
while ($row = $cfgResult->fetch_assoc()) {
    $cfg[$row['config_key']] = $row['config_value'];
}

// Test: count active licenses
$total_licenses   = $conn->query("SELECT COUNT(*) FROM licenses WHERE status='active'")->fetch_row()[0];
$expiring_soon    = $conn->query("SELECT COUNT(*) FROM licenses WHERE status='active' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch_row()[0];
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
        body { background:#f8fafc; font-family:'Inter',sans-serif; overflow:hidden; }
        .nav-item { white-space:nowrap; overflow:hidden; transition:all .2s; display:flex; align-items:center; }
        .active-link-white { background:#f0fdf4!important; border:1px solid #dcfce7; color:#166534!important; }
        .active-link-white i { color:#22c55e!important; }
    </style>
</head>
<body class="flex h-screen">
<?php include 'sections/sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'sections/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-8 antialiased">

<div class="flex items-start justify-between mb-8">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Extension Services Config</h1>
        <p class="text-sm text-gray-500 mt-1">Configure what the Chrome extension receives when it calls <code class="bg-gray-100 text-indigo-600 px-1.5 py-0.5 rounded text-xs font-mono">POST /api/services/update</code> every 5 minutes.</p>
    </div>
    <button onclick="testEndpoint()"
            class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-sm rounded-xl flex items-center gap-2 transition-all border border-indigo-100">
        <i class="fas fa-flask text-xs"></i> Test Endpoint
    </button>
</div>

<!-- Stats row -->
<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
        <div class="w-9 h-9 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-key"></i></div>
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Active Licenses</p>
            <h3 class="text-lg font-black text-gray-800"><?= number_format($total_licenses) ?></h3>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
        <div class="w-9 h-9 bg-orange-50 text-orange-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-clock"></i></div>
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Expiring in 7 Days</p>
            <h3 class="text-lg font-black text-gray-800"><?= number_format($expiring_soon) ?></h3>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
        <div class="w-9 h-9 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-wifi"></i></div>
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Endpoint Status</p>
            <h3 class="text-sm font-black text-green-600" id="endpointStatus">—</h3>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- LEFT: Config form -->
    <div class="lg:col-span-2 space-y-5">

        <!-- Auth -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-shield-alt"></i></div>
                <div>
                    <h3 class="font-bold text-gray-700 text-sm">Authentication</h3>
                    <p class="text-[11px] text-gray-400">Must match <code class="font-mono">cript_key</code> in extension's background.js</p>
                </div>
            </div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Crypt Key (access-token header)</label>
            <div class="flex gap-2">
                <input type="text" id="services_cript_key" value="<?= htmlspecialchars($cfg['services_cript_key'] ?? 'ffce211a-7b07-4d91-ba5d-c40bb4034a83') ?>"
                       class="flex-1 px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white font-mono transition-all">
                <button onclick="copyToClipboard('services_cript_key')"
                        class="px-3 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs rounded-xl transition-all">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <p class="text-[11px] text-amber-600 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mt-2">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                This key is hardcoded in the compiled extension. Only change it when deploying a new extension build.
            </p>
        </div>

        <!-- Branding -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 bg-purple-50 text-purple-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-paint-brush"></i></div>
                <div>
                    <h3 class="font-bold text-gray-700 text-sm">White-Label Branding</h3>
                    <p class="text-[11px] text-gray-400">Extension name & ID returned in every sync response</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Extension Name (nome)</label>
                    <input type="text" id="ext_nome" value="<?= htmlspecialchars($cfg['ext_nome'] ?? 'GD CRM') ?>"
                           placeholder="GD CRM"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">WL ID (wl_id)</label>
                    <input type="text" id="ext_wl_id" value="<?= htmlspecialchars($cfg['ext_wl_id'] ?? 'gdcrm') ?>"
                           placeholder="gdcrm"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white font-mono transition-all">
                </div>
            </div>
        </div>

        <!-- URLs -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-link"></i></div>
                <div>
                    <h3 class="font-bold text-gray-700 text-sm">Extension URLs</h3>
                    <p class="text-[11px] text-gray-400">Returned in every sync — extension uses these for navigation and support</p>
                </div>
            </div>
            <div class="space-y-4">
                <?php
                $url_fields = [
                    ['ext_checkout_url',   'Checkout URL',     'Opened when free user clicks Upgrade',     'fas fa-shopping-cart', 'green'],
                    ['ext_painel_cliente', 'Panel Client URL', 'Link to your CRM panel login page',        'fas fa-th-large',      'indigo'],
                    ['ext_tutorial_url',   'Tutorial URL',     'Tutorial link shown in extension (optional)','fas fa-graduation-cap','yellow'],
                ];
                foreach ($url_fields as [$key, $label, $hint, $icon, $color]):
                ?>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">
                        <i class="<?= $icon ?> text-<?= $color ?>-400 mr-1"></i>
                        <?= $label ?>
                    </label>
                    <p class="text-[10px] text-gray-400 mb-1.5"><?= $hint ?></p>
                    <input type="url" id="<?= $key ?>" value="<?= htmlspecialchars($cfg[$key] ?? '') ?>"
                           placeholder="https://..."
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Support -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 bg-green-50 text-green-500 rounded-xl flex items-center justify-center text-sm"><i class="fab fa-whatsapp"></i></div>
                <div>
                    <h3 class="font-bold text-gray-700 text-sm">Support Links</h3>
                    <p class="text-[11px] text-gray-400">Separate support contacts for premium and free users</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5"><span class="text-yellow-500">★</span> Premium Support</label>
                    <input type="text" id="ext_support_premium" value="<?= htmlspecialchars($cfg['ext_support_premium'] ?? '') ?>"
                           placeholder="https://wa.me/91..."
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Free Support</label>
                    <input type="text" id="ext_support_free" value="<?= htmlspecialchars($cfg['ext_support_free'] ?? '') ?>"
                           placeholder="https://wa.me/91..."
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
                </div>
            </div>
        </div>

        <!-- Phone lookup toggle -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 bg-indigo-50 text-indigo-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-mobile-alt"></i></div>
                <div>
                    <h3 class="font-bold text-gray-700 text-sm">Phone Lookup</h3>
                    <p class="text-[11px] text-gray-400">Auto-match license by WhatsApp phone number</p>
                </div>
            </div>
            <label class="flex items-center gap-3 cursor-pointer">
                <div class="relative">
                    <input type="checkbox" id="ext_phone_lookup_enabled" class="sr-only peer"
                           <?= ($cfg['ext_phone_lookup_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-gray-200 peer-checked:bg-indigo-600 rounded-full peer transition-all"></div>
                    <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow peer-checked:translate-x-5 transition-all"></div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-700">Enable auto phone lookup</p>
                    <p class="text-[11px] text-gray-400">When ON: extension sends phone → system looks up license and returns premium status automatically. When OFF: user must manually login with license key.</p>
                </div>
            </label>
        </div>

        <!-- Social Networks -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-9 h-9 bg-pink-50 text-pink-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-share-alt"></i></div>
                <div>
                    <h3 class="font-bold text-gray-700 text-sm">Social Networks</h3>
                    <p class="text-[11px] text-gray-400">Returned under <code class="font-mono bg-gray-100 px-1 rounded">urls.redes_sociais</code> in initial-data</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <?php
                $socials = [
                    ['ext_youtube_url',   'YouTube',   'fab fa-youtube',   'red'],
                    ['ext_instagram_url', 'Instagram', 'fab fa-instagram',  'pink'],
                    ['ext_facebook_url',  'Facebook',  'fab fa-facebook',   'blue'],
                    ['ext_telegram_url',  'Telegram',  'fab fa-telegram',   'sky'],
                    ['ext_tiktok_url',    'TikTok',    'fab fa-tiktok',     'gray'],
                    ['ext_twitter_url',   'Twitter/X', 'fab fa-x-twitter',  'gray'],
                ];
                foreach ($socials as [$key, $label, $icon, $c]):
                ?>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">
                        <i class="<?= $icon ?> text-<?= $c ?>-400 mr-1"></i><?= $label ?>
                    </label>
                    <input type="url" id="<?= $key ?>" value="<?= htmlspecialchars($cfg[$key] ?? '') ?>"
                           placeholder="https://..."
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Webhooks JSON -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-orange-50 text-orange-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-bell"></i></div>
                    <div>
                        <h3 class="font-bold text-gray-700 text-sm">Webhooks</h3>
                        <p class="text-[11px] text-gray-400">JSON array — returned as <code class="font-mono bg-gray-100 px-1 rounded">webhooks</code></p>
                    </div>
                </div>
                <button onclick="formatJson('ext_webhooks_json')" class="text-[11px] px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-all font-mono">Format</button>
            </div>
            <textarea id="ext_webhooks_json" rows="4"
                      placeholder='[{"name":"lead","url":"https://...","active":true}]'
                      class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs font-mono focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all resize-none"><?= htmlspecialchars($cfg['ext_webhooks_json'] ?? '[]') ?></textarea>
        </div>

        <!-- Meet JSON -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-teal-50 text-teal-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-video"></i></div>
                    <div>
                        <h3 class="font-bold text-gray-700 text-sm">Meet Config</h3>
                        <p class="text-[11px] text-gray-400">JSON object — returned as <code class="font-mono bg-gray-100 px-1 rounded">meet</code></p>
                    </div>
                </div>
                <button onclick="formatJson('ext_meet_json')" class="text-[11px] px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-all font-mono">Format</button>
            </div>
            <textarea id="ext_meet_json" rows="3"
                      placeholder='{"enabled":false,"provider":"google","link":""}'
                      class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs font-mono focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all resize-none"><?= htmlspecialchars($cfg['ext_meet_json'] ?? '{}') ?></textarea>
        </div>

        <!-- Migration JSON -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-yellow-50 text-yellow-500 rounded-xl flex items-center justify-center text-sm"><i class="fas fa-exchange-alt"></i></div>
                    <div>
                        <h3 class="font-bold text-gray-700 text-sm">Migration</h3>
                        <p class="text-[11px] text-gray-400">JSON object — returned as <code class="font-mono bg-gray-100 px-1 rounded">migration</code></p>
                    </div>
                </div>
                <button onclick="formatJson('ext_migration_json')" class="text-[11px] px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-all font-mono">Format</button>
            </div>
            <textarea id="ext_migration_json" rows="3"
                      placeholder='{"enabled":false,"version":"","notice":""}'
                      class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs font-mono focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all resize-none"><?= htmlspecialchars($cfg['ext_migration_json'] ?? '{}') ?></textarea>
        </div>

        <button onclick="saveServicesConfig()"
                class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="fas fa-save"></i> Save Extension Services Config
        </button>
    </div>

    <!-- RIGHT: How it works -->
    <div class="space-y-5">

        <!-- Endpoint info -->
        <div class="bg-gray-900 rounded-2xl p-5 text-xs font-mono">
            <p class="text-gray-400 text-[10px] uppercase tracking-widest font-bold mb-3">Endpoint</p>
            <p class="text-green-400">POST</p>
            <p class="text-yellow-300 break-all">/api/services/update</p>
            <div class="border-t border-gray-800 my-3"></div>
            <p class="text-gray-400 text-[10px] uppercase tracking-widest font-bold mb-2">Headers</p>
            <p class="text-blue-300">access-token: <span class="text-white" id="tokenPreview"><?= htmlspecialchars(substr($cfg['services_cript_key'] ?? '...', 0, 20)) ?>...</span></p>
            <p class="text-blue-300">content-type: <span class="text-white">application/json</span></p>
            <div class="border-t border-gray-800 my-3"></div>
            <p class="text-gray-400 text-[10px] uppercase tracking-widest font-bold mb-2">Called Every</p>
            <p class="text-white">5 minutes (Five_Minutes alarm)</p>
        </div>

        <!-- Response preview -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h4 class="font-bold text-gray-700 text-sm mb-3 flex items-center gap-2">
                <i class="fas fa-arrow-left text-indigo-400"></i> Response Shape
            </h4>
            <pre class="text-[10px] text-gray-500 bg-gray-50 rounded-xl p-3 overflow-x-auto leading-relaxed">{
  "success": true,
  "msg_id": "license_valid",
  "nome": "<?= htmlspecialchars($cfg['ext_nome'] ?? 'GD CRM') ?>",
  "checkout": "...",
  "painel_cliente": "...",
  "backend": "...",
  "tutorial": "...",
  "suporte_clientes": {
    "premium": "...",
    "gratuitos": "..."
  },
  "user_logado": {
    "session": {
      "is_auth": true,
      "user_status": "premium",
      "is_premium": true
    },
    "user": {
      "user_id": "42",
      "name": "John Doe",
      "bearer_token": "...",
      ...
    }
  }
}</pre>
        </div>

        <!-- msg_id reference -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h4 class="font-bold text-gray-700 text-sm mb-3">msg_id Reference</h4>
            <div class="space-y-2 text-xs">
                <?php
                $ids = [
                    ['license_valid',   'green',  'Active license found'],
                    ['license_expired', 'orange', 'License found but expired'],
                    ['no_license',      'gray',   'No license for this phone'],
                    ['no_phone',        'gray',   'Phone missing in payload'],
                    ['invalid_token',   'red',    'Wrong access-token header'],
                    ['sync_ok',         'blue',   'Phone lookup disabled'],
                ];
                foreach ($ids as [$id, $c, $desc]):
                ?>
                <div class="flex items-start gap-2">
                    <span class="px-1.5 py-0.5 bg-<?= $c ?>-50 text-<?= $c ?>-600 border border-<?= $c ?>-100 rounded font-mono text-[10px] flex-shrink-0 mt-0.5"><?= $id ?></span>
                    <span class="text-gray-500"><?= $desc ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Test result modal -->
<div id="testModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] overflow-auto">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="font-black text-gray-800">Endpoint Test Result</h3>
            <button onclick="document.getElementById('testModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg text-gray-500 transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-5">
            <pre id="testResult" class="text-xs bg-gray-900 text-green-400 p-4 rounded-xl overflow-x-auto whitespace-pre-wrap"></pre>
        </div>
    </div>
</div>

</main>
</div>

<script>
function saveServicesConfig() {
    const data = {
        action:                    'save_services_config',
        services_cript_key:        document.getElementById('services_cript_key').value.trim(),
        ext_nome:                  document.getElementById('ext_nome').value.trim(),
        ext_wl_id:                 document.getElementById('ext_wl_id').value.trim(),
        ext_checkout_url:          document.getElementById('ext_checkout_url').value.trim(),
        ext_painel_cliente:        document.getElementById('ext_painel_cliente').value.trim(),
        ext_tutorial_url:          document.getElementById('ext_tutorial_url').value.trim(),
        ext_support_premium:       document.getElementById('ext_support_premium').value.trim(),
        ext_support_free:          document.getElementById('ext_support_free').value.trim(),
        ext_phone_lookup_enabled:  document.getElementById('ext_phone_lookup_enabled').checked ? '1' : '0',
        // Social
        ext_youtube_url:           document.getElementById('ext_youtube_url').value.trim(),
        ext_instagram_url:         document.getElementById('ext_instagram_url').value.trim(),
        ext_facebook_url:          document.getElementById('ext_facebook_url').value.trim(),
        ext_telegram_url:          document.getElementById('ext_telegram_url').value.trim(),
        ext_tiktok_url:            document.getElementById('ext_tiktok_url').value.trim(),
        ext_twitter_url:           document.getElementById('ext_twitter_url').value.trim(),
        // JSON blocks
        ext_webhooks_json:         document.getElementById('ext_webhooks_json').value.trim(),
        ext_meet_json:             document.getElementById('ext_meet_json').value.trim(),
        ext_migration_json:        document.getElementById('ext_migration_json').value.trim(),
    };

    $.post('api/extension_services_api.php', data, function(res) {
        showToast(res.status === 'success' ? res.message : res.message,
                  res.status === 'success' ? 'success' : 'error');
        if (res.status === 'success') {
            document.getElementById('tokenPreview').textContent = data.services_cript_key.substring(0, 20) + '...';
        }
    }, 'json');
}

function testEndpoint() {
    const token = document.getElementById('services_cript_key').value.trim();
    document.getElementById('testResult').textContent = 'Sending test request...';
    document.getElementById('testModal').classList.remove('hidden');

    fetch('api/services/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'access-token': token,
        },
        body: JSON.stringify({
            phone: '0000000000',
            chromeStoreID: 'test_from_admin_panel',
            user_logado: {
                session: { is_load: true, is_auth: false, is_auth_google: false, user_status: 'free', is_premium: false },
                user: { user_id: '', name: '', email: '', wl_id: '', bearer_token: '', access_token_plugin: '', user_premium: null, dataCadastro: new Date().toISOString(), whatsapp_registro: '', whatsapp_plugin: '', path: '', afiliado: '', campanhaID: '', cookies: {} }
            },
            nome: '', checkout: '', painel_cliente: '', backend: '', tutorial: '', suporte_clientes: { premium: '', gratuitos: '' }, timeZone: {}
        })
    })
    .then(r => r.json())
    .then(json => {
        document.getElementById('testResult').textContent = JSON.stringify(json, null, 2);
        document.getElementById('endpointStatus').textContent = json.success ? '✓ Online' : '⚠ Responding';
        document.getElementById('endpointStatus').className = json.success ? 'text-sm font-black text-green-600' : 'text-sm font-black text-orange-500';
    })
    .catch(e => {
        document.getElementById('testResult').textContent = 'Error: ' + e.message;
        document.getElementById('endpointStatus').textContent = '✗ Offline';
        document.getElementById('endpointStatus').className = 'text-sm font-black text-red-500';
    });
}

function formatJson(id) {
    const el = document.getElementById(id);
    try {
        el.value = JSON.stringify(JSON.parse(el.value), null, 2);
        el.classList.remove('border-red-300');
    } catch(e) {
        el.classList.add('border-red-300');
        showToast('Invalid JSON in ' + id, 'error');
    }
}

    const val = document.getElementById(id).value;
    navigator.clipboard.writeText(val).then(() => showToast('Copied!', 'success'));
}
</script>

<?php include 'sections/common_modal.php'; include 'sections/footer.php'; ?>
