<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/config.php';
require_admin();

$booking_id = intval($_GET['id'] ?? 0);

if ($booking_id == 0) {
    die("Error: No booking ID provided. <a href='view_booking.php'>Go back to bookings</a>");
}

$conn = getDBConnection();

// Handle payment verification actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'verify_payment') {
        // get user_id for notification
        $uidStmt = $conn->prepare("SELECT user_id FROM bookings WHERE booking_id = ?");
        $uidStmt->bind_param("i", $booking_id);
        $uidStmt->execute();
        $uidRes = $uidStmt->get_result()->fetch_assoc();
        $user_id_notif = $uidRes['user_id'] ?? null;
        $uidStmt->close();
        $stmt = $conn->prepare("UPDATE payments SET verification_status = 'Verified' WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Approved' WHERE booking_id = ?");
        // send notification
        if ($user_id_notif) {
            send_notification($user_id_notif, 'Payment Verified', 'Your payment has been verified and booking approved.', $booking_id);
        }
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        // Redirect to avoid reload loop
        header("Location: booking_detail.php?id=$booking_id&success=verified");
        exit;
    }
    
    if ($action == 'reject_payment') {
        // get user_id for notification
        $uidStmt = $conn->prepare("SELECT user_id FROM bookings WHERE booking_id = ?");
        $uidStmt->bind_param("i", $booking_id);
        $uidStmt->execute();
        $uidRes = $uidStmt->get_result()->fetch_assoc();
        $user_id_notif = $uidRes['user_id'] ?? null;
        $uidStmt->close();
        $reason = sanitize_input($_POST['rejection_reason']);
        
        $stmt = $conn->prepare("UPDATE payments SET verification_status = 'Rejected', admin_remarks = ? WHERE booking_id = ?");
        $stmt->bind_param("si", $reason, $booking_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Rejected', admin_remarks = ? WHERE booking_id = ?");
        // send notification
        if ($user_id_notif) {
            send_notification($user_id_notif, 'Payment Rejected', 'Your payment was rejected. Reason: ' . $reason, $booking_id);
        }
        $stmt->bind_param("si", $reason, $booking_id);
        $stmt->execute();
        
        // Redirect to avoid reload loop
        header("Location: booking_detail.php?id=$booking_id&success=rejected");
        exit;
    }
    
    if ($action == 'assign_technician') {
        $technician_id = intval($_POST['technician_id']);
        
        $stmt = $conn->prepare("UPDATE bookings SET technician_id = ? WHERE booking_id = ?");
        $stmt->bind_param("ii", $technician_id, $booking_id);
        $stmt->execute();
        
        // Redirect to avoid reload loop
        header("Location: booking_detail.php?id=$booking_id&success=assigned");
        exit;
    }
    
    if ($action == 'update_status') {
        $uidStmt = $conn->prepare("SELECT user_id FROM bookings WHERE booking_id = ?");
        $uidStmt->bind_param("i", $booking_id);
        $uidStmt->execute();
        $uidRes = $uidStmt->get_result()->fetch_assoc();
        $user_id_notif = $uidRes['user_id'] ?? null;
        $uidStmt->close();
        $status = sanitize_input($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        $stmt->execute();
        
        // send notification for status change
        if ($user_id_notif) {
            if ($status == 'Completed') {
                send_notification($user_id_notif, 'Service Completed', 'Your watch service has been completed. Thank you!', $booking_id);
            } elseif ($status == 'Cancelled') {
                send_notification($user_id_notif, 'Booking Cancelled', 'Your booking has been cancelled.', $booking_id);
            }
        }
        // Redirect to avoid reload loop
        header("Location: booking_detail.php?id=$booking_id&success=updated");
        exit;
    }
    
    if ($action == 'mark_paid') {
        $payment_status = sanitize_input($_POST['payment_status']);
        
        $stmt = $conn->prepare("UPDATE bookings SET payment_status = ? WHERE booking_id = ?");
        $stmt->bind_param("si", $payment_status, $booking_id);
        $stmt->execute();
        
        // send notification when fully paid
        if ($payment_status == 'Fully Paid') {
            $uidStmt = $conn->prepare("SELECT user_id FROM bookings WHERE booking_id = ?");
            $uidStmt->bind_param("i", $booking_id);
            $uidStmt->execute();
            $uidRes = $uidStmt->get_result()->fetch_assoc();
            $uidStmt->close();
            if ($uidRes) {
                send_notification($uidRes['user_id'], 'Payment Completed', 'Your booking is now fully paid. Thank you!', $booking_id);
            }
        }
        // Redirect to avoid reload loop
        header("Location: booking_detail.php?id=$booking_id&success=payment_updated");
        exit;
    }
}

// Fetch booking details
$stmt = $conn->prepare("SELECT b.*, u.full_name as customer_name, u.email, u.contact_number, u.address as customer_address,
                        s.service_name, s.base_price, t.full_name as technician_name, t.technician_id,
                        ts.start_time, ts.end_time, 
                        p.payment_proof, p.amount as paid_amount, p.payment_method, p.verification_status, p.admin_remarks as payment_remarks,
                        wt.type_name as watch_type_name
                        FROM bookings b 
                        JOIN users u ON b.user_id = u.user_id 
                        JOIN services s ON b.service_id = s.service_id 
                        LEFT JOIN technicians t ON b.technician_id = t.technician_id 
                        JOIN timeslots ts ON b.timeslot_id = ts.timeslot_id
                        LEFT JOIN payments p ON b.booking_id = p.booking_id
                        LEFT JOIN watch_types wt ON b.watch_type_id = wt.watch_type_id
                        WHERE b.booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    redirect(SITE_URL . '/admin/view_booking.php');
}

// Fetch all services for this booking
$stmt = $conn->prepare("SELECT s.service_name, bs.service_price 
                        FROM booking_services bs 
                        JOIN services s ON bs.service_id = s.service_id 
                        WHERE bs.booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking_services = $stmt->get_result();

// Fetch available technicians
$technicians = $conn->query("SELECT * FROM technicians ORDER BY full_name");

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .workflow-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        .workflow-step {
            flex: 1;
            text-align: center;
            padding: 1rem;
            background: var(--bg-darker);
            border-radius: 0.5rem;
            margin: 0 0.5rem;
            position: relative;
        }
        .workflow-step.active {
            background: var(--primary-color);
            color: white;
        }
        .workflow-step.completed {
            background: #28a745;
            color: white;
        }
        .workflow-step.rejected {
            background: #dc3545;
            color: white;
        }
        .payment-proof-container {
            max-width: 500px;
            margin: 0 auto;
            border: 3px solid var(--primary-color);
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .payment-proof-container img {
            width: 100%;
            display: block;
        }
        .verification-panel {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        .verification-panel h3 {
            color: #856404;
            margin-top: 0;
        }
        .amount-check {
            display: flex;
            justify-content: space-around;
            margin: 1rem 0;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
        }
        .amount-item {
            text-align: center;
        }
        .amount-item .label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        .amount-item .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .action-buttons button {
            flex: 1;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: bold;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            max-width: 500px;
            width: 90%;
        }
    </style>
    <script src="../assets/js/logout-confirm.js"></script>
</head>
<body class="admin-layout">
    <!-- Vertical Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <a href="dashboard.php" class="admin-sidebar-brand">
                <span>⚙️</span>
                <span><?php echo SITE_NAME; ?></span>
            </a>
        </div>
        <ul class="admin-sidebar-menu">
            <li><a href="dashboard.php"><span class="admin-sidebar-icon">📊</span> Dashboard</a></li>
            <li><a href="view_booking.php" class="active"><span class="admin-sidebar-icon">📋</span> Bookings</a></li>
            <li><a href="manage.php?entity=services"><span class="admin-sidebar-icon">🔧</span> Services</a></li>
            <li><a href="manage.php?entity=technicians"><span class="admin-sidebar-icon">👨‍🔧</span> Technicians</a></li>
            <li><a href="manage.php?entity=users"><span class="admin-sidebar-icon">👥</span> Users</a></li>
            <li><a href="manage.php?entity=timeslots"><span class="admin-sidebar-icon">⏰</span> Time Slots</a></li>
            <li><a href="view_feedback.php"><span class="admin-sidebar-icon">⭐</span> Feedback</a></li>
            <li><a href="logout.php"><span class="admin-sidebar-icon">🚪</span> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="admin-topbar">
            <div>
                <button class="admin-sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1 style="margin: 0; display: inline-block;">📋 Booking Details</h1>
            </div>
            <a href="view_booking.php" class="btn btn-secondary">← Back to All Bookings</a>
        </div>

        <?php 
        $success_msg = $_GET['success'] ?? '';
        if ($success_msg): 
            $messages = [
                'verified' => '✅ Payment verified and booking approved!',
                'rejected' => '❌ Payment rejected and customer notified!',
                'assigned' => '✅ Technician assigned successfully!',
                'updated' => '✅ Booking status updated!',
                'payment_updated' => '✅ Payment status updated successfully!'
            ];
            $message = $messages[$success_msg] ?? 'Action completed successfully!';
        ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Workflow Steps -->
        <div class="workflow-steps">
            <div class="workflow-step <?php echo $booking['verification_status'] == 'Pending' ? 'active' : ($booking['verification_status'] == 'Verified' ? 'completed' : 'rejected'); ?>">
                <strong>1. Payment Verification</strong>
                <div><?php echo $booking['verification_status'] ?? 'Pending'; ?></div>
            </div>
            <div class="workflow-step <?php echo $booking['status'] == 'Approved' ? 'active' : ($booking['status'] == 'Completed' ? 'completed' : ''); ?>">
                <strong>2. Assign Technician</strong>
                <div><?php echo $booking['technician_name'] ?? 'Not Assigned'; ?></div>
            </div>
            <div class="workflow-step <?php echo $booking['status'] == 'Completed' ? 'completed' : ''; ?>">
                <strong>3. Complete Service</strong>
                <div><?php echo $booking['status']; ?></div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Payment Verification -->
            <div class="col-6">
                <?php if ($booking['verification_status'] == 'Pending' || $booking['verification_status'] == null): ?>
                <div class="verification-panel">
                    <h3>⚠️ Payment Verification Required</h3>
                    <p><strong>Please verify the payment proof below:</strong></p>
                    
                    <!-- Amount Comparison -->
                    <div class="amount-check">
                        <div class="amount-item">
                            <div class="label">Required (20%)</div>
                            <div class="value"><?php echo format_currency($booking['down_payment']); ?></div>
                        </div>
                        <div class="amount-item">
                            <div class="label">Total Cost</div>
                            <div class="value"><?php echo format_currency($booking['total_cost']); ?></div>
                        </div>
                        <div class="amount-item">
                            <div class="label">Payment Method</div>
                            <div class="value" style="font-size:1rem;"><?php echo htmlspecialchars($booking['payment_method'] ?? 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- Payment Proof Image -->
                    <?php if ($booking['payment_proof']): ?>
                    <div class="payment-proof-container">
                        <img src="../uploads/<?php echo htmlspecialchars($booking['payment_proof']); ?>" 
                             alt="Payment Proof" 
                             onclick="openImageModal(this.src)">
                    </div>
                    <p style="text-align:center; margin-top:0.5rem; font-size:0.875rem; color:var(--text-secondary);">
                        Click image to enlarge
                    </p>
                    <?php else: ?>
                    <div class="alert alert-warning">No payment proof uploaded</div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <form method="POST" style="flex:1;">
                            <input type="hidden" name="action" value="verify_payment">
                            <button type="submit" class="btn btn-success" style="width:100%;">
                                ✅ Verify & Approve
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger" onclick="showRejectModal()" style="flex:1;">
                            ❌ Reject Payment
                        </button>
                    </div>
                </div>
                <?php elseif ($booking['verification_status'] == 'Verified'): ?>
                <div class="alert alert-success">
                    <strong>✅ Payment Verified</strong><br>
                    Payment has been verified and booking is approved.
                </div>
                <?php elseif ($booking['verification_status'] == 'Rejected'): ?>
                <div class="alert alert-danger">
                    <strong>❌ Payment Rejected</strong><br>
                    Reason: <?php echo htmlspecialchars($booking['payment_remarks'] ?? 'No reason provided'); ?>
                </div>
                <?php endif; ?>

                <!-- Booking Details Card -->
                <div class="card mt-3">
                    <div class="card-header">📝 Booking Details</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Customer:</strong><br>
                            <?php echo htmlspecialchars($booking['customer_name']); ?><br>
                            <small><?php echo htmlspecialchars($booking['email']); ?></small><br>
                            <small><?php echo htmlspecialchars($booking['contact_number']); ?></small>
                        </div>

                        <div class="mb-3">
                            <strong>Services:</strong><br>
                            <?php if ($booking_services->num_rows > 0): ?>
                                <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                                    <?php while ($service = $booking_services->fetch_assoc()): ?>
                                        <li><?php echo htmlspecialchars($service['service_name']); ?> - <?php echo format_currency($service['service_price']); ?></li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <?php echo htmlspecialchars($booking['service_name']); ?>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <strong>Date & Time:</strong><br>
                            <?php echo date('F d, Y', strtotime($booking['booking_date'])); ?><br>
                            <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                        </div>

                        <?php if ($booking['watch_type_name']): ?>
                        <div class="mb-3">
                            <strong>Watch Type:</strong><br>
                            <?php echo htmlspecialchars($booking['watch_type_name']); ?>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <strong>Problem Description:</strong><br>
                            <?php echo nl2br(htmlspecialchars($booking['problem_description'])); ?>
                        </div>

                        <div class="mb-3">
                            <strong>Service Address:</strong><br>
                            <?php echo nl2br(htmlspecialchars($booking['user_address'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Technician & Status Management -->
            <div class="col-6">
                <!-- Assign Technician -->
                <?php if ($booking['status'] == 'Approved' && !$booking['technician_id']): ?>
                <div class="card mb-3" style="border: 2px solid var(--primary-color);">
                    <div class="card-header" style="background: var(--primary-color); color: white;">
                        <strong>👨‍🔧 Assign Technician</strong>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="assign_technician">
                            <div class="form-group">
                                <label>Select Technician:</label>
                                <select name="technician_id" class="form-control" required>
                                    <option value="">Choose a technician...</option>
                                    <?php while ($tech = $technicians->fetch_assoc()): ?>
                                        <option value="<?php echo $tech['technician_id']; ?>">
                                            <?php echo htmlspecialchars($tech['full_name']); ?> - <?php echo htmlspecialchars($tech['specialization']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;">
                                Assign Technician
                            </button>
                        </form>
                    </div>
                </div>
                <?php elseif ($booking['technician_id']): ?>
                <div class="card mb-3">
                    <div class="card-header">👨‍🔧 Assigned Technician</div>
                    <div class="card-body">
                        <strong><?php echo htmlspecialchars($booking['technician_name']); ?></strong>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Remaining Balance Payment -->
                <?php if ($booking['status'] == 'Approved' || $booking['status'] == 'Completed'): 
                    $remaining_balance = $booking['total_cost'] - $booking['down_payment'];
                    $payment_status = $booking['payment_status'] ?? 'Down Payment Only';
                ?>
                <div class="card mb-3" style="border: 2px solid #ffc107;">
                    <div class="card-header" style="background: #ffc107; color: #000;">
                        <strong>💰 Remaining Balance</strong>
                    </div>
                    <div class="card-body">
                        <div class="amount-check" style="background: #fff3cd; margin-bottom: 1rem;">
                            <div class="amount-item">
                                <div class="label">Total Cost</div>
                                <div class="value"><?php echo format_currency($booking['total_cost']); ?></div>
                            </div>
                            <div class="amount-item">
                                <div class="label">Down Payment (50%)</div>
                                <div class="value" style="color: #28a745;"><?php echo format_currency($booking['down_payment']); ?></div>
                            </div>
                            <div class="amount-item">
                                <div class="label">Remaining (50%)</div>
                                <div class="value" style="color: #dc3545;"><?php echo format_currency($remaining_balance); ?></div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" style="margin-bottom: 1rem;">
                            <strong>📝 Note:</strong> Customer should pay the remaining balance to the technician upon service completion.
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="mark_paid">
                            <div class="form-group">
                                <label><strong>Payment Status:</strong></label>
                                <select name="payment_status" class="form-control" required>
                                    <option value="Down Payment Only" <?php echo $payment_status == 'Down Payment Only' ? 'selected' : ''; ?>>Down Payment Only (50%)</option>
                                    <option value="Fully Paid" <?php echo $payment_status == 'Fully Paid' ? 'selected' : ''; ?>>Fully Paid (100%)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-warning" style="width:100%; margin-top:1rem; color: #000;">
                                Update Payment Status
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Update Status -->
                <?php if ($booking['status'] == 'Approved' && $booking['technician_id']): ?>
                <div class="card mb-3" style="border: 2px solid #28a745;">
                    <div class="card-header" style="background: #28a745; color: white;">
                        <strong>📊 Update Booking Status</strong>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_status">
                            <div class="form-group">
                                <label>Change Status:</label>
                                <select name="status" class="form-control" required>
                                    <option value="Approved" <?php echo $booking['status'] == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success" style="width:100%; margin-top:1rem;">
                                Update Booking Status
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Current Status -->
                <div class="card">
                    <div class="card-header">📌 Current Status</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Booking Status:</strong><br>
                            <span class="badge <?php echo get_status_badge_class($booking['status']); ?>" style="font-size:1rem; padding:0.5rem 1rem;">
                                <?php echo $booking['status']; ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <strong>Payment Verification:</strong><br>
                            <span class="badge <?php echo get_status_badge_class($booking['verification_status'] ?? 'Pending'); ?>" style="font-size:1rem; padding:0.5rem 1rem;">
                                <?php echo $booking['verification_status'] ?? 'Pending'; ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <strong>Payment Status:</strong><br>
                            <?php 
                            $payment_status = $booking['payment_status'] ?? 'Down Payment Only';
                            $badge_class = $payment_status == 'Fully Paid' ? 'badge-success' : 'badge-warning';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>" style="font-size:1rem; padding:0.5rem 1rem;">
                                <?php echo $payment_status; ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <strong>Created:</strong><br>
                            <?php echo date('F d, Y g:i A', strtotime($booking['created_at'])); ?>
                        </div>

                        <?php if ($booking['admin_remarks']): ?>
                        <div class="alert alert-info">
                            <strong>Admin Remarks:</strong><br>
                            <?php echo nl2br(htmlspecialchars($booking['admin_remarks'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h2>❌ Reject Payment</h2>
            <form method="POST">
                <input type="hidden" name="action" value="reject_payment">
                <div class="form-group">
                    <label>Reason for Rejection:</label>
                    <textarea name="rejection_reason" class="form-control" rows="4" placeholder="Please explain why the payment is being rejected..." required></textarea>
                </div>
                <div class="d-flex gap-2" style="margin-top:1rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Payment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal" onclick="closeImageModal()">
        <div style="max-width:90%; max-height:90%;">
            <img id="modalImage" src="" style="max-width:100%; max-height:90vh; border-radius:0.5rem;">
        </div>
    </div>

    <script>
        function showRejectModal() {
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }

        function openImageModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').classList.add('active');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('active');
        }

        function toggleSidebar() {
            document.querySelector('.admin-sidebar').classList.toggle('active');
        }

        // Close modal when clicking outside
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    </script>
</body>
</html>
<?php closeDBConnection($conn); ?>
