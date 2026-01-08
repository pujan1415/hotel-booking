<?php
require_once '../config/config.php';
require_once '../config/db.php';

// Simple JSON response for booking if you decide to switch to AJAX later
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Logic similar to booking.php but returning JSON
    echo json_encode(['status' => 'success', 'message' => 'Booking logic managed in booking.php mostly, but this endpoint exists.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
