<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    echo "You must log in to view this page.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $recipient_account_number = $_POST['recipient_account_number'];
    $add_favourite = $_POST['add_favourite'];

    if ($add_favourite === 'yes') {
        $stmt = $conn->prepare("INSERT INTO favorites (user_id, account_number) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $recipient_account_number);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = "User has been successfully added to the favourite.";
    }

    header("Location: transfer_funds.php");
    exit;
}

$recipient_account_number = $_GET['recipient_account_number'];

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Transfer Confirmation</title>
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
                        <h1>Transfer Successful</h1>
                    </div>
                    <div class="card-body">
                        <p>Do you want to add the account number
                            <?php echo $recipient_account_number; ?> to your favourites?
                        </p>
                        <form action="transfer_confirmation.php" method="post">
                            <input type="hidden" name="recipient_account_number"
                                value="<?php echo $recipient_account_number; ?>">
                            <button type="submit" name="add_favourite" value="yes" class="btn btn-primary">Yes</button>
                            <button type="submit" name="add_favourite" value="no" class="btn btn-secondary">No</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>