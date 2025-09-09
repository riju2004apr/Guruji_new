<?php
session_start();
require_once "../connection/config.php";

// Ensure the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../login/login.php");
    exit;
}

// Get the driver ID
$driver_id = $_SESSION["user_id"];

// Fetch driver details from the database
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

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $age = $_POST['age'];
    $experience = $_POST['experience'];

    // Check if email or phone already exists for another driver
    $check_email = "SELECT id FROM drivers WHERE email = ? AND id != ?";
    if ($stmt = $mysqli->prepare($check_email)) {
        $stmt->bind_param("si", $email, $driver_id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            echo "<script>alert('Email already taken. Please use a different one.'); window.history.back();</script>";
            exit;
        }
        $stmt->close();
    }


    $check_phone = "SELECT id FROM drivers WHERE contact = ? AND id != ?";
    if ($stmt = $mysqli->prepare($check_phone)) {
        $stmt->bind_param("si", $contact, $driver_id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            echo "<script>alert('Phone number already taken. Please use a different one.'); window.history.back();</script>";
            exit;
        }
        $stmt->close();
    }

    // Validate experience: must be <= (age - 20)
    if ($experience > ($age - 20)) {
        echo "<script>alert('Experience cannot be more than (Age - 20) years.'); window.history.back();</script>";
        exit;
    }

    // Handle profile picture and license image upload
    $pfp_path = $pfp;
    $license_path = $license;

    // Create upload directories if they don't exist
    $pfp_dir = '../uploads/pfp/';
    if (!file_exists($pfp_dir)) {
        mkdir($pfp_dir, 0777, true);
    }

    $license_dir = '../uploads/license/';
    if (!file_exists($license_dir)) {
        mkdir($license_dir, 0777, true);
    }

    // Handle profile picture upload
    if (isset($_FILES['pfp']) && $_FILES['pfp']['error'] == 0) {
        $pfp_ext = strtolower(pathinfo($_FILES['pfp']['name'], PATHINFO_EXTENSION));
        if (in_array($pfp_ext, ['jpg', 'jpeg', 'png'])) {
            if (!empty($pfp) && file_exists($pfp) && $pfp != '../images/default.png') {
                unlink($pfp);
            }
            
            $pfp_filename = uniqid() . '.' . $pfp_ext;
            $pfp_path = $pfp_dir . $pfp_filename;
            
            if (!move_uploaded_file($_FILES['pfp']['tmp_name'], $pfp_path)) {
                echo "<script>alert('Failed to upload profile picture. Please try again.'); window.history.back();</script>";
                exit;
            }
        } else {
            echo "<script>alert('Profile picture must be in jpg, jpeg, or png format.'); window.history.back();</script>";
            exit;
        }
    }

    // Handle license image upload
    if (isset($_FILES['license']) && $_FILES['license']['error'] == 0) {
        $license_ext = strtolower(pathinfo($_FILES['license']['name'], PATHINFO_EXTENSION));
        if (in_array($license_ext, ['jpg', 'jpeg', 'png'])) {
            if (!empty($license) && file_exists($license)) {
                unlink($license);
            }
            
            $license_filename = uniqid() . '.' . $license_ext;
            $license_path = $license_dir . $license_filename;
            
            if (!move_uploaded_file($_FILES['license']['tmp_name'], $license_path)) {
                echo "<script>alert('Failed to upload license image. Please try again.'); window.history.back();</script>";
                exit;
            }
        } else {
            echo "<script>alert('License image must be in jpg, jpeg, or png format.'); window.history.back();</script>";
            exit;
        }
    }

    // Update profile details in the database
    $sql = "UPDATE drivers SET fullname = ?, email = ?, contact = ?, age = ?, experience = ?, pfp = ?, license = ?, account_status = 'Unverified' WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("sssiissi", $fullname, $email, $contact, $age, $experience, $pfp_path, $license_path, $driver_id);
        if ($stmt->execute()) {
            echo "<script>alert('Profile updated successfully!'); window.location.href='index.php';</script>";
        } else {
            echo "<script>alert('Error updating profile: " . $mysqli->error . "'); window.history.back();</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Error preparing SQL statement: " . $mysqli->error . "'); window.history.back();</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Profile - Guruji</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                <?php if (!empty($pfp) && file_exists($pfp)): ?>
                    <img src="<?php echo htmlspecialchars($pfp); ?>" alt="Profile Picture">
                <?php else: ?>
                    <img src="../uploads/default-avatar.png" alt="Default Profile Picture">
                <?php endif; ?>
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
            </div>
        </aside>

        <!-- Main Content Section for Update Profile -->
        <main class="update-profile-container">
            <h2>Update Profile</h2>

            <form id="update-profile-form" method="POST" enctype="multipart/form-data">
                <label for="fullname">Full Name:</label>
                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($full_name); ?>" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

                <label for="contact">Phone:</label>
                <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($phone); ?>" required>

                <label for="age">Age:</label>
                <input type="number" id="age" name="age" min="23" max="50" value="<?php echo htmlspecialchars($age); ?>" required>

                <label for="experience">Experience (years):</label>
                <input type="number" id="experience" name="experience" min="3" value="<?php echo htmlspecialchars($experience); ?>" required>

                <label for="pfp">Profile Picture:</label>
                <input type="file" id="pfp" name="pfp" accept="image/*">

                <div class="current-image">
                    <?php if (!empty($pfp) && file_exists($pfp)): ?>
                        <p>Current profile picture:</p>
                        <img src="<?php echo htmlspecialchars($pfp); ?>" alt="Profile Picture" style="width: 100px; height: 100px; margin-top: 10px;">
                    <?php else: ?>
                        <p>No profile picture uploaded!</p>
                    <?php endif; ?>
                </div>

                <label for="license">License Image:</label>
                <input type="file" id="license" name="license" accept="image/*">

                <div class="current-image">
                    <?php if (!empty($license) && file_exists($license)): ?>
                        <p>Current license image:</p>
                        <img src="<?php echo htmlspecialchars($license); ?>" alt="License Image" style="width: 100px; height: 100px; margin-top: 10px;">
                    <?php else: ?>
                        <p>No license image uploaded!</p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn">Update Profile</button>
            </form>
        </main>
    </div>

    <script>
        $(document).ready(function () {
    $("#update-profile-form").submit(function (e) {
        let age = parseInt($("#age").val());
        let experience = parseInt($("#experience").val());
        let email = $("#email").val();
        let contact = $("#contact").val();

        // Ensure experience is <= (age - 20)
        if (experience > (age - 20)) {
            alert("Experience cannot be more than (Age - 20) years.");
            e.preventDefault();
            return false;
        }
    });
});


        $(document).ready(function () {
            // Validate on form submit
            $("#update-profile-form").submit(function (e) {
                let age = $("#age").val();
                let experience = $("#experience").val();
                let validImageExtensions = ["jpg", "jpeg", "png"];
                let pfpFile = $("#pfp")[0].files[0];
                let licenseFile = $("#license")[0].files[0];

                // Check age
                if (age < 20) {
                    alert("Minimum 20 years age required.");
                    e.preventDefault();
                    return false;
                }

                // Check experience
                if (experience < 2) {
                    alert("Minimum 2 years experience required.");
                    e.preventDefault();
                    return false;
                }

                // Check for valid image formats
                if (pfpFile && !validImageExtensions.includes(pfpFile.name.split('.').pop().toLowerCase())) {
                    alert("Profile picture must be in jpg, jpeg, or png format.");
                    e.preventDefault();
                    return false;
                }

                if (licenseFile && !validImageExtensions.includes(licenseFile.name.split('.').pop().toLowerCase())) {
                    alert("License image must be in jpg, jpeg, or png format.");
                    e.preventDefault();
                    return false;
                }

                // Form is valid, continue submission
                return true;
            });
        });
    </script>
</body>
</html>