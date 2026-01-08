<?php
// Base URL - Update this if hosting on a different path
define('BASE_URL', 'http://localhost/b/');

// Application Name
define('APP_NAME', 'Hotel Booking');

// Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
