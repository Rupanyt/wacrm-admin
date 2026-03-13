<?php
include 'include/config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo get_config('site_name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="icon" type="image/png" href="<?= get_config('circle_logo_path') ?>">
    <link rel="apple-touch-icon" href="<?= get_config('circle_logo_path') ?>">
    <link rel="shortcut icon" href="<?= get_config('circle_logo_path') ?>" type="image/x-icon">
    
</head>
<body class="bg-gray-50 flex items-center justify-center h-screen">



    <div class="w-full max-w-md p-8 bg-white shadow-sm border border-gray-100" style="border-radius: 4px;">
        <div class="text-center mb-8">




        <?php 
        
            $c = get_config('theme_color'); 
            $site_logo = get_config('circle_logo_path'); 
        ?>

        <div class="flex justify-center mb-8">
            <div class="relative group">
                <div class="absolute inset-0 bg-<?= $c ?>-300 rounded-full blur-xl opacity-20 group-hover:opacity-40 transition-opacity"></div>
                
                <div class="relative w-24 h-24 bg-white rounded-full p-1.5 shadow-2xl border-4 border-<?= $c ?>-100 flex items-center justify-center overflow-hidden">
                    <?php if(!empty($site_logo)): ?>
                        <img src="<?= $site_logo ?>" alt="Logo" class="w-full h-full object-contain rounded-full">
                    <?php else: ?>
                        <i class="fas fa-user-shield text-3xl text-<?= $c ?>-500"></i>
                    <?php endif; ?>
                </div>
            </div>
        </div>






            <h2 class="text-2xl font-bold text-gray-800 uppercase tracking-tight">
                <?php echo get_config('site_name'); ?>
            </h2>
            <p class="text-sm text-gray-500 mt-2">Sign in to manage your software licenses</p>
        </div>

        

        <form id="loginForm" class="space-y-6">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Username</label>
                <input type="text" name="username" required 
                       class="w-full px-4 py-3 bg-gray-50 border border-gray-200 focus:border-green-300 focus:ring-0 outline-none transition text-sm" 
                       placeholder="Enter username" style="border-radius: 2px;">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Password</label>
                <input type="password" name="password" required 
                       class="w-full px-4 py-3 bg-gray-50 border border-gray-200 focus:border-green-300 focus:ring-0 outline-none transition text-sm" 
                       placeholder="••••••••" style="border-radius: 2px;">
            </div>

            <button type="submit" id="loginBtn" 
                    class="w-full flex items-center justify-center gap-2 bg-<?= $c ?>-300 hover:bg-<?= $c ?>-400 text-<?= $c ?>-900 font-black py-3.5 uppercase tracking-widest text-xs transition-all transform hover:-translate-y-0.5 shadow-md active:scale-95"
                    style="border-radius: 2px;">
                
                <i class="fas fa-lock text-sm opacity-80"></i>
                
                <span>Secure Login</span>
            </button>
        </form>

        <div class="mt-8 text-center border-t pt-6">
            <p class="text-xs text-gray-400">&copy; <?php echo date('Y'); ?> Genius Devel. All Rights Reserved.</p>
        </div>
    </div>

    <?php include 'include/message_toast.php'; ?>

    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                const btn = $('#loginBtn');
                btn.html('<i class="fas fa-spinner fa-spin"></i> Authenticating...').attr('disabled', true);

                $.ajax({
                    url: 'api/login_api',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if(res.status === 'success') {
                            showToast(res.message, 'success');
                            // 2 sec baad redirect
                            setTimeout(() => {
                                window.location.href = 'dashboard.php';
                            }, 2000);
                        } else {
                            showToast(res.message, 'error');
                            btn.html('Secure Login').attr('disabled', false);
                        }
                    },
                    error: function() {
                        showToast("Something went wrong. Server error.", "error");
                        btn.html('Secure Login').attr('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html>