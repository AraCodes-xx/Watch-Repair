<?php
session_start();
require_once 'config/config.php';

echo "<h1>Fix User Issue</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .error{color:red;}</style>";

$conn = getDBConnection();

// Check current session
if (isset($_SESSION['user_id'])) {
    $session_user_id = $_SESSION['user_id'];
    echo "<p>Session user_id: $session_user_id</p>";
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $session_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p class='ok'>✅ User exists in database!</p>";
        $user = $result->fetch_assoc();
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    } else {
        echo "<p class='error'>❌ User ID $session_user_id does NOT exist in database</p>";
        echo "<h2>Fix Options:</h2>";
        
        // Option 1: Create the missing user
        if (isset($_POST['create_user'])) {
            $email = "user" . $session_user_id . "@example.com";
            $password = password_hash("password123", PASSWORD_DEFAULT);
            $full_name = "Test User " . $session_user_id;
            
            $stmt = $conn->prepare("INSERT INTO users (user_id, email, password, full_name, contact_number, address) VALUES (?, ?, ?, ?, '09123456789', 'Test Address')");
            $stmt->bind_param("isss", $session_user_id, $email, $password, $full_name);
            
            if ($stmt->execute()) {
                echo "<p class='ok'>✅ User created successfully!</p>";
                echo "<p>Email: $email</p>";
                echo "<p>Password: password123</p>";
                echo "<p><a href='user/dashboard.php'>Go to Dashboard</a></p>";
            } else {
                echo "<p class='error'>Error: " . $stmt->error . "</p>";
            }
        } else {
            echo "<form method='POST'>";
            echo "<p><strong>Option 1:</strong> Create missing user with ID $session_user_id</p>";
            echo "<button type='submit' name='create_user' class='btn btn-primary'>Create User</button>";
            echo "</form>";
            
            echo "<p><strong>Option 2:</strong> <a href='user/logout.php'>Logout</a> and <a href='user/signup.php'>Register new account</a></p>";
        }
    }
} else {
    echo "<p class='error'>❌ No user session</p>";
    echo "<p><a href='user/login.php'>Login here</a> or <a href='user/signup.php'>Sign up</a></p>";
}

closeDBConnection($conn);
?>
