<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/redirect.php';

// 1. Capture Search Input
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 2. Real-Life Query: Fetch users and their total booking count
$sql = "SELECT users.*, 
        (SELECT COUNT(*) FROM bookings WHERE user_id = users.id) as total_bookings 
        FROM users";

if ($search !== '') {
    $sql .= " WHERE users.name LIKE :search 
              OR users.email LIKE :search 
              OR users.phone LIKE :search";
}

$sql .= " ORDER BY users.id DESC";

$stmt = $pdo->prepare($sql);

if ($search !== '') {
    $stmt->bindValue(':search', '%' . $search . '%');
}

$stmt->execute();
$users = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-users-cog me-2 text-primary"></i>User Management</h1>
        <div class="text-muted small">Total Registered: <strong><?php echo count($users); ?></strong></div>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-light rounded">
            <form action="" method="GET" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" 
                               placeholder="Search by name, email, or phone number..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter Users</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th class="ps-4 py-3">User Profile</th>
                            <th>Contact Info</th>
                            <th>History</th>
                            <th>Status</th>
                            <th class="text-center">Account Security</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3 bg-secondary text-white d-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px;">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['name']); ?></div>
                                            <small class="text-muted">ID: #<?php echo $user['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small"><i class="fas fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($user['email']); ?></div>
                                    <div class="small"><i class="fas fa-phone me-2 text-muted"></i><?php echo htmlspecialchars($user['phone']); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fas fa-book me-1"></i> <?php echo $user['total_bookings']; ?> Bookings
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?php echo ($user['status'] == 'active') ? 'bg-success' : 'bg-danger'; ?> px-3">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if($user['status'] == 'active'): ?>
                                        <a href="block.php?id=<?php echo $user['id']; ?>&action=block" 
                                           class="btn btn-sm btn-outline-danger px-3 shadow-sm" 
                                           onclick="return confirm('Suspended users cannot log in. Proceed?')">
                                            <i class="fas fa-user-slash me-1"></i> Block
                                        </a>
                                    <?php else: ?>
                                        <a href="block.php?id=<?php echo $user['id']; ?>&action=unblock" 
                                           class="btn btn-sm btn-outline-success px-3 shadow-sm" 
                                           onclick="return confirm('Restore access for this user?')">
                                            <i class="fas fa-user-check me-1"></i> Unblock
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-user-friends fa-3x mb-3 opacity-25"></i>
                                        <h5>No users found</h5>
                                        <p>No records match your current search criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>