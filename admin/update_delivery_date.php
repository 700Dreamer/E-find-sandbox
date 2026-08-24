<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || !Session::isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
$estDate = $_POST['estimated_delivery'] ?? '';

if (!$orderId || empty($estDate)) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID and date are required']);
    exit;
}

// Validate date format
$dateObj = DateTime::createFromFormat('Y-m-d', $estDate);
if (!$dateObj || $dateObj->format('Y-m-d') !== $estDate) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD.']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Update the order
    $stmt = $db->prepare("UPDATE orders SET estimated_delivery = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$estDate, $orderId]);

    // Get order details for notification
    $orderStmt = $db->prepare("SELECT user_id, order_number FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // Insert notification
        $message = "Estimated delivery date for order #{$order['order_number']} set to " . date('M d, Y', strtotime($estDate)) . ".";
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, order_id, type, message) VALUES (?, ?, 'delivery_date', ?)");
        $notifStmt->execute([$order['user_id'], $orderId, $message]);
    }

    echo json_encode(['success' => true, 'message' => 'Delivery date updated successfully!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}