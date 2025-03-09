<?php
session_start();
require '../db.php';

$email = $_POST["email"];

$token = bin2hex(random_bytes(16));
$token_hash = hash("sha256", $token);
$expiry = date("Y-m-d H:i:s", time() + 60 * 30);

$sql = "UPDATE admins
        SET reset_token = ?,
            reset_expires = ?
        WHERE email = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['alert_type'] = 'error';
    $_SESSION['alert_message'] = 'SQL error: ' . $conn->error;
    header("Location: forgot-password.php");
    exit();
}

$stmt->bind_param("sss", $token_hash, $expiry, $email);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $mail = require __DIR__ . "/mailer.php";

    $mail->setFrom("noreply@example.com", "Library System");
    $mail->addAddress($email);
    $mail->Subject = "Password Reset";

    $mail->isHTML(true);
    $encoded_token = urlencode($token);
    $mail->Body = <<<END
    Click <a href="http://localhost/Library-System/Admin/forgot-reset-password.php?token=$encoded_token">here</a>
    to reset your password.
    END;

    try {
        $mail->send();
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Password reset email sent successfully!';
    } catch (Exception $e) {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Message could not be sent. Error: ' . $mail->ErrorInfo;
    }
} else {
    $_SESSION['alert_type'] = 'error';
    $_SESSION['alert_message'] = 'No matching email found';
}

// Always redirect to `forgot-password.php` regardless of success or error
header("Location: forgot_password.php");
exit();
?>
