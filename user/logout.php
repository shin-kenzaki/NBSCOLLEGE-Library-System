<?php
session_start();

// Only unset user-specific session variables
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_type']);

// Redirect to the user login page
header("Location: index.php");
exit();
?>