<?php
session_start();

// Reset all values related to the add-book form
unset($_SESSION['reset_book_form']);
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

// Clear all session data and destroy the session
session_unset();
session_destroy();

// Redirect to the login page
header("Location: index.php");
exit();
?>