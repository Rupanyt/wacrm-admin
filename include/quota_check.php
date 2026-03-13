<?php
/**
 * LICENSE QUOTA CHECKER
 * Include this file at the top of your existing license creation logic.
 * Call: check_reseller_quota($conn, $user_id)
 * Returns: ['allowed' => bool, 'message' => string, 'used' => int, 'limit' => int]
 */

function check_reseller_quota($conn, $user_id) {
    $reseller = $conn->query("
        SELECT u.extra_licenses, u.plan_id, u.plan_expiry,
               rp.license_limit
        FROM users u
        LEFT JOIN reseller_plans rp ON u.plan_id = rp.id
        WHERE u.id = '$user_id' AND u.role = 'reseller'
    ")->fetch_assoc();

    if (!$reseller) {
        return ['allowed' => true, 'message' => '', 'used' => 0, 'limit' => 0]; // Not a reseller, skip
    }

    // No plan assigned - block license creation
    if (empty($reseller['plan_id'])) {
        return [
            'allowed' => false,
            'message' => 'You do not have an active plan. Please contact your admin or subscribe to a plan.',
            'used'    => 0,
            'limit'   => 0
        ];
    }

    // Plan expired
    if ($reseller['plan_expiry'] && date('Y-m-d') > $reseller['plan_expiry']) {
        return [
            'allowed' => false,
            'message' => 'Your plan has expired on ' . date('d M Y', strtotime($reseller['plan_expiry'])) . '. Please renew your plan to create licenses.',
            'used'    => 0,
            'limit'   => 0
        ];
    }

    $base_limit  = (int)($reseller['license_limit'] ?? 0);
    $extra       = (int)($reseller['extra_licenses'] ?? 0);
    $total_limit = $base_limit + $extra;

    $used = (int)$conn->query("SELECT COUNT(*) FROM licenses WHERE created_by='$user_id'")->fetch_row()[0];

    if ($used >= $total_limit) {
        return [
            'allowed' => false,
            'message' => "License quota reached! You've used $used of $total_limit licenses. Buy extra licenses or upgrade your plan from My Plan page.",
            'used'    => $used,
            'limit'   => $total_limit
        ];
    }

    return [
        'allowed' => true,
        'message' => '',
        'used'    => $used,
        'limit'   => $total_limit
    ];
}
?>
