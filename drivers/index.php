<?php
session_start();
require_once "../connection/config.php";

// Check if the user is logged in as a driver
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../login/login.php");
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

// Fetch statistics using prepared statements
function getBookingCount($mysqli, $driver_id, $status) {
    $sql = "SELECT COUNT(*) as total FROM bookings WHERE driver_id = ? AND status = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("is", $driver_id, $status);
        $stmt->execute();
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
        return $total;
    }
    return 0;
}

$successful_bookings = getBookingCount($mysqli, $driver_id, 'Completed');
$pending_requests = getBookingCount($mysqli, $driver_id, 'Pending');

// Fetch current availability status
function getCurrentStatus($mysqli, $driver_id) {
    $sql = "SELECT availability_status FROM drivers WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $stmt->bind_result($availability_status);
        $stmt->fetch();
        $stmt->close();
        return $availability_status;
    }
    return "Unavailable"; // Default fallback status
}

// Fetch availability status
$current_status = getCurrentStatus($mysqli, $driver_id);

// Handle AJAX request to update status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['status'])) {
    $status = $_POST['status'];

    if ($status == 'Online' || $status == 'Offline') {
        // Update driver's availability status
        $sql = "UPDATE drivers SET availability_status = ? WHERE id = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("si", $status, $driver_id);
            $stmt->execute();
            $stmt->close();

            // If the status is 'Offline', update all 'Pending' bookings to 'Failed'
            if ($status == 'Offline') {
                $update_bookings = "UPDATE bookings SET status = 'Failed' WHERE driver_id = ? AND status = 'Pending'";
                if ($stmt2 = $mysqli->prepare($update_bookings)) {
                    $stmt2->bind_param("i", $driver_id);
                    $stmt2->execute();
                    $stmt2->close();
                }
            }

            // Return the updated status to the client
            echo $status;
        } else {
            echo "Failed to update status.";
        }
    } else {
        echo "Invalid status.";
    }
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - Guruji</title>
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
        <!-- Profile Section on the Left -->
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

        <!-- Main Content Section -->
        <main class="dashboard-content">
            <h1>Welcome, <?php echo htmlspecialchars($full_name); ?>!</h1>
            <section class="cards">
                <div class="card">
                    <h3>Current Status</h3>
                    <p style="color: <?php echo ($current_status == 'Online') ? 'green' : ($current_status == 'Booked' ? 'red' : 'black'); ?>;"><?php echo $current_status; ?></p>
                </div>
                <div class="card">
                    <h3>Successful Bookings</h3>
                    <p><?php echo $successful_bookings; ?></p>
                </div>
                <div class="card">
                    <h3>Pending Requests</h3>
                    <p><?php echo $pending_requests; ?></p>
                </div>
                <div class="card">
    <h3>Update Status</h3>
    <?php if ($current_status == 'Booked'): ?>
        <p>No action available.</p>
    <?php elseif ($current_status == 'Online'): ?>
        <a class="btn" id="off-btn" style="display:inline-block;">Go Offline</a>
        <a class="btn" id="on-btn" style="display:none;">Go Online</a>
    <?php else: ?>
        <a class="btn" id="on-btn" style="display:inline-block;">Go Online</a>
        <a class="btn" id="off-btn" style="display:none;">Go Offline</a>
    <?php endif; ?>
</div>
                <div class="card">
                    <h3>Manage Bookings</h3>
                    <a href="manage_bookings.php" class="btn">Manage</a>
                </div>
                <div class="card">
                    <h3>View Booking History</h3>
                    <a href="history.php" class="btn">View History</a>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Function to update the status
function updateStatus(status) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // Update the status on success
            document.querySelector('.card p').textContent = xhr.responseText;
            // Change the color based on the status
            document.querySelector('.card p').style.color = (xhr.responseText === 'Online') ? 'green' : 'black';

            // Update button visibility based on new status
            if (xhr.responseText === 'Online') {
                // If now online, show the "Go Offline" button and hide the "Go Online" button
                if (document.getElementById('off-btn')) document.getElementById('off-btn').style.display = 'inline-block';
                if (document.getElementById('on-btn')) document.getElementById('on-btn').style.display = 'none';
            } else if (xhr.responseText === 'Offline') {
                // If now offline, show the "Go Online" button and hide the "Go Offline" button
                if (document.getElementById('on-btn')) document.getElementById('on-btn').style.display = 'inline-block';
                if (document.getElementById('off-btn')) document.getElementById('off-btn').style.display = 'none';
            }
            
            // Reload the page to ensure all elements are updated correctly
            // Alternatively, we can remove this and rely on the above code to update the UI
            // window.location.reload();
        }
    };
    xhr.send('status=' + status);
}

// When 'Go Online' button is clicked
document.getElementById('on-btn')?.addEventListener('click', function() {
    updateStatus('Online');
});

// When 'Go Offline' button is clicked
document.getElementById('off-btn')?.addEventListener('click', function() {
    updateStatus('Offline');
});
    </script>

</body>
</html>
