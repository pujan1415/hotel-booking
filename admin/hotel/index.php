<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/redirect.php';

// 1. Capture Search Input
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 2. Real-Life Query: Includes a SUBQUERY to count room types for each hotel
$sql = "SELECT h.*, 
        (SELECT COUNT(*) FROM rooms WHERE hotel_id = h.id) as room_count,
        (SELECT image_path FROM hotel_images WHERE hotel_id = h.id LIMIT 1) as image
        FROM hotels h";

if ($search !== '') {
    $sql .= " WHERE h.name LIKE :search OR h.location LIKE :search";
}

$sql .= " ORDER BY h.id DESC";

$stmt = $pdo->prepare($sql);

if ($search !== '') {
    $stmt->bindValue(':search', '%' . $search . '%');
}

$stmt->execute();
$hotels = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-city me-2"></i>Property Portfolio</h1>
        <a href="add.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus-circle me-1"></i> Register New Hotel
        </a>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" 
                               placeholder="Search by hotel name or location..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <button type="submit" class="btn btn-dark w-100">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4" width="80">ID</th>
                            <th>Property Info</th>
                            <th>Location</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($hotels) > 0): ?>
                            <?php foreach ($hotels as $hotel): ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?php echo $hotel['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <?php if($hotel['image']): ?>
                                                <img src="<?php echo BASE_URL . 'uploads/hotels/' . $hotel['image']; ?>" 
                                                     class="rounded shadow-sm" width="60" height="45" 
                                                     style="object-fit: cover;" alt="Hotel">
                                            <?php else: ?>
                                                <div class="rounded bg-light border d-flex align-items-center justify-content-center text-muted" 
                                                     style="width: 60px; height: 45px;">
                                                    <i class="fas fa-image fa-xs"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($hotel['name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small"><i class="fas fa-map-marker-alt text-danger me-1"></i><?php echo htmlspecialchars($hotel['location']); ?></div>
                                </td>
                                
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="edit.php?id=<?php echo $hotel['id']; ?>" class="btn btn-sm btn-outline-warning border-0" title="Edit">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $hotel['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger border-0" 
                                           onclick="return confirm('WARNING: Deleting this hotel will remove all associated rooms. Continue?')" 
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-hotel fa-3x mb-3 d-block opacity-25"></i>
                                    No properties found in your portfolio.
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