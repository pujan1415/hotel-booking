<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/redirect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
    if ($stmt->execute([$id])) {
        redirect('admin/hotel/index.php', 'success', 'Hotel deleted successfully');
    } else {
        redirect('admin/hotel/index.php', 'danger', 'Failed to delete hotel');
    }
} else {
    redirect('admin/hotel/index.php');
}
?>
