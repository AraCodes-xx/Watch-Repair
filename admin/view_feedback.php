<?php
require_once '../config/config.php';
require_admin();

$filter = $_GET['filter'] ?? 'all';

$conn = getDBConnection();

$query = "SELECT f.*, u.full_name as customer_name, t.full_name as technician_name, b.booking_id 
          FROM feedback f 
          JOIN users u ON f.user_id = u.user_id 
          JOIN technicians t ON f.technician_id = t.technician_id 
          JOIN bookings b ON f.booking_id = b.booking_id";

if ($filter == 'pending') {
    $query .= " WHERE f.is_approved = 0";
} elseif ($filter == 'approved') {
    $query .= " WHERE f.is_approved = 1";
}

$query .= " ORDER BY f.created_at DESC";

$feedbacks = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedback - <?php echo SITE_NAME; ?></title>
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
            <li><a href="view_booking.php"><span class="admin-sidebar-icon">📋</span> Bookings</a></li>
            <li><a href="manage.php?entity=services"><span class="admin-sidebar-icon">🔧</span> Services</a></li>
            <li><a href="manage.php?entity=technicians"><span class="admin-sidebar-icon">👨‍🔧</span> Technicians</a></li>
            <li><a href="manage.php?entity=users"><span class="admin-sidebar-icon">👥</span> Users</a></li>
            <li><a href="manage.php?entity=timeslots"><span class="admin-sidebar-icon">⏰</span> Time Slots</a></li>
            <li><a href="view_feedback.php" class="active"><span class="admin-sidebar-icon">⭐</span> Feedback</a></li>
            <li><a href="logout.php"><span class="admin-sidebar-icon">🚪</span> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="admin-topbar">
            <div>
                <button class="admin-sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1 style="margin: 0; display: inline-block;">Manage Feedback</h1>
            </div>
            <div style="color: var(--text-secondary);">
                Review and approve customer feedback
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex gap-2">
                    <a href="?filter=all" class="btn btn-sm <?php echo $filter == 'all' ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
                    <a href="?filter=pending" class="btn btn-sm <?php echo $filter == 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
                    <a href="?filter=approved" class="btn btn-sm <?php echo $filter == 'approved' ? 'btn-primary' : 'btn-secondary'; ?>">Approved</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if ($feedbacks->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Technician</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($feedback = $feedbacks->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($feedback['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($feedback['technician_name']); ?></td>
                            <td>
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo '<span style="color: ' . ($i <= $feedback['rating'] ? '#f59e0b' : '#334155') . ';">★</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars(substr($feedback['comment'], 0, 50)) . '...'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></td>
                            <td>
                                <span class="badge <?php echo $feedback['is_approved'] ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $feedback['is_approved'] ? 'Approved' : 'Pending'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_feedback.php?id=<?php echo $feedback['feedback_id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                <a href="delete_feedback.php?id=<?php echo $feedback['feedback_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="text-center" style="padding: 3rem;">
                    <h3>No feedback found</h3>
                    <p style="color: var(--text-secondary);">No feedback matches the selected filter</p>
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
