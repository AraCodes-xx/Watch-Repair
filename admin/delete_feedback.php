<?php
require_once '../config/config.php';
require_admin();

$feedback_id = intval($_GET['id'] ?? 0);

if ($feedback_id > 0) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id = ?");
    $stmt->bind_param("i", $feedback_id);
    $stmt->execute();
    $stmt->close();
    
    closeDBConnection($conn);
}

redirect(SITE_URL . '/admin/view_feedback.php');
?>
