<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'helpers/sanitize.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$query = "SELECT h.*, (SELECT image_path FROM hotel_images WHERE hotel_id = h.id LIMIT 1) as gallery_image FROM hotels h";
$params = [];

if ($search) {
    $query .= " WHERE name LIKE ? OR location LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$hotels = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="hero text-center py-5" style="overflow: visible; position: relative; z-index: 20;">
    <h1 class="display-4 fw-bold mb-3">Find Your Next Stay</h1>
    <p class="hero-subtitle">Search low prices on hotels, homes and much more...</p>
    
    <div class="container position-relative" style="max-width: 1000px; overflow: visible;">
        <div class="card shadow-lg border-0 p-2" style="overflow: visible;">
            <form action="hotels.php" method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="adults" id="inputAdults" value="1">
                <input type="hidden" name="children" id="inputChildren" value="0">
                <input type="hidden" name="rooms_count" id="inputRooms" value="1">
                <!-- Destination -->
                <div class="col-md-3 position-relative">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0"><i class="fas fa-bed text-secondary"></i></span>
                        <input type="text" name="destination" id="destinationInput" class="form-control border-0 shadow-none ps-0" placeholder="Where are you going?" required autocomplete="off">
                        <div id="suggestionsList" class="suggestions-list"></div>
                    </div>
                </div>
                
                <!-- Dates -->
                <div class="col-md-2 border-start">
                    <div class="d-flex flex-column px-2">
                         <div class="mb-1">
                             <small class="text-muted" style="font-size: 10px;">Check-in</small>
                             <input type="date" name="check_in" id="checkIn" class="form-control border-0 p-0 shadow-none small" style="font-size: 14px; font-weight: bold;">
                         </div>
                    </div>
                </div>
                <div class="col-md-2 border-start">
                    <div class="d-flex flex-column px-2">
                         <div class="mb-1">
                             <small class="text-muted" style="font-size: 10px;">Check-out</small>
                             <input type="date" name="check_out" id="checkOut" class="form-control border-0 p-0 shadow-none small" style="font-size: 14px; font-weight: bold;">
                         </div>
                    </div>
                </div>

                <!-- Guests -->
                <div class="col-md-3 border-start position-relative">
                    <div class="d-flex align-items-center p-2" id="guestToggle" style="cursor: pointer;">
                        <i class="fas fa-user-friends text-secondary me-2"></i>
                        <span id="guestLabel" class="text-truncate">1 Adult, 0 Children, 1 Room</span>
                    </div>

                     <!-- Guest Dropdown -->
                     <div class="guest-dropdown card shadow p-3" id="homeGuestDropdown" style="display: none; position: absolute; top: 100%; left: 0; width: 300px; z-index: 1000;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold">Adults</span>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateHomeGuest('adult', -1)">-</button>
                                <span id="hAdultQty" class="mx-2 fw-bold">1</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateHomeGuest('adult', 1)">+</button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold">Children</span>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateHomeGuest('child', -1)">-</button>
                                <span id="hChildQty" class="mx-2 fw-bold">0</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateHomeGuest('child', 1)">+</button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold">Rooms</span>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateHomeGuest('room', -1)">-</button>
                                <span id="hRoomQty" class="mx-2 fw-bold">1</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateHomeGuest('room', 1)">+</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary w-100 btn-sm" onclick="closeHomeGuest()">Done</button>
                    </div>
                </div>

                <!-- Search Button -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Search</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="row">
    <?php foreach ($hotels as $hotel): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
            <?php if($hotel['image']): ?>
                <img src="<?php echo BASE_URL . 'uploads/hotels/' . $hotel['image']; ?>" class="card-img-top" alt="<?php echo $hotel['name']; ?>" style="height: 200px; object-fit: cover;">
            <?php elseif(isset($hotel['gallery_image']) && $hotel['gallery_image']): ?>
                <img src="<?php echo BASE_URL . 'uploads/hotels/' . $hotel['gallery_image']; ?>" class="card-img-top" alt="<?php echo $hotel['name']; ?>" style="height: 200px; object-fit: cover;">
            <?php else: ?>
                <img src="https://via.placeholder.com/400x200" class="card-img-top" alt="Placeholder">
            <?php endif; ?>
            <div class="card-body">
                <h5 class="card-title text-list-name">
                    <?php echo $hotel['name']; ?>
                    <div class="small text-warning">
                        <?php 
                        $r = isset($hotel['rating']) ? $hotel['rating'] : 3;
                        for($i=0; $i<$r; $i++) echo '<i class="fas fa-star"></i>';
                        for($i=$r; $i<5; $i++) echo '<i class="far fa-star"></i>'; 
                        ?>
                    </div>
                </h5>
                <p class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo $hotel['location']; ?></p>
                <p class="card-text"><?php echo substr($hotel['description'], 0, 100); ?>...</p>
                <a href="hotel_detail.php?id=<?php echo $hotel['id']; ?>" class="btn btn-primary w-100">View Details</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if(count($hotels) == 0): ?>
        <p class="text-center">No hotels found.</p>
    <?php endif; ?>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
<?php include 'includes/footer.php'; ?>
