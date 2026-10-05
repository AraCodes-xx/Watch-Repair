<?php
// Debug file to test availability API for different users
session_start();
require_once '../config/config.php';

echo "<h2>Booking Availability Debug Test</h2>";

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    echo "<p><strong>Current User ID:</strong> " . $_SESSION['user_id'] . "</p>";
    
    // Get user info
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT user_id, full_name, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        echo "<p><strong>User Name:</strong> " . htmlspecialchars($user['full_name']) . "</p>";
        echo "<p><strong>User Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
    }
} else {
    echo "<p style='color: red;'><strong>No user logged in!</strong></p>";
}

// Test the availability API
echo "<h3>Testing Availability API</h3>";
$test_date = date('Y-m-d');
echo "<p><strong>Testing date:</strong> " . $test_date . "</p>";

// Make a request to our API
$api_url = "http://localhost/repair_shop/api/get_timeslot_availability.php?date=" . $test_date;
echo "<p><strong>API URL:</strong> " . $api_url . "</p>";

// Use cURL to test the API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Response Code:</strong> " . $http_code . "</p>";
echo "<p><strong>API Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test direct database query
echo "<h3>Direct Database Test</h3>";
try {
    $conn = getDBConnection();
    
    // Test technicians count
    $tech_result = $conn->query("SELECT COUNT(*) as count FROM technicians WHERE is_available = 1");
    $tech_count = $tech_result->fetch_assoc()['count'];
    echo "<p><strong>Available Technicians:</strong> " . $tech_count . "</p>";
    
    // Test timeslots
    $slots_result = $conn->query("SELECT COUNT(*) as count FROM timeslots WHERE is_active = 1");
    $slots_count = $slots_result->fetch_assoc()['count'];
    echo "<p><strong>Active Timeslots:</strong> " . $slots_count . "</p>";
    
    // Test bookings for today
    $bookings_result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_date = CURDATE() AND status NOT IN ('Cancelled', 'Rejected')");
    $bookings_count = $bookings_result->fetch_assoc()['count'];
    echo "<p><strong>Today's Bookings:</strong> " . $bookings_count . "</p>";
    
    closeDBConnection($conn);
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Database Error:</strong> " . $e->getMessage() . "</p>";
}

// JavaScript test
echo "<h3>JavaScript API Test</h3>";
echo "<button onclick='testAPI()'>Test API with JavaScript</button>";
echo "<div id='jsResult'></div>";

echo "<script>
function testAPI() {
    const resultDiv = document.getElementById('jsResult');
    resultDiv.innerHTML = 'Testing...';
    
    fetch('../api/get_timeslot_availability.php?date=" . $test_date . "')
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API Response:', data);
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.innerHTML = '<p style=\"color: red;\">Error: ' + error.message + '</p>';
        });
}
</script>";
?>
