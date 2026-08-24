<?php
require_once '../config/app.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$deliveryId = (int)($_GET['delivery_id'] ?? 0);
$orderNumber = $_GET['order'] ?? '';

if (!$deliveryId && !$orderNumber) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

if ($orderNumber) {
    $stmt = $db->prepare("
        SELECT rl.*, d.id as delivery_id, d.delivery_address, d.status as delivery_status
        FROM rider_locations rl
        JOIN deliveries d ON rl.delivery_id = d.id
        JOIN orders o ON d.order_id = o.id
        WHERE o.order_number = ?
        ORDER BY rl.updated_at DESC
        LIMIT 1
    ");
    $stmt->execute([$orderNumber]);
} else {
    $stmt = $db->prepare("
        SELECT rl.*, d.delivery_address, d.status as delivery_status
        FROM rider_locations rl
        JOIN deliveries d ON rl.delivery_id = d.id
        WHERE rl.delivery_id = ?
        ORDER BY rl.updated_at DESC
        LIMIT 1
    ");
    $stmt->execute([$deliveryId]);
}

$location = $stmt->fetch(PDO::FETCH_ASSOC);

if ($location) {
    echo json_encode([
        'success' => true,
        'data' => [
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'address' => $location['delivery_address'],
            'status' => $location['delivery_status'],
            'updated_at' => $location['updated_at']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No location data available']);
}