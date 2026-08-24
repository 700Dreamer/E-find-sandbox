<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || !Session::isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date']   ?? date('Y-m-d');

// Core Stats
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn() ?: 0;
$unreadMessages = $db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn() ?: 0;

// Filtered Stats
$filteredStats = $db->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount),0) as total_revenue, SUM(CASE WHEN status IN ('completed','delivered') THEN 1 ELSE 0 END) as completed_orders, SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled_orders, COALESCE(SUM(CASE WHEN status='cancelled' THEN total_amount ELSE 0 END),0) as lost_revenue FROM orders WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)");
$filteredStats->execute([$startDate, $endDate]);
$fStats = $filteredStats->fetch(PDO::FETCH_ASSOC);
$completionRate = $fStats['total_orders'] > 0 ? round(($fStats['completed_orders'] / $fStats['total_orders']) * 100) : 0;

// Daily Performance
$dailyStmt = $db->prepare("SELECT DATE(created_at) AS order_date, COUNT(*) AS orders, SUM(CASE WHEN status IN ('completed','delivered') THEN 1 ELSE 0 END) AS completed, SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled, COALESCE(SUM(total_amount),0) AS revenue FROM orders WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) GROUP BY DATE(created_at) ORDER BY order_date ASC");
$dailyStmt->execute([$startDate, $endDate]);
$daily = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);

// Top Services
$topServices = $db->prepare("SELECT s.name, COUNT(*) as count, COALESCE(SUM(o.total_amount),0) as revenue FROM orders o JOIN services s ON o.service_id = s.id WHERE o.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) GROUP BY s.id ORDER BY count DESC LIMIT 5");
$topServices->execute([$startDate, $endDate]);
$topServices = $topServices->fetchAll(PDO::FETCH_ASSOC);

// Rider Performance
$riderPerformance = $db->prepare("SELECT u.name, COUNT(d.id) as total_deliveries, SUM(CASE WHEN d.status='delivered' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN d.status='undelivered' THEN 1 ELSE 0 END) as failed, AVG(CASE WHEN d.rating>0 THEN d.rating END) as avg_rating FROM deliveries d JOIN users u ON d.delivery_person_id = u.id WHERE d.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) GROUP BY u.id, u.name ORDER BY completed DESC");
$riderPerformance->execute([$startDate, $endDate]);
$riders = $riderPerformance->fetchAll(PDO::FETCH_ASSOC);

// Undelivered Reasons
$undeliveredReasons = $db->prepare("SELECT d.delivery_notes as reason, COUNT(*) as count, COALESCE(SUM(o.total_amount),0) as total_loss FROM deliveries d JOIN orders o ON d.order_id = o.id WHERE d.status='undelivered' AND d.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) GROUP BY d.delivery_notes ORDER BY count DESC");
$undeliveredReasons->execute([$startDate, $endDate]);
$reasons = $undeliveredReasons->fetchAll(PDO::FETCH_ASSOC);

// Service Trends
$serviceTrend = $db->query("SELECT s.name, COUNT(CASE WHEN o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as last_30_days, COUNT(CASE WHEN o.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as prev_30_days FROM orders o JOIN services s ON o.service_id = s.id WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY) GROUP BY s.id, s.name ORDER BY last_30_days DESC");
$serviceTrends = $serviceTrend->fetchAll(PDO::FETCH_ASSOC);

// Yearly Sales
$currentYear = date('Y');
$monthlySales = $db->prepare("SELECT MONTH(created_at) as month, COUNT(*) as orders, COALESCE(SUM(total_amount),0) as revenue FROM orders WHERE YEAR(created_at)=? GROUP BY MONTH(created_at) ORDER BY month ASC");
$monthlySales->execute([$currentYear]);
$monthlyData = $monthlySales->fetchAll(PDO::FETCH_ASSOC);
$monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$monthlyRevenue = array_fill(0, 12, 0);
$monthlyOrders = array_fill(0, 12, 0);
foreach ($monthlyData as $m) { $monthlyRevenue[$m['month']-1] = (float)$m['revenue']; $monthlyOrders[$m['month']-1] = (int)$m['orders']; }
$yearlyTotal = array_sum($monthlyRevenue);
$yearlyOrdersTotal = array_sum($monthlyOrders);

// Location Sales
$locationSales = $db->prepare("SELECT u.city, COUNT(o.id) as orders, COALESCE(SUM(o.total_amount),0) as revenue FROM orders o JOIN users u ON o.user_id = u.id WHERE YEAR(o.created_at)=? AND u.city IS NOT NULL AND u.city != '' GROUP BY u.city ORDER BY orders DESC LIMIT 8");
$locationSales->execute([$currentYear]);
$locationData = $locationSales->fetchAll(PDO::FETCH_ASSOC);

// Recent Orders
$recentOrders = $db->query("SELECT o.*, s.name AS service_name, u.name AS customer_name FROM orders o JOIN services s ON o.service_id = s.id JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

// Prepare chart data as JSON
$chartLabels = []; $chartRevenue = []; $chartOrdersData = [];
foreach ($daily as $d) { $chartLabels[] = date('M d', strtotime($d['order_date'])); $chartRevenue[] = (float)$d['revenue']; $chartOrdersData[] = (int)$d['orders']; }

$riderNames = []; $riderCompleted = []; $riderRatingsArr = [];
foreach ($riders as $r) { $riderNames[] = $r['name']; $riderCompleted[] = (int)$r['completed']; $riderRatingsArr[] = round((float)($r['avg_rating'] ?? 0), 1); }

$reasonLabels = []; $reasonCountsArr = [];
foreach ($reasons as $r) { $reasonLabels[] = !empty($r['reason']) ? (strlen($r['reason']) > 25 ? substr($r['reason'], 0, 25).'...' : $r['reason']) : 'No reason'; $reasonCountsArr[] = (int)$r['count']; }

$locLabels = []; $locOrdersArr = []; $locRevenueArr = [];
foreach ($locationData as $l) { $locLabels[] = $l['city']; $locOrdersArr[] = (int)$l['orders']; $locRevenueArr[] = (float)$l['revenue']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{50:'#eff6ff',600:'#2563eb',700:'#1d4ed8'}},fontFamily:{poppins:['Poppins','sans-serif'],inter:['Inter','sans-serif']}}}}</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root{--sidebar-width:260px}.sidebar{width:var(--sidebar-width)}.main-content{margin-left:var(--sidebar-width)}
        .sidebar-link{transition:all .3s ease;position:relative}.sidebar-link::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:0;background:#2563eb;border-radius:0 4px 4px 0;transition:height .3s ease}
        .sidebar-link:hover::before,.sidebar-link.active::before{height:60%}.sidebar-link.active{background:rgba(37,99,235,.15);color:#60a5fa}
        .stat-card{background:white;border-radius:1.25rem;padding:1.5rem;border:1px solid #f1f5f9;transition:all .4s;position:relative;overflow:hidden}
        .stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#2563eb,#7c3aed,#f97316);background-size:200% 100%;animation:shimmer 3s linear infinite;transform:scaleX(0);transform-origin:left;transition:transform .5s ease}
        .stat-card:hover::before{transform:scaleX(1)}.stat-card:hover{transform:translateY(-6px);box-shadow:0 25px 50px -12px rgba(37,99,235,.15)}
        @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
        .stat-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;transition:all .4s ease}.stat-card:hover .stat-icon{transform:scale(1.1) rotate(-5deg)}
        .table-row{transition:all .25s ease}.table-row:hover{background:linear-gradient(90deg,#eff6ff 0%,transparent 100%);transform:translateX(3px)}
        canvas{max-width:100%}
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
            <a href="dashboard.php" class="sidebar-link active flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-300"><i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span><?php if($pendingOrders>0):?><span class="ml-auto bg-amber-500 text-white text-xs rounded-full px-2 py-0.5"><?php echo $pendingOrders;?></span><?php endif;?></a>
            <a href="orders.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-shopping-cart w-5"></i><span>Orders</span></a>
            <a href="services-manage.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-cogs w-5"></i><span>Services</span></a>
            <a href="audit-logs.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-history w-5"></i><span>Audit Logs</span></a>
            <a href="users.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-users w-5"></i><span>Users</span></a>
            <a href="payments.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-credit-card w-5"></i><span>Payments</span></a>
            <a href="messages.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-envelope w-5"></i><span>Messages</span></a>
            <a href="reports.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-file-alt w-5"></i><span>Reports</span></a>
            <a href="undelivered.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-exclamation-triangle w-5"></i><span>Undelivered</span></a>
            <hr class="border-slate-700/50 my-4">
            <a href="../index.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-home w-5"></i><span>Back to Site</span></a>
            <a href="../logout.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-red-400 hover:text-red-300 hover:bg-red-500/10"><i class="fas fa-sign-out-alt w-5"></i><span>Logout</span></a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content flex-1 p-6 lg:p-8">
        
        <!-- Header -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
            <div><h1 class="text-3xl font-poppins font-extrabold text-slate-900"><span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Dashboard</span></h1><p class="text-slate-500 mt-1"><?php echo date('F d, Y', strtotime($startDate));?> – <?php echo date('F d, Y', strtotime($endDate));?></p></div>
            <form method="GET" class="flex items-center gap-2 bg-white rounded-xl p-2 shadow-sm border">
                <input type="date" name="start_date" value="<?php echo $startDate;?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none">
                <span class="text-slate-400 text-sm">to</span>
                <input type="date" name="end_date" value="<?php echo $endDate;?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700"><i class="fas fa-filter mr-1"></i>Filter</button>
            </form>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="stat-card"><div class="flex justify-between items-start"><div><p class="text-slate-500 text-xs font-semibold uppercase">Total Orders</p><p class="text-3xl font-bold text-slate-900 mt-2"><?php echo number_format($fStats['total_orders']);?></p></div><div class="stat-icon bg-blue-100 text-blue-600"><i class="fas fa-shopping-cart"></i></div></div></div>
            <div class="stat-card"><div class="flex justify-between items-start"><div><p class="text-slate-500 text-xs font-semibold uppercase">Revenue</p><p class="text-3xl font-bold text-slate-900 mt-2">UGX <?php echo number_format($fStats['total_revenue']/1000000,1);?>M</p></div><div class="stat-icon bg-emerald-100 text-emerald-600"><i class="fas fa-dollar-sign"></i></div></div></div>
            <div class="stat-card"><div class="flex justify-between items-start"><div><p class="text-slate-500 text-xs font-semibold uppercase">Completed</p><p class="text-3xl font-bold text-slate-900 mt-2"><?php echo number_format($fStats['completed_orders']);?></p><div class="w-full bg-gray-200 rounded-full h-1.5 mt-2"><div class="bg-green-500 h-1.5 rounded-full" style="width:<?php echo $completionRate;?>%"></div></div></div><div class="stat-icon bg-green-100 text-green-600"><i class="fas fa-check-circle"></i></div></div></div>
            <div class="stat-card"><div class="flex justify-between items-start"><div><p class="text-slate-500 text-xs font-semibold uppercase">Lost Revenue</p><p class="text-3xl font-bold text-red-600 mt-2">UGX <?php echo number_format($fStats['lost_revenue']/1000,0);?>K</p></div><div class="stat-icon bg-red-100 text-red-600"><i class="fas fa-times-circle"></i></div></div></div>
        </div>

        <!-- CHART 1: Revenue & Orders -->
        <div class="grid lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <h3 class="font-poppins font-bold text-slate-900 mb-4 text-sm uppercase">Revenue & Orders Trend</h3>
                <div style="height:280px;"><canvas id="chart_revenue"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <h3 class="font-poppins font-bold text-slate-900 mb-4 text-sm uppercase">Top Services</h3>
                <?php if(!empty($topServices)):?>
                <div class="space-y-4"><?php $colors=['bg-blue-500','bg-purple-500','bg-emerald-500','bg-amber-500','bg-rose-500'];$max=$topServices[0]['count'];foreach($topServices as $i=>$svc):$w=$max>0?round(($svc['count']/$max)*100):0;?>
                <div><div class="flex justify-between text-sm mb-1"><span class="font-medium"><?php echo htmlspecialchars($svc['name']);?></span><span class="text-slate-500"><?php echo $svc['count'];?></span></div><div class="w-full bg-gray-200 rounded-full h-2"><div class="h-2 rounded-full <?php echo $colors[$i];?>" style="width:<?php echo $w;?>%"></div></div><p class="text-xs text-slate-400 mt-1">UGX <?php echo number_format($svc['revenue']);?></p></div>
                <?php endforeach;?></div><?php else:?><p class="text-slate-400 text-sm text-center py-8">No data.</p><?php endif;?>
            </div>
        </div>

        <!-- Daily Breakdown -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b"><h3 class="font-poppins font-bold text-slate-900 text-sm uppercase">Daily Breakdown</h3></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[600px]"><thead><tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase"><th class="px-5 py-3">Date</th><th class="px-5 py-3">Orders</th><th class="px-5 py-3">Completed</th><th class="px-5 py-3">Cancelled</th><th class="px-5 py-3">Revenue</th></tr></thead><tbody class="divide-y"><?php foreach(array_slice(array_reverse($daily),0,10) as $day):?><tr class="table-row"><td class="px-5 py-3 text-sm font-medium"><?php echo date('M d, Y',strtotime($day['order_date']));?></td><td class="px-5 py-3 text-sm font-semibold"><?php echo $day['orders'];?></td><td class="px-5 py-3"><span class="px-2.5 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold"><?php echo $day['completed'];?></span></td><td class="px-5 py-3"><span class="px-2.5 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold"><?php echo $day['cancelled'];?></span></td><td class="px-5 py-3 text-sm font-semibold">UGX <?php echo number_format($day['revenue']);?></td></tr><?php endforeach;?></tbody></table></div>
        </div>

        <!-- CHART 2: Rider Performance -->
        <div class="mb-6"><div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"><div class="px-6 py-4 border-b"><h3 class="font-poppins font-bold text-slate-900 text-sm uppercase"><i class="fas fa-motorcycle mr-2 text-amber-600"></i>Rider Performance</h3></div><div class="grid lg:grid-cols-2 gap-0"><div class="p-6"><div style="height:280px;"><canvas id="chart_rider"></canvas></div></div><div class="p-6 overflow-x-auto"><table class="w-full min-w-[400px] text-sm"><thead><tr class="text-left text-xs font-semibold text-slate-500 uppercase"><th class="pb-2">Rider</th><th class="pb-2 text-center">Total</th><th class="pb-2 text-center">Done</th><th class="pb-2 text-center">Failed</th><th class="pb-2 text-center">Rating</th></tr></thead><tbody class="divide-y"><?php foreach($riders as $r):?><tr class="table-row"><td class="py-2 font-medium"><?php echo htmlspecialchars($r['name']);?></td><td class="py-2 text-center"><?php echo $r['total_deliveries'];?></td><td class="py-2 text-center text-green-600 font-semibold"><?php echo $r['completed'];?></td><td class="py-2 text-center text-red-600 font-semibold"><?php echo $r['failed'];?></td><td class="py-2 text-center"><?php echo $r['avg_rating']?round($r['avg_rating'],1).' ⭐':'—';?></td></tr><?php endforeach;?></tbody></table></div></div></div></div>

        <!-- CHART 3: Reasons + Predictions -->
        <div class="grid lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"><div class="px-6 py-4 border-b"><h3 class="font-poppins font-bold text-slate-900 text-sm uppercase"><i class="fas fa-exclamation-triangle mr-2 text-red-600"></i>Undelivered Reasons</h3></div><div class="p-6"><?php if(!empty($reasons)):?><div style="height:250px;"><canvas id="chart_reasons"></canvas></div><div class="mt-4 space-y-2"><?php foreach($reasons as $r):?><div class="flex justify-between text-sm"><span class="text-slate-600"><?php echo htmlspecialchars($r['reason']?:'No reason');?></span><span class="font-semibold text-red-600"><?php echo $r['count'];?> (UGX <?php echo number_format($r['total_loss']);?>)</span></div><?php endforeach;?></div><?php else:?><p class="text-slate-400 text-sm text-center py-8">No undelivered orders.</p><?php endif;?></div></div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"><div class="px-6 py-4 border-b"><h3 class="font-poppins font-bold text-slate-900 text-sm uppercase"><i class="fas fa-chart-line mr-2 text-blue-600"></i>Demand Predictions</h3></div><div class="p-6"><p class="text-xs text-slate-500 mb-4">90-day trend analysis for inventory planning.</p><div class="space-y-3"><?php foreach($serviceTrends as $st):$trend=$st['prev_30_days']>0?round((($st['last_30_days']-$st['prev_30_days'])/$st['prev_30_days'])*100):($st['last_30_days']>0?100:0);$icon=$trend>10?'fa-arrow-up text-green-500':($trend<-10?'fa-arrow-down text-red-500':'fa-minus text-amber-500');$rec=$trend>20?'Stock Up':($trend<-20?'Reduce':'Maintain');$rc=$trend>20?'bg-green-100 text-green-700':($trend<-20?'bg-red-100 text-red-700':'bg-amber-100 text-amber-700');?><div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl"><div><p class="font-semibold text-sm"><?php echo htmlspecialchars($st['name']);?></p><p class="text-xs text-slate-500">Last 30d: <strong><?php echo $st['last_30_days'];?></strong> orders</p></div><div class="text-right"><p class="font-bold <?php echo $trend>10?'text-green-600':($trend<-10?'text-red-600':'text-amber-600');?>"><i class="fas <?php echo $icon;?> mr-1"></i><?php echo $trend;?>%</p><span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $rc;?>"><?php echo $rec;?></span></div></div><?php endforeach;?></div></div></div>
        </div>

        <!-- CHART 4: Yearly Sales -->
        <div class="mb-6"><div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"><div class="px-6 py-4 border-b bg-gradient-to-r from-blue-50 to-purple-50"><h3 class="font-poppins font-bold text-slate-900 text-sm uppercase"><i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Yearly Sales – <?php echo $currentYear;?></h3></div><div class="p-6">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 rounded-xl p-4 text-center"><p class="text-xs text-blue-600 font-semibold">Year Revenue</p><p class="text-2xl font-bold text-blue-700">UGX <?php echo number_format($yearlyTotal/1000000,2);?>M</p></div>
                <div class="bg-emerald-50 rounded-xl p-4 text-center"><p class="text-xs text-emerald-600 font-semibold">Total Orders</p><p class="text-2xl font-bold text-emerald-700"><?php echo number_format($yearlyOrdersTotal);?></p></div>
                <div class="bg-amber-50 rounded-xl p-4 text-center"><p class="text-xs text-amber-600 font-semibold">Total Users</p><p class="text-2xl font-bold text-amber-700"><?php echo number_format($totalUsers);?></p></div>
                <div class="bg-purple-50 rounded-xl p-4 text-center"><p class="text-xs text-purple-600 font-semibold">Pending</p><p class="text-2xl font-bold text-purple-700"><?php echo $pendingOrders;?></p></div>
            </div>
            <div class="mb-6"><h4 class="font-semibold text-slate-800 mb-3 text-sm">Monthly Sales Trend</h4><div style="height:250px;"><canvas id="chart_yearly"></canvas></div></div>
            <?php if(!empty($locationData)):?>
            <div class="grid lg:grid-cols-2 gap-6">
                <div><h4 class="font-semibold text-slate-800 mb-3 text-sm">Orders by Location</h4><div style="height:280px;"><canvas id="chart_location_orders"></canvas></div></div>
                <div><h4 class="font-semibold text-slate-800 mb-3 text-sm">Revenue by Location</h4><div style="height:280px;"><canvas id="chart_location_revenue"></canvas></div></div>
            </div>
            <?php endif;?>
        </div></div></div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"><div class="px-6 py-4 border-b flex justify-between"><h3 class="font-poppins font-bold text-slate-900 text-sm uppercase">Recent Orders</h3><a href="orders.php" class="text-blue-600 text-sm font-semibold">View All →</a></div><div class="overflow-x-auto"><table class="w-full min-w-[700px]"><thead><tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase"><th class="px-5 py-3">Order #</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Service</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Date</th></tr></thead><tbody class="divide-y"><?php foreach($recentOrders as $o):$colors=['pending'=>'amber','confirmed'=>'blue','processing'=>'indigo','delivered'=>'green','completed'=>'green','cancelled'=>'red'];$c=$colors[$o['status']]??'slate';?><tr class="table-row"><td class="px-5 py-4 font-semibold text-blue-600 text-sm"><?php echo htmlspecialchars($o['order_number']);?></td><td class="px-5 py-4 text-sm"><?php echo htmlspecialchars($o['customer_name']);?></td><td class="px-5 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($o['service_name']);?></td><td class="px-5 py-4 text-sm font-semibold">UGX <?php echo number_format($o['total_amount']);?></td><td class="px-5 py-4"><span class="px-2.5 py-1 bg-<?php echo $c;?>-100 text-<?php echo $c;?>-700 rounded-full text-xs font-semibold"><?php echo ucfirst(str_replace('_',' ',$o['status']));?></span></td><td class="px-5 py-4 text-sm text-slate-500"><?php echo date('M d',strtotime($o['created_at']));?></td></tr><?php endforeach;?></tbody></table></div></div>
    </main>
</div>

<script>
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // CHART 1: Revenue & Orders
    var ctx1 = document.getElementById('chart_revenue');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [
                    {
                        label: 'Revenue (UGX)',
                        data: <?php echo json_encode($chartRevenue); ?>,
                        backgroundColor: 'rgba(37, 99, 235, 0.6)',
                        borderColor: '#2563eb',
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: <?php echo json_encode($chartOrdersData); ?>,
                        type: 'line',
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#f97316',
                        tension: 0.3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } },
                scales: {
                    y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Revenue (UGX)' }, ticks: { callback: function(v) { return 'UGX ' + v.toLocaleString(); } } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Orders' }, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // CHART 2: Rider Performance
    var ctx2 = document.getElementById('chart_rider');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($riderNames); ?>,
                datasets: [
                    {
                        label: 'Completed Deliveries',
                        data: <?php echo json_encode($riderCompleted); ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.6)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Rating (1-5)',
                        data: <?php echo json_encode($riderRatingsArr); ?>,
                        type: 'line',
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 2,
                        pointRadius: 5,
                        pointBackgroundColor: '#f59e0b',
                        tension: 0.3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } },
                scales: {
                    y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Deliveries' }, ticks: { stepSize: 1 } },
                    y1: { beginAtZero: true, position: 'right', max: 5, grid: { drawOnChartArea: false }, title: { display: true, text: 'Rating' } }
                }
            }
        });
    }

    // CHART 3: Undelivered Reasons
    var ctx3 = document.getElementById('chart_reasons');
    if (ctx3 && <?php echo json_encode(!empty($reasons)); ?>) {
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($reasonLabels); ?>,
                datasets: [{
                    label: 'Count',
                    data: <?php echo json_encode($reasonCountsArr); ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.6)',
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // CHART 4: Yearly Sales
    var ctx4 = document.getElementById('chart_yearly');
    if (ctx4) {
        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($monthLabels); ?>,
                datasets: [
                    {
                        label: 'Revenue (UGX)',
                        data: <?php echo json_encode(array_values($monthlyRevenue)); ?>,
                        backgroundColor: 'rgba(37, 99, 235, 0.6)',
                        borderColor: '#2563eb',
                        borderWidth: 1,
                        borderRadius: 8,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: <?php echo json_encode(array_values($monthlyOrders)); ?>,
                        type: 'line',
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        borderWidth: 2,
                        pointRadius: 5,
                        pointBackgroundColor: '#f97316',
                        tension: 0.3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } },
                scales: {
                    y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Revenue (UGX)' }, ticks: { callback: function(v) { return 'UGX ' + v.toLocaleString(); } } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Orders' }, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // CHART 5: Location Orders
    var ctx5 = document.getElementById('chart_location_orders');
    if (ctx5 && <?php echo json_encode(!empty($locationData)); ?>) {
        new Chart(ctx5, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($locLabels); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode($locOrdersArr); ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // CHART 6: Location Revenue
    var ctx6 = document.getElementById('chart_location_revenue');
    if (ctx6 && <?php echo json_encode(!empty($locationData)); ?>) {
        new Chart(ctx6, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($locLabels); ?>,
                datasets: [{
                    label: 'Revenue (UGX)',
                    data: <?php echo json_encode($locRevenueArr); ?>,
                    backgroundColor: 'rgba(124, 58, 237, 0.6)',
                    borderColor: '#7c3aed',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { callback: function(v) { return 'UGX ' + v.toLocaleString(); } } } }
            }
        });
    }

});
</script>
</body>
</html>