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

// Filters
$reportType = $_GET['type'] ?? 'orders';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$statusFilter = $_GET['status'] ?? '';
$serviceFilter = $_GET['service'] ?? '';
$riderFilter = $_GET['rider'] ?? '';
$export = $_GET['export'] ?? '';

// Fetch filter options
$services = $db->query("SELECT id, name FROM services ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$riders = $db->query("SELECT id, name FROM users WHERE role = 'delivery' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Build report data based on type
$reportData = [];
$reportTitle = '';

switch ($reportType) {
    case 'financial':
        $reportTitle = 'Financial Report';
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as orders, COALESCE(SUM(total_amount),0) as revenue, SUM(CASE WHEN status='cancelled' THEN total_amount ELSE 0 END) as lost FROM orders WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
        $params = [$startDate, $endDate];
        if ($statusFilter) { $sql .= " AND status = ?"; $params[] = $statusFilter; }
        $sql .= " GROUP BY DATE(created_at) ORDER BY date DESC";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'orders':
        $reportTitle = 'Order Report';
        $sql = "SELECT o.*, s.name as service_name, u.name as customer_name, p.status as payment_status FROM orders o JOIN services s ON o.service_id = s.id JOIN users u ON o.user_id = u.id LEFT JOIN payments p ON o.id = p.order_id WHERE o.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
        $params = [$startDate, $endDate];
        if ($statusFilter) { $sql .= " AND o.status = ?"; $params[] = $statusFilter; }
        if ($serviceFilter) { $sql .= " AND o.service_id = ?"; $params[] = $serviceFilter; }
        $sql .= " ORDER BY o.created_at DESC";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'sales':
        $reportTitle = 'Sales Report';
        $sql = "SELECT s.name as service_name, COUNT(o.id) as total_orders, COALESCE(SUM(o.total_amount),0) as total_revenue, AVG(o.total_amount) as avg_order FROM orders o JOIN services s ON o.service_id = s.id WHERE o.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
        $params = [$startDate, $endDate];
        if ($serviceFilter) { $sql .= " AND o.service_id = ?"; $params[] = $serviceFilter; }
        $sql .= " GROUP BY s.id ORDER BY total_orders DESC";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'performance':
        $reportTitle = 'Rider Performance Report';
        $sql = "SELECT u.name, COUNT(d.id) as deliveries, SUM(CASE WHEN d.status='delivered' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN d.status='undelivered' THEN 1 ELSE 0 END) as failed, AVG(CASE WHEN d.rating>0 THEN d.rating END) as rating FROM deliveries d JOIN users u ON d.delivery_person_id = u.id WHERE d.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
        $params = [$startDate, $endDate];
        if ($riderFilter) { $sql .= " AND d.delivery_person_id = ?"; $params[] = $riderFilter; }
        $sql .= " GROUP BY u.id ORDER BY completed DESC";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'delivery':
        $reportTitle = 'Delivery Report';
        $sql = "SELECT d.*, o.order_number, u.name as customer_name, r.name as rider_name FROM deliveries d JOIN orders o ON d.order_id = o.id JOIN users u ON o.user_id = u.id JOIN users r ON d.delivery_person_id = r.id WHERE d.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
        $params = [$startDate, $endDate];
        if ($riderFilter) { $sql .= " AND d.delivery_person_id = ?"; $params[] = $riderFilter; }
        $sql .= " ORDER BY d.created_at DESC";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'customers':
        $reportTitle = 'Customer Activity Report';
        $sql = "SELECT u.name, u.email, u.phone, COUNT(o.id) as orders, COALESCE(SUM(o.total_amount),0) as total_spent, MAX(o.created_at) as last_order FROM users u LEFT JOIN orders o ON u.id = o.user_id AND o.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) WHERE u.role = 'customer'";
        $params = [$startDate, $endDate];
        $sql .= " GROUP BY u.id HAVING orders > 0 ORDER BY orders DESC";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
}

// Handle export
if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$reportType.'_report_'.date('Ymd').'.csv"');
    $output = fopen('php://output', 'w');
    if (!empty($reportData)) {
        fputcsv($output, array_keys($reportData[0]));
        foreach ($reportData as $row) { fputcsv($output, $row); }
    }
    fclose($output);
    exit;
}

if ($export === 'print') {
    echo '<!DOCTYPE html><html><head><title>'.$reportTitle.'</title>';
    echo '<style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px;text-align:left;font-size:12px}th{background:#f4f4f4}h1{font-size:20px;margin-bottom:5px}p{color:#666;font-size:12px}@media print{body{padding:0}}</style></head><body>';
    echo '<h1>'.$reportTitle.'</h1><p>Period: '.$startDate.' to '.$endDate.' | Generated: '.date('Y-m-d H:i').'</p>';
    if (!empty($reportData)) {
        echo '<table><thead><tr>';
        foreach (array_keys($reportData[0]) as $header) { echo '<th>'.ucwords(str_replace('_',' ',$header)).'</th>'; }
        echo '</tr></thead><tbody>';
        foreach ($reportData as $row) { echo '<tr>'; foreach ($row as $cell) { echo '<td>'.htmlspecialchars($cell??'').'</td>'; } echo '</tr>'; }
        echo '</tbody></table>';
    } else { echo '<p>No data found.</p>'; }
    echo '<script>window.print();</script></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{50:'#eff6ff',600:'#2563eb',700:'#1d4ed8'}},fontFamily:{poppins:['Poppins','sans-serif'],inter:['Inter','sans-serif']},animation:{'fade-in':'fadeIn .5s ease-out','slide-down':'slideDown .4s ease-out'},keyframes:{fadeIn:{'0%':{opacity:'0'},'100%':{opacity:'1'}},slideDown:{'0%':{transform:'translateY(-15px)',opacity:'0'},'100%':{transform:'translateY(0)',opacity:'1'}}}}}}</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--sidebar-width:260px}.sidebar{width:var(--sidebar-width)}.main-content{margin-left:var(--sidebar-width)}
        .sidebar-link{transition:all .3s ease;position:relative}.sidebar-link::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:0;background:#2563eb;border-radius:0 4px 4px 0;transition:height .3s ease}
        .sidebar-link:hover::before,.sidebar-link.active::before{height:60%}.sidebar-link.active{background:rgba(37,99,235,.15);color:#60a5fa}
        .report-type-btn{transition:all .3s ease}.report-type-btn.active{background:#2563eb;color:white;box-shadow:0 4px 15px rgba(37,99,235,.3)}
        .table-row{transition:all .25s ease}.table-row:hover{background:linear-gradient(90deg,#eff6ff 0%,transparent 100%);transform:translateX(3px)}
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
            <a href="orders.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-shopping-cart w-5"></i><span>Orders</span></a>
            <a href="users.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-users w-5"></i><span>Users</span></a>
            <a href="payments.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-credit-card w-5"></i><span>Payments</span></a>
            <a href="services-manage.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-cogs w-5"></i><span>Services</span></a>
            <a href="audit-logs.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-history w-5"></i><span>Audit Logs</span></a>
            <a href="messages.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-envelope w-5"></i><span>Messages</span></a>
            <a href="reports.php" class="sidebar-link active flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-300 hover:text-white"><i class="fas fa-file-alt w-5"></i><span>Reports</span></a>
            <a href="undelivered.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-exclamation-triangle w-5"></i><span>Undelivered</span></a>
            <hr class="border-slate-700/50 my-4">
            <a href="../index.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-home w-5"></i><span>Back to Site</span></a>
            <a href="../logout.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-red-400 hover:text-red-300 hover:bg-red-500/10"><i class="fas fa-sign-out-alt w-5"></i><span>Logout</span></a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content flex-1 p-6 lg:p-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 animate-slide-down">
            <div><h1 class="text-3xl font-poppins font-extrabold text-slate-900"><span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Reports</span></h1><p class="text-slate-500 mt-1">Generate and export detailed business reports</p></div>
        </div>

        <!-- Report Type Selector -->
        <div class="flex flex-wrap gap-2 mb-6">
            <?php $types=['orders'=>'Order Report','financial'=>'Financial Report','sales'=>'Sales Report','performance'=>'Performance Report','delivery'=>'Delivery Report','customers'=>'Customer Activity'];?>
            <?php foreach($types as $key=>$label):?>
            <a href="?type=<?php echo $key;?>&start_date=<?php echo $startDate;?>&end_date=<?php echo $endDate;?>" class="report-type-btn px-4 py-2 rounded-xl text-sm font-semibold transition-all <?php echo $reportType===$key?'active':'bg-white border border-gray-200 text-slate-600 hover:border-blue-300';?>"><?php echo $label;?></a>
            <?php endforeach;?>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6 shadow-sm">
            <form method="GET" class="grid grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                <input type="hidden" name="type" value="<?php echo $reportType;?>">
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">Start Date</label><input type="date" name="start_date" value="<?php echo $startDate;?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none"></div>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">End Date</label><input type="date" name="end_date" value="<?php echo $endDate;?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none"></div>
                <?php if(in_array($reportType,['orders','financial'])):?>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">Status</label><select name="status" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none"><option value="">All</option><option value="pending" <?php echo $statusFilter==='pending'?'selected':'';?>>Pending</option><option value="completed" <?php echo $statusFilter==='completed'?'selected':'';?>>Completed</option><option value="cancelled" <?php echo $statusFilter==='cancelled'?'selected':'';?>>Cancelled</option></select></div>
                <?php endif;?>
                <?php if(in_array($reportType,['orders','sales'])):?>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">Service</label><select name="service" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none"><option value="">All</option><?php foreach($services as $s):?><option value="<?php echo $s['id'];?>" <?php echo $serviceFilter==$s['id']?'selected':'';?>><?php echo htmlspecialchars($s['name']);?></option><?php endforeach;?></select></div>
                <?php endif;?>
                <?php if(in_array($reportType,['performance','delivery'])):?>
                <div><label class="block text-xs font-semibold text-slate-600 mb-1">Rider</label><select name="rider" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none"><option value="">All</option><?php foreach($riders as $r):?><option value="<?php echo $r['id'];?>" <?php echo $riderFilter==$r['id']?'selected':'';?>><?php echo htmlspecialchars($r['name']);?></option><?php endforeach;?></select></div>
                <?php endif;?>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors"><i class="fas fa-filter mr-1"></i> Apply</button>
                    <a href="?type=<?php echo $reportType;?>&start_date=<?php echo $startDate;?>&end_date=<?php echo $endDate;?>&status=<?php echo $statusFilter;?>&service=<?php echo $serviceFilter;?>&rider=<?php echo $riderFilter;?>&export=csv" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 transition-colors"><i class="fas fa-file-csv mr-1"></i> CSV</a>
                    <a href="?type=<?php echo $reportType;?>&start_date=<?php echo $startDate;?>&end_date=<?php echo $endDate;?>&status=<?php echo $statusFilter;?>&service=<?php echo $serviceFilter;?>&rider=<?php echo $riderFilter;?>&export=print" target="_blank" class="px-4 py-2 bg-slate-600 text-white rounded-lg text-sm font-semibold hover:bg-slate-700 transition-colors"><i class="fas fa-print mr-1"></i> Print</a>
                </div>
            </form>
        </div>

        <!-- Report Content -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b bg-gradient-to-r from-blue-50 to-purple-50"><h3 class="font-poppins font-bold text-slate-900 text-sm uppercase tracking-wider"><i class="fas fa-file-alt mr-2 text-blue-600"></i><?php echo $reportTitle;?> <span class="text-slate-400 font-normal text-xs ml-2"><?php echo $startDate;?> to <?php echo $endDate;?></span></h3></div>
            <div class="overflow-x-auto">
                <?php if(empty($reportData)):?>
                <div class="text-center py-16"><i class="fas fa-chart-bar text-5xl text-slate-200 mb-4"></i><p class="text-slate-400">No data found for the selected period and filters.</p></div>
                <?php else:?>
                <table class="w-full min-w-[600px]">
                    <thead><tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider"><?php foreach(array_keys($reportData[0]) as $header):?><th class="px-5 py-3"><?php echo ucwords(str_replace('_',' ',$header));?></th><?php endforeach;?></tr></thead>
                    <tbody class="divide-y"><?php foreach($reportData as $row):?><tr class="table-row"><?php foreach($row as $cell):?><td class="px-5 py-3 text-sm text-slate-700"><?php echo is_numeric($cell)&&strpos($cell,'.')!==false?number_format($cell,2):htmlspecialchars($cell??'—');?></td><?php endforeach;?></tr><?php endforeach;?></tbody>
                </table>
                <?php endif;?>
            </div>
            <div class="px-6 py-3 bg-slate-50 border-t text-xs text-slate-500">Total records: <strong><?php echo count($reportData);?></strong></div>
        </div>
    </main>
</div>
</body>
</html>