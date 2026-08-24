<?php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../includes/Session.php';

header('Content-Type: application/json');

Session::init();
if (!Session::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? null;
$transactionId = $data['transaction_id'] ?? null;
$status = $data['status'] ?? 'completed';

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID missing']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("UPDATE payments SET transaction_id = ?, status = ?, paid_at = NOW() WHERE order_id = ?");
    $stmt->execute([$transactionId, $status, $orderId]);
    
    // Update order status to processing if payment completed
    if ($status === 'completed') {
        $stmt = $db->prepare("UPDATE orders SET status = 'processing' WHERE id = ? AND status = 'pending'");
        $stmt->execute([$orderId]);
    }

    echo json_encode(['success' => true, 'message' => 'Payment updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>