<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is allowed to access this page
if (!isset($_SESSION['email']) || !isset($_SESSION['otp_allowed']) || $_SESSION['otp_allowed'] !== true) {
    header("Location: register.php");
    exit();
}

// Unset the OTP access flag to prevent re-accessing this page
unset($_SESSION['otp_allowed']);

// Generate OTP
$otp = rand(100000, 999999);
$_SESSION['otp'] = $otp;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send OTP</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="inc/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <script>
        // Show SweetAlert while sending OTP
        Swal.fire({
            title: 'Sending OTP...',
            text: 'Please wait while we send the OTP to your email.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Use JavaScript to trigger the PHP OTP sending process
        setTimeout(() => {
            window.location.href = 'process_send_otp.php';
        }, 1000); // Redirect to the PHP processing script after 1 second
    </script>
</body>
</html>