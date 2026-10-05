<?php
require_once '../config/config.php';
require_login();

$user_id = get_current_user_id();

// Fetch user info
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch bookings count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_bookings = $stmt->get_result()->fetch_assoc()['total'];

// Fetch pending bookings
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ? AND status = 'Pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_bookings = $stmt->get_result()->fetch_assoc()['total'];

// Fetch total spending
$stmt = $conn->prepare("SELECT SUM(total_cost) as total FROM bookings WHERE user_id = ? AND status = 'Completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_spending = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Fetch recent bookings
$stmt = $conn->prepare("SELECT b.*, s.service_name, t.full_name as technician_name, ts.start_time, ts.end_time 
                        FROM bookings b 
                        JOIN services s ON b.service_id = s.service_id 
                        LEFT JOIN technicians t ON b.technician_id = t.technician_id 
                        JOIN timeslots ts ON b.timeslot_id = ts.timeslot_id 
                        WHERE b.user_id = ? 
                        ORDER BY b.created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_bookings = $stmt->get_result();

// Fetch unread notifications
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_notifications = $stmt->get_result()->fetch_assoc()['total'];

// Fetch services
$services = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");

// Fetch approved feedback
$feedback_query = "SELECT f.*, u.full_name, t.full_name as technician_name 
                   FROM feedback f 
                   JOIN users u ON f.user_id = u.user_id 
                   JOIN technicians t ON f.technician_id = t.technician_id 
                   WHERE f.is_approved = 1 
                   ORDER BY f.created_at DESC LIMIT 6";
$feedback_result = $conn->query($feedback_query);

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/logout-confirm.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-container">
                <a href="../index.php" class="navbar-brand">⌚ <?php echo SITE_NAME; ?></a>
                <ul class="navbar-menu">
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="booking_wizard.php">Book Service</a></li>
                    <li><a href="my_bookings.php">My Bookings</a></li>
                    <li><a href="notifications.php" class="notification-badge" data-count="<?php echo $unread_notifications; ?>">Notifications</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem; margin-bottom: 3rem;">
        <h1>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Manage your watch repair bookings</p>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-4">
                <div class="card text-center">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">📅</div>
                    <h3><?php echo $total_bookings; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center">
                    <div style="font-size: 2.5rem; color: var(--warning); margin-bottom: 0.5rem;">⏳</div>
                    <h3><?php echo $pending_bookings; ?></h3>
                    <p>Pending Bookings</p>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center">
                    <div style="font-size: 2.5rem; color: var(--success); margin-bottom: 0.5rem;">💳</div>
                    <h3><?php echo format_currency($total_spending); ?></h3>
                    <p>Total Spending</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">Quick Actions</div>
            <div class="card-body">
                <div class="d-flex gap-2">
                    <a href="booking_wizard.php" class="btn btn-primary">📅 Book New Service</a>
                    <a href="my_bookings.php" class="btn btn-secondary">📋 View All Bookings</a>
                    <a href="notifications.php" class="btn btn-secondary">🔔 Notifications (<?php echo $unread_notifications; ?>)</a>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">Recent Bookings</div>
            <div class="card-body">
                <?php if ($recent_bookings->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Technician</th>
                            <th>Total Cost</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                            <td>
                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?><br>
                                <small><?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?></small>
                            </td>
                            <td><?php echo $booking['technician_name'] ?? 'Not Assigned'; ?></td>
                            <td><?php echo format_currency($booking['total_cost']); ?></td>
                            <td><span class="badge <?php echo get_status_badge_class($booking['status']); ?>"><?php echo $booking['status']; ?></span></td>
                            <td><a href="view_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-secondary">View</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-center" style="padding: 2rem;">No bookings yet. <a href="booking.php">Book your first service!</a></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Services Section -->
        <section id="services" class="p-5">
            <h2 class="text-center mb-4">Our Services</h2>
            <p class="text-center mb-5" style="color: var(--text-secondary);">We offer comprehensive watch repair and maintenance services</p>
            
            <div class="services-grid">
                <?php while ($service = $services->fetch_assoc()): ?>
                <div class="service-card">
                    <div class="service-icon">🔧</div>
                    <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                    <p><?php echo htmlspecialchars($service['description']); ?></p>
                    <div class="service-price"><?php echo format_currency($service['base_price']); ?></div>
                    <p style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.5rem;">
                        Duration: ~<?php echo $service['duration_hours']; ?> hour(s)
                    </p>
                </div>
                <?php endwhile; ?>
            </div>
        </section>

        <!-- Reviews Section -->
        <?php if ($feedback_result->num_rows > 0): ?>
        <section id="reviews" class="p-5" style="background-color: var(--bg-darker); border-radius: 1rem; margin-top: 2rem;">
            <h2 class="text-center mb-4">Customer Reviews</h2>
            <p class="text-center mb-5" style="color: var(--text-secondary);">See what our customers say about us</p>
            
            <div class="services-grid">
                <?php while ($review = $feedback_result->fetch_assoc()): ?>
                <div class="card">
                    <div class="d-flex justify-between align-center mb-2">
                        <strong><?php echo htmlspecialchars($review['full_name']); ?></strong>
                        <div>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span style="color: <?php echo $i <= $review['rating'] ? '#f59e0b' : '#334155'; ?>;">★</span>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                        Technician: <?php echo htmlspecialchars($review['technician_name']); ?>
                    </p>
                    <p><?php echo htmlspecialchars($review['comment']); ?></p>
                    <p style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 1rem;">
                        <?php echo date('F d, Y', strtotime($review['created_at'])); ?>
                    </p>
                </div>
                <?php endwhile; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Contact Section -->
        <section id="contact" class="p-5" style="margin-top: 2rem;">
            <h2 class="text-center mb-4">Contact Us</h2>
            <div class="row" style="margin-top: 3rem;">
                <div class="col-4">
                    <div class="card text-center">
                        <div style="font-size: 2rem; margin-bottom: 1rem;">📍</div>
                        <h4>Location</h4>
                        <p>123 Watch Street, Manila, Philippines</p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card text-center">
                        <div style="font-size: 2rem; margin-bottom: 1rem;">📞</div>
                        <h4>Phone</h4>
                        <p>+63 917 123 4567</p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card text-center">
                        <div style="font-size: 2rem; margin-bottom: 1rem;">✉️</div>
                        <h4>Email</h4>
                        <p><?php echo ADMIN_EMAIL; ?></p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
<?php closeDBConnection($conn); ?>
