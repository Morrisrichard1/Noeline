<?php
session_start();
require_once "config.php";

$message = '';

if (!isset($_SESSION['user_id'])) {
    echo "You must log in to transfer funds.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $recipient_account_number = trim($_POST['recipient_account_number']);
    $amount = floatval($_POST['amount']);

    // Validate input
    if (empty($recipient_account_number) || empty($amount) || $amount <= 0) {
        $message = "Please fill in all the required fields.";
    } else {
        // Check if the user has sufficient balance
        $stmt = $conn->prepare("SELECT id, account_number, balance FROM accounts WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($account_id, $account_number, $balance);
            $stmt->fetch();

            if ($balance < $amount) {
                $message = "Insufficient balance.";
            }
        } else {
            $message = "Account not found.";
        }

        $stmt->close();
    }

    if (empty($message)) {
        // Define recipient_balance with an initial value
        $recipient_balance = 0;

        // Check if the recipient account exists
        $stmt = $conn->prepare("SELECT id, balance FROM accounts WHERE account_number = ?");
        $stmt->bind_param("s", $recipient_account_number);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($recipient_account_id, $recipient_balance);
            $stmt->fetch();
            if ($account_number === $recipient_account_number) {
                $message = "You cannot transfer funds to your own account.";
            }
        } else {
            $message = "Recipient account not found.";
        }

        $stmt->close();

        if (empty($message)) {
            // Begin a transaction
            $conn->begin_transaction();

            // Update the sender's account balance
            $new_sender_balance = $balance - $amount;
            $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
            $stmt->bind_param("di", $new_sender_balance, $account_id);

            if (!$stmt->execute()) {
                $conn->rollback();
                echo "Error: " . $stmt->error;
                $stmt->close();
                exit;
            }

            $stmt->close();

            // Update the recipient's account balance
            $new_recipient_balance = $recipient_balance + $amount;
            $stmt = $conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
            $stmt->bind_param("di", $new_recipient_balance, $recipient_account_id);

            if (!$stmt->execute()) {
                $conn->rollback();
                echo "Error: " . $stmt->error;
                $stmt->close();
                exit;
            }

            $stmt->close();

            // Create a transaction record for the sender
            $stmt = $conn->prepare("INSERT INTO transactions (account_id, type, amount, description, running_balance) VALUES (?, 'withdrawal', ?, 'Transfer to {$recipient_account_number}', ?)");
            $stmt->bind_param("idd", $account_id, $amount, $new_sender_balance);

            if (!$stmt->execute()) {
                $conn->rollback();
                echo "Error: " . $stmt->error;
                $stmt->close();
                exit;



        }

        $stmt->close();

        // Create a transaction record for the recipient
        $stmt = $conn->prepare("INSERT INTO transactions (account_id, type, amount, description, running_balance) VALUES (?, 'deposit', ?, 'Received from {$account_number}', ?)");
        $stmt->bind_param("idd", $recipient_account_id, $amount, $new_recipient_balance);

        if (!$stmt->execute()) {
            $conn->rollback();
            echo "Error: " . $stmt->error;
            $stmt->close();
            exit;
        }

        $stmt->close();

        // Commit the transaction
        $conn->commit();

        // Redirect the user to the transfer confirmation page
        header("Location: transfer_confirmation.php?recipient_account_number={$recipient_account_number}");
        exit;
    }
}


}

$conn->close();
?>





<!DOCTYPE html>
<html>

<head>
    <title>Transfer Funds</title>
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
                        <h1>Transfer Funds</h1>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-danger">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form action="transfer_funds.php" method="post">
                            <div class="form-group">
                                <input type="text" name="recipient_account_number"
                                    placeholder="Recipient Account Number" required class="form-control">
                            </div>
                            <div class="form-group">
                                <input type="number" name="amount" step="0.01" min="0.01" placeholder="Amount" required
                                    class="form-control">
                            </div>
                            <input type="submit" value="Transfer" class="btn btn-primary">
                            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>