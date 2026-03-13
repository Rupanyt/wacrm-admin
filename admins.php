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




$title = "Admins Management";

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) FROM users WHERE role = 'admin'";
$total_results = $conn->query($count_sql)->fetch_row()[0];
$total_pages = ceil($total_results / $limit);

$query = "SELECT * FROM users WHERE role = 'admin' ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);





$adm_total = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetch_row()[0];

$adm_active = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'")->fetch_row()[0];

$adm_blocked = $adm_total - $adm_active;







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
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Admin Management</h1>
            <p class="text-sm text-gray-500 mt-1">Create and manage top-level administrators.</p>
        </div>
        <button onclick="openModal('Add New Admin', 'admin_modal')" 
                class="px-5 py-2.5 bg-<?= get_config('theme_color'); ?>-300 hover:bg-<?= get_config('theme_color'); ?>-400 text-<?= get_config('theme_color'); ?>-900 font-bold text-sm rounded-xl transition-all shadow-sm flex items-center gap-2">
            <i class="fas fa-user-shield"></i> Add Admin
        </button>
    </div>


<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    
    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-user-shield"></i>
        </div>
        <div>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Admins</p>
            <h3 class="text-2xl font-black text-gray-800"><?php echo $adm_total; ?></h3>
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
            <h3 class="text-2xl font-black text-gray-800"><?php echo $adm_active; ?></h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-12 h-12 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-user-lock"></i>
        </div>
        <div>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Disabled</p>
            <h3 class="text-2xl font-black text-gray-800"><?php echo $adm_blocked; ?></h3>
        </div>
    </div>

</div>


    <div class="bg-white border border-gray-100 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Admin Details</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Contact Info</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-center">Account Status</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                    <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
                        $is_active = ($row['status'] == 'active');
                        $s_bg = $is_active ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600';
                        $dot = $is_active ? 'bg-green-500 animate-pulse' : 'bg-red-500';
                    ?>
                    <tr class="hover:bg-green-50/30 transition-all group">
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
                                <span class="text-[12px] font-semibold text-gray-700"><i class="fas fa-phone-alt mr-1.5 text-gray-300"></i><?php echo $row['mobile']; ?></span>
                                <span class="text-[11px] text-gray-400 font-medium"><i class="fas fa-envelope mr-1.5 text-gray-300"></i><?php echo $row['email']; ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span onclick="toggleAdminStatus(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>')" 
                                  class="inline-flex items-center gap-1.5 px-3 py-1.5 <?php echo $s_bg; ?> text-[10px] font-black rounded-full uppercase cursor-pointer hover:shadow-md transition-all border border-transparent hover:border-current">
                                <span class="w-1.5 h-1.5 rounded-full <?php echo $dot; ?>"></span>
                                <span id="status-text-<?php echo $row['id']; ?>"><?php echo $is_active ? 'Active' : 'Disabled'; ?></span>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-1">
                                <button onclick="openModal('Edit Admin', 'edit_admin_modal', {id: <?php echo $row['id']; ?>})" 
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition-all">
                                    <i class="fas fa-pencil-alt text-xs"></i>
                                </button>
                                <button onclick="openModal('Delete Admin', 'delete_admin_confirm', {id: <?php echo $row['id']; ?>})" 
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-600 transition-all">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" class="px-6 py-20 text-center text-gray-400 italic">No Admins found.</td></tr>
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

function toggleAdminStatus(id, currentStatus) {
    const statusText = document.getElementById('status-text-' + id);
    statusText.innerText = "Wait...";

    $.ajax({
        url: 'api/admin_api',
        type: 'POST',
        data: { 
            action: 'toggle_admin_status', 
            id: id, 
            status: currentStatus 
        },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(() => { location.reload(); }, 1500);
            } else {
                showToast(res.message, 'error');
                location.reload();
            }
        }
    });
}

</script>


<?php  

    include 'sections/common_modal.php';
    include 'sections/footer.php'; 


?>