<?php
require_once '../config/config.php';
require_login();

$user_id = get_current_user_id();

// Fetch all bookings
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT b.*, s.service_name, t.full_name as technician_name, ts.start_time, ts.end_time, 
                        p.verification_status, p.payment_method, b.payment_status 
                        FROM bookings b 
                        JOIN services s ON b.service_id = s.service_id 
                        LEFT JOIN technicians t ON b.technician_id = t.technician_id 
                        JOIN timeslots ts ON b.timeslot_id = ts.timeslot_id 
                        LEFT JOIN payments p ON b.booking_id = p.booking_id 
                        WHERE b.user_id = ? 
                        ORDER BY b.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/logout-confirm.js"></script>
    <style>
        .booking-card {
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: rgba(255,255,255,0.1);
            transition: box-shadow 0.3s;
        }
        .booking-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .booking-card.completed {
            border-left: 4px solid #28a745;
        }
        .booking-card.pending {
            border-left: 4px solid #ffc107;
        }
        .booking-card.rejected {
            border-left: 4px solid #dc3545;
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .booking-id {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }
        .detail-value {
            font-weight: 500;
        }
        .status-timeline {
            display: flex;
            justify-content: space-between;
            margin: 1.5rem 0;
            position: relative;
        }
        .status-timeline::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--border-color);
            z-index: 0;
        }
        .timeline-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 1.2rem;
        }
        .timeline-step.active .timeline-icon {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        .timeline-step.completed .timeline-icon {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }
        .timeline-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        .payment-info {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
        }
        .completion-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: bold;
        }
        .completion-badge.completed {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-container">
                <a href="../index.php" class="navbar-brand">⌚ <?php echo SITE_NAME; ?></a>
                <ul class="navbar-menu">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="booking_wizard.php">Book Service</a></li>
                    <li><a href="my_bookings.php" class="active">My Bookings</a></li>
                    <li><a href="notifications.php">Notifications</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem; margin-bottom: 3rem;">
        <h1>My Bookings</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">View and manage your service bookings</p>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Booking submitted successfully! Waiting for admin approval.</div>
        <?php endif; ?>

        <?php if ($bookings->num_rows > 0): ?>
            <?php while ($booking = $bookings->fetch_assoc()): 
                $status_class = strtolower($booking['status']);
                $is_completed = $booking['status'] == 'Completed';
                $payment_status = $booking['payment_status'] ?? 'Down Payment Only';
                $remaining_balance = $booking['total_cost'] - $booking['down_payment'];
            ?>
            <div class="booking-card <?php echo $status_class; ?>">
                <!-- Header -->
                <div class="booking-header">
                    <div>
                        <span class="booking-id"><?php echo htmlspecialchars($booking['service_name']); ?> - <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></span>
                        <?php if ($is_completed): ?>
                            <span class="completion-badge completed">✅ Completed</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="badge <?php echo get_status_badge_class($booking['status']); ?>" style="font-size:1rem; padding:0.5rem 1rem;">
                            <?php echo $booking['status']; ?>
                        </span>
                    </div>
                </div>

                <!-- Status Timeline -->
                <div class="status-timeline">
                    <div class="timeline-step <?php echo $booking['verification_status'] == 'Verified' ? 'completed' : ($booking['verification_status'] == 'Pending' ? 'active' : ''); ?>">
                        <div class="timeline-icon">💳</div>
                        <div class="timeline-label">Payment Verified</div>
                    </div>
                    <div class="timeline-step <?php echo $booking['status'] == 'Approved' || $booking['status'] == 'Completed' ? 'completed' : ''; ?>">
                        <div class="timeline-icon">✅</div>
                        <div class="timeline-label">Booking Approved</div>
                    </div>
                    <div class="timeline-step <?php echo $booking['technician_name'] ? 'completed' : ''; ?>">
                        <div class="timeline-icon">👨‍🔧</div>
                        <div class="timeline-label">Technician Assigned</div>
                    </div>
                    <div class="timeline-step <?php echo $is_completed ? 'completed' : ''; ?>">
                        <div class="timeline-icon">🎉</div>
                        <div class="timeline-label">Service Completed</div>
                    </div>
                </div>

                <!-- Booking Details -->
                <div class="booking-details">
                    <div class="detail-item">
                        <span class="detail-label">Service</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['service_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date & Time</span>
                        <span class="detail-value">
                            <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?><br>
                            <small><?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?></small>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Technician</span>
                        <span class="detail-value"><?php echo $booking['technician_name'] ?? '<em>Not Assigned Yet</em>'; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Cost</span>
                        <span class="detail-value" style="font-size:1.2rem; color:var(--primary-color);"><?php echo format_currency($booking['total_cost']); ?></span>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="payment-info">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <strong>💰 Payment Information</strong>
                        <?php 
                        $payment_badge_class = $payment_status == 'Fully Paid' ? 'badge-success' : 'badge-warning';
                        ?>
                        <span class="badge <?php echo $payment_badge_class; ?>"><?php echo $payment_status; ?></span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1rem;">
                        <div>
                            <div class="detail-label">Down Payment (50%)</div>
                            <div class="detail-value" style="color: #28a745;"><?php echo format_currency($booking['down_payment']); ?></div>
                            <small style="color: var(--text-secondary);">
                                <?php echo $booking['verification_status'] ?? 'Pending Verification'; ?>
                            </small>
                        </div>
                        <div>
                            <div class="detail-label">Remaining Balance (50%)</div>
                            <div class="detail-value" style="color: <?php echo $payment_status == 'Fully Paid' ? '#28a745' : '#dc3545'; ?>;">
                                <?php echo format_currency($remaining_balance); ?>
                            </div>
                            <small style="color: var(--text-secondary);">
                                <?php echo $payment_status == 'Fully Paid' ? 'Paid to technician' : 'Pay to technician'; ?>
                            </small>
                        </div>
                        <div>
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['payment_method'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    
                    <?php if ($payment_status != 'Fully Paid' && $booking['status'] == 'Approved'): ?>
                    <div class="alert alert-info" style="margin-top: 1rem; margin-bottom: 0;">
                        <strong>📝 Note:</strong> Please pay the remaining balance of <strong><?php echo format_currency($remaining_balance); ?></strong> to the technician upon service completion.
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
                    <a href="view_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-secondary">📋 View Details</a>
                    <?php if ($is_completed): ?>
                        <a href="add_feedback.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary">⭐ Rate Service</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
                <?php else: ?>
                <div class="text-center" style="padding: 3rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📅</div>
                    <h3>No Bookings Yet</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 2rem;">Start by booking your first service</p>
                    <a href="booking.php" class="btn btn-primary">Book Now</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php closeDBConnection($conn); ?>
