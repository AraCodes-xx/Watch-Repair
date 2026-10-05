<?php
require_once '../config/config.php';
require_once 'config/entities.php';
require_admin();

$entity = $_GET['entity'] ?? '';
$action = $_GET['action'] ?? 'create';
$id = intval($_GET['id'] ?? 0);

if (!entity_exists($entity)) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$config = get_entity_config($entity);
$conn = getDBConnection();
$error = '';
$success = '';
$data = [];

// For edit action, fetch existing data
if ($action == 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM {$config['table']} WHERE {$config['primary_key']} = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        redirect(SITE_URL . "/admin/manage.php?entity={$entity}");
    }
    
    $data = $result->fetch_assoc();
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $values = [];
    $valid = true;
    
    // Validate and collect form data
    foreach ($config['fields'] as $field_name => $field_config) {
        if ($field_config['type'] == 'checkbox') {
            $values[$field_name] = isset($_POST[$field_name]) ? 1 : 0;
        } else {
            $values[$field_name] = sanitize_input($_POST[$field_name] ?? '');
            
            if ($field_config['required'] && empty($values[$field_name])) {
                $error = "Please fill in all required fields";
                $valid = false;
                break;
            }
            
            // Email validation
            if ($field_config['type'] == 'email' && !empty($values[$field_name])) {
                if (!filter_var($values[$field_name], FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email format";
                    $valid = false;
                    break;
                }
                
                // Check unique email
                if (isset($field_config['unique']) && $field_config['unique']) {
                    $check_query = "SELECT {$config['primary_key']} FROM {$config['table']} WHERE {$field_name} = ?";
                    if ($action == 'edit') {
                        $check_query .= " AND {$config['primary_key']} != ?";
                    }
                    
                    $stmt = $conn->prepare($check_query);
                    if ($action == 'edit') {
                        $stmt->bind_param("si", $values[$field_name], $id);
                    } else {
                        $stmt->bind_param("s", $values[$field_name]);
                    }
                    $stmt->execute();
                    
                    if ($stmt->get_result()->num_rows > 0) {
                        $error = "Email already exists";
                        $valid = false;
                        $stmt->close();
                        break;
                    }
                    $stmt->close();
                }
            }
            
            // Time validation
            if ($field_config['type'] == 'time' && $field_name == 'end_time') {
                if (!empty($values['start_time']) && !empty($values['end_time'])) {
                    if ($values['start_time'] >= $values['end_time']) {
                        $error = "End time must be after start time";
                        $valid = false;
                        break;
                    }
                }
            }
        }
    }
    
    if ($valid) {
        if ($action == 'create') {
            // Insert new record
            $fields = array_keys($values);
            $placeholders = array_fill(0, count($values), '?');
            
            $query = "INSERT INTO {$config['table']} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $conn->prepare($query);
            
            $types = '';
            $params = [];
            foreach ($values as $value) {
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $params[] = $value;
            }
            
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                redirect(SITE_URL . "/admin/manage.php?entity={$entity}&success=created");
            } else {
                $error = "Failed to create {$config['singular']}";
            }
            $stmt->close();
            
        } else {
            // Update existing record
            $set_parts = [];
            foreach (array_keys($values) as $field) {
                $set_parts[] = "{$field} = ?";
            }
            
            $query = "UPDATE {$config['table']} SET " . implode(', ', $set_parts) . " WHERE {$config['primary_key']} = ?";
            $stmt = $conn->prepare($query);
            
            $types = '';
            $params = [];
            foreach ($values as $value) {
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $params[] = $value;
            }
            $types .= 'i';
            $params[] = $id;
            
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                redirect(SITE_URL . "/admin/manage.php?entity={$entity}&success=updated");
            } else {
                $error = "Failed to update {$config['singular']}";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action == 'create' ? 'Add' : 'Edit'; ?> <?php echo $config['singular']; ?> - <?php echo SITE_NAME; ?></title>
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
                <h1 style="margin: 0; display: inline-block;"><?php echo $action == 'create' ? 'Add New' : 'Edit'; ?> <?php echo $config['singular']; ?></h1>
            </div>
            <a href="manage.php?entity=<?php echo $entity; ?>" class="btn btn-secondary">← Back to <?php echo $config['name']; ?></a>
        </div>

        <div style="max-width: 800px;">

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <?php foreach ($config['fields'] as $field_name => $field_config): ?>
                        <div class="form-group">
                            <label class="form-label">
                                <?php echo $field_config['label']; ?>
                                <?php if ($field_config['required']): ?> *<?php endif; ?>
                            </label>
                            
                            <?php if ($field_config['type'] == 'textarea'): ?>
                                <textarea 
                                    name="<?php echo $field_name; ?>" 
                                    class="form-control" 
                                    rows="4"
                                    <?php echo $field_config['required'] ? 'required' : ''; ?>
                                ><?php echo htmlspecialchars($data[$field_name] ?? ''); ?></textarea>
                            
                            <?php elseif ($field_config['type'] == 'checkbox'): ?>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input 
                                        type="checkbox" 
                                        name="<?php echo $field_name; ?>"
                                        <?php echo ($data[$field_name] ?? $field_config['default'] ?? 0) ? 'checked' : ''; ?>
                                    >
                                    <span>Enable this option</span>
                                </label>
                            
                            <?php else: ?>
                                <input 
                                    type="<?php echo $field_config['type']; ?>" 
                                    name="<?php echo $field_name; ?>" 
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($data[$field_name] ?? ''); ?>"
                                    <?php echo $field_config['required'] ? 'required' : ''; ?>
                                    <?php echo $field_config['attributes'] ?? ''; ?>
                                >
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($action == 'edit' && isset($config['extra_display'])): ?>
                        <div class="mb-3" style="padding: 1rem; background-color: var(--bg-darker); border-radius: 0.5rem;">
                            <strong>Additional Information:</strong><br>
                            <?php foreach ($config['extra_display'] as $field => $label): ?>
                                <small style="color: var(--text-secondary);">
                                    <?php echo $label; ?>: 
                                    <?php 
                                    if ($field == 'rating') {
                                        echo number_format($data[$field], 2) . ' / 5.00';
                                    } elseif ($field == 'created_at') {
                                        echo date('F d, Y', strtotime($data[$field]));
                                    } else {
                                        echo htmlspecialchars($data[$field]);
                                    }
                                    ?>
                                </small><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action == 'create' ? 'Create' : 'Update'; ?> <?php echo $config['singular']; ?>
                        </button>
                        <a href="manage.php?entity=<?php echo $entity; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
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
