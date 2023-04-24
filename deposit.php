<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    echo "You must log in to make a deposit.";
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the account_id and account_number of the user from the database
$stmt = $conn->prepare("SELECT id, account_number FROM accounts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($account_id, $account_number);
$stmt->fetch();
$stmt->close();

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];
    $description = $_POST['description'];

    // Check if the user has entered the amount and description
    if (empty($amount) || empty($description)) {
        $message = "Please fill in all the required fields.";
        $message_type = "alert-danger";
    } else {
        // Update the account balance
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE user_id = ?");
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();
        $stmt->close();

        // Get the updated account balance
        $stmt = $conn->prepare("SELECT balance FROM accounts WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($new_balance);
        $stmt->fetch();
        $stmt->close();

        // Add a new transaction record
        $type = "deposit";
        $stmt = $conn->prepare("INSERT INTO transactions (account_id, type, amount, description, running_balance) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsd", $account_id, $type, $amount, $description, $new_balance);
        $stmt->execute();
        $stmt->close();

        $message = "Deposit successful!";
        $message_type = "alert-success";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Deposit</title>
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
                <div class="card">
                    <div class="card-header text-center">
                        <h1>Make a Deposit</h1>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)) : ?>
                            <div class="alert <?php echo $message_type; ?>">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group">
                                <label for="amount">Amount:</label>
                                <input type="number" name="amount" id="amount" step="0.01" min="0" class="form-control">
</div>
<div class="form-group">
<label for="description">Description:</label>
<input type="text" name="description" id="description" class="form-control">
</div>
<input type="submit" value="Make Deposit" class="btn btn-primary">
</form>
</div>
<div class="text-center mt-3">
<a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
</div>
</div>
</div>
</div>
</div>

</body>
</html>
