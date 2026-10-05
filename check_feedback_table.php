<?php
require_once 'config/config.php';

echo "<h1>Feedback Table Check</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .error{color:red;}</style>";

$conn = getDBConnection();

// Check if feedback table exists
$result = $conn->query("SHOW TABLES LIKE 'feedback'");

if ($result->num_rows > 0) {
    echo "<p class='ok'>✅ Feedback table exists</p>";
    
    // Show table structure
    echo "<h2>Table Structure:</h2>";
    $structure = $conn->query("DESCRIBE feedback");
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count feedback
    $count = $conn->query("SELECT COUNT(*) as total FROM feedback")->fetch_assoc()['total'];
    $approved = $conn->query("SELECT COUNT(*) as total FROM feedback WHERE is_approved = 1")->fetch_assoc()['total'];
    
    echo "<h2>Statistics:</h2>";
    echo "<p>Total Feedback: <strong>$count</strong></p>";
    echo "<p>Approved Feedback: <strong>$approved</strong></p>";
    
    if ($count == 0) {
        echo "<div style='background:#fff3cd;padding:1rem;border-radius:0.5rem;margin-top:1rem;'>";
        echo "<h3>⚠️ No Feedback Yet</h3>";
        echo "<p><strong>To add feedback:</strong></p>";
        echo "<ol>";
        echo "<li>Complete a booking (mark as 'Completed' in admin)</li>";
        echo "<li>Login as the customer</li>";
        echo "<li>Go to My Bookings</li>";
        echo "<li>Click '⭐ Rate Service' button</li>";
        echo "<li>Submit your rating</li>";
        echo "<li>Admin approves it in Admin Panel → Feedback</li>";
        echo "</ol>";
        echo "</div>";
    } elseif ($approved == 0) {
        echo "<div style='background:#fff3cd;padding:1rem;border-radius:0.5rem;margin-top:1rem;'>";
        echo "<h3>⚠️ No Approved Feedback</h3>";
        echo "<p><strong>To approve feedback:</strong></p>";
        echo "<ol>";
        echo "<li>Login as admin</li>";
        echo "<li>Go to Admin Panel → Feedback</li>";
        echo "<li>Click 'Approve' on feedback entries</li>";
        echo "<li>Refresh homepage to see them</li>";
        echo "</ol>";
        echo "</div>";
    }
    
} else {
    echo "<p class='error'>❌ Feedback table does NOT exist!</p>";
    echo "<p><strong>Solution:</strong> Run the database migration script</p>";
    echo "<p>File: <code>database/watch_repair_shop.sql</code></p>";
}

closeDBConnection($conn);
?>

<p style="margin-top:2rem;">
    <a href="test_feedback.php">View Feedback Details</a> | 
    <a href="index.php">Homepage</a> | 
    <a href="admin/view_feedback.php">Admin Feedback</a>
</p>
