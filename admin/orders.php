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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);
    $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, created_by) VALUES (?, ?, 'Status updated by admin', ?)");
    $stmt->execute([$orderId, $newStatus, Session::get('user_id')]);
    $success = "Order status updated to " . ucfirst(str_replace('_', ' ', $newStatus));
}

// Handle rider assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_rider'])) {
    $orderId = (int)$_POST['order_id'];
    $riderId = (int)$_POST['rider_id'];
    $check = $db->prepare("SELECT id FROM deliveries WHERE order_id = ?");
    $check->execute([$orderId]);
    if ($check->rowCount() > 0) {
        $stmt = $db->prepare("UPDATE deliveries SET delivery_person_id = ?, status = 'assigned' WHERE order_id = ?");
        $stmt->execute([$riderId, $orderId]);
    } else {
        $order = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $order->execute([$orderId]);
        $orderData = $order->fetch();
        $stmt = $db->prepare("INSERT INTO deliveries (order_id, delivery_person_id, status, delivery_address) VALUES (?, ?, 'assigned', ?)");
        $stmt->execute([$orderId, $riderId, $orderData['delivery_address'] ?? '']);
    }
    $success = "Rider assigned successfully!";
}

// Handle delivery date update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_date'])) {
    $orderId = (int)$_POST['order_id'];
    $estDate = $_POST['estimated_delivery'];
    $stmt = $db->prepare("UPDATE orders SET estimated_delivery = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$estDate, $orderId]);
    $success = "Estimated delivery date updated!";
}

// Fetch orders
$orders = $db->query("
    SELECT o.*, s.name AS service_name, u.name AS customer_name, u.email AS customer_email,
           p.status AS payment_status, d.delivery_person_id, r.name AS rider_name
    FROM orders o
    JOIN services s ON o.service_id = s.id
    JOIN users u ON o.user_id = u.id
    LEFT JOIN payments p ON o.id = p.order_id
    LEFT JOIN deliveries d ON o.id = d.order_id
    LEFT JOIN users r ON d.delivery_person_id = r.id
    ORDER BY o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$riders = $db->query("SELECT id, name FROM users WHERE role = 'delivery' AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$totalDisplayed = count($orders);
$pendingCount = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - <?php echo APP_NAME; ?></title>
    
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
        
        .order-row { transition: all 0.3s ease; }
        .order-row:hover { background: linear-gradient(90deg, #eff6ff 0%, transparent 100%); transform: translateX(3px); }
        
        .status-badge { transition: all 0.3s ease; }
        .status-badge:hover { transform: scale(1.05); }
        
        .form-select-sm { transition: all 0.3s ease; }
        .form-select-sm:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        
        .btn-action { transition: all 0.3s cubic-bezier(0.25,0.46,0.45,0.94); }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        
        .modal-overlay { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-content { animation: scaleIn 0.3s ease; }
        @keyframes scaleIn { from { opacity:0; transform:scale(0.9); } to { opacity:1; transform:scale(1); } }
        
        .toast { animation: slideInRight 0.4s ease; }
        @keyframes slideInRight { from { transform: translateX(100%); opacity:0; } to { transform: translateX(0); opacity:1; } }
        
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
                <a href="orders.php" class="sidebar-link active flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-300 hover:text-white">
                    <i class="fas fa-shopping-cart w-5"></i><span>Orders</span>
                    <?php if ($pendingCount > 0): ?>
                    <span class="ml-auto bg-amber-500 text-white text-xs rounded-full px-2 py-0.5"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="users.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
                    <i class="fas fa-users w-5"></i><span>Users</span>
                </a>
                <a href="payments.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white">
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
                        Manage <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Orders</span>
                    </h1>
                    <p class="text-slate-500 mt-1">Showing <strong><?php echo $totalDisplayed; ?></strong> orders · <span class="text-amber-600"><?php echo $pendingCount; ?> pending</span></p>
                </div>
            </div>

            <?php if (isset($success)): ?>
            <div class="toast fixed top-20 right-6 z-50 bg-green-500 text-white px-5 py-3 rounded-xl shadow-lg flex items-center" id="successToast">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
                <button onclick="this.parentElement.remove()" class="ml-3 text-white/80 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <script>setTimeout(() => { const t = document.getElementById('successToast'); if(t) t.remove(); }, 4000);</script>
            <?php endif; ?>

            <!-- Orders Table -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden animate-scale-in">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1200px]">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="px-5 py-4">Order #</th>
                                <th class="px-5 py-4">Customer</th>
                                <th class="px-5 py-4">Service</th>
                                <th class="px-5 py-4">Amount</th>
                                <th class="px-5 py-4">Payment</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4">Rider</th>
                                <th class="px-5 py-4">Est. Delivery</th>
                                <th class="px-5 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($orders as $order): 
                                $statusColors = [
                                    'pending'=>'amber','confirmed'=>'blue','processing'=>'indigo','ready'=>'emerald',
                                    'in_transit'=>'cyan','delivered'=>'green','completed'=>'green','cancelled'=>'red'
                                ];
                                $color = $statusColors[$order['status']] ?? 'slate';
                                $payColor = ($order['payment_status'] === 'completed') ? 'green' : (($order['payment_status'] === 'pending') ? 'amber' : 'slate');
                            ?>
                            <tr class="order-row">
                                <!-- Order Number -->
                                <td class="px-5 py-4">
                                    <button onclick="openOrderDetail(<?php echo $order['id']; ?>)" class="font-semibold text-blue-600 hover:text-blue-800 transition-colors text-sm">
                                        <?php echo htmlspecialchars($order['order_number']); ?>
                                    </button>
                                </td>
                                
                                <!-- Customer -->
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    <p class="text-xs text-slate-400"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                                </td>
                                
                                <!-- Service -->
                                <td class="px-5 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($order['service_name']); ?></td>
                                
                                <!-- Amount -->
                                <td class="px-5 py-4 text-sm font-semibold text-slate-800">UGX <?php echo number_format($order['total_amount']); ?></td>
                                
                                <!-- Payment -->
                                <td class="px-5 py-4">
                                    <span class="status-badge px-2.5 py-1 bg-<?php echo $payColor; ?>-100 text-<?php echo $payColor; ?>-700 rounded-full text-xs font-semibold whitespace-nowrap">
                                        <?php echo $order['payment_status'] ? ucfirst($order['payment_status']) : 'N/A'; ?>
                                    </span>
                                </td>
                                
                                <!-- Status -->
                                <td class="px-5 py-4">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="form-select-sm px-2 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold focus:outline-none bg-<?php echo $color; ?>-50 text-<?php echo $color; ?>-700 cursor-pointer" onchange="this.form.submit()">
                                            <option value="">Change</option>
                                            <option value="pending" <?php echo $order['status']==='pending'?'selected':''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $order['status']==='confirmed'?'selected':''; ?>>Confirmed</option>
                                            <option value="processing" <?php echo $order['status']==='processing'?'selected':''; ?>>Processing</option>
                                            <option value="ready" <?php echo $order['status']==='ready'?'selected':''; ?>>Ready</option>
                                            <option value="in_transit" <?php echo $order['status']==='in_transit'?'selected':''; ?>>In Transit</option>
                                            <option value="delivered" <?php echo $order['status']==='delivered'?'selected':''; ?>>Delivered</option>
                                            <option value="completed" <?php echo $order['status']==='completed'?'selected':''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $order['status']==='cancelled'?'selected':''; ?>>Cancelled</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                
                                <!-- Rider -->
                                <td class="px-5 py-4">
                                    <form method="POST" class="flex items-center gap-1">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="rider_id" class="form-select-sm px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none" style="width:120px;">
                                            <option value="">Select</option>
                                            <?php foreach ($riders as $rider): ?>
                                            <option value="<?php echo $rider['id']; ?>" <?php echo (isset($order['delivery_person_id']) && $order['delivery_person_id']==$rider['id'])?'selected':''; ?>>
                                                <?php echo htmlspecialchars($rider['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="assign_rider" class="btn-action px-2 py-1.5 bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold hover:bg-blue-600 hover:text-white">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                    </form>
                                    <?php if ($order['rider_name']): ?>
                                    <p class="text-xs text-green-600 mt-1"><?php echo htmlspecialchars($order['rider_name']); ?></p>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Est. Delivery -->
                                <td class="px-5 py-4">
                                    <form method="POST" class="flex items-center gap-1">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="date" name="estimated_delivery" value="<?php echo $order['estimated_delivery'] ?? ''; ?>" class="form-select-sm px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none" style="width:130px;" onchange="this.form.submit()">
                                        <input type="hidden" name="update_date" value="1">
                                    </form>
                                </td>
                                
                                <!-- Actions -->
                                <td class="px-5 py-4 text-center">
                                    <button onclick="openOrderDetail(<?php echo $order['id']; ?>)" class="btn-action px-3 py-1.5 bg-white border-2 border-blue-500 text-blue-600 rounded-lg text-xs font-semibold hover:bg-blue-600 hover:text-white">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Footer -->
                <div class="px-6 py-3 bg-slate-50 border-t border-gray-100 flex justify-between items-center">
                    <span class="text-xs text-slate-500">Total: <strong><?php echo $totalDisplayed; ?></strong> orders</span>
                    <span class="text-xs text-slate-400">Click <strong>View</strong> to see order details</span>
                </div>
            </div>
        </main>
    </div>

    <!-- ORDER DETAIL MODAL -->
    <div id="orderDetailModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4 modal-overlay">
        <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center rounded-t-2xl z-10">
                <h3 class="text-lg font-poppins font-bold text-slate-900"><i class="fas fa-receipt mr-2 text-blue-600"></i>Order Details</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 rounded-full w-8 h-8 flex items-center justify-center hover:bg-gray-100 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6" id="orderDetailContent">
                <div class="text-center py-8">
                    <div class="w-10 h-10 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto"></div>
                    <p class="text-slate-500 mt-3">Loading details...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openOrderDetail(orderId) {
            document.getElementById('orderDetailModal').classList.remove('hidden');
            document.getElementById('orderDetailContent').innerHTML = `
                <div class="text-center py-8">
                    <div class="w-10 h-10 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto"></div>
                    <p class="text-slate-500 mt-3">Loading details...</p>
                </div>`;
            
            fetch(`get_order_details.php?id=${orderId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('orderDetailContent').innerHTML = `<div class="text-red-500 text-center py-4">${data.error}</div>`;
                        return;
                    }
                    const o = data.order;
                    const customization = data.customization?.details || 'No customization details provided.';
                    const filesHtml = data.files && data.files.length > 0 
                        ? data.files.map(f => `<a href="${f.path}" target="_blank" class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3 hover:bg-blue-50 transition-colors"><span><i class="fas fa-file text-blue-500 mr-2"></i>${f.name}</span><span class="text-xs text-slate-500">${f.size}</span></a>`).join('')
                        : '<p class="text-slate-400 text-sm">No files uploaded.</p>';
                    
                    document.getElementById('orderDetailContent').innerHTML = `
                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <h4 class="font-semibold text-slate-900 mb-3 text-sm uppercase tracking-wider">Order Info</h4>
                                <div class="space-y-2 text-sm">
                                    <p><strong>Order #:</strong> ${o.order_number}</p>
                                    <p><strong>Service:</strong> ${o.service_name}</p>
                                    <p><strong>Quantity:</strong> ${o.quantity}</p>
                                    <p><strong>Unit Price:</strong> UGX ${o.unit_price}</p>
                                    <p><strong>Total:</strong> <span class="font-bold text-blue-600">UGX ${o.total_amount}</span></p>
                                    <p><strong>Date:</strong> ${o.created_at}</p>
                                    <p><strong>Est. Delivery:</strong> ${o.estimated_delivery}</p>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-900 mb-3 text-sm uppercase tracking-wider">Customer & Delivery</h4>
                                <div class="space-y-2 text-sm">
                                    <p><strong>Customer:</strong> ${o.customer_name}</p>
                                    <p><strong>Email:</strong> ${o.customer_email}</p>
                                    <p><strong>Phone:</strong> ${o.customer_phone || 'N/A'}</p>
                                    <p><strong>Method:</strong> ${o.delivery_method.replace(/_/g,' ')}</p>
                                    <p><strong>Address:</strong> ${o.delivery_address}</p>
                                    <p><strong>Notes:</strong> ${o.delivery_notes || 'None'}</p>
                                    <p><strong>Rider:</strong> ${o.rider_name}</p>
                                </div>
                            </div>
                        </div>
                        <hr class="mb-4">
                        <h4 class="font-semibold text-slate-900 mb-3 text-sm uppercase tracking-wider">Customization</h4>
                        <div class="bg-gray-50 rounded-xl p-4 text-sm text-slate-700 mb-4">${customization}</div>
                        <h4 class="font-semibold text-slate-900 mb-3 text-sm uppercase tracking-wider">Files</h4>
                        <div class="space-y-2">${filesHtml}</div>
                    `;
                })
                .catch(() => {
                    document.getElementById('orderDetailContent').innerHTML = '<div class="text-red-500 text-center py-4">Failed to load details.</div>';
                });
        }
        
        function closeModal() { document.getElementById('orderDetailModal').classList.add('hidden'); }
        document.getElementById('orderDetailModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    </script>
</body>
</html>