<?php
function logActivity($userId, $action, $description) {
    $database = new Database();
    $db = $database->getConnection();
    
    $userStmt = $db->prepare("SELECT name, role FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("INSERT INTO audit_logs (user_id, user_name, user_role, action, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $user ? $user['name'] : 'Unknown',
        $user ? $user['role'] : 'unknown',
        $action,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]);
}