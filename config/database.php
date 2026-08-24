<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        $this->host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: "localhost";
        $this->db_name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: "e_find_db";
        $this->username = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: "root";
        $this->password = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : "");
        $this->port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: "3306";
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                )
            );
        } catch(PDOException $exception) {
            die("Database Connection Failed: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>