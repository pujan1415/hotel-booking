<?php
require_once '../config/config.php';
require_once '../config/db.php';

if (!isset($_GET['booking_id'])) {
    die("Booking ID required");
}

$booking_id = $_GET['booking_id'];
// Fetch total amount
$stmt = $pdo->prepare("SELECT total_price FROM bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking not found");
}

$amount = $booking['total_price'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Simulate successful payment callback
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'completed' WHERE id = ?");
    $stmt->execute([$booking_id]);
    
    // Log payment
    $stmtPay = $pdo->prepare("INSERT INTO payments (booking_id, gateway, transaction_id, amount, status) VALUES (?, 'khalti', 'TXN_KHALTI_" . time() . "', ?, 'success')");
    $stmtPay->execute([$booking_id, $amount]);

    header("Location: ../booking_success.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head><title>Khalti Payment Mock</title></head>
<body style="text-align:center; padding: 50px;">
    <h1>Khalti Payment Gateway (Mock)</h1>
    <p>Pay Amount: NPR <?php echo $amount; ?></p>
    <form method="POST">
        <button style="background:#5C2D91; color:white; padding:10px 20px; border:none; border-radius:5px; font-size:18px;">Confirm Payment</button>
    </form>
</body>
</html>
