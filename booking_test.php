<?php
// Minimal booking page test
session_start();
require_once 'config/config.php';

// Check if user is logged in (for testing, we'll skip this)
// require_login();

$user_id = 1; // Test user ID
$conn = getDBConnection();

// Fetch available technicians count
$technicians_query = "SELECT COUNT(*) as available_technicians FROM technicians WHERE is_available = 1";
$technicians_result = $conn->query($technicians_query);
$technicians_data = $technicians_result->fetch_assoc();
$max_bookings_per_slot = $technicians_data['available_technicians'];

// If no available technicians, set default to 4
if ($max_bookings_per_slot == 0) {
    $max_bookings_per_slot = 4;
}

// Fetch timeslots
$timeslots = $conn->query("SELECT * FROM timeslots WHERE is_active = 1 ORDER BY start_time");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .form-control { width: 100%; padding: 10px; font-size: 16px; }
        .card { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .card-header { font-size: 18px; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Booking Page Test</h1>
    
    <div class="card">
        <div class="card-header">Time Slot Selection Test</div>
        <p><strong>Max bookings per slot:</strong> <?php echo $max_bookings_per_slot; ?></p>
        
        <div class="form-group">
            <label>Select Time Slot:</label>
            <select name="timeslot_id" class="form-control">
                <option value="">Select time slot...</option>
                <?php 
                while ($slot = $timeslots->fetch_assoc()): 
                    // Get current booking count for this timeslot
                    $booking_count_query = "SELECT COUNT(*) as booking_count FROM bookings WHERE booking_date = CURDATE() AND timeslot_id = ? AND status != 'Cancelled' AND status != 'Rejected'";
                    $count_stmt = $conn->prepare($booking_count_query);
                    $count_stmt->bind_param("i", $slot['timeslot_id']);
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $count_data = $count_result->fetch_assoc();
                    $current_bookings = $count_data['booking_count'];
                    $available_spots = max(0, $max_bookings_per_slot - $current_bookings);
                ?>
                    <option value="<?php echo $slot['timeslot_id']; ?>" <?php echo $available_spots <= 0 ? 'disabled' : ''; ?>>
                        <?php echo date('g:i A', strtotime($slot['start_time'])); ?> - <?php echo date('g:i A', strtotime($slot['end_time'])); ?> 
                        (<?php echo $available_spots; ?> of <?php echo $max_bookings_per_slot; ?> slots available)
                    </option>
                <?php 
                endwhile; 
                $count_stmt->close();
                ?>
            </select>
        </div>
        
        <div style="background-color: #f8f9fa; padding: 10px; margin-top: 15px; border-radius: 5px;">
            <strong>Expected Result:</strong> You should see time slots with availability information like "(3 of 3 slots available)"
        </div>
    </div>
    
    <div class="card">
        <h3>Debug Information:</h3>
        <ul>
            <li>Total Technicians: <?php echo $conn->query("SELECT COUNT(*) as total FROM technicians")->fetch_assoc()['total']; ?></li>
            <li>Available Technicians: <?php echo $max_bookings_per_slot; ?></li>
            <li>Active Time Slots: <?php echo $timeslots->num_rows; ?></li>
            <li>Today's Bookings: <?php echo $conn->query("SELECT COUNT(*) as total FROM bookings WHERE booking_date = CURDATE() AND status NOT IN ('Cancelled', 'Rejected')")->fetch_assoc()['total']; ?></li>
        </ul>
    </div>
    
</body>
</html>
<?php closeDBConnection($conn); ?>
