<?php
session_start();
require_once "../connection/config.php"; // Database connection file

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../login/login.php");
    exit;
}

// Fetch user details from the database
$user_id = $_SESSION["user_id"];
$sql = "SELECT username, fullname, email, contact FROM users WHERE id = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($username, $full_name, $email, $phone);
    $stmt->fetch();
    $stmt->close();
} else {
    die("Database query failed");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Guruji</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="profile-container">
        <h2>User Profile</h2>
        <div class="profile-info">
            <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($full_name); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>
        </div>
        <div class="profile-actions">
            <a href="index.php" class="btn">Back to Home</a>
            <a href="change_password.php" class="btn">Change Password</a>
            <a href="history.php" class="btn">Booking History</a>
        </div>
    </div>
</body>
</html>