<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || !Session::isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID required']);
    exit;
}

$orderId = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Order details with customer and service
$stmt = $db->prepare("
    SELECT o.*, s.name AS service_name, s.icon AS service_icon,
           u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
           d.status AS delivery_status, d.delivery_address,
           r.name AS rider_name
    FROM orders o
    JOIN services s ON o.service_id = s.id
    JOIN users u ON o.user_id = u.id
    LEFT JOIN deliveries d ON o.id = d.order_id
    LEFT JOIN users r ON d.delivery_person_id = r.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

// Get uploaded files
$fileStmt = $db->prepare("SELECT * FROM order_files WHERE order_id = ?");
$fileStmt->execute([$orderId]);
$files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

// Parse customization details
$customization = json_decode($order['customization_details'], true);

$response = [
    'order' => [
        'order_number' => $order['order_number'],
        'service_name' => $order['service_name'],
        'service_icon' => $order['service_icon'],
        'customer_name' => $order['customer_name'],
        'customer_email' => $order['customer_email'],
        'customer_phone' => $order['customer_phone'],
        'quantity' => $order['quantity'],
        'unit_price' => number_format($order['unit_price']),
        'total_amount' => number_format($order['total_amount']),
        'status' => $order['status'],
        'delivery_method' => $order['delivery_method'],
        'delivery_address' => $order['delivery_address'] ?? 'N/A',
        'delivery_notes' => $order['delivery_notes'],
        'delivery_status' => $order['delivery_status'] ?? 'N/A',
        'rider_name' => $order['rider_name'] ?? 'Not assigned',
        'created_at' => date('M d, Y H:i', strtotime($order['created_at'])),
        'estimated_delivery' => $order['estimated_delivery'] ? date('M d, Y', strtotime($order['estimated_delivery'])) : 'N/A',
    ],
    'customization' => $customization,
    'files' => array_map(function($f) {
        return [
            'name' => $f['original_name'],
            'path' => '../' . $f['file_path'],
            'size' => round($f['size'] / 1024, 1) . ' KB'
        ];
    }, $files)
];

header('Content-Type: application/json');
echo json_encode($response);