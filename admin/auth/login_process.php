<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/sanitize.php';
require_once '../../helpers/redirect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    // 1. Fetch Admin from 'admins' table
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    // 2. Verify Password
    if ($admin && password_verify($password, $admin['password'])) {
        // 3. Set Session Variables
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['user_role'] = 'admin'; // Optional: Legacy support
        
        redirect('admin/dashboard.php');
    } else {
        redirect('admin/auth/login.php', 'danger', 'Invalid Admin Credentials');
    }
} else {
    redirect('admin/auth/login.php');
}
?>
