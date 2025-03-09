<?php

$email = $_POST["email"];

$token = bin2hex(random_bytes(16));

$token_hash = hash("sha256", $token);

$expiry = date("Y-m-d H:i:s", time() + 60 * 30);

$conn = require __DIR__ . "/../db.php";


$sql = "UPDATE admins
        SET reset_token = ?,
            reset_expires = ?
        WHERE email = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL error: " . $conn->error);  // Added for troubleshooting SQL issues
}

$stmt->bind_param("sss", $token_hash, $expiry, $email);

$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Database updated successfully.<br>";

    $mail = require __DIR__ . "/mailer.php";

    $mail->setFrom("noreply@example.com");
    $mail->addAddress($email);
    $mail->Subject = "Password Reset";

    $mail->isHTML(true);  // Important for clickable links
    $encoded_token = urlencode($token);
    $mail->Body = <<<END
    Click <a href="http://localhost/Library-System/Admin/forgot-reset-password.php?token=$encoded_token">here</a>
    to reset your password.
    END;


    try {
        $mail->send();
        echo "Message sent successfully!";
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    echo "No matching email found or no changes made.";
}
?>
