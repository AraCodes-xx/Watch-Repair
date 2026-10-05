<?php
require_once '../config/config.php';
require_admin();

$payment_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

$conn = getDBConnection();

// Fetch payment details
$stmt = $conn->prepare("SELECT p.*, b.booking_id, b.booking_date, b.user_id, u.full_name as customer_name, 
                        u.email, s.service_name 
                        FROM payments p 
                        JOIN bookings b ON p.booking_id = b.booking_id 
                        JOIN users u ON b.user_id = u.user_id 
                        JOIN services s ON b.service_id = s.service_id 
                        WHERE p.payment_id = ?");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect(SITE_URL . '/admin/view_payment.php');
}

$payment = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $verification_status = $_POST['verification_status'];
    $admin_remarks = sanitize_input($_POST['admin_remarks']);
    $admin_id = get_current_admin_id();
    
    $stmt = $conn->prepare("UPDATE payments SET verification_status = ?, admin_remarks = ?, verified_by = ?, verified_at = NOW() WHERE payment_id = ?");
    $stmt->bind_param("ssii", $verification_status, $admin_remarks, $admin_id, $payment_id);
    
    if ($stmt->execute()) {
        // Send notification to user
        $notification_title = '';
        $notification_message = '';
        
        if ($verification_status == 'Approved') {
            $notification_title = 'Payment Verified';
            $notification_message = 'Your payment for booking #' . $payment['booking_id'] . ' has been verified and approved.';
        } elseif ($verification_status == 'Rejected') {
            $notification_title = 'Payment Rejected';
            $notification_message = 'Your payment for booking #' . $payment['booking_id'] . ' has been rejected. Reason: ' . $admin_remarks;
        }
        
        if ($notification_title) {
            send_notification($payment['user_id'], $notification_title, $notification_message, $payment['booking_id']);
        }
        
        $success = 'Payment verification updated successfully!';
        
        // Refresh payment data
        $stmt = $conn->prepare("SELECT p.*, b.booking_id, b.booking_date, b.user_id, u.full_name as customer_name, 
                                u.email, s.service_name 
                                FROM payments p 
                                JOIN bookings b ON p.booking_id = b.booking_id 
                                JOIN users u ON b.user_id = u.user_id 
                                JOIN services s ON b.service_id = s.service_id 
                                WHERE p.payment_id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
    } else {
        $error = 'Failed to update payment verification';
    }
    
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Payment - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="view_payment.php" class="active">Payments</a></li>
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
            <h1>Verify Payment #<?php echo $payment['payment_id']; ?></h1>
            <a href="view_payment.php" class="btn btn-secondary">← Back to Payments</a>
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
                    <div class="card-header">Payment Information</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Booking ID:</strong> #<?php echo $payment['booking_id']; ?><br>
                            <strong>Customer:</strong> <?php echo htmlspecialchars($payment['customer_name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($payment['email']); ?><br>
                            <strong>Service:</strong> <?php echo htmlspecialchars($payment['service_name']); ?><br>
                            <strong>Booking Date:</strong> <?php echo date('F d, Y', strtotime($payment['booking_date'])); ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Payment Amount:</strong> <?php echo format_currency($payment['amount']); ?><br>
                            <strong>Payment Method:</strong> <?php echo htmlspecialchars($payment['payment_method']); ?><br>
                            <strong>Submitted:</strong> <?php echo date('F d, Y g:i A', strtotime($payment['created_at'])); ?>
                        </div>
                        
                        <div>
                            <strong>Current Status:</strong> 
                            <span class="badge <?php echo get_status_badge_class($payment['verification_status']); ?>">
                                <?php echo $payment['verification_status']; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Payment Proof</div>
                    <div class="card-body">
                        <?php if ($payment['payment_proof']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($payment['payment_proof']); ?>" 
                                 alt="Payment Proof" 
                                 style="max-width: 100%; border-radius: 0.5rem; border: 1px solid var(--border-color);">
                        <?php else: ?>
                            <p>No payment proof uploaded</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card">
                    <div class="card-header">Verification</div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">Verification Status</label>
                                <select name="verification_status" class="form-control" required>
                                    <option value="Pending" <?php echo $payment['verification_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Approved" <?php echo $payment['verification_status'] == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Rejected" <?php echo $payment['verification_status'] == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Admin Remarks</label>
                                <textarea name="admin_remarks" class="form-control" rows="5" placeholder="Add verification notes or rejection reason..."><?php echo htmlspecialchars($payment['admin_remarks'] ?? ''); ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Update Verification</button>
                                <a href="view_payment.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>

                        <?php if ($payment['verified_at']): ?>
                        <div class="mt-4" style="padding-top: 1rem; border-top: 1px solid var(--border-color);">
                            <small style="color: var(--text-secondary);">
                                Last verified: <?php echo date('F d, Y g:i A', strtotime($payment['verified_at'])); ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php closeDBConnection($conn); ?>
