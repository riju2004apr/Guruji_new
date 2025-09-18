<?php
session_start();
$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Database connection file
require_once "../connection/config.php";

// Process booking if form submitted and user is logged in
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["driver_id"]) && $is_logged_in) {
    $driver_id = $_POST["driver_id"];
    $user_id = $_SESSION["user_id"];
    
    // Get user information
    $user_sql = "SELECT fullname, contact FROM users WHERE id = ?";
    $user_stmt = $mysqli->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_stmt->bind_result($user_name, $user_phone);
    $user_stmt->fetch();
    $user_stmt->close();
    
    // Get driver information
    $driver_sql = "SELECT fullname, contact FROM drivers WHERE id = ?";
    $driver_stmt = $mysqli->prepare($driver_sql);
    $driver_stmt->bind_param("i", $driver_id);
    $driver_stmt->execute();
    $driver_stmt->bind_result($driver_name, $driver_phone);
    $driver_stmt->fetch();
    $driver_stmt->close();
    
    // Set user location (placeholder)
    $user_location = "Not specified";
    
    // Insert booking into database
    $booking_sql = "INSERT INTO bookings (user_id, driver_id, user_name, user_phone, driver_name, driver_phone) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $booking_stmt = $mysqli->prepare($booking_sql);
    $booking_stmt->bind_param("iissss", $user_id, $driver_id, $user_name, $user_phone, $driver_name, $driver_phone);
    
    if ($booking_stmt->execute()) {
        // Show success message and redirect
        echo "<script>
            alert('Hire request has been submitted, please wait for driver\'s response!');
            window.location.href = 'history.php';
        </script>";
        exit;
    }
}

// Check if the user has any pending or accepted bookings with any drivers
$pending_or_accepted_drivers = [];
if ($is_logged_in) {
    $user_id = $_SESSION["user_id"];
    // Query to get all drivers that the user has a pending or accepted booking with
    $pending_or_accepted_bookings_sql = "SELECT driver_id FROM bookings WHERE user_id = ? AND (status = 'Pending' OR status = 'Accepted')";
    $pending_or_accepted_bookings_stmt = $mysqli->prepare($pending_or_accepted_bookings_sql);
    $pending_or_accepted_bookings_stmt->bind_param("i", $user_id);
    $pending_or_accepted_bookings_stmt->execute();
    $pending_or_accepted_bookings_result = $pending_or_accepted_bookings_stmt->get_result();

    // Store the driver IDs that the user has pending or accepted bookings with
    while ($row = $pending_or_accepted_bookings_result->fetch_assoc()) {
        $pending_or_accepted_drivers[] = $row['driver_id'];
    }
    $pending_or_accepted_bookings_stmt->close();
}

$countongoingbookings = count($pending_or_accepted_drivers);

// Fetch available drivers from the database
$sql = "SELECT id, fullname, pfp, age, contact, experience FROM drivers WHERE account_status = 'Verified' AND availability_status = 'Online'";
$result = $mysqli->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Drivers - Guruji</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<nav>
    <div class="logo" onclick="window.location.href='index.php';" style="cursor: pointer;">GURUJI</div>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="drivers.php">Drivers</a>
        
        <!-- Profile Icon -->
        <a href="<?php echo $is_logged_in ? 'profile.php' : '../login/login.php'; ?>" class="profile-icon">
            <i class="fas fa-user"></i>
        </a>

        <!-- Login/Logout Button Toggle -->
        <?php if ($is_logged_in): ?>
            <form action="../login/logout.php" method="POST">
                <button type="submit" class="login-btn">Logout</button>
            </form>
        <?php else: ?>
            <a href="../login/login.php" class="login-btn">Login</a>
        <?php endif; ?>
    </div>
</nav>

<section class="drivers-container">
    <h2 class="section-title">Available Drivers</h2>
    <div class="drivers-list">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                // Skip drivers that the user already has a pending or accepted booking with
                if (in_array($row['id'], $pending_or_accepted_drivers)) {
                    continue;
                }
                ?>
                <div class="driver-card">
                    <img src="<?php echo htmlspecialchars($row['pfp']); ?>" alt="Driver Picture" class="driver-img">
                    <div class="driver-info">
                        <h3><?php echo htmlspecialchars($row['fullname']); ?> 
                        <i class="fa-regular fa-circle-check"></i>
                        </h3>
                        <p><strong>Age:</strong> <?php echo htmlspecialchars($row['age']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($row['contact']); ?></p>
                        <p><strong>Experience:</strong> <?php echo htmlspecialchars($row['experience']); ?> years</p>
                        <form action="<?php echo $is_logged_in ? 'drivers.php' : '../login/login.php'; ?>" method="POST">
                            <input type="hidden" name="driver_id" value="<?php echo $row['id']; ?>">

                            <?php if ($countongoingbookings > 0): ?>
                                <button class="disabled-btn" disabled>Unfinished booking exists.</button>
                            <?php else: ?>
                                <button type="submit" class="hire-now-btn">Hire Now</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="driver-card">
                    <div class="driver-info">
                        <h3>No Drivers Available.</h3>
                    </div>
                </div>
                        <?php endif; ?>
    </div>
</section>


<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h3>About Guruji</h3>
            <p>Your trusted partner in professional driver hiring services.</p>
        </div>
        <div class="footer-section">
            <h3>Contact us</h3>
            <p><i class="fa-solid fa-phone"></i> 01-528599</p>
            <p><i class="fa-solid fa-envelope"></i> info@guruji.com.np</p>
            <p><i class="fa-solid fa-location-dot"></i> Godawari-11, Lalitpur</p>
        </div>
        <div class="footer-section">
            <h3>Connect With Us</h3>
            <div class="social-icons">
    <a href="https://www.facebook.com" target="_blank"><i class="fab fa-facebook"></i></a>
    <a href="https://x.com" target="_blank"><i class="fab fa-twitter"></i></a>
    <a href="https://www.instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
    <a href="https://www.linkedin.com" target="_blank"><i class="fab fa-linkedin"></i></a>
</div>
        </div>
    </div>
</footer>

</body>
</html>
