<?php
session_start();
require __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['email'])) {
  header("Location: register.php");
  exit();
}

// Generate OTP
$otp = rand(100000, 999999);
$_SESSION['otp'] = $otp;

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

  $mail->isHTML(true);
  $mail->Subject = 'Email Verification OTP';
  $mail->Body = "Your OTP for email verification is: <b>$otp</b>";

  $mail->send();
  $success = "OTP has been sent to your email. Please verify.";
  $redirect = "verify_otp.php";
} catch (Exception $e) {
  $error = "OTP could not be sent. Mailer Error: {$mail->ErrorInfo}";
  $redirect = "register.php";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Send OTP</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <script>
    <?php if (isset($success)): ?>
      Swal.fire({
        title: 'Success!',
        text: '<?php echo $success; ?>',
        icon: 'success'
      }).then(function() {
        window.location.href = '<?php echo $redirect; ?>';
      });
    <?php elseif (isset($error)): ?>
      Swal.fire({
        title: 'Error!',
        text: '<?php echo $error; ?>',
        icon: 'error'
      }).then(function() {
        window.location.href = '<?php echo $redirect; ?>';
      });
    <?php endif; ?>
  </script>
</body>
</html>