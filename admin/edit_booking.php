<?php
require_once '../config/config.php';
require_admin();

$booking_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

$conn = getDBConnection();

// Fetch booking details
$stmt = $conn->prepare("SELECT b.*, u.full_name as customer_name, u.email, s.service_name 
                        FROM bookings b 
                        JOIN users u ON b.user_id = u.user_id 
                        JOIN services s ON b.service_id = s.service_id 
                        WHERE b.booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect(SITE_URL . '/admin/view_booking.php');
}

$booking = $result->fetch_assoc();

// Fetch technicians
$technicians = $conn->query("SELECT * FROM technicians WHERE is_available = 1 ORDER BY full_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    $technician_id = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null;
    $admin_remarks = sanitize_input($_POST['admin_remarks']);
    
    $stmt = $conn->prepare("UPDATE bookings SET status = ?, technician_id = ?, admin_remarks = ? WHERE booking_id = ?");
    $stmt->bind_param("sisi", $status, $technician_id, $admin_remarks, $booking_id);
    
    if ($stmt->execute()) {
        // Send notification to user
        $notification_title = '';
        $notification_message = '';
        
        switch($status) {
            case 'Approved':
                $notification_title = 'Booking Approved';
                $notification_message = 'Your booking #' . $booking_id . ' has been approved!';
                if ($technician_id) {
                    $tech_stmt = $conn->prepare("SELECT full_name FROM technicians WHERE technician_id = ?");
                    $tech_stmt->bind_param("i", $technician_id);
                    $tech_stmt->execute();
                    $tech_name = $tech_stmt->get_result()->fetch_assoc()['full_name'];
                    $notification_message .= ' Technician ' . $tech_name . ' has been assigned to your service.';
                    $tech_stmt->close();
                }
                break;
            case 'Rejected':
                $notification_title = 'Booking Rejected';
                $notification_message = 'Your booking #' . $booking_id . ' has been rejected. ' . $admin_remarks;
                break;
            case 'Completed':
                $notification_title = 'Service Completed';
                $notification_message = 'Your booking #' . $booking_id . ' has been completed. Please rate your experience!';
                break;
        }
        
        if ($notification_title) {
            send_notification($booking['user_id'], $notification_title, $notification_message, $booking_id);
        }
        
        $success = 'Booking updated successfully!';
        
        // Refresh booking data
        $stmt = $conn->prepare("SELECT b.*, u.full_name as customer_name, u.email, s.service_name 
                                FROM bookings b 
                                JOIN users u ON b.user_id = u.user_id 
                                JOIN services s ON b.service_id = s.service_id 
                                WHERE b.booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
    } else {
        $error = 'Failed to update booking';
    }
    
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container-fluid">
            <div class="navbar-container">
                <a href="dashboard.php" class="navbar-brand">⚙️ Admin Panel</a>
                <ul class="navbar-menu">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="view_booking.php" class="active">Bookings</a></li>
                    <li><a href="view_payment.php">Payments</a></li>
                    <li><a href="manage.php?entity=services">Services</a></li>
                    <li><a href="manage.php?entity=technicians">Technicians</a></li>
                    <li><a href="manage.php?entity=users">Users</a></li>
                    <li><a href="manage.php?entity=timeslots">Time Slots</a></li>
                    <li><a href="view_feedback.php">Feedback</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem; margin-bottom: 3rem;">
        <div class="d-flex justify-between align-center mb-3">
            <h1>Manage Booking #<?php echo $booking['booking_id']; ?></h1>
            <a href="view_booking.php" class="btn btn-secondary">← Back to Bookings</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-6">
                <div class="card mb-3">
                    <div class="card-header">Booking Details</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Customer:</strong> <?php echo htmlspecialchars($booking['customer_name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?><br>
                            <strong>Service:</strong> <?php echo htmlspecialchars($booking['service_name']); ?><br>
                            <strong>Date:</strong> <?php echo date('F d, Y', strtotime($booking['booking_date'])); ?><br>
                            <?php if (!empty($booking['watch_type'])): ?>
                            <strong>Watch Type:</strong> <?php echo htmlspecialchars($booking['watch_type']); ?><br>
                            <?php endif; ?>
                            <strong>Total Cost:</strong> <?php echo format_currency($booking['total_cost']); ?><br>
                            <strong>Down Payment:</strong> <?php echo format_currency($booking['down_payment']); ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Problem Description:</strong><br>
                            <?php echo nl2br(htmlspecialchars($booking['problem_description'])); ?>
                        </div>
                        
                        <div>
                            <strong>Service Address:</strong><br>
                            <?php echo nl2br(htmlspecialchars($booking['user_address'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">Update Booking</div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="Pending" <?php echo $booking['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Approved" <?php echo $booking['status'] == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Rejected" <?php echo $booking['status'] == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="Completed" <?php echo $booking['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo $booking['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Assign Technician</label>
                                <select name="technician_id" class="form-control">
                                    <option value="">-- Select Technician --</option>
                                    <?php while ($tech = $technicians->fetch_assoc()): ?>
                                        <option value="<?php echo $tech['technician_id']; ?>" 
                                                <?php echo $booking['technician_id'] == $tech['technician_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tech['full_name']); ?> - <?php echo htmlspecialchars($tech['specialization']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Admin Remarks</label>
                                <textarea name="admin_remarks" class="form-control" rows="4" placeholder="Add any remarks or notes..."><?php echo htmlspecialchars($booking['admin_remarks'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Booking</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php closeDBConnection($conn); ?>
