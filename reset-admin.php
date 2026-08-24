<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// New password
$newPassword = 'admin123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

echo "New password hash: " . $hash . "<br><br>";

// Update admin
$stmt = $db->prepare("UPDATE users SET password = ? WHERE email = 'admin@efind.com'");
$stmt->execute([$hash]);

// Verify
$stmt = $db->prepare("SELECT * FROM users WHERE email = 'admin@efind.com'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "✅ Admin user found:<br>";
    echo "Name: " . $user['name'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
    echo "Password hash: " . $user['password'] . "<br><br>";
    
    // Test password verification
    if (password_verify($newPassword, $user['password'])) {
        echo "✅ Password verification WORKS!<br>";
        echo "You can now login with:<br>";
        echo "Email: admin@efind.com<br>";
        echo "Password: " . $newPassword;
    } else {
        echo "❌ Password verification FAILED!";
    }
} else {
    echo "❌ Admin user not found. Creating one...<br>";
    
    $stmt = $db->prepare("INSERT INTO users (name, email, password, phone, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Administrator', 'admin@efind.com', $hash, '+256700000000', 'admin', 1]);
    
    echo "✅ Admin created!<br>";
    echo "Email: admin@efind.com<br>";
    echo "Password: " . $newPassword;
}
?>