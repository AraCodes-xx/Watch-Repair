<?php
require_once '../config/config.php';
require_admin();

$admin_id = get_current_admin_id();

// Fetch statistics
$conn = getDBConnection();

// Total bookings
$total_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings")->fetch_assoc()['total'];

// Pending bookings
$pending_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'Pending'")->fetch_assoc()['total'];

// Approved bookings
$approved_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'Approved'")->fetch_assoc()['total'];

// Completed bookings
$completed_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'Completed'")->fetch_assoc()['total'];

// Total income (completed bookings)
$total_income = $conn->query("SELECT SUM(total_cost) as total FROM bookings WHERE status = 'Completed'")->fetch_assoc()['total'] ?? 0;

// Down payments collected
$down_payments = $conn->query("SELECT SUM(down_payment) as total FROM bookings WHERE status != 'Rejected' AND status != 'Cancelled'")->fetch_assoc()['total'] ?? 0;

// Pending payment verifications
$pending_payments = $conn->query("SELECT COUNT(*) as total FROM payments WHERE verification_status = 'Pending'")->fetch_assoc()['total'];

// Total technicians
$total_technicians = $conn->query("SELECT COUNT(*) as total FROM technicians")->fetch_assoc()['total'];

// Total users
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

// Recent bookings
$recent_bookings = $conn->query("SELECT b.*, u.full_name as customer_name, s.service_name, t.full_name as technician_name 
                                 FROM bookings b 
                                 JOIN users u ON b.user_id = u.user_id 
                                 JOIN services s ON b.service_id = s.service_id 
                                 LEFT JOIN technicians t ON b.technician_id = t.technician_id 
                                 ORDER BY b.created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
            <li><a href="dashboard.php" class="active"><span class="admin-sidebar-icon">📊</span> Dashboard</a></li>
            <li><a href="view_booking.php"><span class="admin-sidebar-icon">📋</span> Bookings</a></li>
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
        <!-- Top Bar -->
        <div class="admin-topbar">
            <div>
                <button class="admin-sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1 style="margin: 0; display: inline-block;">Dashboard</h1>
            </div>
            <div style="color: var(--text-secondary);">
                Welcome back, <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-3">
                <div class="card text-center">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">📊</div>
                    <h3><?php echo $total_bookings; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="col-3">
                <div class="card text-center">
                    <div style="font-size: 2.5rem; color: var(--warning); margin-bottom: 0.5rem;">⏳</div>
                    <h3><?php echo $pending_bookings; ?></h3>
                    <p>Pending Approval</p>
                </div>
            </div>
            <div class="col-3">
                <div class="card text-center">
                    <div style="font-size: 2.5rem; color: var(--success); margin-bottom: 0.5rem;">✅</div>
                    <h3><?php echo $completed_bookings; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            <div class="col-3">
                <div class="card text-center">
                    <div style="font-size: 2.5rem; color: var(--info); margin-bottom: 0.5rem;">💰</div>
                    <h3><?php echo format_currency($total_income); ?></h3>
                    <p>Total Income</p>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-3">
                <div class="card text-center">
                    <div style="font-size: 2.5rem; color: var(--secondary-color); margin-bottom: 0.5rem;">💳</div>
                    <h3><?php echo format_currency($down_payments); ?></h3>
                    <p>Down Payments</p>
                </div>
            </div>
            <div class="col-3">
                <div class="card text-center">
                    <div style="font-size: 2.5rem; color: var(--danger); margin-bottom: 0.5rem;">🔍</div>
                    <h3><?php echo $pending_payments; ?></h3>
                    <p>Pending Verifications</p>
                </div>
            </div>
            <div class="col-3">
                <div class="card text-center">
                    <div style="font-size: 2.5rem; color: var(--success); margin-bottom: 0.5rem;">👨‍🔧</div>
                    <h3><?php echo $total_technicians; ?></h3>
                    <p>Technicians</p>
                </div>
            </div>
            <div class="col-3">
                <div class="card text-center">
                    <div style="font-size: 2.5rem; color: var(--info); margin-bottom: 0.5rem;">✔️</div>
                    <h3><?php echo $approved_bookings; ?></h3>
                    <p>Approved Bookings</p>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-3">
                <div class="card text-center">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">👥</div>
                    <h3><?php echo $total_users; ?></h3>
                    <p>Registered Users</p>
                    <a href="manage.php?entity=users" class="btn btn-sm btn-secondary mt-2">View All</a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">Quick Actions</div>
            <div class="card-body">
                <div class="d-flex gap-2">
                    <a href="view_booking.php?filter=pending" class="btn btn-warning">⏳ Review Pending Bookings (<?php echo $pending_bookings; ?>)</a>
                    <a href="view_payment.php?filter=pending" class="btn btn-danger">💳 Verify Payments (<?php echo $pending_payments; ?>)</a>
                    <a href="form.php?entity=services&action=create" class="btn btn-primary">➕ Add Service</a>
                    <a href="form.php?entity=technicians&action=create" class="btn btn-success">👨‍🔧 Add Technician</a>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">Recent Bookings</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Technician</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                            <td><?php echo $booking['technician_name'] ?? '<em>Not Assigned</em>'; ?></td>
                            <td><?php echo format_currency($booking['total_cost']); ?></td>
                            <td><span class="badge <?php echo get_status_badge_class($booking['status']); ?>"><?php echo $booking['status']; ?></span></td>
                            <td>
                                <a href="edit_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-primary">Manage</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.admin-sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
<?php closeDBConnection($conn); ?>
