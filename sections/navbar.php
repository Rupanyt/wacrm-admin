<header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8">
    <div class="flex items-center gap-4">
        <button class="text-gray-500 lg:hidden"><i class="fas fa-bars"></i></button>
        <h2 class="font-semibold text-gray-700"><?= get_config('site_name'); ?></h2>
    </div>
    <div class="flex items-center gap-4">
        <div class="text-right hidden sm:block">
            <p class="text-sm font-bold text-gray-800 leading-none"><?= htmlspecialchars($username ?? $_SESSION['username'] ?? 'User'); ?></p>
            <p class="text-[10px] tracking-widest text-gray-400 font-semibold mt-1">
                <?= str_replace('_', ' ', $role ?? $_SESSION['role'] ?? ''); ?>
            </p>
        </div>
        <div class="w-10 h-10 bg-<?= get_config('theme_color'); ?>-100 rounded-full flex items-center justify-center text-<?= get_config('theme_color'); ?>-700 font-bold border border-<?= get_config('theme_color'); ?>-200 shadow-sm">
            <?= strtoupper(substr($username ?? $_SESSION['username'] ?? 'U', 0, 1)); ?>
        </div>
    </div>
</header>
