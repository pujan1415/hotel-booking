<?php
require_once 'config/config.php';
include 'includes/header.php';
?>

<div class="text-center mt-5">
    <div class="mb-4">
        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
    </div>
    <h2>Booking Successful!</h2>
    <p class="lead">Thank you for your booking. You can view your bookings in your dashboard.</p>
    <a href="user/my_bookings.php" class="btn btn-primary">Go to My Bookings</a>
    <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
</div>

<?php include 'includes/footer.php'; ?>
