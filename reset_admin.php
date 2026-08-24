<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed. Make sure MySQL is running in XAMPP.");
}

// Set the new admin password
$newPassword = 'admin123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

// Check if admin exists
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['admin@efind.com']);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    // Update password
    $stmt = $db->prepare("UPDATE users SET password = ?, role = 'admin', is_active = 1 WHERE email = ?");
    $stmt->execute([$hash, 'admin@efind.com']);
    echo "✅ Admin user UPDATED.<br>";
} else {
    // Create admin
    $stmt = $db->prepare("INSERT INTO users (name, email, password, phone, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Administrator', 'admin@efind.com', $hash, '+256700000000', 'admin', 1]);
    echo "✅ Admin user CREATED.<br>";
}

// Verify the password works
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['admin@efind.com']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<br>Admin user details:<br>";
echo "Name: " . $user['name'] . "<br>";
echo "Email: " . $user['email'] . "<br>";
echo "Role: " . $user['role'] . "<br>";
echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";

if (password_verify($newPassword, $user['password'])) {
    echo "<br>✅ Password verification SUCCESS!<br>";
    echo "<h3>You can now login with:</h3>";
    echo "Email: <b>admin@efind.com</b><br>";
    echo "Password: <b>" . $newPassword . "</b><br>";
    echo "<br><a href='login.php' style='font-size:18px; color:blue;'>👉 Click here to go to Login Page</a>";
} else {
    echo "<br>❌ Password verification FAILED. Something is wrong with the hash.";
}
?>