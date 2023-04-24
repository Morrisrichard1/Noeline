<?php
require_once "config.php";

$security_question_error = '';
$answer_error_1 = '';
$answer_error_2 = '';
$email_sent = false;

// Fetch security questions from the database
$security_questions = [];
$query = "SELECT question FROM security_question_options";
$result = $conn->query($query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $security_questions[] = $row['question'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $security_question_1 = $_POST['security_question_1'];
    $security_question_2 = $_POST['security_question_2'];
    $answer_1 = $_POST['answer_1'];
    $answer_2 = $_POST['answer_2'];

    // Validate input
    if (empty($email) || empty($security_question_1) || empty($security_question_2) || empty($answer_1) || empty($answer_2)) {
        $security_question_error = "Please fill in the required fields.";
    }

    // Check the user in the database
    $stmt = $conn->prepare("SELECT users.id, users.username, users.email, security_questions.question, security_questions.answer FROM users INNER JOIN security_questions ON users.id = security_questions.user_id WHERE users.email = ? AND (security_questions.question = ? OR security_questions.question = ?)");
    $stmt->bind_param("sss", $email, $security_question_1, $security_question_2);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $email, $question, $stored_answer);

        $answer_1_correct = false;
        $answer_2_correct = false;

        while ($stmt->fetch()) {
            if ($question === $security_question_1 && strcasecmp($answer_1, $stored_answer) === 0) {
                $answer_1_correct = true;
            } elseif ($question === $security_question_2 && strcasecmp($answer_2, $stored_answer) === 0) {
                $answer_2_correct = true;
            }
        }

        if ($answer_1_correct && $answer_2_correct) {
            // Generate a token and store it in the database
            $token = bin2hex(random_bytes(32));
            $stmt2 = $conn->prepare("UPDATE users SET password_reset_token = ? WHERE id = ?");
            $stmt2->bind_param("si", $token, $id);
            $stmt2->execute();

            // Send the password reset link to the user's email
            require 'vendor/autoload.php'; // Adjust the path to the autoload.php file according to your installation

            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->SMTPDebug = 0; // Enable verbose debug output
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'sender_email'; // Replace with your Gmail address
            $mail->Password = 'sender_pass'; // Replace with your Gmail password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('sender_email', 'admin'); // Replace with your "From" email address and name
            $mail->addAddress($email, $username);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset';
            $reset_link = $_SERVER['HTTP_HOST'] . "/asg/password_reset_page.php?token=$token";

            $mail->Body = "Hi $username,\n\nPlease click on the link below to reset your password: <br><br><a href='$reset_link'>$reset_link</a>";

            if (!$mail->send()) {
                echo 'Mailer Error: ' . $mail->ErrorInfo;
            } else {
                $email_sent = true;
            }

        } else {
            if (!$answer_1_correct) {
                $answer_error_1 = "Incorrect answer for Security Question 1.";
            }
            if (!$answer_2_correct) {
                $answer_error_2 = "Incorrect answer for Security Question 2.";
            }
        }

    } else {
        $security_question_error = "Email not found or security questions don't match.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <h1 class="mt-4">Reset Password</h1>
        <form action="reset_password.php" method="post">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="form-group">
                <label for="security_question_1">Security Question 1:</label>
                <select id="security_question_1" name="security_question_1" class="form-control" required>
                    <option value="">Choose a question</option>
                    <?php foreach ($security_questions as $question): ?>
                        <option value="<?php echo $question; ?>"><?php echo $question; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="answer_1">Answer 1:</label>
                <input type="text" id="answer_1" name="answer_1" class="form-control" placeholder="Answer 1" required>
                <span class="text-danger">
                    <?php echo $answer_error_1; ?>
                </span>
            </div>
            <div class="form-group">
                <label for="security_question_2">Security Question 2:</label>
                <select id="security_question_2" name="security_question_2" class="form-control" required>
                    <option value="">Choose a question</option>
                    <?php foreach ($security_questions as $question): ?>
                        <option value="<?php echo $question; ?>"><?php echo $question; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="answer_2">Answer 2:</label>
                <input type="text" id="answer_2" name="answer_2" class="form-control" placeholder="Answer 2" required>
                <span class="text-danger">
                    <?php echo $answer_error_2; ?>
                </span>
                <span class="text-danger">
                    <?php echo $security_question_error; ?>
                </span>

            </div>

            <div class="row">
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
                <div class="col-md-3">
                    <a href="login.php" class="btn btn-secondary">Back to Login</a>
                </div>
            </div>
        </form>
        <?php if ($email_sent): ?>
            <div class="alert alert-success mt-3">A password reset link has been sent to your email.</div>
        <?php endif; ?>
    </div>
</body>

</html>