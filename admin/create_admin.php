<?php
// 1. Database connection settings
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "hotel_booking"; // Ensure this matches your database name

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Define the Admin details
$admin_name = "Administrator";
$admin_email = "admin@gmail.com";
$admin_password = "admin123"; // Change this to your preferred password

// 3. Hash the password for security
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// 4. Check if admin already exists
$check = $conn->prepare("SELECT id FROM admins WHERE email = ?");
$check->bind_param("s", $admin_email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows == 0) {
    // 5. Insert into the 'admins' table
    $stmt = $conn->prepare("INSERT INTO admins (NAME, email, PASSWORD) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $admin_name, $admin_email, $hashed_password);

    if ($stmt->execute()) {
        echo "<h2>Admin Created Successfully!</h2>";
        echo "<b>Email:</b> $admin_email <br>";
        echo "<b>Password:</b> $admin_password <br><br>";
        echo "<span style='color:red;'>Important: Delete this file after use for security reasons.</span>";
    } else {
        echo "Error creating admin: " . $conn->error;
    }
} else {
    echo "Admin with this email already exists.";
}

$conn->close();
?>