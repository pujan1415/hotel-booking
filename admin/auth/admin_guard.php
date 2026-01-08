<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Strict Check: Must have admin_id set
if (!isset($_SESSION['admin_id'])) {
    // Determine path to login
    $loginPath = defined('BASE_URL') ? BASE_URL . "admin/auth/login.php" : "../auth/login.php";
    
    header("Location: " . $loginPath);
    exit();
}
?>
