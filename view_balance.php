<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    echo "You must log in to view your balance.";
    exit;
}

$user_id = $_SESSION['user_id'];

// Retrieve the account balance from the database
$stmt = $conn->prepare("SELECT account_number, balance FROM accounts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($account_number, $balance);
    $stmt->fetch();
    echo "Account number: {$account_number}<br>";
    echo "Account balance: {$balance}";
} else {
    echo "Account not found.";
}

$stmt->close();
$conn->close();