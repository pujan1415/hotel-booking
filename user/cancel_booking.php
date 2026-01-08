<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../auth/user_auth_check.php';
require_once '../helpers/sanitize.php';
require_once '../helpers/redirect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    $user_id = $_SESSION['user_id'];

    // Verify booking belongs to user
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();

    if ($booking) {
        if ($booking['status'] != 'cancelled') {
            // Cancel booking
            $update = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            if ($update->execute([$booking_id])) {
                // Return capacity to room (if using decrement logic in future, for now just status update)
                redirect('user/my_bookings.php', 'success', 'Booking cancelled successfully.');
            } else {
                redirect('user/my_bookings.php', 'danger', 'Failed to cancel booking.');
            }
        } else {
            redirect('user/my_bookings.php', 'warning', 'Booking is already cancelled.');
        }
    } else {
        redirect('user/my_bookings.php', 'danger', 'Invalid booking.');
    }
} else {
    redirect('user/my_bookings.php');
}
?>
