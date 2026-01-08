<?php
require_once '../../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear Admin Session Only
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['user_role']);

// Optional: Destroy whole session if not shared with user login
// session_destroy(); 

header("Location: " . BASE_URL . "admin/auth/login.php");
exit();
?>
