<?php
session_start();
require '../db.php';
require __DIR__ . "/mailer.php";

$success = $error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['verify'])) {
    $entered_otp = $_POST['otp'];

    if ($entered_otp == $_SESSION['otp']) {
      // Retrieve data from session
      $school_id = $_SESSION['school_id'];
      $firstname = $_SESSION['firstname'];
      $middle_init = $_SESSION['middle_init'];
      $lastname = $_SESSION['lastname'];
      $email = $_SESSION['email'];
      $hashed_password = $_SESSION['hashed_password'];
      $usertype = $_SESSION['usertype'];
      $image = $_SESSION['image'];

      // If all checks pass, proceed with insert
      $sql = "INSERT INTO users (school_id, firstname, middle_init, lastname, email, password,
              user_image, usertype, date_added)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssssss",
            $school_id, $firstname, $middle_init, $lastname, $email, $hashed_password, $image, $usertype);

        if ($stmt->execute()) {
          $_SESSION['success'] = "Registration successful! You can now login with your School ID and password.";
          $success = "Registration successful! You will be redirected to the login page.";
        } else {
          $error = "Something went wrong! Please try again.";
        }
      }
      $stmt->close();
    } else {
      $error = "Invalid OTP!";
    }
  } elseif (isset($_POST['resend'])) {
    // Generate new OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;

    try {
      $mail->isSMTP();
      $mail->setFrom('cevangelista2021@student.nbscollege.edu.ph', 'Library System');
      $mail->addAddress($_SESSION['email']);

      $mail->Subject = 'Email Verification OTP';
      $mail->Body = "Your OTP for email verification is: <b>$otp</b>";

      $mail->send();
      $success = "A new OTP has been sent to your email.";
    } catch (Exception $e) {
      $error = "OTP could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify OTP</title>
  <link href="inc/css/sb-admin-2.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gradient-primary">
  <div class="container">
    <div class="card o-hidden border-0 shadow-lg my-5">
      <div class="card-body p-0">
        <div class="row">
          <div class="col-lg-5 d-none d-lg-block bg-login-image"></div>
          <div class="col-lg-7">
            <div class="p-5">
              <div class="text-center">
                <h1 class="h4 text-gray-900 mb-4">Verify OTP</h1>
              </div>

              <?php if(isset($error) && $error != ''): ?>
                <script>
                  Swal.fire({
                    title: 'Error!',
                    text: '<?php echo $error; ?>',
                    icon: 'error'
                  });
                </script>
              <?php endif; ?>

              <?php if(isset($success) && $success != ''): ?>
                <script>
                  Swal.fire({
                    title: 'Success!',
                    text: '<?php echo $success; ?>',
                    icon: 'success'
                  }).then(function() {
                    window.location.href = 'index.php';
                  });
                </script>
              <?php endif; ?>

              <form class="user" method="POST" action="">
                <div class="form-group">
                  <input
                    type="text"
                    class="form-control form-control-user"
                    name="otp"
                    placeholder="Enter OTP"
                    required
                  />
                </div>
                <button
                  type="submit"
                  name="verify"
                  class="btn btn-primary btn-user btn-block"
                >
                  Verify OTP
                </button>
                <hr />
              </form>

              <form class="user" method="POST" action="">
                <button
                  type="submit"
                  name="resend"
                  class="btn btn-secondary btn-user btn-block"
                >
                  Resend OTP
                </button>
                <hr />
              </form>

              <hr />
              <div class="text-center">
                <a class="small" href="register.php">Back to Register</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="../inc/js/sb-admin-2.min.js"></script>
</body>
</html>