<?php
require_once '../config/config.php';
require_once '../config/db.php';

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];

// Get unique locations
$stmt = $pdo->prepare("SELECT DISTINCT location FROM hotels WHERE location LIKE ? LIMIT 5");
$stmt->execute(["%$term%"]);
$locations = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Add locations to results
foreach ($locations as $location) {
    $results[] = [
        'type' => 'location',
        'value' => $location,
        'label' => $location
    ];
}

// Get hotels with their locations
$stmt = $pdo->prepare("SELECT DISTINCT name, location FROM hotels WHERE name LIKE ? OR location LIKE ? LIMIT 5");
$stmt->execute(["%$term%", "%$term%"]);
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add hotels to results
foreach ($hotels as $hotel) {
    $results[] = [
        'type' => 'hotel',
        'value' => $hotel['name'],
        'label' => $hotel['name'],
        'location' => $hotel['location']
    ];
}

// Limit to 10 total results
$results = array_slice($results, 0, 10);

echo json_encode($results);
?>
