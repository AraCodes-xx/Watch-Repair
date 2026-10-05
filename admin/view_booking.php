<?php
require_once '../config/config.php';
require_admin();

$filter = $_GET['filter'] ?? 'all';

$conn = getDBConnection();

// Build query based on filter
$query = "SELECT b.*, u.full_name as customer_name, u.email, u.contact_number, 
          s.service_name, t.full_name as technician_name, ts.start_time, ts.end_time,
          p.verification_status as payment_status
          FROM bookings b 
          JOIN users u ON b.user_id = u.user_id 
          JOIN services s ON b.service_id = s.service_id 
          LEFT JOIN technicians t ON b.technician_id = t.technician_id 
          JOIN timeslots ts ON b.timeslot_id = ts.timeslot_id
          LEFT JOIN payments p ON b.booking_id = p.booking_id";

if ($filter != 'all') {
    $query .= " WHERE b.status = '" . $conn->real_escape_string(ucfirst($filter)) . "'";
}

$query .= " ORDER BY b.created_at DESC";

$bookings = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - <?php echo SITE_NAME; ?></title>
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
                <h1 style="margin: 0; display: inline-block;">Manage Bookings</h1>
            </div>
            <div style="color: var(--text-secondary);">
                View and manage all service bookings
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex gap-2">
                    <a href="?filter=all" class="btn btn-sm <?php echo $filter == 'all' ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
                    <a href="?filter=pending" class="btn btn-sm <?php echo $filter == 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
                    <a href="?filter=approved" class="btn btn-sm <?php echo $filter == 'approved' ? 'btn-primary' : 'btn-secondary'; ?>">Approved</a>
                    <a href="?filter=completed" class="btn btn-sm <?php echo $filter == 'completed' ? 'btn-primary' : 'btn-secondary'; ?>">Completed</a>
                    <a href="?filter=rejected" class="btn btn-sm <?php echo $filter == 'rejected' ? 'btn-primary' : 'btn-secondary'; ?>">Rejected</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if ($bookings->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Technician</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($booking['customer_name']); ?><br>
                                <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($booking['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                            <td>
                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?><br>
                                <small><?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?></small>
                            </td>
                            <td><?php echo $booking['technician_name'] ?? '<em>Not Assigned</em>'; ?></td>
                            <td><?php echo format_currency($booking['total_cost']); ?></td>
                            <td><span class="badge <?php echo get_status_badge_class($booking['status']); ?>"><?php echo $booking['status']; ?></span></td>
                            <td>
                                <a href="booking_detail.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-primary">📋 View Details</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="text-center" style="padding: 3rem;">
                    <h3>No bookings found</h3>
                    <p style="color: var(--text-secondary);">No bookings match the selected filter</p>
                </div>
                <?php endif; ?>
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
