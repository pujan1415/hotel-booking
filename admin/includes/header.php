<?php require_once __DIR__ . '/../../config/config.php'; ?>
<?php require_once __DIR__ . '/../auth/admin_guard.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/assets/css/admin.css">
</head>
<body>
    <div class="d-flex">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="flex-grow-1">
            <nav class="navbar navbar-light bg-light shadow-sm px-4">
                <span class="navbar-brand mb-0 h1">Admin Panel</span>
                <span class="ms-auto">Welcome, <?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin'; ?></span>
            </nav>
            <div class="content">
            <?php 
                if (function_exists('flash_message')) {
                    flash_message();
                }
            ?>
