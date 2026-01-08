<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/redirect.php';

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    $status = ($action == 'block') ? 'blocked' : 'active';

    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    redirect('admin/user/index.php', 'success', 'User status updated');
}
redirect('admin/user/index.php');
?>
