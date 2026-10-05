<?php
require_once '../config/config.php';
require_once '../config/payment_accounts.php';
require_login();

$user_id = get_current_user_id();
$conn = getDBConnection();

// Fetch available technicians count for booking limits
$technicians_query = "SELECT COUNT(*) as available_technicians FROM technicians WHERE is_available = 1";
$technicians_result = $conn->query($technicians_query);
$technicians_data = $technicians_result->fetch_assoc();
$max_bookings_per_slot = $technicians_data['available_technicians'];

// If no available technicians, set default to 4
if ($max_bookings_per_slot == 0) {
    $max_bookings_per_slot = 4;
}

// Fetch data for the form
$watch_types = $conn->query("SELECT * FROM watch_types WHERE is_active = 1 ORDER BY type_name");
$services = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");
$timeslots = $conn->query("SELECT * FROM timeslots WHERE is_active = 1 ORDER BY start_time");

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get payment accounts for JavaScript
$payment_accounts_json = get_payment_accounts_json();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/logout-confirm.js"></script>
    <style>
        .wizard-container {
            max-width: 800px;
            margin: 3rem auto;
        }
        
        .wizard-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3rem;
            position: relative;
        }
        
        .wizard-progress::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--border-color);
            z-index: 0;
        }
        
        .wizard-progress-line {
            position: absolute;
            top: 25px;
            left: 0;
            height: 4px;
            background: var(--primary-color);
            z-index: 1;
            transition: width 0.3s ease;
        }
        
        .wizard-step-indicator {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .wizard-step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--bg-card);
            border: 4px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .wizard-step-indicator.active .wizard-step-circle {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .wizard-step-indicator.completed .wizard-step-circle {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }
        
        .wizard-step-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .wizard-step-indicator.active .wizard-step-label {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .wizard-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 2rem;
        }
        
        .wizard-step {
            display: none;
        }
        
        .wizard-step.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .wizard-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            gap: 1rem;
        }
        
        .watch-type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .watch-type-card {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .watch-type-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
        }
        
        .watch-type-card.selected {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
        }
        
        .watch-type-card input[type="radio"] {
            display: none;
        }
        
        .watch-type-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        
        .service-checkbox {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .service-checkbox:hover {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.05);
        }
        
        .service-checkbox input[type="checkbox"] {
            margin-right: 1rem;
            width: 20px;
            height: 20px;
        }
        
        .service-info {
            flex: 1;
        }
        
        .service-price {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .summary-section {
            background: var(--bg-darker);
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-container">
                <a href="../index.php" class="navbar-brand">⌚ <?php echo SITE_NAME; ?></a>
                <ul class="navbar-menu">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="booking_wizard.php" class="active">Book Service</a></li>
                    <li><a href="my_bookings.php">My Bookings</a></li>
                    <li><a href="notifications.php">Notifications</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container wizard-container">
        <h1 class="text-center">Book a Service</h1>
        <p class="text-center" style="color: var(--text-secondary); margin-bottom: 3rem;">
            Complete the steps below to book your watch repair service
        </p>
        
        <!-- Error/Success Messages -->
        <?php if (isset($_SESSION['booking_error'])): ?>
            <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <strong>❌ Booking Error:</strong> <?php echo htmlspecialchars($_SESSION['booking_error']); ?>
            </div>
            <?php unset($_SESSION['booking_error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['booking_success'])): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <strong>✅ Success:</strong> <?php echo htmlspecialchars($_SESSION['booking_success']); ?>
            </div>
            <?php unset($_SESSION['booking_success']); ?>
        <?php endif; ?>

        <!-- Progress Indicator -->
        <div class="wizard-progress">
            <div class="wizard-progress-line" id="progressLine" style="width: 0%"></div>
            <div class="wizard-step-indicator active" data-step="1">
                <div class="wizard-step-circle">1</div>
                <div class="wizard-step-label">Watch Type</div>
            </div>
            <div class="wizard-step-indicator" data-step="2">
                <div class="wizard-step-circle">2</div>
                <div class="wizard-step-label">Problem & Services</div>
            </div>
            <div class="wizard-step-indicator" data-step="3">
                <div class="wizard-step-circle">3</div>
                <div class="wizard-step-label">Date & Time</div>
            </div>
            <div class="wizard-step-indicator" data-step="4">
                <div class="wizard-step-circle">4</div>
                <div class="wizard-step-label">Service Address</div>
            </div>
            <div class="wizard-step-indicator" data-step="5">
                <div class="wizard-step-circle">5</div>
                <div class="wizard-step-label">Summary & Payment</div>
            </div>
        </div>

        <!-- Wizard Form -->
        <form id="bookingWizardForm" method="POST" action="process_booking_wizard.php" enctype="multipart/form-data">
            <div class="wizard-content">
                <!-- Step 1: Watch Type -->
                <div class="wizard-step active" data-step="1">
                    <h2>Select Your Watch Type</h2>
                    <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                        Choose the type of watch you need serviced
                    </p>
                    
                    <div class="watch-type-grid">
                        <?php while ($watch_type = $watch_types->fetch_assoc()): ?>
                        <label class="watch-type-card">
                            <input type="radio" name="watch_type_id" value="<?php echo $watch_type['watch_type_id']; ?>" required>
                            <div class="watch-type-icon">⌚</div>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($watch_type['type_name']); ?></div>
                        </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Step 2: Problem Description + Services -->
                <div class="wizard-step" data-step="2">
                    <h2>Describe Problem & Select Services</h2>
                    <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                        Tell us what's wrong and choose the services you need
                    </p>
                    
                    <div class="form-group">
                        <label class="form-label">Describe the Problem</label>
                        <textarea name="problem_description" class="form-control" rows="4" 
                                  placeholder="Please describe the issue with your watch..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Select Services (Multiple)</label>
                        <?php 
                        $services->data_seek(0); // Reset pointer
                        while ($service = $services->fetch_assoc()): 
                        ?>
                        <label class="service-checkbox">
                            <input type="checkbox" name="service_ids[]" value="<?php echo $service['service_id']; ?>" 
                                   data-price="<?php echo $service['base_price']; ?>">
                            <div class="service-info">
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                    <?php echo htmlspecialchars($service['description']); ?>
                                </div>
                            </div>
                            <div class="service-price"><?php echo format_currency($service['base_price']); ?></div>
                        </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Step 3: Date + Time Slot -->
                <div class="wizard-step" data-step="3">
                    <h2>Select Date & Time</h2>
                    <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                        Choose your preferred appointment date and time slot
                    </p>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Booking Date</label>
                                <input type="date" name="booking_date" id="bookingDate" class="form-control" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                                <small style="color: var(--text-secondary);">Sundays are not available. Select today or any future date.</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Time Slot <span id="loadingSlots" style="display:none; color: var(--primary-color);">Loading...</span></label>
                                <select name="timeslot_id" id="timeslotSelect" class="form-control" required style="font-size: 16px; padding: 12px;">
                                    <option value="">Select a date first</option>
                                </select>
                                <small id="slotInfo" style="color: var(--text-secondary); display: none;"></small>
                                
                                <!-- Real-time Availability Display -->
                                <div id="availabilityStatus" style="margin-top: 15px; padding: 15px; border-radius: 8px; display: none; font-weight: bold; text-align: center;">
                                    <div id="availabilityText"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Service Address -->
                <div class="wizard-step" data-step="4">
                    <h2>Service Address</h2>
                    <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                        Confirm or update your service address
                    </p>
                    
                    <div class="form-group">
                        <label class="form-label">Complete Address</label>
                        <textarea name="user_address" class="form-control" rows="4" 
                                  placeholder="Enter your complete address..." required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        <small style="color: var(--text-secondary);">
                            This is where our technician will visit for the service
                        </small>
                    </div>
                </div>

                <!-- Step 5: Summary + Payment -->
                <div class="wizard-step" data-step="5">
                    <h2>Booking Summary & Payment</h2>
                    <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                        Review your booking details and complete payment
                    </p>
                    
                    <div class="summary-section">
                        <h3 style="margin-bottom: 1rem;">Booking Details</h3>
                        <div class="summary-item">
                            <span>Watch Type:</span>
                            <span id="summaryWatchType">-</span>
                        </div>
                        <div class="summary-item">
                            <span>Problem:</span>
                            <span id="summaryProblem">-</span>
                        </div>
                        <div class="summary-item">
                            <span>Services:</span>
                            <span id="summaryServices">-</span>
                        </div>
                        <div class="summary-item">
                            <span>Date & Time:</span>
                            <span id="summaryDateTime">-</span>
                        </div>
                        <div class="summary-item">
                            <span>Address:</span>
                            <span id="summaryAddress">-</span>
                        </div>
                    </div>

                    <div class="summary-section">
                        <h3 style="margin-bottom: 1rem;">Payment Details</h3>
                        <div class="summary-item">
                            <span>Total Cost:</span>
                            <span id="summaryTotalCost" class="summary-total">₱0.00</span>
                        </div>
                        <div class="summary-item">
                            <span>Down Payment (50%):</span>
                            <span id="summaryDownPayment" style="color: var(--success); font-weight: 700; font-size: 1.25rem;">₱0.00</span>
                        </div>
                        <div class="summary-item">
                            <span>Remaining Balance (50%):</span>
                            <span id="summaryRemaining" style="color: var(--warning); font-weight: 600;">₱0.00</span>
                        </div>
                        <div class="alert alert-info" style="margin-top: 1rem;">
                            <strong>📝 Note:</strong> Pay 50% down payment now. Remaining 50% will be paid to the technician upon service completion.
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" id="paymentMethod" class="form-control" required>
                            <option value="">Select payment method</option>
                            <option value="GCash">GCash</option>
                            <option value="PayMaya">PayMaya</option>
                            <option value="PayPal">PayPal</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <!-- Payment Account Details -->
                    <div id="paymentDetails" class="alert alert-info" style="display: none; margin-top: 1rem;">
                        <h4 style="margin-bottom: 1rem; color: var(--primary-color);">💳 Payment Details</h4>
                        <div id="paymentInfo"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Upload Payment Proof</label>
                        <input type="file" name="payment_proof" class="form-control" accept="image/*" required>
                        <small style="color: var(--text-secondary);">
                            Upload screenshot of your payment transaction
                        </small>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="wizard-buttons">
                    <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">
                        ← Back
                    </button>
                    <div style="flex: 1;"></div>
                    <button type="button" class="btn btn-primary" id="nextBtn">
                        Next →
                    </button>
                    <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                        Submit Booking
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 5;

        // Watch type card selection
        document.querySelectorAll('.watch-type-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.watch-type-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Navigation
        document.getElementById('nextBtn').addEventListener('click', function() {
            if (validateStep(currentStep)) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                    updateProgress();
                }
            }
        });

        document.getElementById('prevBtn').addEventListener('click', function() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
                updateProgress();
            }
        });

        function showStep(step) {
            document.querySelectorAll('.wizard-step').forEach(s => s.classList.remove('active'));
            document.querySelector(`.wizard-step[data-step="${step}"]`).classList.add('active');
            
            document.querySelectorAll('.wizard-step-indicator').forEach(indicator => {
                const indicatorStep = parseInt(indicator.dataset.step);
                indicator.classList.remove('active', 'completed');
                if (indicatorStep === step) {
                    indicator.classList.add('active');
                } else if (indicatorStep < step) {
                    indicator.classList.add('completed');
                }
            });

            // Show/hide buttons
            document.getElementById('prevBtn').style.display = step === 1 ? 'none' : 'block';
            document.getElementById('nextBtn').style.display = step === totalSteps ? 'none' : 'block';
            document.getElementById('submitBtn').style.display = step === totalSteps ? 'block' : 'none';

            // Update summary on last step
            if (step === 5) {
                updateSummary();
            }
        }

        function updateProgress() {
            const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
            document.getElementById('progressLine').style.width = progress + '%';
        }

        function validateStep(step) {
            const currentStepEl = document.querySelector(`.wizard-step[data-step="${step}"]`);
            const inputs = currentStepEl.querySelectorAll('input[required], select[required], textarea[required]');
            
            for (let input of inputs) {
                if (input.type === 'radio') {
                    const radioGroup = currentStepEl.querySelectorAll(`input[name="${input.name}"]`);
                    const checked = Array.from(radioGroup).some(r => r.checked);
                    if (!checked) {
                        alert('Please select a watch type');
                        return false;
                    }
                } else if (input.type === 'checkbox') {
                    const checkboxes = currentStepEl.querySelectorAll('input[type="checkbox"]');
                    const checked = Array.from(checkboxes).some(c => c.checked);
                    if (!checked) {
                        alert('Please select at least one service');
                        return false;
                    }
                } else if (!input.value) {
                    alert('Please fill in all required fields');
                    input.focus();
                    return false;
                }
            }
            return true;
        }

        function updateSummary() {
            // Watch Type
            const watchType = document.querySelector('input[name="watch_type_id"]:checked');
            document.getElementById('summaryWatchType').textContent = 
                watchType ? watchType.parentElement.querySelector('div:last-child').textContent : '-';

            // Problem
            const problem = document.querySelector('textarea[name="problem_description"]').value;
            document.getElementById('summaryProblem').textContent = 
                problem.substring(0, 50) + (problem.length > 50 ? '...' : '');

            // Services
            const services = Array.from(document.querySelectorAll('input[name="service_ids[]"]:checked'))
                .map(cb => cb.parentElement.querySelector('.service-info div').textContent);
            document.getElementById('summaryServices').textContent = services.join(', ') || '-';

            // Date & Time
            const date = document.querySelector('input[name="booking_date"]').value;
            const timeSlot = document.querySelector('select[name="timeslot_id"]');
            const timeText = timeSlot.options[timeSlot.selectedIndex]?.text || '';
            document.getElementById('summaryDateTime').textContent = 
                date && timeText ? `${date} at ${timeText}` : '-';

            // Address
            const address = document.querySelector('textarea[name="user_address"]').value;
            document.getElementById('summaryAddress').textContent = 
                address.substring(0, 50) + (address.length > 50 ? '...' : '');

            // Calculate costs
            let totalCost = 0;
            document.querySelectorAll('input[name="service_ids[]"]:checked').forEach(cb => {
                totalCost += parseFloat(cb.dataset.price);
            });
            
            const downPayment = totalCost * 0.5;
            const remaining = totalCost * 0.5;

            document.getElementById('summaryTotalCost').textContent = '₱' + totalCost.toFixed(2);
            document.getElementById('summaryDownPayment').textContent = '₱' + downPayment.toFixed(2);
            document.getElementById('summaryRemaining').textContent = '₱' + remaining.toFixed(2);
        }

        // Payment Method Selection
        const paymentMethodSelect = document.getElementById('paymentMethod');
        const paymentDetails = document.getElementById('paymentDetails');
        const paymentInfo = document.getElementById('paymentInfo');

        // Load payment accounts from PHP configuration
        const paymentAccounts = <?php echo $payment_accounts_json; ?>;

        paymentMethodSelect.addEventListener('change', function() {
            const selectedMethod = this.value;
            
            if (selectedMethod && paymentAccounts[selectedMethod]) {
                const account = paymentAccounts[selectedMethod];
                let html = '<div style="background: white; padding: 1.5rem; border-radius: 0.5rem; border: 2px solid var(--primary-color);">';
                
                if (selectedMethod === 'GCash' || selectedMethod === 'PayMaya') {
                    html += `
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: var(--text-primary);">Account Name:</strong><br>
                            <span style="font-size: 1.1rem; color: var(--primary-color);">${account.name}</span>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: var(--text-primary);">Mobile Number:</strong><br>
                            <span style="font-size: 1.3rem; font-weight: 700; color: var(--success);">${account.number}</span>
                        </div>
                    `;
                } else if (selectedMethod === 'PayPal') {
                    html += `
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: var(--text-primary);">Account Name:</strong><br>
                            <span style="font-size: 1.1rem; color: var(--primary-color);">${account.name}</span>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: var(--text-primary);">PayPal Email:</strong><br>
                            <span style="font-size: 1.3rem; font-weight: 700; color: var(--success);">${account.email}</span>
                        </div>
                    `;
                } else if (selectedMethod === 'Bank Transfer') {
                    html += `
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: var(--text-primary);">Bank:</strong><br>
                            <span style="font-size: 1.1rem; color: var(--primary-color);">${account.bank}</span>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: var(--text-primary);">Account Name:</strong><br>
                            <span style="font-size: 1.1rem; color: var(--primary-color);">${account.accountName}</span>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: var(--text-primary);">Account Number:</strong><br>
                            <span style="font-size: 1.3rem; font-weight: 700; color: var(--success);">${account.accountNumber}</span>
                        </div>
                    `;
                }
                
                html += `
                    <div style="margin-top: 1rem; padding: 1rem; background: rgba(99, 102, 241, 0.1); border-radius: 0.5rem;">
                        <strong>📌 Instructions:</strong><br>
                        ${account.instructions}
                    </div>
                </div>`;
                
                paymentInfo.innerHTML = html;
                paymentDetails.style.display = 'block';
            } else {
                paymentDetails.style.display = 'none';
            }
        });

        // Date and Time Slot Validation
        const bookingDateInput = document.getElementById('bookingDate');
        const timeslotSelect = document.getElementById('timeslotSelect');
        const loadingSlots = document.getElementById('loadingSlots');
        const slotInfo = document.getElementById('slotInfo');
        let slotRefreshInterval = null;

        // Load slots when date changes
        bookingDateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            if (!selectedDate) return;

            // Check if Sunday
            const date = new Date(selectedDate + 'T00:00:00');
            if (date.getDay() === 0) {
                alert('Sundays are not available for booking. Please select another date.');
                this.value = '';
                return;
            }

            loadTimeSlots(selectedDate);
            
            // Clear any existing interval
            if (slotRefreshInterval) {
                clearInterval(slotRefreshInterval);
            }
            
            // Refresh slots every minute if today is selected
            const today = new Date().toISOString().split('T')[0];
            if (selectedDate === today) {
                slotRefreshInterval = setInterval(() => {
                    loadTimeSlots(selectedDate, true);
                }, 60000); // Refresh every 60 seconds
            }
        });

        function loadTimeSlots(date, isRefresh = false) {
            if (!isRefresh) {
                loadingSlots.style.display = 'inline';
                timeslotSelect.disabled = true;
            }

            console.log('Loading time slots for date:', date);

            // Use our new availability API
            fetch(`../api/get_timeslot_availability.php?date=${date}`)
                .then(response => {
                    console.log('API Response status:', response.status);
                    console.log('API Response headers:', response.headers);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    return response.text(); // Get as text first to see raw response
                })
                .then(text => {
                    console.log('Raw API response:', text);
                    
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed API data:', data);
                        
                        if (data.error) {
                            throw new Error('API Error: ' + data.error);
                        }
                        
                        if (data && typeof data === 'object') {
                            updateTimeslotOptions(data, date);
                            
                            const today = new Date().toISOString().split('T')[0];
                            if (date === today) {
                                slotInfo.textContent = '⏰ Showing real-time availability for today';
                                slotInfo.style.display = 'block';
                                slotInfo.style.color = 'var(--info)';
                            } else {
                                slotInfo.style.display = 'none';
                            }
                        } else {
                            throw new Error('Invalid data format received from API');
                        }
                    } catch (parseError) {
                        console.error('JSON Parse Error:', parseError);
                        console.error('Raw response that failed to parse:', text);
                        alert('Error parsing API response. Check console for details.');
                    }
                })
                .catch(error => {
                    console.error('API Error:', error);
                    
                    // Show user-friendly error message
                    timeslotSelect.innerHTML = '<option value="">Error loading time slots</option>';
                    slotInfo.textContent = '❌ Error loading availability. Please refresh the page and try again.';
                    slotInfo.style.display = 'block';
                    slotInfo.style.color = 'var(--danger)';
                    
                    // Also show detailed error in console
                    console.error('Detailed error information:', {
                        message: error.message,
                        stack: error.stack,
                        date: date,
                        apiUrl: `../api/get_timeslot_availability.php?date=${date}`
                    });
                })
                .finally(() => {
                    loadingSlots.style.display = 'none';
                    timeslotSelect.disabled = false;
                });
        }

        function updateTimeslotOptions(data, selectedDate) {
            const currentValue = timeslotSelect.value;
            timeslotSelect.innerHTML = '<option value="">-- Select a time slot --</option>';
            
            let availableCount = 0;
            let fullyBookedCount = 0;
            
            // Convert data object to array and sort by start time
            const slots = Object.values(data).sort((a, b) => a.start_time.localeCompare(b.start_time));

            slots.forEach(slot => {
                const option = document.createElement('option');
                option.value = slot.timeslot_id;
                
                const startTime = formatTime(slot.start_time);
                const endTime = formatTime(slot.end_time);
                const availableSpots = slot.available_spots;
                const maxSlots = slot.max_slots;

                // Disable past slots if selected date is today
                const todayStr = new Date().toISOString().split('T')[0];
                let isPast = false;
                if (selectedDate === todayStr) {
                    const now = new Date();
                    const [sHour, sMin] = slot.end_time.split(':');
                    const slotEnd = new Date();
                    slotEnd.setHours(parseInt(sHour), parseInt(sMin), 0, 0);
                    if (now >= slotEnd) {
                        isPast = true;
                    }
                }
                
                // Set option data attributes
                option.dataset.availableSpots = availableSpots;
                option.dataset.maxSlots = maxSlots;

                const isSelectable = availableSpots > 0 && !isPast;

                if (isSelectable) {
                    option.textContent = `${startTime} - ${endTime} (${availableSpots} of ${maxSlots} available)`;
                    availableCount++;
                } else {
                    let label = `${startTime} - ${endTime}`;
                    label += isPast ? ' (Past)' : ` (Fully Booked - 0 of ${maxSlots} available)`;
                    option.textContent = label;
                    option.disabled = true;
                    option.style.color = '#999';
                    option.style.backgroundColor = '#f5f5f5';
                    fullyBookedCount++;
                }
                
                timeslotSelect.appendChild(option);
            });

            // Restore previous selection if still available
            if (currentValue) {
                const selectedOption = timeslotSelect.querySelector(`option[value="${currentValue}"]`);
                if (selectedOption && !selectedOption.disabled) {
                    timeslotSelect.value = currentValue;
                    updateAvailabilityDisplay();
                }
            }

            // Update info message
            if (availableCount === 0) {
                slotInfo.textContent = '❌ All time slots are fully booked for this date. Please select another date.';
                slotInfo.style.display = 'block';
                slotInfo.style.color = 'var(--danger)';
            } else {
                const today = new Date().toISOString().split('T')[0];
                if (selectedDate === today) {
                    slotInfo.textContent = `✅ ${availableCount} slot(s) available today (${fullyBookedCount} fully booked)`;
                } else {
                    slotInfo.textContent = `✅ ${availableCount} slot(s) available (${fullyBookedCount} fully booked)`;
                }
                slotInfo.style.display = 'block';
                slotInfo.style.color = 'var(--success)';
            }
        }
        
        // Add new function to update availability display when slot is selected
        function updateAvailabilityDisplay() {
            const selectedOption = timeslotSelect.options[timeslotSelect.selectedIndex];
            const availabilityStatus = document.getElementById('availabilityStatus');
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
                availabilityStatus.style.backgroundColor = bgColor;
                availabilityStatus.style.color = textColor;
                availabilityStatus.style.border = `2px solid ${textColor}`;
                availabilityStatus.style.display = 'block';
            } else {
                availabilityStatus.style.display = 'none';
            }
        }
        
        // Add event listener for timeslot selection
        timeslotSelect.addEventListener('change', updateAvailabilityDisplay);

        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        }

        // Clean up interval when leaving the page
        window.addEventListener('beforeunload', function() {
            if (slotRefreshInterval) {
                clearInterval(slotRefreshInterval);
            }
        });

        // Initialize
        updateProgress();
    </script>
</body>
</html>
<?php closeDBConnection($conn); ?>
