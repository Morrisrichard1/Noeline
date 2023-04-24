<?php
session_start();
require_once "config.php";

// Check if user is logged in as admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    // Redirect non-admin users to another page, e.g., index.php
    header("Location: index.php");
    exit;
}


// Fetch security questions from the database
$questions = [];
$query = "SELECT id, question FROM security_question_options";
$result = $conn->query($query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>View Security Questions</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
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
        <h1 class="mt-4">View Security Questions</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Question</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $question): ?>
                    <tr>
                        <td><?php echo $question['id']; ?></td>
                        <td><?php echo $question['question']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</body>

</html>
