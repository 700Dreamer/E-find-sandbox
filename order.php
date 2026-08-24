<?php
require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/Session.php';
require_once 'includes/Auth.php';
require_once 'includes/Service.php';

Session::init();
$auth = new Auth();
$user = $auth->getCurrentUser();

if (!$user) {
    header('Location: login.php');
    exit;
}

$serviceManager = new ServiceManager();
$service = null;
if (isset($_GET['service'])) {
    $service = $serviceManager->getServiceBySlug($_GET['service']);
} elseif (isset($_GET['id'])) {
    $service = $serviceManager->getServiceById($_GET['id']);
}

if (!$service) {
    echo '<div style="text-align:center;padding:100px 20px;font-family:sans-serif;">';
    echo '<h2>Service not found</h2>';
    echo '<p>The requested service does not exist.</p>';
    echo '<a href="services.php" style="color:#2563eb;">← Back to Services</a>';
    echo '</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order <?php echo htmlspecialchars($service['name']); ?> – <?php echo APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --card-transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Card Base */
        .premium-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #f1f5f9;
            transition: var(--card-transition);
            position: relative;
            overflow: hidden;
        }
        .premium-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2563eb, #7c3aed, #f97316, #10b981, #2563eb);
            background-size: 200% 100%;
            animation: borderShimmer 3s linear infinite;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .premium-card:hover::before { transform: scaleX(1); }
        .premium-card:hover { transform: translateY(-4px); box-shadow: 0 25px 50px -12px rgba(37,99,235,0.15); border-color: #cbd5e1; }
        
        @keyframes borderShimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Service Icon Card */
        .service-icon-card {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #eff6ff, #f5f3ff);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            color: #2563eb;
            transition: var(--card-transition);
        }
        .premium-card:hover .service-icon-card {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: white;
            transform: scale(1.1) rotate(-5deg);
            box-shadow: 0 12px 30px rgba(37,99,235,0.3);
        }
        
        /* Form Inputs */
        .form-input {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }
        .form-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37,99,235,0.08);
            transform: translateY(-1px);
            outline: none;
        }
        .form-input:hover { border-color: #93c5fd; }
        
        /* Quantity Buttons */
        .qty-btn {
            transition: all 0.2s ease;
        }
        .qty-btn:hover { background: #dbeafe; color: #2563eb; transform: scale(1.05); }
        .qty-btn:active { transform: scale(0.95); }
        
        /* Upload Zone */
        .upload-zone {
            transition: all 0.4s ease;
            cursor: pointer;
            border: 2px dashed #d1d5db;
        }
        .upload-zone:hover {
            border-color: #2563eb;
            background: #eff6ff;
            transform: scale(1.01);
        }
        .upload-zone.dragover {
            border-color: #2563eb;
            background: #dbeafe;
            transform: scale(1.02);
        }
        .upload-zone i {
            transition: all 0.4s ease;
        }
        .upload-zone:hover i {
            color: #2563eb;
            transform: translateY(-5px);
        }
        
        /* File Chip */
        .file-chip {
            animation: chipIn 0.3s ease;
            transition: all 0.3s ease;
        }
        .file-chip:hover {
            background: #f1f5f9;
            transform: translateX(4px);
        }
        @keyframes chipIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Delivery Cards */
        .delivery-card {
            transition: var(--card-transition);
            cursor: pointer;
            border: 2px solid #e5e7eb;
        }
        .delivery-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
        }
        .delivery-card.selected {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 8px 25px rgba(37,99,235,0.12);
        }
        .delivery-card i { transition: all 0.4s ease; }
        .delivery-card.selected i { color: #2563eb; }
        .delivery-card:hover i { transform: scale(1.1); }
        
        /* Submit Button */
        .btn-submit {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
        }
        .btn-submit::before {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            width: 0; height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .btn-submit:hover::before { width: 300px; height: 300px; }
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(37,99,235,0.35);
        }
        .btn-submit:active:not(:disabled) { transform: scale(0.97); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
        
        /* Summary Sidebar */
        .summary-card {
            transition: var(--card-transition);
        }
        .summary-card:hover {
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .summary-item {
            transition: all 0.2s ease;
            padding: 8px 0;
            border-radius: 8px;
        }
        .summary-item:hover {
            background: #f9fafb;
            padding-left: 8px;
        }
        
        /* Modal */
        .modal-overlay {
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            animation: overlayIn 0.3s ease;
        }
        @keyframes overlayIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes modalBounceIn {
            0% { opacity: 0; transform: scale(0.8) translateY(30px); }
            60% { transform: scale(1.02) translateY(-5px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-animate { animation: modalBounceIn 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        
        .success-icon-circle {
            animation: iconPop 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.2s both;
        }
        @keyframes iconPop {
            0% { transform: scale(0); }
            60% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        /* Spinner */
        .spinner {
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Back Button */
        .back-btn {
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            transform: translateX(-4px);
            color: #2563eb;
        }
        .back-btn i {
            transition: transform 0.3s ease;
        }
        .back-btn:hover i {
            transform: translateX(-3px);
        }
        
        /* Pulse Animation */
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(37,99,235,0.4); }
            50% { box-shadow: 0 0 0 12px rgba(37,99,235,0); }
        }
        .pulse-ring { animation: pulse 2s infinite; }
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
                    <a href="services.php" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">Services</a>
                    <a href="my-orders.php" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">My Orders</a>
                    <div class="flex items-center space-x-2">
                        <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-xs font-bold"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                        <span class="text-sm text-gray-600 hidden sm:inline"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <a href="logout.php" class="text-sm text-red-500 hover:text-red-700 transition-colors"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="max-w-5xl mx-auto px-4 py-8">
        
        <!-- Back Button -->
        <a href="services.php" class="back-btn inline-flex items-center text-gray-500 mb-6 text-sm font-medium">
            <i class="fas fa-arrow-left mr-2"></i> Back to Services
        </a>

        <div class="grid lg:grid-cols-3 gap-6">
            
            <!-- ORDER FORM COLUMN -->
            <div class="lg:col-span-2 space-y-5" data-aos="fade-up">
                
                <!-- Service Info Card -->
                <div class="premium-card p-5">
                    <div class="flex items-center space-x-4">
                        <div class="service-icon-card">
                            <i class="fas <?php echo htmlspecialchars($service['icon'] ?? 'fa-cog'); ?>"></i>
                        </div>
                        <div>
                            <h2 class="font-poppins font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($service['name']); ?></h2>
                            <p class="text-sm text-gray-500">Base Price: <span class="font-bold text-blue-600">UGX <?php echo number_format($service['base_price']); ?></span></p>
                        </div>
                    </div>
                </div>

                <!-- Order Form Card -->
                <div class="premium-card p-5 lg:p-6">
                    <h3 class="font-poppins font-bold text-gray-900 mb-5 flex items-center">
                        <i class="fas fa-sliders-h mr-2 text-blue-600"></i> Customize Your Order
                    </h3>
                    
                    <form id="orderForm" enctype="multipart/form-data" class="space-y-5">
                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">

                        <!-- Quantity -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-hashtag mr-1.5 text-blue-500"></i> Quantity
                            </label>
                            <div class="flex items-center space-x-2">
                                <button type="button" onclick="changeQty(-1)" class="qty-btn w-11 h-11 rounded-xl bg-gray-100 flex items-center justify-center font-bold text-gray-600 text-lg">−</button>
                                <input type="number" id="quantity" name="quantity" min="1" value="1" class="w-16 text-center py-2.5 form-input rounded-xl font-bold text-lg" required>
                                <button type="button" onclick="changeQty(1)" class="qty-btn w-11 h-11 rounded-xl bg-gray-100 flex items-center justify-center font-bold text-gray-600 text-lg">+</button>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-pen mr-1.5 text-blue-500"></i> Describe Your Requirements
                            </label>
                            <textarea name="customization[details]" rows="4" class="w-full px-4 py-3 form-input rounded-xl resize-none text-gray-700" placeholder="E.g., size, color, text to engrave, design preferences, special instructions..."></textarea>
                        </div>

                        <!-- File Upload -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-cloud-upload-alt mr-1.5 text-blue-500"></i> Upload Design Files
                                <span class="text-gray-400 font-normal text-xs">(Optional)</span>
                            </label>
                            <div class="upload-zone rounded-2xl p-8 text-center" id="uploadArea" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-300 mb-3 block"></i>
                                <p class="font-medium text-gray-600 text-sm mb-1">Click to browse or drag files here</p>
                                <p class="text-xs text-gray-400">JPG, PNG, PDF • Max 10MB each</p>
                                <input type="file" name="design_files[]" id="fileInput" multiple accept=".jpg,.jpeg,.png,.pdf" class="hidden" onchange="updateFileList()">
                            </div>
                            <div id="fileList" class="mt-3 space-y-2"></div>
                        </div>

                        <!-- Delivery Method -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">
                                <i class="fas fa-truck mr-1.5 text-blue-500"></i> Delivery Method
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="delivery-card selected rounded-2xl p-5 text-center" id="doorCard" onclick="selectDelivery('door_delivery')">
                                    <input type="radio" name="delivery_method" value="door_delivery" checked class="hidden">
                                    <i class="fas fa-truck text-3xl mb-3 block"></i>
                                    <p class="font-semibold text-sm text-gray-900">Door Delivery</p>
                                    <p class="text-xs text-gray-400 mt-1">Delivered to you</p>
                                </div>
                                <div class="delivery-card rounded-2xl p-5 text-center" id="pickupCard" onclick="selectDelivery('pickup')">
                                    <input type="radio" name="delivery_method" value="pickup" class="hidden">
                                    <i class="fas fa-store text-3xl mb-3 block text-gray-400"></i>
                                    <p class="font-semibold text-sm text-gray-900">Store Pickup</p>
                                    <p class="text-xs text-gray-400 mt-1">Collect in person</p>
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div id="addressSection">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-map-marker-alt mr-1.5 text-blue-500"></i> Delivery Address
                            </label>
                            <textarea name="delivery_address" rows="3" class="w-full px-4 py-3 form-input rounded-xl resize-none text-gray-700" placeholder="Enter your full delivery address..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-sticky-note mr-1.5 text-blue-500"></i> Additional Notes
                                <span class="text-gray-400 font-normal text-xs">(Optional)</span>
                            </label>
                            <textarea name="delivery_notes" rows="2" class="w-full px-4 py-3 form-input rounded-xl resize-none text-gray-700" placeholder="Any special instructions..."></textarea>
                        </div>

                        <!-- Submit -->
                        <div>
                            <button type="submit" class="btn-submit w-full py-4 text-white font-semibold rounded-xl text-base relative overflow-hidden" id="submitBtn">
                                <i class="fas fa-shopping-cart mr-2"></i>
                                Place Order – UGX <span id="totalPrice"><?php echo number_format($service['base_price']); ?></span>
                            </button>
                            <div id="spinner" class="hidden text-center py-3">
                                <div class="w-10 h-10 border-4 border-blue-200 border-t-blue-600 rounded-full spinner mx-auto"></div>
                                <p class="text-sm text-gray-500 mt-2 font-medium">Processing your order...</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- SUMMARY SIDEBAR -->
            <div class="lg:col-span-1" data-aos="fade-up" data-aos-delay="150">
                <div class="premium-card summary-card p-5 sticky top-24">
                    <h3 class="font-poppins font-bold text-gray-900 mb-5 flex items-center text-lg">
                        <i class="fas fa-receipt mr-2 text-blue-600"></i> Order Summary
                    </h3>
                    
                    <div class="space-y-1 text-sm">
                        <div class="summary-item flex justify-between">
                            <span class="text-gray-500">Service</span>
                            <span class="font-medium text-right text-gray-800 max-w-[150px] truncate"><?php echo htmlspecialchars($service['name']); ?></span>
                        </div>
                        <div class="summary-item flex justify-between">
                            <span class="text-gray-500">Base Price</span>
                            <span class="font-medium">UGX <?php echo number_format($service['base_price']); ?></span>
                        </div>
                        <div class="summary-item flex justify-between">
                            <span class="text-gray-500">Quantity</span>
                            <span class="font-medium" id="sideQty">1</span>
                        </div>
                        <hr class="my-2">
                        <div class="summary-item flex justify-between text-base">
                            <span class="font-bold text-gray-900">Total</span>
                            <span class="font-bold text-blue-600 text-xl" id="sideTotal">UGX <?php echo number_format($service['base_price']); ?></span>
                        </div>
                    </div>
                    
                    <div class="mt-5 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-100">
                        <p class="text-xs text-blue-700 flex items-start">
                            <i class="fas fa-info-circle mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Final price may vary based on customization. You'll review before payment.</span>
                        </p>
                    </div>
                    
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center text-xs text-gray-500">
                            <i class="fas fa-shield-alt text-green-500 mr-2"></i> SSL Secure Checkout
                        </div>
                        <div class="flex items-center text-xs text-gray-500">
                            <i class="fas fa-credit-card text-green-500 mr-2"></i> Multiple Payment Options
                        </div>
                        <div class="flex items-center text-xs text-gray-500">
                            <i class="fas fa-truck-fast text-green-500 mr-2"></i> Real-time Tracking
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SUCCESS MODAL -->
    <div id="successModal" class="fixed inset-0 z-50 hidden modal-overlay flex items-center justify-center p-4">
        <div class="modal-animate bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 text-center">
            <div class="success-icon-circle w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-lg shadow-green-500/30">
                <i class="fas fa-check text-3xl text-white"></i>
            </div>
            <h3 class="text-2xl font-poppins font-bold text-gray-900 mb-2">Order Placed!</h3>
            <p class="text-gray-500 mb-1">Your order number:</p>
            <h2 class="text-2xl font-bold text-blue-600 mb-3 tracking-wider" id="modalOrderNum"></h2>
            <p class="text-gray-600 mb-6">Total: <strong class="text-gray-900">UGX <span id="modalTotal"></span></strong></p>
            <div class="space-y-3">
                <a href="" id="payLink" class="block w-full py-3.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-blue-600/25 transition-all duration-300 transform hover:-translate-y-0.5">
                    <i class="fas fa-credit-card mr-2"></i> Pay Now
                </a>
                <a href="" id="trackLink" class="block w-full py-3.5 border-2 border-blue-500 text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition-all duration-300">
                    <i class="fas fa-map-marker-alt mr-2"></i> Track Order
                </a>
                <button onclick="document.getElementById('successModal').classList.add('hidden')" class="block w-full py-3 text-gray-500 hover:text-gray-700 transition-colors font-medium">
                    Continue Shopping
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 700, once: true });
        
        const basePrice = <?php echo $service['base_price']; ?>;
        const qtyInput = document.getElementById('quantity');
        
        function changeQty(delta) {
            let val = parseInt(qtyInput.value) || 1;
            val = Math.max(1, Math.min(99, val + delta));
            qtyInput.value = val;
            updatePrice();
        }
        
        qtyInput.addEventListener('input', updatePrice);
        
        function updatePrice() {
            const qty = parseInt(qtyInput.value) || 1;
            const total = basePrice * qty;
            document.getElementById('totalPrice').textContent = total.toLocaleString();
            document.getElementById('sideQty').textContent = qty;
            document.getElementById('sideTotal').textContent = 'UGX ' + total.toLocaleString();
        }

        function selectDelivery(method) {
            const doorCard = document.getElementById('doorCard');
            const pickupCard = document.getElementById('pickupCard');
            const addressSection = document.getElementById('addressSection');
            
            if (method === 'door_delivery') {
                doorCard.classList.add('selected');
                doorCard.querySelector('i').classList.remove('text-gray-400');
                pickupCard.classList.remove('selected');
                pickupCard.querySelector('i').classList.add('text-gray-400');
                document.querySelector('[value="door_delivery"]').checked = true;
                addressSection.style.display = 'block';
            } else {
                pickupCard.classList.add('selected');
                pickupCard.querySelector('i').classList.remove('text-gray-400');
                doorCard.classList.remove('selected');
                doorCard.querySelector('i').classList.add('text-gray-400');
                document.querySelector('[value="pickup"]').checked = true;
                addressSection.style.display = 'none';
            }
        }

        // File handling
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const uploadArea = document.getElementById('uploadArea');
        
        uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('dragover'); });
        uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            updateFileList();
        });
        
        function updateFileList() {
            fileList.innerHTML = '';
            Array.from(fileInput.files).forEach((file, i) => {
                const div = document.createElement('div');
                div.className = 'file-chip flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3';
                div.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-file text-blue-500"></i>
                        <span class="text-sm text-gray-700">${file.name}</span>
                        <span class="text-xs text-gray-400">(${(file.size/1024).toFixed(1)} KB)</span>
                    </div>
                    <button type="button" onclick="removeFile(${i})" class="text-red-400 hover:text-red-600 transition-colors p-1 hover:bg-red-50 rounded-lg">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                fileList.appendChild(div);
            });
        }
        
        function removeFile(index) {
            const dt = new DataTransfer();
            Array.from(fileInput.files).filter((_, i) => i !== index).forEach(f => dt.items.add(f));
            fileInput.files = dt.files;
            updateFileList();
        }

        // Form submission
        document.getElementById('orderForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            const spinner = document.getElementById('spinner');
            
            btn.disabled = true;
            btn.classList.add('hidden');
            spinner.classList.remove('hidden');
            
            try {
                const res = await fetch('api/orders/create.php', { method: 'POST', body: new FormData(this) });
                const result = await res.json();
                
                if (result.success) {
                    document.getElementById('modalOrderNum').textContent = result.data.order_number;
                    document.getElementById('modalTotal').textContent = result.data.total_amount.toLocaleString();
                    document.getElementById('payLink').href = 'payment.php?order_id=' + result.data.order_id;
                    document.getElementById('trackLink').href = 'track.php?order=' + result.data.order_number;
                    document.getElementById('successModal').classList.remove('hidden');
                    this.reset();
                    fileList.innerHTML = '';
                    updatePrice();
                } else {
                    alert('Error: ' + (result.message || 'Something went wrong'));
                }
            } catch (err) {
                alert('Network error. Please try again.');
            } finally {
                btn.disabled = false;
                btn.classList.remove('hidden');
                spinner.classList.add('hidden');
            }
        });
    </script>
</body>
</html>