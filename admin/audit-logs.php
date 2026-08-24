<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || !Session::isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Log this page visit
$database = new Database();
$db = $database->getConnection();

// Filter parameters
$search = $_GET['search'] ?? '';
$actionFilter = $_GET['action'] ?? '';
$userFilter = $_GET['user'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Build query
$where = "WHERE 1=1";
$params = [];

if ($search) { $where .= " AND (user_name LIKE ? OR action LIKE ? OR description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($actionFilter) { $where .= " AND action = ?"; $params[] = $actionFilter; }
if ($userFilter) { $where .= " AND user_name LIKE ?"; $params[] = "%$userFilter%"; }
$where .= " AND DATE(created_at) BETWEEN ? AND ?"; $params[] = $startDate; $params[] = $endDate;

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs $where");
$countStmt->execute($params);
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);

// Fetch logs
$logsStmt = $db->prepare("SELECT * FROM audit_logs $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$logsStmt->execute($params);
$logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique actions for filter
$actions = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

// Stats
$todayLogs = $db->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$weekLogs = $db->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{50:'#eff6ff',600:'#2563eb',700:'#1d4ed8'}},fontFamily:{poppins:['Poppins','sans-serif'],inter:['Inter','sans-serif']}}}}</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--sidebar-width:260px}.sidebar{width:var(--sidebar-width)}.main-content{margin-left:var(--sidebar-width)}
        .sidebar-link{transition:all .3s ease;position:relative}.sidebar-link::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:0;background:#2563eb;border-radius:0 4px 4px 0;transition:height .3s ease}
        .sidebar-link:hover::before,.sidebar-link.active::before{height:60%}.sidebar-link.active{background:rgba(37,99,235,.15);color:#60a5fa}
        .log-row{transition:all .2s ease}.log-row:hover{background:#f8fafc}
        @media(max-width:1024px){.sidebar{width:100%;position:relative;height:auto}.main-content{margin-left:0}}
    </style>
</head>
<body class="font-inter antialiased bg-slate-50">
<div class="flex min-h-screen">
    
    <!-- SIDEBAR -->
    <aside class="sidebar fixed top-0 left-0 bottom-0 bg-slate-900 text-white z-50 flex flex-col overflow-y-auto">
        <div class="p-6 border-b border-slate-700/50">
            <a href="../index.php" class="flex items-center space-x-3 group"><div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-orange-500 rounded-xl flex items-center justify-center text-white shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-105"><i class="fas fa-cubes"></i></div><div><span class="font-poppins font-bold text-lg">E-Find Admin</span><p class="text-xs text-slate-400">Management Panel</p></div></a>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <a href="dashboard.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span></a>
            <a href="services-manage.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-cogs w-5"></i><span>Services</span></a>
            <a href="audit-logs.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-history w-5"></i><span>Audit Logs</span></a>
            <a href="orders.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-shopping-cart w-5"></i><span>Orders</span></a>
            <a href="users.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-users w-5"></i><span>Users</span></a>
            <a href="payments.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-credit-card w-5"></i><span>Payments</span></a>
            <a href="messages.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-envelope w-5"></i><span>Messages</span></a>
            <a href="reports.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-file-alt w-5"></i><span>Reports</span></a>
            <a href="services-manage.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-cogs w-5"></i><span>Services</span></a>
            <a href="audit-logs.php" class="sidebar-link active flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-300"><i class="fas fa-history w-5"></i><span>Audit Logs</span></a>
            <a href="undelivered.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-exclamation-triangle w-5"></i><span>Undelivered</span></a>
            <hr class="border-slate-700/50 my-4">
            <a href="../index.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-home w-5"></i><span>Back to Site</span></a>
            <a href="../logout.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-red-400 hover:text-red-300 hover:bg-red-500/10"><i class="fas fa-sign-out-alt w-5"></i><span>Logout</span></a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content flex-1 p-6 lg:p-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
            <div><h1 class="text-3xl font-poppins font-extrabold text-slate-900"><span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Audit Logs</span></h1><p class="text-slate-500 mt-1">System activity monitoring & accountability</p></div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 shadow-sm border text-center"><p class="text-2xl font-bold text-blue-600"><?php echo number_format($totalLogs);?></p><p class="text-xs text-slate-500">Total Logs</p></div>
            <div class="bg-white rounded-xl p-4 shadow-sm border text-center"><p class="text-2xl font-bold text-green-600"><?php echo number_format($todayLogs);?></p><p class="text-xs text-slate-500">Today</p></div>
            <div class="bg-white rounded-xl p-4 shadow-sm border text-center"><p class="text-2xl font-bold text-purple-600"><?php echo number_format($weekLogs);?></p><p class="text-xs text-slate-500">This Week</p></div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6 shadow-sm">
            <form method="GET" class="grid grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">Search</label><input type="text" name="search" value="<?php echo htmlspecialchars($search);?>" placeholder="Search logs..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">Action</label><select name="action" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none"><option value="">All Actions</option><?php foreach($actions as $a):?><option value="<?php echo htmlspecialchars($a);?>" <?php echo $actionFilter===$a?'selected':'';?>><?php echo ucwords(str_replace('_',' ',$a));?></option><?php endforeach;?></select></div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">User</label><input type="text" name="user" value="<?php echo htmlspecialchars($userFilter);?>" placeholder="Filter by user..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">Start</label><input type="date" name="start_date" value="<?php echo $startDate;?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">End</label><input type="date" name="end_date" value="<?php echo $endDate;?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none"></div>
                <div class="flex gap-2"><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700"><i class="fas fa-filter mr-1"></i>Filter</button><a href="audit-logs.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300">Clear</a></div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead><tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase"><th class="px-5 py-3">Date/Time</th><th class="px-5 py-3">User</th><th class="px-5 py-3">Role</th><th class="px-5 py-3">Action</th><th class="px-5 py-3">Description</th><th class="px-5 py-3">IP Address</th></tr></thead>
                    <tbody class="divide-y">
                        <?php if(empty($logs)):?>
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400"><i class="fas fa-history text-4xl mb-3 block"></i>No audit logs found.</td></tr>
                        <?php else:?>
                        <?php foreach($logs as $log): 
                            $actionColors = ['login'=>'blue','logout'=>'slate','create'=>'green','update'=>'amber','delete'=>'red','status_change'=>'purple','upload'=>'indigo'];
                            $ac = $actionColors[$log['action']] ?? 'slate';
                        ?>
                        <tr class="log-row">
                            <td class="px-5 py-4 text-sm text-slate-600 whitespace-nowrap"><?php echo date('M d, Y H:i:s', strtotime($log['created_at']));?></td>
                            <td class="px-5 py-4 text-sm font-medium"><?php echo htmlspecialchars($log['user_name'] ?: 'System');?></td>
                            <td class="px-5 py-4"><span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-xs"><?php echo ucfirst($log['user_role'] ?: 'N/A');?></span></td>
                            <td class="px-5 py-4"><span class="px-2.5 py-1 bg-<?php echo $ac;?>-100 text-<?php echo $ac;?>-700 rounded-full text-xs font-semibold whitespace-nowrap"><?php echo ucwords(str_replace('_',' ',$log['action']));?></span></td>
                            <td class="px-5 py-4 text-sm text-slate-600 max-w-[300px]"><?php echo htmlspecialchars($log['description']);?></td>
                            <td class="px-5 py-4 text-xs text-slate-400 font-mono"><?php echo htmlspecialchars($log['ip_address']);?></td>
                        </tr>
                        <?php endforeach;?>
                        <?php endif;?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <?php if($totalPages > 1):?>
            <div class="px-6 py-3 bg-slate-50 border-t flex justify-between items-center">
                <span class="text-xs text-slate-500">Page <?php echo $page;?> of <?php echo $totalPages;?> (<?php echo $totalLogs;?> total)</span>
                <div class="flex gap-1">
                    <?php if($page > 1):?><a href="?page=<?php echo $page-1;?>&search=<?php echo urlencode($search);?>&action=<?php echo urlencode($actionFilter);?>&user=<?php echo urlencode($userFilter);?>&start_date=<?php echo $startDate;?>&end_date=<?php echo $endDate;?>" class="px-3 py-1 border rounded text-sm hover:bg-gray-100">← Prev</a><?php endif;?>
                    <?php if($page < $totalPages):?><a href="?page=<?php echo $page+1;?>&search=<?php echo urlencode($search);?>&action=<?php echo urlencode($actionFilter);?>&user=<?php echo urlencode($userFilter);?>&start_date=<?php echo $startDate;?>&end_date=<?php echo $endDate;?>" class="px-3 py-1 border rounded text-sm hover:bg-gray-100">Next →</a><?php endif;?>
                </div>
            </div>
            <?php endif;?>
        </div>
    </main>
</div>
</body>
</html>