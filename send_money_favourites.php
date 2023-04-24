<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    echo "You must log in to send money to your favorite accounts.";
    exit;
}

$user_id = $_SESSION['user_id'];
$sender_account_id = null;
$recipient_account_id = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recipient_account_number = $_POST["recipient_account_number"];
    $amount = $_POST["amount"];

    // Fetch sender's account id and balance
    $stmt = $conn->prepare("SELECT id, balance FROM accounts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($sender_account_id, $sender_balance);
    $stmt->fetch();
    $stmt->close();

    // Fetch recipient's account id
    $stmt = $conn->prepare("SELECT id FROM accounts WHERE account_number = ?");
    $stmt->bind_param("s", $recipient_account_number);
    $stmt->execute();
    $stmt->bind_result($recipient_account_id);
    $stmt->fetch();
    $stmt->close();

     // Fetch recipient's current balance
     $stmt = $conn->prepare("SELECT balance FROM accounts WHERE account_number = ?");
     $stmt->bind_param("s", $recipient_account_number);
     $stmt->execute();
     $stmt->bind_result($recipient_balance);
     $stmt->fetch();
     $stmt->close();

     // Check if the amount is less than or equal to the sender's balance
    if ($amount <= $sender_balance) {
        $conn->autocommit(false);

        // Calculate the updated balance for sender and recipient
        $updated_sender_balance = $sender_balance - $amount;
        $updated_recipient_balance = $recipient_balance + $amount;
        


        // Deduct amount from sender's account
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE user_id = ?");
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        // Add amount to recipient's account
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE account_number = ?");
        $stmt->bind_param("ds", $amount, $recipient_account_number);
        $stmt->execute();
        $affected_rows += $stmt->affected_rows;
        $stmt->close();

        if ($affected_rows == 2) {
            

       
    

    


    // Insert transaction records
    $sender_description = "Transfer to account: " . $recipient_account_number;
    $recipient_description = "Received from account: " . $sender_account_id;

        // Insert sender transaction record
        $stmt = $conn->prepare("INSERT INTO transactions (account_id, type, amount, description, running_balance, timestamp) VALUES (?, 'withdrawal', ?, ?, ?, NOW())");
        $stmt->bind_param("idsd", $sender_account_id, $amount, $sender_description, $updated_sender_balance);
        $stmt->execute();
        $stmt->close();

            // Insert recipient transaction record
            $stmt = $conn->prepare("INSERT INTO transactions (account_id, type, amount, description, running_balance, timestamp) VALUES (?, 'deposit', ?, ?, ?, NOW())");
            $stmt->bind_param("idsd", $recipient_account_id, $amount, $recipient_description, $updated_recipient_balance);
            $stmt->execute();
            $stmt->close();

    $conn->commit();
    $_SESSION["success_message"] = "Money has been sent successfully!";
} else {
    $conn->rollback();
    $_SESSION["error_message"] = "Something went wrong, please try again.";
}
    
    $conn->close();
    header("Location: send_money_favourites.php");
    exit;
}}

// Fetch favourite accounts
$favourite_accounts = [];
$stmt = $conn->prepare("SELECT account_number FROM favorites WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fav_account_number);

while ($stmt->fetch()) {
$favourite_accounts[] = $fav_account_number;
}

$stmt->close();

// Fetch account names
$account_names = [];
foreach ($favourite_accounts as $fav_account_number) {
$stmt = $conn->prepare("SELECT users.username FROM users INNER JOIN accounts ON users.id = accounts.user_id WHERE accounts.account_number = ?");
$stmt->bind_param("s", $fav_account_number);
$stmt->execute();
$stmt->bind_result($account_name);

if ($stmt->fetch()) {
    $account_names[$fav_account_number] = $account_name;
}

$stmt->close();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Send Money to Favourite Accounts</title>
    <!-- Add Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <!-- Add this code snippet right after the opening <body> tag -->
    

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-header text-center">
                    <h1>Send Money to Favourite Accounts</h1>
                </div>
                <div class="card-body">
                <?php if (isset($_SESSION["success_message"])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION["success_message"]; ?>
        </div>
        <?php unset($_SESSION["success_message"]); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION["error_message"])): ?>
    <div class="alert alert-danger">
        <?php echo $_SESSION["error_message"]; ?>
    </div>
    <?php unset($_SESSION["error_message"]); ?>
<?php endif; ?>
                    <form action="send_money_favourites.php" method="post">
                        <div class="form-group">
                            <label for="fav_account_number">Select Favourite Account:</label>
                            <select name="recipient_account_number" id="fav_account_number" class="form-control"
                                required>
                                <option value="">Select an account</option>
                                <?php foreach ($account_names as $fav_account_number => $account_name): ?>
                                    <option value="<?php echo $fav_account_number; ?>"><?php echo $account_name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="number" name="amount" step="0.01" min="0.01" placeholder="Amount" required
                                class="form-control">
                        </div>
                        <input type="submit" value="Send Money" class="btn btn-primary">
                        <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                        </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
