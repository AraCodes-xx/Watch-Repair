<?php
require_once '../config/config.php';
require_admin();

$technician_id = intval($_GET['id'] ?? 0);
if ($technician_id === 0) {
    redirect(SITE_URL . '/admin/manage.php?entity=technicians');
}

$conn = getDBConnection();
// fetch technician
$stmt = $conn->prepare("SELECT * FROM technicians WHERE technician_id = ?");
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$technician = $stmt->get_result()->fetch_assoc();
if (!$technician) {
    redirect(SITE_URL . '/admin/manage.php?entity=technicians');
}

// fetch jobs (completed or approved)
$jobs_query = "SELECT b.booking_id, b.booking_date, s.service_name, u.full_name AS customer_name, b.status
               FROM bookings b
               JOIN services s ON b.service_id = s.service_id
               JOIN users u ON b.user_id = u.user_id
               WHERE b.technician_id = ?
               ORDER BY b.booking_date DESC";
$stmt = $conn->prepare($jobs_query);
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$jobs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($technician['full_name']); ?> - Job History | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-layout">
    <div class="container" style="margin-top:3rem;">
        <a href="manage.php?entity=technicians" class="btn btn-secondary mb-3">← Back to Technicians</a>
        <h1>👨‍🔧 Job History - <?php echo htmlspecialchars($technician['full_name']); ?></h1>
        <div class="card mt-3">
            <div class="card-body">
                <?php if ($jobs->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Service</th>
                            <th>Customer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($job = $jobs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $job['booking_id']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($job['booking_date'])); ?></td>
                            <td><?php echo htmlspecialchars($job['service_name']); ?></td>
                            <td><?php echo htmlspecialchars($job['customer_name']); ?></td>
                            <td><span class="badge <?php echo get_status_badge_class($job['status']); ?>"><?php echo $job['status']; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p>No job history found for this technician.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php closeDBConnection($conn); ?>
