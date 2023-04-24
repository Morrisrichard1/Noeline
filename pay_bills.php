<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    echo "You must log in to pay bills.";
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the first account number of the user from the database
$stmt_account_number = $conn->prepare("SELECT account_number FROM accounts WHERE user_id = ? ORDER BY id LIMIT 1");
$stmt_account_number->bind_param("i", $user_id);
$stmt_account_number->execute();
$stmt_account_number->bind_result($account_number);
if (!$stmt_account_number->fetch()) {
    echo "Failed to fetch the user's account number.";
    exit;
}
$stmt_account_number->close();


$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bill_id = $_POST['bill_type']; // Corrected the variable name and array key here
    $amount = $_POST['amount'];
    $description = $_POST['description'];

    // Get the bill account number from the database
    $stmt_bill_account = $conn->prepare("SELECT account_number FROM bills WHERE id = ?");
    $stmt_bill_account->bind_param("i", $bill_id);
    $stmt_bill_account->execute();
    $stmt_bill_account->bind_result($bill_account_number);
    if (!$stmt_bill_account->fetch()) {
        $message = "Invalid bill account number.";
        $message_type = "alert-danger";
    }
    $stmt_bill_account->close();

    if (empty($bill_account_number) || empty($amount) || empty($description)) {
        $message = "Please fill in all the required fields.";
        $message_type = "alert-danger";
    } else {
        // Check if the user has enough balance
        $stmt = $conn->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_number = ?");
        $stmt->bind_param("is", $user_id, $account_number);
        $stmt->execute();
        $stmt->bind_result($account_id, $balance);
        $stmt->fetch();
        $stmt->close();

        if ($balance >= $amount) {
            // Deduct the amount from the user's account
            $new_balance = $balance - $amount;
            $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
            $stmt->bind_param("di", $new_balance, $account_id);
            $stmt->execute();
            $stmt->close();

            // Add a new transaction record
            $type = "withdrawal";
            $stmt = $conn->prepare("INSERT INTO transactions (account_id, type, amount, description, running_balance) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdsd", $account_id, $type, $amount, $description, $new_balance);
            $stmt->execute();
            $stmt->close();

            $message = "Bill payment successful!";
            $message_type = "alert-success";
        } else {
            $message = "Insufficient balance!";
            $message_type = "alert-danger";
        }
    }
}
$stmt = $conn->prepare("SELECT id, name FROM bills ORDER BY name");
$stmt->execute();
$stmt->bind_result($bill_type_id, $bill_type_name);
$bills = [];
while ($stmt->fetch()) {
    $bills[$bill_type_id] = $bill_type_name;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>

<head>
<title>Pay Bills</title>
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
                        <h1>Pay Bills</h1>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)) : ?>
                            <div class="alert <?php echo $message_type; ?>">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group">
                                <label for="bill_type">Bill Type:</label>
                                <select name="bill_type" id="bill_type" class="form-control">
                                    <?php
                                    foreach ($bills as $id => $name) {
                                        echo "<option value='{$id}'>{$name}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount:</label>
                                <input type="number" name="amount" id="amount" step="0.01" min="0" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="description">Description:</label>
                                <input type="text" name="description" id="description" class="form-control">
                            </div>
                            <input type="submit" value="Pay Bill" class="btn btn-primary">
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

