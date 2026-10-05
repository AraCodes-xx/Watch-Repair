<?php
require_once '../config/config.php';
require_login();

$user_id = get_current_user_id();
$error = '';
$success = '';

$conn = getDBConnection();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize_input($_POST['full_name']);
    $contact_number = sanitize_input($_POST['contact_number']);
    $address = sanitize_input($_POST['address']);
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, contact_number = ?, address = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $full_name, $contact_number, $address, $user_id);
    
    if ($stmt->execute()) {
        $success = 'Profile updated successfully!';
        $_SESSION['user_name'] = $full_name;
    } else {
        $error = 'Failed to update profile';
    }
    $stmt->close();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    
    if (!password_verify($current_password, $user_data['password'])) {
        $error = 'Current password is incorrect';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $success = 'Password changed successfully!';
        } else {
            $error = 'Failed to change password';
        }
    }
    $stmt->close();
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-container">
                <a href="../index.php" class="navbar-brand">⌚ <?php echo SITE_NAME; ?></a>
                <ul class="navbar-menu">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="booking_wizard.php">Book Service</a></li>
                    <li><a href="my_bookings.php">My Bookings</a></li>
                    <li><a href="notifications.php">Notifications</a></li>
                    <li><a href="profile.php" class="active">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem; margin-bottom: 3rem;">
        <h1>My Profile</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Manage your account information</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">Personal Information</div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small style="color: var(--text-secondary);">Email cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Contact Number</label>
                                <input type="tel" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($user['contact_number']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card mb-3">
                    <div class="card-header">Change Password</div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Account Information</div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Member Since:</strong><br>
                            <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
                        </div>
                        <div>
                            <strong>Last Updated:</strong><br>
                            <?php echo date('F d, Y g:i A', strtotime($user['updated_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php closeDBConnection($conn); ?>
