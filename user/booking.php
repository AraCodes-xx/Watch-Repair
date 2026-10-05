<?php
require_once '../config/config.php';
require_login();

$user_id = get_current_user_id();
$error = '';
$success = '';

// Fetch services
$conn = getDBConnection();
$services = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");

// Fetch timeslots
$timeslots = $conn->query("SELECT * FROM timeslots WHERE is_active = 1 ORDER BY start_time");

// Fetch available technicians count
$technicians_query = "SELECT COUNT(*) as available_technicians FROM technicians WHERE is_available = 1";
$technicians_result = $conn->query($technicians_query);
$technicians_data = $technicians_result->fetch_assoc();
$max_bookings_per_slot = $technicians_data['available_technicians'];

// If no available technicians, set default to 4
if ($max_bookings_per_slot == 0) {
    $max_bookings_per_slot = 4;
}

// Fetch watch types (check if table exists first)
$watch_types_result = $conn->query("SHOW TABLES LIKE 'watch_types'");
if ($watch_types_result && $watch_types_result->num_rows > 0) {
    $watch_types = $conn->query("SELECT * FROM watch_types WHERE is_active = 1 ORDER BY type_name");
} else {
    // Table doesn't exist, create empty result
    $watch_types = null;
}

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// If user not found, set default empty array
if (!$user) {
    $user = ['address' => '', 'full_name' => '', 'email' => ''];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $service_ids = isset($_POST['service_ids']) ? $_POST['service_ids'] : [];
        $booking_date = $_POST['booking_date'] ?? '';
        $timeslot_id = intval($_POST['timeslot_id'] ?? 0);
        $watch_type_id = intval($_POST['watch_type_id'] ?? 0);
        $payment_method = sanitize_input($_POST['payment_method'] ?? '');
        $problem_description = sanitize_input($_POST['problem_description'] ?? '');
        $user_address = sanitize_input($_POST['user_address'] ?? '');
    
    // Validate date
    $selected_date = new DateTime($booking_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    // Validate services selected
    if (empty($service_ids)) {
        $error = 'Please select at least one service';
    } elseif ($selected_date < $today) {
        $error = 'Cannot book past dates';
    } elseif ($selected_date->format('N') == 7) { // Sunday
        $error = 'Sundays are not available for booking';
    } else {
        // Check if timeslot has reached maximum bookings
        $stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM bookings WHERE booking_date = ? AND timeslot_id = ? AND status != 'Cancelled' AND status != 'Rejected'");
        $stmt->bind_param("si", $booking_date, $timeslot_id);
        $stmt->execute();
        $booking_count_result = $stmt->get_result();
        $booking_count_data = $booking_count_result->fetch_assoc();
        
        if ($booking_count_data['booking_count'] >= $max_bookings_per_slot) {
            $error = 'This time slot is fully booked. Only ' . $max_bookings_per_slot . ' bookings allowed per time slot. Please choose another time.';
        } else {
            // Calculate total cost from multiple services
            $total_cost = 0;
            $service_prices = [];
            
            foreach ($service_ids as $service_id) {
                $service_id = intval($service_id);
                $stmt = $conn->prepare("SELECT service_id, base_price FROM services WHERE service_id = ?");
                $stmt->bind_param("i", $service_id);
                $stmt->execute();
                $service = $stmt->get_result()->fetch_assoc();
                if ($service) {
                    $total_cost += $service['base_price'];
                    $service_prices[$service['service_id']] = $service['base_price'];
                }
            }
            
            $down_payment = calculate_down_payment($total_cost);
            $primary_service_id = intval($service_ids[0]); // Use first service as primary
            
            // Handle payment proof upload
            if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == 0) {
                $upload_result = upload_file($_FILES['payment_proof'], 'payment_');
                
                if ($upload_result['success']) {
                    // Insert booking
                    $stmt = $conn->prepare("INSERT INTO bookings (user_id, service_id, booking_date, timeslot_id, watch_type_id, problem_description, user_address, total_cost, down_payment, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                    $stmt->bind_param("iisisssdd", $user_id, $primary_service_id, $booking_date, $timeslot_id, $watch_type_id, $problem_description, $user_address, $total_cost, $down_payment);
                    
                    if ($stmt->execute()) {
                        $booking_id = $conn->insert_id;
                        
                        // Insert all services into booking_services table (if table exists)
                        $table_check = $conn->query("SHOW TABLES LIKE 'booking_services'");
                        if ($table_check && $table_check->num_rows > 0) {
                            foreach ($service_prices as $sid => $price) {
                                $stmt = $conn->prepare("INSERT INTO booking_services (booking_id, service_id, service_price) VALUES (?, ?, ?)");
                                $stmt->bind_param("iid", $booking_id, $sid, $price);
                                $stmt->execute();
                            }
                        }
                        
                        // Insert payment record
                        $payment_proof = $upload_result['filename'];
                        $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_proof, payment_method, verification_status) VALUES (?, ?, ?, ?, 'Pending')");
                        $stmt->bind_param("idss", $booking_id, $down_payment, $payment_proof, $payment_method);
                        $stmt->execute();
                        
                        // Send notification
                        send_notification($user_id, 'Booking Submitted', 'Your booking has been submitted and is pending admin approval.', $booking_id);
                        
                        redirect(SITE_URL . '/user/my_bookings.php?success=1');
                    } else {
                        $error = 'Failed to create booking. Please try again.';
                    }
                } else {
                    $error = $upload_result['message'];
                }
            } else {
                $error = 'Please upload proof of payment';
            }
        }
    }
    } catch (Exception $e) {
        $error = 'Booking error: ' . $e->getMessage();
        error_log('Booking error: ' . $e->getMessage());
    }
}

if (isset($stmt)) {
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .calendar-container {
            background-color: var(--bg-card);
            border-radius: 1rem;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }
        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 0.5rem;
            color: var(--text-secondary);
        }
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            background-color: var(--bg-darker);
        }
        .calendar-day:hover:not(.disabled):not(.past) {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }
        .calendar-day.selected {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        .calendar-day.disabled, .calendar-day.past {
            opacity: 0.3;
            cursor: not-allowed;
        }
        .calendar-day.today {
            border-color: var(--primary-color);
            border-width: 2px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-container">
                <a href="../index.php" class="navbar-brand">⌚ <?php echo SITE_NAME; ?></a>
                <ul class="navbar-menu">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="booking.php" class="active">Book Service</a></li>
                    <li><a href="my_bookings.php">My Bookings</a></li>
                    <li><a href="notifications.php">Notifications</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem; margin-bottom: 3rem;">
        <h1>Book a Service</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Schedule your watch repair appointment</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" id="bookingForm">
            <div class="row">
                <div class="col-6">
                    <!-- Service Selection -->
                    <div class="card mb-3">
                        <div class="card-header">1. Select Services (Multiple)</div>
                        <div class="card-body">
                            <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem;">Select one or more services you need</p>
                            <div class="form-group">
                                <?php 
                                $services->data_seek(0);
                                while ($service = $services->fetch_assoc()): 
                                ?>
                                    <label style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; margin-bottom: 0.5rem; cursor: pointer; transition: all 0.2s;">
                                        <input type="checkbox" name="service_ids[]" value="<?php echo $service['service_id']; ?>" class="service-checkbox" data-price="<?php echo $service['base_price']; ?>" onchange="calculateTotal()">
                                        <div style="flex: 1;">
                                            <strong><?php echo htmlspecialchars($service['service_name']); ?></strong>
                                            <div style="font-size: 0.875rem; color: var(--text-secondary);"><?php echo htmlspecialchars($service['description']); ?></div>
                                        </div>
                                        <div style="font-weight: bold; color: var(--primary-color);">
                                            <?php echo format_currency($service['base_price']); ?>
                                        </div>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                            <div style="background-color: var(--bg-darker); padding: 1rem; border-radius: 0.5rem; margin-top: 1rem;">
                                <div class="d-flex justify-between">
                                    <span><strong>Selected Total:</strong></span>
                                    <strong id="selectedTotal" style="color: var(--primary-color); font-size: 1.25rem;">₱0.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Date Selection -->
                    <div class="card mb-3">
                        <div class="card-header">2. Select Date</div>
                        <div class="card-body">
                            <div class="calendar-container">
                                <div class="calendar-header">
                                    <button type="button" id="prevMonth" class="btn btn-sm btn-secondary">←</button>
                                    <h4 id="currentMonth"></h4>
                                    <button type="button" id="nextMonth" class="btn btn-sm btn-secondary">→</button>
                                </div>
                                <div class="calendar-grid">
                                    <div class="calendar-day-header">Sun</div>
                                    <div class="calendar-day-header">Mon</div>
                                    <div class="calendar-day-header">Tue</div>
                                    <div class="calendar-day-header">Wed</div>
                                    <div class="calendar-day-header">Thu</div>
                                    <div class="calendar-day-header">Fri</div>
                                    <div class="calendar-day-header">Sat</div>
                                </div>
                                <div class="calendar-grid" id="calendarDays"></div>
                            </div>
                            <input type="hidden" name="booking_date" id="bookingDate" required>
                        </div>
                    </div>

                    <!-- Time Slot Selection -->
                    <div class="card mb-3">
                        <div class="card-header">3. Select Time Slot</div>
                        <div class="card-body">
                            <!-- Availability Summary -->
                            <div style="background: #e3f2fd; border: 1px solid #2196f3; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                                <h5 style="margin: 0 0 10px 0; color: #1976d2;">📊 Booking Availability</h5>
                                <p style="margin: 0; font-size: 14px;">
                                    <strong>Available Technicians:</strong> <?php echo $max_bookings_per_slot; ?> | 
                                    <strong>Max Bookings Per Slot:</strong> <?php echo $max_bookings_per_slot; ?>
                                </p>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Choose your preferred time slot:</strong></label>
                                <select name="timeslot_id" id="timeslotSelect" class="form-control" required onchange="updateTimeslotDisplay()" style="font-size: 16px; padding: 12px;">
                                    <option value="">-- Select a time slot --</option>
                                    <?php 
                                    $timeslots->data_seek(0);
                                    while ($slot = $timeslots->fetch_assoc()): 
                                        // Get current booking count for this timeslot (for today by default)
                                        $booking_count_query = "SELECT COUNT(*) as booking_count FROM bookings WHERE booking_date = CURDATE() AND timeslot_id = ? AND status != 'Cancelled' AND status != 'Rejected'";
                                        $count_stmt = $conn->prepare($booking_count_query);
                                        $count_stmt->bind_param("i", $slot['timeslot_id']);
                                        $count_stmt->execute();
                                        $count_result = $count_stmt->get_result();
                                        $count_data = $count_result->fetch_assoc();
                                        $current_bookings = $count_data['booking_count'];
                                        $available_spots = max(0, $max_bookings_per_slot - $current_bookings);
                                    ?>
                                        <option value="<?php echo $slot['timeslot_id']; ?>" 
                                                data-start-time="<?php echo $slot['start_time']; ?>" 
                                                data-end-time="<?php echo $slot['end_time']; ?>"
                                                data-available-spots="<?php echo $available_spots; ?>"
                                                data-max-slots="<?php echo $max_bookings_per_slot; ?>"
                                                <?php echo $available_spots <= 0 ? 'disabled style="color: #999; background-color: #f5f5f5;"' : ''; ?>>
                                            <?php echo date('g:i A', strtotime($slot['start_time'])); ?> - <?php echo date('g:i A', strtotime($slot['end_time'])); ?> 
                                            (<?php echo $available_spots; ?> of <?php echo $max_bookings_per_slot; ?> slots available)
                                        </option>
                                    <?php 
                                    endwhile; 
                                    $count_stmt->close();
                                    ?>
                                </select>
                                
                                <!-- Availability Status Display -->
                                <div id="timeslotAvailability" style="margin-top: 15px; padding: 10px; border-radius: 5px; display: none; font-size: 14px;">
                                    <strong>Availability Status:</strong>
                                    <div id="availabilityText"></div>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin-top: 15px;">
                                <h6 style="margin: 0 0 10px 0; color: #495057;">ℹ️ How Booking Limits Work:</h6>
                                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #6c757d;">
                                    <li>Each time slot allows up to <strong><?php echo $max_bookings_per_slot; ?> bookings</strong> based on available technicians</li>
                                    <li>Available spots decrease as other users make bookings</li>
                                    <li>Fully booked slots are automatically disabled</li>
                                    <li>Past time slots for today are also disabled</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6">
                    <!-- Watch Type -->
                    <div class="card mb-3">
                        <div class="card-header">4. Watch Type</div>
                        <div class="card-body">
                            <div class="form-group">
                                <select name="watch_type_id" class="form-control" required>
                                    <option value="">Select watch type...</option>
                                    <?php 
                                    if ($watch_types && $watch_types->num_rows > 0):
                                        $watch_types->data_seek(0);
                                        while ($type = $watch_types->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $type['watch_type_id']; ?>">
                                            <?php echo htmlspecialchars($type['type_name']); ?>
                                        </option>
                                    <?php 
                                        endwhile;
                                    else:
                                        // Fallback to hardcoded types if table doesn't exist
                                    ?>
                                        <option value="0">Mechanical Watch</option>
                                        <option value="0">Automatic Watch</option>
                                        <option value="0">Quartz Watch</option>
                                        <option value="0">Digital Watch</option>
                                        <option value="0">Smartwatch</option>
                                        <option value="0">Other</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Problem Description -->
                    <div class="card mb-3">
                        <div class="card-header">5. Describe the Problem</div>
                        <div class="card-body">
                            <div class="form-group">
                                <textarea name="problem_description" class="form-control" rows="4" placeholder="Describe what's wrong with your watch..." required></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="card mb-3">
                        <div class="card-header">6. Service Address</div>
                        <div class="card-body">
                            <div class="form-group">
                                <textarea name="user_address" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="card mb-3">
                        <div class="card-header">7. Payment Information</div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" id="paymentMethod" class="form-control" onchange="updatePaymentInstructions()" required>
                                    <option value="">Select payment method...</option>
                                    <option value="GCash">GCash</option>
                                    <option value="PayMaya">PayMaya</option>
                                    <option value="PayPal">PayPal</option>
                                </select>
                            </div>
                            
                            <div id="paymentInstructions" class="alert alert-info" style="display: none; margin-top: 1rem;">
                                <!-- Payment instructions will be shown here -->
                            </div>
                            
                            <div id="paymentSummary" style="background-color: var(--bg-darker); padding: 1rem; border-radius: 0.5rem; margin: 1rem 0;">
                                <div class="d-flex justify-between mb-2">
                                    <span>Total Cost:</span>
                                    <strong id="totalCost">₱0.00</strong>
                                </div>
                                <div class="d-flex justify-between" style="color: var(--primary-color);">
                                    <span>20% Down Payment:</span>
                                    <strong id="downPayment">₱0.00</strong>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Upload Payment Proof (Screenshot)</label>
                                <input type="file" name="payment_proof" class="form-control" accept="image/*" required>
                                <small style="color: var(--text-secondary);">Max file size: 5MB. Formats: JPG, PNG, GIF</small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Booking</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Calendar functionality
        let currentDate = new Date();
        let selectedDate = null;

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            document.getElementById('currentMonth').textContent = 
                currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            let calendarHTML = '';
            
            // Empty cells before first day
            for (let i = 0; i < firstDay; i++) {
                calendarHTML += '<div></div>';
            }
            
            // Days of month
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                date.setHours(0, 0, 0, 0);
                // Format date as YYYY-MM-DD in local timezone (not UTC)
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const isSunday = date.getDay() === 0;
                const isPast = date < today;
                const isToday = date.getTime() === today.getTime();
                const isSelected = selectedDate === dateStr;
                
                let classes = 'calendar-day';
                if (isSunday) classes += ' disabled';
                if (isPast) classes += ' past';
                if (isToday) classes += ' today';
                if (isSelected) classes += ' selected';
                
                calendarHTML += `<div class="${classes}" data-date="${dateStr}" onclick="selectDate('${dateStr}', this)">${day}</div>`;
            }
            
            document.getElementById('calendarDays').innerHTML = calendarHTML;
        }

        function selectDate(dateStr, element) {
            if (element.classList.contains('disabled') || element.classList.contains('past')) {
                return;
            }
            
            document.querySelectorAll('.calendar-day').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            selectedDate = dateStr;
            document.getElementById('bookingDate').value = dateStr;
            
            // Validate timeslots when date is selected
            validateTimeslots(dateStr);
        }
        
        // Validate and disable past timeslots for today
        function validateTimeslots(dateStr) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const selectedDate = new Date(dateStr);
            selectedDate.setHours(0, 0, 0, 0);
            
            // Check availability for selected date
            if (dateStr) {
                // Fetch booking counts for selected date via AJAX
                fetch('<?php echo SITE_URL; ?>/api/get_timeslot_availability.php?date=' + dateStr)
                    .then(response => response.json())
                    .then(data => {
                        const timeslotSelect = document.getElementById('timeslotSelect');
                        const options = timeslotSelect.querySelectorAll('option:not([value=""])');
                        
                        options.forEach(option => {
                            const timeslotId = option.value;
                            const availability = data[timeslotId];
                            
                            if (availability) {
                                const availableSpots = availability.available_spots;
                                const maxSlots = availability.max_slots;
                                
                                // Update option text and availability
                                const startTime = option.dataset.startTime;
                                const endTime = option.dataset.endTime;
                                option.textContent = `${formatTime(startTime)} - ${formatTime(endTime)} (${availableSpots} of ${maxSlots} slots available)`;
                                
                                // Enable/disable based on availability
                                if (availableSpots <= 0) {
                                    option.disabled = true;
                                } else {
                                    option.disabled = false;
                                }
                                
                                // Update data attributes
                                option.dataset.availableSpots = availableSpots;
                                option.dataset.maxSlots = maxSlots;
                            }
                        });
                        
                        // Update availability display
                        updateTimeslotDisplay();
                    })
                    .catch(error => console.error('Error fetching availability:', error));
            }
            
            // Only disable past slots if booking for today
            if (selectedDate.getTime() === today.getTime()) {
                const currentHour = new Date().getHours();
                const currentMinute = new Date().getMinutes();
                const currentTimeInMinutes = currentHour * 60 + currentMinute;
                
                const options = document.querySelectorAll('#timeslotSelect option:not([value=""])');
                options.forEach(option => {
                    // Check END time, not start time
                    const endTime = option.dataset.endTime; // Format: HH:MM:SS
                    const [hours, minutes] = endTime.split(':');
                    const slotEndTimeInMinutes = parseInt(hours) * 60 + parseInt(minutes);
                    
                    // Disable if slot end time has passed
                    if (slotEndTimeInMinutes <= currentTimeInMinutes) {
                        option.disabled = true;
                    }
                });
            }
        }
        
        // Format time function
        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const date = new Date();
            date.setHours(parseInt(hours), parseInt(minutes));
            return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        }
        
        // Update timeslot availability display
        function updateTimeslotDisplay() {
            const timeslotSelect = document.getElementById('timeslotSelect');
            const selectedOption = timeslotSelect.options[timeslotSelect.selectedIndex];
            const availabilityDiv = document.getElementById('timeslotAvailability');
            const availabilityText = document.getElementById('availabilityText');
            
            if (selectedOption && selectedOption.value) {
                const availableSpots = parseInt(selectedOption.dataset.availableSpots) || 0;
                const maxSlots = parseInt(selectedOption.dataset.maxSlots) || 0;
                
                let statusHtml = '';
                let bgColor = '';
                let textColor = '';
                
                if (availableSpots > 0) {
                    statusHtml = `✅ <strong>${availableSpots}</strong> slots available out of <strong>${maxSlots}</strong> total`;
                    bgColor = '#d4edda';
                    textColor = '#155724';
                } else {
                    statusHtml = `❌ This time slot is <strong>fully booked</strong> (${maxSlots}/${maxSlots})`;
                    bgColor = '#f8d7da';
                    textColor = '#721c24';
                }
                
                availabilityText.innerHTML = statusHtml;
                availabilityDiv.style.backgroundColor = bgColor;
                availabilityDiv.style.color = textColor;
                availabilityDiv.style.border = `1px solid ${textColor}`;
                availabilityDiv.style.display = 'block';
            } else {
                availabilityDiv.style.display = 'none';
            }
        }

        document.getElementById('prevMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        });

        // Calculate total from selected services
        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.service-checkbox:checked').forEach(checkbox => {
                total += parseFloat(checkbox.dataset.price);
            });
            
            const downPayment = total * 0.2;
            
            document.getElementById('selectedTotal').textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('totalCost').textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('downPayment').textContent = '₱' + downPayment.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        
        // Update payment instructions based on selected method
        function updatePaymentInstructions() {
            const method = document.getElementById('paymentMethod').value;
            const instructionsDiv = document.getElementById('paymentInstructions');
            
            if (!method) {
                instructionsDiv.style.display = 'none';
                return;
            }
            
            let instructions = '';
            switch(method) {
                case 'GCash':
                    instructions = '<strong>GCash Payment Instructions:</strong><br>1. Send payment to: <strong>0917-123-4567</strong><br>2. Account Name: Watch Repair Shop<br>3. Upload screenshot as proof';
                    break;
                case 'PayMaya':
                    instructions = '<strong>PayMaya Payment Instructions:</strong><br>1. Send payment to: <strong>0918-123-4567</strong><br>2. Account Name: Watch Repair Shop<br>3. Upload screenshot as proof';
                    break;
                case 'PayPal':
                    instructions = '<strong>PayPal Payment Instructions:</strong><br>1. Send payment to: <strong>payments@watchrepair.com</strong><br>2. Include booking reference in notes<br>3. Upload screenshot as proof';
                    break;
            }
            
            instructionsDiv.innerHTML = instructions;
            instructionsDiv.style.display = 'block';
        }

        // Initialize calendar
        renderCalendar();

        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const selectedServices = document.querySelectorAll('.service-checkbox:checked').length;
            
            if (selectedServices === 0) {
                e.preventDefault();
                alert('Please select at least one service');
                return false;
            }
            
            if (!selectedDate) {
                e.preventDefault();
                alert('Please select a booking date');
                return false;
            }
            
            const timeslotSelect = document.getElementById('timeslotSelect');
            if (!timeslotSelect.value) {
                e.preventDefault();
                alert('Please select a time slot');
                return false;
            }
            
            // Check if selected timeslot is available
            const selectedOption = timeslotSelect.options[timeslotSelect.selectedIndex];
            if (selectedOption.disabled) {
                e.preventDefault();
                alert('This time slot is not available. Please select another time slot.');
                return false;
            }
        });
    </script>
</body>
</html>
<?php closeDBConnection($conn); ?>
