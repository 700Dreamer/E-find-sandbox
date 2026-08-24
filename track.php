<?php
require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/Session.php';
Session::init();

$order = null; $error = null; $orderNumber = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_number'])) {
    $orderNumber = trim($_POST['order_number']);
}
if (!$order && isset($_GET['order'])) {
    $orderNumber = trim($_GET['order']);
}

if ($orderNumber) {
    $database = new Database(); $db = $database->getConnection();
    $stmt = $db->prepare("SELECT o.*, s.name as service_name, s.icon as service_icon, d.status as delivery_status, d.delivery_person_id, d.id as delivery_id, d.delivery_address as delivery_location, u.name as delivery_person_name, u.phone as delivery_person_phone FROM orders o JOIN services s ON o.service_id = s.id LEFT JOIN deliveries d ON o.id = d.order_id LEFT JOIN users u ON d.delivery_person_id = u.id WHERE o.order_number = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) $error = 'Order not found. Please check your order number and try again.';
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{50:'#eff6ff',600:'#2563eb',700:'#1d4ed8'},accent:{500:'#f97316',600:'#ea580c'}},fontFamily:{poppins:['Poppins','sans-serif'],inter:['Inter','sans-serif']},animation:{'fade-in':'fadeIn .5s ease-out','slide-up':'slideUp .5s ease-out','scale-in':'scaleIn .3s ease-out','pulse-soft':'pulseSoft 2s ease-in-out infinite','bounce-in':'bounceIn .6s ease-out'},keyframes:{fadeIn:{'0%':{opacity:'0'},'100%':{opacity:'1'}},slideUp:{'0%':{transform:'translateY(30px)',opacity:'0'},'100%':{transform:'translateY(0)',opacity:'1'}},scaleIn:{'0%':{transform:'scale(0.9)',opacity:'0'},'100%':{transform:'scale(1)',opacity:'1'}},pulseSoft:{'0%,100%':{opacity:'1',transform:'scale(1)'},'50%':{opacity:'0.8',transform:'scale(1.05)'}},bounceIn:{'0%':{transform:'scale(0.3)',opacity:'0'},'50%':{transform:'scale(1.05)'},'70%':{transform:'scale(0.9)'},'100%':{transform:'scale(1)',opacity:'1'}}}}}}</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .status-card{transition:all .4s cubic-bezier(.4,0,.2,1)}.status-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(37,99,235,.15)}
        .status-card.active{transform:scale(1.05);box-shadow:0 20px 40px rgba(37,99,235,.2)}.status-card.clickable{cursor:pointer}
        .status-card.clickable:hover{transform:scale(1.08);box-shadow:0 25px 50px rgba(34,197,94,.3)}
        .pulse-dot{animation:pulseSoft 2s ease-in-out infinite}.gradient-text{background:linear-gradient(135deg,#2563eb,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .glass-card{background:rgba(255,255,255,.8);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.3)}
        #trackingMap{height:300px;border-radius:1rem}#fullScreenMap{height:70vh;border-radius:1rem}
    </style>
</head>
<body class="font-inter antialiased bg-gradient-to-br from-gray-50 via-blue-50/30 to-white min-h-screen">

<nav class="fixed top-0 left-0 right-0 z-50 glass-card shadow-sm" id="mainNav">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="flex items-center justify-between h-16 lg:h-20">
        <a href="index.php" class="flex items-center space-x-3 group"><div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-orange-500 rounded-xl flex items-center justify-center text-white text-lg shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-105"><i class="fas fa-cubes"></i></div><span class="text-xl lg:text-2xl font-poppins font-bold"><span class="text-blue-600">E-Find</span><span class="text-orange-500"> & Soft Solutions</span></span></a>
        <div class="hidden lg:flex items-center space-x-1"><a href="index.php" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all">Home</a><a href="services.php" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all">Services</a><a href="track.php" class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg transition-all">Track Order</a><a href="contact.php" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-all">Contact</a></div>
        <div class="hidden lg:flex items-center space-x-3"><?php if(isset($_SESSION['user_id'])): ?><div class="relative group"><button class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-blue-50 hover:text-blue-600 rounded-xl transition-all"><i class="fas fa-user-circle text-lg"></i><span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span><i class="fas fa-chevron-down text-xs group-hover:rotate-180 transition-transform"></i></button><div class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-2"><a href="profile.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 mx-2 rounded-lg"><i class="fas fa-user w-5 mr-3"></i>Profile</a><a href="my-orders.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 mx-2 rounded-lg"><i class="fas fa-shopping-bag w-5 mr-3"></i>My Orders</a><?php if($_SESSION['user_role']==='admin'): ?><hr class="my-1"><a href="admin/dashboard.php" class="flex items-center px-4 py-3 text-sm text-blue-600 hover:bg-blue-50 mx-2 rounded-lg"><i class="fas fa-tachometer-alt w-5 mr-3"></i>Admin Panel</a><?php endif; ?><hr class="my-1"><a href="logout.php" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 mx-2 rounded-lg"><i class="fas fa-sign-out-alt w-5 mr-3"></i>Logout</a></div></div><?php else: ?><a href="login.php" class="px-5 py-2.5 text-sm font-semibold text-gray-700 border-2 border-gray-300 rounded-xl hover:border-blue-600 hover:text-blue-600 transition-all">Sign In</a><a href="register.php" class="px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl hover:shadow-lg transition-all">Get Started</a><?php endif; ?></div>
        <button class="lg:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg" onclick="document.getElementById('mobileMenu').classList.toggle('hidden')"><i class="fas fa-bars text-xl"></i></button>
    </div><div id="mobileMenu" class="lg:hidden hidden pb-4 space-y-1"><a href="index.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">Home</a><a href="services.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">Services</a><a href="track.php" class="block px-4 py-3 text-blue-600 bg-blue-50 rounded-lg font-medium">Track Order</a><a href="contact.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">Contact</a></div></div>
</nav>

<main class="pt-24 lg:pt-28 pb-16"><div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12" data-aos="fade-up"><div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center text-white text-3xl mx-auto mb-6 shadow-lg shadow-blue-500/25 animate-bounce-in"><i class="fas fa-map-marker-alt"></i></div><h1 class="text-4xl lg:text-5xl font-poppins font-extrabold mb-3"><span class="gradient-text">Track Your Order</span></h1><p class="text-gray-500 text-lg">Enter your order number to see real-time status updates</p></div>

    <div class="glass-card rounded-2xl p-6 mb-8 shadow-lg" data-aos="fade-up" data-aos-delay="100"><form method="POST" class="flex flex-col sm:flex-row gap-3"><div class="flex-1 relative"><span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-search"></i></span><input type="text" name="order_number" class="w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all outline-none text-lg" placeholder="e.g., EF-20240101-ABC123" value="<?php echo htmlspecialchars($orderNumber); ?>" required></div><button type="submit" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-blue-600/25 transition-all duration-300 transform hover:-translate-y-0.5 whitespace-nowrap"><i class="fas fa-search mr-2"></i>Track Order</button></form></div>

    <?php if($error): ?><div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-6 flex items-center animate-slide-down" data-aos="fade-up"><i class="fas fa-exclamation-circle text-xl mr-3"></i><span><?php echo $error; ?></span></div><?php endif; ?>

    <?php if($order): ?>
    <div class="space-y-6" data-aos="fade-up">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 animate-scale-in"><div class="flex flex-col sm:flex-row items-center justify-between gap-4"><div class="flex items-center space-x-4"><div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl flex items-center justify-center text-3xl"><?php echo htmlspecialchars($order['service_icon']??'📦'); ?></div><div><h2 class="text-xl font-poppins font-bold text-gray-900"><?php echo htmlspecialchars($order['service_name']); ?></h2><p class="text-gray-500">Order <span class="font-semibold text-blue-600">#<?php echo htmlspecialchars($order['order_number']); ?></span></p></div></div><div class="text-right"><p class="text-sm text-gray-500">Total Amount</p><p class="text-2xl font-bold text-blue-600">UGX <?php echo number_format($order['total_amount']); ?></p></div></div></div>

        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8"><h3 class="text-lg font-poppins font-bold text-gray-900 mb-8 flex items-center"><i class="fas fa-route mr-2 text-blue-600"></i>Order Progress</h3>
        <div class="relative"><?php $statuses=['pending'=>['icon'=>'fa-clock','label'=>'Order Placed','color'=>'blue'],'confirmed'=>['icon'=>'fa-check-circle','label'=>'Confirmed','color'=>'indigo'],'processing'=>['icon'=>'fa-cog fa-spin','label'=>'Processing','color'=>'purple'],'ready'=>['icon'=>'fa-box','label'=>'Ready','color'=>'teal'],'in_transit'=>['icon'=>'fa-truck','label'=>'In Transit','color'=>'green','clickable'=>true],'delivered'=>['icon'=>'fa-home','label'=>'Delivered','color'=>'emerald'],'completed'=>['icon'=>'fa-star','label'=>'Completed','color'=>'emerald']];$statusKeys=array_keys($statuses);$currentIndex=array_search($order['status'],$statusKeys);if($currentIndex===false)$currentIndex=0;$progressPercent=round(($currentIndex/(count($statusKeys)-1))*100);$isInTransit=($order['status']==='in_transit'&&isset($order['delivery_id']));?>
        <div class="hidden sm:block w-full bg-gray-200 rounded-full h-2 mb-8 overflow-hidden"><div class="h-full bg-gradient-to-r from-blue-500 to-green-500 rounded-full transition-all duration-1000 ease-out" style="width:<?php echo $progressPercent; ?>%"></div></div>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3"><?php foreach($statuses as $key=>$status): $isCompleted=array_search($key,$statusKeys)<=$currentIndex;$isCurrent=$key===$order['status'];$isClickable=isset($status['clickable'])&&$status['clickable']&&$isCurrent&&isset($order['delivery_id']);?>
        <div class="status-card <?php echo $isCurrent?'active':($isCompleted?'completed':'pending');?> <?php echo $isClickable?'clickable':'';?> bg-<?php echo $isCurrent?$status['color'].'-50 border-'.$status['color'].'-300':($isCompleted?'gray-50 border-gray-200':'gray-50 border-gray-100');?> border-2 rounded-xl p-4 text-center transition-all duration-500"<?php if($isClickable):?> onclick="openLiveTrackingMap()" title="Click to view live rider tracking"<?php endif;?>>
        <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center mb-3 <?php echo $isCurrent?'bg-'.$status['color'].'-500 text-white pulse-dot':($isCompleted?'bg-green-500 text-white':'bg-gray-300 text-gray-500');?> transition-all duration-500 relative"><i class="fas <?php echo $status['icon'];?> <?php echo $isCurrent?'text-lg':'text-sm';?>"></i><?php if($isClickable):?><span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center"><i class="fas fa-satellite-dish text-white text-xs"></i></span><?php endif;?></div>
        <p class="text-xs font-semibold <?php echo $isCurrent?'text-'.$status['color'].'-700':($isCompleted?'text-gray-700':'text-gray-400');?>"><?php echo $status['label'];?></p><?php if($isClickable):?><span class="inline-block mt-1 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-bold cursor-pointer">LIVE • Tap to view</span><?php endif;?></div><?php endforeach;?></div>
        <?php if($isInTransit):?><div class="mt-6 text-center"><button onclick="openLiveTrackingMap()" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-green-500/25 transition-all duration-300 transform hover:-translate-y-0.5"><i class="fas fa-map-marked-alt mr-2"></i>View Live Tracking Map</button></div><?php endif;?></div></div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 status-card"><h3 class="text-lg font-poppins font-bold text-gray-900 mb-6"><i class="fas fa-receipt mr-2 text-blue-600"></i>Order Details</h3><div class="space-y-4"><div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-500">Service</span><span class="font-semibold text-gray-900"><?php echo htmlspecialchars($order['service_name']);?></span></div><div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-500">Quantity</span><span class="font-semibold text-gray-900"><?php echo $order['quantity'];?></span></div><div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-500">Total Amount</span><span class="font-bold text-blue-600 text-lg">UGX <?php echo number_format($order['total_amount']);?></span></div><div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-500">Order Date</span><span class="font-semibold text-gray-900"><?php echo date('M d, Y',strtotime($order['created_at']));?></span></div><?php if($order['estimated_delivery']):?><div class="flex justify-between py-2"><span class="text-gray-500">Est. Delivery</span><span class="font-semibold text-green-600"><?php echo date('M d, Y',strtotime($order['estimated_delivery']));?></span></div><?php endif;?></div></div>
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 status-card"><h3 class="text-lg font-poppins font-bold text-gray-900 mb-6"><i class="fas fa-truck mr-2 text-orange-600"></i>Delivery Information</h3><div class="space-y-4"><div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-500">Method</span><span class="font-semibold text-gray-900"><?php echo ucwords(str_replace('_',' ',$order['delivery_method']));?></span></div><?php if($order['delivery_location']||$order['delivery_address']):?><div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-500">Address</span><span class="font-semibold text-gray-900 text-right max-w-[200px]"><?php echo htmlspecialchars($order['delivery_location']??$order['delivery_address']);?></span></div><?php endif;?><?php if($order['delivery_person_name']):?><div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-500">Rider</span><span class="font-semibold text-green-600"><i class="fas fa-motorcycle mr-1"></i><?php echo htmlspecialchars($order['delivery_person_name']);?></span></div><?php endif;?><?php if($order['delivery_person_phone']):?><div class="flex justify-between py-2"><span class="text-gray-500">Rider Contact</span><a href="tel:<?php echo htmlspecialchars($order['delivery_person_phone']);?>" class="font-semibold text-blue-600 hover:text-blue-700"><i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($order['delivery_person_phone']);?></a></div><?php endif;?></div></div>
        </div>

        <?php if($order['status']==='in_transit'&&isset($order['delivery_id'])):?>
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6" data-aos="fade-up"><div class="flex justify-between items-center mb-3"><h5 class="font-bold text-gray-900"><i class="fas fa-map-marked-alt mr-2 text-blue-600"></i>Live Rider Tracking</h5><span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold"><span class="w-2 h-2 bg-green-500 rounded-full inline-block mr-1 animate-pulse"></span>Live</span></div><div id="trackingMap"></div><div class="flex justify-between items-center mt-3 text-sm"><span class="text-gray-500"><span id="distanceDisplay">-- km</span> remaining</span><span class="text-gray-500">ETA: <strong id="etaDisplay">-- min</strong></span></div></div>
        <?php endif;?>

        <div class="flex flex-col sm:flex-row gap-4 justify-center"><a href="my-orders.php" class="px-6 py-3.5 bg-white border-2 border-blue-500 text-blue-600 font-semibold rounded-xl hover:bg-blue-600 hover:text-white transition-all duration-300 text-center"><i class="fas fa-list mr-2"></i>View All Orders</a><a href="contact.php" class="px-6 py-3.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all duration-300 text-center"><i class="fas fa-headset mr-2"></i>Need Help? Contact Support</a></div>
    </div>
    <?php endif;?>
</div></main>

<?php if($order&&$order['status']==='in_transit'&&isset($order['delivery_id'])):?>
<div id="liveMapModal" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4"><div class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden animate-scale-in"><div class="flex items-center justify-between p-6 border-b bg-gradient-to-r from-green-500 to-green-600 text-white rounded-t-3xl"><div><h5 class="text-xl font-poppins font-bold"><i class="fas fa-satellite-dish mr-2"></i>Live Rider Tracking</h5><p class="text-green-100 text-sm">Order #<?php echo htmlspecialchars($order['order_number']);?> • Rider: <?php echo htmlspecialchars($order['delivery_person_name']??'Assigned');?></p></div><button onclick="document.getElementById('liveMapModal').classList.add('hidden')" class="text-white hover:bg-white/20 rounded-full w-10 h-10 flex items-center justify-center"><i class="fas fa-times text-xl"></i></button></div><div class="p-0 relative"><div id="fullScreenMap"></div><div class="absolute bottom-4 left-4 right-4 bg-white rounded-2xl shadow-lg p-4 z-[1000]"><div class="flex justify-between items-center"><div><p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($order['delivery_person_name']??'Rider');?></p><p class="text-xs text-gray-500"><span class="w-2 h-2 bg-green-500 rounded-full mr-1 inline-block animate-pulse"></span>Moving</p></div><div class="text-right"><p class="text-xs text-gray-500">ETA</p><p class="font-bold text-green-600" id="fullEtaDisplay">Calculating...</p></div></div></div></div></div></div>
<?php endif;?>

<footer class="bg-gray-900 text-gray-400 py-8 mt-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center"><p class="text-sm">&copy; <?php echo date('Y');?> E-Find and Soft Solutions. All rights reserved.</p></div></footer>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>AOS.init({duration:800,once:true});window.addEventListener('scroll',function(){document.getElementById('mainNav').classList.toggle('shadow-lg',window.scrollY>50)});</script>

<?php if($order&&$order['status']==='in_transit'&&isset($order['delivery_id'])):?>
<script>
let trackMap,trackRiderMarker,trackDestMarker,trackRouteLine,fullMap,fullRiderMarker,fullDestMarker,fullRouteLine;
let destinationCoords=null;
const deliveryAddress=<?php echo json_encode($order['delivery_location']??$order['delivery_address']??'');?>;
const riderIcon=L.divIcon({html:'<i class="fas fa-motorcycle" style="font-size:28px;color:#2563eb;"></i>',className:'pulse-marker',iconSize:[30,30],iconAnchor:[15,15]});
const destIcon=L.divIcon({html:'<i class="fas fa-map-pin" style="font-size:28px;color:#ef4444;"></i>',iconSize:[30,30],iconAnchor:[15,30]});

// Initialize mini tracking map
trackMap=L.map('trackingMap').setView([0.3476,32.5825],13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap'}).addTo(trackMap);

if(deliveryAddress){fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(deliveryAddress)}&limit=1`).then(r=>r.json()).then(data=>{if(data.length>0){destinationCoords={lat:parseFloat(data[0].lat),lng:parseFloat(data[0].lon)};trackDestMarker=L.marker([destinationCoords.lat,destinationCoords.lng],{icon:destIcon}).addTo(trackMap).bindPopup('<strong>Destination</strong><br>'+deliveryAddress)}})}

function drawRoadRoute(map,startLat,startLng,destLat,destLng,isFull){fetch(`https://router.project-osrm.org/route/v1/driving/${startLng},${startLat};${destLng},${destLat}?overview=full&geometries=geojson`).then(r=>r.json()).then(data=>{if(data.code==='Ok'&&data.routes.length>0){const coords=data.routes[0].geometry.coordinates.map(c=>[c[1],c[0]]);const line=L.polyline(coords,{color:'#2563eb',weight:5,opacity:.7,lineCap:'round',lineJoin:'round'}).addTo(map);const dist=(data.routes[0].distance/1000).toFixed(1);const eta=Math.round(data.routes[0].duration/60);if(!isFull){document.getElementById('distanceDisplay').textContent=dist+' km';document.getElementById('etaDisplay').textContent=eta+' min'}else{document.getElementById('fullEtaDisplay').textContent=eta+' min'}}})}

function updateRiderPosition(){fetch('api/track-location.php?delivery_id=<?php echo $order['delivery_id'];?>').then(r=>r.json()).then(data=>{if(data.success&&data.data){const lat=parseFloat(data.data.latitude),lng=parseFloat(data.data.longitude);if(trackRiderMarker)trackRiderMarker.setLatLng([lat,lng]);else trackRiderMarker=L.marker([lat,lng],{icon:riderIcon}).addTo(trackMap).bindPopup('Rider');trackRiderMarker.setPopupContent('Rider<br>'+new Date().toLocaleTimeString());if(destinationCoords){if(trackRouteLine)trackMap.removeLayer(trackRouteLine);drawRoadRoute(trackMap,lat,lng,destinationCoords.lat,destinationCoords.lng,false)}trackMap.setView([lat,lng],15)}})}

updateRiderPosition();
setInterval(updateRiderPosition,8000);

// Full screen map
window.openLiveTrackingMap=function(){document.getElementById('liveMapModal').classList.remove('hidden');setTimeout(()=>{if(!fullMap){fullMap=L.map('fullScreenMap').setView([0.3476,32.5825],13);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(fullMap)}if(destinationCoords&&!fullDestMarker){fullDestMarker=L.marker([destinationCoords.lat,destinationCoords.lng],{icon:destIcon}).addTo(fullMap).bindPopup('<strong>Destination</strong><br>'+deliveryAddress)}updateFullMap();setInterval(updateFullMap,8000);setTimeout(()=>fullMap.invalidateSize(),200)},300)};

function updateFullMap(){fetch('api/track-location.php?delivery_id=<?php echo $order['delivery_id'];?>').then(r=>r.json()).then(data=>{if(data.success&&data.data&&fullMap){const lat=parseFloat(data.data.latitude),lng=parseFloat(data.data.longitude);if(fullRiderMarker)fullRiderMarker.setLatLng([lat,lng]);else fullRiderMarker=L.marker([lat,lng],{icon:riderIcon}).addTo(fullMap).bindPopup('Rider');if(destinationCoords){if(fullRouteLine)fullMap.removeLayer(fullRouteLine);drawRoadRoute(fullMap,lat,lng,destinationCoords.lat,destinationCoords.lng,true)}fullMap.setView([lat,lng],15)}})}

document.getElementById('liveMapModal').addEventListener('click',function(e){if(e.target===this)this.classList.add('hidden')});
</script>
<?php endif;?>
</body>
</html>