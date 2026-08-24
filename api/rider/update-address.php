<?php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../includes/Session.php';

header('Content-Type: application/json');

Session::init();
if (!Session::isLoggedIn() || Session::get('user_role') !== 'delivery') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$deliveryId = (int)($data['delivery_id'] ?? 0);
$newAddress = trim($data['new_address'] ?? '');
$reason = trim($data['reason'] ?? '');
$riderId = (int)Session::get('user_id');

if (!$deliveryId || empty($newAddress)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Verify delivery
$check = $db->prepare("SELECT d.*, o.delivery_address as old_address FROM deliveries d JOIN orders o ON d.order_id = o.id WHERE d.id = ? AND d.delivery_person_id = ?");
$check->execute([$deliveryId, $riderId]);
$delivery = $check->fetch(PDO::FETCH_ASSOC);

if (!$delivery) {
    echo json_encode(['success' => false, 'message' => 'Invalid delivery']);
    exit;
}

try {
    $db->beginTransaction();

    // Update delivery address
    $stmt = $db->prepare("UPDATE deliveries SET delivery_address = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newAddress, $deliveryId]);

    // Update order address
    $stmt = $db->prepare("UPDATE orders SET delivery_address = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newAddress, $delivery['order_id']]);

    // Record the change
    $stmt = $db->prepare("INSERT INTO delivery_address_updates (delivery_id, old_address, new_address, reason, updated_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$deliveryId, $delivery['old_address'], $newAddress, $reason, $riderId]);

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Address updated successfully', 'new_address' => $newAddress]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
}