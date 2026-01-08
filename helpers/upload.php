<?php
function uploadImage($file, $targetDir) {
    $targetDir = __DIR__ . '/../' . $targetDir; // Adjust path relative to helpers
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($file["name"]);
    $targetFilePath = $targetDir . time() . "_" . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
    if (in_array(strtolower($fileType), $allowTypes)) {
        if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
            return time() . "_" . $fileName;
        }
    }
    return false;
}
?>
