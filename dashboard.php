<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    echo "You must log in to access the dashboard.";
    exit;
}

$user_id = $_SESSION['user_id'];

// Retrieve the account balance from the database
$stmt = $conn->prepare("SELECT account_number, balance FROM accounts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($account_number, $balance);
$stmt->fetch();
$stmt->close();

?>

<!DOCTYPE html>
<html>

<head>
    <title>Dashboard</title>
    <!-- Add Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card {
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
        }

        .rounded-circle {
            height: 80px;
            width: 80px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-4 offset-md-4">
                <div class="text-center">
                    <img src="path/to/your/profile-picture.jpg" alt="Profile Picture" class="rounded-circle mb-3">
                </div>
                <div class="card">
                    <div class="card-header text-center">
                        <h1>Dashboard</h1>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_SESSION['success_message'])) {
                            echo "<div class='alert alert-success'>" . $_SESSION['success_message'] . "</div>";
                            unset($_SESSION['success_message']);
                        }
                        ?>
                        <h2 class="text-center">Welcome,
                            <?php echo $_SESSION['username']; ?>
                        </h2>
                        <hr>
                        <p><strong>Account Number:</strong>
                            <?php echo $account_number; ?>
                        </p>
                        <p><strong>Balance:</strong> UGshs 
                            <?php echo number_format($balance, 2); ?>
                        </p>

                        <h3 class="text-center">Actions</h3>
                        <div class="list-group mb-3">
                            <a href="deposit.php" class="list-group-item list-group-item-action">Deposit Money</a>
                            <a href="pay_bills.php" class="list-group-item list-group-item-action">Pay Bills</a>
                            <a href="transfer_funds.php" class="list-group-item list-group-item-action">Transfer
                                Funds</a>
                            <a href="send_money_favourites.php" class="list-group-item list-group-item-action">Transfer
                                Funds to Favorite</a>
                            <a href="bank_statement.php" class="list-group-item list-group-item-action">View Bank
                                Statement</a>
                        </div>

                        <div class="text-center">
                            <a href="logout.php" class="btn btn-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>