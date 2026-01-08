<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/sanitize.php';
require_once '../../helpers/upload.php';
require_once '../../helpers/redirect.php';

if (!isset($_GET['id'])) {
    redirect('admin/hotel/index.php');
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->execute([$id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    redirect('admin/hotel/index.php', 'danger', 'Hotel not found');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $location = sanitize($_POST['location']);
    $description = sanitize($_POST['description']);
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 3;
    $image = $hotel['image'];

    // Generate folder name based on hotel name (sanitized)
    $hotelDirName = preg_replace('/[^A-Za-z0-9\-]/', '_', $name);

    if (!empty($_FILES['image']['name'])) {
        // Upload to specific hotel folder
        // Note: uploadImage appends result filename to targetDir in its return? 
        // No, uploadImage returns JUST the filename. 
        // targetDir needs to include the subfolder.
        
        $targetSubDir = 'uploads/hotels/' . $hotelDirName . '/';
        $uploaded = uploadImage($_FILES['image'], $targetSubDir);
        
        if ($uploaded) {
            // Save relative path: Folder/Filename
            $image = $hotelDirName . '/' . $uploaded;
        }
    }

    $stmt = $pdo->prepare("UPDATE hotels SET name=?, location=?, description=?, rating=? WHERE id=?");
    if ($stmt->execute([$name, $location, $description, $rating, $id])) {
        
        // Handle Multiple Images
        if (!empty($_FILES['images']['name'][0])) {
            $files = $_FILES['images'];
            $count = count($files['name']);
            
            $imgStmt = $pdo->prepare("INSERT INTO hotel_images (hotel_id, image_path) VALUES (?, ?)");
            
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] === 0) {
                        $hotelDirName = preg_replace('/[^A-Za-z0-9\-]/', '_', $name);
                        $uploadDir = '../../uploads/hotels/' . $hotelDirName;
                        
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }

                    $fileTmp = $files['tmp_name'][$i];
                    $fileName = time() . '_' . $files['name'][$i];
                    $targetPath = $uploadDir . '/' . $fileName;
                    
                    if (move_uploaded_file($fileTmp, $targetPath)) {
                        $dbPath = $hotelDirName . '/' . $fileName;
                        $imgStmt->execute([$id, $dbPath]);
                    }
                }
            }
        }
        
        redirect('admin/hotel/index.php', 'success', 'Hotel updated successfully');
    } else {
        $error = "Database error";
    }
}

// Fetch Images
$imgQ = $pdo->prepare("SELECT * FROM hotel_images WHERE hotel_id = ?");
$imgQ->execute([$id]);
$hotel_images = $imgQ->fetchAll();

include '../includes/header.php';
?>

<h2>Edit Hotel</h2>
<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo $hotel['name']; ?>" required>
            </div>
            <div class="mb-3">
                <label>Location</label>
                <input type="text" name="location" class="form-control" value="<?php echo $hotel['location']; ?>" required>
            </div>
            <div class="mb-3">
                <label>Rating (Stars)</label>
                <select name="rating" class="form-control">
                    <?php 
                    $current_rating = isset($hotel['rating']) ? $hotel['rating'] : 3; 
                    for($i=1; $i<=5; $i++): 
                    ?>
                        <option value="<?php echo $i; ?>" <?php if($i == $current_rating) echo 'selected'; ?>><?php echo $i; ?> Star</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4"><?php echo $hotel['description']; ?></textarea>
            </div>
            
            <div class="mb-3">
                <label>Current Gallery Images</label><br>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach($hotel_images as $img): ?>
                        <div class="position-relative">
                            <img src="<?php echo BASE_URL . 'uploads/hotels/' . $img['image_path']; ?>" width="100" height="100" style="object-fit:cover; border-radius:4px;">
                            <a href="delete_image.php?id=<?php echo $img['id']; ?>&hotel_id=<?php echo $id; ?>" class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0" style="width:20px;height:20px;line-height:18px;" onclick="return confirm('Delete this image?')">&times;</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mb-3">
                <label>Add More Images</label>
                <input type="file" name="images[]" class="form-control" multiple>
            </div>
            <button type="submit" class="btn btn-primary">Update Hotel</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
