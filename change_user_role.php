<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change User Role</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    
    <?php
    session_start();

    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
        header("Location: index.php");
        exit;
    }

    require_once 'config.php';

    if (isset($_POST['submit'])) {
        $id = $_POST['id'];
        $user_role = $_POST['user_role'];

        $sql = "UPDATE users SET user_role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_role, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "User role updated successfully!";
        } else {
            $message = "Error updating user role!";
        }
        $stmt->close();
    }
    ?>
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
        <h1 class="mt-4">Change User Role</h1>
        <?php
        if (isset($message)) {
            echo '<div class="alert alert-info">' . $message . '</div>';
        }
        ?>
        <form action="change_user_role.php" method="post">
            <div class="form-group">
                <label for="id">User ID:</label>
                <input type="number" class="form-control" id="id" name="id" required>
            </div>
            <div class="form-group">
                <label for="user_role">Role:</label>
                <select class="form-control" id="user_role" name="user_role">
                    <option value="0">Regular User</option>
                    <option value="1">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" name="submit">Submit</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
