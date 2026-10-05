<?php
// Simple test to check if booking page loads
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Booking Page Test</h1>";

try {
    require_once 'config/config.php';
    echo "✅ Config loaded successfully<br>";
    
    $conn = getDBConnection();
    echo "✅ Database connected successfully<br>";
    
    // Test technicians count
    $tech_query = "SELECT COUNT(*) as available_technicians FROM technicians WHERE is_available = 1";
    $tech_result = $conn->query($tech_query);
    $tech_data = $tech_result->fetch_assoc();
    $max_bookings = $tech_data['available_technicians'];
    
    if ($max_bookings == 0) {
        $max_bookings = 4;
    }
    
    echo "📊 Max bookings per slot: " . $max_bookings . "<br>";
    
    // Test timeslots
    $slots_query = "SELECT * FROM timeslots WHERE is_active = 1 ORDER BY start_time";
    $slots_result = $conn->query($slots_query);
    
    echo "<h2>Time Slots with Availability:</h2>";
    
    while ($slot = $slots_result->fetch_assoc()) {
        // Get booking count
        $booking_query = "SELECT COUNT(*) as booking_count FROM bookings WHERE booking_date = CURDATE() AND timeslot_id = ? AND status != 'Cancelled' AND status != 'Rejected'";
        $stmt = $conn->prepare($booking_query);
        $stmt->bind_param("i", $slot['timeslot_id']);
        $stmt->execute();
        $booking_result = $stmt->get_result();
        $booking_data = $booking_result->fetch_assoc();
        
        $current_bookings = $booking_data['booking_count'];
        $available_spots = max(0, $max_bookings - $current_bookings);
        
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
        echo "<strong>" . date('g:i A', strtotime($slot['start_time'])) . " - " . date('g:i A', strtotime($slot['end_time'])) . "</strong><br>";
        echo "Available: " . $available_spots . " of " . $max_bookings . " slots<br>";
        echo "Status: " . ($available_spots > 0 ? "✅ Available" : "❌ Full") . "</div>";
        
        $stmt->close();
    }
    
    closeDBConnection($conn);
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString();
}
?>
