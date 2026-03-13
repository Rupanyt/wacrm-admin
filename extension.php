<?php
include 'include/config.php';

if (!isset($_SESSION['user_id'])) { header("Location: login"); exit(); }
if ($_SESSION['role'] !== 'reseller') { header("Location: dashboard"); exit(); }

$role    = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$name    = $_SESSION['name'];
$title   = "My Extension";

// Load saved branding
$branding = $conn->query("SELECT * FROM reseller_branding WHERE reseller_id='$user_id'")->fetch_assoc();

// Latest active version
$latest = $conn->query("SELECT * FROM extension_versions WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch_assoc();

// All active versions for changelog page
$all_versions = $conn->query("SELECT * FROM extension_versions WHERE is_active=1 ORDER BY id DESC");

$has_branding = !empty($branding);
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
        .drop-zone { border: 2px dashed #d1d5db; transition: all 0.2s; cursor: pointer; }
        .drop-zone:hover, .drop-zone.drag-over { border-color: #6366f1; background: #eef2ff; }
        .img-preview { max-width: 80px; max-height: 80px; object-fit: contain; }
        .tab-btn { transition: all 0.2s; }
        .tab-btn.active { background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); color: #1f2937; font-weight: 700; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="flex h-screen">
<?php include 'sections/sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
<?php include 'sections/navbar.php'; ?>

<main class="flex-1 overflow-y-auto p-8 antialiased">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800">My White-Label Extension</h1>
            <p class="text-sm text-gray-500 mt-1">Customize and generate your branded Chrome extension.</p>
        </div>
        <?php if ($latest): ?>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100">
                <i class="fas fa-tag mr-1"></i> Latest: v<?= htmlspecialchars($latest['version_name']) ?>
            </span>
            <?php if ($has_branding && $branding['last_generated_at']): ?>
            <span class="px-3 py-1.5 bg-green-50 text-green-700 text-xs font-bold rounded-lg border border-green-100">
                <i class="fas fa-check mr-1"></i> Generated <?= date('d M Y', strtotime($branding['last_generated_at'])) ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$latest): ?>
    <!-- No version available -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-8 text-center">
        <i class="fas fa-exclamation-triangle text-yellow-400 text-3xl mb-3 block"></i>
        <h3 class="font-bold text-yellow-700">No Extension Version Available</h3>
        <p class="text-sm text-yellow-600 mt-1">Your admin has not uploaded any extension version yet. Please contact support.</p>
    </div>
    <?php else: ?>

    <!-- Tabs -->
    <div class="bg-gray-100 rounded-xl p-1 flex gap-1 mb-6 w-fit">
        <button class="tab-btn active px-5 py-2 rounded-lg text-sm text-gray-500" onclick="switchTab('branding')">
            <i class="fas fa-paint-brush mr-1.5"></i> Branding & Generate
        </button>
        <button class="tab-btn px-5 py-2 rounded-lg text-sm text-gray-500" onclick="switchTab('changelog')">
            <i class="fas fa-history mr-1.5"></i> Version History
        </button>
    </div>

    <!-- TAB: Branding -->
    <div id="tab-branding" class="tab-content active">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Form -->
            <div class="lg:col-span-2">
                <form id="brandingForm" enctype="multipart/form-data">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
                        <h3 class="font-bold text-gray-700 text-sm mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-md flex items-center justify-center text-xs font-black">1</span>
                            Extension Identity
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1.5">Extension Full Name <span class="text-red-400">*</span></label>
                                <input type="text" name="ext_name" id="ext_name" required maxlength="255"
                                       value="<?= htmlspecialchars($branding['ext_name'] ?? '') ?>"
                                       placeholder="e.g. MyBrand CRM: Superpowers for WhatsApp"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all"
                                       oninput="updatePreview()">
                                <p class="text-[10px] text-gray-400 mt-1">Shown in Chrome Extensions page</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Short Brand Name <span class="text-red-400">*</span></label>
                                    <input type="text" name="ext_short_name" id="ext_short_name" required maxlength="100"
                                           value="<?= htmlspecialchars($branding['ext_short_name'] ?? '') ?>"
                                           placeholder="e.g. MyBrand"
                                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all"
                                           oninput="updatePreview()">
                                    <p class="text-[10px] text-gray-400 mt-1">Displayed inside the extension UI</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Support / Website URL</label>
                                    <input type="url" name="support_url" id="support_url"
                                           value="<?= htmlspecialchars($branding['support_url'] ?? '') ?>"
                                           placeholder="https://yoursite.com"
                                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1.5">Description</label>
                                <textarea name="ext_description" id="ext_description" rows="3" maxlength="500"
                                          placeholder="Describe what your extension does..."
                                          class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-400 bg-gray-50 focus:bg-white transition-all resize-none"
                                          oninput="updatePreview()"><?= htmlspecialchars($branding['ext_description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Icons Upload -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
                        <h3 class="font-bold text-gray-700 text-sm mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-md flex items-center justify-center text-xs font-black">2</span>
                            Icons & Branding
                            <span class="text-[10px] font-normal text-gray-400 ml-1">PNG format recommended</span>
                        </h3>
                        <div class="grid grid-cols-2 gap-5">

                            <!-- Icon.png -->
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-2">Extension Icon <span class="text-gray-400 font-normal">(icon.png — shown in Chrome toolbar)</span></label>
                                <div class="drop-zone rounded-xl p-4 text-center relative" id="iconDropZone" onclick="document.getElementById('iconInput').click()">
                                    <?php if (!empty($branding['icon_path']) && file_exists($branding['icon_path'])): ?>
                                    <img id="iconPreviewImg" src="<?= $branding['icon_path'] ?>" class="img-preview mx-auto mb-2 rounded-lg">
                                    <?php else: ?>
                                    <img id="iconPreviewImg" src="" class="img-preview mx-auto mb-2 rounded-lg hidden">
                                    <?php endif; ?>
                                    <div id="iconPlaceholder" class="<?= !empty($branding['icon_path']) ? 'hidden' : '' ?>">
                                        <i class="fas fa-image text-gray-300 text-2xl mb-1 block"></i>
                                        <p class="text-xs text-gray-400">Click to upload</p>
                                        <p class="text-[10px] text-gray-300 mt-0.5">128×128px recommended</p>
                                    </div>
                                    <input type="file" id="iconInput" name="icon_file" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewImage(this, 'iconPreviewImg', 'iconPlaceholder')">
                                </div>
                                <?php if (!empty($branding['icon_path'])): ?>
                                <p class="text-[10px] text-green-600 mt-1"><i class="fas fa-check-circle mr-1"></i>Previously saved — upload new to replace</p>
                                <?php endif; ?>
                            </div>

                            <!-- Logo.png -->
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-2">Brand Logo <span class="text-gray-400 font-normal">(logo.png — shown inside extension)</span></label>
                                <div class="drop-zone rounded-xl p-4 text-center relative" id="logoDropZone" onclick="document.getElementById('logoInput').click()">
                                    <?php if (!empty($branding['logo_path']) && file_exists($branding['logo_path'])): ?>
                                    <img id="logoPreviewImg" src="<?= $branding['logo_path'] ?>" class="img-preview mx-auto mb-2 rounded-lg">
                                    <?php else: ?>
                                    <img id="logoPreviewImg" src="" class="img-preview mx-auto mb-2 rounded-lg hidden">
                                    <?php endif; ?>
                                    <div id="logoPlaceholder" class="<?= !empty($branding['logo_path']) ? 'hidden' : '' ?>">
                                        <i class="fas fa-image text-gray-300 text-2xl mb-1 block"></i>
                                        <p class="text-xs text-gray-400">Click to upload</p>
                                        <p class="text-[10px] text-gray-300 mt-0.5">Rectangular logo</p>
                                    </div>
                                    <input type="file" id="logoInput" name="logo_file" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewImage(this, 'logoPreviewImg', 'logoPlaceholder')">
                                </div>
                                <?php if (!empty($branding['logo_path'])): ?>
                                <p class="text-[10px] text-green-600 mt-1"><i class="fas fa-check-circle mr-1"></i>Previously saved — upload new to replace</p>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3">
                        <button type="button" id="saveBrandingBtn" onclick="saveBranding()"
                                class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm rounded-xl transition-all flex items-center gap-2">
                            <i class="fas fa-save"></i> Save Profile
                        </button>
                        <button type="button" id="generateBtn" onclick="generateExtension()"
                                class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                            <i class="fas fa-download"></i> Save & Generate Extension (.zip)
                        </button>
                    </div>

                </form>
            </div>

            <!-- Live Preview -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sticky top-6">
                    <h3 class="font-bold text-gray-700 text-sm mb-4 flex items-center gap-2">
                        <i class="fas fa-eye text-gray-400"></i> Live Preview
                    </h3>

                    <!-- Chrome Extension Card Preview -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 mb-4">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">Chrome Extensions Page</p>
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 bg-gray-200 rounded-lg overflow-hidden flex items-center justify-center flex-shrink-0" id="previewIconWrap">
                                <i class="fas fa-puzzle-piece text-gray-400 text-lg" id="previewIconFallback"></i>
                                <img id="previewIconImg" src="" class="w-full h-full object-cover hidden">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p id="previewName" class="font-bold text-gray-800 text-xs leading-tight truncate">
                                    <?= htmlspecialchars($branding['ext_name'] ?? 'Your Extension Name') ?>
                                </p>
                                <p id="previewVersion" class="text-[10px] text-indigo-600 mt-0.5">v<?= htmlspecialchars($latest['version_name']) ?></p>
                                <p id="previewDesc" class="text-[10px] text-gray-500 mt-1 leading-relaxed line-clamp-2">
                                    <?= htmlspecialchars($branding['ext_description'] ?? 'Your extension description will appear here.') ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- manifest.json preview -->
                    <div class="bg-gray-900 rounded-xl p-3 text-left overflow-hidden">
                        <p class="text-[9px] font-bold text-gray-500 uppercase mb-2">manifest.json preview</p>
                        <pre class="text-[10px] text-green-400 leading-relaxed overflow-x-auto whitespace-pre-wrap"><span class="text-gray-500">{</span>
  <span class="text-blue-300">"name"</span>: <span class="text-yellow-300">"<span id="previewManifestName"><?= htmlspecialchars($branding['ext_name'] ?? '...') ?></span>"</span>,
  <span class="text-blue-300">"version"</span>: <span class="text-yellow-300">"<?= htmlspecialchars($latest['version_name']) ?>"</span>,
  <span class="text-blue-300">"description"</span>: <span class="text-yellow-300">"<span id="previewManifestDesc"><?= htmlspecialchars(substr($branding['ext_description'] ?? '...', 0, 40)) ?></span>"</span>
<span class="text-gray-500">}</span></pre>
                    </div>

                    <!-- utils.json preview -->
                    <div class="bg-gray-900 rounded-xl p-3 text-left overflow-hidden mt-2">
                        <p class="text-[9px] font-bold text-gray-500 uppercase mb-2">utils.json preview</p>
                        <pre class="text-[10px] text-green-400 leading-relaxed overflow-x-auto whitespace-pre-wrap"><span class="text-gray-500">{</span>
  <span class="text-blue-300">"nameID"</span>: <span class="text-yellow-300">"<span id="previewNameID"><?= htmlspecialchars($branding['ext_short_name'] ?? '...') ?></span>"</span>,
  <span class="text-blue-300">"name"</span>: <span class="text-yellow-300">"<span id="previewUtilsName"><?= htmlspecialchars($branding['ext_name'] ?? '...') ?></span>"</span>,
  <span class="text-blue-300">"primeiroNome"</span>: <span class="text-yellow-300">"<span id="previewShortName"><?= htmlspecialchars($branding['ext_short_name'] ?? '...') ?></span>"</span>
<span class="text-gray-500">}</span></pre>
                    </div>

                    <?php if ($has_branding && $branding['last_generated_at']): ?>
                    <div class="mt-4 p-3 bg-green-50 border border-green-100 rounded-xl text-xs text-green-700">
                        <i class="fas fa-history mr-1"></i> Last generated <strong><?= date('d M Y, h:i A', strtotime($branding['last_generated_at'])) ?></strong>
                        <?php if ($branding['last_version_id']): ?>
                        <?php $lv = $conn->query("SELECT version_name FROM extension_versions WHERE id='{$branding['last_version_id']}'")->fetch_assoc(); ?>
                        <?php if ($lv): ?> using <strong>v<?= htmlspecialchars($lv['version_name']) ?></strong><?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- TAB: Changelog -->
    <div id="tab-changelog" class="tab-content">
        <div class="space-y-4">
            <?php if ($all_versions && $all_versions->num_rows > 0):
                $vi = 0;
                while ($v = $all_versions->fetch_assoc()):
                    $vi++;
                    $is_latest = ($vi === 1);
            ?>
            <div class="bg-white rounded-2xl border <?= $is_latest ? 'border-indigo-200 shadow-md' : 'border-gray-100 shadow-sm' ?> p-6">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center font-black text-sm">
                            v<?= htmlspecialchars($v['version_name']) ?>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-800">Version <?= htmlspecialchars($v['version_name']) ?></h3>
                                <?php if ($is_latest): ?>
                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[9px] font-black rounded-full uppercase">Latest</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-[11px] text-gray-400"><?= date('d M Y', strtotime($v['created_at'])) ?></p>
                        </div>
                    </div>
                    <?php if ($is_latest): ?>
                    <button onclick="generateExtension()"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl flex items-center gap-1.5 transition-all">
                        <i class="fas fa-download"></i> Generate This Version
                    </button>
                    <?php endif; ?>
                </div>
                <?php if ($v['changelog']): ?>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">What's New</p>
                    <div class="text-sm text-gray-600 leading-relaxed whitespace-pre-line"><?= nl2br(htmlspecialchars($v['changelog'])) ?></div>
                </div>
                <?php else: ?>
                <p class="text-sm text-gray-400 italic">No changelog provided for this version.</p>
                <?php endif; ?>
            </div>
            <?php endwhile; else: ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
                <i class="fas fa-history text-gray-200 text-4xl mb-3 block"></i>
                <p class="text-gray-400">No version history available yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; // end $latest check ?>

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

// Live preview update
function updatePreview() {
    const name  = document.getElementById('ext_name')?.value || '';
    const short = document.getElementById('ext_short_name')?.value || '';
    const desc  = document.getElementById('ext_description')?.value || '';
    document.getElementById('previewName').textContent         = name || 'Your Extension Name';
    document.getElementById('previewDesc').textContent         = desc || 'Your extension description will appear here.';
    document.getElementById('previewManifestName').textContent = name || '...';
    document.getElementById('previewManifestDesc').textContent = desc.substring(0, 40) || '...';
    document.getElementById('previewNameID').textContent       = short || '...';
    document.getElementById('previewUtilsName').textContent    = name || '...';
    document.getElementById('previewShortName').textContent    = short || '...';
}

// Image preview
function previewImage(input, imgId, placeholderId) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById(imgId);
        img.src = e.target.result;
        img.classList.remove('hidden');
        document.getElementById(placeholderId)?.classList.add('hidden');

        // Update extension card preview icon
        if (input.id === 'iconInput') {
            const pImg = document.getElementById('previewIconImg');
            pImg.src = e.target.result;
            pImg.classList.remove('hidden');
            document.getElementById('previewIconFallback')?.classList.add('hidden');
        }
    };
    reader.readAsDataURL(file);
}

// Save branding only
function saveBranding(andGenerate = false) {
    const name  = document.getElementById('ext_name').value.trim();
    const short = document.getElementById('ext_short_name').value.trim();
    if (!name || !short) { showToast('Extension name and short name are required.', 'error'); return; }

    const btn  = document.getElementById(andGenerate ? 'generateBtn' : 'saveBrandingBtn');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Saving...';
    btn.disabled  = true;

    const fd = new FormData(document.getElementById('brandingForm'));
    fd.append('action', 'save_branding');

    $.ajax({
        url: 'api/extension_api.php', type: 'POST',
        data: fd, processData: false, contentType: false, dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                showToast('Profile saved!', 'success');
                if (andGenerate) {
                    btn.innerHTML = '<i class="fas fa-cog fa-spin mr-1.5"></i> Generating...';
                    triggerDownload();
                } else {
                    btn.innerHTML = orig; btn.disabled = false;
                }
            } else {
                showToast(res.message, 'error');
                btn.innerHTML = orig; btn.disabled = false;
            }
        },
        error: function() { showToast('Server error.', 'error'); btn.innerHTML = orig; btn.disabled = false; }
    });
}

// Generate = save then download
function generateExtension() {
    saveBranding(true);
}

function triggerDownload() {
    // Direct browser download via form POST
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/extension_api.php';
    const actions = [['action', 'generate_extension']];
    actions.forEach(([k, v]) => {
        const i = document.createElement('input');
        i.type = 'hidden'; i.name = k; i.value = v;
        form.appendChild(i);
    });
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    setTimeout(() => {
        const btn = document.getElementById('generateBtn');
        btn.innerHTML = '<i class="fas fa-check mr-1.5"></i> Downloaded! Generate Again';
        btn.disabled  = false;
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-download mr-1.5"></i> Save & Generate Extension (.zip)';
        }, 3000);
    }, 2000);
}

// Drag & drop for icon zones
['iconDropZone', 'logoDropZone'].forEach(zoneId => {
    const zone = document.getElementById(zoneId);
    if (!zone) return;
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
        e.preventDefault(); zone.classList.remove('drag-over');
        const inputId = zoneId === 'iconDropZone' ? 'iconInput' : 'logoInput';
        const input   = document.getElementById(inputId);
        input.files   = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
    });
});
</script>

<?php include 'sections/common_modal.php'; include 'sections/footer.php'; ?>
