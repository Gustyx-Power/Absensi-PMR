<?php
require_once __DIR__ . '/config/session_handler.php';

// Store user name for logout message
$userName = $_SESSION['nama'] ?? '';

// Unset all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start new session for success message
session_start();

if ($userName) {
    $_SESSION['login_success'] = 'Sampai jumpa, ' . $userName . '! Anda telah berhasil logout.';
}

// Redirect to login page
header('Location: login.php');
exit;
?>