<?php
// =============================================================
//  api/services/update.php
//
//  Called by Chrome extension background.js every 5 minutes
//  (alarm: "Five_Minutes" → license_update → content script
//   sends this fetch from web.whatsapp.com)
//
//  Method : POST (JSON body)
//  Header : access-token: {cript_key}
//
//  Payload:
//    phone           - user's WhatsApp number
//    chromeStoreID   - Chrome extension runtime ID
//    user_logado     - current session state in extension
//    nome, checkout, painel_cliente, backend, tutorial,
//    suporte_clientes, timeZone
//
//  Response shape (must match what extension expects):
//  {
//    success     : bool,
//    user_logado : { session:{...}, user:{...} },
//    checkout    : url,
//    painel_cliente: url,
//    backend     : url,
//    nome        : string,
//    tutorial    : url,
//    suporte_clientes: { premium:url, gratuitos:url },
//    msg_id      : string
//  }
// =============================================================

require_once '../../include/config.php';

// ── CORS & headers ────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Token, access-token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// ── Helpers ───────────────────────────────────────────────────
function resp(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function normalize_phone(string $phone): string {
    // Strip all non-digits
    $p = preg_replace('/\D/', '', $phone);
    // Strip leading country code 91 if 12 digits starting with 91
    if (strlen($p) === 12 && substr($p, 0, 2) === '91') {
        $p = substr($p, 2);
    }
    return $p;
}

function build_user_logado(
    array  $license,
    string $wl_id,
    string $cript_key,
    string $backend_url,
    string $phone_raw
): array {
    $expiry   = $license['expiry_date'] ?? null;
    $is_valid = true;

    if ($expiry && strtotime($expiry) < time()) {
        $is_valid = false;
    }

    $user_status = ($is_valid && $license['status'] === 'active') ? 'premium' : 'free';
    $is_premium  = $user_status === 'premium';

    // Generate a deterministic but session-safe bearer token
    // based on license_key + date (changes daily, stable within a day)
    $bearer = hash('sha256', $license['license_key'] . date('Y-m-d') . 'gdcrm_salt');
    $plugin_token = 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.'
                  . rtrim(base64_encode(json_encode(['sub'=>$license['id'],'iat'=>time()])), '=')
                  . '.gdcrm_sig';

    return [
        'session' => [
            'is_load'        => true,
            'is_auth'        => true,
            'is_auth_google' => false,
            'user_status'    => $user_status,
            'is_premium'     => $is_premium,
        ],
        'user' => [
            'user_id'             => (string)$license['id'],
            'name'                => $license['client_name']   ?? '',
            'email'               => $license['client_email']  ?? '',
            'wl_id'               => $wl_id,
            'bearer_token'        => $bearer,
            'access_token_plugin' => $plugin_token,
            'user_premium'        => $is_premium ? ($expiry ?? true) : null,
            'dataCadastro'        => date('c', strtotime($license['created_at'] ?? 'now')),
            'whatsapp_registro'   => $license['client_mobile'] ?? '',
            'whatsapp_plugin'     => $phone_raw,
            'path'                => rtrim($backend_url, '/') . '/',
            'afiliado'            => '',
            'campanhaID'          => '',
            'cookies'             => [
                '_fbc'    => '',
                '_fbp'    => '',
                '_ga'     => '',
                '_ttclid' => '',
                '_ttp'    => '',
            ],
        ],
    ];
}

function build_free_user_logado(string $phone_raw, string $wl_id, string $backend_url): array {
    return [
        'session' => [
            'is_load'        => true,
            'is_auth'        => false,
            'is_auth_google' => false,
            'user_status'    => 'free',
            'is_premium'     => false,
        ],
        'user' => [
            'user_id'             => '',
            'name'                => '',
            'email'               => '',
            'wl_id'               => $wl_id,
            'bearer_token'        => '',
            'access_token_plugin' => '',
            'user_premium'        => null,
            'dataCadastro'        => date('c'),
            'whatsapp_registro'   => $phone_raw,
            'whatsapp_plugin'     => $phone_raw,
            'path'                => rtrim($backend_url, '/') . '/',
            'afiliado'            => '',
            'campanhaID'          => '',
            'cookies'             => [
                '_fbc' => '', '_fbp' => '', '_ga' => '',
                '_ttclid' => '', '_ttp' => '',
            ],
        ],
    ];
}

// ── Load config from DB ───────────────────────────────────────
$expected_token   = get_config('services_cript_key')       ?: 'ffce211a-7b07-4d91-ba5d-c40bb4034a83';
$nome             = get_config('ext_nome')                  ?: (get_config('site_name') ?: 'GD CRM');
$wl_id            = get_config('ext_wl_id')                 ?: 'gdcrm';
$checkout_url     = get_config('ext_checkout_url')          ?: ($base_url . 'user_login');
$tutorial_url     = get_config('ext_tutorial_url')          ?: '';
$support_premium  = get_config('ext_support_premium')       ?: '';
$support_free     = get_config('ext_support_free')          ?: '';
$painel_url       = get_config('ext_painel_cliente')        ?: ($base_url . 'user_login');
$phone_lookup     = get_config('ext_phone_lookup_enabled')  ?: '1';
$log_enabled      = get_config('ext_services_log_enabled')  ?: '0';
$backend_url      = $base_url;

// ── Validate access-token header ─────────────────────────────
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$req_token  = trim($headers['access-token'] ?? '');

if (!hash_equals($expected_token, $req_token)) {
    resp([
        'success'    => false,
        'msg_id'     => 'invalid_token',
        'message'    => 'Unauthorized: invalid access token.',
        'user_logado'=> build_free_user_logado('', $wl_id, $backend_url),
    ], 401);
}

// ── Parse JSON body ───────────────────────────────────────────
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    resp(['success' => false, 'msg_id' => 'invalid_payload', 'message' => 'Invalid JSON payload.'], 400);
}

$phone_raw    = trim($body['phone']        ?? '');
$chrome_id    = trim($body['chromeStoreID'] ?? '');
$user_logado  = $body['user_logado']        ?? [];

// ── Build standard response shell ────────────────────────────
$base_response = [
    'success'          => true,
    'msg_id'           => 'sync_ok',
    'nome'             => $nome,
    'checkout'         => $checkout_url,
    'painel_cliente'   => $painel_url,
    'backend'          => $backend_url,
    'tutorial'         => $tutorial_url,
    'suporte_clientes' => [
        'premium'   => $support_premium,
        'gratuitos' => $support_free,
    ],
];

// ── Optional: log the call ────────────────────────────────────
if ($log_enabled === '1' && !empty($phone_raw)) {
    $phone_esc  = $conn->real_escape_string(substr($phone_raw, 0, 30));
    $chrome_esc = $conn->real_escape_string(substr($chrome_id, 0, 100));
    $ip         = $conn->real_escape_string(
        explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]
    );
    $conn->query("INSERT IGNORE INTO extension_installs (ext_id, event, ip_address, user_agent)
                  VALUES ('$chrome_esc', 'update', '$ip', 'services_update')");
}

// ── Phone lookup disabled → return free session ───────────────
if ($phone_lookup !== '1') {
    resp(array_merge($base_response, [
        'user_logado' => build_free_user_logado($phone_raw, $wl_id, $backend_url),
    ]));
}

// ── Phone number lookup ───────────────────────────────────────
if (empty($phone_raw)) {
    resp(array_merge($base_response, [
        'msg_id'     => 'no_phone',
        'user_logado'=> build_free_user_logado('', $wl_id, $backend_url),
    ]));
}

$phone_clean = normalize_phone($phone_raw);

// Search licenses table by client_mobile
// Try: exact match, with country code, and without
$phones_to_try = array_unique(array_filter([
    $phone_raw,
    $phone_clean,
    '91' . $phone_clean,
    '+91' . $phone_clean,
]));

$license = null;

foreach ($phones_to_try as $try) {
    $try_esc = $conn->real_escape_string($try);
    $res = $conn->query(
        "SELECT * FROM licenses
         WHERE client_mobile = '$try_esc'
           AND status != 'blocked'
         ORDER BY
           CASE status WHEN 'active' THEN 0 ELSE 1 END,
           expiry_date DESC
         LIMIT 1"
    );
    if ($res && $res->num_rows > 0) {
        $license = $res->fetch_assoc();
        break;
    }
}

// ── No license found → return free user ──────────────────────
if (!$license) {
    resp(array_merge($base_response, [
        'success'    => true,
        'msg_id'     => 'no_license',
        'user_logado'=> build_free_user_logado($phone_raw, $wl_id, $backend_url),
    ]));
}

// ── License found — check expiry ─────────────────────────────
$expiry = $license['expiry_date'];
$expired = $expiry && strtotime($expiry) < time();

if ($license['status'] === 'expired' || $expired) {
    // Update status in DB if it hasn't been updated yet
    if ($license['status'] === 'active' && $expired) {
        $lid = intval($license['id']);
        $conn->query("UPDATE licenses SET status='expired' WHERE id=$lid");
    }

    resp(array_merge($base_response, [
        'success'    => true,
        'msg_id'     => 'license_expired',
        'user_logado'=> [
            'session' => [
                'is_load'        => true,
                'is_auth'        => false,
                'is_auth_google' => false,
                'user_status'    => 'free',
                'is_premium'     => false,
            ],
            'user' => [
                'user_id'             => (string)$license['id'],
                'name'                => $license['client_name'] ?? '',
                'email'               => $license['client_email'] ?? '',
                'wl_id'               => $wl_id,
                'bearer_token'        => '',
                'access_token_plugin' => '',
                'user_premium'        => null,
                'dataCadastro'        => date('c', strtotime($license['created_at'] ?? 'now')),
                'whatsapp_registro'   => $license['client_mobile'] ?? '',
                'whatsapp_plugin'     => $phone_raw,
                'path'                => rtrim($backend_url, '/') . '/',
                'afiliado'            => '',
                'campanhaID'          => '',
                'cookies'             => ['_fbc'=>'','_fbp'=>'','_ga'=>'','_ttclid'=>'','_ttp'=>''],
            ],
        ],
    ]));
}

// ── Active license → return premium session ───────────────────
resp(array_merge($base_response, [
    'success'    => true,
    'msg_id'     => 'license_valid',
    'user_logado'=> build_user_logado($license, $wl_id, $expected_token, $backend_url, $phone_raw),
]));
?>
