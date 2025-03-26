<?php
session_start();
require __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is allowed to access this page
if (!isset($_SESSION['email']) || !isset($_SESSION['otp'])) {
    header("Location: register.php");
    exit();
}

// Send OTP via email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
    $mail->SMTPAuth = true;
    $mail->Username = 'cevangelista2021@student.nbscollege.edu.ph'; // SMTP username
    $mail->Password = 'bzid uvxz qmys xqjq'; // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('cevangelista2021@student.nbscollege.edu.ph', 'Library System');
    $mail->addAddress($_SESSION['email']);
    $mail->addReplyTo('nbsclibrary-noreply@nbcollege.edu.ph', 'No Reply'); // Set the Reply-To address to a "noreply" email

    $mail->isHTML(true);
    $mail->Subject = 'Email Verification OTP';
    $mail->Body = "Your OTP for email verification is: <b>{$_SESSION['otp']}</b>";

    $mail->send();

    // Redirect to verify_otp.php with a success message
    $_SESSION['success'] = "OTP has been sent to your email. Please verify.";
    header("Location: verify_otp.php");
    exit();
} catch (Exception $e) {
    // Redirect to register.php with an error message
    $_SESSION['error'] = "OTP could not be sent. Mailer Error: {$mail->ErrorInfo}";
    header("Location: register.php");
    exit();
}
?>