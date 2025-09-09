<?php
session_start();

// Database connection file
require_once "../connection/config.php";

// Check if driver is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login/login.php");
    exit;
}

// Fetch driver details from the database
$driver_id = $_SESSION["user_id"];
$sql = "SELECT username, fullname, email, contact, pfp, license, age, experience, account_status, availability_status 
    FROM drivers WHERE id = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $stmt->bind_result($username, $full_name, $email, $phone, $pfp, $license, $age, $experience, $account_status, $availability_status);
    $stmt->fetch();
    $stmt->close();
} else {
    die("Database query failed");
}

// Fetch booking history for the logged-in driver with specific statuses
$sql = "SELECT id, user_name, user_phone, status, order_date 
        FROM bookings 
        WHERE driver_id = ? AND status IN ('Cancelled', 'Completed', 'Failed')
        ORDER BY order_date DESC";


$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Booking History - Guruji</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav>
        <div class="logo" onclick="window.location.href='index.php';">GURUJI</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="manage_bookings.php">Bookings</a>
            <form action="../login/logout.php" method="POST">
                <button type="submit" class="login-btn">Logout</button>
            </form>
        </div>
    </nav>
    
<div class="container">
<aside class="profile-container">
            <h2>Driver Profile</h2>
            <div class="profile-picture">
            <img src="<?php echo htmlspecialchars($pfp); ?>" alt="Profile Picture">
            </div>
            <div class="profile-info">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($full_name); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>
                <p><strong>Age:</strong> <?php echo htmlspecialchars($age); ?> yrs</p>
                <p><strong>Experience:</strong> <?php echo htmlspecialchars($experience); ?> yrs</p>
                <p><strong>Account Status:</strong> <?php echo htmlspecialchars($account_status); ?></p>
            </div>
            <div class="profile-actions">
    <a href="change_password.php" class="btn">Change Password</a>
    <?php if ($account_status !== 'Verified'): ?>
        <a href="update_profile.php" class="btn">Update Profile</a>
    <?php endif; ?>
</div>
        </aside>

        <main class="history-container" id="history">
    <h1 class="section-title">Booking History</h1>
    
    <?php if ($result->num_rows > 0): ?>
        <div class="booking-list">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="booking-card">
                    <div class="booking-header">
                        <h3>Customer: <?php echo htmlspecialchars($row['user_name']); ?></h3>
                        <span class="booking-status status-<?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                    </div>
                    <div class="booking-info">
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['user_phone']); ?></p>
                        <p><i class="fas fa-calendar"></i> <?php echo date('F j, Y, g:i a', strtotime($row['order_date'])); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-bookings">
            <i class="fas fa-calendar-xmark"></i>
            <p>No bookings history available.</p>
        </div>
    <?php endif; ?>
</main>

</div>
</body>
</html>
