<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Enable error logging for debugging
error_log("Availability API called with date: " . ($_GET['date'] ?? 'none'));

// Get date from query parameter
$date = $_GET['date'] ?? '';

if (empty($date)) {
    error_log("Availability API: No date parameter provided");
    echo json_encode(['error' => 'Date parameter is required']);
    exit;
}

// Validate date format
if (!DateTime::createFromFormat('Y-m-d', $date)) {
    error_log("Availability API: Invalid date format: " . $date);
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

try {
    $conn = getDBConnection();
    error_log("Availability API: Database connection successful");
    
    // Get available technicians count
    $technicians_query = "SELECT COUNT(*) as available_technicians FROM technicians WHERE is_available = 1";
    $technicians_result = $conn->query($technicians_query);
    $technicians_data = $technicians_result->fetch_assoc();
    $max_bookings_per_slot = $technicians_data['available_technicians'];
    
    error_log("Availability API: Available technicians: " . $max_bookings_per_slot);
    
    // If no available technicians, set default to 4
    if ($max_bookings_per_slot == 0) {
        $max_bookings_per_slot = 4;
        error_log("Availability API: No technicians found, using default: 4");
    }
    
    // Get all active timeslots
    $timeslots_query = "SELECT timeslot_id, start_time, end_time FROM timeslots WHERE is_active = 1 ORDER BY start_time";
    $timeslots_result = $conn->query($timeslots_query);
    
    error_log("Availability API: Found " . $timeslots_result->num_rows . " active timeslots");
    
    $availability = [];
    
    while ($slot = $timeslots_result->fetch_assoc()) {
        $timeslot_id = $slot['timeslot_id'];
        
        // Count existing bookings for this date and timeslot
        $booking_count_query = "SELECT COUNT(*) as booking_count FROM bookings WHERE booking_date = ? AND timeslot_id = ? AND status != 'Cancelled' AND status != 'Rejected'";
        $stmt = $conn->prepare($booking_count_query);
        $stmt->bind_param("si", $date, $timeslot_id);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        
        $current_bookings = $count_data['booking_count'];
        $available_spots = max(0, $max_bookings_per_slot - $current_bookings);
        
        error_log("Availability API: Slot $timeslot_id - Current bookings: $current_bookings, Available: $available_spots");
        
        $availability[$timeslot_id] = [
            'timeslot_id' => $timeslot_id,
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time'],
            'current_bookings' => $current_bookings,
            'max_slots' => $max_bookings_per_slot,
            'available_spots' => $available_spots,
            'is_available' => $available_spots > 0
        ];
        
        $stmt->close();
    }
    
    closeDBConnection($conn);
    
    error_log("Availability API: Returning data for " . count($availability) . " slots");
    echo json_encode($availability);
    
} catch (Exception $e) {
    error_log("Availability API Error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
