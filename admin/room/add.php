<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/sanitize.php';
require_once '../../helpers/upload.php';
require_once '../../helpers/redirect.php';

$hotels = $pdo->query("SELECT * FROM hotels")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hotel_id = $_POST['hotel_id'];
    $type = sanitize($_POST['type']);
    $capacity = $_POST['capacity'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $description = sanitize($_POST['description']);
    $image = '';

    if (!empty($_FILES['image']['name'])) {
        $image = uploadImage($_FILES['image'], 'uploads/rooms/');
        if (!$image) {
            $error = "Image upload failed!";
        }
    }

    if (!isset($error)) {
        $stmt = $pdo->prepare("INSERT INTO rooms (hotel_id, type, capacity, price, quantity, description, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$hotel_id, $type, $capacity, $price, $quantity, $description, $image])) {
            redirect('admin/room/index.php', 'success', 'Room added successfully');
        } else {
            $error = "Database error";
        }
    }
}

include '../includes/header.php';
?>

<h2>Add Room</h2>
<div class="card">
    <div class="card-body">
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Select Hotel</label>
                <select name="hotel_id" class="form-control" required>
                    <option value="">-- Select Hotel --</option>
                    <?php foreach($hotels as $hotel): ?>
                        <option value="<?php echo $hotel['id']; ?>"><?php echo $hotel['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Room Type</label>
                <select name="type" id="room_type" class="form-control" onchange="updateCapacity()" required>
                    <option value="">-- Select Type --</option>
                    <option value="Single Room" data-cap="1">Single Room (1 Person)</option>
                    <option value="Double Room" data-cap="2">Double Room (2 People)</option>
                    <option value="Deluxe Room" data-cap="3">Deluxe Room (3 People)</option>
                    <option value="Family Suite" data-cap="4">Family Suite (4 People)</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label>Capacity (Persons)</label>
                <input type="number" name="capacity" id="capacity" class="form-control" required readonly>
            </div>

            <div class="mb-3">
                <label>Price</label>
                <input type="number" name="price" class="form-control" step="0.01" required>
            </div>
            <div class="mb-3">
                <label>Quantity</label>
                <input type="number" name="quantity" class="form-control" required>
            </div>

            <script>
            function updateCapacity() {
                var select = document.getElementById('room_type');
                var option = select.options[select.selectedIndex];
                var capacity = option.getAttribute('data-cap');
                document.getElementById('capacity').value = capacity ? capacity : '';
            }
            </script>
            <div class="mb-3">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
            <div class="mb-3">
                <label>Image</label>
                <input type="file" name="image" class="form-control">
            </div>
            <button type="submit" class="btn btn-success">Save Room</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
