<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Booking Page Debug Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .error{color:red;}</style>";

try {
    require_once 'config/config.php';
    echo "<p class='ok'>✅ Config loaded successfully</p>";
    
    // Check if user is logged in
    session_start();
    if (isset($_SESSION['user_id'])) {
        echo "<p class='ok'>✅ User is logged in (ID: " . $_SESSION['user_id'] . ")</p>";
    } else {
        echo "<p class='error'>❌ User is NOT logged in</p>";
        echo "<p>→ <a href='user/login.php'>Login here</a></p>";
    }
    
    $conn = getDBConnection();
    echo "<p class='ok'>✅ Database connected</p>";
    
    // Test services query
    $services = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");
    if ($services) {
        echo "<p class='ok'>✅ Services query OK (" . $services->num_rows . " services)</p>";
    } else {
        echo "<p class='error'>❌ Services query failed: " . $conn->error . "</p>";
    }
    
    // Test timeslots query
    $timeslots = $conn->query("SELECT * FROM timeslots WHERE is_active = 1 ORDER BY start_time");
    if ($timeslots) {
        echo "<p class='ok'>✅ Timeslots query OK (" . $timeslots->num_rows . " slots)</p>";
    } else {
        echo "<p class='error'>❌ Timeslots query failed: " . $conn->error . "</p>";
    }
    
    // Test watch_types table
    $watch_types_check = $conn->query("SHOW TABLES LIKE 'watch_types'");
    if ($watch_types_check && $watch_types_check->num_rows > 0) {
        echo "<p class='ok'>✅ watch_types table exists</p>";
        $watch_types = $conn->query("SELECT * FROM watch_types WHERE is_active = 1 ORDER BY type_name");
        if ($watch_types) {
            echo "<p class='ok'>   → Query OK (" . $watch_types->num_rows . " types)</p>";
        } else {
            echo "<p class='error'>   → Query failed: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='error'>❌ watch_types table does NOT exist</p>";
    }
    
    // Test booking_services table
    $booking_services_check = $conn->query("SHOW TABLES LIKE 'booking_services'");
    if ($booking_services_check && $booking_services_check->num_rows > 0) {
        echo "<p class='ok'>✅ booking_services table exists</p>";
    } else {
        echo "<p class='error'>❌ booking_services table does NOT exist</p>";
    }
    
    // Check bookings table structure
    $columns = $conn->query("SHOW COLUMNS FROM bookings");
    $has_watch_type_id = false;
    while ($col = $columns->fetch_assoc()) {
        if ($col['Field'] == 'watch_type_id') {
            $has_watch_type_id = true;
            break;
        }
    }
    
    if ($has_watch_type_id) {
        echo "<p class='ok'>✅ bookings.watch_type_id column exists</p>";
    } else {
        echo "<p class='error'>❌ bookings.watch_type_id column does NOT exist</p>";
    }
    
    echo "<hr>";
    echo "<h2>Summary:</h2>";
    
    if ($watch_types_check->num_rows > 0 && $booking_services_check->num_rows > 0 && $has_watch_type_id) {
        echo "<p class='ok' style='font-size:1.2em;'><strong>✅ All tables exist! Migration was successful!</strong></p>";
        echo "<p>→ <a href='user/booking.php'>Try booking page now</a></p>";
    } else {
        echo "<p class='error' style='font-size:1.2em;'><strong>❌ Migration incomplete!</strong></p>";
        echo "<p>→ <a href='check_migration.php'>Check migration status</a></p>";
    }
    
    closeDBConnection($conn);
    
} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
