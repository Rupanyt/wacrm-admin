<?php
// extension_notes.php
// Public page — no login needed
// Opened in Chrome tab after extension update
require_once 'include/config.php';

// Get latest active version for notes display
$latest = $conn->query("SELECT * FROM extension_versions WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch_assoc();
$all    = $conn->query("SELECT * FROM extension_versions WHERE is_active=1 ORDER BY id DESC LIMIT 5");

$site_name  = get_config('site_name')      ?: 'GD CRM';
$logo       = get_config('rect_logo_path') ?: '';
$theme      = get_config('theme_color')    ?: 'green';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>What's New — <?= htmlspecialchars($site_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-indigo-50/30">

    <!-- Header -->
    <div class="bg-white border-b border-gray-100 shadow-sm">
        <div class="max-w-2xl mx-auto px-6 py-5 flex items-center gap-4">
            <?php if ($logo): ?>
            <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($site_name) ?>" class="h-10 w-auto object-contain">
            <?php else: ?>
            <h1 class="font-black text-gray-800 text-xl"><?= htmlspecialchars($site_name) ?></h1>
            <?php endif; ?>
            <div class="ml-auto">
                <span class="px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-full border border-indigo-100">
                    <i class="fas fa-rocket mr-1"></i> What's New
                </span>
            </div>
        </div>
    </div>

    <div class="max-w-2xl mx-auto px-6 py-10">

        <!-- Hero -->
        <div class="text-center mb-10">
            <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center text-white text-2xl mx-auto mb-4 shadow-lg shadow-indigo-200">
                <i class="fas fa-puzzle-piece"></i>
            </div>
            <h1 class="text-3xl font-black text-gray-800 mb-2">Extension Updated!</h1>
            <p class="text-gray-500">Your <?= htmlspecialchars($site_name) ?> extension has been updated to the latest version. Here's what changed.</p>
        </div>

        <?php if ($all && $all->num_rows > 0):
            $is_first = true;
            while ($v = $all->fetch_assoc()):
        ?>
        <!-- Version Card -->
        <div class="bg-white rounded-2xl border <?= $is_first ? 'border-indigo-200 shadow-lg shadow-indigo-50' : 'border-gray-100 shadow-sm' ?> p-6 mb-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 <?= $is_first ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600' ?> rounded-xl flex items-center justify-center font-black text-sm">
                    v<?= htmlspecialchars($v['version_name']) ?>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-black text-gray-800">Version <?= htmlspecialchars($v['version_name']) ?></h2>
                        <?php if ($is_first): ?>
                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-black rounded-full uppercase">Latest</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-[11px] text-gray-400"><?= date('d M Y', strtotime($v['created_at'])) ?></p>
                </div>
            </div>

            <?php if ($v['changelog']): ?>
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line"><?= nl2br(htmlspecialchars($v['changelog'])) ?></div>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400 italic">No changelog for this version.</p>
            <?php endif; ?>
        </div>
        <?php $is_first = false; endwhile; else: ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
            <i class="fas fa-history text-gray-200 text-4xl mb-3 block"></i>
            <p class="text-gray-400">No release notes available yet.</p>
        </div>
        <?php endif; ?>

        <!-- Footer CTA -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-400 mb-4">Ready to use your updated extension?</p>
            <a href="https://web.whatsapp.com" target="_blank"
               class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all shadow-sm shadow-indigo-200">
                <i class="fas fa-arrow-right"></i> Open WhatsApp Web
            </a>
        </div>
    </div>
</body>
</html>