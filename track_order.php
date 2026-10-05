<?php
require_once 'config/config.php';

$booking = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_id = intval($_POST['booking_id']);
    $email = sanitize_input($_POST['email']);
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT b.*, s.service_name, t.full_name as technician_name, ts.start_time, ts.end_time,
                            p.verification_status, p.payment_method, u.email
                            FROM bookings b 
                            JOIN services s ON b.service_id = s.service_id 
                            LEFT JOIN technicians t ON b.technician_id = t.technician_id 
                            JOIN timeslots ts ON b.timeslot_id = ts.timeslot_id
                            LEFT JOIN payments p ON b.booking_id = p.booking_id
                            JOIN users u ON b.user_id = u.user_id
                            WHERE b.booking_id = ? AND u.email = ?");
    $stmt->bind_param("is", $booking_id, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
    } else {
        $error = "Booking not found. Please check your Booking ID and email address.";
    }
    
    $stmt->close();
    closeDBConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track My Order - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .track-form {
            max-width: 500px;
            margin: 3rem auto;
        }
        .tracking-result {
            max-width: 800px;
            margin: 2rem auto;
        }
        .progress-tracker {
            display: flex;
            justify-content: space-between;
            margin: 3rem 0;
            position: relative;
        }
        .progress-tracker::before {
            content: '';
            position: absolute;
            top: 30px;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--border-color);
            z-index: 0;
        }
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--bg-card);
            border: 4px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        .progress-step.completed .progress-circle {
            background: var(--success);
            border-color: var(--success);
        }
        .progress-step.active .progress-circle {
            background: var(--primary-color);
            border-color: var(--primary-color);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(99, 102, 241, 0); }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-container">
                <a href="index.php" class="navbar-brand">⌚ <?php echo SITE_NAME; ?></a>
                <ul class="navbar-menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#services">Services</a></li>
                    <li><a href="track_order.php" class="active">Track Order</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="user/dashboard.php">Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn btn-primary btn-sm">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem; margin-bottom: 3rem;">
        <h1 class="text-center">Track My Order</h1>
        <p class="text-center" style="color: var(--text-secondary); margin-bottom: 3rem;">
            Enter your booking details to track your watch repair status
        </p>

        <?php if (!$booking): ?>
        <div class="track-form">
            <div class="card">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Booking ID</label>
                            <input type="number" name="booking_id" class="form-control" placeholder="Enter your booking ID (e.g., 123)" required>
                            <small style="color: var(--text-secondary);">You received this in your booking confirmation</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="Enter your email address" required>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">Track Order</button>
                    </form>

                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                        <p style="text-align: center; color: var(--text-secondary); font-size: 0.875rem;">
                            Already have an account? <a href="login.php" style="color: var(--primary-color);">Login</a> to view all your bookings
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="tracking-result">
            <!-- Progress Tracker -->
            <div class="progress-tracker">
                <div class="progress-step <?php echo $booking['verification_status'] == 'Verified' ? 'completed' : ($booking['verification_status'] == 'Pending' ? 'active' : ''); ?>">
                    <div class="progress-circle">💳</div>
                    <div style="font-weight: 600;">Payment Verified</div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                        <?php echo $booking['verification_status'] ?? 'Pending'; ?>
                    </div>
                </div>
                <div class="progress-step <?php echo $booking['status'] == 'Approved' || $booking['status'] == 'Completed' ? 'completed' : ''; ?>">
                    <div class="progress-circle">✅</div>
                    <div style="font-weight: 600;">Booking Approved</div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                        <?php echo $booking['status'] == 'Approved' || $booking['status'] == 'Completed' ? 'Approved' : 'Pending'; ?>
                    </div>
                </div>
                <div class="progress-step <?php echo $booking['technician_name'] ? 'completed' : ''; ?>">
                    <div class="progress-circle">👨‍🔧</div>
                    <div style="font-weight: 600;">Technician Assigned</div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                        <?php echo $booking['technician_name'] ?? 'Not Yet'; ?>
                    </div>
                </div>
                <div class="progress-step <?php echo $booking['status'] == 'Completed' ? 'completed' : ($booking['status'] == 'Approved' ? 'active' : ''); ?>">
                    <div class="progress-circle">🎉</div>
                    <div style="font-weight: 600;">Service Completed</div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                        <?php echo $booking['status'] == 'Completed' ? 'Done' : 'In Progress'; ?>
                    </div>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">📋 Booking Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div style="margin-bottom: 1.5rem;">
                                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Service</div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($booking['service_name']); ?></div>
                            </div>
                            <div style="margin-bottom: 1.5rem;">
                                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Date & Time</div>
                                <div style="font-weight: 600;">
                                    <?php echo date('F d, Y', strtotime($booking['booking_date'])); ?><br>
                                    <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="margin-bottom: 1.5rem;">
                                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Status</div>
                                <span class="badge <?php echo get_status_badge_class($booking['status']); ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                    <?php echo $booking['status']; ?>
                                </span>
                            </div>
                            <div style="margin-bottom: 1.5rem;">
                                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Total Cost</div>
                                <div style="font-weight: 600; font-size: 1.5rem; color: var(--primary-color);">
                                    <?php echo format_currency($booking['total_cost']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($booking['technician_name']): ?>
                    <div class="alert alert-info">
                        <strong>👨‍🔧 Technician Assigned:</strong> <?php echo htmlspecialchars($booking['technician_name']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($booking['status'] == 'Completed'): ?>
                    <div class="alert alert-success">
                        <strong>🎉 Service Completed!</strong> Your watch is ready for pickup.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="track_order.php" class="btn btn-secondary">Track Another Order</a>
                <?php if (is_logged_in()): ?>
                    <a href="user/my_bookings.php" class="btn btn-primary">View All My Bookings</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Login to Your Account</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
