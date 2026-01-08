<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/redirect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    if ($stmt->execute([$id])) {
        redirect('admin/room/index.php', 'success', 'Room deleted successfully');
    } else {
        redirect('admin/room/index.php', 'danger', 'Failed to delete room');
    }
} else {
    redirect('admin/room/index.php');
}
?>
