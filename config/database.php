<?php
/**
 * Database Configuration
 * PMR Attendance System
 */

// Set timezone to WIB (Western Indonesian Time)
date_default_timezone_set('Asia/Jakarta');

class Database
{
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'absensi_pmr';
    private $charset = 'utf8mb4';

    public $conn;

    public function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        $this->conn = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database
        );

        if ($this->conn->connect_error) {
            die("Koneksi database gagal: " . $this->conn->connect_error);
        }

        $this->conn->set_charset($this->charset);
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function close()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

$db = new Database();
$conn = $db->getConnection();
?>