<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'helpers/redirect.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$hotel_id = $_GET['id'];

// Get Hotel Details
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->execute([$hotel_id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    redirect('index.php');
}

// Get Rooms with Dynamic Availability
$search_check_in = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d');
$search_check_out = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));

// Logic: total_quantity - (booked_count_in_date_range)
$sql = "
    SELECT r.*, 
    (r.quantity - IFNULL((
        SELECT SUM(b.room_id = r.id) -- Essentially relying on row count if we didn't store qty in bookings, but wait.
        -- If schema doesn't have 'quantity' in bookings, we assume 1 booking = 1 room.
        -- If we want robust, we need to COUNT(*) overlapping bookings.
        SELECT COUNT(*) 
        FROM bookings b 
        WHERE b.room_id = r.id 
          AND b.status IN ('confirmed', 'pending')
          AND (
            (b.check_in < ?) AND (b.check_out > ?) -- Overlap logic: Start < End AND End > Start
          )
    ), 0)) as available_qty
    FROM rooms r 
    WHERE r.hotel_id = ? 
    HAVING available_qty > 0
";

// Correction: The INNER subquery needs to be precise. 
// Standard Overlap: (BookingStart < SearchEnd) AND (BookingEnd > SearchStart)
// Let's refine the query variable.

$stmtRooms = $pdo->prepare("
    SELECT r.*,
           (r.quantity - IFNULL((
               SELECT SUM(b.quantity) 
               FROM bookings b 
               WHERE b.room_id = r.id 
               AND b.status IN ('confirmed', 'pending')
               AND b.check_in < ? 
               AND b.check_out > ?
           ), 0)) as available_qty
    FROM rooms r
    WHERE r.hotel_id = ?
    HAVING available_qty > 0
");

$stmtRooms->execute([$search_check_out, $search_check_in, $hotel_id]);
$rooms = $stmtRooms->fetchAll();

include 'includes/header.php';
?>

<style>
    /* Custom Styles for this page */
    .guest-dropdown {
        display: none;
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        padding: 15px;
        z-index: 1000;
        width: 300px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-radius: 8px;
        top: 100%;
    }
    .qty-btn {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: 1px solid #28a745;
        background: white;
        color: #28a745;
        font-weight: bold;
    }
    .qty-btn:hover { background: #28a745; color: white; }
    
    .sticky-sidebar {
        position: -webkit-sticky;
        position: sticky;
        top: 80px; /* adjust based on navbar height */
    }
    .room-img-sm {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    /* Gallery Styles */
    .object-fit-cover { object-fit: cover; }
    .hover-zoom { transition: transform 0.3s ease; }
    .hover-zoom:hover { transform: scale(1.02); }
    
    /* Lightbox Styles */
    .lightbox-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .lightbox-content {
        max-width: 90%;
        max-height: 85%;
    }
    .lightbox-content img {
        max-width: 100%;
        max-height: 80vh;
        border-radius: 4px;
        box-shadow: 0 0 20px rgba(0,0,0,0.5);
    }
    .lightbox-close {
        position: absolute;
        top: 30px;
        right: 40px;
        background: none;
        border: none;
        color: white;
        font-size: 2rem;
        cursor: pointer;
        opacity: 0.8;
    }
    .lightbox-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255,255,255,0.1);
        border: none;
        color: white;
        font-size: 2rem;
        padding: 15px;
        cursor: pointer;
        border-radius: 50%;
        transition: background 0.3s;
    }
    .lightbox-nav:hover { background: rgba(255,255,255,0.3); }
    .prev { left: 40px; }
    .next { right: 40px; }
    .lightbox-close:hover { opacity: 1; transform: scale(1.1); }
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
    <li class="breadcrumb-item active text-primary" aria-current="page"><?php echo $hotel['name']; ?></li>
  </ol>
</nav>

<!-- Hotel Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <h1 class="fw-bold">
            <?php echo $hotel['name']; ?>
            <span class="text-warning fs-4 ms-2">
                <?php 
                $r = isset($hotel['rating']) ? $hotel['rating'] : 3;
                for($i=0; $i<$r; $i++) echo '<i class="fas fa-star"></i>';
                ?>
            </span>
        </h1>
        <p class="text-muted"><i class="fas fa-map-marker-alt text-primary"></i> <?php echo $hotel['location']; ?></p>
    </div>
</div>

<!-- Hotel Images (Hero) -->
<!-- Hotel Images (Hero & Grid) -->
<div class="mb-5 position-relative">
    <?php 
    // Fetch all images for this hotel
    $stmtImg = $pdo->prepare("SELECT image_path FROM hotel_images WHERE hotel_id = ?");
    $stmtImg->execute([$hotel['id']]);
    $hotelImages = $stmtImg->fetchAll(PDO::FETCH_COLUMN);

    // If no images in DB, check if there's a legacy image in hotels table
    if (empty($hotelImages) && !empty($hotel['image'])) {
        $hotelImages[] = $hotel['image'];
    }
    
    // Fallback if absolutely no images
    if (empty($hotelImages)) {
        $hotelImages[] = 'https://via.placeholder.com/1200x600?text=No+Image+Available';
    }
    ?>

    <!-- Image Grid: 1 Main + 2 Side -->
    <div class="row g-2" style="height: 450px;">
        <!-- Main Image (Left, Large) -->
        <div class="col-md-8 h-100">
            <div class="h-100 w-100 position-relative overflow-hidden rounded-start" style="cursor: pointer;" onclick="openLightbox(0)">
                <img src="<?php echo strpos($hotelImages[0], 'http') === 0 ? $hotelImages[0] : BASE_URL . 'uploads/hotels/' . $hotelImages[0]; ?>" 
                     class="w-100 h-100 object-fit-cover hover-zoom" alt="Main Hotel Image">
            </div>
        </div>

        <!-- Side Images (Right, Stacked) -->
        <div class="col-md-4 h-100 d-none d-md-flex flex-column">
            <!-- Top Right -->
            <div class="flex-grow-1 pb-1">
                <div class="h-100 w-100 position-relative overflow-hidden rounded-top-end" style="cursor: pointer;" onclick="openLightbox(<?php echo isset($hotelImages[1]) ? 1 : 0; ?>)">
                    <?php if(isset($hotelImages[1])): ?>
                        <img src="<?php echo BASE_URL . 'uploads/hotels/' . $hotelImages[1]; ?>" class="w-100 h-100 object-fit-cover hover-zoom" alt="Hotel Image 2">
                    <?php else: ?>
                        <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center text-muted"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Bottom Right (With "See All" overlay if more images) -->
            <div class="flex-grow-1 pt-1">
                <div class="h-100 w-100 position-relative overflow-hidden rounded-bottom-end" style="cursor: pointer;" onclick="openLightbox(<?php echo isset($hotelImages[2]) ? 2 : 0; ?>)">
                    <?php if(isset($hotelImages[2])): ?>
                        <img src="<?php echo BASE_URL . 'uploads/hotels/' . $hotelImages[2]; ?>" class="w-100 h-100 object-fit-cover hover-zoom" alt="Hotel Image 3">
                        <?php if(count($hotelImages) > 3): ?>
                            <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center">
                                <span class="text-white fw-bold fs-5">+<?php echo count($hotelImages) - 3; ?> More</span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center text-muted"><i class="fas fa-image"></i></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox Modal -->
<div id="lightbox" class="lightbox-overlay" style="display: none;">
    <button class="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
    <button class="lightbox-nav prev" onclick="changeSlide(-1)"><i class="fas fa-chevron-left"></i></button>
    
    <div class="lightbox-content">
        <img id="lightbox-img" src="" alt="Full View">
    </div>

    <button class="lightbox-nav next" onclick="changeSlide(1)"><i class="fas fa-chevron-right"></i></button>
    <div class="lightbox-counter text-white position-absolute bottom-0 start-50 translate-middle-x mb-3">
        <span id="current-slide">1</span> / <span id="total-slides">1</span>
    </div>
</div>

<script>
    // Pass images to JS
    const hotelImages = <?php echo json_encode(array_map(function($img) {
        return strpos($img, 'http') === 0 ? $img : BASE_URL . 'uploads/hotels/' . $img;
    }, $hotelImages)); ?>;
</script>

<!-- Page Navigation -->
<div class="bg-white shadow-sm border-bottom mb-4">
    <div class="container">
        <nav class="nav">
            <a class="nav-link text-dark fw-bold py-3 active" href="#overview">Overview</a>
            <a class="nav-link text-dark fw-bold py-3" href="#availability">Availability</a>
            <a class="nav-link text-dark fw-bold py-3" href="#amenities">Amenities</a>
        </nav>      
    </div>
</div>

<!-- Overview Section -->
<div class="container mb-5">
    <div id="overview" class="scroll-offset">
        <h4 class="fw-bold text-section-title mb-3">About this Hotel</h4>
        <p class="text-secondary" style="line-height: 1.7;"><?php echo nl2br($hotel['description']); ?></p>
        
        <div id="amenities" class="mt-5">
            <h5 class="fw-bold text-section-title mb-3">Amenities</h5>
            <div class="row g-3">
                <div class="col-6 col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-wifi text-primary me-2 bg-light p-2 rounded-circle"></i> <span>Free WiFi</span>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-parking text-primary me-2 bg-light p-2 rounded-circle"></i> <span>Parking</span>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-utensils text-primary me-2 bg-light p-2 rounded-circle"></i> <span>Restaurant</span>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-concierge-bell text-primary me-2 bg-light p-2 rounded-circle"></i> <span>24/7 Service</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Availability Section -->
<div id="availability" class="mb-5 scroll-offset">
<div class="row">
    <!-- Left: Rooms List -->
    <div class="col-lg-8">
        

        
        <!-- Search Bar (Moved Here) -->
        <div class="card shadow-sm border mb-4 p-3 bg-light">
            <h5 class="fw-bold text-section-title mb-3">Check Availability</h5>
            <form id="availabilityForm">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="fw-bold small">Check-in</label>
                        <input type="date" id="check_in" name="check_in" class="form-control" value="<?php echo isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold small">Check-out</label>
                        <input type="date" id="check_out" name="check_out" class="form-control" value="<?php echo isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day')); ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    <div class="col-md-4 position-relative">
                        <label class="fw-bold small">Guests</label>
                        <div class="form-control" id="guestTrigger" style="cursor: pointer;">
                            <span id="guestSummary">1 Adult, 0 Children, 1 Room</span>
                        </div>
                        
                        <!-- Guest Dropdown -->
                        <div class="guest-dropdown" id="guestDropdown">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Adults</span>
                                <div>
                                    <button type="button" class="qty-btn" onclick="updateQty('adult', -1)">-</button>
                                    <span id="adultQty" class="mx-2">1</span>
                                    <button type="button" class="qty-btn" onclick="updateQty('adult', 1)">+</button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Children</span>
                                <div>
                                    <button type="button" class="qty-btn" onclick="updateQty('child', -1)">-</button>
                                    <span id="childQty" class="mx-2">0</span>
                                    <button type="button" class="qty-btn" onclick="updateQty('child', 1)">+</button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Rooms</span>
                                <div>
                                    <button type="button" class="qty-btn" onclick="updateQty('room', -1)">-</button>
                                    <span id="roomQty" class="mx-2">1</span>
                                    <button type="button" class="qty-btn" onclick="updateQty('room', 1)">+</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary w-100" onclick="toggleGuestDropdown()">Done</button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary w-100" onclick="checkAvailability()">Check</button>
                    </div>
                </div>
            </form>
        </div>

        <h3 class="fw-bold text-section-title mb-4">Available Rooms</h3>
        
        <?php if(empty($rooms)): ?>
            <div class="alert alert-warning shadow-sm border-0">
                <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> No Booking Available</h4>
                <p class="mb-0">Sorry, we have no available rooms for the selected dates (<?php echo $search_check_in; ?> to <?php echo $search_check_out; ?>). Please try different dates.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($rooms as $room): ?>
        <div class="card mb-4 border-0 shadow-sm room-item" id="room-<?php echo $room['id']; ?>">
            <div class="row g-0">
                <div class="col-md-4">
                    <?php if($room['image']): ?>
                        <img src="<?php echo BASE_URL . 'uploads/rooms/' . $room['image']; ?>" class="room-img-sm" alt="Room">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/300x200" class="room-img-sm" alt="Room">
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><?php echo $room['type']; ?></h5>
                        <div class="text-muted small mb-2">
                            <i class="fas fa-user"></i> Max <?php echo isset($room['capacity']) ? $room['capacity'] : 2; ?> Guests
                            <span class="mx-2">|</span>
                            <i class="fas fa-bed"></i> 1 Bed
                            <span class="mx-2">|</span>
                            <i class="fas fa-thumbs-up text-primary"></i> Breakfast Included
                        </div>
                        <p class="text-secondary small"><?php echo substr($room['description'], 0, 100); ?>...</p>
                        
                        <div class="d-flex justify-content-between align-items-end mt-3">
                            <div>
                                <h4 class="text-primary fw-bold mb-0">NPR <?php echo $room['price']; ?></h4>
                                <small class="text-muted">per night</small>
                            </div>
                            <div class="text-end">
                                <label class="small fw-bold">Select Rooms</label>
                                <select class="form-select w-auto d-inline-block ms-2 room-selector" 
                                        data-id="<?php echo $room['id']; ?>" 
                                        data-price="<?php echo $room['price']; ?>"
                                        data-type="<?php echo $room['type']; ?>"
                                        data-capacity="<?php echo isset($room['capacity']) ? $room['capacity'] : 2; ?>"
                                        onchange="updateSelection(this)">
                                    <option value="0">0</option>
                                    <?php for($i=1; $i <= $room['available_qty']; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Right: Sticky "Your Booking" Sidebar -->
    <div class="col-lg-4">
        <div class="sticky-sidebar">
            <div class="card shadow border-0 bg-light">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="fas fa-receipt me-2"></i> Your Booking
                </div>
                <div class="card-body">
                    <div class="mb-3 border-bottom pb-2">
                        <p class="mb-1"><strong>Hotel:</strong> <?php echo $hotel['name']; ?></p>
                        <p class="mb-1"><strong>Dates:</strong> <span id="summaryDates">Select Dates</span></p>
                        <p class="mb-1"><strong>Guests:</strong> <span id="summaryGuests">1 Adult, 0 Child</span></p>
                    </div>
                    
                    <div id="selectedRoomsContainer" class="mb-3">
                        <small class="text-muted fst-italic">No rooms selected</small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold">Total Price:</span>
                        <span class="fs-4 fw-bold text-primary" id="grandTotal">NPR 0</span>
                    </div>

                    <form action="booking.php" method="POST" id="bookingForm">
                        <input type="hidden" name="hotel_id" value="<?php echo $hotel_id; ?>">
                        <input type="hidden" name="check_in" id="inputCheckIn">
                        <input type="hidden" name="check_out" id="inputCheckOut">
                        <!-- We will rely on booking.php handling logic via simple GET for now, 
                             or adapt booking.php to accept POST params. 
                             For simpler integration with existing booking.php which expects GET room_id,
                             we might need to direct to a 'cart style' page or stick to single room logic if complex.
                             
                             However, user asked for "update selection". 
                             Let's assume for this step we proceed with the FIRST selected room type mainly, 
                             or update booking.php. 
                             
                             For now, let's keep it simple: Select ONE room type but allow quantity.
                        -->
                        <input type="hidden" name="room_id" id="inputRoomId">
                        <input type="hidden" name="quantity" id="inputQuantity">
                        <button type="button" onclick="submitBooking()" class="btn btn-primary w-100 py-2 fw-bold" id="bookBtn" disabled>
                            Proceed to Book
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Pass PHP data to JS
    window.initialGuests = { 
        adult: <?php echo isset($_GET['adults']) ? (int)$_GET['adults'] : 1; ?>, 
        child: <?php echo isset($_GET['children']) ? (int)$_GET['children'] : 0; ?>, 
        room: <?php echo isset($_GET['rooms_count']) ? (int)$_GET['rooms_count'] : 1; ?> 
    };
    
    // Lightbox Logic
    let currentSlideIndex = 0;
    
    function openLightbox(index) {
        currentSlideIndex = index;
        updateLightboxImage();
        document.getElementById('lightbox').style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
    
    function closeLightbox() {
        document.getElementById('lightbox').style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
    }
    
    function changeSlide(direction) {
        currentSlideIndex += direction;
        
        // Loop controls
        if (currentSlideIndex >= hotelImages.length) {
            currentSlideIndex = 0;
        } else if (currentSlideIndex < 0) {
            currentSlideIndex = hotelImages.length - 1;
        }
        
        updateLightboxImage();
    }
    
    function updateLightboxImage() {
        if(hotelImages.length > 0) {
            const imgPath = hotelImages[currentSlideIndex];
            document.getElementById('lightbox-img').src = imgPath;
            document.getElementById('current-slide').innerText = currentSlideIndex + 1;
            document.getElementById('total-slides').innerText = hotelImages.length;
        }
    }
    
    // Keyboard Navigation
    document.addEventListener('keydown', function(e) {
        if(document.getElementById('lightbox').style.display === 'flex') {
            if(e.key === 'Escape') closeLightbox();
            if(e.key === 'ArrowLeft') changeSlide(-1);
            if(e.key === 'ArrowRight') changeSlide(1);
        }
    });
</script>
<script src="<?php echo BASE_URL; ?>assets/js/hotel_detail.js"></script>

<?php include 'includes/footer.php'; ?>
