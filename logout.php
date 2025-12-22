<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    logActivity($conn, "User {$_SESSION['username']} berhasil logout", 'info');
}

session_destroy();
header('Location: ' . BASE_URL . 'login.php');
exit();
?>



