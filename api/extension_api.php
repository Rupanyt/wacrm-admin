<?php
ob_start();
include '../include/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired.']); exit;
}

$my_id   = $_SESSION['user_id'];
$my_role = $_SESSION['role'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

// ═══════════════════════════════════════════════════════════════
// ADMIN ACTIONS
// ═══════════════════════════════════════════════════════════════

if ($action === 'save_version') {
    // ── Upload new base extension ZIP ───────────────────────────
    if (!in_array($my_role, ['super_admin', 'admin'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']); exit;
    }

    $version_name = trim($_POST['version_name'] ?? '');
    $changelog    = trim($_POST['changelog'] ?? '');

    if (empty($version_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Version name is required.']); exit;
    }
    if (empty($_FILES['base_zip']['name'])) {
        echo json_encode(['status' => 'error', 'message' => 'Please upload a ZIP file.']); exit;
    }

    // Validate ZIP
    $file     = $_FILES['base_zip'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'zip') {
        echo json_encode(['status' => 'error', 'message' => 'Only ZIP files are allowed.']); exit;
    }
    if ($file['size'] > 100 * 1024 * 1024) { // 100MB max
        echo json_encode(['status' => 'error', 'message' => 'File too large (max 100MB).']); exit;
    }

    $upload_dir = '../uploads/extensions/base/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $safe_version = preg_replace('/[^a-zA-Z0-9._-]/', '_', $version_name);
    $filename     = 'base_v' . $safe_version . '_' . time() . '.zip';
    $dest         = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['status' => 'error', 'message' => 'File upload failed. Check folder permissions.']); exit;
    }

    $zip_path  = 'uploads/extensions/base/' . $filename;
    $vn_esc    = $conn->real_escape_string($version_name);
    $cl_esc    = $conn->real_escape_string($changelog);
    $zp_esc    = $conn->real_escape_string($zip_path);

    $conn->query("INSERT INTO extension_versions (version_name, changelog, zip_path, is_active, created_by)
                  VALUES ('$vn_esc', '$cl_esc', '$zp_esc', 1, '$my_id')");

    if ($conn->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Version v' . $version_name . ' uploaded successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

if ($action === 'update_version') {
    if (!in_array($my_role, ['super_admin', 'admin'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']); exit;
    }
    $id           = (int)($_POST['id'] ?? 0);
    $version_name = $conn->real_escape_string(trim($_POST['version_name'] ?? ''));
    $changelog    = $conn->real_escape_string(trim($_POST['changelog'] ?? ''));

    if (!$id || !$version_name) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']); exit;
    }

    // If new zip uploaded, handle it
    $zip_update = '';
    if (!empty($_FILES['base_zip']['name'])) {
        $file = $_FILES['base_zip'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'zip') { echo json_encode(['status' => 'error', 'message' => 'Only ZIP allowed.']); exit; }

        $upload_dir = '../uploads/extensions/base/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $safe    = preg_replace('/[^a-zA-Z0-9._-]/', '_', $version_name);
        $fname   = 'base_v' . $safe . '_' . time() . '.zip';
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $fname)) {
            $zp = $conn->real_escape_string('uploads/extensions/base/' . $fname);
            $zip_update = ", zip_path='$zp'";
            // Delete old file
            $old = $conn->query("SELECT zip_path FROM extension_versions WHERE id='$id'")->fetch_assoc();
            if ($old && file_exists('../' . $old['zip_path'])) @unlink('../' . $old['zip_path']);
        }
    }

    $conn->query("UPDATE extension_versions SET version_name='$version_name', changelog='$changelog' $zip_update WHERE id='$id'");
    echo json_encode(['status' => 'success', 'message' => 'Version updated!']);
    exit;
}

if ($action === 'toggle_version') {
    if (!in_array($my_role, ['super_admin', 'admin'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']); exit;
    }
    $id      = (int)($_POST['id'] ?? 0);
    $current = (int)($_POST['current'] ?? 0);
    $new     = $current ? 0 : 1;
    $conn->query("UPDATE extension_versions SET is_active='$new' WHERE id='$id'");
    echo json_encode(['status' => 'success', 'message' => 'Status updated.']);
    exit;
}

if ($action === 'delete_version') {
    if (!in_array($my_role, ['super_admin', 'admin'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']); exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    $v  = $conn->query("SELECT zip_path FROM extension_versions WHERE id='$id'")->fetch_assoc();
    if ($v) {
        $conn->query("DELETE FROM extension_versions WHERE id='$id'");
        if (file_exists('../' . $v['zip_path'])) @unlink('../' . $v['zip_path']);
        echo json_encode(['status' => 'success', 'message' => 'Version deleted.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Version not found.']);
    }
    exit;
}

if ($action === 'get_version') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    $v  = $conn->query("SELECT id, version_name, changelog FROM extension_versions WHERE id='$id'")->fetch_assoc();
    if ($v) echo json_encode(['status' => 'success', 'data' => $v]);
    else echo json_encode(['status' => 'error', 'message' => 'Not found.']);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// RESELLER ACTIONS
// ═══════════════════════════════════════════════════════════════

if ($action === 'save_branding') {
    if ($my_role !== 'reseller') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']); exit;
    }

    $ext_name     = $conn->real_escape_string(trim($_POST['ext_name'] ?? ''));
    $ext_short    = $conn->real_escape_string(trim($_POST['ext_short_name'] ?? ''));
    $ext_desc     = $conn->real_escape_string(trim($_POST['ext_description'] ?? ''));
    $support_url  = $conn->real_escape_string(trim($_POST['support_url'] ?? ''));

    if (!$ext_name || !$ext_short) {
        echo json_encode(['status' => 'error', 'message' => 'Extension name and short name are required.']); exit;
    }

    // Icon upload
    $icon_dir = '../uploads/extensions/icons/';
    if (!is_dir($icon_dir)) mkdir($icon_dir, 0755, true);

    $existing = $conn->query("SELECT icon_path, logo_path FROM reseller_branding WHERE reseller_id='$my_id'")->fetch_assoc();

    $icon_path_db = $conn->real_escape_string($existing['icon_path'] ?? '');
    $logo_path_db = $conn->real_escape_string($existing['logo_path'] ?? '');

    if (!empty($_FILES['icon_file']['name']) && $_FILES['icon_file']['error'] === 0) {
        $f    = $_FILES['icon_file'];
        $fext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($fext, ['png', 'jpg', 'jpeg', 'webp'])) {
            echo json_encode(['status' => 'error', 'message' => 'Icon must be PNG/JPG/WEBP.']); exit;
        }
        $fname = 'icon_' . $my_id . '_' . time() . '.png';
        if (move_uploaded_file($f['tmp_name'], $icon_dir . $fname)) {
            if ($existing['icon_path'] && file_exists('../' . $existing['icon_path'])) @unlink('../' . $existing['icon_path']);
            $icon_path_db = $conn->real_escape_string('uploads/extensions/icons/' . $fname);
        }
    }

    if (!empty($_FILES['logo_file']['name']) && $_FILES['logo_file']['error'] === 0) {
        $f    = $_FILES['logo_file'];
        $fext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($fext, ['png', 'jpg', 'jpeg', 'webp'])) {
            echo json_encode(['status' => 'error', 'message' => 'Logo must be PNG/JPG/WEBP.']); exit;
        }
        $fname = 'logo_' . $my_id . '_' . time() . '.png';
        if (move_uploaded_file($f['tmp_name'], $icon_dir . $fname)) {
            if ($existing['logo_path'] && file_exists('../' . $existing['logo_path'])) @unlink('../' . $existing['logo_path']);
            $logo_path_db = $conn->real_escape_string('uploads/extensions/icons/' . $fname);
        }
    }

    if ($existing) {
        $conn->query("UPDATE reseller_branding SET
            ext_name='$ext_name', ext_short_name='$ext_short', ext_description='$ext_desc',
            support_url='$support_url', icon_path='$icon_path_db', logo_path='$logo_path_db'
            WHERE reseller_id='$my_id'");
    } else {
        $conn->query("INSERT INTO reseller_branding
            (reseller_id, ext_name, ext_short_name, ext_description, support_url, icon_path, logo_path)
            VALUES ('$my_id', '$ext_name', '$ext_short', '$ext_desc', '$support_url', '$icon_path_db', '$logo_path_db')");
    }

    echo json_encode(['status' => 'success', 'message' => 'Branding profile saved!']);
    exit;
}

if ($action === 'generate_extension') {
    if ($my_role !== 'reseller') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']); exit;
    }

    // Load branding
    $branding = $conn->query("SELECT * FROM reseller_branding WHERE reseller_id='$my_id'")->fetch_assoc();
    if (!$branding || empty($branding['ext_name'])) {
        // Can't echo JSON here since headers may not be set yet
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Please save your branding profile first.']);
        exit;
    }

    // Get latest active version
    $version = $conn->query("SELECT * FROM extension_versions WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch_assoc();
    if (!$version) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'No active extension version available.']);
        exit;
    }

    $base_zip = '../' . $version['zip_path'];
    if (!file_exists($base_zip)) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Base ZIP file not found on server.']);
        exit;
    }

    // Create temp working directory
    $tmp_dir  = sys_get_temp_dir() . '/ext_gen_' . $my_id . '_' . time() . '/';
    mkdir($tmp_dir, 0755, true);

    // Extract base ZIP
    $zip = new ZipArchive();
    if ($zip->open($base_zip) !== true) {
        @rmdir($tmp_dir);
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Could not open base ZIP.']);
        exit;
    }
    $zip->extractTo($tmp_dir);
    $zip->close();

    // Find the root folder inside the ZIP (e.g. "whatsapp crm/")
    $items = scandir($tmp_dir);
    $ext_root = $tmp_dir;
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..' && is_dir($tmp_dir . $item)) {
            $ext_root = $tmp_dir . $item . '/';
            break;
        }
    }

    // ── 1. Modify manifest.json ─────────────────────────────────
    $manifest_path = $ext_root . 'manifest.json';
    if (file_exists($manifest_path)) {
        $manifest = json_decode(file_get_contents($manifest_path), true);
        if ($manifest) {
            $manifest['name']        = $branding['ext_name'];
            $manifest['description'] = $branding['ext_description'] ?: $branding['ext_name'];
            // Keep version from the version record
            $manifest['version']     = $version['version_name'];
            // Remove update_url for white-label (avoids Chrome CWS update check)
            unset($manifest['update_url']);
            // Remove the hardcoded key so reseller can sideload
            unset($manifest['key']);
            file_put_contents($manifest_path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    }

    // ── 2. Modify label/config/utils.json ───────────────────────
    $utils_path = $ext_root . 'label/config/utils.json';
    if (file_exists($utils_path)) {
        $utils = json_decode(file_get_contents($utils_path), true);
        if ($utils) {
            $utils['name']         = $branding['ext_name'];
            $utils['primeiroNome'] = $branding['ext_short_name'];
            $utils['nameID']       = $branding['ext_short_name'];
            $utils['descricao']    = $branding['ext_description'] ?: $branding['ext_name'];
            if (!empty($branding['support_url'])) {
                $utils['suporte']  = $branding['support_url'];
            }
            // Remove original key so it doesn't conflict
            unset($utils['key']);
            $utils['chromeStoreID'] = 'activate';
            file_put_contents($utils_path, json_encode($utils, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    }

    // ── 3. Replace icons ────────────────────────────────────────
    $icons_dir = $ext_root . 'label/icons/plugin/';

    if (!empty($branding['icon_path']) && file_exists('../' . $branding['icon_path'])) {
        copy('../' . $branding['icon_path'], $icons_dir . 'icon.png');
    }
    if (!empty($branding['logo_path']) && file_exists('../' . $branding['logo_path'])) {
        copy('../' . $branding['logo_path'], $icons_dir . 'logo.png');
    }

    // ── 4. Create output ZIP ────────────────────────────────────
    $out_dir = '../uploads/extensions/generated/';
    if (!is_dir($out_dir)) mkdir($out_dir, 0755, true);

    $safe_name  = preg_replace('/[^a-zA-Z0-9_-]/', '_', $branding['ext_short_name']);
    $out_zip_path = $out_dir . $safe_name . '_v' . $version['version_name'] . '_' . time() . '.zip';

    $out_zip = new ZipArchive();
    if ($out_zip->open($out_zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        _cleanup_dir($tmp_dir);
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Could not create output ZIP.']);
        exit;
    }

    // Add all files recursively
    _zip_directory($out_zip, $tmp_dir, '');
    $out_zip->close();

    // ── 5. Update DB ────────────────────────────────────────────
    $vid = $version['id'];
    $conn->query("UPDATE reseller_branding SET last_generated_at=NOW(), last_version_id='$vid' WHERE reseller_id='$my_id'");

    // ── 6. Stream ZIP to browser ────────────────────────────────
    $download_name = $safe_name . '_extension_v' . $version['version_name'] . '.zip';

    ob_end_clean(); // Clear any output buffers
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $download_name . '"');
    header('Content-Length: ' . filesize($out_zip_path));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    readfile($out_zip_path);

    // Cleanup
    @unlink($out_zip_path);
    _cleanup_dir($tmp_dir);
    exit;
}

// ── Helper: Recursively add directory to zip ───────────────────
function _zip_directory(ZipArchive $zip, string $base_dir, string $prefix): void {
    $items = scandir($base_dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full_path  = $base_dir . $item;
        $zip_path   = $prefix ? $prefix . '/' . $item : $item;
        if (is_dir($full_path)) {
            $zip->addEmptyDir($zip_path);
            _zip_directory($zip, $full_path . '/', $zip_path);
        } else {
            $zip->addFile($full_path, $zip_path);
        }
    }
}

// ── Helper: Recursively delete temp dir ────────────────────────
function _cleanup_dir(string $dir): void {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . $item;
        is_dir($path) ? _cleanup_dir($path . '/') : @unlink($path);
    }
    @rmdir(rtrim($dir, '/'));
}

// Fallback
echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
ob_end_flush();
?>
