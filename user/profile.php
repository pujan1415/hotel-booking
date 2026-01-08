<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../auth/user_auth_check.php';
require_once '../helpers/sanitize.php';
require_once '../helpers/redirect.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];

    if ($password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, password = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $hashed_password, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $user_id]);
    }
    
    $_SESSION['user_name'] = $name;
    redirect('user/profile.php', 'success', 'Profile updated successfully');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="list-group mb-3">
            <a href="my_bookings.php" class="list-group-item list-group-item-action">My Bookings</a>
            <a href="profile.php" class="list-group-item list-group-item-action active">Profile</a>
        </div>
    </div>
    <div class="col-md-9">
        <h2>Edit Profile</h2>
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $user['name']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" value="<?php echo $user['email']; ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo $user['phone']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
