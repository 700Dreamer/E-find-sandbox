<?php
require_once __DIR__ . '/../config/database.php';
require_once 'Session.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        Session::init();
    }

    public function register($data) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already registered'];
            }

            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            $stmt = $this->db->prepare(
                "INSERT INTO users (name, email, password, phone, address, city) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['name'],
                $data['email'],
                $hashedPassword,
                $data['phone'] ?? '',
                $data['address'] ?? '',
                $data['city'] ?? ''
            ]);

            $userId = $this->db->lastInsertId();
            $this->loginUser($userId);

            return ['success' => true, 'message' => 'Registration successful'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Account is deactivated'];
            }
            
            $this->loginUser($user['id']);
            return ['success' => true, 'message' => 'Login successful'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    private function loginUser($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        Session::set('user_id', $user['id']);
        Session::set('user_name', $user['name']);
        Session::set('user_email', $user['email']);
        Session::set('user_role', $user['role']);
    }

    public function logout() {
        Session::destroy();
    }

    public function getCurrentUser() {
        if (Session::isLoggedIn()) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([Session::get('user_id')]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    public function updateProfile($userId, $data) {
        try {
            $sql = "UPDATE users SET name = ?, phone = ?, address = ?, city = ?";
            $params = [$data['name'], $data['phone'], $data['address'], $data['city']];

            if (!empty($data['avatar'])) {
                $sql .= ", avatar = ?";
                $params[] = $data['avatar'];
            }

            $sql .= " WHERE id = ?";
            $params[] = $userId;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            Session::set('user_name', $data['name']);
            return ['success' => true, 'message' => 'Profile updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Update failed'];
        }
    }
}
?>