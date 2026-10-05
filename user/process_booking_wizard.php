<?php
require_once '../config/config.php';
require_login();

$user_id = get_current_user_id();
$error = '';

// Add debugging
error_log("Booking wizard process started for user: " . $user_id);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn = getDBConnection();
        
        // Get form data
        $watch_type_id = intval($_POST['watch_type_id']);
        $problem_description = sanitize_input($_POST['problem_description']);
        $service_ids = isset($_POST['service_ids']) ? $_POST['service_ids'] : [];
        $booking_date = $_POST['booking_date'];
        $timeslot_id = intval($_POST['timeslot_id']);
        $user_address = sanitize_input($_POST['user_address']);
        $payment_method = sanitize_input($_POST['payment_method']);
        
        // Debug form data
        error_log("Form data - Watch Type: $watch_type_id, Services: " . implode(',', $service_ids) . ", Date: $booking_date, Timeslot: $timeslot_id");
        
        // Validate
        if (empty($service_ids)) {
            throw new Exception('Please select at least one service');
        }
        
        // Validate date
        $selected_date = new DateTime($booking_date);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($selected_date < $today) {
            throw new Exception('Cannot book past dates');
        }
        
        if ($selected_date->format('N') == 7) {
            throw new Exception('Sundays are not available for booking');
        }
        
        // Get available technicians count for booking limits
        $technicians_query = "SELECT COUNT(*) as available_technicians FROM technicians WHERE is_available = 1";
        $technicians_result = $conn->query($technicians_query);
        $technicians_data = $technicians_result->fetch_assoc();
        $max_bookings_per_slot = $technicians_data['available_technicians'];
        
        // If no available technicians, set default to 4
        if ($max_bookings_per_slot == 0) {
            $max_bookings_per_slot = 4;
        }
        
        // Check if timeslot has reached maximum capacity
        $stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM bookings WHERE booking_date = ? AND timeslot_id = ? AND status != 'Cancelled' AND status != 'Rejected'");
        $stmt->bind_param("si", $booking_date, $timeslot_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_bookings = $result->fetch_assoc()['booking_count'];
        
        if ($current_bookings >= $max_bookings_per_slot) {
            throw new Exception("This time slot is fully booked ($current_bookings/$max_bookings_per_slot slots taken). Please choose another time.");
        }
        
        // Calculate total cost
        $total_cost = 0;
        foreach ($service_ids as $service_id) {
            $service_id = intval($service_id);
            $stmt = $conn->prepare("SELECT base_price FROM services WHERE service_id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            $service = $stmt->get_result()->fetch_assoc();
            if ($service) {
                $total_cost += $service['base_price'];
            }
        }
        
        $down_payment = calculate_down_payment($total_cost);
        $primary_service_id = intval($service_ids[0]);
        
        // Handle payment proof upload
        if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] != 0) {
            throw new Exception('Please upload payment proof');
        }
        
        $upload_result = upload_file($_FILES['payment_proof'], 'payment_');
        
        if (!$upload_result['success']) {
            throw new Exception($upload_result['error']);
        }
        
        $payment_proof = $upload_result['filename'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert booking
            error_log("Inserting booking for user $user_id, service $primary_service_id, date $booking_date, timeslot $timeslot_id");
            
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, service_id, booking_date, timeslot_id, 
                                    watch_type_id, problem_description, user_address, total_cost, down_payment, 
                                    status, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
            $stmt->bind_param("iisiissdd", $user_id, $primary_service_id, $booking_date, $timeslot_id, 
                             $watch_type_id, $problem_description, $user_address, $total_cost, $down_payment);
            $stmt->execute();
            $booking_id = $conn->insert_id;
            
            error_log("Booking inserted with ID: $booking_id");
            
            // Insert booking services (for multiple services)
            foreach ($service_ids as $service_id) {
                $service_id = intval($service_id);
                $stmt = $conn->prepare("INSERT INTO booking_services (booking_id, service_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $booking_id, $service_id);
                $stmt->execute();
                error_log("Added service $service_id to booking $booking_id");
            }
            
            // Insert payment record
            $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, payment_proof, 
                                    verification_status, created_at) 
                                    VALUES (?, ?, ?, ?, 'Pending', NOW())");
            $stmt->bind_param("idss", $booking_id, $down_payment, $payment_method, $payment_proof);
            $stmt->execute();
            
            error_log("Payment record inserted for booking $booking_id");
            
            $conn->commit();
            
            error_log("Booking transaction committed successfully. Redirecting to my_bookings.php");
            
            // Success - redirect to my bookings
            $_SESSION['booking_success'] = 'Booking submitted successfully! Please wait for admin approval.';
            redirect(SITE_URL . '/user/my_bookings.php?success=1');
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Booking transaction failed: " . $e->getMessage());
            throw $e;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Booking process failed for user $user_id: " . $error);
        $_SESSION['booking_error'] = $error;
        redirect(SITE_URL . '/user/booking_wizard.php');
    }
} else {
    redirect(SITE_URL . '/user/booking_wizard.php');
}
?>
