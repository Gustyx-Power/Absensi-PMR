<?php
session_start();

require_once 'config/database.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Get and sanitize input
$nis = trim($_POST['nis'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($nis) || empty($password)) {
    $_SESSION['login_error'] = 'NIS dan Password harus diisi.';
    header('Location: login.php');
    exit;
}

// Prepare statement to prevent SQL injection
$stmt = $conn->prepare("SELECT id, nis, nama, kelas, jabatan, password FROM users WHERE nis = ?");
$stmt->bind_param("s", $nis);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $user['password'])) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nis'] = $user['nis'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['kelas'] = $user['kelas'];
        $_SESSION['jabatan'] = $user['jabatan'];
        $_SESSION['is_login'] = true;
        $_SESSION['login_time'] = time();

        // Close statement
        $stmt->close();

        // Redirect to dashboard
        // Redirect to dashboard (index.php handles role routing)
        header('Location: admin/index.php');
        exit;
    } else {
        $_SESSION['login_error'] = 'Password yang Anda masukkan salah.';
    }
} else {
    $_SESSION['login_error'] = 'NIS tidak ditemukan dalam sistem.';
}

$stmt->close();

// Redirect back to login on failure
header('Location: login.php');
exit;
?>