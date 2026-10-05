<?php
require_once 'config/config.php';

$conn = getDBConnection();

echo "<h1>Database Migration Status</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .missing{color:red;font-weight:bold;}</style>";

// Check for watch_types table
$result = $conn->query("SHOW TABLES LIKE 'watch_types'");
if ($result && $result->num_rows > 0) {
    echo "<p class='ok'>✅ watch_types table EXISTS</p>";
    $count = $conn->query("SELECT COUNT(*) as cnt FROM watch_types")->fetch_assoc()['cnt'];
    echo "<p>   → Contains $count watch types</p>";
} else {
    echo "<p class='missing'>❌ watch_types table MISSING - Need to run migration!</p>";
}

// Check for booking_services table
$result = $conn->query("SHOW TABLES LIKE 'booking_services'");
if ($result && $result->num_rows > 0) {
    echo "<p class='ok'>✅ booking_services table EXISTS</p>";
    $count = $conn->query("SELECT COUNT(*) as cnt FROM booking_services")->fetch_assoc()['cnt'];
    echo "<p>   → Contains $count booking service records</p>";
} else {
    echo "<p class='missing'>❌ booking_services table MISSING - Need to run migration!</p>";
}

// Check for watch_type_id column in bookings
$result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'watch_type_id'");
if ($result && $result->num_rows > 0) {
    echo "<p class='ok'>✅ bookings.watch_type_id column EXISTS</p>";
} else {
    echo "<p class='missing'>❌ bookings.watch_type_id column MISSING - Need to run migration!</p>";
}

echo "<hr>";
echo "<h2>How to Fix:</h2>";
echo "<ol>";
echo "<li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
echo "<li>Select database: <strong>watch_repair_shop</strong></li>";
echo "<li>Click 'SQL' tab</li>";
echo "<li>Copy and paste the contents of: <strong>database/migrate_booking_improvements.sql</strong></li>";
echo "<li>Click 'Go' button</li>";
echo "<li>Refresh this page to verify</li>";
echo "</ol>";

closeDBConnection($conn);
?>
