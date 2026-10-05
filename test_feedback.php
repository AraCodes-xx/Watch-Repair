<?php
require_once 'config/config.php';

echo "<h1>Feedback Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}</style>";

$conn = getDBConnection();

// Check feedback table
echo "<h2>All Feedback in Database:</h2>";
$result = $conn->query("SELECT f.*, u.full_name, t.full_name as technician_name 
                        FROM feedback f 
                        LEFT JOIN users u ON f.user_id = u.user_id 
                        LEFT JOIN technicians t ON f.technician_id = t.technician_id 
                        ORDER BY f.created_at DESC");

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>User</th><th>Technician</th><th>Rating</th><th>Comment</th><th>Approved</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $approved = $row['is_approved'] ? '✅ Yes' : '❌ No';
        echo "<tr>";
        echo "<td>" . $row['feedback_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['full_name'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['technician_name'] ?? 'N/A') . "</td>";
        echo "<td>" . str_repeat('⭐', $row['rating']) . " (" . $row['rating'] . ")</td>";
        echo "<td>" . htmlspecialchars($row['comment']) . "</td>";
        echo "<td>" . $approved . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>❌ No feedback found in database!</p>";
    echo "<p><strong>Solution:</strong> You need to:</p>";
    echo "<ol>";
    echo "<li>Complete a booking</li>";
    echo "<li>Go to My Bookings → Click 'Rate Service'</li>";
    echo "<li>Submit a rating</li>";
    echo "<li>Admin approves the feedback</li>";
    echo "</ol>";
}

// Check approved feedback (what shows on homepage)
echo "<h2>Approved Feedback (Shows on Homepage):</h2>";
$approved_result = $conn->query("SELECT f.*, u.full_name, t.full_name as technician_name 
                                 FROM feedback f 
                                 JOIN users u ON f.user_id = u.user_id 
                                 JOIN technicians t ON f.technician_id = t.technician_id 
                                 WHERE f.is_approved = 1 
                                 ORDER BY f.created_at DESC");

if ($approved_result->num_rows > 0) {
    echo "<p style='color:green;'>✅ Found " . $approved_result->num_rows . " approved feedback(s)</p>";
    echo "<table>";
    echo "<tr><th>User</th><th>Technician</th><th>Rating</th><th>Comment</th></tr>";
    while ($row = $approved_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['technician_name']) . "</td>";
        echo "<td>" . str_repeat('⭐', $row['rating']) . "</td>";
        echo "<td>" . htmlspecialchars($row['comment']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange;'>⚠️ No approved feedback yet!</p>";
    echo "<p><strong>To fix:</strong></p>";
    echo "<ol>";
    echo "<li>Go to Admin Panel → Feedback</li>";
    echo "<li>Approve some feedback entries</li>";
    echo "<li>Refresh homepage to see them</li>";
    echo "</ol>";
}

closeDBConnection($conn);
?>

<p><a href="index.php">← Back to Homepage</a></p>
