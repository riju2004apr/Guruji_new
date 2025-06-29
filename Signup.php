<?php
session_start();
require_once '../connection/config.php';

// Function to validate input fields
function validateInput($username, $fullname, $email, $contact, $password, $confirm_password) {
    $errors = [];

    // Username validation
    if (!preg_match('/^[a-z0-9_]{6,16}$/', $username)) {
        $errors[] = "Username must be 6-16 characters long and contain only lowercase letters, numbers, and underscores.";
    }

    // Full Name validation
    if (!preg_match('/^[A-Za-z ]+$/', $fullname)) {
        $errors[] = "Full name can only contain letters and spaces.";
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    // Contact number validation
    if (!preg_match('/^9\d{9}$/', $contact)) {
        $errors[] = "Contact number must start with 9 and be exactly 10 digits long.";
    }

    // Password validation
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $errors[] = "Password must be at least 8 characters long inluding at least lowercase letters, uppercase letters, numbers & special characters.";
    }

    // Confirm password match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    return $errors;
}

// Function to check if a value already exists in any table
function isValueTaken($mysqli, $column, $value) {
    $tables = [];

    // Username should be checked in all tables
    if ($column === 'username') {
        $tables = ['users', 'drivers', 'admin'];
    }
    // Email and Contact should be checked in only users and drivers
    elseif (in_array($column, ['email', 'contact'])) {
        $tables = ['users', 'drivers'];
    } else {
        return false;
    }

    foreach ($tables as $table) {
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
        if (!$stmt) {
            die("Error preparing statement: " . $mysqli->error);
        }
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        if ($count > 0) {
            return true;
        }
    }
    return false;
}

// Handle AJAX validation requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['field'])) {
    $field = $_POST['field'];
    $value = trim($_POST['value']);
    $error = '';

    switch ($field) {
        case 'username':
            if (empty($value)) {
                $error = "Please enter a username.";
            } elseif (!preg_match('/^[a-z0-9_]{6,16}$/', $value)) {
                $error = "Username must be 6-16 characters and contain only lowercase letters, numbers, and underscores.";
            } elseif (isValueTaken($mysqli, 'username', $value)) {
                $error = "This username is already taken.";
            }
            break;

        case 'fullname':
            if (empty($value)) {
                $error = "Please enter your full name.";
            } elseif (!preg_match('/^[A-Za-z ]+$/', $value)) {
                $error = "Full name can only contain letters and spaces.";
            }
            break;

        case 'email':
            if (empty($value)) {
                $error = "Please enter your email address.";
            } elseif (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address.";
            } elseif (isValueTaken($mysqli, 'email', $value)) {
                $error = "This email is already registered.";
            }
            break;

        case 'contact':
            if (empty($value)) {
                $error = "Please enter your contact number.";
            } elseif (!preg_match('/^9\d{9}$/', $value)) {
                $error = "Contact number must start with 9 and be exactly 10 digits.";
            } elseif (isValueTaken($mysqli, 'contact', $value)) {
                $error = "This contact number is already registered.";
            }
            break;

        case 'password':
            if (empty($value)) {
                $error = "Please enter a password.";
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $value)) {
                $error = "Password must be at least 8 characters long inluding at least lowercase letters, uppercase letters, numbers & special characters.";
            }
            break;

        case 'confirm_password':
            $password = $_POST['password'] ?? '';
            if ($value !== $password) {
                $error = "Passwords do not match.";
            }
            break;
    }

    echo json_encode(['error' => $error]);
    exit;
}

    // Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['field'])) {
    // Get and sanitize form data
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $is_driver = isset($_POST['is_driver']);

    // Validate input
    $errors = validateInput($username, $fullname, $email, $contact, $password, $confirm_password);

    // Check for existing values
    if (isValueTaken($mysqli, 'username', $username)) {
        $errors[] = "Username is already taken.";
    }
    if (isValueTaken($mysqli, 'email', $email)) {
        $errors[] = "Email is already taken.";
    }
    if (isValueTaken($mysqli, 'contact', $contact)) {
        $errors[] = "Contact number is already taken.";
    }

    if (!empty($errors)) {
        echo json_encode(['errors' => $errors]);
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        if ($is_driver) {
            // Insert into drivers table
            $stmt = $mysqli->prepare("INSERT INTO drivers (username, fullname, pfp, email, contact, license, age, experience, account_status, availability_status, password) 
                                    VALUES (?, ?, '../images/default.png', ?, ?, '', 0, 0, 'Unverified', 'Offline', ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }
            $stmt->bind_param("sssss", $username, $fullname, $email, $contact, $hashed_password);
        } else {
            // Insert into users table
            $stmt = $mysqli->prepare("INSERT INTO users (username, fullname, email, contact, password) 
                                    VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }
            $stmt->bind_param("sssss", $username, $fullname, $email, $contact, $hashed_password);
        }

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();
        echo json_encode(['success' => "Account created successfully! Please log in."]);
        
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['errors' => ["Something went wrong. Please try again."]]);
    }
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - GURUJI</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
$(document).ready(function() {
    // Function to handle field validation
    function validateField(field) {
        const $field = $(field);
        const $formGroup = $field.closest('.form-group');
        const $errorSpan = $formGroup.find('.error-message');
        
        $.ajax({
            type: 'POST',
            url: window.location.href, // Use current URL
            data: {
                field: $field.attr('name'),
                value: $field.val(),
                password: $('#password').val() // needed for confirm password validation
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    $formGroup.addClass('error');
                    $errorSpan.text(response.error);
                } else {
                    $formGroup.removeClass('error');
                    $errorSpan.text('');
                }
            },
            error: function() {
                $errorSpan.text('Validation error occurred. Please try again.');
            }
        });
    }

    // Real-time validation on input change
    $('.form-group input').on('blur', function() {
        validateField(this);
    });

    // Special handling for confirm password
    $('#confirm_password').on('input', function() {
        validateField(this);
    });

    // Handle form submission
    $('#signupForm').on('submit', function(e) {
        e.preventDefault();
        
        let hasErrors = false;
        
        // Validate all fields before submission
        $('.form-group input').each(function() {
            validateField(this);
        });

        // Check if there are any errors after a short delay to allow validations to complete
        setTimeout(() => {
            if ($('.form-group.error').length === 0) {
                $.ajax({
                    type: 'POST',
                    url: window.location.href,
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.success);
                            window.location.href = 'login.php';
                        } else if (response.errors) {
                            alert(response.errors.join('\n'));
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            }
        }, 500);
    });
});
</script>
</head>
<body>
    <div class="signup-container">
        <h2>Sign up</h2>
        <form id="signupForm" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" name="fullname" id="fullname" required>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" required>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="text" name="contact" id="contact" required>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
                <span class="error-message"></span>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="is_driver" id="is_driver">
                <label for="is_driver">I am a driver</label>
            </div>

            <button type="submit">Sign Up</button>
            
            <p class="signup-link">Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </div>
</body>
</html>