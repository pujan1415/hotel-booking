<?php
require_once '../config/config.php';
require_once '../config/db.php';

$destination = isset($_POST['destination']) ? $_POST['destination'] : '';
$stars = isset($_POST['stars']) ? $_POST['stars'] : []; // Array of star ratings
$min_price = isset($_POST['min_price']) ? (int)$_POST['min_price'] : 0;
$max_price = isset($_POST['max_price']) ? (int)$_POST['max_price'] : 100000;
$check_in = isset($_POST['check_in']) ? $_POST['check_in'] : '';
$check_out = isset($_POST['check_out']) ? $_POST['check_out'] : '';
$adults = isset($_POST['adults']) ? (int)$_POST['adults'] : 1;
$children = isset($_POST['children']) ? (int)$_POST['children'] : 0;
$rooms = isset($_POST['rooms']) ? (int)$_POST['rooms'] : 1;

// Build Query
$query = "
    SELECT h.*, MIN(r.price) as start_price,
    (SELECT image_path FROM hotel_images WHERE hotel_id = h.id LIMIT 1) as gallery_image
    FROM hotels h 
    LEFT JOIN rooms r ON h.id = r.hotel_id 
";

$params = [];
$conditions = [];

// Destination Filter
if ($destination) {
    $conditions[] = "(h.name LIKE ? OR h.location LIKE ?)";
    $params[] = "%$destination%";
    $params[] = "%$destination%";
}

// Star Rating Filter
if (!empty($stars)) {
    $placeholders = implode(',', array_fill(0, count($stars), '?'));
    $conditions[] = "h.rating IN ($placeholders)";
    foreach ($stars as $star) {
        $params[] = $star;
    }
}

$conditions[] = "1=1";

// Apply WHERE conditions
if (count($conditions) > 0) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " GROUP BY h.id";

// Apply Price Filter (HAVING)
// Show if price is within range OR if price is NULL (no rooms) and we are looking at the default min (0)
// This ensures hotels without rooms don't disappear unless we explicitly filter for higher price
$query .= " HAVING (start_price >= ? AND start_price <= ?) OR (start_price IS NULL AND ? = 0)";
$params[] = $min_price;
$params[] = $max_price;
$params[] = $min_price;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$hotels = $stmt->fetchAll();

// Generate HTML
if (count($hotels) > 0) {
    foreach ($hotels as $hotel) {
        // Star Logic
        $r = isset($hotel['rating']) ? $hotel['rating'] : 3;
        $stars_html = '';
        for($i=0; $i<$r; $i++) $stars_html .= '<i class="fas fa-star"></i>';
        
        if(isset($hotel['gallery_image']) && $hotel['gallery_image']) {
            $imgSrc = BASE_URL . 'uploads/hotels/' . $hotel['gallery_image'];
        } elseif($hotel['image']) {
            $imgSrc = BASE_URL . 'uploads/hotels/' . $hotel['image'];
        } else {
            $imgSrc = 'https://via.placeholder.com/400x300';
        }
        $price_formatted = number_format($hotel['start_price'] ?? 0);
        $desc_short = substr($hotel['description'], 0, 120) . '...';
        
        echo <<<HTML
        <div class="card shadow-sm border-0 mb-3">
            <div class="row g-0">
                <div class="col-md-4">
                    <img src="$imgSrc" class="img-fluid rounded-start h-100" alt="{$hotel['name']}" style="object-fit: cover; min-height: 200px;">
                </div>
                <div class="col-md-5 p-3">
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title fw-bold text-list-name mb-1">{$hotel['name']}</h5>
                        <div class="text-warning small">
                            $stars_html
                        </div>
                    </div>
                    <p class="small text-muted mb-2"><i class="fas fa-map-marker-alt text-success"></i> {$hotel['location']}</p>
                    <div class="mb-2">
                        <span class="badge bg-success">4.5</span> <span class="small fw-bold text-dark">Excellent</span> <span class="small text-muted">(120 reviews)</span>
                    </div>
                    <p class="card-text small text-secondary">
                        $desc_short
                    </p>
                </div>
                <div class="col-md-3 p-3 border-start d-flex flex-column justify-content-between align-items-end text-end bg-light">
                    <div>
                        <small class="text-muted">Price starts from</small>
                        <h4 class="fw-bold text-dark mb-0">NPR $price_formatted</h4>
                        <small class="text-muted">per night</small>
                    </div>
                    <a href="hotel_detail.php?id={$hotel['id']}&check_in=$check_in&check_out=$check_out&adults=$adults&children=$children&rooms_count=$rooms" class="btn btn-primary w-100 mt-2 fw-bold">Select Room <i class="fas fa-chevron-right small"></i></a>
                </div>
            </div>
        </div>
HTML;
    }
} else {
    echo '<div class="text-center py-5"><i class="fas fa-search fa-3x text-muted mb-3"></i><h4>No properties found</h4><p class="text-muted">Try changing your filters</p></div>';
}
?>

