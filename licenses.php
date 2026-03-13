<?php
include 'include/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];
$name = $_SESSION['name'];

$title = "License Management";



$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

if ($role == 'super_admin') {
    $count_sql = "SELECT COUNT(*) FROM licenses";
    $base_sql  = "SELECT l.*, u.username AS creator FROM licenses l 
                  JOIN users u ON l.created_by = u.id";
} elseif ($role == 'admin') {
    $count_sql = "SELECT COUNT(*) FROM licenses WHERE created_by = '$user_id' 
                  OR created_by IN (SELECT id FROM users WHERE parent_id = '$user_id')";
    $base_sql  = "SELECT l.*, u.username AS creator FROM licenses l 
                  JOIN users u ON l.created_by = u.id 
                  WHERE l.created_by = '$user_id' 
                  OR l.created_by IN (SELECT id FROM users WHERE parent_id = '$user_id')";
} else {
    $count_sql = "SELECT COUNT(*) FROM licenses WHERE created_by = '$user_id'";
    $base_sql  = "SELECT l.*, u.username AS creator FROM licenses l 
                  JOIN users u ON l.created_by = u.id 
                  WHERE l.created_by = '$user_id'";
}

$total_results = $conn->query($count_sql)->fetch_row()[0];
$total_pages = ceil($total_results / $limit);

$final_query = $base_sql . " ORDER BY l.id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($final_query);






$today_date = date('Y-m-d');

if ($role == 'super_admin') {
    $c_where = "1=1";
} elseif ($role == 'admin') {
    $c_where = "(created_by = '$user_id' OR created_by IN (SELECT id FROM users WHERE parent_id = '$user_id'))";
} else {
    $c_where = "created_by = '$user_id'";
}

$stat_total = $conn->query("SELECT COUNT(*) FROM licenses WHERE $c_where")->fetch_row()[0];

$stat_active = $conn->query("SELECT COUNT(*) FROM licenses WHERE $c_where AND status = 'active' AND expiry_date >= '$today_date'")->fetch_row()[0];


$stat_disabled = $stat_total - $stat_active;










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
            <h1 class="text-xl font-bold text-gray-800">Software Licenses</h1>
            <p class="text-sm text-gray-500 mt-1">Manage and track all generated tool licenses.</p>
        </div>
        <button onclick="openModal('Generate New License', 'license_modal')" 
                class="px-5 py-2.5 bg-<?= get_config('theme_color'); ?>-300 hover:bg-<?= get_config('theme_color'); ?>-400 text-<?= get_config('theme_color'); ?>-900 font-bold text-sm rounded-xl transition-all shadow-sm">
            <i class="fas fa-plus mr-2"></i> Generate License
        </button>
    </div>


<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    
    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-key"></i>
        </div>
        <div>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Licenses</p>
            <h3 class="text-2xl font-black text-gray-800"><?php echo $stat_total; ?></h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-12 h-12 bg-green-50 text-green-500 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-check-circle"></i>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Active Now</p>
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            </div>
            <h3 class="text-2xl font-black text-gray-800"><?php echo $stat_active; ?></h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-12 h-12 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Expired / Disabled</p>
            <h3 class="text-2xl font-black text-gray-800"><?php echo $stat_disabled; ?></h3>
        </div>
    </div>

</div>






    <div class="bg-white border border-gray-100 shadow-sm rounded-xl overflow-hidden">
    
        
        <div class="p-5 border-b border-gray-50 bg-gray-50/30">
            <div class="relative w-full md:w-72">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="licenseSearch" onkeyup="filterTable()" placeholder="Search client, key or tool..." 
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:border-<?= get_config('theme_color'); ?>-300 outline-none transition-all">
            </div>
        </div>

        <div class="overflow-x-auto">
            
     

<table class="w-full text-left border-collapse" id="mainLicenseTable">
    <thead>
        <tr class="bg-gray-50/80 border-b border-gray-100">
            <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Client & Software</th>
            <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">License Key</th>
            <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Generator</th>
            <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Status</th>
            <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-right">Actions</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-50 text-sm">
        <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
            

            $today = date('Y-m-d');
            $expiry_date = $row['expiry_date'];
            $current_db_status = $row['status'];

           
            if ($current_db_status == 'blocked') {
                $display_status = 'Disabled';
                $s_config = ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'dot' => 'bg-red-400', 'pulse' => ''];
            } elseif ($today > $expiry_date) {
                $display_status = 'Expired';
                $s_config = ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'dot' => 'bg-red-500', 'pulse' => ''];
            } else {
                $display_status = 'Active';
                $s_config = ['bg' => 'bg-green-50', 'text' => 'text-green-600', 'dot' => 'bg-green-500', 'pulse' => 'animate-pulse'];
            }
                        
            
            
            
            
            ?>
        <tr class="hover:bg-<?= get_config('theme_color'); ?>-50/30 transition-all group">
            <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 group-hover:bg-<?= get_config('theme_color'); ?>-100 group-hover:text-<?= get_config('theme_color'); ?>-600 transition-colors">
                        <i class="fas fa-desktop text-xs"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-800 tracking-tight"><?php echo $row['client_name']; ?></div>
                        <div class="text-[11px] text-gray-400 font-medium"><?php echo $row['software_name']; ?></div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    <code class="bg-gray-50 px-3 py-1.5 rounded-md text-[12px] font-mono border border-gray-200 text-gray-600 select-all">
                        <?php echo $row['license_key']; ?>
                    </code>
                    <button onclick="copyToClipboard('<?php echo $row['license_key']; ?>')" 
                            class="text-gray-400 hover:text-<?= get_config('theme_color'); ?>-600 p-1.5 rounded-md hover:bg-<?= get_config('theme_color'); ?>-50 transition-all" 
                            title="Copy Key">
                        <i class="far fa-copy"></i>
                    </button>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-<?= get_config('theme_color'); ?>-50 text-<?= get_config('theme_color'); ?>-600 flex items-center justify-center text-[10px] font-bold">
                        <?php echo strtoupper(substr($row['creator'], 0, 1)); ?>
                    </div>
                    <span class="text-gray-600 font-medium text-xs"><?php echo $row['creator']; ?></span>
                </div>
            </td>
            <td class="px-6 py-4" id="status-container-<?php echo $row['id']; ?>">
                <span onclick="toggleStatus(<?php echo $row['id']; ?>, '<?php echo $current_db_status; ?>')" 
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 <?php echo $s_config['bg']; ?> <?php echo $s_config['text']; ?> text-[10px] font-bold rounded-full uppercase cursor-pointer hover:opacity-80 transition-all shadow-sm border border-transparent hover:border-current">
                    <span class="w-1.5 h-1.5 rounded-full <?php echo $s_config['dot']; ?> <?php echo $s_config['pulse']; ?>"></span>
                    <span id="status-text-<?php echo $row['id']; ?>"><?php echo $display_status; ?></span>
                </span>
            </td>
            <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-1">
                    <button onclick="openModal('Edit License', 'edit_license_modal', {id: <?php echo $row['id']; ?>})" 
                            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition-all">
                        <i class="fas fa-pencil-alt text-xs"></i>
                    </button>
                    <button onclick="openModal('Confirm Delete', 'delete_confirm_modal', {id: <?php echo $row['id']; ?>})" 
                            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-600 transition-all">
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
                        <i class="fas fa-folder-open text-xl"></i>
                    </div>
                    <p class="text-gray-400 italic text-sm">No license records found in database.</p>
                </div>
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>




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
    </div>
</main>





</div>





<script>


function toggleStatus(id, currentStatus) {
    const statusText = document.getElementById('status-text-' + id);
    const originalText = statusText.innerText;
    statusText.innerText = "Updating....";

    $.ajax({
        url: 'api/license_api',
        type: 'POST',
        data: { 
            action: 'toggle_status', 
            id: id, 
            status: currentStatus 
        },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 2000);

            } else {
                showToast(res.message, 'error');
                statusText.innerText = originalText;
            }
        },
        error: function() {
            showToast("Server connection error occurred", "error");
            statusText.innerText = originalText;
        }
    });
}









function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast("License key copied to clipboard!", "success");
    }).catch(err => {
        showToast("Failed to copy key", "error");
    });
}
</script>


<?php  

    include 'sections/common_modal.php';
    include 'sections/footer.php'; 


?>