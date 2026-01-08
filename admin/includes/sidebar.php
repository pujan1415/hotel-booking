<div class="sidebar d-flex flex-column p-3 text-white">
    <h4 class="text-center py-3">Hotel booking Admin</h4>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-link">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/hotel/index.php" class="nav-link">
                <i class="fas fa-hotel me-2"></i> Hotels
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/room/index.php" class="nav-link">
                <i class="fas fa-bed me-2"></i> Rooms
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/bookings/index.php" class="nav-link">
                <i class="fas fa-calendar-check me-2"></i> Bookings
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>admin/user/index.php" class="nav-link">
                <i class="fas fa-users me-2"></i> Users
            </a>
        </li>
        <li class="mt-4">
            <a href="<?php echo BASE_URL; ?>admin/auth/logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </li>
    </ul>
</div>
