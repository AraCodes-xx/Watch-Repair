<?php
require_once '../config/config.php';
require_admin();

$feedback_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT f.*, u.full_name as customer_name, t.full_name as technician_name, 
                        b.booking_id, s.service_name 
                        FROM feedback f 
                        JOIN users u ON f.user_id = u.user_id 
                        JOIN technicians t ON f.technician_id = t.technician_id 
                        JOIN bookings b ON f.booking_id = b.booking_id 
                        JOIN services s ON b.service_id = s.service_id 
                        WHERE f.feedback_id = ?");
$stmt->bind_param("i", $feedback_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect(SITE_URL . '/admin/view_feedback.php');
}

$feedback = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $is_approved = isset($_POST['is_approved']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE feedback SET is_approved = ? WHERE feedback_id = ?");
    $stmt->bind_param("ii", $is_approved, $feedback_id);
    
    if ($stmt->execute()) {
        $success = 'Feedback updated successfully!';
        
        $stmt = $conn->prepare("SELECT f.*, u.full_name as customer_name, t.full_name as technician_name, 
                                b.booking_id, s.service_name 
                                FROM feedback f 
                                JOIN users u ON f.user_id = u.user_id 
                                JOIN technicians t ON f.technician_id = t.technician_id 
                                JOIN bookings b ON f.booking_id = b.booking_id 
                                JOIN services s ON b.service_id = s.service_id 
                                WHERE f.feedback_id = ?");
        $stmt->bind_param("i", $feedback_id);
        $stmt->execute();
        $feedback = $stmt->get_result()->fetch_assoc();
    } else {
        $error = 'Failed to update feedback';
    }
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Feedback - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container-fluid">
            <div class="navbar-container">
                <a href="dashboard.php" class="navbar-brand">⚙️ Admin Panel</a>
                <ul class="navbar-menu">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="view_booking.php">Bookings</a></li>
                    <li><a href="view_payment.php">Payments</a></li>
                    <li><a href="manage.php?entity=services">Services</a></li>
                    <li><a href="manage.php?entity=technicians">Technicians</a></li>
                    <li><a href="manage.php?entity=users">Users</a></li>
                    <li><a href="manage.php?entity=timeslots">Time Slots</a></li>
                    <li><a href="view_feedback.php" class="active">Feedback</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="max-width: 800px; margin-top: 3rem; margin-bottom: 3rem;">
        <div class="d-flex justify-between align-center mb-3">
            <h1>Review Feedback</h1>
            <a href="view_feedback.php" class="btn btn-secondary">← Back</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-header">Feedback Details</div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Booking ID:</strong> #<?php echo $feedback['booking_id']; ?><br>
                    <strong>Service:</strong> <?php echo htmlspecialchars($feedback['service_name']); ?><br>
                    <strong>Customer:</strong> <?php echo htmlspecialchars($feedback['customer_name']); ?><br>
                    <strong>Technician:</strong> <?php echo htmlspecialchars($feedback['technician_name']); ?><br>
                    <strong>Submitted:</strong> <?php echo date('F d, Y g:i A', strtotime($feedback['created_at'])); ?>
                </div>

                <div class="mb-3">
                    <strong>Rating:</strong><br>
                    <div style="font-size: 2rem;">
                        <?php 
                        for ($i = 1; $i <= 5; $i++) {
                            echo '<span style="color: ' . ($i <= $feedback['rating'] ? '#f59e0b' : '#334155') . ';">★</span>';
                        }
                        ?>
                        <span style="font-size: 1rem; margin-left: 1rem;"><?php echo $feedback['rating']; ?> / 5</span>
                    </div>
                </div>

                <div>
                    <strong>Comment:</strong><br>
                    <p style="background-color: var(--bg-darker); padding: 1rem; border-radius: 0.5rem; margin-top: 0.5rem;">
                        <?php echo nl2br(htmlspecialchars($feedback['comment'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Approval Status</div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_approved" <?php echo $feedback['is_approved'] ? 'checked' : ''; ?>>
                            <span>Approve this feedback (will be displayed on homepage)</span>
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Status</button>
                        <a href="view_feedback.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php closeDBConnection($conn); ?>
