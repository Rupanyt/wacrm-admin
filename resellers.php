<?php
include 'include/config.php';

// Auth & Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'reseller') {
    header("Location: dashboard.php"); 
    exit();
}
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$name = $_SESSION['name'];

$title = "Resellers Management";

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Query Logic
if ($role == 'super_admin') {
    $count_sql = "SELECT COUNT(*) FROM users WHERE role = 'reseller'";
    $base_sql  = "SELECT r.*, p.username as parent_name FROM users r 
                  LEFT JOIN users p ON r.parent_id = p.id 
                  WHERE r.role = 'reseller'";
} else {
 
    $count_sql = "SELECT COUNT(*) FROM users WHERE role = 'reseller' AND parent_id = '$user_id'";
    $base_sql  = "SELECT r.*, '$username' as parent_name FROM users r 
                  WHERE r.role = 'reseller' AND r.parent_id = '$user_id'";
}

$total_results = $conn->query($count_sql)->fetch_row()[0];
$total_pages = ceil($total_results / $limit);

$final_query = $base_sql . " ORDER BY r.id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($final_query);






if ($role == 'super_admin') {
   
    $res_total = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'reseller'")->fetch_row()[0];
  
    $res_active = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'reseller' AND status = 'active'")->fetch_row()[0];
} else {
    
    $res_total = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'reseller' AND parent_id = '$user_id'")->fetch_row()[0];
 
    $res_active = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'reseller' AND parent_id = '$user_id' AND status = 'active'")->fetch_row()[0];
}


$res_blocked = $res_total - $res_active;




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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body { 
            background-color: #f8fafc; 
            font-family: 'Inter', sans-serif; 
            overflow: hidden;
        }

        /* Sidebar Nav Item Styling */
        .nav-item {
            white-space: nowrap;
            overflow: hidden;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        /* Active State Style */
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
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Reseller Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage resellers, their contact info, and account status.</p>
        </div>
        <button onclick="openModal('Add New Reseller', 'reseller_modal')" 
                class="px-5 py-2.5 bg-<?= get_config('theme_color'); ?>-300 hover:bg-<?= get_config('theme_color'); ?>-400 text-<?= get_config('theme_color'); ?>-900 font-bold text-sm rounded-xl transition-all shadow-sm flex items-center gap-2">
            <i class="fas fa-user-plus"></i> Add Reseller
        </button>
    </div>



<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    
    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-12 h-12 bg-indigo-50 text-indigo-500 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Resellers</p>
            <h3 class="text-2xl font-black text-gray-800"><?php echo $res_total; ?></h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-12 h-12 bg-green-50 text-green-500 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-user-check"></i>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Active</p>
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            </div>
            <h3 class="text-2xl font-black text-gray-800"><?php echo $res_active; ?></h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-12 h-12 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-user-slash"></i>
        </div>
        <div>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Blocked / Disabled</p>
            <h3 class="text-2xl font-black text-gray-800"><?php echo $res_blocked; ?></h3>
        </div>
    </div>

</div>



    <div class="bg-white border border-gray-100 shadow-sm rounded-xl overflow-hidden">
        
        <div class="p-5 border-b border-gray-50 bg-gray-50/30">
            <div class="relative w-full md:w-72">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="resellerSearch" onkeyup="filterTable()" placeholder="Search reseller name or mobile..." 
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:border-<?= get_config('theme_color'); ?>-300 outline-none transition-all">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="resellerTable">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Reseller Detail</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Contact Info</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Parent / Admin</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-center">Status</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                    <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
                        // Status Styling Logic
                        $is_active = ($row['status'] == 'active');
                        $s_bg = $is_active ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600';
                        $dot = $is_active ? 'bg-green-500 animate-pulse' : 'bg-red-500';
                        $display_text = $is_active ? 'Active' : 'Disabled';
                    ?>
                    <tr class="hover:bg-<?= get_config('theme_color'); ?>-50/30 transition-all group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-<?= get_config('theme_color'); ?>-50 text-<?= get_config('theme_color'); ?>-600 flex items-center justify-center font-bold border border-<?= get_config('theme_color'); ?>-100 shadow-sm">
                                    <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800 leading-none mb-1"><?php echo $row['name']; ?></div>
                                    <div class="text-[11px] text-gray-400 font-medium tracking-tight">@<?php echo $row['username']; ?></div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                <span class="text-[12px] font-semibold text-gray-700">
                                    <i class="fas fa-phone-alt mr-1.5 text-gray-300"></i><?php echo $row['mobile']; ?>
                                </span>
                                <span class="text-[11px] text-gray-400 font-medium">
                                    <i class="fas fa-envelope mr-1.5 text-gray-300"></i><?php echo $row['email']; ?>
                                </span>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <span class="text-[11px] font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded-md uppercase tracking-tighter">
                                <i class="fas fa-user-tie mr-1 opacity-50"></i> <?php echo $row['parent_name'] ?: 'System'; ?>
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <?php 
                           
                                $theme = get_config("theme_color"); 
                                
              
                                $hover_border = $is_active ? "hover:border-{$theme}-200" : "hover:border-red-200";
                            ?>
                            <span onclick="toggleUserStatus(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>')" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 <?php echo $s_bg; ?> <?php echo $hover_border; ?> text-[10px] font-black rounded-full uppercase cursor-pointer hover:shadow-md transition-all border border-transparent">
                                
                                <span class="w-1.5 h-1.5 rounded-full <?php echo $dot; ?>"></span>
                                <span id="status-text-<?php echo $row['id']; ?>"><?php echo $display_text; ?></span>
                            </span>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-1">
                                <button onclick="openModal('Edit Reseller', 'edit_reseller_modal', {id: <?php echo $row['id']; ?>})" 
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition-all" title="Edit Profile">
                                    <i class="fas fa-user-edit text-xs"></i>
                                </button>
                                <button onclick="openModal('Confirm Delete', 'delete_reseller_confirm', {id: <?php echo $row['id']; ?>})" 
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-600 transition-all" title="Delete User">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center text-gray-300">
                                    <i class="fas fa-users-slash text-xl"></i>
                                </div>
                                <p class="text-gray-400 italic text-sm font-medium">No resellers found in your network.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        


        <div class="p-6 border-t border-gray-50 flex flex-col md:flex-row items-center justify-between gap-4 bg-white rounded-b-xl">
            <p class="text-[12px] font-medium text-gray-500">
                Showing <span class="text-gray-800"><?php echo ($offset + 1); ?></span> to 
                <span class="text-gray-800"><?php echo min($offset + $limit, $total_results); ?></span> of 
                <span class="text-gray-800"><?php echo $total_results; ?></span> licenses
            </p>

            <div class="flex items-center gap-1">
                <a href="?page=<?php echo ($page - 1); ?>" 
                class="<?php echo ($page <= 1) ? 'pointer-events-none opacity-50' : ''; ?> w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-400 hover:bg-gray-50 transition-all">
                    <i class="fas fa-chevron-left text-[10px]"></i>
                </a>

                <?php
        
                $range = 2; 
                
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
                        $activeClass = ($i == $page) ? 'bg-'.get_config('theme_color').'-100 text-'.get_config('theme_color').'-700 border-'.get_config('theme_color').'-200 font-bold' : 'border-gray-200 text-gray-600 hover:bg-gray-50';
                        echo '<a href="?page='.$i.'" class="w-8 h-8 flex items-center justify-center border '.$activeClass.' rounded-lg text-xs transition-all">'.$i.'</a>';
                    } 
                    elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
                        echo '<span class="px-2 text-gray-400 text-xs">...</span>';
                    }
                }
                ?>

                <a href="?page=<?php echo ($page + 1); ?>" 
                class="<?php echo ($page >= $total_pages) ? 'pointer-events-none opacity-50' : ''; ?> w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-400 hover:bg-gray-50 transition-all">
                    <i class="fas fa-chevron-right text-[10px]"></i>
                </a>
            </div>
        </div>











    </div>
</main>



</div>





<script>


function toggleUserStatus(id, currentStatus) {
    const statusText = document.getElementById('status-text-' + id);
    const originalText = statusText.innerText;
    statusText.innerText = "Updating...";

    $.ajax({
        url: 'api/reseller_api.php', 
        type: 'POST',
        data: { 
            action: 'toggle_user_status', 
            id: id, 
            status: currentStatus 
        },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                showToast(res.message, 'success');
          
                setTimeout(function() { 
                    window.location.href = window.location.pathname + window.location.search; 
                }, 1500);
            } else {
                showToast(res.message, 'error');
                statusText.innerText = originalText;
                setTimeout(function() { location.reload(true); }, 1500); 
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error: " + error);
            showToast("Server error occurred", "error");
            statusText.innerText = originalText;
        }
    });
}


</script>


<?php  

    include 'sections/common_modal.php';
    include 'sections/footer.php'; 


?>