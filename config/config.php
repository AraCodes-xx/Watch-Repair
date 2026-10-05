<?php
// General Configuration
session_start();

// Site Settings
define('SITE_NAME', 'MC Repair');
define('SITE_URL', 'http://localhost:8080/repair_shop');
define('ADMIN_EMAIL', 'admin@mcrepair.com');

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Payment Settings
define('DOWN_PAYMENT_PERCENTAGE', 50);

// Timezone
date_default_timezone_set('Asia/Manila');

// Include database connection
require_once __DIR__ . '/database.php';

// Helper Functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect(SITE_URL . '/user/login.php');
    }
}

function require_admin() {
    if (!is_admin_logged_in()) {
        redirect(SITE_URL . '/admin/login.php');
    }
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_current_admin_id() {
    return $_SESSION['admin_id'] ?? null;
}

function format_currency($amount) {
    return '₱' . number_format($amount, 2);
}

function calculate_down_payment($total) {
    return ($total * DOWN_PAYMENT_PERCENTAGE) / 100;
}

function upload_file($file, $prefix = 'payment_') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_name = $file['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if ($file_size > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds 5MB'];
    }
    
    if (!in_array($file_ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, GIF allowed'];
    }
    
    $new_filename = $prefix . uniqid() . '.' . $file_ext;
    $upload_path = UPLOAD_DIR . $new_filename;
    
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }
    
    if (move_uploaded_file($file_tmp, $upload_path)) {
        return ['success' => true, 'filename' => $new_filename];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

function send_notification($user_id, $title, $message, $booking_id = null) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, booking_id, title, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $user_id, $booking_id, $title, $message);
    $result = $stmt->execute();
    
    $stmt->close();
    closeDBConnection($conn);
    
    return $result;
}

function get_status_badge_class($status) {
    switch($status) {
        case 'Pending':
            return 'badge-warning';
        case 'Approved':
            return 'badge-success';
        case 'Rejected':
        case 'Cancelled':
            return 'badge-danger';
        case 'Completed':
            return 'badge-info';
        default:
            return 'badge-secondary';
    }
}
?>
