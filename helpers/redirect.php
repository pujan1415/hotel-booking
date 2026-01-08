<?php
function redirect($url, $type = 'success', $message = '') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: " . BASE_URL . $url);
    exit();
}

function flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] == 'success' ? 'success' : 'danger';
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . $_SESSION['flash_message'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}
?>
