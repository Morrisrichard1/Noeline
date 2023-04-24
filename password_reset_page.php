<?php
require_once "config.php";

$password_error = $confirm_password_error = "";
$password_updated = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["password"]) || empty($_POST["confirm_password"])) {
        $password_error = "Password fields cannot be empty.";
    } else {
        $token = $_POST["token"];
        $password = $_POST["password"];
        $confirm_password = $_POST["confirm_password"];

        if ($password !== $confirm_password) {
            $confirm_password_error = "Passwords do not match.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL WHERE password_reset_token = ?");
            $stmt->bind_param("ss", $hashed_password, $token);

            if ($stmt->execute()) {
                $password_updated = true;
            } else {
                $password_error = "Invalid token or token has expired.";
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Password Reset</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <h1 class="mt-4">Password Reset</h1>
        <form action="password_reset_page.php" method="post">
            <!-- Add the token as a hidden input -->
            <input type="hidden" id="token" name="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>">

            <div class="form-group">
                <label for="password">New Password:</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="New Password"
                    required>
                <span class="text-danger">
                    <?php echo $password_error; ?>
                </span>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                    placeholder="Confirm Password" required>
                <span class="text-danger">
                    <?php echo $confirm_password_error; ?>
                </span>
            </div>
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
        <?php if ($password_updated): ?>
            <div class="alert alert-success mt-3">Your password has been updated. <a href="login.php">Click here to log
                    in</a>.</div>
        <?php endif; ?>
    </div>
</body>

</html>
