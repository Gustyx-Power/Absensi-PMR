<?php
/**
 * DATABASE SETUP SCRIPT
 * Run this once to setup the database tables and admin user.
 */

require_once 'config/database.php';

$password = '011010'; // Default password requested
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Define SQL schema directly to avoid file path issues in Vercel
$queries = [
    // 1. Users Table
    "CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `nis` varchar(20) NOT NULL,
      `nama` varchar(100) NOT NULL,
      `kelas` varchar(20) DEFAULT NULL,
      `jabatan` enum('Pembina','Pengurus','Anggota') NOT NULL DEFAULT 'Anggota',
      `password` varchar(255) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `nis` (`nis`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 2. Events Table
    "CREATE TABLE IF NOT EXISTS `events` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `nama_kegiatan` varchar(150) NOT NULL,
      `tanggal` date NOT NULL,
      `jam_mulai` time NOT NULL,
      `tolerance_time` time DEFAULT NULL,
      `jam_selesai` time NOT NULL,
      `batas_pulang` time DEFAULT NULL,
      `deskripsi` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 3. Attendance Table
    "CREATE TABLE IF NOT EXISTS `attendance` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `event_id` int(11) NOT NULL,
      `status` enum('Hadir','Terlambat','Izin','Sakit','Alpha') NOT NULL DEFAULT 'Alpha',
      `waktu_absen` datetime DEFAULT NULL,
      `clock_out` datetime DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_attendance` (`user_id`,`event_id`),
      KEY `event_id` (`event_id`),
      CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 4. Sessions Table (For Serverless Support)
    "CREATE TABLE IF NOT EXISTS `sessions` (
        `id` varchar(128) NOT NULL,
        `data` mediumtext NOT NULL,
        `timestamp` int(11) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

// Admin User Query
$adminSql = "INSERT INTO users (nis, nama, kelas, jabatan, password) VALUES 
('252601023', 'Putri Awidiya Prameswari', 'XII', 'Pembina', '$hashed_password')";

echo "<h1>Database Installation</h1>";
echo "<pre>";

// 1. Create Tables
foreach ($queries as $index => $query) {
    if ($conn->query($query)) {
        echo "[SUCCESS] Table " . ($index + 1) . " created/checked.\n";
    } else {
        echo "[ERROR] Table " . ($index + 1) . ": " . $conn->error . "\n";
    }
}

// 2. Insert Admin User
// Check if table users exists first (sanity check)
$checkTable = $conn->query("SHOW TABLES LIKE 'users'");
if ($checkTable->num_rows > 0) {
    // Check if user exists
    $check = $conn->query("SELECT id FROM users WHERE nis = '252601023'");
    if ($check && $check->num_rows == 0) {
        if ($conn->query($adminSql)) {
            echo "[SUCCESS] Created Admin User: Putri Awidiya Prameswari (NIS: 252601023)\n";
        } else {
            echo "[ERROR] Failed to create admin: " . $conn->error . "\n";
        }
    } else {
        echo "[INFO] Admin User already exists.\n";
    }
} else {
    echo "[CRITICAL] Tables were not created, cannot insert admin.\n";
}

echo "\nDone! You can now login.";
echo "</pre>";
?>