<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/redirect.php';

if (!isset($_GET['id'])) {
    redirect('admin/bookings/index.php');
}

$id = $_GET['id'];
$stmt = $pdo->prepare("
    SELECT bookings.*, users.name as user_name, users.email as user_email, rooms.type as room_type, hotels.name as hotel_name, hotels.location 
    FROM bookings 
    JOIN users ON bookings.user_id = users.id 
    JOIN rooms ON bookings.room_id = rooms.id 
    JOIN hotels ON rooms.hotel_id = hotels.id 
    WHERE bookings.id = ?
");
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    redirect('admin/bookings/index.php', 'danger', 'Booking not found');
}

include '../includes/header.php';
?>

<h2>Booking Details #<?php echo $booking['id']; ?></h2>
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h4>Customer Info</h4>
                <p><strong>Name:</strong> <?php echo $booking['user_name']; ?></p>
                <p><strong>Email:</strong> <?php echo $booking['user_email']; ?></p>
            </div>
            <div class="col-md-6">
                <h4>Booking Info</h4>
                <p><strong>Hotel:</strong> <?php echo $booking['hotel_name']; ?> (<?php echo $booking['location']; ?>)</p>
                <p><strong>Room:</strong> <?php echo $booking['room_type']; ?></p>
                <p><strong>Check In:</strong> <?php echo $booking['check_in']; ?></p>
                <p><strong>Check Out:</strong> <?php echo $booking['check_out']; ?></p>
                <p><strong>Total Price:</strong> NPR <?php echo $booking['total_price']; ?></p>
                <p><strong>Status:</strong> <?php echo ucfirst($booking['status']); ?></p>
            </div>
        </div>
        <a href="index.php" class="btn btn-secondary mt-3">Back</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
