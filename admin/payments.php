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

// Fetch all payments with details
$payments = $db->query("
    SELECT p.*, o.order_number, o.total_amount AS order_total, 
           u.name AS customer_name, u.email AS customer_email,
           s.name AS service_name
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.user_id = u.id
    JOIN services s ON o.service_id = s.id
    ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalPayments = count($payments);
$totalRevenue = 0;
$completedPayments = 0;
$pendingPayments = 0;
$failedPayments = 0;
$mobileMoneyCount = 0;
$codCount = 0;

foreach ($payments as $p) {
    $totalRevenue += $p['amount'];
    if ($p['status'] === 'completed') $completedPayments++;
    if ($p['status'] === 'pending') $pendingPayments++;
    if ($p['status'] === 'failed') $failedPayments++;
    if (strpos($p['payment_method'], 'mobile') !== false || strpos($p['payment_method'], 'momo') !== false) $mobileMoneyCount++;
    if ($p['payment_method'] === 'cash_on_delivery') $codCount++;
}

$completionRate = $totalPayments > 0 ? round(($completedPayments / $totalPayments) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - <?php echo APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' }
                    },
                    fontFamily: { 'poppins':['Poppins','sans-serif'], 'inter':['Inter','sans-serif'] },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'slide-down': 'slideDown 0.4s ease-out',
                        'scale-in': 'scaleIn 0.3s ease-out',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { transform: 'translateY(20px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
                        slideDown: { '0%': { transform: 'translateY(-15px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
                        scaleIn: { '0%': { transform: 'scale(0.95)', opacity: '0' }, '100%': { transform: 'scale(1)', opacity: '1' } },
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
        
        .sidebar-link {
            transition: all 0.3s ease;
            position: relative;
        }
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 0;
            background: #2563eb;
            border-radius: 0 4px 4px 0;
            transition: height 0.3s ease;
        }
        .sidebar-link:hover::before,
        .sidebar-link.active::before { height: 60%; }
        .sidebar-link.active { background: rgba(37,99,235,0.15); color: #60a5fa; }
        
        .payment-row { transition: all 0.3s ease; }
        .payment-row:hover { background: linear-gradient(90deg, #eff6ff 0%, transparent 100%); transform: translateX(3px); }
        
        .stat-card {
            background: white;
            border-radius: 1.25rem;
            padding: 1.5rem;
            border: 1px solid #f1f5f9;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2563eb, #7c3aed, #f97316);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s ease;
        }
        .stat-card:hover::before { transform: scaleX(1); }
        .stat-card:hover { transform: translateY(-6px); box-shadow: 0 25px 50px -12px rgba(37,99,235,0.15); }
        
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        
        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            transition: all 0.4s ease;
        }
        .stat-card:hover .stat-icon { transform: scale(1.1) rotate(-5deg); }
        
        @media (max-width: 1024px) {
            .sidebar { width: 100%; position: relative; height: auto; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body class="font-inter antialiased bg-slate-50">

    <div class="flex min-h-screen">
        
        <!-- SIDEBAR -->
        <aside class="sidebar fixed top-0 left-0 bottom-0 bg-slate-900 text-white z-50 flex flex-col overflow-y-auto">
            <div class="p-6 border-b border-slate-700/50">
                <a href="../index.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-orange-500 rounded-xl flex items-center justify-center text-white shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-105">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div>
                        <span class="font-poppins font-bold text-lg">E-Find Admin</span>
                        <p class="text-xs text-slate-400">Management Panel</p>
                    </div>
                </a>
            </div>
            
            <nav class="flex-1 p-4 space-y-1">
                <a href="dashboard.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span>
                </a>
                <a href="orders.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-shopping-cart w-5"></i><span>Orders</span>
                </a>
                <a href="users.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-users w-5"></i><span>Users</span>
                </a>
                <a href="payments.php" class="sidebar-link active flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-300 hover:text-white">
                    <i class="fas fa-credit-card w-5"></i><span>Payments</span>
                </a>
                <a href="messages.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-envelope w-5"></i><span>Messages</span>
                </a>
                <a href="reports.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-chart-bar w-5"></i><span>Reports</span>
                </a>
                <a href="undelivered.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-exclamation-triangle w-5"></i><span>Undelivered</span>
                </a>
                
                <hr class="border-slate-700/50 my-4">
                
                <a href="../index.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-home w-5"></i><span>Back to Site</span>
                </a>
                <a href="../logout.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-red-400 hover:text-red-300 hover:bg-red-500/10">
                    <i class="fas fa-sign-out-alt w-5"></i><span>Logout</span>
                </a>
            </nav>
            
            <div class="p-4 border-t border-slate-700/50">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                        <?php echo strtoupper(substr(Session::get('user_name'), 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white"><?php echo htmlspecialchars(Session::get('user_name')); ?></p>
                        <p class="text-xs text-slate-400">Administrator</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content flex-1 p-6 lg:p-8">
            
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 animate-slide-down">
                <div>
                    <h1 class="text-3xl font-poppins font-extrabold text-slate-900">
                        Payment <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Transactions</span>
                    </h1>
                    <p class="text-slate-500 mt-1">Total: <strong><?php echo $totalPayments; ?></strong> transactions · Revenue: <strong class="text-green-600">UGX <?php echo number_format($totalRevenue); ?></strong></p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="stat-card animate-scale-in" style="animation-delay:0s;">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Total Revenue</p>
                            <p class="text-2xl font-bold text-slate-900 mt-2">UGX <?php echo number_format($totalRevenue / 1000000, 1); ?>M</p>
                        </div>
                        <div class="stat-icon bg-emerald-100 text-emerald-600"><i class="fas fa-dollar-sign"></i></div>
                    </div>
                </div>
                <div class="stat-card animate-scale-in" style="animation-delay:0.1s;">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Completed</p>
                            <p class="text-2xl font-bold text-slate-900 mt-2"><?php echo $completedPayments; ?></p>
                        </div>
                        <div class="stat-icon bg-green-100 text-green-600"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="stat-card animate-scale-in" style="animation-delay:0.2s;">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Pending</p>
                            <p class="text-2xl font-bold text-slate-900 mt-2"><?php echo $pendingPayments; ?></p>
                        </div>
                        <div class="stat-icon bg-amber-100 text-amber-600"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
                <div class="stat-card animate-scale-in" style="animation-delay:0.3s;">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Completion Rate</p>
                            <p class="text-2xl font-bold text-slate-900 mt-2"><?php echo $completionRate; ?>%</p>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                                <div class="bg-green-500 h-1.5 rounded-full transition-all duration-1000" style="width:<?php echo $completionRate; ?>%"></div>
                            </div>
                        </div>
                        <div class="stat-icon bg-blue-100 text-blue-600"><i class="fas fa-chart-pie"></i></div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm animate-scale-in">
                    <h3 class="font-poppins font-bold text-slate-900 mb-4 text-sm uppercase tracking-wider">Payment Methods</h3>
                    <canvas id="methodChart" height="200"></canvas>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm animate-scale-in" style="animation-delay:0.1s;">
                    <h3 class="font-poppins font-bold text-slate-900 mb-4 text-sm uppercase tracking-wider">Payment Status</h3>
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden animate-scale-in" style="animation-delay:0.2s;">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-poppins font-bold text-slate-900 text-sm uppercase tracking-wider">All Transactions</h3>
                    <span class="text-xs text-slate-400"><?php echo $totalPayments; ?> records</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px]">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-5 py-3">Transaction ID</th>
                                <th class="px-5 py-3">Order #</th>
                                <th class="px-5 py-3">Customer</th>
                                <th class="px-5 py-3">Method</th>
                                <th class="px-5 py-3">Amount</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($payments as $payment): 
                                $statusColor = match($payment['status']) {
                                    'completed' => 'green',
                                    'pending' => 'amber',
                                    'failed' => 'red',
                                    'refunded' => 'purple',
                                    default => 'slate'
                                };
                                $methodIcon = match(true) {
                                    strpos($payment['payment_method'], 'mobile') !== false || strpos($payment['payment_method'], 'momo') !== false => 'fa-mobile-alt',
                                    $payment['payment_method'] === 'cash_on_delivery' => 'fa-hand-holding-usd',
                                    $payment['payment_method'] === 'card' => 'fa-credit-card',
                                    default => 'fa-money-bill'
                                };
                            ?>
                            <tr class="payment-row">
                                <td class="px-5 py-4">
                                    <span class="font-mono text-xs text-slate-600"><?php echo $payment['transaction_id'] ? htmlspecialchars(substr($payment['transaction_id'], 0, 16).'...') : '<span class="text-slate-400 italic">Pending</span>'; ?></span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-blue-600 text-sm"><?php echo htmlspecialchars($payment['order_number']); ?></span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($payment['customer_name']); ?></p>
                                    <p class="text-xs text-slate-400"><?php echo htmlspecialchars($payment['customer_email']); ?></p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-semibold">
                                        <i class="fas <?php echo $methodIcon; ?> mr-1.5"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-slate-800 text-sm">UGX <?php echo number_format($payment['amount']); ?></span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-<?php echo $statusColor; ?>-100 text-<?php echo $statusColor; ?>-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-<?php echo $statusColor; ?>-500 mr-1.5"></span>
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    <?php echo date('M d, Y', strtotime($payment['created_at'])); ?>
                                    <br><span class="text-xs text-slate-400"><?php echo date('h:i A', strtotime($payment['created_at'])); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($payments)): ?>
                            <tr><td colspan="7" class="px-5 py-12 text-center text-slate-400">No payment transactions yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Payment Methods Chart
        new Chart(document.getElementById('methodChart'), {
            type: 'doughnut',
            data: {
                labels: ['Mobile Money', 'Cash on Delivery'],
                datasets: [{
                    data: [<?php echo $mobileMoneyCount; ?>, <?php echo $codCount; ?>],
                    backgroundColor: ['rgba(37,99,235,0.7)', 'rgba(249,115,22,0.7)'],
                    borderColor: ['#2563eb', '#f97316'],
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Payment Status Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending', 'Failed'],
                datasets: [{
                    data: [<?php echo $completedPayments; ?>, <?php echo $pendingPayments; ?>, <?php echo $failedPayments; ?>],
                    backgroundColor: ['rgba(16,185,129,0.7)', 'rgba(245,158,11,0.7)', 'rgba(239,68,68,0.7)'],
                    borderColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
</body>
</html>