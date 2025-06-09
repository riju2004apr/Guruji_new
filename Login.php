<?php
session_start();
require_once '../connection/config.php';

function checkLogin($mysqli, $username, $password) {
    // Check users table
    $stmt = $mysqli->prepare("SELECT id, password FROM users WHERE username = ?");
    if (!$stmt) {
        die("Error preparing statement: " . $mysqli->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hashed_password);
    $stmt->fetch();
    $stmt->close();

    if ($id && password_verify($password, $hashed_password)) {
        return ['table' => 'users', 'id' => $id];
    }

    // Check admin table
    $stmt = $mysqli->prepare("SELECT id, password FROM admin WHERE username = ?");
    if (!$stmt) {
        die("Error preparing statement: " . $mysqli->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hashed_password);
    $stmt->fetch();
    $stmt->close();

    if ($id && password_verify($password, $hashed_password)) {
        return ['table' => 'admin', 'id' => $id];
    }

    // Check drivers table
    $stmt = $mysqli->prepare("SELECT id, password FROM drivers WHERE username = ?");
    if (!$stmt) {
        die("Error preparing statement: " . $mysqli->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hashed_password);
    $stmt->fetch();
    $stmt->close();

    if ($id && password_verify($password, $hashed_password)) {
        return ['table' => 'drivers', 'id' => $id];
    }

    return false;
}

// Handle login request via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if the username and password match any table
    $user = checkLogin($mysqli, $username, $password);

    if ($user) {
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_table'] = $user['table'];
        $_SESSION['loggedin'] = true;
    
        if ($user['table'] == 'users') {
            echo json_encode(['redirect' => '../user/index.php']);
        } elseif ($user['table'] == 'admin') {
            echo json_encode(['redirect' => '../admin/index.php']);
        } elseif ($user['table'] == 'drivers') {
            echo json_encode(['redirect' => '../drivers/index.php']);
        }
    } else {
        echo json_encode(['error' => 'Incorrect username or password.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GURUJI</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();

                const username = $('#username').val();
                const password = $('#password').val();

                $.ajax({
                    type: 'POST',
                    url: 'login.php',
                    data: {
                        username: username,
                        password: password
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else if (response.error) {
                            $('.error-message').text(response.error);
                        }
                    },
                    error: function() {
                        $('.error-message').text('An error occurred. Please try again.');
                    }
                });
            });
        });
    </script>
</head>
<body>
<div class="login-container">
        <h2>Login</h2>
        <form id="loginForm" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Enter your password" required>
                <span class="error-message" style="color: red;"></span>
            </div>
            <button type="submit">Login</button>
            <p class="signup-link">Don't have an account? <a href="signup.php">Sign up here</a>.</p>
        </form>
    </div>   
</body>
</html>
