<?php
include 'include/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login"); exit(); }

$role    = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$title   = "API Access";

// Base URL for docs
$api_base = rtrim($base_url, '/') . '/api/public_api.php';

// Load this user's API keys
$keys = $conn->query("SELECT * FROM api_keys WHERE user_id='$user_id' ORDER BY id DESC");

// Stats
$total_keys   = $conn->query("SELECT COUNT(*) FROM api_keys WHERE user_id='$user_id'")->fetch_row()[0];
$active_keys  = $conn->query("SELECT COUNT(*) FROM api_keys WHERE user_id='$user_id' AND status='active'")->fetch_row()[0];
$total_calls  = $conn->query("SELECT COALESCE(SUM(total_calls),0) FROM api_keys WHERE user_id='$user_id'")->fetch_row()[0];
$today_calls  = $conn->query("SELECT COUNT(*) FROM api_logs WHERE user_id='$user_id' AND DATE(created_at)=CURDATE()")->fetch_row()[0];
$error_calls  = $conn->query("SELECT COUNT(*) FROM api_logs WHERE user_id='$user_id' AND response_code!=200 AND DATE(created_at)=CURDATE()")->fetch_row()[0];
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
        @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&display=swap');
        body { background:#f8fafc; font-family:'Inter',sans-serif; overflow:hidden; }
        .nav-item { white-space:nowrap; overflow:hidden; transition:all .2s; display:flex; align-items:center; }
        .active-link-white { background:#f0fdf4!important; border:1px solid #dcfce7; color:#166534!important; }
        .active-link-white i { color:#22c55e!important; }
        .code-block { font-family:'Fira Code',monospace; font-size:12px; }
        .tab-btn { transition:all .2s; }
        .tab-btn.active { background:white; box-shadow:0 1px 3px rgba(0,0,0,.1); color:#1f2937; font-weight:700; }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
        .method-badge { font-family:'Fira Code',monospace; font-size:10px; font-weight:700; padding:2px 8px; border-radius:4px; }
        .endpoint-card:hover { border-color:#6366f1; }
        .copy-btn:hover { opacity:1; }
        pre { overflow-x:auto; }
    </style>
</head>
<body class="flex h-screen">
<?php include 'sections/sidebar.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'sections/navbar.php'; ?>
<main class="flex-1 overflow-y-auto p-8 antialiased">

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-xl font-bold text-gray-800">API Access</h1>
        <p class="text-sm text-gray-500 mt-1">Manage API keys and integrate license management into your apps.</p>
    </div>
    <button onclick="openModal('Create API Key', 'create_api_key_modal')"
            class="px-5 py-2.5 bg-<?= get_config('theme_color') ?>-300 hover:bg-<?= get_config('theme_color') ?>-400 text-<?= get_config('theme_color') ?>-900 font-bold text-sm rounded-xl flex items-center gap-2 shadow-sm transition-all">
        <i class="fas fa-plus"></i> New API Key
    </button>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <?php
    $stats = [
        ['icon'=>'fas fa-key',          'color'=>'indigo', 'label'=>'Total Keys',    'val'=>$total_keys],
        ['icon'=>'fas fa-check-circle',  'color'=>'green',  'label'=>'Active Keys',   'val'=>$active_keys],
        ['icon'=>'fas fa-bolt',          'color'=>'blue',   'label'=>'Total Calls',   'val'=>number_format($total_calls)],
        ['icon'=>'fas fa-calendar-day',  'color'=>'purple', 'label'=>"Today's Calls", 'val'=>$today_calls],
        ['icon'=>'fas fa-exclamation-triangle','color'=>'red','label'=>'Today Errors','val'=>$error_calls],
    ];
    foreach ($stats as $s): ?>
    <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-3">
        <div class="w-9 h-9 bg-<?= $s['color'] ?>-50 text-<?= $s['color'] ?>-500 rounded-xl flex items-center justify-center text-sm flex-shrink-0">
            <i class="<?= $s['icon'] ?>"></i>
        </div>
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider"><?= $s['label'] ?></p>
            <h3 class="text-lg font-black text-gray-800"><?= $s['val'] ?></h3>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabs -->
<div class="bg-gray-100 rounded-xl p-1 flex gap-1 mb-6 w-fit">
    <button class="tab-btn active px-5 py-2 rounded-lg text-sm text-gray-500" onclick="switchTab('keys')"><i class="fas fa-key mr-1.5"></i> My API Keys</button>
    <button class="tab-btn px-5 py-2 rounded-lg text-sm text-gray-500" onclick="switchTab('docs')"><i class="fas fa-book mr-1.5"></i> Documentation</button>
    <button class="tab-btn px-5 py-2 rounded-lg text-sm text-gray-500" onclick="switchTab('logs')"><i class="fas fa-list mr-1.5"></i> API Logs</button>
</div>

<!-- ═══ TAB: Keys ═══════════════════════════════════════════ -->
<div id="tab-keys" class="tab-content active">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/80 border-b border-gray-100">
                    <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Key Name</th>
                    <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">API Key</th>
                    <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Permissions</th>
                    <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-center">Rate Limit</th>
                    <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-center">Calls</th>
                    <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-center">Status</th>
                    <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 text-sm">
            <?php if ($keys && $keys->num_rows > 0): while ($k = $keys->fetch_assoc()): ?>
            <tr class="hover:bg-gray-50/50 transition-all">
                <td class="px-6 py-4">
                    <div class="font-bold text-gray-800"><?= htmlspecialchars($k['key_name']) ?></div>
                    <div class="text-[10px] text-gray-400 mt-0.5"><?= $k['last_used_at'] ? 'Last used '.date('d M Y', strtotime($k['last_used_at'])) : 'Never used' ?></div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded-lg font-mono text-gray-700 max-w-[180px] truncate block">
                            <?= htmlspecialchars($k['api_key']) ?>
                        </code>
                        <button onclick="copyText('<?= $k['api_key'] ?>')" class="text-gray-400 hover:text-indigo-600 transition-all" title="Copy">
                            <i class="fas fa-copy text-xs"></i>
                        </button>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-1">
                        <?php foreach (explode(',', $k['permissions']) as $perm): ?>
                        <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-600 text-[9px] font-bold rounded uppercase"><?= str_replace('_license','', $perm) ?></span>
                        <?php endforeach; ?>
                    </div>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="text-xs font-bold text-gray-600"><?= $k['rate_limit'] ?>/min</span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="text-xs font-bold text-gray-600"><?= number_format($k['total_calls']) ?></span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-black rounded-full uppercase
                        <?= $k['status']==='active' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600' ?>">
                        <span class="w-1.5 h-1.5 rounded-full <?= $k['status']==='active' ? 'bg-green-500 animate-pulse' : 'bg-red-400' ?>"></span>
                        <?= $k['status'] ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-1">
                        <button onclick="openModal('View API Key', 'view_api_key_modal', {id: <?= $k['id'] ?>})"
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 transition-all" title="View Secret">
                            <i class="fas fa-eye text-xs"></i>
                        </button>
                        <button onclick="openModal('Edit API Key', 'edit_api_key_modal', {id: <?= $k['id'] ?>})"
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition-all" title="Edit">
                            <i class="fas fa-pencil-alt text-xs"></i>
                        </button>
                        <button onclick="toggleApiKey(<?= $k['id'] ?>, '<?= $k['status'] ?>')"
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-yellow-50 hover:text-yellow-600 transition-all"
                                title="<?= $k['status']==='active' ? 'Revoke' : 'Activate' ?>">
                            <i class="fas fa-<?= $k['status']==='active' ? 'ban' : 'check' ?> text-xs"></i>
                        </button>
                        <button onclick="deleteApiKey(<?= $k['id'] ?>)"
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-600 transition-all" title="Delete">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="7" class="px-6 py-16 text-center">
                <i class="fas fa-key text-gray-200 text-4xl mb-3 block"></i>
                <p class="text-gray-400 text-sm">No API keys yet. Create one to get started.</p>
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══ TAB: Documentation ══════════════════════════════════ -->
<div id="tab-docs" class="tab-content">
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <!-- Sidebar nav -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sticky top-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Contents</p>
            <nav class="space-y-0.5 text-xs">
                <?php
                $nav = [
                    ['#auth',       'fas fa-lock',        'Authentication'],
                    ['#errors',     'fas fa-times-circle','Error Codes'],
                    ['#rate',       'fas fa-tachometer-alt','Rate Limiting'],
                    ['#validate',   'fas fa-check-double','Validate License'],
                    ['#create',     'fas fa-plus-circle', 'Create License'],
                    ['#list',       'fas fa-list',        'List Licenses'],
                    ['#get',        'fas fa-search',      'Get License'],
                    ['#update',     'fas fa-edit',        'Update License'],
                    ['#toggle',     'fas fa-toggle-on',   'Toggle Status'],
                    ['#delete',     'fas fa-trash',       'Delete License'],
                    
                ];
 if (in_array($role, ['super_admin', 'admin'])) {
                    $nav[] = ['#cron', 'fas fa-clock', 'Cron Setup'];
                }
                foreach ($nav as [$href, $ico, $label]):
                ?>
                <a href="<?= $href ?>" class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 transition-all">
                    <i class="<?= $ico ?> w-4 text-center text-[11px] text-gray-400"></i> <?= $label ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <!-- Docs content -->
    <div class="lg:col-span-3 space-y-6">

        <!-- Base URL -->
        <div class="bg-indigo-600 text-white rounded-2xl p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-indigo-200 mb-1">Base URL</p>
            <div class="flex items-center gap-3">
                <code class="text-sm font-mono flex-1"><?= $api_base ?></code>
                <button onclick="copyText('<?= $api_base ?>')" class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white text-xs font-bold rounded-lg transition-all">
                    <i class="fas fa-copy mr-1"></i>Copy
                </button>
            </div>
        </div>

        <!-- Authentication -->
        <div id="auth" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h2 class="font-black text-gray-800 mb-1 flex items-center gap-2"><i class="fas fa-lock text-indigo-500"></i> Authentication</h2>
            <p class="text-sm text-gray-500 mb-4">Every request must include your API Key and Secret in the HTTP headers.</p>

            <div class="bg-gray-900 rounded-xl p-4 code-block text-green-400 mb-4">
                <p class="text-gray-500 text-[10px] mb-2 font-bold uppercase">Required Headers</p>
                <span class="text-blue-300">X-API-Key</span>: your_api_key_here<br>
                <span class="text-blue-300">X-API-Secret</span>: your_api_secret_here<br>
                <span class="text-blue-300">Content-Type</span>: application/json
            </div>

            <p class="text-xs text-gray-500 mb-3">Or using <strong>curl</strong>:</p>
            <div class="bg-gray-900 rounded-xl p-4 relative">
                <button onclick="copyText(this.nextElementSibling.textContent)" class="absolute top-3 right-3 text-gray-500 hover:text-white transition-all text-xs"><i class="fas fa-copy"></i></button>
                <pre class="code-block text-green-400">curl -X POST <?= $api_base ?> \
  -H "X-API-Key: your_api_key" \
  -H "X-API-Secret: your_api_secret" \
  -H "Content-Type: application/json" \
  -d '{"action":"validate_license","license_key":"XXXX-XXXX-XXXX-XXXX"}'</pre>
            </div>

            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-100 rounded-xl text-xs text-yellow-700">
                <i class="fas fa-shield-alt mr-1"></i> <strong>Never expose your API Secret</strong> in client-side code (JavaScript/HTML). Use it only from your server.
            </div>
        </div>

        <!-- Error Codes -->
        <div id="errors" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h2 class="font-black text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-times-circle text-red-500"></i> Error Codes</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead><tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-2 text-left font-bold text-gray-500">HTTP Code</th>
                        <th class="px-4 py-2 text-left font-bold text-gray-500">status field</th>
                        <th class="px-4 py-2 text-left font-bold text-gray-500">Meaning</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                    <?php
                    $errs = [
                        ['200','success','Request completed successfully'],
                        ['400','error','Bad request — missing or invalid parameter'],
                        ['401','error','Invalid or missing API Key / Secret'],
                        ['403','error','API key revoked, or action not permitted by key permissions'],
                        ['404','error','License not found, or does not belong to your account'],
                        ['422','error','Quota exceeded — upgrade your plan to create more licenses'],
                        ['429','error','Rate limit exceeded — slow down requests'],
                        ['500','error','Server error — contact support'],
                    ];
                    foreach ($errs as [$code, $status, $meaning]):
                    $cls = $code==='200' ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2"><span class="px-2 py-0.5 <?= $cls ?> font-black rounded font-mono"><?= $code ?></span></td>
                        <td class="px-4 py-2 font-mono text-gray-600">"<?= $status ?>"</td>
                        <td class="px-4 py-2 text-gray-600"><?= $meaning ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rate Limiting -->
        <div id="rate" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h2 class="font-black text-gray-800 mb-1 flex items-center gap-2"><i class="fas fa-tachometer-alt text-blue-500"></i> Rate Limiting</h2>
            <p class="text-sm text-gray-500 mb-4">Each API key has a per-minute rate limit (set when creating the key). Response headers tell you your current usage:</p>
            <div class="bg-gray-900 rounded-xl p-4 code-block text-green-400">
                <span class="text-blue-300">X-RateLimit-Limit</span>: 60<br>
                <span class="text-blue-300">X-RateLimit-Remaining</span>: 45<br>
                <span class="text-blue-300">X-RateLimit-Reset</span>: 1710000060
            </div>
            <p class="text-xs text-gray-400 mt-3">When limit is exceeded, HTTP 429 is returned. Wait until the next minute window to retry.</p>
        </div>

        <?php
        // Endpoint definitions
        $endpoints = [
            [
                'id'     => 'validate',
                'method' => 'POST',
                'action' => 'validate_license',
                'color'  => 'green',
                'title'  => 'Validate License',
                'desc'   => 'Check if a license key is valid and active. Useful for verifying licenses from your app before granting access.',
                'perm'   => 'validate_license',
                'params' => [
                    ['license_key','string','required','The license key to validate (format: XXXX-XXXX-XXXX-XXXX)'],
                ],
                'success'=> '{"status":"success","valid":true,"license":{"id":1,"key":"ABCD-1234-EFGH-5678","client_name":"John Doe","client_mobile":"9876543210","software_name":"MyApp","status":"active","expiry_date":"2025-12-31","created_at":"2024-01-01 10:00:00"}}',
                'error'  => '{"status":"error","valid":false,"message":"License not found or inactive."}',
            ],
            [
                'id'     => 'create',
                'method' => 'POST',
                'action' => 'create_license',
                'color'  => 'indigo',
                'title'  => 'Create License',
                'desc'   => 'Generate a new license key. Respects your reseller plan quota.',
                'perm'   => 'create_license',
                'params' => [
                    ['software_name','string','required','Name of the software this license is for'],
                    ['client_name',  'string','required','Client/customer name'],
                    ['client_mobile','string','optional','Client phone number'],
                    ['expiry_date',  'date',  'optional','Expiry date (YYYY-MM-DD). Leave empty for no expiry.'],
                ],
                'success'=> '{"status":"success","message":"License created successfully.","license_key":"ABCD-1234-EFGH-5678","id":42}',
                'error'  => '{"status":"error","message":"Quota exceeded. Upgrade your plan to create more licenses."}',
            ],
            [
                'id'     => 'list',
                'method' => 'POST',
                'action' => 'list_licenses',
                'color'  => 'blue',
                'title'  => 'List Licenses',
                'desc'   => 'Retrieve all licenses belonging to your account, with pagination and optional filters.',
                'perm'   => 'read_license',
                'params' => [
                    ['page',    'int',   'optional','Page number (default: 1)'],
                    ['per_page','int',   'optional','Results per page (default: 20, max: 100)'],
                    ['status',  'string','optional','Filter by status: active | blocked'],
                    ['search',  'string','optional','Search by client name, mobile, or license key'],
                ],
                'success'=> '{"status":"success","total":150,"page":1,"per_page":20,"licenses":[{"id":1,"key":"ABCD-1234-EFGH-5678","client_name":"John","status":"active","expiry_date":"2025-12-31"}]}',
                'error'  => '{"status":"error","message":"Invalid page number."}',
            ],
            [
                'id'     => 'get',
                'method' => 'POST',
                'action' => 'get_license',
                'color'  => 'blue',
                'title'  => 'Get License',
                'desc'   => 'Retrieve full details of a single license by ID or license key.',
                'perm'   => 'read_license',
                'params' => [
                    ['id',          'int',   'optional','License ID (use id or license_key)'],
                    ['license_key', 'string','optional','License key string (use id or license_key)'],
                ],
                'success'=> '{"status":"success","license":{"id":1,"key":"ABCD-1234-EFGH-5678","client_name":"John Doe","client_mobile":"9876543210","software_name":"MyApp","status":"active","expiry_date":"2025-12-31","created_at":"2024-01-01"}}',
                'error'  => '{"status":"error","message":"License not found."}',
            ],
            [
                'id'     => 'update',
                'method' => 'POST',
                'action' => 'update_license',
                'color'  => 'yellow',
                'title'  => 'Update License',
                'desc'   => 'Update details of an existing license. Only licenses you own can be updated.',
                'perm'   => 'update_license',
                'params' => [
                    ['id',           'int',   'required','License ID to update'],
                    ['software_name','string','optional','New software name'],
                    ['client_name',  'string','optional','New client name'],
                    ['client_mobile','string','optional','New client mobile'],
                    ['expiry_date',  'date',  'optional','New expiry date (YYYY-MM-DD)'],
                    ['status',       'string','optional','New status: active | blocked'],
                ],
                'success'=> '{"status":"success","message":"License updated successfully."}',
                'error'  => '{"status":"error","message":"License not found or unauthorized."}',
            ],
            [
                'id'     => 'toggle',
                'method' => 'POST',
                'action' => 'toggle_license',
                'color'  => 'yellow',
                'title'  => 'Toggle License Status',
                'desc'   => 'Toggle a license between active and blocked states.',
                'perm'   => 'update_license',
                'params' => [
                    ['id','int','required','License ID to toggle'],
                ],
                'success'=> '{"status":"success","message":"License status changed to blocked.","new_status":"blocked"}',
                'error'  => '{"status":"error","message":"License not found."}',
            ],
            [
                'id'     => 'delete',
                'method' => 'POST',
                'action' => 'delete_license',
                'color'  => 'red',
                'title'  => 'Delete License',
                'desc'   => 'Permanently delete a license. This action cannot be undone.',
                'perm'   => 'delete_license',
                'params' => [
                    ['id','int','required','License ID to delete'],
                ],
                'success'=> '{"status":"success","message":"License deleted successfully."}',
                'error'  => '{"status":"error","message":"License not found or unauthorized."}',
            ],
        ];

        $method_colors = ['POST'=>'bg-blue-100 text-blue-700','GET'=>'bg-green-100 text-green-700'];

        foreach ($endpoints as $ep):
        $mc = ['green'=>'green','indigo'=>'indigo','blue'=>'blue','yellow'=>'yellow','red'=>'red'][$ep['color']];
        ?>
        <div id="<?= $ep['id'] ?>" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 endpoint-card transition-all">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="method-badge bg-blue-100 text-blue-700">POST</span>
                        <code class="text-xs text-gray-600 font-mono">action: "<?= $ep['action'] ?>"</code>
                    </div>
                    <h2 class="font-black text-gray-800 text-base"><?= $ep['title'] ?></h2>
                    <p class="text-sm text-gray-500 mt-0.5"><?= $ep['desc'] ?></p>
                </div>
                <span class="px-2 py-1 bg-<?= $mc ?>-50 text-<?= $mc ?>-600 text-[10px] font-black rounded-lg uppercase flex-shrink-0">
                    <?= str_replace('_license','', $ep['perm']) ?>
                </span>
            </div>

            <!-- Params -->
            <div class="mb-4">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Parameters</p>
                <table class="w-full text-xs border border-gray-100 rounded-xl overflow-hidden">
                    <thead><tr class="bg-gray-50">
                        <th class="px-3 py-2 text-left font-bold text-gray-500">Field</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-500">Type</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-500">Required</th>
                        <th class="px-3 py-2 text-left font-bold text-gray-500">Description</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr class="bg-gray-50/50">
                            <td class="px-3 py-2 font-mono text-indigo-600">action</td>
                            <td class="px-3 py-2 text-gray-500">string</td>
                            <td class="px-3 py-2"><span class="text-red-500 font-bold">required</span></td>
                            <td class="px-3 py-2 text-gray-600">"<?= $ep['action'] ?>"</td>
                        </tr>
                    <?php foreach ($ep['params'] as [$field, $type, $req, $desc]): ?>
                        <tr>
                            <td class="px-3 py-2 font-mono text-indigo-600"><?= $field ?></td>
                            <td class="px-3 py-2 text-gray-500"><?= $type ?></td>
                            <td class="px-3 py-2">
                                <?php if ($req==='required'): ?>
                                <span class="text-red-500 font-bold">required</span>
                                <?php else: ?>
                                <span class="text-gray-400">optional</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 text-gray-600"><?= $desc ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Response examples -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <p class="text-[10px] font-black text-green-500 uppercase tracking-wider mb-1">✓ Success Response</p>
                    <div class="bg-gray-900 rounded-xl p-3 relative">
                        <button onclick="copyText(this.nextElementSibling.textContent.trim())" class="absolute top-2 right-2 text-gray-500 hover:text-white transition-all text-[10px]"><i class="fas fa-copy"></i></button>
                        <pre class="code-block text-green-400 text-[10px] whitespace-pre-wrap"><?= htmlspecialchars(json_encode(json_decode($ep['success']), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-black text-red-400 uppercase tracking-wider mb-1">✗ Error Response</p>
                    <div class="bg-gray-900 rounded-xl p-3 relative">
                        <button onclick="copyText(this.nextElementSibling.textContent.trim())" class="absolute top-2 right-2 text-gray-500 hover:text-white transition-all text-[10px]"><i class="fas fa-copy"></i></button>
                        <pre class="code-block text-red-400 text-[10px] whitespace-pre-wrap"><?= htmlspecialchars(json_encode(json_decode($ep['error']), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Cron Setup -->
          <?php if (in_array($role, ['super_admin', 'admin'])): ?>
        <div id="cron" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h2 class="font-black text-gray-800 mb-1 flex items-center gap-2"><i class="fas fa-clock text-purple-500"></i> Cron Job Setup</h2>
            <p class="text-sm text-gray-500 mb-4">Set up a cron job to automatically clean old API logs. Error logs are deleted after 30 days, success logs after 60 days.</p>
            <div class="bg-gray-900 rounded-xl p-4 relative mb-3">
                <button onclick="copyText(document.getElementById('cronCmd').textContent)" class="absolute top-3 right-3 text-gray-500 hover:text-white text-xs"><i class="fas fa-copy"></i></button>
                <pre id="cronCmd" class="code-block text-green-400"># Run daily at 2:00 AM
0 2 * * * php <?= rtrim(str_replace('/api', '', dirname($_SERVER['SCRIPT_FILENAME'])), '/') ?>/cron/api_logs_cleanup.php >> /var/log/api_cleanup.log 2>&1</pre>
            </div>
            <p class="text-[11px] text-gray-400">Add this to your crontab via <code class="bg-gray-100 px-1 rounded">crontab -e</code>. Or add it in your hosting control panel's Cron Jobs section.</p>
        </div>
        <?php endif; ?>

    </div><!-- end docs content -->
</div><!-- end grid -->
</div><!-- end tab-docs -->

<!-- ═══ TAB: Logs ════════════════════════════════════════════ -->
<div id="tab-logs" class="tab-content">
    <div class="flex items-center justify-between mb-4">
        <div class="flex gap-2">
            <select id="logFilter" onchange="filterLogs()" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-white">
                <option value="">All Status</option>
                <option value="200">200 Success</option>
                <option value="400">400 Bad Request</option>
                <option value="401">401 Unauthorized</option>
                <option value="403">403 Forbidden</option>
                <option value="404">404 Not Found</option>
                <option value="422">422 Quota Error</option>
                <option value="429">429 Rate Limited</option>
                <option value="500">500 Server Error</option>
            </select>
            <select id="logAction" onchange="filterLogs()" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-white">
                <option value="">All Actions</option>
                <option value="validate_license">validate_license</option>
                <option value="create_license">create_license</option>
                <option value="list_licenses">list_licenses</option>
                <option value="get_license">get_license</option>
                <option value="update_license">update_license</option>
                <option value="toggle_license">toggle_license</option>
                <option value="delete_license">delete_license</option>
            </select>
        </div>
        <p class="text-xs text-gray-400"><i class="fas fa-info-circle mr-1"></i> Logs auto-deleted: errors after 30 days, success after 60 days</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden" id="logsTable">
        <?php
        $logs = $conn->query("
            SELECT al.*, ak.key_name, ak.api_key
            FROM api_logs al
            LEFT JOIN api_keys ak ON al.api_key_id = ak.id
            WHERE al.user_id='$user_id'
            ORDER BY al.id DESC LIMIT 100
        ");
        ?>
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/80 border-b border-gray-100">
                    <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Time</th>
                    <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Action</th>
                    <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Key</th>
                    <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest">IP</th>
                    <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-center">Status</th>
                    <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-center">Time(ms)</th>
                    <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Response</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 text-xs" id="logsBody">
            <?php if ($logs && $logs->num_rows > 0): while ($log = $logs->fetch_assoc()):
                $sc = $log['response_code'];
                $sc_cls = $sc==200 ? 'bg-green-50 text-green-700' : ($sc>=500 ? 'bg-red-50 text-red-600' : ($sc>=400 ? 'bg-yellow-50 text-yellow-700' : 'bg-gray-50 text-gray-600'));
            ?>
            <tr class="hover:bg-gray-50/50 log-row" data-code="<?= $sc ?>" data-action="<?= htmlspecialchars($log['endpoint']) ?>">
                <td class="px-5 py-3 text-gray-500 whitespace-nowrap"><?= date('d M, H:i:s', strtotime($log['created_at'])) ?></td>
                <td class="px-5 py-3 font-mono text-indigo-600"><?= htmlspecialchars($log['endpoint']) ?></td>
                <td class="px-5 py-3 text-gray-600"><?= htmlspecialchars($log['key_name'] ?? '-') ?></td>
                <td class="px-5 py-3 text-gray-500 font-mono"><?= htmlspecialchars($log['ip_address']) ?></td>
                <td class="px-5 py-3 text-center">
                    <span class="px-2 py-0.5 <?= $sc_cls ?> font-black rounded font-mono text-[10px]"><?= $sc ?></span>
                </td>
                <td class="px-5 py-3 text-center text-gray-500"><?= $log['duration_ms'] ?>ms</td>
                <td class="px-5 py-3 max-w-xs">
                    <span class="text-gray-500 truncate block max-w-[200px]" title="<?= htmlspecialchars($log['response_msg']) ?>">
                        <?= htmlspecialchars(substr($log['response_msg'] ?? '', 0, 60)) ?>
                    </span>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="7" class="px-6 py-16 text-center text-gray-400 text-sm">No API logs yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}

function copyText(text) {
    navigator.clipboard.writeText(text.trim()).then(() => showToast('Copied!', 'success'));
}

function toggleApiKey(id, status) {
    const action = status === 'active' ? 'Revoke' : 'Activate';
    if (!confirm(action + ' this API key?')) return;
    $.post('api/api_key_api.php', { action: 'toggle_key', id }, function(res) {
        if (res.status === 'success') { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1200); }
        else showToast(res.message, 'error');
    }, 'json');
}

function deleteApiKey(id) {
    if (!confirm('Delete this API key permanently? All associated logs will remain but the key will stop working immediately.')) return;
    $.post('api/api_key_api.php', { action: 'delete_key', id }, function(res) {
        if (res.status === 'success') { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1200); }
        else showToast(res.message, 'error');
    }, 'json');
}

function filterLogs() {
    const code   = document.getElementById('logFilter').value;
    const action = document.getElementById('logAction').value;
    document.querySelectorAll('.log-row').forEach(row => {
        const matchCode   = !code   || row.dataset.code === code;
        const matchAction = !action || row.dataset.action === action;
        row.style.display = (matchCode && matchAction) ? '' : 'none';
    });
}
</script>

<?php include 'sections/common_modal.php'; include 'sections/footer.php'; ?>
