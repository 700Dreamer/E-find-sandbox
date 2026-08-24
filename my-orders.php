<?php
require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/Session.php';

Session::init();

if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = (int) Session::get('user_id');
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("
    SELECT o.*, s.name AS service_name, s.icon AS service_icon,
           p.status AS payment_status
    FROM orders o
    JOIN services s ON o.service_id = s.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.user_id = :uid
    ORDER BY o.created_at DESC
    LIMIT 20
");
$stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - <?php echo APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        .order-row { transition: all 0.3s ease; }
        .order-row:hover { background: #f8fafc; }
        .status-badge { white-space: nowrap; }
        .btn-track { transition: all 0.3s ease; }
        .btn-track:hover { transform: translateY(-2px); }
        .btn-pay { transition: all 0.3s ease; }
        .btn-pay:hover { transform: translateY(-2px); }
        .quick-link-card { transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .quick-link-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(37,99,235,0.1); }
        .quick-link-icon { transition: all 0.4s ease; }
        .quick-link-card:hover .quick-link-icon { transform: scale(1.1); }
    </style>
</head>
<body class="font-inter antialiased bg-gradient-to-br from-gray-50 via-blue-50/20 to-white min-h-screen">

    <!-- NAVIGATION -->
    <nav class="bg-white/90 backdrop-blur-xl shadow-sm border-b border-gray-100/50 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="index.php" class="flex items-center space-x-2 group">
                    <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-orange-500 rounded-lg flex items-center justify-center text-white shadow-md group-hover:shadow-lg transition-all duration-300 group-hover:scale-105">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <span class="text-lg font-poppins font-bold">
                        <span class="text-blue-600">E-Find</span>
                        <span class="text-orange-500"> & Soft Solutions</span>
                    </span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">Home</a>
                    <a href="services.php" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">Services</a>
                    <a href="my-orders.php" class="text-sm text-blue-600 font-semibold">My Orders</a>
                    <div class="flex items-center space-x-2">
                        <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-xs font-bold"><?php echo strtoupper(substr(Session::get('user_name'), 0, 1)); ?></div>
                        <span class="text-sm text-gray-600 hidden sm:inline"><?php echo htmlspecialchars(Session::get('user_name')); ?></span>
                    </div>
                    <a href="logout.php" class="text-sm text-red-500 hover:text-red-700 transition-colors"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8" data-aos="fade-up">
            <div>
                <h1 class="text-3xl font-poppins font-extrabold text-gray-900">My Orders</h1>
                <p class="text-gray-500 mt-1">Track and manage all your orders in one place</p>
            </div>
            <a href="services.php" class="mt-4 sm:mt-0 inline-flex items-center px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all duration-300">
                <i class="fas fa-plus mr-2"></i> New Order
            </a>
        </div>

        <?php if (empty($orders)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center" data-aos="fade-up">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <i class="fas fa-shopping-bag text-3xl text-blue-600"></i>
            </div>
            <h3 class="text-xl font-poppins font-bold text-gray-900 mb-2">No Orders Yet</h3>
            <p class="text-gray-500 mb-6">You haven't placed any orders yet. Start exploring our services!</p>
            <a href="services.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                <i class="fas fa-compass mr-2"></i> Browse Services
            </a>
        </div>
        
        <?php else: ?>
        <!-- Orders Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" data-aos="fade-up">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider w-[180px]">Order #</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider w-[200px]">Service</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider w-[130px]">Amount</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider w-[130px]">Date</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider w-[140px]">Status</th>
                            <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider w-[130px]">Payment</th>
                            <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider w-[120px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($orders as $order): 
                            $statusColors = [
                                'pending' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'dot' => 'bg-amber-500'],
                                'confirmed' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'dot' => 'bg-blue-500'],
                                'processing' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'dot' => 'bg-indigo-500'],
                                'ready' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500'],
                                'in_transit' => ['bg' => 'bg-cyan-50', 'text' => 'text-cyan-700', 'dot' => 'bg-cyan-500'],
                                'delivered' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'dot' => 'bg-green-500'],
                                'completed' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'dot' => 'bg-green-500'],
                                'cancelled' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'dot' => 'bg-red-500'],
                            ];
                            $s = $statusColors[$order['status']] ?? ['bg' => 'bg-gray-50', 'text' => 'text-gray-700', 'dot' => 'bg-gray-500'];
                        ?>
                        <tr class="order-row">
                            <td class="px-6 py-4">
                                <span class="font-semibold text-blue-600 text-sm"><?php echo htmlspecialchars($order['order_number']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <span class="text-base"><?php echo htmlspecialchars($order['service_icon'] ?? '📦'); ?></span>
                                    <span class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($order['service_name']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-gray-900 text-sm">UGX <?php echo number_format($order['total_amount']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="status-badge inline-flex items-center px-3 py-1.5 <?php echo $s['bg']; ?> <?php echo $s['text']; ?> rounded-full text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full <?php echo $s['dot']; ?> mr-1.5"></span>
                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if (isset($order['payment_status']) && $order['payment_status'] === 'pending'): ?>
                                    <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn-pay inline-flex items-center px-3 py-1.5 bg-amber-100 text-amber-700 rounded-full text-xs font-semibold hover:bg-amber-200">
                                        <i class="fas fa-credit-card mr-1"></i> Pay
                                    </a>
                                <?php elseif (isset($order['payment_status']) && $order['payment_status'] === 'completed'): ?>
                                    <span class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                                        <i class="fas fa-check-circle mr-1"></i> Paid
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="track.php?order=<?php echo urlencode($order['order_number']); ?>" class="btn-track inline-flex items-center px-4 py-2 bg-white border-2 border-blue-500 text-blue-600 font-semibold rounded-lg hover:bg-blue-600 hover:text-white transition-all text-xs">
                                    <i class="fas fa-map-marker-alt mr-1.5"></i> Track
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Table Footer -->
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                <span class="text-xs text-gray-500">Showing <strong><?php echo count($orders); ?></strong> order(s)</span>
                <span class="text-xs text-gray-400">Click Track to view order details</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Links Section -->
        <div class="mt-10" data-aos="fade-up" data-aos-delay="200">
            <h3 class="text-lg font-poppins font-bold text-gray-900 mb-5">Quick Actions</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Track Order Card -->
                <a href="track.php" class="quick-link-card bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4 group">
                    <div class="quick-link-icon w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 text-xl group-hover:bg-blue-600 group-hover:text-white">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm">Track Order</h4>
                        <p class="text-xs text-gray-500">Enter order number to track</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-300 ml-auto group-hover:text-blue-600 group-hover:translate-x-1 transition-all"></i>
                </a>
                
                <!-- New Order Card -->
                <a href="services.php" class="quick-link-card bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4 group">
                    <div class="quick-link-icon w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600 text-xl group-hover:bg-orange-600 group-hover:text-white">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm">Place New Order</h4>
                        <p class="text-xs text-gray-500">Browse all services</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-300 ml-auto group-hover:text-orange-600 group-hover:translate-x-1 transition-all"></i>
                </a>
                
                <!-- Support Card -->
                <a href="contact.php" class="quick-link-card bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4 group">
                    <div class="quick-link-icon w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-green-600 text-xl group-hover:bg-green-600 group-hover:text-white">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 text-sm">Need Help?</h4>
                        <p class="text-xs text-gray-500">Contact our support team</p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-300 ml-auto group-hover:text-green-600 group-hover:translate-x-1 transition-all"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="bg-gray-900 text-gray-400 pt-16 pb-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
                <div class="col-span-2 lg:col-span-1">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-orange-500 rounded-lg flex items-center justify-center text-white"><i class="fas fa-cubes"></i></div>
                        <span class="text-xl font-poppins font-bold text-white">E-Find & Soft Solutions</span>
                    </div>
                    <p class="text-gray-500 text-sm leading-relaxed">Professional custom services & tracking platform. Quality delivered with care.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="index.php" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="services.php" class="hover:text-white transition-colors">Services</a></li>
                        <li><a href="track.php" class="hover:text-white transition-colors">Track Order</a></li>
                        <li><a href="contact.php" class="hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Services</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="services.php?category=engraving" class="hover:text-white transition-colors"><i class="fas fa-trophy mr-2 text-blue-400"></i>Engraving</a></li>
                        <li><a href="services.php?category=embroidery" class="hover:text-white transition-colors"><i class="fas fa-tshirt mr-2 text-blue-400"></i>Embroidery</a></li>
                        <li><a href="services.php?category=tracking" class="hover:text-white transition-colors"><i class="fas fa-map-marker-alt mr-2 text-blue-400"></i>Tracking</a></li>
                        <li><a href="services.php?category=branding" class="hover:text-white transition-colors"><i class="fas fa-palette mr-2 text-blue-400"></i>Branding</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Contact</h4>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-start space-x-2"><i class="fas fa-map-marker-alt text-blue-400 mt-1"></i><span>123 Business Park, Kampala, Uganda</span></li>
                        <li class="flex items-center space-x-2"><i class="fas fa-phone text-blue-400"></i><span>+256 700 000000</span></li>
                        <li class="flex items-center space-x-2"><i class="fas fa-envelope text-blue-400"></i><span>info@efind.com</span></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-6 text-center">
                <p class="text-gray-600 text-xs">&copy; <?php echo date('Y'); ?> E-Find and Soft Solutions. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>AOS.init({ duration: 700, once: true });</script>
</body>
</html>