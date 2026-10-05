<?php
session_start();
require_once 'config/config.php';

echo "<h1>User Session Check</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .error{color:red;}</style>";

// Check session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "<p class='ok'>✅ User ID in session: $user_id</p>";
    
    // Check if user exists in database
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p class='ok'>✅ User exists in database</p>";
        echo "<pre>";
        echo "User ID: " . $user['user_id'] . "\n";
        echo "Name: " . $user['full_name'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Address: " . ($user['address'] ?? 'Not set') . "\n";
        echo "</pre>";
    } else {
        echo "<p class='error'>❌ User ID $user_id does NOT exist in users table!</p>";
        echo "<p><strong>Solution:</strong> You need to re-register or login with a valid account.</p>";
        echo "<p>→ <a href='user/logout.php'>Logout</a> and <a href='user/signup.php'>Register again</a></p>";
    }
    
    $stmt->close();
    closeDBConnection($conn);
} else {
    echo "<p class='error'>❌ No user session found</p>";
    echo "<p>→ <a href='user/login.php'>Login here</a></p>";
}
?>
