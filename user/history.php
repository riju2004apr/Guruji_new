<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login/login.php");
    exit;
}

// Database connection file
require_once "../connection/config.php";

$user_id = $_SESSION["user_id"];

// Cancel booking if requested
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel_booking"]) && isset($_POST["booking_id"])) {
    $booking_id = $_POST["booking_id"];
    
    // Update booking status to Cancelled
    $cancel_sql = "UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'Pending'";
    $cancel_stmt = $mysqli->prepare($cancel_sql);
    $cancel_stmt->bind_param("ii", $booking_id, $user_id);
    
    if ($cancel_stmt->execute()) {
        echo "<script>
            alert('Booking has been cancelled successfully');
            window.location.href = 'history.php';
        </script>";
        exit;
    }
}

// Fetch booking history for the logged-in user
$sql = "SELECT b.id, b.driver_name, b.driver_phone, b.status, b.order_date
        FROM bookings b
        WHERE b.user_id = ?
        ORDER BY b.order_date DESC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History - Guruji</title>
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
        <a href="profile.php" class="profile-icon">
            <i class="fas fa-user"></i>
        </a>

        <!-- Logout Button -->
        <form action="../login/logout.php" method="POST">
            <button type="submit" class="login-btn">Logout</button>
        </form>
    </div>
</nav>

<section class="history-container">
    <h2 class="section-title">Your Booking History</h2>
    
    <?php if ($result->num_rows > 0): ?>
        <div class="booking-list">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="booking-card">
                    <div class="booking-header">
                        <h3>Driver: <?php echo htmlspecialchars($row['driver_name']); ?></h3>
                        <span class="booking-status status-<?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                    </div>
                    <div class="booking-info">
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['driver_phone']); ?></p>
                        <p><i class="fas fa-calendar"></i> <?php echo date('F j, Y, g:i a', strtotime($row['order_date'])); ?></p>
                    </div>
                    <?php if ($row['status'] == 'Pending'): ?>
                        <div class="booking-actions">
                            <form action="history.php" method="POST">
                                <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="cancel_booking" class="cancel-btn">Cancel Request</button>
                            </form>
                        </div>
                    <?php elseif ($row['status'] == 'Accepted'): ?>
                        <div class="booking-actions">
                            <p class="action-message">Driver has accepted your request!</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-bookings">
            <i class="fas fa-calendar-xmark"></i>
            <p>You don't have any booking history yet.</p>
            <a href="drivers.php" class="browse-drivers-btn">Browse Available Drivers</a>
        </div>
    <?php endif; ?>
</section>

</body>
</html>
