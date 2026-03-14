<?php
include 'include/config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','admin'])) {
    header("Location: dashboard"); exit();
}

$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$title    = "Extension Tracker";

// Stats
$total_installs   = $conn->query("SELECT COUNT(*) FROM extension_installs WHERE event='install'")->fetch_row()[0];
$total_uninstalls = $conn->query("SELECT COUNT(*) FROM extension_installs WHERE event='uninstall'")->fetch_row()[0];
$total_updates    = $conn->query("SELECT COUNT(*) FROM extension_installs WHERE event='update'")->fetch_row()[0];
$today_installs   = $conn->query("SELECT COUNT(*) FROM extension_installs WHERE event='install' AND DATE(created_at)=CURDATE()")->fetch_row()[0];

// Unique extension IDs currently installed (installed but not uninstalled)
$unique_active = $conn->query("
    SELECT COUNT(DISTINCT ext_id) FROM extension_installs ei
    WHERE event='install'
    AND NOT EXISTS (
        SELECT 1 FROM extension_installs ei2
        WHERE ei2.ext_id=ei.ext_id AND ei2.event='uninstall' AND ei2.created_at > ei.created_at
    )
")->fetch_row()[0];

// Last 30 days chart data
$chart_data = [];
$chart_labels = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime("-$i days"));
    $installs   = $conn->query("SELECT COUNT(*) FROM extension_installs WHERE event='install' AND DATE(created_at)='$date'")->fetch_row()[0];
    $uninstalls = $conn->query("SELECT COUNT(*) FROM extension_installs WHERE event='uninstall' AND DATE(created_at)='$date'")->fetch_row()[0];
    $chart_labels[] = $label;
    $chart_data['installs'][]   = (int)$installs;
    $chart_data['uninstalls'][] = (int)$uninstalls;
}

// Recent logs
$logs = $conn->query("SELECT * FROM extension_installs ORDER BY id DESC LIMIT 100");

// Config values
$install_url   = get_config('ext_install_redirect_url') ?: '';
$update_url    = get_config('ext_update_notes_url')     ?: '';
$uninstall_url = get_config('ext_uninstall_redirect_url') ?: '';
$dom_version   = get_config('dom_selector_version')     ?: '1.0.0';
$dom_json      = get_config('dom_selector_json')        ?: '{"version":"1.0.0","selectors":{}}';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | <?= get_config('site_name') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/png" href="<?= get_config('circle_logo_path') ?>">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { background:#f8fafc; font-family:'Inter',sans-serif; overflow:hidden; }
        .nav-item { white-space:nowrap; overflow:hidden; transition:all .2s; display:flex; align-items:center; }
        .active-link-white { background:#f0fdf4!important; border:1px solid #dcfce7; color:#166534!important; }
        .active-link-white i { color:#22c55e!important; }
        .tab-btn { transition:all .2s; }
        .tab-btn.active { background:white; box-shadow:0 1px 3px rgba(0,0,0,.1); color:#1f2937; font-weight:700; }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
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
        <h1 class="text-xl font-bold text-gray-800">Extension Tracker</h1>
        <p class="text-sm text-gray-500 mt-1">Monitor installs, uninstalls, updates and configure extension backend URLs.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= rtrim($base_url,'/') ?>/extend/domSelector.json" target="_blank"
           class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm rounded-xl flex items-center gap-2 transition-all">
            <i class="fas fa-external-link-alt text-xs"></i> View domSelector.json
        </a>
        <a href="extension_notes" target="_blank"
           class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-sm rounded-xl flex items-center gap-2 transition-all">
            <i class="fas fa-eye text-xs"></i> Preview Notes Page
        </a>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <?php
    $stats = [
        ['fas fa-download',      'green',  'Total Installs',    $total_installs],
        ['fas fa-times-circle',  'red',    'Total Uninstalls',  $total_uninstalls],
        ['fas fa-sync',          'blue',   'Total Updates',     $total_updates],
        ['fas fa-calendar-day',  'indigo', "Today's Installs",  $today_installs],
        ['fas fa-users',         'purple', 'Active Users (est)',$unique_active],
    ];
    foreach ($stats as [$icon, $color, $label, $val]):
    ?>
    <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-3">
        <div class="w-9 h-9 bg-<?= $color ?>-50 text-<?= $color ?>-500 rounded-xl flex items-center justify-center text-sm flex-shrink-0">
            <i class="<?= $icon ?>"></i>
        </div>
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider"><?= $label ?></p>
            <h3 class="text-lg font-black text-gray-800"><?= number_format($val) ?></h3>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabs -->
<div class="bg-gray-100 rounded-xl p-1 flex gap-1 mb-6 w-fit">
    <button class="tab-btn active px-4 py-2 rounded-lg text-sm text-gray-500" onclick="switchTab('overview')"><i class="fas fa-chart-bar mr-1.5"></i> Overview</button>
    <button class="tab-btn px-4 py-2 rounded-lg text-sm text-gray-500" onclick="switchTab('logs')"><i class="fas fa-list mr-1.5"></i> Logs</button>
    <button class="tab-btn px-4 py-2 rounded-lg text-sm text-gray-500" onclick="switchTab('config')"><i class="fas fa-cog mr-1.5"></i> URL Config</button>
    <button class="tab-btn px-4 py-2 rounded-lg text-sm text-gray-500" onclick="switchTab('domselector')"><i class="fas fa-code mr-1.5"></i> domSelector</button>
</div>

<!-- ── TAB: Overview ─────────────────────────────────────── -->
<div id="tab-overview" class="tab-content active">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-gray-700 text-sm mb-5">Installs vs Uninstalls — Last 30 Days</h3>
        <canvas id="installChart" height="100"></canvas>
    </div>
</div>

<!-- ── TAB: Logs ─────────────────────────────────────────── -->
<div id="tab-logs" class="tab-content">
    <div class="flex gap-2 mb-4">
        <select id="eventFilter" onchange="filterLogs()" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-white">
            <option value="">All Events</option>
            <option value="install">Install</option>
            <option value="uninstall">Uninstall</option>
            <option value="update">Update</option>
        </select>
        <input type="text" id="extIdFilter" oninput="filterLogs()" placeholder="Search Extension ID..."
               class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-white w-64">
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead><tr class="bg-gray-50/80 border-b border-gray-100">
                <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Time</th>
                <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Event</th>
                <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Extension ID</th>
                <th class="px-5 py-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest">IP Address</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50 text-xs" id="logsBody">
            <?php if ($logs && $logs->num_rows > 0): while ($log = $logs->fetch_assoc()):
                $ec = ['install'=>'bg-green-50 text-green-700', 'uninstall'=>'bg-red-50 text-red-600', 'update'=>'bg-blue-50 text-blue-600'][$log['event']] ?? 'bg-gray-50 text-gray-600';
            ?>
            <tr class="hover:bg-gray-50/50 log-row" data-event="<?= $log['event'] ?>" data-extid="<?= htmlspecialchars($log['ext_id']) ?>">
                <td class="px-5 py-3 text-gray-500 whitespace-nowrap"><?= date('d M Y, H:i:s', strtotime($log['created_at'])) ?></td>
                <td class="px-5 py-3">
                    <span class="px-2 py-0.5 <?= $ec ?> font-black text-[10px] rounded-full uppercase"><?= $log['event'] ?></span>
                </td>
                <td class="px-5 py-3 font-mono text-gray-600 text-[11px]"><?= htmlspecialchars($log['ext_id']) ?></td>
                <td class="px-5 py-3 font-mono text-gray-500"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400">No logs yet. Logs appear when users install or update the extension.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── TAB: URL Config ───────────────────────────────────── -->
<div id="tab-config" class="tab-content">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-gray-700 text-sm mb-1">Extension URL Configuration</h3>
        <p class="text-xs text-gray-400 mb-6">These URLs are called by the Chrome extension's background.js. Changes take effect immediately.</p>

        <div class="space-y-5">

            <div class="p-4 border border-gray-100 rounded-xl bg-gray-50/50">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-8 h-8 bg-green-50 text-green-600 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-download text-xs"></i></div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Install Redirect URL</label>
                        <p class="text-[11px] text-gray-400 mt-0.5">Tab opened in Chrome <strong>after user installs</strong> your extension. Usually your login or welcome page.</p>
                        <code class="text-[10px] text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded mt-1 block w-fit font-mono">GET /api/urls/install/{ext_id} → opens this URL</code>
                    </div>
                </div>
                <input type="url" id="cfg_install_url" value="<?= htmlspecialchars($install_url) ?>"
                       placeholder="https://crm.waclick.in/user_login"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-white transition-all">
            </div>

            <div class="p-4 border border-gray-100 rounded-xl bg-gray-50/50">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-sync text-xs"></i></div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Update Notes URL</label>
                        <p class="text-[11px] text-gray-400 mt-0.5">Tab opened in Chrome <strong>after extension updates</strong>. Shows what's new. Use your extension_notes page or any URL.</p>
                        <code class="text-[10px] text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded mt-1 block w-fit font-mono">GET /api/urls/notes/{ext_id} → redirects to this URL</code>
                    </div>
                </div>
                <input type="url" id="cfg_update_url" value="<?= htmlspecialchars($update_url) ?>"
                       placeholder="https://crm.waclick.in/extension_notes"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-white transition-all">
            </div>

            <div class="p-4 border border-gray-100 rounded-xl bg-gray-50/50">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-8 h-8 bg-red-50 text-red-500 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-times text-xs"></i></div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Uninstall Redirect URL <span class="text-gray-400 font-normal">(optional)</span></label>
                        <p class="text-[11px] text-gray-400 mt-0.5">Page shown <strong>after user uninstalls</strong>. Good for feedback forms. Leave empty to skip.</p>
                        <code class="text-[10px] text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded mt-1 block w-fit font-mono">setUninstallURL → GET /api/urls/uninstall/{ext_id} → redirects here</code>
                    </div>
                </div>
                <input type="url" id="cfg_uninstall_url" value="<?= htmlspecialchars(get_config('ext_uninstall_redirect_url') ?? '') ?>"
                       placeholder="https://crm.waclick.in/goodbye (optional)"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-white transition-all">
            </div>

            <button onclick="saveUrlConfig()"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all flex items-center gap-2 shadow-sm">
                <i class="fas fa-save"></i> Save URL Configuration
            </button>
        </div>
    </div>
</div>

<!-- ── TAB: domSelector ──────────────────────────────────── -->
<div id="tab-domselector" class="tab-content">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="font-bold text-gray-700 text-sm">domSelector.json Editor</h3>
                <p class="text-xs text-gray-400 mt-1">The extension fetches this every 10 minutes. Increment the version to force all extensions to refresh their DOM selectors.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500">Current version:</span>
                <span class="px-2 py-1 bg-indigo-50 text-indigo-700 text-xs font-black rounded-lg" id="domVersionBadge"><?= htmlspecialchars($dom_version) ?></span>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Version String <span class="text-gray-400 font-normal">(increment to push update to all extensions)</span></label>
            <div class="flex gap-2">
                <input type="text" id="dom_version_input" value="<?= htmlspecialchars($dom_version) ?>" placeholder="1.0.0"
                       class="w-40 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all font-mono">
                <button onclick="bumpVersion()" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all">
                    <i class="fas fa-plus mr-1"></i> Auto Bump
                </button>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-600 mb-1.5">JSON Content</label>
            <div class="bg-gray-900 rounded-xl overflow-hidden border border-gray-800">
                <div class="flex items-center gap-2 px-4 py-2 border-b border-gray-800 bg-gray-950">
                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">domSelector.json</span>
                    <button onclick="formatJson()" class="ml-auto text-[10px] text-gray-400 hover:text-white transition-all"><i class="fas fa-magic mr-1"></i>Format</button>
                    <button onclick="validateJson()" class="text-[10px] text-gray-400 hover:text-white transition-all"><i class="fas fa-check mr-1"></i>Validate</button>
                </div>
                <textarea id="dom_json_input" rows="16" spellcheck="false"
                          class="w-full px-4 py-4 bg-gray-900 text-green-400 text-xs font-mono resize-none focus:outline-none leading-relaxed"><?= htmlspecialchars($dom_json) ?></textarea>
            </div>
            <div id="jsonValidationMsg" class="hidden mt-2 text-xs rounded-xl px-3 py-2"></div>
        </div>

        <button onclick="saveDomSelector()"
                class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all flex items-center gap-2 shadow-sm">
            <i class="fas fa-save"></i> Save & Publish domSelector
        </button>
    </div>
</div>

</main>
</div>

<script>
// Tab switching
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}

// Chart
const ctx = document.getElementById('installChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                {
                    label: 'Installs',
                    data: <?= json_encode($chart_data['installs']) ?>,
                    backgroundColor: 'rgba(99,102,241,0.8)',
                    borderRadius: 6,
                },
                {
                    label: 'Uninstalls',
                    data: <?= json_encode($chart_data['uninstalls']) ?>,
                    backgroundColor: 'rgba(239,68,68,0.7)',
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
}

// Log filter
function filterLogs() {
    const event  = document.getElementById('eventFilter').value;
    const extid  = document.getElementById('extIdFilter').value.toLowerCase();
    document.querySelectorAll('.log-row').forEach(row => {
        const matchEvent = !event || row.dataset.event === event;
        const matchId    = !extid || row.dataset.extid.toLowerCase().includes(extid);
        row.style.display = (matchEvent && matchId) ? '' : 'none';
    });
}

// Save URL config
function saveUrlConfig() {
    const data = {
        action:         'save_ext_urls',
        install_url:    document.getElementById('cfg_install_url').value.trim(),
        update_url:     document.getElementById('cfg_update_url').value.trim(),
        uninstall_url:  document.getElementById('cfg_uninstall_url').value.trim(),
    };
    $.post('api/extension_tracker_api.php', data, function(res) {
        showToast(res.status === 'success' ? res.message : res.message, res.status === 'success' ? 'success' : 'error');
    }, 'json');
}

// Auto-bump version (1.2.3 → 1.2.4)
function bumpVersion() {
    const inp = document.getElementById('dom_version_input');
    const parts = inp.value.split('.').map(Number);
    if (parts.length === 3) {
        parts[2]++;
        inp.value = parts.join('.');
    }
}

// Format JSON
function formatJson() {
    const ta = document.getElementById('dom_json_input');
    try {
        ta.value = JSON.stringify(JSON.parse(ta.value), null, 2);
        showValidation('Formatted successfully.', true);
    } catch(e) {
        showValidation('Invalid JSON: ' + e.message, false);
    }
}

// Validate JSON
function validateJson() {
    const ta = document.getElementById('dom_json_input');
    try {
        JSON.parse(ta.value);
        showValidation('Valid JSON ✓', true);
    } catch(e) {
        showValidation('Invalid JSON: ' + e.message, false);
    }
}

function showValidation(msg, ok) {
    const el = document.getElementById('jsonValidationMsg');
    el.className = 'mt-2 text-xs rounded-xl px-3 py-2 ' + (ok ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-600 border border-red-100');
    el.textContent = msg;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 4000);
}

// Save domSelector
function saveDomSelector() {
    const version = document.getElementById('dom_version_input').value.trim();
    const json    = document.getElementById('dom_json_input').value.trim();

    // Validate JSON first
    try { JSON.parse(json); } catch(e) {
        showToast('Invalid JSON — fix errors before saving.', 'error'); return;
    }
    if (!version) { showToast('Version is required.', 'error'); return; }

    $.post('api/extension_tracker_api.php', {
        action:  'save_dom_selector',
        version: version,
        json:    json,
    }, function(res) {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            document.getElementById('domVersionBadge').textContent = version;
        } else {
            showToast(res.message, 'error');
        }
    }, 'json');
}
</script>

<?php include 'sections/common_modal.php'; include 'sections/footer.php'; ?>