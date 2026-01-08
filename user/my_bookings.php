<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../auth/user_auth_check.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT bookings.*, rooms.type as room_type, hotels.name as hotel_name, hotels.location 
    FROM bookings 
    JOIN rooms ON bookings.room_id = rooms.id 
    JOIN hotels ON rooms.hotel_id = hotels.id 
    WHERE bookings.user_id = ? 
    ORDER BY bookings.id DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="list-group mb-3">
            <a href="my_bookings.php" class="list-group-item list-group-item-action active">My Bookings</a>
            <a href="profile.php" class="list-group-item list-group-item-action">Profile</a>
        </div>
    </div>
    <div class="col-md-9">
        <h2>My Bookings</h2>
        <?php if(function_exists('flash_message')) flash_message(); ?>
        <div class="card">
            <div class="card-body">
                <?php if(count($bookings) > 0): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Hotel</th>
                                <th>Dates</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td><?php echo $booking['hotel_name']; ?> <br> <small><?php echo $booking['room_type']; ?></small></td>
                                <td><?php echo $booking['check_in']; ?> to <?php echo $booking['check_out']; ?></td>
                                <td>NPR <?php echo $booking['total_price']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($booking['status'] == 'confirmed') ? 'success' : (($booking['status'] == 'cancelled') ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($booking['status'] != 'cancelled'): ?>
                                    <form action="cancel_booking.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>You have no bookings yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
