<?php
session_start();

// Redirect to dashboard if already logged in, or to login if not
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: ../login.php');
}
exit;
?>