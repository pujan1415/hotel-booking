<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/sanitize.php';
require_once '../../helpers/upload.php';
require_once '../../helpers/redirect.php';

if (!isset($_GET['id'])) {
    redirect('admin/room/index.php');
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$id]);
$room = $stmt->fetch();
$hotels = $pdo->query("SELECT * FROM hotels")->fetchAll();

if (!$room) {
    redirect('admin/room/index.php', 'danger', 'Room not found');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hotel_id = $_POST['hotel_id'];
    $type = sanitize($_POST['type']);
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $description = sanitize($_POST['description']);
    $image = $room['image'];

    if (!empty($_FILES['image']['name'])) {
        $uploaded = uploadImage($_FILES['image'], 'uploads/rooms/');
        if ($uploaded) {
            $image = $uploaded;
        }
    }

    $stmt = $pdo->prepare("UPDATE rooms SET hotel_id=?, type=?, price=?, quantity=?, description=?, image=? WHERE id=?");
    if ($stmt->execute([$hotel_id, $type, $price, $quantity, $description, $image, $id])) {
        redirect('admin/room/index.php', 'success', 'Room updated successfully');
    } else {
        $error = "Database error";
    }
}

include '../includes/header.php';
?>

<h2>Edit Room</h2>
<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Select Hotel</label>
                <select name="hotel_id" class="form-control" required>
                    <?php foreach($hotels as $hotel): ?>
                        <option value="<?php echo $hotel['id']; ?>" <?php if($hotel['id'] == $room['hotel_id']) echo 'selected'; ?>>
                            <?php echo $hotel['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Room Type</label>
                <input type="text" name="type" class="form-control" value="<?php echo $room['type']; ?>" required>
            </div>
            <div class="mb-3">
                <label>Price</label>
                <input type="number" name="price" class="form-control" step="0.01" value="<?php echo $room['price']; ?>" required>
            </div>
            <div class="mb-3">
                <label>Quantity</label>
                <input type="number" name="quantity" class="form-control" value="<?php echo $room['quantity']; ?>" required>
            </div>
            <div class="mb-3">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4"><?php echo $room['description']; ?></textarea>
            </div>
            <div class="mb-3">
                <label>Current Image</label><br>
                <?php if($room['image']): ?>
                    <img src="<?php echo BASE_URL . 'uploads/rooms/' . $room['image']; ?>" width="100"><br>
                <?php endif; ?>
                <label>Change Image</label>
                <input type="file" name="image" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Update Room</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
