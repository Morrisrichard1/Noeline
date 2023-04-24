<?php
session_start();
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SESSION['token'];
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate input
    if (empty($new_password) || empty($confirm_password)) {
        echo "Please fill in all the required fields.";
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo "Passwords do not match.";
        exit;
    }

    // Update the password in the database
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ?, password_reset_token =
NULL WHERE password_reset_token = ?");
    $stmt->bind_param("ss", $hashed_password, $token);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo "Your password has been updated. Please <a href='login.php'>log in</a> with your new password.";
    } else {
        echo "An error occurred. Please try again.";
    }

    $stmt->close();
    unset($_SESSION['token']);
} else {
    if (isset($_GET['token'])) {
        $_SESSION['token'] = $_GET['token'];
    } else {
        echo "Invalid password reset token.";
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>New Password</title>
    <!-- Add Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Add jQuery and Bootstrap JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>

<body>
    <div class="container">
        <h1>Set New Password</h1>
        <form action="new_password.php" method="post">
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" class="form-control"
                    placeholder="New Password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                    placeholder="Confirm New Password" required>
            </div>
            <input type="submit" value="Submit" class="btn btn-primary">
        </form>
        <a href="login.php" class="btn btn-secondary mt-3">Back to Login</a>
    </div>
</body>

</html>