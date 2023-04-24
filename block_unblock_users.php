<?php
session_start();
require_once "config.php";

// Check if user is logged in as admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    // Redirect non-admin users to another page, e.g., index.php
    header("Location: index.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if ($action === 'block') {
        $is_blocked = 1;
    } elseif ($action === 'unblock') {
        $is_blocked = 0;
        $login_attempts=0;
    } else {
        echo "Invalid action.";
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET is_blocked = ?, login_attempts = ? WHERE id = ?");
    $stmt->bind_param("iii", $is_blocked, $login_attempts, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch users from the database
$users = [];
$query = "SELECT id, username, email, is_blocked FROM users";
$result = $conn->query($query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Block / Unblock Users</title>
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
        <h1 class="mt-4">Block / Unblock Users</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['is_blocked'] ? 'Blocked' : 'Active'; ?></td>
                        <td>
                            <form action="block_unblock_users.php" method="post">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <?php if ($user['is_blocked']): ?>
                                    <button type="submit" name="action" value="unblock" class="btn btn-success">Unblock</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="block" class="btn btn-danger">Block</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</body>

</html>
