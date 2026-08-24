<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || Session::get('user_role') !== 'delivery') {
    header('Location: ../login.php');
    exit;
}

$riderId = Session::get('user_id');
$database = new Database();
$db = $database->getConnection();

// Handle delivery status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_delivery'])) {
    $deliveryId = (int)$_POST['delivery_id'];
    $newStatus = $_POST['delivery_status'];
    $undeliveredReason = $_POST['undelivered_reason'] ?? '';
    
    if ($newStatus === 'undelivered' && empty($undeliveredReason)) {
        $error = "Please provide a reason for failed delivery.";
    } else {
        $stmt = $db->prepare("UPDATE deliveries SET status = ?, updated_at = NOW() WHERE id = ? AND delivery_person_id = ?");
        $stmt->execute([$newStatus, $deliveryId, $riderId]);
        
        if ($newStatus === 'undelivered') {
            $stmt = $db->prepare("UPDATE deliveries SET delivery_notes = ? WHERE id = ?");
            $stmt->execute([$undeliveredReason, $deliveryId]);
            $del = $db->prepare("SELECT order_id FROM deliveries WHERE id = ?");
            $del->execute([$deliveryId]);
            $orderId = $del->fetchColumn();
            $stmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$orderId]);
            $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, created_by) VALUES (?, 'cancelled', ?, ?)");
            $stmt->execute([$orderId, "Undelivered: " . $undeliveredReason, $riderId]);
            $stmt = $db->prepare("DELETE FROM rider_locations WHERE delivery_id = ?");
            $stmt->execute([$deliveryId]);
        }
        
        if ($newStatus === 'delivered') {
            $del = $db->prepare("SELECT order_id FROM deliveries WHERE id = ?");
            $del->execute([$deliveryId]);
            $orderId = $del->fetchColumn();
            $stmt = $db->prepare("UPDATE deliveries SET actual_delivery_time = NOW() WHERE id = ?");
            $stmt->execute([$deliveryId]);
            $stmt = $db->prepare("UPDATE orders SET status = 'delivered' WHERE id = ?");
            $stmt->execute([$orderId]);
            $stmt = $db->prepare("DELETE FROM rider_locations WHERE delivery_id = ?");
            $stmt->execute([$deliveryId]);
            
            $delData = $db->prepare("SELECT created_at FROM deliveries WHERE id = ?");
            $delData->execute([$deliveryId]);
            $delivery = $delData->fetch(PDO::FETCH_ASSOC);
            if ($delivery) {
                $startTime = strtotime($delivery['created_at']);
                $actualTime = time();
                $durationMinutes = ($actualTime - $startTime) / 60;
                $rating = $durationMinutes <= 30 ? 5 : ($durationMinutes <= 60 ? 4 : ($durationMinutes <= 120 ? 3 : ($durationMinutes <= 180 ? 2 : 1)));
                $stmt = $db->prepare("UPDATE deliveries SET rating = ? WHERE id = ?");
                $stmt->execute([$rating, $deliveryId]);
            }
        }
        
        if ($newStatus === 'in_transit') {
            $del = $db->prepare("SELECT order_id FROM deliveries WHERE id = ?");
            $del->execute([$deliveryId]);
            $orderId = $del->fetchColumn();
            $stmt = $db->prepare("UPDATE orders SET status = 'in_transit' WHERE id = ?");
            $stmt->execute([$orderId]);
        }
        
        $success = "Delivery status updated successfully!";
    }
}

// Handle Go Live
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['go_live'])) {
    $deliveryId = (int)$_POST['delivery_id'];
    $lat = (float)$_POST['latitude'];
    $lng = (float)$_POST['longitude'];
    
    $stmt = $db->prepare("UPDATE deliveries SET status = 'in_transit', updated_at = NOW() WHERE id = ? AND delivery_person_id = ?");
    $stmt->execute([$deliveryId, $riderId]);
    
    $del = $db->prepare("SELECT order_id FROM deliveries WHERE id = ?");
    $del->execute([$deliveryId]);
    $orderId = $del->fetchColumn();
    $stmt = $db->prepare("UPDATE orders SET status = 'in_transit' WHERE id = ?");
    $stmt->execute([$orderId]);
    
    $check = $db->prepare("SELECT id FROM rider_locations WHERE delivery_id = ? AND rider_id = ?");
    $check->execute([$deliveryId, $riderId]);
    if ($check->fetch()) {
        $stmt = $db->prepare("UPDATE rider_locations SET latitude = ?, longitude = ?, updated_at = NOW() WHERE delivery_id = ? AND rider_id = ?");
        $stmt->execute([$lat, $lng, $deliveryId, $riderId]);
    } else {
        $stmt = $db->prepare("INSERT INTO rider_locations (delivery_id, rider_id, latitude, longitude) VALUES (?, ?, ?, ?)");
        $stmt->execute([$deliveryId, $riderId, $lat, $lng]);
    }
    
    $success = "You are now LIVE! Customer can track your movement.";
}

// Handle address update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
    $deliveryId = (int)$_POST['delivery_id'];
    $newAddress = trim($_POST['new_address']);
    $reason = trim($_POST['address_reason']);
    
    if (!empty($newAddress)) {
        $del = $db->prepare("SELECT order_id, delivery_address as old_address FROM deliveries WHERE id = ? AND delivery_person_id = ?");
        $del->execute([$deliveryId, $riderId]);
        $delData = $del->fetch(PDO::FETCH_ASSOC);
        
        if ($delData) {
            $stmt = $db->prepare("UPDATE deliveries SET delivery_address = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newAddress, $deliveryId]);
            $stmt = $db->prepare("UPDATE orders SET delivery_address = ? WHERE id = ?");
            $stmt->execute([$newAddress, $delData['order_id']]);
            $stmt = $db->prepare("INSERT INTO delivery_address_updates (delivery_id, old_address, new_address, reason, updated_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$deliveryId, $delData['old_address'], $newAddress, $reason, $riderId]);
            $success = "Delivery address updated successfully!";
        }
    }
}

// Stats
$statsStmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as delivered, SUM(CASE WHEN status='undelivered' THEN 1 ELSE 0 END) as undelivered, SUM(CASE WHEN status='in_transit' THEN 1 ELSE 0 END) as in_transit, SUM(CASE WHEN status IN ('assigned','order_received','picked_up') THEN 1 ELSE 0 END) as pending, AVG(CASE WHEN rating>0 THEN rating END) as avg_rating FROM deliveries WHERE delivery_person_id = ?");
$statsStmt->execute([$riderId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
$successRate = $stats['total'] > 0 ? round(($stats['delivered'] / $stats['total']) * 100) : 0;

// Deliveries
$deliveries = $db->prepare("SELECT d.*, o.order_number, o.status AS order_status, o.total_amount, u.name AS customer_name, u.phone AS customer_phone, u.address AS customer_address, s.name AS service_name FROM deliveries d JOIN orders o ON d.order_id = o.id JOIN users u ON o.user_id = u.id JOIN services s ON o.service_id = s.id WHERE d.delivery_person_id = ? ORDER BY d.status='delivered' ASC, d.status='undelivered' ASC, d.created_at DESC");
$deliveries->execute([$riderId]);
$deliveries = $deliveries->fetchAll(PDO::FETCH_ASSOC);

$activeDelivery = null;
foreach ($deliveries as $d) { if ($d['status'] === 'in_transit') { $activeDelivery = $d; break; } }

// Chart
$chartStmt = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as delivered, SUM(CASE WHEN status='undelivered' THEN 1 ELSE 0 END) as failed FROM deliveries WHERE delivery_person_id=? AND created_at>=DATE_SUB(NOW(),INTERVAL 14 DAY) GROUP BY DATE(created_at) ORDER BY date ASC");
$chartStmt->execute([$riderId]);
$chartData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{50:'#eff6ff',600:'#2563eb',700:'#1d4ed8'},accent:{500:'#f97316',600:'#ea580c'}},fontFamily:{poppins:['Poppins','sans-serif'],inter:['Inter','sans-serif']}}}}</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .table-row{transition:all .25s ease}.table-row:hover{background:linear-gradient(90deg,#eff6ff 0%,transparent 100%);transform:translateX(2px)}
        .stat-card{transition:all .3s cubic-bezier(.25,.46,.45,.94)}.stat-card:hover{transform:translateY(-4px);box-shadow:0 15px 35px rgba(0,0,0,.1)}
        #liveMap{height:400px;border-radius:1rem;z-index:1}.pulse-dot{animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.5)}50%{box-shadow:0 0 0 15px rgba(34,197,94,0)}}
        .modal-overlay{animation:fadeIn .3s ease}@keyframes fadeIn{from{opacity:0}to{opacity:1}}
        .modal-content{animation:slideUp .4s cubic-bezier(.25,.46,.45,.94)}@keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
    </style>
</head>
<body class="font-inter antialiased bg-gray-50 min-h-screen">

<header class="bg-white shadow-sm border-b sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <a href="../index.php" class="w-10 h-10 bg-gradient-to-br from-blue-600 to-orange-500 rounded-xl flex items-center justify-center text-white text-lg shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105"><i class="fas fa-motorcycle"></i></a>
            <div><h2 class="font-poppins font-bold text-lg text-gray-900">Rider Panel</h2><p class="text-xs text-gray-500"><?php echo htmlspecialchars(Session::get('user_name')); ?> · ⭐ <?php echo round($stats['avg_rating'] ?? 0, 1); ?></p></div>
        </div>
        <div class="flex items-center gap-3">
            <a href="../index.php" class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-blue-50 hover:text-blue-600 transition-all text-sm"><i class="fas fa-home mr-1"></i> Home</a>
            <a href="../logout.php" class="px-4 py-2 border-2 border-red-300 text-red-600 font-semibold rounded-xl hover:bg-red-50 transition-colors text-sm"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
        </div>
    </div>
</header>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php if (isset($success)): ?><div class="bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl mb-6"><i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?></div><?php endif; ?>
    <?php if (isset($error)): ?><div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl mb-6"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?></div><?php endif; ?>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="stat-card bg-white rounded-2xl p-5 text-center shadow-sm border border-gray-100"><p class="text-3xl font-bold text-blue-600"><?php echo $stats['total']; ?></p><p class="text-gray-500 text-xs mt-1 font-medium">Total Assigned</p></div>
        <div class="stat-card bg-white rounded-2xl p-5 text-center shadow-sm border border-gray-100"><p class="text-3xl font-bold text-green-600"><?php echo $stats['delivered']; ?></p><p class="text-gray-500 text-xs mt-1 font-medium">Delivered</p></div>
        <div class="stat-card bg-white rounded-2xl p-5 text-center shadow-sm border border-gray-100"><p class="text-3xl font-bold text-blue-500"><?php echo $stats['in_transit']; ?></p><p class="text-gray-500 text-xs mt-1 font-medium">In Transit</p></div>
        <div class="stat-card bg-white rounded-2xl p-5 text-center shadow-sm border border-gray-100"><p class="text-3xl font-bold text-amber-600"><?php echo $stats['pending']; ?></p><p class="text-gray-500 text-xs mt-1 font-medium">Pending</p></div>
        <div class="stat-card bg-white rounded-2xl p-5 text-center shadow-sm border border-gray-100"><p class="text-3xl font-bold text-red-600"><?php echo $stats['undelivered']; ?></p><p class="text-gray-500 text-xs mt-1 font-medium">Failed</p></div>
    </div>

    <div class="grid lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5"><h4 class="font-poppins font-bold text-gray-900 mb-4 text-sm">Performance</h4><div class="space-y-3"><div class="flex justify-between text-sm"><span class="text-gray-500">Success Rate</span><span class="font-bold text-green-600"><?php echo $successRate; ?>%</span></div><div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-green-500 h-2 rounded-full" style="width:<?php echo $successRate; ?>%"></div></div><div class="flex justify-between text-sm"><span class="text-gray-500">Avg Rating</span><span class="font-bold text-yellow-600"><?php echo round($stats['avg_rating']??0,1); ?> ⭐</span></div></div></div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5"><h4 class="font-poppins font-bold text-gray-900 mb-3 text-sm">14-Day Trend</h4><canvas id="trendChart" height="150"></canvas></div>
        </div>

        <div class="lg:col-span-3">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-xl font-poppins font-bold text-gray-900 mb-6"><i class="fas fa-clipboard-list mr-2 text-blue-600"></i> My Deliveries</h3>
                <?php if (empty($deliveries)): ?><div class="text-center py-12"><i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i><p class="text-gray-500">No deliveries assigned yet.</p></div>
                <?php else: ?><div class="overflow-x-auto"><table class="w-full min-w-[800px]"><thead><tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b"><th class="pb-3">Order #</th><th class="pb-3">Customer</th><th class="pb-3">Address</th><th class="pb-3">Status</th><th class="pb-3">Action</th></tr></thead>
                <tbody class="divide-y divide-gray-100"><?php foreach ($deliveries as $del): $sc=match($del['status']){'delivered'=>'green','undelivered'=>'red','in_transit'=>'blue','picked_up'=>'amber','order_received'=>'indigo',default=>'gray'};$active=!in_array($del['status'],['delivered','undelivered']); ?>
                <tr class="table-row"><td class="py-3 font-semibold text-blue-600 text-sm"><?php echo htmlspecialchars($del['order_number']); ?></td><td class="py-3 text-sm"><p class="font-medium"><?php echo htmlspecialchars($del['customer_name']); ?></p><p class="text-gray-400 text-xs"><?php echo htmlspecialchars($del['customer_phone']); ?></p></td><td class="py-3 text-sm text-gray-500 max-w-[180px] truncate"><?php echo htmlspecialchars(substr($del['delivery_address']??$del['customer_address']??'N/A',0,40)); ?><?php if($active): ?><button onclick="openAddressModal(<?php echo $del['id']; ?>,'<?php echo addslashes($del['delivery_address']??$del['customer_address']??''); ?>')" class="text-blue-500 hover:text-blue-700 ml-1"><i class="fas fa-edit text-xs"></i></button><?php endif; ?></td><td class="py-3"><span class="px-2.5 py-1 bg-<?php echo $sc; ?>-100 text-<?php echo $sc; ?>-700 rounded-full text-xs font-semibold whitespace-nowrap"><?php echo ucfirst(str_replace('_',' ',$del['status'])); ?></span></td><td class="py-3"><?php if($active): ?><div class="flex items-center gap-1"><form method="POST" class="flex items-center gap-1"><input type="hidden" name="delivery_id" value="<?php echo $del['id']; ?>"><select name="delivery_status" class="px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:border-blue-500 outline-none" onchange="handleStatus(this,<?php echo $del['id']; ?>,'<?php echo addslashes($del['delivery_address']??$del['customer_address']??''); ?>','<?php echo addslashes($del['order_number']); ?>')"><option value="">Update</option><option value="order_received">Order Received</option><option value="picked_up">Picked Up</option><option value="in_transit">In Transit</option><option value="delivered">Delivered</option><option value="undelivered">Undelivered</option></select><input type="hidden" name="update_delivery" value="1"></form><button onclick="openGoLiveModal(<?php echo $del['id']; ?>,'<?php echo addslashes($del['delivery_address']??$del['customer_address']??''); ?>','<?php echo addslashes($del['order_number']); ?>')" class="px-2 py-1.5 bg-green-600 text-white rounded-lg text-xs font-semibold hover:bg-green-700"><i class="fas fa-broadcast-tower"></i> Live</button></div><?php endif; ?></td></tr>
                <?php endforeach; ?></tbody></table></div><?php endif; ?>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex justify-between items-center mb-4"><h3 class="text-xl font-poppins font-bold text-gray-900"><i class="fas fa-map-marked-alt mr-2 text-green-600"></i>Live Navigation Map</h3><span id="mapStatus" class="px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-semibold">Offline</span></div>
                <div id="liveMap"></div>
                <div class="flex justify-between items-center mt-3 text-sm"><span class="text-gray-500">Active: <strong id="activeDeliveryLabel" class="text-blue-600">None</strong></span><span class="text-gray-500"><span id="distanceDisplay">--</span> · <span id="etaDisplay">--</span></span></div>
            </div>
        </div>
    </div>
</div>

<!-- GO LIVE MODAL -->
<div id="goLiveModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4 modal-overlay">
    <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="bg-green-600 text-white px-6 py-4 flex justify-between items-center"><h4 class="font-poppins font-bold"><i class="fas fa-broadcast-tower mr-2"></i>Go Live - Start Delivery</h4><button onclick="closeModal('goLiveModal')" class="text-white hover:bg-white/20 rounded-full w-8 h-8 flex items-center justify-center"><i class="fas fa-times"></i></button></div>
        <div class="p-4"><p class="text-sm text-gray-600 mb-3">Confirm your location to go live. Customer can track you in real-time.</p><div id="goLiveMap" style="height:250px;border-radius:0.75rem;"></div><p class="text-sm mt-3"><strong>Destination:</strong> <span id="goLiveAddress"></span></p><button onclick="openAddressModalFromLive()" class="text-blue-600 text-sm hover:underline mt-1"><i class="fas fa-edit"></i> Change address</button><input type="hidden" id="goLiveDeliveryId"><input type="hidden" id="goLiveLat"><input type="hidden" id="goLiveLng"><div class="flex justify-end gap-3 mt-4"><button onclick="closeModal('goLiveModal')" class="px-5 py-2.5 border border-gray-300 rounded-xl text-gray-700 text-sm">Cancel</button><button onclick="confirmGoLive()" class="px-5 py-2.5 bg-green-600 text-white rounded-xl font-medium hover:bg-green-700 text-sm"><i class="fas fa-broadcast-tower mr-1"></i> Go Live Now</button></div></div>
    </div>
</div>

<!-- UNDELIVERED MODAL -->
<div id="undeliveredModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4 modal-overlay">
    <div class="modal-content bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl"><h4 class="text-lg font-poppins font-bold text-gray-900 mb-4">Reason for Failed Delivery</h4><textarea id="undeliveredReason" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-red-500 outline-none resize-none text-sm" rows="3" placeholder="Explain why delivery failed..."></textarea><input type="hidden" id="currentDeliveryId"><div class="flex justify-end gap-3 mt-5"><button onclick="closeModal('undeliveredModal')" class="px-5 py-2.5 border border-gray-300 rounded-xl text-gray-700 text-sm">Cancel</button><button onclick="submitUndelivered()" class="px-5 py-2.5 bg-red-600 text-white rounded-xl text-sm">Submit</button></div></div>
</div>

<!-- ADDRESS MODAL -->
<div id="addressModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4 modal-overlay">
    <div class="modal-content bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl"><h4 class="text-lg font-poppins font-bold text-gray-900 mb-4"><i class="fas fa-edit mr-2 text-blue-600"></i>Update Delivery Address</h4><form method="POST"><input type="hidden" name="delivery_id" id="addrDeliveryId"><label class="block text-sm font-semibold text-gray-700 mb-2">New Address</label><textarea name="new_address" id="newAddress" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none resize-none text-sm" rows="3" required></textarea><label class="block text-sm font-semibold text-gray-700 mb-2 mt-3">Reason for Change</label><input type="text" name="address_reason" id="addressReason" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 outline-none text-sm" placeholder="Customer requested new location..."><div class="flex justify-end gap-3 mt-5"><button type="button" onclick="closeModal('addressModal')" class="px-5 py-2.5 border border-gray-300 rounded-xl text-gray-700 text-sm">Cancel</button><button type="submit" name="update_address" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm">Update Address</button></div></form></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ===== CHART =====
const chartData = <?php echo json_encode($chartData); ?>;
if (chartData.length > 0) {
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: { labels: chartData.map(d => d.date), datasets: [
            { label: 'Delivered', data: chartData.map(d => d.delivered), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4, pointRadius: 3 },
            { label: 'Failed', data: chartData.map(d => d.failed), borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.4, pointRadius: 3 }
        ]},
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
}

// ===== GLOBAL STATE =====
let liveMap, liveMarker, destinationMarker, currentRoute;
let liveTrackingInterval = null;
let activeDeliveryId = null;
let currentTrackingAddress = '';

// ===== INITIALIZE LIVE MAP =====
function initLiveMap() {
    if (liveMap) { liveMap.remove(); liveMap = null; }
    liveMarker = null; destinationMarker = null; currentRoute = null;
    liveMap = L.map('liveMap').setView([0.3476, 32.5825], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(liveMap);
}
initLiveMap();

// ===== CHECK ACTIVE DELIVERY ON LOAD =====
<?php if ($activeDelivery): ?>
(function() {
    startLiveTracking(
        <?php echo $activeDelivery['id']; ?>,
        '<?php echo addslashes($activeDelivery['delivery_address'] ?? $activeDelivery['customer_address'] ?? ''); ?>',
        '<?php echo addslashes($activeDelivery['order_number']); ?>'
    );
})();
<?php endif; ?>

// ===== STOP TRACKING =====
function stopLiveTracking() {
    if (liveTrackingInterval) { clearInterval(liveTrackingInterval); liveTrackingInterval = null; }
    if (currentRoute && currentRoute.line) { liveMap.removeLayer(currentRoute.line); currentRoute = null; }
    if (liveMarker) { liveMap.removeLayer(liveMarker); liveMarker = null; }
    if (destinationMarker) { liveMap.removeLayer(destinationMarker); destinationMarker = null; }
    activeDeliveryId = null; currentTrackingAddress = '';
    document.getElementById('mapStatus').textContent = 'Offline';
    document.getElementById('mapStatus').className = 'px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-semibold';
    document.getElementById('activeDeliveryLabel').textContent = 'None';
    document.getElementById('distanceDisplay').textContent = '--';
    document.getElementById('etaDisplay').textContent = '--';
}

// ===== GECODE AND DRAW ROUTE =====
function geocodeAndDrawRoute(map, startLat, startLng, address) {
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`)
        .then(r => r.json()).then(data => {
            if (data.length > 0) {
                const dLat = parseFloat(data[0].lat), dLng = parseFloat(data[0].lon);
                const dIcon = L.divIcon({ html: '<i class="fas fa-map-pin" style="font-size:28px;color:#ef4444;"></i>', iconSize: [30,30], iconAnchor: [15,30] });
                if (destinationMarker) destinationMarker.setLatLng([dLat, dLng]);
                else destinationMarker = L.marker([dLat, dLng], { icon: dIcon }).addTo(map).bindPopup('<strong>Destination</strong><br>' + address);
                destinationMarker.setPopupContent('<strong>Destination</strong><br>' + address);
                drawRoadRoute(map, startLat, startLng, dLat, dLng);
                map.fitBounds(L.latLngBounds([[startLat, startLng], [dLat, dLng]]), { padding: [50,50] });
            }
        });
}

// ===== DRAW ROAD ROUTE (OSRM) =====
function drawRoadRoute(map, startLat, startLng, destLat, destLng) {
    if (currentRoute && currentRoute.line) { map.removeLayer(currentRoute.line); currentRoute = null; }
    fetch(`https://router.project-osrm.org/route/v1/driving/${startLng},${startLat};${destLng},${destLat}?overview=full&geometries=geojson&steps=true`)
        .then(r => r.json()).then(data => {
            if (data.code === 'Ok' && data.routes.length > 0) {
                const coords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                const line = L.polyline(coords, { color: '#2563eb', weight: 6, opacity: 0.8, lineCap: 'round', lineJoin: 'round' }).addTo(map);
                currentRoute = { line: line, distance: (data.routes[0].distance / 1000).toFixed(1), duration: Math.round(data.routes[0].duration / 60) };
                document.getElementById('etaDisplay').textContent = currentRoute.duration + ' min';
                document.getElementById('distanceDisplay').textContent = currentRoute.distance + ' km';
            }
        });
}

// ===== START LIVE TRACKING =====
function startLiveTracking(deliveryId, address, orderNumber) {
    stopLiveTracking();
    activeDeliveryId = deliveryId;
    currentTrackingAddress = address;
    document.getElementById('mapStatus').textContent = 'Live';
    document.getElementById('mapStatus').className = 'px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold pulse-dot';
    document.getElementById('activeDeliveryLabel').textContent = orderNumber;
    liveMap.setView([0.3476, 32.5825], 13);

    function updatePosition() {
        if (!activeDeliveryId) return;
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                const lat = pos.coords.latitude, lng = pos.coords.longitude;
                fetch('../api/rider/update-location.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ delivery_id: activeDeliveryId, latitude: lat, longitude: lng }) });
                const rIcon = L.divIcon({ html: '<i class="fas fa-motorcycle" style="font-size:28px;color:#2563eb;"></i>', className: 'pulse-dot', iconSize: [30,30], iconAnchor: [15,15] });
                if (liveMarker) liveMarker.setLatLng([lat, lng]); else liveMarker = L.marker([lat, lng], { icon: rIcon }).addTo(liveMap);
                liveMarker.setPopupContent('Rider<br>Updated: ' + new Date().toLocaleTimeString());
                if (currentTrackingAddress && currentTrackingAddress !== 'No address provided') {
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(currentTrackingAddress)}&limit=1`)
                        .then(r => r.json()).then(data => {
                            if (data.length > 0) {
                                const dLat = parseFloat(data[0].lat), dLng = parseFloat(data[0].lon);
                                const dIcon = L.divIcon({ html: '<i class="fas fa-map-pin" style="font-size:28px;color:#ef4444;"></i>', iconSize: [30,30], iconAnchor: [15,30] });
                                if (destinationMarker) destinationMarker.setLatLng([dLat, dLng]); else destinationMarker = L.marker([dLat, dLng], { icon: dIcon }).addTo(liveMap).bindPopup('<strong>Destination</strong><br>' + currentTrackingAddress);
                                destinationMarker.setPopupContent('<strong>Destination</strong><br>' + currentTrackingAddress);
                                drawRoadRoute(liveMap, lat, lng, dLat, dLng);
                            }
                        });
                }
                liveMap.setView([lat, lng], 15);
            }, err => console.log('Geo error:', err), { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 });
        }
    }
    updatePosition();
    liveTrackingInterval = setInterval(() => { if (activeDeliveryId) updatePosition(); }, 8000);
}

// ===== GO LIVE MODAL =====
function openGoLiveModal(deliveryId, address, orderNumber) {
    document.getElementById('goLiveDeliveryId').value = deliveryId;
    document.getElementById('goLiveAddress').textContent = address || 'No address provided';
    document.getElementById('goLiveModal').classList.remove('hidden');
    setTimeout(() => {
        if (window.goLiveMapInstance) { window.goLiveMapInstance.remove(); window.goLiveMapInstance = null; }
        window.goLiveMapInstance = L.map('goLiveMap').setView([0.3476, 32.5825], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(window.goLiveMapInstance);
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                const lat = pos.coords.latitude, lng = pos.coords.longitude;
                document.getElementById('goLiveLat').value = lat; document.getElementById('goLiveLng').value = lng;
                window.goLiveMapInstance.setView([lat, lng], 16);
                const rIcon = L.divIcon({ html: '<i class="fas fa-motorcycle" style="font-size:24px;color:#2563eb;"></i>', className: 'pulse-dot', iconSize: [30,30], iconAnchor: [15,15] });
                L.marker([lat, lng], { icon: rIcon }).addTo(window.goLiveMapInstance).bindPopup('You are here').openPopup();
                if (address && address !== 'No address provided') geocodeAndDrawRoute(window.goLiveMapInstance, lat, lng, address);
            }, () => { window.goLiveMapInstance.setView([0.3476, 32.5825], 13); }, { enableHighAccuracy: true });
        }
    }, 400);
}

function confirmGoLive() {
    const did = document.getElementById('goLiveDeliveryId').value;
    const lat = document.getElementById('goLiveLat').value || '0.3476';
    const lng = document.getElementById('goLiveLng').value || '32.5825';
    const form = document.createElement('form'); form.method = 'POST';
    form.innerHTML = `<input name="delivery_id" value="${did}"><input name="latitude" value="${lat}"><input name="longitude" value="${lng}"><input name="go_live" value="1">`;
    document.body.appendChild(form); form.submit();
}

// ===== HELPERS =====
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
function handleStatus(select, deliveryId, address, orderNumber) {
    const v = select.value;
    if (v === 'undelivered') { document.getElementById('currentDeliveryId').value = deliveryId; document.getElementById('undeliveredModal').classList.remove('hidden'); select.value = ''; }
    else if (v === 'in_transit') { openGoLiveModal(deliveryId, address, orderNumber); select.value = ''; }
    else if (v) { select.closest('form').submit(); }
}
function submitUndelivered() {
    const reason = document.getElementById('undeliveredReason').value;
    if (!reason.trim()) { alert('Enter a reason.'); return; }
    const did = document.getElementById('currentDeliveryId').value;
    document.querySelectorAll('form').forEach(f => {
        const inp = f.querySelector('input[name="delivery_id"]');
        if (inp && inp.value === did) { const ri = document.createElement('input'); ri.type = 'hidden'; ri.name = 'undelivered_reason'; ri.value = reason; f.appendChild(ri); f.querySelector('select[name="delivery_status"]').value = 'undelivered'; f.submit(); }
    });
}
function openAddressModal(deliveryId, addr) { document.getElementById('addrDeliveryId').value = deliveryId; document.getElementById('newAddress').value = addr || ''; document.getElementById('addressReason').value = ''; document.getElementById('addressModal').classList.remove('hidden'); }
function openAddressModalFromLive() { const did = document.getElementById('goLiveDeliveryId').value; const addr = document.getElementById('goLiveAddress').textContent; openAddressModal(did, addr); }
document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', function(e) { if (e.target === this) this.classList.add('hidden'); }));
</script>
</body>
</html>