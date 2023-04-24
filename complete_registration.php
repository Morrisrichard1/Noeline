<?php
require_once "config.php";

$password_error = $confirm_password_error = $questions_error = '';
$registration_complete = false;

// Fetch security questions from the database
$sql = "SELECT * FROM security_question_options";
$result = $conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_GET['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $question1 = $_POST['question1'];
    $answer1 = $_POST['answer1'];
    $question2 = $_POST['question2'];
    $answer2 = $_POST['answer2'];

    if (empty($password) || empty($confirm_password) || empty($question1) || empty($answer1) || empty($question2) || empty($answer2)) {
        $questions_error = "Please fill in all the fields.";
    } elseif ($password !== $confirm_password) {
        $confirm_password_error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Get the user ID using the registration token
        $stmt = $conn->prepare("SELECT id FROM users WHERE registration_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_id = $row['id'];

            $stmt = $conn->prepare("UPDATE users SET password = ?, registration_token = NULL, is_blocked = 0 WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);

            if ($stmt->execute()) {
                // Save security questions and answers
                $stmt2 = $conn->prepare("INSERT INTO security_questions (user_id, question, answer) VALUES (?, ?, ?), (?, ?, ?)");
                $stmt2->bind_param("isssss", $user_id, $question1, $answer1, $user_id, $question2, $answer2);
                $stmt2->execute();
                $stmt2->close();

                $registration_complete = true;
            } else {
                $questions_error = "Error updating password.";
            }
            $stmt->close();
        } else {
            $questions_error = "Invalid token or token has expired.";
        }
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html>

<head>
    <title>Complete Registration</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <h1 class="mt-4">Complete Registration</h1>
        <form action="" method="post">
            <!-- Remaining form elements (password, confirm password, etc.) -->

            <div class="form-group">
                <label for="question1">Security Question 1:</label>
                <select id="question1" name="question1" class="form-control" required>
                    <option value="">Select a security question</option>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <option value="<?php echo $row['question']; ?>"><?php echo $row['question']; ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="text" id="answer1" name="answer1" class="form-control mt-2" placeholder="Answer for Security Question 1" required>
            </div>
            <div class="form-group">
                <label for="question2">Security Question 2:</label>
                <select id="question2" name="question2" class="form-control" required>
                <option value="">Select a security question</option>
                    <?php
                    // Reset the result pointer to fetch the questions again
                    $result->data_seek(0);
                    while ($row = $result->fetch_assoc()): ?>
                        <option value="<?php echo $row['question']; ?>"><?php echo $row['question']; ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="text" id="answer2" name="answer2" class="form-control mt-2" placeholder="Answer for Security Question 2" required>
            </div>
            <div class="form-group">
            <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
            <?php if (!empty($password_error)): ?>
                <div class="alert alert-danger mt-2"><?php echo $password_error; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
            <?php if (!empty($confirm_password_error)): ?>
                <div class="alert alert-danger mt-2"><?php echo $confirm_password_error; ?></div>
            <?php endif; ?>
        </div>

        <input type="submit" class="btn btn-primary" value="Submit">

        <?php if (!empty($questions_error)): ?>
            <div class="alert alert-danger mt-2"><?php echo $questions_error; ?></div>
        <?php endif; ?>

        <?php if ($registration_complete): ?>
            <div class="alert alert-success mt-2">Registration complete. <a href="login.php">Click here to login</a></div>
        <?php endif; ?>
    </form>
</div>
</body>
</html>                  
