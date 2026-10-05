<?php
require_once '../config/config.php';
require_admin();

$booking_id = intval($_GET['id'] ?? 0);

if ($booking_id > 0) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->close();
    
    closeDBConnection($conn);
}

redirect(SITE_URL . '/admin/view_booking.php');
?>
