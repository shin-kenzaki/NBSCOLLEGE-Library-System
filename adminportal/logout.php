<?php
session_start();
session_unset();
session_destroy();

// Prevent back navigation
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: 0");
header("Pragma: no-cache");

// Redirect to login page
header("Location: login.php");
exit;
?>
