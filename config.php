<?php
// config.php - Database configuration
class Database {
    private $host = "localhost";
    private $db_name = "citywing_shuttles";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// JWT Secret Key
define('JWT_SECRET', 'citywing_shuttles_secret_key_2024');
define('JWT_ALGORITHM', 'HS256');
?>