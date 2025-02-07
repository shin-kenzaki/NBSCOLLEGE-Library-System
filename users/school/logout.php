<?php
session_start();
session_unset();
session_destroy();

// Prevent navigating back after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: 0");
header("Pragma: no-cache");

// Redirect to select user type page
header("Location: ../select_usertype.php");
exit;
?>  
