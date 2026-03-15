<?php
// =============================================================
//  api/services/initial-data.php
//
//  GET /api/services/initial-data/{chromeStoreID}
//  Header: access-token: {cript_key}
//
//  Called once on extension startup / install.
//  Returns webhooks, meet, migration config + all URLs.
//  All values are read from app_config via get_config().
//  No DB writes, no new tables.
// =============================================================

require_once '../../include/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Token, access-token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// ── Validate access-token ─────────────────────────────────────
$expected = get_config('services_cript_key') ?: 'ffce211a-7b07-4d91-ba5d-c40bb4034a83';
$headers  = array_change_key_case(getallheaders(), CASE_LOWER);
$token    = trim($headers['access-token'] ?? '');

if (!hash_equals($expected, $token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'msg_id' => 'invalid_token', 'message' => 'Unauthorized.']);
    exit;
}

// ── chromeStoreID from URL (set by .htaccess rewrite) ────────
$chrome_id = trim($_GET['chrome_id'] ?? '');

// ── Helper: safely decode a JSON config value ────────────────
function cfg_json(string $key, $default = null) {
    $raw = get_config($key);
    if (empty($raw)) return $default;
    $decoded = json_decode($raw, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $default;
}

// ── Webhooks ──────────────────────────────────────────────────
// Stored as JSON array in app_config key: ext_webhooks_json
// Example value: [{"name":"lead_capture","url":"https://...","active":true}]
$webhooks = cfg_json('ext_webhooks_json', []);

// ── Meet ──────────────────────────────────────────────────────
// Stored as JSON object in app_config key: ext_meet_json
// Example value: {"enabled":true,"provider":"google","link":"https://meet.google.com/abc"}
$meet = cfg_json('ext_meet_json', (object)[]);

// ── Migration ─────────────────────────────────────────────────
// Stored as JSON object in app_config key: ext_migration_json
// Example value: {"enabled":false,"version":"2.0","notice":"Please re-login"}
$migration = cfg_json('ext_migration_json', (object)[]);

// ── URLs → principais ─────────────────────────────────────────
// Built from individual config keys already set by extension_services.php
$checkout_url       = get_config('ext_checkout_url')    ?: '';
$support_premium    = get_config('ext_support_premium') ?: '';
$support_free       = get_config('ext_support_free')    ?: '';

// Optional extras stored as JSON: ext_urls_extras_json
// Example: [{"id":"blog","active":true,"redirect":false,"link":"https://..."}]
$extras = cfg_json('ext_urls_extras_json', []);

$principais = array_filter(array_merge(
    [
        ['id' => 'checkout',          'active' => true,  'redirect' => true,  'link' => $checkout_url],
        ['id' => 'suporte_premium',   'active' => true,  'redirect' => false, 'link' => $support_premium],
        ['id' => 'suporte_gratuitos', 'active' => true,  'redirect' => false, 'link' => $support_free],
    ],
    $extras
), fn($u) => !empty($u['link']));

// ── URLs → redes_sociais ──────────────────────────────────────
// Individual keys for common networks; all optional
$social_map = [
    'youtube'   => 'ext_youtube_url',
    'instagram' => 'ext_instagram_url',
    'facebook'  => 'ext_facebook_url',
    'telegram'  => 'ext_telegram_url',
    'tiktok'    => 'ext_tiktok_url',
    'twitter'   => 'ext_twitter_url',
];

$redes_sociais = [];
foreach ($social_map as $id => $cfg_key) {
    $link = get_config($cfg_key);
    if (!empty($link)) {
        $redes_sociais[] = ['id' => $id, 'link' => $link];
    }
}

// Extra social entries: ext_social_extras_json
// Example: [{"id":"discord","link":"https://..."}]
$social_extras = cfg_json('ext_social_extras_json', []);
foreach ($social_extras as $s) {
    if (!empty($s['id']) && !empty($s['link'])) {
        $redes_sociais[] = ['id' => $s['id'], 'link' => $s['link']];
    }
}

// ── Build response ────────────────────────────────────────────
$response = [
    'webhooks'  => $webhooks,
    'meet'      => $meet,
    'migration' => $migration,
    'urls'      => [
        'principais'    => array_values($principais),
        'redes_sociais' => $redes_sociais,
    ],
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
exit;
?>
