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

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_id = intval($_POST['service_id']);
    $booking_date = $_POST['booking_date'];
    $timeslot_id = intval($_POST['timeslot_id']);
    $watch_type = sanitize_input($_POST['watch_type']);
    $problem_description = sanitize_input($_POST['problem_description']);
    $user_address = sanitize_input($_POST['user_address']);
    
    // Validate date
    $selected_date = new DateTime($booking_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($selected_date < $today) {
        $error = 'Cannot book past dates';
    } elseif ($selected_date->format('N') == 7) { // Sunday
        $error = 'Sundays are not available for booking';
    } else {
        // Check if timeslot is already booked
        $stmt = $conn->prepare("SELECT booking_id FROM bookings WHERE booking_date = ? AND timeslot_id = ? AND status != 'Cancelled' AND status != 'Rejected'");
        $stmt->bind_param("si", $booking_date, $timeslot_id);
        $stmt->execute();
        $existing = $stmt->get_result();
        
        if ($existing->num_rows > 0) {
            $error = 'This time slot is already booked. Please choose another time.';
        } else {
            // Get service price
            $stmt = $conn->prepare("SELECT base_price FROM services WHERE service_id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            $service = $stmt->get_result()->fetch_assoc();
            $total_cost = $service['base_price'];
            $down_payment = calculate_down_payment($total_cost);
            
            // Handle payment proof upload
            if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == 0) {
                $upload_result = upload_file($_FILES['payment_proof'], 'payment_');
                
                if ($upload_result['success']) {
                    // Insert booking
                    $stmt = $conn->prepare("INSERT INTO bookings (user_id, service_id, booking_date, timeslot_id, watch_type, problem_description, user_address, total_cost, down_payment, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                    $stmt->bind_param("isissssdd", $user_id, $service_id, $booking_date, $timeslot_id, $watch_type, $problem_description, $user_address, $total_cost, $down_payment);
                    
                    if ($stmt->execute()) {
                        $booking_id = $conn->insert_id;
                        
                        // Insert payment record
                        $payment_proof = $upload_result['filename'];
                        $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_proof, verification_status) VALUES (?, ?, ?, 'Pending')");
                        $stmt->bind_param("ids", $booking_id, $down_payment, $payment_proof);
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
}

$stmt->close();
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
        .timeslot-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .timeslot-option {
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        .timeslot-option:hover {
            border-color: var(--primary-color);
            background-color: var(--bg-darker);
        }
        .timeslot-option.selected {
            border-color: var(--primary-color);
            background-color: var(--primary-color);
            color: white;
        }
        .timeslot-option input[type="radio"] {
            display: none;
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
                        <div class="card-header">1. Select Service</div>
                        <div class="card-body">
                            <div class="form-group">
                                <select name="service_id" id="serviceSelect" class="form-control" required>
                                    <option value="">Choose a service...</option>
                                    <?php while ($service = $services->fetch_assoc()): ?>
                                        <option value="<?php echo $service['service_id']; ?>" data-price="<?php echo $service['base_price']; ?>">
                                            <?php echo htmlspecialchars($service['service_name']); ?> - <?php echo format_currency($service['base_price']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
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
                            <div class="timeslot-grid">
                                <?php 
                                $timeslots->data_seek(0);
                                while ($slot = $timeslots->fetch_assoc()): 
                                ?>
                                    <label class="timeslot-option">
                                        <input type="radio" name="timeslot_id" value="<?php echo $slot['timeslot_id']; ?>" required>
                                        <div>
                                            <strong><?php echo date('g:i A', strtotime($slot['start_time'])); ?></strong>
                                            <br>to<br>
                                            <strong><?php echo date('g:i A', strtotime($slot['end_time'])); ?></strong>
                                        </div>
                                    </label>
                                <?php endwhile; ?>
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
                                <select name="watch_type" class="form-control" required>
                                    <option value="">Select watch type...</option>
                                    <option value="Mechanical">Mechanical Watch</option>
                                    <option value="Automatic">Automatic Watch</option>
                                    <option value="Quartz">Quartz Watch</option>
                                    <option value="Digital">Digital Watch</option>
                                    <option value="Smartwatch">Smartwatch</option>
                                    <option value="Chronograph">Chronograph</option>
                                    <option value="Diving">Diving Watch</option>
                                    <option value="Pocket">Pocket Watch</option>
                                    <option value="Luxury">Luxury/Designer Watch</option>
                                    <option value="Vintage">Vintage Watch</option>
                                    <option value="Other">Other</option>
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
                                <textarea name="user_address" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="card mb-3">
                        <div class="card-header">7. Payment Information</div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <strong>Payment Instructions:</strong><br>
                                1. Pay 20% down payment via GCash to: <strong>0917-123-4567</strong><br>
                                2. Upload screenshot of payment proof below
                            </div>
                            
                            <div id="paymentSummary" style="background-color: var(--bg-darker); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
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
                const dateStr = date.toISOString().split('T')[0];
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
        }

        document.getElementById('prevMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        });

        // Service selection and price calculation
        document.getElementById('serviceSelect').addEventListener('change', function() {
            const price = parseFloat(this.options[this.selectedIndex].dataset.price) || 0;
            const downPayment = price * 0.2;
            
            document.getElementById('totalCost').textContent = '₱' + price.toFixed(2);
            document.getElementById('downPayment').textContent = '₱' + downPayment.toFixed(2);
        });

        // Timeslot selection
        document.querySelectorAll('.timeslot-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.timeslot-option').forEach(el => el.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Initialize calendar
        renderCalendar();

        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            if (!selectedDate) {
                e.preventDefault();
                alert('Please select a booking date');
                return false;
            }
        });
    </script>
</body>
</html>
<?php closeDBConnection($conn); ?>
