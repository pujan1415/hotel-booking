<?php
require_once '../config/config.php';
require_once '../config/db.php';

if (isset($_POST['room_id']) && isset($_POST['check_in']) && isset($_POST['check_out'])) {
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status != 'cancelled' AND ((check_in BETWEEN ? AND ?) OR (check_out BETWEEN ? AND ?))");
    $stmt->execute([$room_id, $check_in, $check_out, $check_in, $check_out]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo json_encode(['status' => 'unavailable', 'message' => 'Room is booked for these dates.']);
    } else {
        echo json_encode(['status' => 'available', 'message' => 'Room is available.']);
    }
}
?>
