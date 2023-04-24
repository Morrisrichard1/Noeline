<?php
require_once "config.php";

$input_error = '';
$email_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);

    if (empty($email) || empty($name) || empty($username)) {
        $input_error = "Please fill in the required fields.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $input_error = "Email or username already exists. Please use different ones.";
        } else {
        // Auto-generate account number
        $account_number = mt_rand(10000000, 99999999);

        // Insert the user into the users table
        $stmt = $conn->prepare("INSERT INTO users (email, name, username, is_blocked) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $email, $name, $username);
        $stmt->execute();

        $user_id = $conn->insert_id;

        // Insert the account number into the accounts table
        $stmt_acc = $conn->prepare("INSERT INTO accounts (user_id, account_number) VALUES (?, ?)");
        $stmt_acc->bind_param("ii", $user_id, $account_number);
        $stmt_acc->execute();

        // Generate a token and store it in the database
        $token = bin2hex(random_bytes(32));
        $stmt2 = $conn->prepare("UPDATE users SET registration_token = ? WHERE id = ?");
        $stmt2->bind_param("si", $token, $user_id);
        $stmt2->execute();

        // Send the registration link to the user's email
        require 'vendor/autoload.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = '';
        $mail->Password = '';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('', '');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Complete Registration';
        $registration_link = $_SERVER['HTTP_HOST'] . "/asg/complete_registration.php?token=$token";

        $mail->Body = "Hi $name,\n\nPlease click on the link below to complete your registration:<br><br><a href='$registration_link'>$registration_link</a>";


        if (!$mail->send()) {
            echo 'Mailer Error: ' . $mail->ErrorInfo;
        } else {
            $email_sent = true;
        }
    }
}
}
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Create User Account</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Admin Dashboard</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
            <li class="nav-item">
                    <a class="nav-link" href="admin_create_user.php">Create Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_users.php">View Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_security_questions.php">View Security Questions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="block_unblock_users.php">Block / Unblock Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="change_user_role.php">Change User Roles</a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container">
        <h1 class="mt-4">Create User Account</h1>
        <form action="admin_create_user.php" method="post">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Name" required>
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Username" required>
            </div>
            <button type="submit" class="btn btn-primary">Create User</button>
</form>
<?php if (!empty($input_error)): ?>
    <div class="alert alert-danger mt-3"><?php echo $input_error; ?></div>
<?php endif; ?>

<?php if ($email_sent): ?>
<div class="alert alert-success mt-3">User account created and registration email sent.</div>
<?php endif; ?>
</div>

</body>
</html>
