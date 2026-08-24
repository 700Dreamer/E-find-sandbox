<?php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../includes/Session.php';
require_once '../../includes/Auth.php';

header('Content-Type: application/json');

Session::init();
if (!Session::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to place an order']);
    exit;
}

$auth = new Auth();
$user = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Get service
        $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$_POST['service_id']]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$service) {
            throw new Exception('Service not found');
        }
        
        $orderNumber = 'EF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $quantity = intval($_POST['quantity']);
        $totalAmount = $service['base_price'] * $quantity;
        
        // Create order
        $stmt = $db->prepare("
            INSERT INTO orders (
                user_id, service_id, order_number, customization_details,
                quantity, unit_price, total_amount, delivery_method,
                delivery_address, delivery_notes, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $user['id'],
            $service['id'],
            $orderNumber,
            json_encode($_POST['customization'] ?? []),
            $quantity,
            $service['base_price'],
            $totalAmount,
            $_POST['delivery_method'],
            $_POST['delivery_address'] ?? null,
            $_POST['delivery_notes'] ?? null
        ]);
        
        $orderId = $db->lastInsertId();
        
        // Handle file uploads
        if (isset($_FILES['design_files']) && !empty($_FILES['design_files']['name'][0])) {
            $uploadDir = '../../uploads/orders/' . $orderId . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            foreach ($_FILES['design_files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['design_files']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileName = time() . '_' . basename($_FILES['design_files']['name'][$key]);
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($tmp_name, $uploadPath)) {
                        $stmt = $db->prepare("INSERT INTO order_files (order_id, file_path, original_name, mime_type, size) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$orderId, 'uploads/orders/' . $orderId . '/' . $fileName, $_FILES['design_files']['name'][$key], $_FILES['design_files']['type'][$key], $_FILES['design_files']['size'][$key]]);
                    }
                }
            }
        }
        
        // Create a pending payment record
        $paymentMethod = $_POST['payment_method'] ?? 'mobile_money';
        $stmt = $db->prepare("INSERT INTO payments (order_id, payment_method, amount, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$orderId, $paymentMethod, $totalAmount]);
        
        // Add status history
        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, created_by) VALUES (?, 'pending', 'Order placed successfully', ?)");
        $stmt->execute([$orderId, $user['id']]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order created successfully!',
            'data' => [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Order creation failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>