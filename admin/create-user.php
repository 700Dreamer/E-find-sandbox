<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || Session::get('user_role') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    
    try {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $phone, $role]);
        
        header('Location: users.php?success=1');
        exit;
    } catch (PDOException $e) {
        header('Location: users.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}