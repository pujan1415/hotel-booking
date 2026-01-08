<?php
require_once '../config/config.php';
require_once '../auth/user_auth_check.php';

// For this simple system, dashboard just redirects to my_bookings
header("Location: my_bookings.php");
exit();
?>
