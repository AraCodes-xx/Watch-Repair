<?php
require_once '../config/config.php';
require_admin();

$filter = $_GET['filter'] ?? 'all';

$conn = getDBConnection();

// Build query based on filter
$query = "SELECT p.*, b.booking_id, b.booking_date, u.full_name as customer_name, s.service_name 
          FROM payments p 
          JOIN bookings b ON p.booking_id = b.booking_id 
          JOIN users u ON b.user_id = u.user_id 
          JOIN services s ON b.service_id = s.service_id";

if ($filter != 'all') {
    $query .= " WHERE p.verification_status = '" . $conn->real_escape_string(ucfirst($filter)) . "'";
}

$query .= " ORDER BY p.created_at DESC";

$payments = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification - <?php echo SITE_NAME; ?></title>
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

    <div class="container-fluid" style="margin-top: 3rem; margin-bottom: 3rem;">
        <h1>Payment Verification</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Verify customer payment proofs</p>

        <!-- Filter Tabs -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex gap-2">
                    <a href="?filter=all" class="btn btn-sm <?php echo $filter == 'all' ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
                    <a href="?filter=pending" class="btn btn-sm <?php echo $filter == 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
                    <a href="?filter=approved" class="btn btn-sm <?php echo $filter == 'approved' ? 'btn-primary' : 'btn-secondary'; ?>">Approved</a>
                    <a href="?filter=rejected" class="btn btn-sm <?php echo $filter == 'rejected' ? 'btn-primary' : 'btn-secondary'; ?>">Rejected</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if ($payments->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payment = $payments->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $payment['payment_id']; ?></td>
                            <td>#<?php echo $payment['booking_id']; ?></td>
                            <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($payment['service_name']); ?></td>
                            <td><?php echo format_currency($payment['amount']); ?></td>
                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                            <td><span class="badge <?php echo get_status_badge_class($payment['verification_status']); ?>"><?php echo $payment['verification_status']; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                            <td>
                                <a href="edit_payment.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-sm btn-primary">Verify</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="text-center" style="padding: 3rem;">
                    <h3>No payments found</h3>
                    <p style="color: var(--text-secondary);">No payments match the selected filter</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php closeDBConnection($conn); ?>
