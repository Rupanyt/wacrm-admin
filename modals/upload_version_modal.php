<?php include '../include/config.php';
if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) exit; ?>

<!-- JSZip for client-side ZIP validation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<div class="space-y-5">

    <!-- ── ZIP Structure Guide ────────────────────────────────── -->
    <div class="bg-gray-950 rounded-xl overflow-hidden border border-gray-800">
        <button type="button" onclick="toggleGuide()" class="w-full flex items-center justify-between px-4 py-3 text-left">
            <span class="flex items-center gap-2 text-xs font-bold text-gray-300">
                <i class="fas fa-book-open text-indigo-400"></i> ZIP Structure Guide — Read before uploading
            </span>
            <i id="guideChevron" class="fas fa-chevron-down text-gray-500 text-xs transition-transform"></i>
        </button>

        <div id="guideBody" class="hidden border-t border-gray-800">

            <div class="px-4 pt-3 pb-2">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Required ZIP Structure</p>
                <pre class="text-[11px] text-green-400 leading-relaxed font-mono">your_extension_folder/
├── <span class="text-yellow-300">manifest.json</span>          ← required
├── background.js
├── content/
├── label/
│   ├── config/
│   │   └── <span class="text-yellow-300">utils.json</span>     ← required
│   └── icons/
│       └── plugin/
│           ├── icon.png   ← replaced by reseller
│           └── logo.png   ← replaced by reseller
└── ...</pre>
            </div>

            <div class="px-4 pt-2 pb-3 border-t border-gray-800">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 bg-yellow-500/20 text-yellow-300 text-[10px] font-black rounded">manifest.json</span>
                    <span class="text-[10px] text-gray-500">Fields replaced per reseller are marked ✓</span>
                </div>
                <pre class="text-[11px] leading-relaxed font-mono bg-black/40 rounded-lg p-3 overflow-x-auto"><span class="text-gray-500">{</span>
  <span class="text-blue-300">"manifest_version"</span>: <span class="text-orange-300">3</span>,                    <span class="text-gray-600">// must be 3</span>
  <span class="text-blue-300">"name"</span>: <span class="text-green-300">"Your CRM Name"</span>,             <span class="text-gray-600">// ✓ replaced</span>
  <span class="text-blue-300">"version"</span>: <span class="text-green-300">"7.4.3.20"</span>,               <span class="text-gray-600">// ✓ replaced</span>
  <span class="text-blue-300">"description"</span>: <span class="text-green-300">"Your description"</span>,  <span class="text-gray-600">// ✓ replaced</span>
  <span class="text-blue-300">"background"</span>: <span class="text-gray-400">{</span> <span class="text-blue-300">"service_worker"</span>: <span class="text-green-300">"background.js"</span> <span class="text-gray-400">}</span>,
  <span class="text-blue-300">"action"</span>: <span class="text-gray-400">{</span> <span class="text-blue-300">"default_icon"</span>: <span class="text-green-300">"label/icons/plugin/icon.png"</span> <span class="text-gray-400">}</span>,
  <span class="text-blue-300">"icons"</span>: <span class="text-gray-400">{</span> <span class="text-blue-300">"128"</span>: <span class="text-green-300">"label/icons/plugin/icon.png"</span> <span class="text-gray-400">}</span>,
  <span class="text-blue-300">"permissions"</span>: <span class="text-gray-400">[</span><span class="text-green-300">"unlimitedStorage"</span>, <span class="text-green-300">"storage"</span>, <span class="text-green-300">"alarms"</span>, <span class="text-green-300">"tabs"</span><span class="text-gray-400">]</span>,
  <span class="text-blue-300">"host_permissions"</span>: <span class="text-gray-400">[</span><span class="text-green-300">"*://*.whatsapp.com/*"</span><span class="text-gray-400">]</span>,
  <span class="text-blue-300">"content_scripts"</span>: <span class="text-gray-400">[{</span>
    <span class="text-blue-300">"matches"</span>: <span class="text-gray-400">[</span><span class="text-green-300">"https://web.whatsapp.com/*"</span><span class="text-gray-400">]</span>,
    <span class="text-blue-300">"js"</span>: <span class="text-gray-400">[</span><span class="text-green-300">"content/index.js"</span><span class="text-gray-400">]</span>
  <span class="text-gray-400">}]</span>
  <span class="text-red-400">// ✗ do NOT include "key" or "update_url" — auto-removed</span>
<span class="text-gray-500">}</span></pre>
            </div>

            <div class="px-4 pt-2 pb-3 border-t border-gray-800">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 bg-indigo-500/20 text-indigo-300 text-[10px] font-black rounded">label/config/utils.json</span>
                    <span class="text-[10px] text-gray-500">Fields replaced per reseller are marked ✓</span>
                </div>
                <pre class="text-[11px] leading-relaxed font-mono bg-black/40 rounded-lg p-3 overflow-x-auto"><span class="text-gray-500">{</span>
  <span class="text-blue-300">"chromeStoreID"</span>: <span class="text-green-300">"activate"</span>,       <span class="text-gray-600">// keep as "activate"</span>
  <span class="text-blue-300">"nameID"</span>: <span class="text-green-300">"GDCRM"</span>,               <span class="text-gray-600">// ✓ replaced with short name</span>
  <span class="text-blue-300">"sigeID"</span>: <span class="text-green-300">"1"</span>,                   <span class="text-gray-600">// keep as "1"</span>
  <span class="text-blue-300">"language"</span>: <span class="text-green-300">"en"</span>,               <span class="text-gray-600">// keep as "en"</span>
  <span class="text-blue-300">"name"</span>: <span class="text-green-300">"Your CRM Name"</span>,         <span class="text-gray-600">// ✓ replaced</span>
  <span class="text-blue-300">"primeiroNome"</span>: <span class="text-green-300">"YourBrand"</span>,    <span class="text-gray-600">// ✓ replaced with short name</span>
  <span class="text-blue-300">"descricao"</span>: <span class="text-green-300">"Your description"</span>  <span class="text-gray-600">// ✓ replaced</span>
  <span class="text-red-400">// ✗ do NOT include "key" — auto-removed</span>
<span class="text-gray-500">}</span></pre>
            </div>

            <div class="px-4 pt-2 pb-3 border-t border-gray-800">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Auto-Validation Checks</p>
                <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                    <div class="flex items-center gap-1.5 text-gray-400"><i class="fas fa-check-circle text-green-500 w-3"></i> ZIP contains manifest.json</div>
                    <div class="flex items-center gap-1.5 text-gray-400"><i class="fas fa-check-circle text-green-500 w-3"></i> manifest_version = 3</div>
                    <div class="flex items-center gap-1.5 text-gray-400"><i class="fas fa-check-circle text-green-500 w-3"></i> manifest has name, version</div>
                    <div class="flex items-center gap-1.5 text-gray-400"><i class="fas fa-check-circle text-green-500 w-3"></i> label/config/utils.json exists</div>
                    <div class="flex items-center gap-1.5 text-gray-400"><i class="fas fa-check-circle text-green-500 w-3"></i> utils.json has nameID, primeiroNome</div>
                    <div class="flex items-center gap-1.5 text-gray-400"><i class="fas fa-check-circle text-green-500 w-3"></i> icons folder exists in label/</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Form ───────────────────────────────────────────────── -->
    <form id="uploadVersionForm" enctype="multipart/form-data">

        <!-- Version Name -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Version Name <span class="text-red-400">*</span></label>
            <input type="text" name="version_name" id="versionNameInput" required placeholder="e.g. 7.5.0"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
            <p class="text-[10px] text-gray-400 mt-1">Use semantic versioning: major.minor.patch</p>
        </div>

        <!-- ZIP Upload -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Base Extension ZIP <span class="text-red-400">*</span></label>
            <div id="zipDropZone" onclick="document.getElementById('zipFileInput').click()"
                 class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/30 transition-all">
                <i id="zipIcon" class="fas fa-file-archive text-gray-300 text-3xl mb-2 block"></i>
                <p id="zipFileName" class="text-sm text-gray-500">Click to upload or drag & drop</p>
                <p class="text-[10px] text-gray-400 mt-0.5">.zip only — max 100MB</p>
                <input type="file" id="zipFileInput" name="base_zip" accept=".zip" required class="hidden" onchange="validateZip(this)">
            </div>

            <!-- Validation result box -->
            <div id="validationBox" class="hidden mt-3 rounded-xl border p-3 text-xs">
                <div id="validationSpinner" class="flex items-center gap-2 text-gray-500">
                    <i class="fas fa-spinner fa-spin text-indigo-500"></i> Validating ZIP structure...
                </div>
                <div id="validationResults" class="hidden space-y-1.5"></div>
            </div>
        </div>

        <!-- Changelog -->
        <div class="mb-5">
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-bold text-gray-600">Changelog / Release Notes</label>
                <div class="flex gap-1 flex-wrap">
                    <button type="button" onclick="insertLine('✅ Added: ')"    class="px-2 py-1 bg-green-50 hover:bg-green-100 text-green-700 text-[10px] font-bold rounded-lg transition-all">+ Added</button>
                    <button type="button" onclick="insertLine('🐛 Fixed: ')"    class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 text-[10px] font-bold rounded-lg transition-all">+ Fixed</button>
                    <button type="button" onclick="insertLine('⚡ Improved: ')" class="px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 text-[10px] font-bold rounded-lg transition-all">+ Improved</button>
                    <button type="button" onclick="insertLine('⚠️ Changed: ')"  class="px-2 py-1 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 text-[10px] font-bold rounded-lg transition-all">+ Changed</button>
                    <button type="button" onclick="insertLine('🗑️ Removed: ')"  class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 text-[10px] font-bold rounded-lg transition-all">+ Removed</button>
                </div>
            </div>

            <!-- Template buttons -->
            <div class="flex gap-2 mb-2">
                <button type="button" onclick="applyTemplate('initial')"
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded-lg transition-all flex items-center gap-1">
                    <i class="fas fa-magic"></i> Initial Release Template
                </button>
                <button type="button" onclick="applyTemplate('update')"
                        class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-[10px] font-bold rounded-lg transition-all flex items-center gap-1">
                    <i class="fas fa-sync"></i> Update Template
                </button>
                <button type="button" onclick="document.getElementById('changelogInput').value = ''; updatePreview();"
                        class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-500 text-[10px] font-bold rounded-lg transition-all ml-auto flex items-center gap-1">
                    <i class="fas fa-trash-alt"></i> Clear
                </button>
            </div>

            <textarea name="changelog" id="changelogInput" rows="8"
                      placeholder="Write your release notes here, or use a template above..."
                      oninput="updatePreview()"
                      class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all resize-none font-mono leading-relaxed"></textarea>

            <!-- Live preview toggle -->
            <div class="mt-2">
                <button type="button" onclick="togglePreview()" class="text-[10px] text-indigo-500 hover:underline font-bold flex items-center gap-1">
                    <i class="fas fa-eye"></i> <span id="previewToggleText">Show Preview</span>
                </button>
                <div id="changelogPreview" class="hidden mt-2 p-3 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-700 leading-relaxed whitespace-pre-line max-h-40 overflow-y-auto"></div>
            </div>

            <p class="text-[10px] text-gray-400 mt-1.5">Resellers will see this in Version History. Use the quick-insert buttons above for consistent formatting.</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3">
            <button type="submit" id="uploadVersionBtn" disabled
                    class="flex-1 px-4 py-3 font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2 bg-gray-200 text-gray-400 cursor-not-allowed"
                    title="Upload a valid ZIP first">
                <i class="fas fa-upload"></i> Upload Version
            </button>
            <button type="button" onclick="closeModal()"
                    class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:bg-gray-50 font-bold text-sm rounded-xl transition-all">
                Cancel
            </button>
        </div>

    </form>
</div>

<script>
// ── Guide toggle ────────────────────────────────────────────
function toggleGuide() {
    const body    = document.getElementById('guideBody');
    const chevron = document.getElementById('guideChevron');
    const hidden  = body.classList.toggle('hidden');
    chevron.style.transform = hidden ? 'rotate(0deg)' : 'rotate(180deg)';
}

// ── Drag & drop ─────────────────────────────────────────────
const zz = document.getElementById('zipDropZone');
zz.addEventListener('dragover',  e => { e.preventDefault(); zz.classList.add('border-indigo-400','bg-indigo-50'); });
zz.addEventListener('dragleave', () => zz.classList.remove('border-indigo-400','bg-indigo-50'));
zz.addEventListener('drop', e => {
    e.preventDefault(); zz.classList.remove('border-indigo-400','bg-indigo-50');
    const inp = document.getElementById('zipFileInput');
    inp.files = e.dataTransfer.files;
    validateZip(inp);
});

// ── Client-side ZIP Validation ──────────────────────────────
async function validateZip(input) {
    const file = input.files[0];
    if (!file) return;

    document.getElementById('zipFileName').textContent = file.name;
    document.getElementById('zipIcon').className       = 'fas fa-spinner fa-spin text-indigo-400 text-3xl mb-2 block';

    const vbox    = document.getElementById('validationBox');
    const spinner = document.getElementById('validationSpinner');
    const results = document.getElementById('validationResults');

    vbox.className = 'mt-3 rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs';
    vbox.classList.remove('hidden');
    spinner.classList.remove('hidden');
    results.classList.add('hidden');
    results.innerHTML = '';

    const checks   = [];
    let allPassed  = true;

    try {
        const arrayBuffer = await file.arrayBuffer();
        const zip         = await JSZip.loadAsync(arrayBuffer);
        const files       = Object.keys(zip.files);

        function findFile(pattern) {
            return files.find(f => f.endsWith(pattern) && !zip.files[f].dir) || null;
        }

        // Check 1: manifest.json
        const manifestPath = findFile('manifest.json');
        if (!manifestPath) {
            checks.push({ ok: false, msg: 'manifest.json not found in ZIP' });
            allPassed = false;
        } else {
            checks.push({ ok: true, msg: 'manifest.json found → ' + manifestPath });
            let manifest;
            try {
                manifest = JSON.parse(await zip.files[manifestPath].async('string'));
                checks.push({ ok: true, msg: 'manifest.json is valid JSON' });
            } catch(e) {
                checks.push({ ok: false, msg: 'manifest.json is not valid JSON: ' + e.message });
                allPassed = false; manifest = null;
            }
            if (manifest) {
                if (manifest.manifest_version === 3) {
                    checks.push({ ok: true, msg: 'manifest_version = 3 ✓' });
                } else {
                    checks.push({ ok: false, msg: 'manifest_version must be 3 (found: ' + manifest.manifest_version + ')' });
                    allPassed = false;
                }
                const missing = ['name','version','description'].filter(f => !manifest[f]);
                if (missing.length === 0) {
                    checks.push({ ok: true, msg: 'Required fields present: name, version, description' });
                } else {
                    checks.push({ ok: false, msg: 'Missing in manifest.json: ' + missing.join(', ') });
                    allPassed = false;
                }
                if (manifest.background && manifest.background.service_worker) {
                    checks.push({ ok: true, msg: 'background.service_worker defined: ' + manifest.background.service_worker });
                } else {
                    checks.push({ ok: 'warn', msg: 'Warning: background.service_worker not defined' });
                }
            }
        }

        // Check 2: utils.json
        const utilsPath = findFile('label/config/utils.json');
        if (!utilsPath) {
            checks.push({ ok: false, msg: 'label/config/utils.json not found in ZIP' });
            allPassed = false;
        } else {
            checks.push({ ok: true, msg: 'label/config/utils.json found → ' + utilsPath });
            let utils;
            try {
                utils = JSON.parse(await zip.files[utilsPath].async('string'));
                checks.push({ ok: true, msg: 'utils.json is valid JSON' });
            } catch(e) {
                checks.push({ ok: false, msg: 'utils.json is not valid JSON: ' + e.message });
                allPassed = false; utils = null;
            }
            if (utils) {
                const missingU = ['nameID','primeiroNome','name','descricao'].filter(f => !utils[f]);
                if (missingU.length === 0) {
                    checks.push({ ok: true, msg: 'Required fields present: nameID, primeiroNome, name, descricao' });
                } else {
                    checks.push({ ok: false, msg: 'Missing in utils.json: ' + missingU.join(', ') });
                    allPassed = false;
                }
                if (utils.chromeStoreID === 'activate') {
                    checks.push({ ok: true, msg: 'chromeStoreID = "activate" ✓' });
                } else {
                    checks.push({ ok: 'warn', msg: 'Warning: chromeStoreID is "' + utils.chromeStoreID + '" — expected "activate"' });
                }
            }
        }

        // Check 3: icons
        const iconPath = findFile('label/icons/plugin/icon.png');
        const logoPath = findFile('label/icons/plugin/logo.png');
        checks.push(iconPath
            ? { ok: true,   msg: 'label/icons/plugin/icon.png found' }
            : { ok: 'warn', msg: 'Warning: label/icons/plugin/icon.png not found' });
        checks.push(logoPath
            ? { ok: true,   msg: 'label/icons/plugin/logo.png found' }
            : { ok: 'warn', msg: 'Warning: label/icons/plugin/logo.png not found' });

    } catch (e) {
        checks.push({ ok: false, msg: 'Could not read ZIP: ' + e.message });
        allPassed = false;
    }

    // Render results
    spinner.classList.add('hidden');
    results.classList.remove('hidden');

    const errors   = checks.filter(c => c.ok === false).length;
    const warnings = checks.filter(c => c.ok === 'warn').length;

    const summary = document.createElement('div');
    summary.className = 'flex items-center gap-2 mb-2 p-2 rounded-lg ' + (allPassed ? 'bg-green-50 border border-green-100' : 'bg-red-50 border border-red-100');
    summary.innerHTML = allPassed
        ? `<i class="fas fa-check-circle text-green-500"></i><span class="font-bold text-green-700">Validation passed! ${warnings > 0 ? warnings + ' warning(s)' : 'Ready to upload.'}</span>`
        : `<i class="fas fa-times-circle text-red-500"></i><span class="font-bold text-red-600">${errors} error(s) found — fix before uploading</span>`;
    results.appendChild(summary);

    checks.forEach(c => {
        const row = document.createElement('div');
        const icon = c.ok === true ? 'fa-check-circle text-green-500' : (c.ok === 'warn' ? 'fa-exclamation-circle text-yellow-500' : 'fa-times-circle text-red-500');
        const cls  = c.ok === true ? 'text-gray-600' : (c.ok === 'warn' ? 'text-yellow-700' : 'text-red-600 font-semibold');
        row.className = 'flex items-start gap-1.5';
        row.innerHTML = `<i class="fas ${icon} mt-0.5 flex-shrink-0 text-[11px]"></i><span class="${cls}">${c.msg}</span>`;
        results.appendChild(row);
    });

    vbox.className = 'mt-3 rounded-xl border p-3 text-xs ' + (allPassed ? 'border-green-200 bg-green-50/50' : 'border-red-200 bg-red-50/50');

    document.getElementById('zipIcon').className = allPassed
        ? 'fas fa-check-circle text-green-500 text-3xl mb-2 block'
        : 'fas fa-times-circle text-red-500 text-3xl mb-2 block';

    // Enable/disable upload button based on validation
    const btn = document.getElementById('uploadVersionBtn');
    if (allPassed) {
        btn.disabled  = false;
        btn.className = 'flex-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm cursor-pointer';
        btn.title     = '';
    } else {
        btn.disabled  = true;
        btn.className = 'flex-1 px-4 py-3 bg-gray-200 text-gray-400 font-bold text-sm rounded-xl flex items-center justify-center gap-2 cursor-not-allowed';
        btn.title     = 'Fix validation errors first';
    }
}

// ── Changelog helpers ───────────────────────────────────────
function insertLine(prefix) {
    const ta  = document.getElementById('changelogInput');
    const pos = ta.selectionStart;
    const val = ta.value;
    // Insert at cursor, on new line if needed
    const before   = val.substring(0, pos);
    const after    = val.substring(pos);
    const newline  = (before.length > 0 && !before.endsWith('\n')) ? '\n' : '';
    ta.value       = before + newline + prefix + after;
    const newPos   = before.length + newline.length + prefix.length;
    ta.setSelectionRange(newPos, newPos);
    ta.focus();
    updatePreview();
}

function applyTemplate(type) {
    const ta = document.getElementById('changelogInput');
    if (ta.value.trim() !== '' && !confirm('This will replace your current notes. Continue?')) return;

    const version = document.getElementById('versionNameInput').value.trim() || 'X.X.X';

    if (type === 'initial') {
        ta.value =
`🚀 Initial Release — Version ${version}

✅ Added: WhatsApp Web CRM sidebar with contact management
✅ Added: Bulk messaging with custom templates
✅ Added: Auto-reply and quick response system
✅ Added: Label & tag system for organizing chats
✅ Added: Notes and follow-up reminders per contact
✅ Added: Export contacts and chat history to CSV
✅ Added: Multi-account support (switch between accounts)
✅ Added: CRM pipeline — Kanban board view
✅ Added: ChatGPT AI assistant integration
✅ Added: Google Contacts & Calendar sync
✅ Added: Custom branding (white-label ready)
✅ Added: Dark mode support

⚡ Performance: Optimized load time for large chat lists
⚡ Performance: Lazy loading for media-heavy conversations

📌 Note: This is the first public release. Please report any bugs via the support link in the extension.`;
    } else {
        ta.value =
`🔄 Update — Version ${version}

✅ Added: Schedule messages for a future date/time
✅ Added: Contact import via CSV file
🐛 Fixed: Extension crashing on WhatsApp Web reload
🐛 Fixed: Labels not saving correctly on first login
🐛 Fixed: Quick reply not working in group chats
⚡ Improved: Sidebar load speed by 40%
⚡ Improved: Memory usage reduced for long sessions
⚠️ Changed: Settings page redesigned — re-check your preferences
🗑️ Removed: Legacy v1 API calls (deprecated)

📌 Note: Please reload WhatsApp Web after updating the extension.`;
    }
    updatePreview();
}

function togglePreview() {
    const preview = document.getElementById('changelogPreview');
    const label   = document.getElementById('previewToggleText');
    const hidden  = preview.classList.toggle('hidden');
    label.textContent = hidden ? 'Show Preview' : 'Hide Preview';
    if (!hidden) updatePreview();
}

function updatePreview() {
    const val     = document.getElementById('changelogInput').value;
    const preview = document.getElementById('changelogPreview');
    if (!preview.classList.contains('hidden')) {
        preview.textContent = val || '(empty)';
    }
}

// ── Form submit ─────────────────────────────────────────────
$('#uploadVersionForm').on('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('uploadVersionBtn');
    if (btn.disabled) return;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    btn.disabled  = true;

    const fd = new FormData(this);
    fd.append('action', 'save_version'); // ← critical: action must be appended

    $.ajax({
        url: 'api/extension_api.php', type: 'POST',
        data: fd, processData: false, contentType: false, dataType: 'json',
        xhr: function() {
            const x = new XMLHttpRequest();
            x.upload.addEventListener('progress', function(ev) {
                if (ev.lengthComputable) {
                    const pct = Math.round((ev.loaded / ev.total) * 100);
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading ' + pct + '%...';
                }
            });
            return x;
        },
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
            showToast('Upload failed. Check file size / server permissions.', 'error');
            btn.innerHTML = orig;
            btn.disabled  = false;
        }
    });
});
</script>
