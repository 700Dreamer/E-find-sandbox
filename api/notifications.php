<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = Session::get('user_id');
$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';

if ($action === 'count') {
    // Return unread count
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();
    echo json_encode(['count' => (int)$count]);
    exit;
}

if ($action === 'mark_read' && isset($_GET['id'])) {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_GET['id'], $userId]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'mark_all_read') {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true]);
    exit;
}

// Default: list recent notifications
$stmt = $db->prepare("SELECT n.*, o.order_number FROM notifications n JOIN orders o ON n.order_id = o.id WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT 10");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($notifications);