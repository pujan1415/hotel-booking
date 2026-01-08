<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/redirect.php';

if (isset($_GET['id']) && isset($_GET['hotel_id'])) {
    $id = $_GET['id'];
    $hotel_id = $_GET['hotel_id'];
    
    // Get image path to unlink file
    $stmt = $pdo->prepare("SELECT image_path FROM hotel_images WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetch();
    
    if ($img) {
        $path = '../../uploads/hotels/' . $img['image_path'];
        if (file_exists($path)) {
            unlink($path);
        }
        
        $del = $pdo->prepare("DELETE FROM hotel_images WHERE id = ?");
        $del->execute([$id]);
    }
    
    redirect('admin/hotel/edit.php?id=' . $hotel_id, 'success', 'Image deleted');
} else {
    redirect('admin/hotel/index.php');
}
?>
