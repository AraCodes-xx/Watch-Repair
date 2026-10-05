<?php
require_once '../config/config.php';
require_login();

$user_id = get_current_user_id();
$booking_id = intval($_GET['booking_id'] ?? 0);
$error = '';
$success = '';

$conn = getDBConnection();

// Verify booking exists and is completed
$stmt = $conn->prepare("SELECT b.*, t.technician_id, t.full_name as technician_name, s.service_name 
                        FROM bookings b 
                        JOIN technicians t ON b.technician_id = t.technician_id 
                        JOIN services s ON b.service_id = s.service_id 
                        WHERE b.booking_id = ? AND b.user_id = ? AND b.status = 'Completed'");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect(SITE_URL . '/user/my_bookings.php');
}

$booking = $result->fetch_assoc();

// Check if feedback already exists
$stmt = $conn->prepare("SELECT feedback_id FROM feedback WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$existing_feedback = $stmt->get_result();

if ($existing_feedback->num_rows > 0) {
    redirect(SITE_URL . '/user/my_bookings.php?error=feedback_exists');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = intval($_POST['rating']);
    $comment = sanitize_input($_POST['comment']);
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5';
    } else {
        $technician_id = $booking['technician_id'];
        
        $stmt = $conn->prepare("INSERT INTO feedback (booking_id, user_id, technician_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $booking_id, $user_id, $technician_id, $rating, $comment);
        
        if ($stmt->execute()) {
            // Update technician rating
            $stmt = $conn->prepare("UPDATE technicians SET 
                                   rating = (SELECT AVG(rating) FROM feedback WHERE technician_id = ?),
                                   total_jobs = total_jobs + 1 
                                   WHERE technician_id = ?");
            $stmt->bind_param("ii", $technician_id, $technician_id);
            $stmt->execute();
            
            redirect(SITE_URL . '/user/my_bookings.php?feedback_success=1');
        } else {
            $error = 'Failed to submit feedback';
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
    <title>Add Feedback - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .star-rating {
            display: flex;
            gap: 0.5rem;
            font-size: 2.5rem;
            margin: 1rem 0;
        }
        .star {
            cursor: pointer;
            color: var(--border-color);
            transition: color 0.3s ease, transform 0.2s ease;
        }
        .star:hover,
        .star.active {
            color: #f59e0b;
            transform: scale(1.2);
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
                    <li><a href="booking.php">Book Service</a></li>
                    <li><a href="my_bookings.php" class="active">My Bookings</a></li>
                    <li><a href="notifications.php">Notifications</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="max-width: 700px; margin-top: 3rem; margin-bottom: 3rem;">
        <h1>Rate Your Experience</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Share your feedback about the service</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <strong>Service:</strong> <?php echo htmlspecialchars($booking['service_name']); ?><br>
                    <strong>Technician:</strong> <?php echo htmlspecialchars($booking['technician_name']); ?><br>
                    <strong>Date:</strong> <?php echo date('F d, Y', strtotime($booking['booking_date'])); ?>
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <div class="star-rating" id="starRating">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" required>
                        <div id="ratingText" style="color: var(--text-secondary); margin-top: 0.5rem;"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Your Comment</label>
                        <textarea name="comment" class="form-control" rows="5" placeholder="Share your experience with this service..." required></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Submit Feedback</button>
                        <a href="my_bookings.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        const ratingText = document.getElementById('ratingText');
        
        const ratingLabels = {
            1: 'Poor',
            2: 'Fair',
            3: 'Good',
            4: 'Very Good',
            5: 'Excellent'
        };

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.dataset.rating;
                ratingInput.value = rating;
                
                stars.forEach(s => {
                    if (s.dataset.rating <= rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
                
                ratingText.textContent = ratingLabels[rating];
            });

            star.addEventListener('mouseenter', function() {
                const rating = this.dataset.rating;
                stars.forEach(s => {
                    if (s.dataset.rating <= rating) {
                        s.style.color = '#f59e0b';
                    } else {
                        s.style.color = 'var(--border-color)';
                    }
                });
            });
        });

        document.getElementById('starRating').addEventListener('mouseleave', function() {
            const currentRating = ratingInput.value;
            stars.forEach(s => {
                if (currentRating && s.dataset.rating <= currentRating) {
                    s.style.color = '#f59e0b';
                } else {
                    s.style.color = 'var(--border-color)';
                }
            });
        });
    </script>
</body>
</html>
<?php closeDBConnection($conn); ?>
