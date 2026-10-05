<?php
require_once '../config/config.php';
require_login();

$user_id = get_current_user_id();

// Mark notification as read if requested
if (isset($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);
    redirect(SITE_URL . '/user/notifications.php');
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);
    redirect(SITE_URL . '/user/notifications.php');
}

// Fetch notifications
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-container">
                <a href="../index.php" class="navbar-brand">⌚ <?php echo SITE_NAME; ?></a>
                <ul class="navbar-menu">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="booking_wizard.php">Book Service</a></li>
                    <li><a href="my_bookings.php">My Bookings</a></li>
                    <li><a href="notifications.php" class="active">Notifications</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem; margin-bottom: 3rem;">
        <div class="d-flex justify-between align-center mb-3">
            <h1>Notifications</h1>
            <?php if ($notifications->num_rows > 0): ?>
                <a href="?mark_all_read=1" class="btn btn-sm btn-secondary">Mark All as Read</a>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if ($notifications->num_rows > 0): ?>
                    <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <div class="card mb-2" style="background-color: <?php echo $notification['is_read'] ? 'var(--bg-darker)' : 'var(--bg-card)'; ?>; border-left: 3px solid <?php echo $notification['is_read'] ? 'var(--border-color)' : 'var(--primary-color)'; ?>;">
                        <div class="card-body" style="padding: 1rem;">
                            <div class="d-flex justify-between align-center mb-2">
                                <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                <small style="color: var(--text-secondary);">
                                    <?php echo date('M d, Y g:i A', strtotime($notification['created_at'])); ?>
                                </small>
                            </div>
                            <p style="margin-bottom: 0.5rem;"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                            <div class="d-flex gap-2">
                                <?php if ($notification['booking_id']): ?>
                                    <a href="view_booking.php?id=<?php echo $notification['booking_id']; ?>" class="btn btn-sm btn-primary">View Booking</a>
                                <?php endif; ?>
                                <?php if (!$notification['is_read']): ?>
                                    <a href="?mark_read=<?php echo $notification['notification_id']; ?>" class="btn btn-sm btn-secondary">Mark as Read</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center" style="padding: 3rem;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">🔔</div>
                        <h3>No Notifications</h3>
                        <p style="color: var(--text-secondary);">You're all caught up!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php closeDBConnection($conn); ?>
