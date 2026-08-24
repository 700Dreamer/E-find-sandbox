<?php
require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/Session.php';
require_once 'includes/Auth.php';

Session::init();
$auth = new Auth();
$user = $auth->getCurrentUser();
if (!$user) { header('Location: login.php'); exit; }

$orderId = $_GET['order_id'] ?? null;
if (!$orderId) die('Order ID missing.');

$database = new Database(); $db = $database->getConnection();
$stmt = $db->prepare("SELECT o.*, s.name AS service_name FROM orders o JOIN services s ON o.service_id = s.id WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$orderId, $user['id']]); $order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die('Order not found.');

$stmt = $db->prepare("SELECT * FROM payments WHERE order_id = ?");
$stmt->execute([$orderId]); $payment = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$payment) die('Payment record missing.');
if ($payment['status'] === 'completed') { header('Location: track.php?order='.urlencode($order['order_number'])); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    $newMethod = $_POST['payment_method'];
    if (in_array($newMethod, ['mobile_money','cash_on_delivery'])) {
        $stmt = $db->prepare("UPDATE payments SET payment_method = ? WHERE order_id = ?");
        $stmt->execute([$newMethod, $orderId]);
        $payment['payment_method'] = $newMethod;
    }
}

$flutterwavePublicKey = defined('FLUTTERWAVE_PUBLIC_KEY') ? FLUTTERWAVE_PUBLIC_KEY : 'FLWPUBK_TEST-xxxxxxxxxxxxx';
$useSimulation = (strpos($flutterwavePublicKey, 'FLWPUBK_TEST') !== false);
$payAmount = $payment['amount'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{50:'#eff6ff',600:'#2563eb',700:'#1d4ed8'}},fontFamily:{poppins:['Poppins','sans-serif'],inter:['Inter','sans-serif']}}}}</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="font-inter antialiased bg-gray-50 min-h-screen pt-24">
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mx-auto px-6 py-8 max-w-lg">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-poppins font-bold text-gray-900 mb-2">Complete Payment</h2>
            <p class="text-gray-500 mb-6">Order #<?php echo htmlspecialchars($order['order_number']); ?></p>
            <div class="flex justify-between items-center bg-gray-50 rounded-xl p-4 mb-6">
                <span class="text-gray-600">Total Amount:</span>
                <span class="text-2xl font-bold text-blue-600">UGX <?php echo number_format($payAmount); ?></span>
            </div>

            <?php if ($payment['payment_method'] === 'cash_on_delivery'): ?>
                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-xl mb-6">Cash on Delivery - Pay when you receive your order.</div>
                <a href="track.php?order=<?php echo urlencode($order['order_number']); ?>" class="block w-full py-3 bg-blue-600 text-white text-center font-semibold rounded-xl hover:bg-blue-700 transition-colors">Go to Order Tracking</a>
            <?php else: ?>
                <form method="POST" class="mb-6">
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <label class="border-2 border-blue-600 bg-blue-50 rounded-xl p-4 text-center cursor-pointer">
                            <input type="radio" name="payment_method" value="mobile_money" onchange="this.form.submit()" checked class="hidden">
                            <i class="fas fa-mobile-alt text-3xl text-blue-600 mb-2 block"></i>
                            <span class="font-semibold text-sm">Mobile Money</span>
                        </label>
                        <label class="border-2 border-gray-200 rounded-xl p-4 text-center cursor-pointer hover:border-blue-300">
                            <input type="radio" name="payment_method" value="cash_on_delivery" onchange="this.form.submit()" class="hidden">
                            <i class="fas fa-hand-holding-usd text-3xl text-gray-400 mb-2 block"></i>
                            <span class="font-semibold text-sm">Cash on Delivery</span>
                        </label>
                    </div>
                </form>
                <button id="startPayment" class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                    <i class="fas fa-credit-card mr-2"></i> Pay with Mobile Money
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($payment['payment_method'] === 'mobile_money'): ?>
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <script>
    document.getElementById('startPayment').addEventListener('click', function() {
        <?php if ($useSimulation): ?>
            if (confirm('Simulate successful payment?')) {
                fetch('api/payments/update.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({order_id:<?php echo $orderId; ?>, transaction_id:'SIM-<?php echo uniqid(); ?>', status:'completed'}) })
                .then(() => window.location.href = 'track.php?order=<?php echo urlencode($order['order_number']); ?>');
            }
        <?php else: ?>
            FlutterwaveCheckout({
                public_key: '<?php echo $flutterwavePublicKey; ?>',
                tx_ref: '<?php echo $order['order_number']; ?>',
                amount: <?php echo $payAmount; ?>,
                currency: 'UGX',
                payment_options: 'mobilemoney',
                customer: { email: '<?php echo $user['email']; ?>', phone_number: '<?php echo $user['phone'] ?? ''; ?>', name: '<?php echo $user['name']; ?>' },
                callback: function(r) { fetch('api/payments/update.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:<?php echo $orderId; ?>,transaction_id:r.transaction_id,status:'completed'})}).then(()=>window.location.href='track.php?order=<?php echo urlencode($order['order_number']); ?>'); },
                customizations: { title:'E-Find Payment', description:'Order #<?php echo $order['order_number']; ?>' }
            });
        <?php endif; ?>
    });
    </script>
    <?php endif; ?>
</body>
</html>