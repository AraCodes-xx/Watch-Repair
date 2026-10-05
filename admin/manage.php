<?php
require_once '../config/config.php';
require_once 'config/entities.php';
require_admin();

$entity = $_GET['entity'] ?? '';

if (!entity_exists($entity)) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$config = get_entity_config($entity);
$conn = getDBConnection();

// Handle success messages
$success = $_GET['success'] ?? '';

// Fetch all records
$query = "SELECT * FROM {$config['table']} ORDER BY {$config['primary_key']} DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage <?php echo $config['name']; ?> - <?php echo SITE_NAME; ?></title>
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
            <li><a href="manage.php?entity=services" <?php echo ($entity == 'services' || $entity == 'watch_types') ? 'class="active"' : ''; ?>><span class="admin-sidebar-icon">🔧</span> Services</a></li>
            <li><a href="manage.php?entity=technicians" <?php echo $entity == 'technicians' ? 'class="active"' : ''; ?>><span class="admin-sidebar-icon">👨‍🔧</span> Technicians</a></li>
            <li><a href="manage.php?entity=users" <?php echo $entity == 'users' ? 'class="active"' : ''; ?>><span class="admin-sidebar-icon">👥</span> Users</a></li>
            <li><a href="manage.php?entity=timeslots" <?php echo $entity == 'timeslots' ? 'class="active"' : ''; ?>><span class="admin-sidebar-icon">⏰</span> Time Slots</a></li>
            <li><a href="view_feedback.php"><span class="admin-sidebar-icon">⭐</span> Feedback</a></li>
            <li><a href="logout.php"><span class="admin-sidebar-icon">🚪</span> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="admin-topbar">
            <div>
                <button class="admin-sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1 style="margin: 0; display: inline-block;">Manage <?php echo $config['name']; ?></h1>
            </div>
            <a href="form.php?entity=<?php echo $entity; ?>&action=create" class="btn btn-primary">+ Add New <?php echo $config['singular']; ?></a>
        </div>
        <?php if ($entity == 'services'): ?>
        <div class="alert alert-info mb-3" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>💡 Tip:</strong> Manage watch types that customers can select when booking services
            </div>
            <a href="manage.php?entity=watch_types" class="btn btn-secondary">Manage Watch Types</a>
        </div>
        <?php endif; ?>
        
        <?php if ($entity == 'watch_types'): ?>
        <div class="alert alert-info mb-3" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>⌚ Watch Types:</strong> These are the watch types customers can select when booking
            </div>
            <a href="manage.php?entity=services" class="btn btn-secondary">← Back to Services</a>
        </div>
        <?php endif; ?>

        <?php if ($success == 'created'): ?>
            <div class="alert alert-success"><?php echo $config['singular']; ?> created successfully!</div>
        <?php elseif ($success == 'updated'): ?>
            <div class="alert alert-success"><?php echo $config['singular']; ?> updated successfully!</div>
        <?php elseif ($success == 'deleted'): ?>
            <div class="alert alert-success"><?php echo $config['singular']; ?> deleted successfully!</div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <?php if ($result->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <?php foreach ($config['fields'] as $field_name => $field_config): ?>
                                <?php if ($field_config['list']): ?>
                                    <th><?php echo $field_config['label']; ?></th>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (isset($config['extra_display'])): ?>
                                <?php foreach ($config['extra_display'] as $label): ?>
                                    <th><?php echo $label; ?></th>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <?php foreach ($config['fields'] as $field_name => $field_config): ?>
                                <?php if ($field_config['list']): ?>
                                    <td>
                                        <?php 
                                        if ($field_config['type'] == 'checkbox') {
                                            echo '<span class="badge ' . ($row[$field_name] ? 'badge-success' : 'badge-secondary') . '">';
                                            echo $row[$field_name] ? 'Yes' : 'No';
                                            echo '</span>';
                                        } elseif ($field_name == 'base_price') {
                                            echo format_currency($row[$field_name]);
                                        } elseif ($field_config['type'] == 'time') {
                                            echo date('g:i A', strtotime($row[$field_name]));
                                        } else {
                                            echo htmlspecialchars($row[$field_name]);
                                        }
                                        ?>
                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (isset($config['extra_display'])): ?>
                                <?php foreach ($config['extra_display'] as $field => $label): ?>
                                    <td>
                                        <?php 
                                        if ($field == 'rating') {
                                            echo number_format($row[$field], 2) . ' / 5.00';
                                        } elseif ($field == 'created_at') {
                                            echo date('M d, Y', strtotime($row[$field]));
                                        } else {
                                            echo htmlspecialchars($row[$field]);
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <td>
    <div style="display:flex;gap:0.4rem;flex-wrap:nowrap;">
        <a href="form.php?entity=<?php echo $entity; ?>&action=edit&id=<?php echo $row[$config['primary_key']]; ?>" class="btn btn-sm btn-primary" style="white-space:nowrap;">Edit</a>
<?php if ($entity == 'technicians'): ?>
        <a href="technician_history.php?id=<?php echo $row[$config['primary_key']]; ?>" class="btn btn-sm" style="background:#2563eb;color:#fff;white-space:nowrap;">History</a>
<?php endif; ?>
        <a href="delete.php?entity=<?php echo $entity; ?>&id=<?php echo $row[$config['primary_key']]; ?>" class="btn btn-sm btn-danger" style="white-space:nowrap;" onclick="return confirm('Are you sure you want to delete this <?php echo strtolower($config['singular']); ?>?')">Delete</a>
    </div>
</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="text-center" style="padding: 3rem;">
                    <h3>No <?php echo strtolower($config['name']); ?> found</h3>
                    <p style="color: var(--text-secondary);">Add your first <?php echo strtolower($config['singular']); ?> to get started</p>
                    <a href="form.php?entity=<?php echo $entity; ?>&action=create" class="btn btn-primary mt-2">Add <?php echo $config['singular']; ?></a>
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
