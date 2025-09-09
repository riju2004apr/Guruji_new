<?php
session_start();
require_once '../connection/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Password validation function with updated regex
function validatePassword($password, $confirm_password) {
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        return "Password must be at least 8 characters long inluding at least lowercase letters, uppercase letters, numbers & special characters.";
    }
    if ($password !== $confirm_password) {
        return "Passwords do not match.";
    }
    return "";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate new password
    $error = validatePassword($new_password, $confirm_password);
    if ($error) {
        echo json_encode(['error' => $error]);
        exit;
    }

    // Update password in the drivers table
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $mysqli->prepare("UPDATE drivers SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => "Password has been changed successfully!",
            'redirect' => 'index.php'
        ]);
    } else {
        echo json_encode(['error' => "Error updating password: " . $mysqli->error]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="cpw.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="login-container">
        <h2>Change Password</h2>
        <form id="changePasswordForm">
            <p id="form-error" class="error-message" style="color: red; font-size: 12px; font-weight: bold;"></p>

            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required>
                <span class="error-message" id="new-password-error" style="color: red; font-size: 12px;"></span>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
                <span class="error-message" id="confirm-password-error" style="color: red; font-size: 12px;"></span>
            </div>

            <button type="submit">Update Password</button>
        </form>
    </div>

    <script>
        $(document).ready(function () {
            // Real-time validation
            $("#new_password, #confirm_password").on("input", function () {
                let newPassword = $("#new_password").val();
                let confirmPassword = $("#confirm_password").val();
                let passwordError = "";

                // Password strength validation using updated regex
                let passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
                if (!passwordPattern.test(newPassword)) {
                    passwordError = "Password must be at least 8 characters long inluding at least lowercase letters, uppercase letters, numbers & special characters.";
                }

                $("#new-password-error").text(passwordError);

                // Confirm password validation
                if (newPassword !== confirmPassword) {
                    $("#confirm-password-error").text("Passwords do not match.");
                } else {
                    $("#confirm-password-error").text("");
                }
            });

            // Handle form submission via AJAX
            $("#changePasswordForm").submit(function (event) {
                event.preventDefault();
                $("#form-error").text(""); // Clear previous errors

                $.ajax({
                    url: window.location.href, // Use current URL
                    type: "POST",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function (response) {
                        if (response.error) {
                            $("#form-error").text(response.error);
                        } else if (response.success) {
                            alert(response.success);
                            window.location.href = response.redirect; // Redirect to the driver's profile page
                        }
                    },
                    error: function () {
                        $("#form-error").text("Something went wrong. Please try again.");
                    }
                });
            });
        });
    </script>
</body>
</html>
