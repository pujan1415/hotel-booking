<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../helpers/sanitize.php';
require_once '../../helpers/upload.php';
require_once '../../helpers/redirect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $location = sanitize($_POST['location']);
    $description = sanitize($_POST['description']);
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 3;
    $image = '';

        $stmt = $pdo->prepare("INSERT INTO hotels (name, location, description, rating) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $location, $description, $rating])) {
            $hotel_id = $pdo->lastInsertId();
            
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
                            // Store relative path including folder
                            $dbPath = $hotelDirName . '/' . $fileName;
                            $imgStmt->execute([$hotel_id, $dbPath]);
                        }
                    }
                }
            }
            
            redirect('admin/hotel/index.php', 'success', 'Hotel added successfully');
        } else {
            $error = "Database error";
        }
    }


include '../includes/header.php';
?>

<h2>Add Hotel</h2>
<div class="card">
    <div class="card-body">
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Location</label>
                <input type="text" name="location" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Rating (Stars)</label>
                <select name="rating" class="form-control">
                    <option value="1">1 Star</option>
                    <option value="2">2 Star</option>
                    <option value="3" selected>3 Star</option>
                    <option value="4">4 Star</option>
                    <option value="5">5 Star</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
            <div class="mb-3">
                <label>Gallery Images (Select Multiple)</label>
                <input type="file" name="images[]" class="form-control" multiple>
            </div>
            <button type="submit" class="btn btn-success">Save Hotel</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
