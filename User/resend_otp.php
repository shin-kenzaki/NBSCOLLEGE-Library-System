<?php
session_start();
require '../db.php';
require __DIR__ . "/mailer.php";

$response = ['success' => false, 'message' => ''];

if (isset($_SESSION['email'])) {
    $current_time = time();
    if (!isset($_SESSION['resend_time']) || ($current_time - $_SESSION['resend_time']) >= 60) {
        // Generate new OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;

        try {
            $mail->setFrom('cevangelista2021@student.nbscollege.edu.ph', 'Library System');
            $mail->addAddress($_SESSION['email']);

            $mail->Subject = 'Email Verification OTP';
            $mail->Body = "Your OTP for email verification is: <b>{$_SESSION['otp']}</b>";

            $mail->send();
            $_SESSION['resend_time'] = $current_time; // Update the resend time
            $response['success'] = true;
            $response['message'] = 'A new OTP has been sent to your email.';
        } catch (Exception $e) {
            $response['message'] = "OTP could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $response['message'] = 'Please wait before requesting another OTP.';
    }
} else {
    $response['message'] = 'Session expired. Please log in again.';
}

echo json_encode($response);