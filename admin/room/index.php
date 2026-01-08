<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/redirect.php';

// 1. Handle Search/Filter Logic (Real-life systems need filters)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$hotel_filter = isset($_GET['hotel_id']) ? $_GET['hotel_id'] : '';

// 2. Fetch Hotels for the filter dropdown
$hotelsList = $pdo->query("SELECT id, name FROM hotels ORDER BY name ASC")->fetchAll();

// 3. Build Protected Query
$query = "SELECT rooms.*, hotels.name as hotel_name 
          FROM rooms 
          JOIN hotels ON rooms.hotel_id = hotels.id";

$params = [];
if ($search || $hotel_filter) {
    $query .= " WHERE 1=1";
    if ($search) {
        $query .= " AND (rooms.type LIKE :search OR hotels.name LIKE :search)";
        $params['search'] = "%$search%";
    }
    if ($hotel_filter) {
        $query .= " AND rooms.hotel_id = :hotel_id";
        $params['hotel_id'] = $hotel_filter;
    }
}
$query .= " ORDER BY rooms.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800"><i class="fas fa-bed me-2"></i>Room Inventory</h2>
        <a href="add.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Add New Room
        </a>
    </div>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form action="" method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search room type..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="hotel_id" class="form-select">
                        <option value="">All Hotels</option>
                        <?php foreach($hotelsList as $h): ?>
                            <option value="<?php echo $h['id']; ?>" <?php echo ($hotel_filter == $h['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($h['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Room Details</th>
                            <th>Hotel</th>
                            <th>Price / Night</th>
                            <th>Availability</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rooms) > 0): ?>
                            <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <?php if($room['image']): ?>
                                                <img src="<?php echo BASE_URL . 'uploads/rooms/' . $room['image']; ?>" 
                                                     class="rounded shadow-sm" width="60" height="45" style="object-fit: cover;" alt="Room">
                                            <?php else: ?>
                                                <div class="rounded bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 60px; height: 45px;">
                                                    <i class="fas fa-image fa-xs"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($room['type']); ?></div>
                                            <small class="text-muted">ID: #<?php echo $room['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-primary fw-600"><?php echo htmlspecialchars($room['hotel_name']); ?></span>
                                </td>
                                <td>
                                    <span class="text-dark fw-bold">NPR <?php echo number_format($room['price'], 2); ?></span>
                                </td>
                                <td>
                                    <?php if($room['quantity'] > 5): ?>
                                        <span class="badge bg-success-soft text-success border border-success px-3">
                                            <?php echo $room['quantity']; ?> Units
                                        </span>
                                    <?php elseif($room['quantity'] > 0): ?>
                                        <span class="badge bg-warning-soft text-warning border border-warning px-3">
                                            Low Stock (<?php echo $room['quantity']; ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-soft text-danger border border-danger px-3">
                                            Sold Out
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group shadow-sm border rounded">
                                        <a href="edit.php?id=<?php echo $room['id']; ?>" class="btn btn-white btn-sm px-3" title="Edit">
                                            <i class="fas fa-edit text-warning"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $room['id']; ?>" class="btn btn-white btn-sm px-3" 
                                           onclick="return confirm('Deleting this room type will affect existing records. Continue?')" title="Delete">
                                            <i class="fas fa-trash text-danger"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No rooms found in inventory.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>