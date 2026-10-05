<?php
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_GET['date'])) {
    echo json_encode(['error' => 'Date is required']);
    exit;
}

$date = $_GET['date'];
$conn = getDBConnection();

// Get all timeslots
$timeslots_query = "SELECT * FROM timeslots WHERE is_active = 1 ORDER BY start_time";
$timeslots = $conn->query($timeslots_query);

// Get booked slots for this date
$booked_query = "SELECT timeslot_id FROM bookings 
                 WHERE booking_date = ? 
                 AND status NOT IN ('Cancelled', 'Rejected')";
$stmt = $conn->prepare($booked_query);
$stmt->bind_param("s", $date);
$stmt->execute();
$booked_result = $stmt->get_result();

$booked_slots = [];
while ($row = $booked_result->fetch_assoc()) {
    $booked_slots[] = $row['timeslot_id'];
}

// Get current time
$current_time = date('H:i:s');
$selected_date = new DateTime($date);
$today = new DateTime();
$today->setTime(0, 0, 0);
$is_today = ($selected_date->format('Y-m-d') === $today->format('Y-m-d'));

$available_slots = [];
while ($slot = $timeslots->fetch_assoc()) {
    $slot_id = $slot['timeslot_id'];
    $is_booked = in_array($slot_id, $booked_slots);
    
    // Check if slot time has passed (only for today)
    $is_expired = false;
    if ($is_today) {
        $is_expired = ($slot['end_time'] <= $current_time);
    }
    
    $available_slots[] = [
        'timeslot_id' => $slot_id,
        'start_time' => $slot['start_time'],
        'end_time' => $slot['end_time'],
        'is_booked' => $is_booked,
        'is_expired' => $is_expired,
        'is_available' => (!$is_booked && !$is_expired)
    ];
}

echo json_encode([
    'success' => true,
    'date' => $date,
    'is_today' => $is_today,
    'current_time' => $current_time,
    'slots' => $available_slots
]);

closeDBConnection($conn);
?>
