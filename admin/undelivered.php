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

// Handle clear/delete undelivered record
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $stmt = $db->prepare("UPDATE deliveries SET status = 'pending', delivery_notes = NULL WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    header('Location: undelivered.php?cleared=1');
    exit;
}

// Fetch all undelivered orders with reasons
$undelivered = $db->query("
    SELECT d.*, o.order_number, o.total_amount,
           u.name AS customer_name, u.phone AS customer_phone,
           r.name AS rider_name,
           s.name AS service_name, s.icon AS service_icon
    FROM deliveries d
    JOIN orders o ON d.order_id = o.id
    JOIN users u ON o.user_id = u.id
    JOIN users r ON d.delivery_person_id = r.id
    JOIN services s ON o.service_id = s.id
    WHERE d.status = 'undelivered'
    ORDER BY d.updated_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Statistics
$totalUndelivered = count($undelivered);
$totalLoss = 0;
$reasonsSummary = [];
foreach ($undelivered as $u) {
    $totalLoss += $u['total_amount'];
    $reason = strtolower($u['delivery_notes'] ?? 'no reason');
    if (strpos($reason, 'customer not available') !== false || strpos($reason, 'unavailable') !== false) {
        $cat = 'Customer Unavailable';
    } elseif (strpos($reason, 'address') !== false || strpos($reason, 'wrong') !== false) {
        $cat = 'Address Issue';
    } elseif (strpos($reason, 'refused') !== false) {
        $cat = 'Refused Delivery';
    } elseif (strpos($reason, 'no reason') !== false || empty(trim($reason))) {
        $cat = 'No Reason Given';
    } else {
        $cat = 'Other';
    }
    $reasonsSummary[$cat] = ($reasonsSummary[$cat] ?? 0) + 1;
}
arsort($reasonsSummary);

// Chart data
$reasonLabels = array_keys($reasonsSummary);
$reasonCounts = array_values($reasonsSummary);
$reasonColors = ['#ef4444','#f97316','#eab308','#6b7280','#3b82f6'];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undelivered Orders - <?php echo APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: { 50:'#eff6ff',100:'#dbeafe',600:'#2563eb',700:'#1d4ed8' } },
                    fontFamily: { 'poppins':['Poppins','sans-serif'], 'inter':['Inter','sans-serif'] },
                    animation: { 'fade-in':'fadeIn 0.5s ease-out','slide-up':'slideUp 0.5s ease-out','slide-down':'slideDown 0.4s ease-out','scale-in':'scaleIn 0.3s ease-out','pulse-soft':'pulseSoft 2s ease-in-out infinite' },
                    keyframes: {
                        fadeIn:{'0%':{opacity:'0'},'100%':{opacity:'1'}},
                        slideUp:{'0%':{transform:'translateY(20px)',opacity:'0'},'100%':{transform:'translateY(0)',opacity:'1'}},
                        slideDown:{'0%':{transform:'translateY(-15px)',opacity:'0'},'100%':{transform:'translateY(0)',opacity:'1'}},
                        scaleIn:{'0%':{transform:'scale(0.95)',opacity:'0'},'100%':{transform:'scale(1)',opacity:'1'}},
                        pulseSoft:{'0%,100%':{opacity:'1'},'50%':{opacity:'0.6'}},
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        :root { --sidebar-width: 260px; }
        .sidebar { width: var(--sidebar-width); }
        .main-content { margin-left: var(--sidebar-width); }
        .sidebar-link { transition: all 0.3s ease; position: relative; }
        .sidebar-link::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 3px; height: 0; background: #2563eb; border-radius: 0 4px 4px 0; transition: height 0.3s ease; }
        .sidebar-link:hover::before, .sidebar-link.active::before { height: 60%; }
        .sidebar-link.active { background: rgba(37,99,235,0.15); color: #60a5fa; }
        
        .stat-card {
            background: white; border-radius: 1.25rem; padding: 1.5rem;
            border: 1px solid #f1f5f9; transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
            position: relative; overflow: hidden;
        }
        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, #ef4444, #f97316, #dc2626);
            background-size: 200% 100%; animation: shimmer 3s linear infinite;
            transform: scaleX(0); transform-origin: left; transition: transform 0.5s ease;
        }
        .stat-card:hover::before { transform: scaleX(1); }
        .stat-card:hover { transform: translateY(-6px); box-shadow: 0 25px 50px -12px rgba(239,68,68,0.2); }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        
        .stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; transition: all 0.4s ease; }
        .stat-card:hover .stat-icon { transform: scale(1.1) rotate(-5deg); }
        
        .order-row { transition: all 0.3s ease; }
        .order-row:hover { background: linear-gradient(90deg, #fef2f2 0%, transparent 100%); transform: translateX(3px); }
        
        .reason-badge { transition: all 0.3s ease; }
        .reason-badge:hover { transform: scale(1.02); }
        
        .btn-action { transition: all 0.3s cubic-bezier(0.25,0.46,0.45,0.94); }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        
        @media (max-width: 1024px) { .sidebar { width: 100%; position: relative; height: auto; } .main-content { margin-left: 0; } }
    </style>
</head>
<body class="font-inter antialiased bg-slate-50">

    <div class="flex min-h-screen">
        
        <!-- SIDEBAR -->
        <aside class="sidebar fixed top-0 left-0 bottom-0 bg-slate-900 text-white z-50 flex flex-col overflow-y-auto">
            <div class="p-6 border-b border-slate-700/50">
                <a href="../index.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-orange-500 rounded-xl flex items-center justify-center text-white shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-105"><i class="fas fa-cubes"></i></div>
                    <div><span class="font-poppins font-bold text-lg">E-Find Admin</span><p class="text-xs text-slate-400">Management Panel</p></div>
                </a>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a href="dashboard.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span></a>
                <a href="orders.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-shopping-cart w-5"></i><span>Orders</span></a>
                <a href="users.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-users w-5"></i><span>Users</span></a>
                <a href="services-manage.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-cogs w-5"></i><span>Services</span></a>
                <a href="audit-logs.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-history w-5"></i><span>Audit Logs</span></a>
                <a href="payments.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-credit-card w-5"></i><span>Payments</span></a>
                <a href="messages.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-envelope w-5"></i><span>Messages</span></a>
                <a href="reports.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-chart-bar w-5"></i><span>Reports</span></a>
                <a href="undelivered.php" class="sidebar-link active flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-300 hover:text-white"><i class="fas fa-exclamation-triangle w-5"></i><span>Undelivered</span><?php if ($totalUndelivered > 0): ?><span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-0.5 animate-pulse-soft"><?php echo $totalUndelivered; ?></span><?php endif; ?></a>
                <hr class="border-slate-700/50 my-4">
                <a href="../index.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white"><i class="fas fa-home w-5"></i><span>Back to Site</span></a>
                <a href="../logout.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-red-400 hover:text-red-300 hover:bg-red-500/10"><i class="fas fa-sign-out-alt w-5"></i><span>Logout</span></a>
            </nav>
            <div class="p-4 border-t border-slate-700/50">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-sm font-bold"><?php echo strtoupper(substr(Session::get('user_name'), 0, 1)); ?></div>
                    <div><p class="text-sm font-medium text-white"><?php echo htmlspecialchars(Session::get('user_name')); ?></p><p class="text-xs text-slate-400">Administrator</p></div>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content flex-1 p-6 lg:p-8">
            
            <!-- Header -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-4 animate-slide-down">
                <div>
                    <h1 class="text-3xl font-poppins font-extrabold text-slate-900">
                        <span class="bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent">Undelivered</span> Orders
                    </h1>
                    <p class="text-slate-500 mt-1">Monitor and analyze failed delivery reasons</p>
                </div>
                <a href="reports.php" class="btn-action px-5 py-2.5 bg-white border-2 border-blue-500 text-blue-600 font-semibold rounded-xl hover:bg-blue-600 hover:text-white transition-all duration-300 text-sm">
                    <i class="fas fa-chart-bar mr-1"></i> View Reports
                </a>
            </div>

            <?php if (isset($_GET['cleared'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl mb-6 flex items-center animate-slide-down">
                <i class="fas fa-check-circle mr-2 text-lg"></i> Undelivered record cleared successfully.
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="stat-card animate-scale-in">
                    <div class="flex justify-between items-start">
                        <div><p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Total Failed</p><p class="text-3xl font-bold text-red-600 mt-2"><?php echo $totalUndelivered; ?></p><p class="text-xs text-slate-400 mt-1">All time</p></div>
                        <div class="stat-icon bg-red-100 text-red-600"><i class="fas fa-times-circle"></i></div>
                    </div>
                </div>
                <div class="stat-card animate-scale-in" style="animation-delay:0.1s;">
                    <div class="flex justify-between items-start">
                        <div><p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Revenue Lost</p><p class="text-3xl font-bold text-red-600 mt-2">UGX <?php echo number_format($totalLoss / 1000, 0); ?>K</p><p class="text-xs text-slate-400 mt-1">From cancelled orders</p></div>
                        <div class="stat-icon bg-orange-100 text-orange-600"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                </div>
                <div class="stat-card animate-scale-in" style="animation-delay:0.2s;">
                    <div class="flex justify-between items-start">
                        <div><p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Top Reason</p><p class="text-xl font-bold text-red-600 mt-2"><?php echo !empty($reasonLabels) ? $reasonLabels[0] : 'N/A'; ?></p><p class="text-xs text-slate-400 mt-1">Most frequent cause</p></div>
                        <div class="stat-icon bg-amber-100 text-amber-600"><i class="fas fa-exclamation-circle"></i></div>
                    </div>
                </div>
                <div class="stat-card animate-scale-in" style="animation-delay:0.3s;">
                    <div class="flex justify-between items-start">
                        <div><p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Avg Loss/Order</p><p class="text-3xl font-bold text-red-600 mt-2">UGX <?php echo $totalUndelivered > 0 ? number_format($totalLoss / $totalUndelivered / 1000, 1) : 0; ?>K</p><p class="text-xs text-slate-400 mt-1">Per failed delivery</p></div>
                        <div class="stat-icon bg-rose-100 text-rose-600"><i class="fas fa-calculator"></i></div>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6 mb-8">
                <!-- Reasons Chart -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm animate-scale-in" style="animation-delay:0.4s;">
                    <h3 class="font-poppins font-bold text-slate-900 mb-4 text-sm uppercase tracking-wider">Failure Reasons</h3>
                    <?php if (!empty($reasonLabels)): ?>
                    <canvas id="reasonsChart" height="220"></canvas>
                    <?php else: ?>
                    <p class="text-slate-400 text-sm text-center py-12">No data available.</p>
                    <?php endif; ?>
                </div>

                <!-- Reason Summary Cards -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm animate-scale-in" style="animation-delay:0.5s;">
                    <h3 class="font-poppins font-bold text-slate-900 mb-4 text-sm uppercase tracking-wider">Reason Breakdown</h3>
                    <?php if (!empty($reasonsSummary)): ?>
                    <div class="grid grid-cols-2 gap-3">
                        <?php 
                        $colorMap = ['Customer Unavailable'=>'amber','Address Issue'=>'blue','Refused Delivery'=>'red','No Reason Given'=>'slate','Other'=>'purple'];
                        foreach ($reasonsSummary as $reason => $count): 
                            $c = $colorMap[$reason] ?? 'slate';
                            $pct = $totalUndelivered > 0 ? round(($count / $totalUndelivered) * 100) : 0;
                        ?>
                        <div class="bg-<?php echo $c; ?>-50 border border-<?php echo $c; ?>-200 rounded-xl p-4 hover:shadow-md transition-all duration-300">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-semibold text-<?php echo $c; ?>-700 uppercase"><?php echo $reason; ?></span>
                                <span class="text-lg font-bold text-<?php echo $c; ?>-600"><?php echo $count; ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-<?php echo $c; ?>-500 transition-all duration-1000" style="width:<?php echo $pct; ?>%"></div>
                            </div>
                            <p class="text-xs text-slate-400 mt-1"><?php echo $pct; ?>% of failures</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-slate-400 text-sm text-center py-8">No undelivered orders recorded.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Undelivered Orders Table -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden animate-scale-in" style="animation-delay:0.6s;">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-poppins font-bold text-slate-900 text-sm uppercase tracking-wider">All Undelivered Orders</h3>
                    <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold"><?php echo $totalUndelivered; ?> Records</span>
                </div>
                
                <?php if (empty($undelivered)): ?>
                <div class="text-center py-16">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                        <i class="fas fa-check-circle text-4xl text-green-500"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-slate-700 mb-2">All Clear!</h4>
                    <p class="text-slate-400">No undelivered orders recorded. All deliveries completed successfully.</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px]">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-5 py-3">Order #</th>
                                <th class="px-5 py-3">Customer</th>
                                <th class="px-5 py-3">Service</th>
                                <th class="px-5 py-3">Amount Lost</th>
                                <th class="px-5 py-3">Assigned Rider</th>
                                <th class="px-5 py-3">Failure Reason</th>
                                <th class="px-5 py-3">Date</th>
                                <th class="px-5 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($undelivered as $u): ?>
                            <tr class="order-row">
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-red-600 text-sm"><?php echo htmlspecialchars($u['order_number']); ?></span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($u['customer_name']); ?></p>
                                    <p class="text-xs text-slate-400"><?php echo htmlspecialchars($u['customer_phone']); ?></p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center space-x-2">
                                        <span><?php echo htmlspecialchars($u['service_icon'] ?? '📦'); ?></span>
                                        <span class="text-sm text-slate-600"><?php echo htmlspecialchars($u['service_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-red-600 text-sm">UGX <?php echo number_format($u['total_amount']); ?></span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-7 h-7 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 text-xs">
                                            <i class="fas fa-motorcycle"></i>
                                        </div>
                                        <span class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars($u['rider_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 max-w-[250px]">
                                    <div class="reason-badge bg-red-50 border border-red-200 rounded-xl px-3 py-2 text-sm text-red-700">
                                        <i class="fas fa-info-circle mr-1.5 text-red-400"></i>
                                        <?php echo htmlspecialchars($u['delivery_notes'] ?: 'No reason provided'); ?>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    <?php echo date('M d, Y', strtotime($u['updated_at'])); ?>
                                    <br><span class="text-xs text-slate-400"><?php echo date('h:i A', strtotime($u['updated_at'])); ?></span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <a href="?delete=1&id=<?php echo $u['id']; ?>" 
                                       class="btn-action inline-flex items-center px-3 py-2 bg-green-50 text-green-700 rounded-lg text-xs font-semibold hover:bg-green-100 transition-all"
                                       onclick="return confirm('Clear this failed delivery record? This will reset the delivery status.')">
                                        <i class="fas fa-check mr-1"></i> Clear
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-3 bg-slate-50 border-t border-gray-100 flex justify-between items-center">
                    <span class="text-xs text-slate-500">Total loss: <strong class="text-red-600">UGX <?php echo number_format($totalLoss); ?></strong></span>
                    <span class="text-xs text-slate-400">Click <strong>Clear</strong> to reset a delivery record</span>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Reasons Chart
        <?php if (!empty($reasonLabels)): ?>
        new Chart(document.getElementById('reasonsChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($reasonLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($reasonCounts); ?>,
                    backgroundColor: ['rgba(239,68,68,0.7)','rgba(249,115,22,0.7)','rgba(234,179,8,0.7)','rgba(107,114,128,0.7)','rgba(59,130,246,0.7)'],
                    borderColor: ['#ef4444','#f97316','#eab308','#6b7280','#3b82f6'],
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>