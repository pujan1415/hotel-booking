<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/redirect.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    if (in_array($status, ['confirmed', 'cancelled', 'pending'])) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        redirect('admin/bookings/index.php', 'success', 'Booking status updated');
    }
}
redirect('admin/bookings/index.php');
?>
