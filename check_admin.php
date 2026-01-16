<?php
require_once 'config/database.php';

$nis = '252601023';
$pass = '011010';

echo "<h1>Debug Admin User</h1>";

// 1. Cek Koneksi DB
if ($conn->connect_error) {
    die("Koneksi DB Gagal: " . $conn->connect_error);
}
echo "Koneksi DB OK.<br>";

// 2. Cek User
$stmt = $conn->prepare("SELECT * FROM users WHERE nis = ?");
$stmt->bind_param("s", $nis);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "User DITEMUKAN:<br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Nama: " . $user['nama'] . "<br>";
    echo "Jabatan: " . $user['jabatan'] . "<br>";
    echo "Hash di DB: " . $user['password'] . "<br>";

    // 3. Cek Password Verify
    if (password_verify($pass, $user['password'])) {
        echo "<h3 style='color:green'>PASSWORD MATCH! Data Benar.</h3>";
    } else {
        echo "<h3 style='color:red'>PASSWORD DOES NOT MATCH!</h3>";
        echo "Coba generate hash baru: " . password_hash($pass, PASSWORD_DEFAULT);
    }
} else {
    echo "<h3 style='color:red'>User dengan NIS $nis TIDAK DITEMUKAN di database.</h3>";
    echo "Silakan jalankan <a href='setup_db.php'>setup_db.php</a> lagi.";
}
?>