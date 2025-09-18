<?php
session_start();

// Check if the user is logged in
$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guruji - Professional Driver Hiring Service</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
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

    <section class="hero">
        <h1>Your Trusted Driver Service</h1>
        <p>Professional drivers at your fingertips</p>
        <button class="hire-btn" onclick="window.location.href='drivers.php';">Hire a Driver</button>
    </section>

    <section class="why-choose-us">
    <h2 class="section-title">Why Choose Us?</h2>
    <div class="info-cards">
        <div class="card">
            <i class="fas fa-shield-alt"></i>
            <h3>Verified Drivers</h3>
            <p>Experienced, vetted, and professionally trained drivers at your service.</p>
        </div>
        <div class="card">
            <i class="fas fa-clock"></i>
            <h3>24/7 Service</h3>
            <p>Round-the-clock service to meet your driving needs anytime.</p>
        </div>
        <div class="card">
            <i class="fas fa-car"></i>
            <h3>Safe & Reliable</h3>
            <p>Your safety is our top priority with our highly experienced drivers.</p>
        </div>
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
