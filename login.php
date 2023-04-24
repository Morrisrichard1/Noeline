<?php
session_start();
require_once "config.php";

if (isset($_SESSION['user_id'])) {
    echo "You are already logged in. <a href='logout.php'>Logout</a>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate input
    if (empty($username) || empty($password)) {
        echo "Please fill in all the required fields.";
        exit;
    }

    // Check the user in the database and verify the password
    $stmt = $conn->prepare("SELECT id, username, password, user_role, is_blocked, login_attempts FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $hashed_password, $user_role, $is_blocked, $login_attempts);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            if ($is_blocked) {
                echo "Your account has been blocked. Please contact the administrator.";
            } else {
                // Reset login_attempts
                $stmt->close();
                $stmt = $conn->prepare("UPDATE users SET login_attempts = 0 WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // Set the session data
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['user_role'] = $user_role;

                // Redirect to the appropriate dashboard based on user_role
                if ($user_role == 1) {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
            }
        } else {
            // Increment login_attempts and check if reached the limit
            $login_attempts++;
            if ($login_attempts >= 3) {
                $is_blocked = 1;
                echo "Your account has been blocked due to too many failed login attempts. Please contact the administrator.";
            } else {
                echo "Incorrect password. You have " . (3 - $login_attempts) . " attempts remaining.";
            }

            // Update login_attempts and is_blocked in the database
            $stmt->close();
            $stmt = $conn->prepare("UPDATE users SET login_attempts = ?, is_blocked = ? WHERE id = ?");
            $stmt->bind_param("iii", $login_attempts, $is_blocked, $id);
            $stmt->execute();
        }
    } else {
        echo "User not found.";
    }

    $stmt->close();
}

$conn->close();
?>



<!DOCTYPE html>
<html>

<head>
    <title>User Login</title>
    <!-- Add Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <h1>User Login</h1>
                <form action="login.php" method="post">
                    <div class="form-group">
                        <input type="text" name="username" placeholder="Username" required class="form-control">
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required class="form-control">
                    </div>
                    <div class="form-group">
                        <input type="submit" value="Login" class="btn btn-primary">
                    </div>
                </form>
                <div class="btn-group">
                    <button onclick="location.href='index.php'" class="btn btn-secondary">Back to Main Page</button>
                </div>
                
            </div>
        </div>
    </div>
</body>

</html>




