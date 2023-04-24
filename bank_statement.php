<?php
session_start();
require_once "config.php";

function generateReferenceNumber()
{
    return 'REF-' . strtoupper(substr(md5(uniqid(mt_rand())), 0, 8));
}

if (!isset($_SESSION['user_id'])) {
    echo "You must log in to view your bank statement.";
    exit;
}

$user_id = $_SESSION['user_id'];

// Default date range: the first day of the current month and the next day after the current day
$start_date = date("Y-m-01");
$end_date = date("Y-m-d",strtotime("+1 day"));

// Check if a date range was submitted
if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
}

// Retrieve the account number and current balance from the database
$stmt_account = $conn->prepare("SELECT account_number, balance FROM accounts WHERE user_id = ?");
$stmt_account->bind_param("i", $user_id);
$stmt_account->execute();
$stmt_account->bind_result($account_number, $current_balance);
$stmt_account->fetch();
$stmt_account->close();

// Retrieve the balance at the start date from the database
$start_date_previous_day = date("Y-m-d", strtotime($start_date . " -1 day")); 

$stmt_previous_balance = $conn->prepare("SELECT running_balance FROM transactions t JOIN accounts a ON t.account_id = a.id WHERE a.user_id = ? AND t.timestamp <= ? ORDER BY t.timestamp DESC LIMIT 1");
$stmt_previous_balance->bind_param("is", $user_id, $start_date_previous_day);
$stmt_previous_balance->execute();
$stmt_previous_balance->bind_result($previous_balance);
$stmt_previous_balance->fetch();
$stmt_previous_balance->close();

// Retrieve the transactions from the database
$stmt = $conn->prepare("SELECT t.id, t.type, t.amount, t.description, t.reference, t.running_balance, t.timestamp FROM transactions t JOIN accounts a ON t.account_id = a.id WHERE a.user_id = ? AND t.timestamp BETWEEN ? AND ? ORDER BY t.timestamp");
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$stmt->bind_result($transaction_id, $type, $amount, $description, $reference, $running_balance, $timestamp);

$transactions = [];
while ($stmt->fetch()) {
    $transactions[] = [
        'id' => $transaction_id,
        'type' => $type,
        'amount' => $amount,
        'description' => $description,
        'reference' => $reference,
        'running_balance' => $running_balance,
        'timestamp' => $timestamp,
    ];
}
$stmt->close();

?>


<!DOCTYPE html>
<html>

<head>
    <title>Bank Statement</title>
    <!-- Add Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        @media print {
            .buttons {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <table class="table">
                    <tr>
                        <td>
                            <img src="doge.png" alt="Bank Logo" width="80">
                            <br>
                            <strong>DOGE Bank</strong>
                            <br>
                            Starz Lili
                        </td>
                        <td style="text-align: right;">
                            <strong>CHEQUING ACCOUNT STATEMENT</strong>
                            <br>
                            Page 1 of 1
                            <br>
                            Statement Period:
                            <?php echo $start_date . " - " . $end_date; ?>
                            <br>
                            Account Number:
                            <?php echo $account_number; ?>
                        </td>
                    </tr>
                </table>

                <h1>Bank Statement</h1>

                <!-- Add a date selection form -->
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="start_date">Start date:</label>
                            <input type="date" name="start_date" value="<?php echo $start_date; ?>"
                                class="form-control">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="end_date">End date:</label>
                            <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-control">
                        </div>
                        <div class="form-group col-md-3 align-self-end">
                            <input type="submit" value="Show" class="btn btn-primary">
                        </div>
                    </div>
                </form>
                <table class="table table-bordered">
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Ref</th>
                        <th>Withdrawals</th>
                        <th>Deposits</th>
                        <th>Balance</th>
                    </tr>

                    <?php
                    $total_withdrawals = 0;
                    $total_deposits = 0;
                    $final_balance = 0;
                    // Show the previous balance
                    echo "<tr><td>{$start_date_previous_day}</td><td><strong>Previous Balance</strong></td><td></td><td></td><td></td><td><strong>{$previous_balance}</strong></td></tr>";

                    foreach ($transactions as $transaction) {
                        $transaction_id = $transaction['id'];
                        $type = $transaction['type'];
                        $amount = $transaction['amount'];
                        $description = $transaction['description'];
                        $reference = $transaction['reference'];
                        $running_balance = $transaction['running_balance'];
                        $timestamp = $transaction['timestamp'];

                        $withdrawals = ($type === 'withdrawal' || $type === 'debit' || $type === 'transfer') ? $amount : '';
                        $deposits = ($type === 'deposit' || $type === 'transfer') ? $amount : '';

                        $formatted_timestamp = date('Y-m-d', strtotime($timestamp));

                        if ($reference === '') {
                            $reference = generateReferenceNumber();

                            $stmt_update_ref = $conn->prepare("UPDATE transactions SET reference = ? WHERE id = ?");
                            $stmt_update_ref->bind_param("si", $reference, $transaction_id);
                            $stmt_update_ref->execute();
                            $stmt_update_ref->close();
                        }

                        if ($type === 'withdrawal' || $type === 'debit' || $type === 'transfer') {
                            $total_withdrawals += $amount;
                        } else {
                            $total_deposits += $amount;
                        }

                        $final_balance = $running_balance;

                        echo "<tr><td>{$formatted_timestamp}</td><td>{$description}</td><td>{$reference}</td><td>{$withdrawals}</td><td>{$deposits}</td><td>{$running_balance}</td></tr>";
                    }

                    // Show the ending balance
                    $end_date_last_day = date("Y-m-t", strtotime($end_date));
                    $formatted_end_date_last_day = date('Y-m-d', strtotime($end_date_last_day));
                    echo "<tr><td>{$formatted_end_date_last_day}</td><td><strong>Ending Balance</strong></td><td></td><td></td><td></td><td><strong>{$final_balance}</strong></td></tr>";

                    ?>
                    <tr>
                        <td colspan="3" style="text-align:right;"><strong>Totals:</strong></td>
                        <td><strong><?php echo number_format($total_withdrawals, 2); ?></strong></td>
                        <td><strong><?php echo number_format($total_deposits, 2); ?></strong></td>
                        <td></td>
                    </tr>
                    
                </table>
                <br>
                <div class="buttons">
                    <button onclick="window.print()" class="btn btn-primary">Print PDF</button>
                    <button onclick="location.href='dashboard.php'" class="btn btn-secondary">Back to Dashboard</button>
                </div>
            </div>
        </div>
    </div>
    <?php

    $conn->close();
    ?>
</body>

</html>







