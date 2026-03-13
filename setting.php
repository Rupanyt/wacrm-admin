<?php
include 'include/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: dashboard");
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$name = $_SESSION['name'];

$title = "System Settings";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title; ?> | <?php echo get_config('site_name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body { 
            background-color: #f8fafc; 
            font-family: 'Inter', sans-serif; 
            overflow: hidden;
        }

        .nav-item {
            white-space: nowrap;
            overflow: hidden;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        .active-link-white {
            background-color: #f0fdf4 !important;
            border: 1px solid #dcfce7;
            color: #166534 !important;
        }
        .active-link-white i { color: #22c55e !important; }

        #fullLogo span { transition: opacity 0.2s ease-in-out; }
    </style>
</head>
<body class="flex h-screen">

<?php include ('sections/sidebar.php'); ?>

<div class="flex-1 flex flex-col overflow-hidden">
    
<?php include 'sections/navbar.php'; ?>

<main class="flex-1 overflow-y-auto p-8 antialiased">
    <div class="mb-8">
        <h1 class="text-xl font-bold text-gray-800">System Configuration</h1>
        <p class="text-sm text-gray-500 mt-1">Customize your panel branding, account security, and theme colors.</p>
    </div>

    <form id="settingsForm" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="action" value="update_settings">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-id-card text-blue-500"></i> General Branding
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Site Name</label>
                            <input type="text" name="site_name" value="<?php echo get_config('site_name'); ?>" 
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:border-<?= get_config('theme_color'); ?>-300 outline-none">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Primary Color Theme</label>
                            <div class="relative">
                                <?php 
                                    $current_color = get_config('theme_color');
                                    $tw_colors = ['slate', 'gray', 'red', 'orange', 'amber', 'yellow', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'pink', 'rose'];
                                ?>
                                
                                <div onclick="toggleColorDropdown()" id="colorSelector" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm cursor-pointer flex items-center justify-between hover:border-gray-300">
                                    <div class="flex items-center gap-2">
                                        <div id="selectedColorCircle" class="w-4 h-4 rounded-full bg-<?= $current_color ?>-500 shadow-sm border border-black/10"></div>
                                        <span id="selectedColorName" class="text-gray-700 font-medium capitalize"><?= $current_color ?></span>
                                    </div>
                                    <i class="fas fa-chevron-down text-[10px] text-gray-400"></i>
                                </div>

                                <input type="hidden" name="theme_color" id="theme_color_input" value="<?= $current_color ?>">

                                <div id="colorDropdown" class="hidden absolute z-50 w-full mt-2 bg-white border border-gray-100 rounded-xl shadow-xl max-h-60 overflow-y-auto p-2">
                                    <?php foreach($tw_colors as $color): ?>
                                    <div onclick="selectColor('<?= $color ?>')" class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 rounded-lg cursor-pointer group">
                                        <div class="w-5 h-5 rounded-full bg-<?= $color ?>-500 border border-black/10"></div>
                                        <span class="text-sm text-gray-600 capitalize"><?= $color ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Default Software Name</label>
                        <input type="text" name="software_name" value="<?php echo get_config('default_software'); ?>"
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:border-<?= get_config('theme_color'); ?>-300 outline-none">
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-user-shield text-red-500"></i> Account Security
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Login Username</label>
                            <div class="relative">
                                <i class="fas fa-at absolute left-3 top-3 text-gray-300 text-xs"></i>
                                <input type="text" name="new_username" value="<?= $username ?>" 
                                       class="w-full pl-8 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Current Password (Required for changes)</label>
                            <input type="password" name="auth_password" placeholder="Verify current password" 
                                   class="w-full px-4 py-2.5 bg-red-50 border border-red-100 rounded-lg text-sm outline-none focus:border-red-300">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-dashed border-gray-100">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">New Password</label>
                            <input type="password" name="new_password" placeholder="Leave blank to keep current" 
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-green-300">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1">Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="Re-type new password" 
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-green-300">
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-3 italic">* Security details change karne ke liye "Current Password" dalna anivarya hai.</p>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-headset text-orange-500"></i> Support Details
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="email" name="support_email" placeholder="Support Email" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none" value="<?php echo get_config('support_email'); ?>">
                        <input type="text" name="support_whatsapp" placeholder="WhatsApp Number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none" value="<?php echo get_config('support_whatsapp'); ?>">
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm text-center">
                    <label class="block text-[11px] font-bold text-gray-400 uppercase mb-4">Rectangular Logo</label>
                    <div class="mb-4 flex justify-center">
                        <img src="<?= get_config('rect_logo_path'); ?>" class="h-12 object-contain bg-gray-50 p-2 rounded border" id="rectPreview">
                    </div>
                    <input type="file" name="rect_logo" class="hidden" id="rectInput" onchange="previewImg(this, 'rectPreview')">
                    <button type="button" onclick="document.getElementById('rectInput').click()" class="text-xs font-bold text-blue-500 hover:underline">Change Logo</button>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm text-center">
                    <label class="block text-[11px] font-bold text-gray-400 uppercase mb-4">Circle Icon (Sidebar)</label>
                    <div class="mb-4 flex justify-center">
                        <img src="<?= get_config('circle_logo_path') ?>" class="w-16 h-16 rounded-full object-contain bg-gray-50 border p-2" id="circlePreview">
                    </div>
                    <input type="file" name="circle_logo" class="hidden" id="circleInput" onchange="previewImg(this, 'circlePreview')">
                    <button type="button" onclick="document.getElementById('circleInput').click()" class="text-xs font-bold text-blue-500 hover:underline">Change Icon</button>
                </div>

                <button type="submit" id="saveBtn" class="w-full py-4 bg-<?= get_config('theme_color'); ?>-300 hover:bg-<?= get_config('theme_color'); ?>-400 text-<?= get_config('theme_color'); ?>-900 font-black rounded-2xl shadow-lg transition-all transform hover:-translate-y-1">
                    <i class="fas fa-save mr-2"></i> SAVE ALL CHANGES
                </button>
            </div>
        </div>
    </form>
</main>

</div>

<script>
// Image preview function
function previewImg(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#' + previewId).attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Form Submit logic
$('#settingsForm').on('submit', function(e){
    e.preventDefault();
    let formData = new FormData(this);
    $('#saveBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

    $.ajax({
        url: 'api/settings_api',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(() => { location.reload(); }, 2000);
            } else {
                showToast(res.message, 'error');
                $('#saveBtn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i> SAVE ALL CHANGES');
            }
        },
        error: function() {
            showToast('Something went wrong!', 'error');
            $('#saveBtn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i> SAVE ALL CHANGES');
        }
    });
});

function toggleColorDropdown() {
    $('#colorDropdown').toggleClass('hidden');
}

function selectColor(color) {
    $('#theme_color_input').val(color);
    $('#selectedColorName').text(color);
    $('#selectedColorCircle').attr('class', 'w-4 h-4 rounded-full border border-black/10 shadow-sm bg-' + color + '-500');
    $('#colorDropdown').addClass('hidden');
}
</script>

<?php  
    include 'sections/common_modal.php';
    include 'sections/footer.php'; 
?>