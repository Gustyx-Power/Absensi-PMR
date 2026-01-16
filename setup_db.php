<?php
/**
 * DATABASE INSTALLER SCRIPT
 * Run this once to setup the database tables and admin user.
 */

require_once 'config/database.php';

$password = '011010'; // Default password requested
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// SQL for Admin User
$adminSql = "INSERT INTO users (nis, nama, kelas, jabatan, password) VALUES 
('252601023', 'Putri Awidiya Prameswari', 'XII', 'Pembina', '$hashed_password')";

// Read SQL file
$sqlFile = file_get_contents('database/absensi_pmr.sql');

// Split into individual queries (semicolon + newline)
// This is a basic splitter, works for this specific SQL file
$queries = explode(";\n", $sqlFile);

echo "<h1>Database Installation</h1>";
echo "<pre>";

// 1. Create Tables
foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query))
        continue;

    // Skip comments
    if (strpos($query, '--') === 0)
        continue;

    // Execute query
    if ($conn->query($query)) {
        echo "[SUCCESS] Executed query: " . substr($query, 0, 50) . "...\n";
    } else {
        echo "[ERROR] " . $conn->error . "\n";
    }
}

// 2. Insert Admin User manually to ensure hash is correct
// Check if user exists first
$check = $conn->query("SELECT id FROM users WHERE nis = '252601023'");
if ($check->num_rows == 0) {
    if ($conn->query($adminSql)) {
        echo "[SUCCESS] Created Admin User: Putri Awidiya Prameswari (NIS: 252601023)\n";
    } else {
        echo "[ERROR] Failed to create admin: " . $conn->error . "\n";
    }
} else {
    echo "[INFO] Admin User already exists.\n";
}

echo "\nDone! Delete this file after use.";
echo "</pre>";
?>