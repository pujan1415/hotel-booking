<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../helpers/redirect.php';

/** * DATABASE LOGIC 
 */

// 1. Core Counts
$hotelCount = $pdo->query("SELECT COUNT(*) FROM hotels")->fetchColumn();
$roomCount  = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$userCount  = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// 2. Revenue Logic (Sum of all confirmed bookings)
$totalRevenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'confirmed'")->fetchColumn() ?? 0;

// 3. Status Tracking
$pendingBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();

// 4. Recent Activity (Fetch last 10 bookings for a fuller table)
$stmt = $pdo->query("
    SELECT b.*, u.name as user_name, h.name as hotel_name 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    JOIN hotels h ON r.hotel_id = h.id
    ORDER BY b.id DESC LIMIT 10
");
$recentBookings = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">System Dashboard</h1>
    </div>

    <div class="row">

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">NPR <?php echo number_format($totalRevenue, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Actions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pendingBookings; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Hotels</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $hotelCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Registered Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $userCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Recent Booking Activity</h6>
                    <a href="bookings/index.php" class="btn btn-sm btn-primary">
                        View All Bookings
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#ID</th>
                                    <th>Customer Name</th>
                                    <th>Hotel Property</th>
                                    <th>Amount</th>
                                    <th>Check-In</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recentBookings) > 0): ?>
                                    <?php foreach ($recentBookings as $res): ?>
                                    <tr>
                                        <td><strong>#<?php echo $res['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($res['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($res['hotel_name']); ?></td>
                                        <td>NPR <?php echo number_format($res['total_price']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($res['check_in'])); ?></td>
                                        <td>
                                            <?php 
                                                $statusClass = 'warning';
                                                if($res['status'] == 'confirmed') $statusClass = 'success';
                                                if($res['status'] == 'cancelled') $statusClass = 'danger';
                                            ?>
                                            <span class="badge rounded-pill bg-<?php echo $statusClass; ?>">
                                                <?php echo ucfirst($res['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="bookings/view.php?id=<?php echo $res['id']; ?>" class="btn btn-sm btn-outline-dark">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No recent bookings recorded in the system.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>