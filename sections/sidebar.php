<?php
$current_page = basename($_SERVER['PHP_SELF'], ".php");

function isActive($pageName, $current_page) {
    return ($pageName == $current_page)
        ? 'active-link-white'
        : 'hover:bg-'.get_config('theme_color').'-50 text-gray-600 hover:text-'.get_config('theme_color').'-700';
}

$role     = $role     ?? $_SESSION['role']     ?? '';
$username = $username ?? $_SESSION['username'] ?? '';
$user_id  = $user_id  ?? $_SESSION['user_id']  ?? 0;

// Pending payments badge (admin/super_admin)
$pending_payments = 0;
if (in_array($role, ['super_admin', 'admin'])) {
    $where = ($role === 'admin')
        ? "WHERE reseller_id IN (SELECT id FROM users WHERE parent_id='$user_id' AND role='reseller') AND payment_status='pending'"
        : "WHERE payment_status='pending'";
    $r = $conn->query("SELECT COUNT(*) FROM payments $where");
    if ($r) $pending_payments = (int)$r->fetch_row()[0];
}

// Reseller plan expiry badge
$plan_badge     = '';
$plan_badge_cls = '';
if ($role === 'reseller') {
    $rd = $conn->query("SELECT u.plan_id, u.plan_expiry, rp.license_limit, u.extra_licenses
                        FROM users u LEFT JOIN reseller_plans rp ON u.plan_id = rp.id
                        WHERE u.id='$user_id'")->fetch_assoc();
    if (empty($rd['plan_id'])) {
        $plan_badge     = 'No Plan';
        $plan_badge_cls = 'bg-red-100 text-red-600';
    } elseif (!empty($rd['plan_expiry']) && date('Y-m-d') > $rd['plan_expiry']) {
        $plan_badge     = 'Expired';
        $plan_badge_cls = 'bg-red-100 text-red-600';
    } elseif (!empty($rd['plan_expiry'])) {
        $days = (int)ceil((strtotime($rd['plan_expiry']) - time()) / 86400);
        if ($days <= 7) {
            $plan_badge     = $days . 'd left';
            $plan_badge_cls = 'bg-orange-100 text-orange-600';
        }
    }
}
?>

<aside id="sidebar" class="w-64 bg-white border-r border-gray-100 flex-shrink-0 flex flex-col h-full transition-all duration-300 relative shadow-sm">

    <!-- Logo -->
    <div class="p-6 h-24 flex items-center justify-center border-b border-gray-50 overflow-hidden">
        <div id="fullLogo" class="flex items-center justify-center transition-opacity duration-300 w-full px-2">
            <a href="dashboard" class="block">
                <img src="<?= get_config('rect_logo_path'); ?>"
                     alt="<?= get_config('site_name'); ?>"
                     class="h-12 w-auto object-contain max-w-full"
                     onerror="">
            </a>
        </div>
        <div id="circleLogo" class="hidden transition-opacity duration-300">
            <div class="w-12 h-12 bg-white rounded-full shadow-md border border-gray-100 flex items-center justify-center">
                <img src="assets/icon-circle.png" alt="Icon" class="w-8 h-8 object-contain">
            </div>
        </div>
    </div>

    <!-- Collapse toggle -->
    <button onclick="toggleSidebar()" class="absolute -right-3 top-10 w-6 h-6 bg-white border border-gray-200 rounded-full flex items-center justify-center text-[10px] text-gray-400 hover:text-<?= get_config('theme_color'); ?>-600 shadow-sm z-50">
        <i id="collapseIcon" class="fas fa-chevron-left"></i>
    </button>

    <nav class="flex-1 mt-4 px-3 space-y-0.5 overflow-y-auto pb-4">

        <!-- Dashboard — all roles -->
        <a href="dashboard" class="nav-item py-3 px-4 rounded-xl <?= isActive('dashboard', $current_page); ?>" title="Dashboard">
            <i class="fas fa-th-large w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Dashboard</span>
        </a>

        <!-- ══════════════════════════════════════════════
             SUPER ADMIN ONLY
        ══════════════════════════════════════════════ -->
        <?php if ($role === 'super_admin'): ?>

        <a href="admins" class="nav-item py-3 px-4 rounded-xl <?= isActive('admins', $current_page); ?>" title="Manage Admins">
            <i class="fas fa-user-shield w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Manage Admins</span>
        </a>

        <?php endif; ?>

        <!-- ══════════════════════════════════════════════
             ADMIN + SUPER ADMIN
        ══════════════════════════════════════════════ -->
        <?php if (in_array($role, ['super_admin', 'admin'])): ?>

        <a href="resellers" class="nav-item py-3 px-4 rounded-xl <?= isActive('resellers', $current_page); ?>" title="Manage Resellers">
            <i class="fas fa-users w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Manage Resellers</span>
        </a>

        <a href="licenses" class="nav-item py-3 px-4 rounded-xl <?= isActive('licenses', $current_page); ?>" title="Licenses">
            <i class="fas fa-key w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Licenses</span>
        </a>

        <!-- Billing -->
        <div class="nav-text pt-4 pb-1 px-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Billing</p>
        </div>

        <a href="plans" class="nav-item py-3 px-4 rounded-xl <?= isActive('plans', $current_page); ?>" title="Reseller Plans">
            <i class="fas fa-boxes w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Reseller Plans</span>
        </a>

        <a href="payments" class="nav-item py-3 px-4 rounded-xl <?= isActive('payments', $current_page); ?>" title="Payments">
            <i class="fas fa-receipt w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium flex-1 flex items-center">
                Payments
                <?php if ($pending_payments > 0): ?>
                <span class="ml-auto bg-red-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full min-w-[18px] text-center"><?= $pending_payments ?></span>
                <?php endif; ?>
            </span>
        </a>

        <!-- Extension -->
        <div class="nav-text pt-4 pb-1 px-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Extension</p>
        </div>

        <a href="extension_versions" class="nav-item py-3 px-4 rounded-xl <?= isActive('extension_versions', $current_page); ?>" title="Extension Versions">
            <i class="fas fa-puzzle-piece w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Ext. Versions</span>
        </a>

        <!-- Developer -->
        <div class="nav-text pt-4 pb-1 px-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Developer</p>
        </div>

        <a href="api_docs" class="nav-item py-3 px-4 rounded-xl <?= isActive('api_docs', $current_page); ?>" title="API Access">
            <i class="fas fa-code w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">API Access</span>
        </a>

        <?php endif; ?>

        <!-- ══════════════════════════════════════════════
             RESELLER ONLY
        ══════════════════════════════════════════════ -->
        <?php if ($role === 'reseller'): ?>

        <a href="licenses" class="nav-item py-3 px-4 rounded-xl <?= isActive('licenses', $current_page); ?>" title="My Licenses">
            <i class="fas fa-key w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">My Licenses</span>
        </a>

        <!-- Plan & Billing -->
        <div class="nav-text pt-4 pb-1 px-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Plan & Billing</p>
        </div>

        <a href="reseller_plan" class="nav-item py-3 px-4 rounded-xl <?= isActive('reseller_plan', $current_page); ?>" title="My Plan">
            <i class="fas fa-id-badge w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium flex-1 flex items-center">
                My Plan
                <?php if ($plan_badge): ?>
                <span class="ml-auto <?= $plan_badge_cls ?> text-[9px] font-black px-1.5 py-0.5 rounded-full"><?= $plan_badge ?></span>
                <?php endif; ?>
            </span>
        </a>

        <!-- Extension -->
        <div class="nav-text pt-4 pb-1 px-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Extension</p>
        </div>

        <a href="extension" class="nav-item py-3 px-4 rounded-xl <?= isActive('extension', $current_page); ?>" title="My Extension">
            <i class="fas fa-puzzle-piece w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">My Extension</span>
        </a>

        <!-- Developer -->
        <div class="nav-text pt-4 pb-1 px-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Developer</p>
        </div>

        <a href="api_docs" class="nav-item py-3 px-4 rounded-xl <?= isActive('api_docs', $current_page); ?>" title="API Access">
            <i class="fas fa-code w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">API Access</span>
        </a>

        <?php endif; ?>

        <!-- ══════════════════════════════════════════════
             ALL ROLES
        ══════════════════════════════════════════════ -->
        <div class="nav-text pt-4 pb-1 px-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Account</p>
        </div>

        <a href="setting" class="nav-item py-3 px-4 rounded-xl <?= isActive('setting', $current_page); ?>" title="Settings">
            <i class="fas fa-cog w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Settings</span>
        </a>

    </nav>

    <!-- Logout -->
    <div class="p-4 border-t border-gray-50 flex-shrink-0">
        <a href="logout" class="nav-item hover:bg-red-50 py-3 px-4 rounded-xl text-red-500" title="Logout">
            <i class="fas fa-sign-out-alt w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Logout</span>
        </a>
    </div>

</aside>
