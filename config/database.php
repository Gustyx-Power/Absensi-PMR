<?php
/**
 * Database Configuration
 * Supports both Localhost (XAMPP/Laragon) and Cloud (TiDB/Vercel)
 */

date_default_timezone_set('Asia/Jakarta');

// Check if running on cloud (Vercel) or localhost
$isCloud = !empty(getenv('DB_HOST'));

if ($isCloud) {
    // ========================================
    // CLOUD (TiDB on Vercel)
    // ========================================
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: 4000;
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $name = getenv('DB_NAME');
    $useSSL = getenv('DB_SSL') === 'true';

    // Create connection with SSL for TiDB
    $conn = mysqli_init();

    if ($useSSL) {
        // TiDB requires SSL - use system CA certificates
        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

        // Connect with SSL flag
        if (!mysqli_real_connect($conn, $host, $user, $pass, $name, (int) $port, NULL, MYSQLI_CLIENT_SSL)) {
            die(json_encode([
                'error' => true,
                'message' => 'Database connection failed: ' . mysqli_connect_error()
            ]));
        }
    } else {
        // Connect without SSL
        if (!mysqli_real_connect($conn, $host, $user, $pass, $name, (int) $port)) {
            die(json_encode([
                'error' => true,
                'message' => 'Database connection failed: ' . mysqli_connect_error()
            ]));
        }
    }

} else {
    // ========================================
    // LOCALHOST (XAMPP/Laragon)
    // ========================================
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $name = 'absensi_pmr';

    $conn = new mysqli($host, $user, $pass, $name);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Set charset
$conn->set_charset('utf8mb4');

// Set MySQL timezone to match PHP timezone (Asia/Jakarta = UTC+7)
$conn->query("SET time_zone = '+07:00'");
?>