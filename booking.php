<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'auth/user_auth_check.php';
require_once 'helpers/sanitize.php';
require_once 'helpers/redirect.php';

// Accept 'rooms' param which is a JSON array of {id, qty}
$rooms_data = isset($_GET['rooms']) ? json_decode(urldecode($_GET['rooms']), true) : [];

// Fallback for legacy single room param
if (empty($rooms_data) && isset($_GET['room_id'])) {
    $qty = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
    $rooms_data[] = ['id' => $_GET['room_id'], 'qty' => $qty];
}

if (empty($rooms_data)) {
    redirect('index.php');
}

// Fetch details for all selected rooms
$placeholders = str_repeat('?,', count($rooms_data) - 1) . '?';
$ids = array_column($rooms_data, 'id');
$stmt = $pdo->prepare("SELECT rooms.*, hotels.name as hotel_name, hotels.image as hotel_image, hotels.location as hotel_location, hotels.description as hotel_desc FROM rooms JOIN hotels ON rooms.hotel_id = hotels.id WHERE rooms.id IN ($placeholders)");
$stmt->execute($ids);
$fetched_rooms = $stmt->fetchAll(PDO::FETCH_PROPS_LATE);

// Fetch Current User Details for Autofill
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();

// Map Qty to Room Details
$order_items = [];
$total_booking_price = 0;
$pre_check_in = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d');
$pre_check_out = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));
$d1 = new DateTime($pre_check_in);
$d2 = new DateTime($pre_check_out);
$diff = $d1->diff($d2);
$days = max(1, $diff->days);

foreach ($fetched_rooms as $room) {
    foreach ($rooms_data as $item) {
        if ($item['id'] == $room['id']) {
            $item_total = $room['price'] * $item['qty'] * $days;
            $total_booking_price += $item_total;
            $room['qty'] = $item['qty'];
            $room['item_total'] = $item_total;
            $order_items[] = $room;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $payment_method = $_POST['payment_method'];

    if ($check_in >= $check_out) {
        $error = "Check-out date must be after check-in date.";
    } else {
        // Re-construct order items from POSTed rooms data for safety
        if (isset($_POST['rooms_encoded'])) {
            $rooms_data_post = json_decode(urldecode($_POST['rooms_encoded']), true);
            if ($rooms_data_post) {
                // Re-map quantity from POST data to ensure accuracy
                foreach ($fetched_rooms as &$room) {
                    foreach ($rooms_data_post as $item) {
                        if ($item['id'] == $room['id']) {
                            $room['qty'] = $item['qty'];
                        }
                    }
                }
                unset($room);
                // Re-build order_items with correct qty
                $order_items = [];
                foreach ($fetched_rooms as $room) {
                     foreach ($rooms_data_post as $item) {
                        if ($item['id'] == $room['id']) {
                            // Recalculate item total if needed or just trust previous logic? 
                            // Better to be consistent. 
                            // Actually, let's just update $order_items directly? 
                            // The original loop (lines 43-53) already built $order_items based on GET/Initial data.
                            // If we trust that GET param persists in URL, we don't need this.
                            // BUT validation is key.
                            
                            // Let's UPDATE $order_items quantities just in case
                             $room['qty'] = $item['qty'];
                             $room['item_total'] = $room['price'] * $item['qty'] * $days;
                             $order_items[] = $room;
                        }
                    }
                }
            }
        }

        $date1 = new DateTime($check_in);
        $date2 = new DateTime($check_out);
        $interval = $date1->diff($date2);
        $days = max(1, $interval->days);
        
        $bookings_created = [];
        
        foreach ($order_items as $item) {
            $room_price_total = $item['price'] * $item['qty'] * $days;
            // Insert Booking
            $stmtBase = $pdo->prepare("INSERT INTO bookings (user_id, room_id, quantity, check_in, check_out, total_price, payment_status, status) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending')");
            if ($stmtBase->execute([$_SESSION['user_id'], $item['id'], $item['qty'], $check_in, $check_out, $room_price_total])) {
                $bookings_created[] = $pdo->lastInsertId();
            }
        }
        
        if (count($bookings_created) > 0) {
            $ref_id = $bookings_created[0];
            if ($payment_method == 'esewa') {
                header("Location: payment/esewa.php?booking_id=$ref_id");
            } elseif ($payment_method == 'khalti') {
                header("Location: payment/khalti.php?booking_id=$ref_id");
            } else {
                $in_query = str_repeat('?,', count($bookings_created) - 1) . '?';
                $stmtUpd = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id IN ($in_query)");
                $stmtUpd->execute($bookings_created);
                redirect('booking_success.php');
            }
            exit();
        } else {
            $error = "Booking failed.";
        }
    }
}
include 'includes/header.php';
?>

<div class="container my-5">
<form method="POST">
    <div class="row g-5">
        
        <!-- LEFT COLUMN: Hotel & Booking Info -->
        <div class="col-lg-4">
            <div class="card shadow border-0 mb-4">
                <?php if($order_items[0]['hotel_image']): ?>
                    <img src="<?php echo BASE_URL . 'uploads/hotels/' . $order_items[0]['hotel_image']; ?>" class="card-img-top" alt="Hotel Image" style="height: 200px; object-fit: cover;">
                <?php else: ?>
                    <img src="https://via.placeholder.com/400x200" class="card-img-top" alt="Hotel Image">
                <?php endif; ?>
                
                <div class="card-body">
                    <h4 class="card-title fw-bold"><?php echo $order_items[0]['hotel_name']; ?></h4>
                    <p class="text-muted small"><i class="fas fa-map-marker-alt text-success"></i> <?php echo $order_items[0]['hotel_location']; ?></p>
                    <hr>
                    <h6 class="fw-bold">Your Booking Details</h6>
                    
                    <div class="mb-2">
                        <small class="text-muted d-block">Dates</small>
                        <strong><?php echo $pre_check_in; ?></strong> to <strong><?php echo $pre_check_out; ?></strong>
                        <span class="badge bg-light text-dark ms-2"><?php echo $days; ?> Nights</span>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Selected Rooms</small>
                        <?php foreach($order_items as $item): ?>
                            <div class="d-flex justify-content-between small mb-1">
                                <span><?php echo $item['qty']; ?>x <?php echo $item['type']; ?></span>
                                <span class="fw-bold">NPR <?php echo $item['item_total']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <span class="fw-bold fs-5">Total</span>
                        <span class="fw-bold fs-4 text-success">NPR <?php echo number_format($total_booking_price); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Guest Details Form -->
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-user-check me-2"></i> Guest Information</h5>
                </div>
                <div class="card-body p-4">
                    
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

                    <div class="d-flex justify-content-end mb-4">
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="fillMyDetails()">
                            <i class="fas fa-magic me-1"></i> Book for Myself (Auto-fill)
                        </button>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">First Name <span class="text-danger">*</span></label>
                            <input type="text" id="fname" name="first_name" class="form-control" required placeholder="First Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" id="lname" name="last_name" class="form-control" required placeholder="Last Name">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" required placeholder="john@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone / Contact <span class="text-danger">*</span></label>
                            <input type="tel" id="phone" name="phone" class="form-control" required placeholder="+977 ">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-bold">Address <span class="text-danger">*</span></label>
                            <input type="text" id="address" name="address" class="form-control" required placeholder="Street Address, City">
                        </div>
                         <div class="col-md-6">
                            <label class="form-label fw-bold">Country <span class="text-danger">*</span></label>
                            <select class="form-select" id="country" name="country">
                                <option value="Nepal">Nepal</option>
                                <option value="India">India</option>
                                <option value="China">China</option>
                                <option value="USA">USA</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <h5 class="fw-bold mb-3"><i class="fas fa-credit-card me-2"></i> Payment Details</h5>
                    
                    <!-- Hidden inputs for validation logic -->
                    <input type="hidden" name="check_in" value="<?php echo $pre_check_in; ?>">
                    <input type="hidden" name="check_out" value="<?php echo $pre_check_out; ?>">
                    <input type="hidden" name="rooms_encoded" value="<?php echo isset($_GET['rooms']) ? htmlspecialchars($_GET['rooms']) : ''; ?>">

                    <div class="mb-4">
                         <div class="form-check form-check-inline p-3 border rounded me-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="esewa" value="esewa" checked>
                            <label class="form-check-label fw-bold text-success" for="esewa">eSewa Wallet</label>
                        </div>
                        <div class="form-check form-check-inline p-3 border rounded me-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="khalti" value="khalti">
                            <label class="form-check-label fw-bold text-primary" for="khalti">Khalti Wallet</label>
                        </div>
                        <div class="form-check form-check-inline p-3 border rounded">
                            <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod">
                            <label class="form-check-label fw-bold text-secondary" for="cod">Pay at Hotel</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold fs-5 shadow-sm">
                        Confirm & Pay NPR <?php echo number_format($total_booking_price); ?>
                    </button>
                    
                </div>
            </div>
        </div>
    </div>
</form>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/booking.js"></script>
<script>
    function triggerFill() {
        const user = {
            name: "<?php echo addslashes($currentUser['name']); ?>",
            email: "<?php echo addslashes($currentUser['email']); ?>",
            phone: "<?php echo addslashes($currentUser['phone']); ?>"
        };
        fillMyDetails(user);
    }
    // Attach to button
    document.querySelector('button[onclick="fillMyDetails()"]').setAttribute('onclick', 'triggerFill()');
</script>

<?php include 'includes/footer.php'; ?>
