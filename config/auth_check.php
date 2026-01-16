<?php
/**
 * Authentication Check Helper
 * Include this file at the top of any protected page
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['is_login']) || $_SESSION['is_login'] !== true) {
    $_SESSION['login_error'] = 'Silakan login terlebih dahulu untuk mengakses halaman ini.';
    header('Location: ' . getBaseUrl() . 'login.php');
    exit;
}

// Optional: Check session timeout (30 minutes)
$session_timeout = 30 * 60; // 30 minutes in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_timeout) {
    // Session expired
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['login_error'] = 'Sesi Anda telah berakhir. Silakan login kembali.';
    header('Location: ' . getBaseUrl() . 'login.php');
    exit;
}

// Update last activity time
$_SESSION['login_time'] = time();

/**
 * Get base URL based on current directory depth
 */
function getBaseUrl()
{
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $depth = substr_count(str_replace('\\', '/', $scriptPath), '/') - 1;

    // Check if we're in a subdirectory (like /admin/)
    if (strpos($scriptPath, '/admin/') !== false) {
        return '../';
    }

    return '';
}

/**
 * Check if user has specific role
 */
function hasRole($allowedRoles)
{
    if (!isset($_SESSION['jabatan'])) {
        return false;
    }

    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    return in_array($_SESSION['jabatan'], $allowedRoles);
}

/**
 * Require specific role to access page
 */
function requireRole($allowedRoles, $redirectUrl = null)
{
    if (!hasRole($allowedRoles)) {
        $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini.';
        $redirect = $redirectUrl ?? getBaseUrl() . 'admin/index.php';
        header('Location: ' . $redirect);
        exit;
    }
}
?>