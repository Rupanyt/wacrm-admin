<?php
$current_page = basename($_SERVER['PHP_SELF'], ".php"); 


function isActive($pageName, $current_page) {
    return ($pageName == $current_page) ? 'active-link-white' : 'hover:bg-'.get_config('theme_color').'-50 text-gray-600 hover:text-'.get_config('theme_color').'-700';
}
?>

<aside id="sidebar" class="w-64 bg-white border-r border-gray-100 flex-shrink-0 flex flex-col h-full transition-all duration-300 relative shadow-sm">
    
    <div class="p-6 h-24 flex items-center justify-center border-b border-gray-50 overflow-hidden">
        <div id="fullLogo" class="flex items-center justify-center transition-opacity duration-300 w-full px-2">
            <a href="dashboard" class="block">
                <img src="<?= get_config('rect_logo_path'); ?>" 
                    alt="<?php echo get_config('site_name'); ?>" 
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

    <button onclick="toggleSidebar()" class="absolute -right-3 top-10 w-6 h-6 bg-white border border-gray-200 rounded-full flex items-center justify-center text-[10px] text-gray-400 hover:text-<?= get_config('theme_color'); ?>-600 shadow-sm z-50">
        <i id="collapseIcon" class="fas fa-chevron-left"></i>
    </button>
    
    <nav class="flex-1 mt-6 px-3 space-y-2">
        <a href="dashboard" class="nav-item py-3 px-4 rounded-xl <?php echo isActive('dashboard', $current_page); ?>" title="Dashboard">
            <i class="fas fa-th-large w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Dashboard</span>
        </a>

        <?php if($role == 'super_admin'): ?>
        <a href="admins" class="nav-item py-3 px-4 rounded-xl <?php echo isActive('admins', $current_page); ?>" title="Manage Admins">
            <i class="fas fa-user-shield w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Manage Admins</span>
        </a>
        <?php endif; ?>

        <?php if($role == 'super_admin' || $role == 'admin'): ?>
        <a href="resellers" class="nav-item py-3 px-4 rounded-xl <?php echo isActive('resellers', $current_page); ?>" title="Manage Resellers">
            <i class="fas fa-users w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Manage Resellers</span>
        </a>
        <?php endif; ?>

        <a href="licenses" class="nav-item py-3 px-4 rounded-xl <?php echo isActive('licenses', $current_page); ?>" title="Licenses">
            <i class="fas fa-key w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Licenses</span>
        </a>

        <a href="setting" class="nav-item py-3 px-4 rounded-xl <?php echo isActive('setting', $current_page); ?>" title="Settings">
            <i class="fas fa-cog w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Settings</span>
        </a>
    </nav>

    <div class="p-4 border-t border-gray-50">
        <a href="logout" class="nav-item hover:bg-red-50 py-3 px-4 rounded-xl text-red-500" title="Logout">
            <i class="fas fa-sign-out-alt w-6 text-center text-sm"></i>
            <span class="nav-text ml-3 text-sm font-medium">Logout</span>
        </a>
    </div>
</aside>