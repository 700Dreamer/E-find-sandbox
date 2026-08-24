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
$latitude = (float)($data['latitude'] ?? 0);
$longitude = (float)($data['longitude'] ?? 0);
$riderId = (int)Session::get('user_id');

if (!$deliveryId || !$latitude || !$longitude) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Verify this delivery belongs to this rider
$check = $db->prepare("SELECT id FROM deliveries WHERE id = ? AND delivery_person_id = ?");
$check->execute([$deliveryId, $riderId]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Invalid delivery']);
    exit;
}

// Update or insert location
$stmt = $db->prepare("SELECT id FROM rider_locations WHERE delivery_id = ? AND rider_id = ?");
$stmt->execute([$deliveryId, $riderId]);

if ($stmt->fetch()) {
    $stmt = $db->prepare("UPDATE rider_locations SET latitude = ?, longitude = ?, updated_at = NOW() WHERE delivery_id = ? AND rider_id = ?");
    $stmt->execute([$latitude, $longitude, $deliveryId, $riderId]);
} else {
    $stmt = $db->prepare("INSERT INTO rider_locations (delivery_id, rider_id, latitude, longitude) VALUES (?, ?, ?, ?)");
    $stmt->execute([$deliveryId, $riderId, $latitude, $longitude]);
}

echo json_encode(['success' => true, 'message' => 'Location updated']);