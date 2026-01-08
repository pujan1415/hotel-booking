<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'helpers/sanitize.php';

// Get search parameters from the URL
$destination = isset($_GET['destination']) ? sanitize($_GET['destination']) : '';
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d');
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));
$adults = isset($_GET['adults']) ? (int)$_GET['adults'] : 1;
$children = isset($_GET['children']) ? (int)$_GET['children'] : 0;
$rooms = isset($_GET['rooms']) ? (int)$_GET['rooms'] : 1;

// Base query
$query = "
    SELECT h.*, MIN(r.price) as start_price,
    (SELECT image_path FROM hotel_images WHERE hotel_id = h.id LIMIT 1) as gallery_image 
    FROM hotels h 
    LEFT JOIN rooms r ON h.id = r.hotel_id
";
$params = [];

if ($destination) {
    $query .= " WHERE (h.name LIKE ? OR h.location LIKE ?)";
    $params[] = "%$destination%";
    $params[] = "%$destination%";
}

$query .= " GROUP BY h.id";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$hotels = $stmt->fetchAll();

include 'includes/header.php';
?>

<style>
    /* Ensures the dropdown floats over results and filters */
    .hero-search-container {
        position: relative;
        z-index: 2000; /* Stays above the filter sidebar and results */
        background: white; 
        padding: 20px 0;
    }

    /* THE FIX: Ensure the dropdown is NOT clipped by the card or container */
    .hero-search-container .container, 
    .hero-search-container .card {
        overflow: visible !important;
    }

    #homeGuestDropdown {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 300px;
        z-index: 9999 !important; /* Critical: Must be highest in the document */
        background: white;
        border-radius: 8px;
        margin-top: 10px;
        border: 1px solid #ddd;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    .suggestions-list {
        position: absolute;
        z-index: 9999;
        background: white;
        width: 100%;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        border-radius: 4px;
        display: none;
    }

    .suggestion-item {
        padding: 10px;
        cursor: pointer;
    }

    .suggestion-item:hover {
        background-color: #f8f9fa;
    }
</style>

<div class="hero-search-container">
    <div class="container">
        <div class="card shadow-sm border-0 p-2">
            <form action="hotels.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-3 position-relative">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0"><i class="fas fa-bed text-secondary"></i></span>
                        <input type="text" name="destination" id="destinationInput" class="form-control border-0 shadow-none ps-0" placeholder="Where are you going?" value="<?php echo htmlspecialchars($destination); ?>" required autocomplete="off">
                        <div id="suggestionsList" class="suggestions-list"></div>
                    </div>
                </div>
                
                <div class="col-md-2 border-start">
                    <div class="d-flex flex-column px-2">
                        <small class="text-muted" style="font-size: 10px;">Check-in</small>
                        <input type="date" name="check_in" id="checkIn" class="form-control border-0 p-0 shadow-none fw-bold" style="font-size: 14px;" value="<?php echo $check_in; ?>">
                    </div>
                </div>
                <div class="col-md-2 border-start">
                    <div class="d-flex flex-column px-2">
                        <small class="text-muted" style="font-size: 10px;">Check-out</small>
                        <input type="date" name="check_out" id="checkOut" class="form-control border-0 p-0 shadow-none fw-bold" style="font-size: 14px;" value="<?php echo $check_out; ?>">
                    </div>
                </div>

                <div class="col-md-3 border-start position-relative">
                    <div class="d-flex align-items-center p-2" id="guestToggle" style="cursor: pointer;">
                        <i class="fas fa-user-friends text-secondary me-2"></i>
                        <span id="guestLabel" class="text-truncate" style="font-size: 14px;">
                            <?php echo "$adults Adult, $children Children, $rooms Room"; ?>
                        </span>
                    </div>

                    <input type="hidden" name="adults" id="input_adults" value="<?php echo $adults; ?>">
                    <input type="hidden" name="children" id="input_children" value="<?php echo $children; ?>">
                    <input type="hidden" name="rooms" id="input_rooms" value="<?php echo $rooms; ?>">

                    <div class="guest-dropdown card shadow p-3" id="homeGuestDropdown">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold">Adults</span>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateHomeGuest('adult', -1)">-</button>
                                <span id="hAdultQty" class="mx-3"><?php echo $adults; ?></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateHomeGuest('adult', 1)">+</button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold">Children</span>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateHomeGuest('child', -1)">-</button>
                                <span id="hChildQty" class="mx-3"><?php echo $children; ?></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateHomeGuest('child', 1)">+</button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-bold">Rooms</span>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateHomeGuest('room', -1)">-</button>
                                <span id="hRoomQty" class="mx-3"><?php echo $rooms; ?></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" onclick="updateHomeGuest('room', 1)">+</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary w-100 btn-sm fw-bold" onclick="closeHomeGuest()">Done</button>
                    </div>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Search</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <h5 class="fw-bold mb-3">Filter by</h5>
                <hr>
                <p class="fw-bold mb-2">Price Range (NPR)</p>
                <input type="range" class="form-range filter-input" id="priceRange" min="0" max="20000" value="20000">
                <div class="d-flex justify-content-between"><small>0</small><small id="priceValue">20000</small></div>
                
                <p class="fw-bold mt-4">Star Rating</p>
                <?php for($i=5; $i>=1; $i--): ?>
                <div class="form-check">
                    <input class="form-check-input filter-input" type="checkbox" name="stars" value="<?php echo $i; ?>" id="star<?php echo $i; ?>">
                    <label class="form-check-label" for="star<?php echo $i; ?>"><?php echo $i; ?> Stars</label>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="col-md-9" id="hotelResults">
            <h3 class="mb-4">Search Results in <?php echo htmlspecialchars($destination ?: 'all locations'); ?></h3>
            <?php foreach ($hotels as $hotel): ?>
            <div class="card mb-3 shadow-sm border-0 overflow-hidden">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img src="<?php echo BASE_URL . 'uploads/hotels/' . ($hotel['gallery_image'] ?: $hotel['image']); ?>" class="img-fluid h-100 w-100" style="object-fit: cover; min-height: 200px;" alt="...">
                    </div>
                    <div class="col-md-5">
                        <div class="card-body">
                            <h5 class="card-title fw-bold"><?php echo $hotel['name']; ?></h5>
                            <div class="text-warning mb-2">
                                <?php for($i=0; $i<$hotel['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                            </div>
                            <p class="text-muted mb-2 small"><i class="fas fa-map-marker-alt text-primary"></i> <?php echo $hotel['location']; ?></p>
                            <span class="badge bg-primary mb-2">4.5 Excellent</span> <small class="text-muted">(120 reviews)</small>
                            <p class="card-text small text-secondary"><?php echo substr($hotel['description'], 0, 80); ?>...</p>
                        </div>
                    </div>
                    <div class="col-md-3 border-start bg-light d-flex flex-column justify-content-center align-items-center p-3">
                        <small class="text-muted">Price starts from</small>
                        <h4 class="fw-bold text-dark">NPR <?php echo number_format($hotel['start_price'] ?? 0); ?></h4>
                        <small class="text-muted mb-3">per night</small>
                        <a href="hotel_detail.php?id=<?php echo $hotel['id']; ?>" class="btn btn-primary w-100">Select Room <i class="fas fa-chevron-right ms-1"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($hotels)): ?>
                <div class="text-center py-5"><p class="lead">No hotels found matching your search.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const guestToggle = document.getElementById('guestToggle');
    const dropdown = document.getElementById('homeGuestDropdown');
    const label = document.getElementById('guestLabel');
    
    // State management
    let guests = { 
        adult: <?php echo $adults; ?>, 
        child: <?php echo $children; ?>, 
        room: <?php echo $rooms; ?> 
    };

    // Toggle dropdown
    guestToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    // Global update function
    window.updateHomeGuest = function(type, change) {
        if(type === 'adult') guests.adult = Math.max(1, guests.adult + change);
        if(type === 'child') guests.child = Math.max(0, guests.child + change);
        if(type === 'room') guests.room = Math.max(1, guests.room + change);
        
        // Update display numbers
        document.getElementById('hAdultQty').innerText = guests.adult;
        document.getElementById('hChildQty').innerText = guests.child;
        document.getElementById('hRoomQty').innerText = guests.room;
        
        // Update hidden form inputs
        document.getElementById('input_adults').value = guests.adult;
        document.getElementById('input_children').value = guests.child;
        document.getElementById('input_rooms').value = guests.room;
        
        // Update main label text
        label.innerText = `${guests.adult} Adult, ${guests.child} Children, ${guests.room} Room`;
    };

    window.closeHomeGuest = function() {
        dropdown.style.display = 'none';
    };

    document.addEventListener('click', (e) => {
        if(!dropdown.contains(e.target) && !guestToggle.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});
</script>

<script src="<?php echo BASE_URL; ?>assets/js/hotels.js"></script>
<?php include 'includes/footer.php'; ?>