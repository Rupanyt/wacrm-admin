<?php
include 'include/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$name = $_SESSION['name'];

$title = "Dashboard";



if ($role == 'super_admin') {
    $total_admins = $conn->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetch_row()[0];
    $total_resellers = $conn->query("SELECT COUNT(*) FROM users WHERE role='reseller'")->fetch_row()[0];
    $total_licenses = $conn->query("SELECT COUNT(*) FROM licenses")->fetch_row()[0];
    $expired_licenses = $conn->query("SELECT COUNT(*) FROM licenses WHERE status='expired' OR status='blocked'")->fetch_row()[0];
} 
elseif ($role == 'admin') {
    $total_resellers = $conn->query("SELECT COUNT(*) FROM users WHERE role='reseller' AND parent_id='$user_id'")->fetch_row()[0];
    $total_licenses = $conn->query("SELECT COUNT(*) FROM licenses WHERE created_by IN (SELECT id FROM users WHERE parent_id='$user_id') OR created_by='$user_id'")->fetch_row()[0];
    $expired_licenses = $conn->query("SELECT COUNT(*) FROM licenses WHERE (created_by IN (SELECT id FROM users WHERE parent_id='$user_id') OR created_by='$user_id') AND (status='expired' OR status='blocked')")->fetch_row()[0];
} 
else {
    $total_licenses = $conn->query("SELECT COUNT(*) FROM licenses WHERE created_by='$user_id'")->fetch_row()[0];
    $expired_licenses = $conn->query("SELECT COUNT(*) FROM licenses WHERE created_by='$user_id' AND (status='expired' OR status='blocked')")->fetch_row()[0];
}



$limit_recent = 15;

if ($role == 'super_admin') {
    $recent_query = "SELECT l.*, u.username as creator_name FROM licenses l 
                     JOIN users u ON l.created_by = u.id 
                     ORDER BY l.id DESC LIMIT $limit_recent";
} elseif ($role == 'admin') {
    $recent_query = "SELECT l.*, u.username as creator_name FROM licenses l 
                     JOIN users u ON l.created_by = u.id 
                     WHERE l.created_by = '$user_id' 
                     OR l.created_by IN (SELECT id FROM users WHERE parent_id = '$user_id') 
                     ORDER BY l.id DESC LIMIT $limit_recent";
} else {
    $recent_query = "SELECT l.*, u.username as creator_name FROM licenses l 
                     JOIN users u ON l.created_by = u.id 
                     WHERE l.created_by = '$user_id' 
                     ORDER BY l.id DESC LIMIT $limit_recent";
}

$recent_result = $conn->query($recent_query);
$total_recent = $recent_result->num_rows;



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=  $title; ?> | <?php echo get_config('site_name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="icon" type="image/png" href="<?= get_config('circle_logo_path') ?>">
    <link rel="apple-touch-icon" href="<?= get_config('circle_logo_path') ?>">
    <link rel="shortcut icon" href="<?= get_config('circle_logo_path') ?>" type="image/x-icon">
    
    
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
            background-color: #f0fdf4 !important; /* Green-50 */
            border: 1px solid #dcfce7; /* Green-100 */
            color: #166534 !important; /* Green-800 */
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
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <?php if($role == 'super_admin'): ?>
        <div class="bg-white p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all rounded-xl">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-[13px] font-medium text-gra
                    y-500">Total Admins</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $total_admins; ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-user-shield text-lg"></i>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if($role == 'super_admin' || $role == 'admin'): ?>
        <div class="bg-white p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all rounded-xl">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-[13px] font-medium text-gray-500">Active Resellers</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $total_resellers; ?></h3>
                </div>
                <div class="w-12 h-12 bg-purple-50 text-purple-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-lg"></i>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all rounded-xl">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-[13px] font-medium text-gray-500">Total Licenses</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $total_licenses; ?></h3>
                </div>
                <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-key text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all rounded-xl">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-[13px] font-medium text-gray-500">Expired or Blocked</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $expired_licenses; ?></h3>
                </div>
                <div class="w-12 h-12 bg-red-50 text-red-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-100 shadow-sm rounded-xl overflow-hidden">
        
        <div class="p-5 border-b border-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h3 class="font-bold text-gray-800 text-base">Recent Licenses</h3>
                <p class="text-xs text-gray-500">Manage and monitor your recently generated tools.</p>
            </div>
            
            <div class="relative w-full md:w-72">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="tableSearch" placeholder="Search client or software..." 
                    class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-green-300 transition-all">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-6 py-4 text-[12px] font-semibold text-gray-600">Client Detail</th>
                        <th class="px-6 py-4 text-[12px] font-semibold text-gray-600">Software</th>
                        <th class="px-6 py-4 text-[12px] font-semibold text-gray-600">Status</th>
                        <th class="px-6 py-4 text-[12px] font-semibold text-gray-600 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
    <?php if($total_recent > 0): while($row = $recent_result->fetch_assoc()): 
        // Status Logic
        $today = date('Y-m-d');
        $is_expired = ($today > $row['expiry_date'] || $row['status'] == 'blocked');
        $status_text = $is_expired ? ($row['status'] == 'blocked' ? 'Blocked' : 'Expired') : 'Active';
        $status_class = $is_expired ? 'bg-red-50 text-red-600 border-red-100' : 'bg-green-50 text-green-600 border-green-100';
    ?>
    <tr class="hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4">
            <div class="flex flex-col">
                <span class="text-sm font-bold text-gray-800"><?php echo $row['client_name']; ?></span>
                <span class="text-[11px] text-gray-400 font-medium">By: <?php echo $row['creator_name']; ?></span>
            </div>
        </td>
        <td class="px-6 py-4">
            <div class="flex flex-col">
                <span class="text-xs font-semibold text-gray-700"><?php echo $row['software_name']; ?></span>
                <code class="text-[10px] text-blue-500 font-mono mt-0.5"><?php echo substr($row['license_key'], 0, 15); ?>...</code>
            </div>
        </td>
        <td class="px-6 py-4">
            <span class="px-2.5 py-1 border <?php echo $status_class; ?> text-[10px] rounded-full font-bold uppercase inline-flex items-center gap-1.5">
                <?php if(!$is_expired): ?>
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                <?php endif; ?>
                <?php echo $status_text; ?>
            </span>
        </td>
        <td class="px-6 py-4 text-right">
            <a href="licenses" class="text-gray-400 hover:text-green-600 transition-colors p-2">
                <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </td>
    </tr>
    <?php endwhile; else: ?>
    <tr>
        <td colspan="4" class="px-6 py-12 text-center">
            <div class="flex flex-col items-center gap-2">
                <i class="fas fa-folder-open text-gray-200 text-3xl"></i>
                <p class="text-sm text-gray-400 italic">No recent licenses found.</p>
            </div>
        </td>
    </tr>
    <?php endif; ?>
</tbody>
            </table>

        </div>

        <div class="p-5 border-t border-gray-50 flex items-center justify-between">
            <p class="text-xs text-gray-500">Showing 0 to 0 of 0 entries</p>
            <div class="flex items-center gap-2">
                <button class="px-3 py-1.5 border border-gray-200 text-xs rounded-lg hover:bg-gray-50 transition-all text-gray-600">Previous</button>
                <button class="px-3 py-1.5 bg-<?= get_config('theme_color'); ?>-100 text-<?= get_config('theme_color'); ?>-700 text-xs font-bold rounded-lg border border-<?= get_config('theme_color'); ?>-200">1</button>
                <button class="px-3 py-1.5 border border-gray-200 text-xs rounded-lg hover:bg-gray-50 transition-all text-gray-600">Next</button>
            </div>
        </div>

    </div>
</main>







</div>





<?php  include 'sections/footer.php'; ?>