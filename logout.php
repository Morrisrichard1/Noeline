<?php
session_start();

// Unset session variables
if (isset($_SESSION['user_id'])) {
    unset($_SESSION['user_id']);
}

if (isset($_SESSION['username'])) {
    unset($_SESSION['username']);
}

// Destroy the session
session_destroy();

// Redirect the user to the login page
header("Location: login.php");
exit;
?>
