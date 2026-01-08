<?php
require_once '../config/config.php';
require_once '../config/db.php';

$search = isset($_GET['q']) ? $_GET['q'] : '';
$stmt = $pdo->prepare("SELECT id, name, location FROM hotels WHERE name LIKE ? OR location LIKE ? LIMIT 5");
$stmt->execute(["%$search%", "%$search%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
?>
