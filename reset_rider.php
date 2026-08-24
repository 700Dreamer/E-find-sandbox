<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$newPassword = 'rider123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['rider@efind.com']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $stmt = $db->prepare("UPDATE users SET password = ?, is_active = 1 WHERE email = ?");
    $stmt->execute([$hash, 'rider@efind.com']);
    echo "✅ Rider password UPDATED.<br>";
} else {
    $stmt = $db->prepare("INSERT INTO users (name, email, password, phone, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['John Rider', 'rider@efind.com', $hash, '+256700000001', 'delivery', 1]);
    echo "✅ Rider user CREATED.<br>";
}

echo "<br><h3>Rider login credentials:</h3>";
echo "Email: <b>rider@efind.com</b><br>";
echo "Password: <b>$newPassword</b><br>";
echo "<br><a href='login.php'>Go to Login Page</a>";
?>