<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/redirect.php';

// 1. Handle Search and Filtering Logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// 2. Build the Real-Life Query with Prepared Statements
$query = "SELECT bookings.*, users.name as user_name, rooms.type as room_type, hotels.name as hotel_name 
          FROM bookings 
          JOIN users ON bookings.user_id = users.id 
          JOIN rooms ON bookings.room_id = rooms.id 
          JOIN hotels ON rooms.hotel_id = hotels.id";

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = "(users.name LIKE :search OR hotels.name LIKE :search OR bookings.id = :id_search)";
    $params[':search'] = "%$search%";
    $params[':id_search'] = is_numeric($search) ? $search : 0;
}

if ($status_filter !== '') {
    $conditions[] = "bookings.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY bookings.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// 3. Stats for the Top Header (Real-life business insight)
$totalRevenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'confirmed'")->fetchColumn() ?? 0;

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-check me-2"></i>Manage Bookings</h2>
        <div class="text-end">
            <span class="text-muted small">Total Confirmed Revenue:</span>
            <h5 class="text-success mb-0">NPR <?php echo number_format($totalRevenue, 2); ?></h5>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search by Guest Name, Hotel, or ID..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Filter</button>
                    <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Guest Details</th>
                            <th>Property & Room</th>
                            <th>Stay Period</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($bookings) > 0): ?>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><span class="fw-bold">#<?php echo $booking['id']; ?></span></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                   
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($booking['hotel_name']); ?></div>
                                    <small class="badge bg-light text-dark border"><?php echo htmlspecialchars($booking['room_type']); ?></small>
                                </td>
                                <td>
                                    <small>
                                        <strong>In:</strong> <?php echo date('M d, Y', strtotime($booking['check_in'])); ?><br>
                                        <strong>Out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark">NPR <?php echo number_format($booking['total_price'], 2); ?></span>
                                </td>
                                <td>
                                    <?php 
                                        $badgeColor = 'warning';
                                        if($booking['status'] == 'confirmed') $badgeColor = 'success';
                                        if($booking['status'] == 'cancelled') $badgeColor = 'danger';
                                    ?>
                                    <span class="badge rounded-pill bg-<?php echo $badgeColor; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group shadow-sm">
                                        <a href="view.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Status
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item text-success" href="update_status.php?id=<?php echo $booking['id']; ?>&status=confirmed"><i class="fas fa-check-circle me-2"></i>Confirm Booking</a></li>
                                            <li><a class="dropdown-item text-danger" href="update_status.php?id=<?php echo $booking['id']; ?>&status=cancelled"><i class="fas fa-times-circle me-2"></i>Cancel Booking</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No bookings found matching your criteria.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>