<?php
session_start();

// Check which ID exists before destroying session
if (isset($_SESSION['schooluser_id'])) {
    $redirect_url = "school\index.php";
} elseif (isset($_SESSION['outsider_id'])) {
    $redirect_url = "outsiders\index.php";
}

// Destroy the session
session_unset();
session_destroy();

// Redirect to the determined URL
header("Location: $redirect_url");
exit();
