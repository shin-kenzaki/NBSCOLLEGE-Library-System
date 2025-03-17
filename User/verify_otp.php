<?php
session_start();
require '../db.php';
require __DIR__ . "/mailer.php";

$success = $error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['verify'])) {
    $entered_otp = implode('', $_POST['otp']);

    if ($entered_otp == $_SESSION['otp']) {
      // Retrieve data from session
      $school_id = $_SESSION['school_id'];
      $firstname = ucfirst(strtolower($_SESSION['firstname']));
      $middle_init = ucfirst(strtolower($_SESSION['middle_init']));
      $lastname = ucfirst(strtolower($_SESSION['lastname']));
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
      $_SESSION['resend_time'] = time(); // Store the resend time in session
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
  <style>
    .otp-input {
      width: 40px;
      height: 40px;
      text-align: center;
      font-size: 18px;
      margin: 0 5px;
    }
    .bg-login-image {
      background: url("inc/img/bg-login.JPG") center center no-repeat;
      background-size: cover;
    }
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background-color: #f8f9fc; /* Optional: set a background color */
    }

    .container {
      width: 100%;
      max-width: 960px; /* Optional: set a maximum width for the container */
      margin: 0 auto; /* Center the container horizontally */
    }

    /* Style for the select dropdown with larger size */
    .select-dropdown {
      display: block;
      width: 100%;
      height: 3.5em; /* Increased height for bigger dropdown */
      padding: 0.5rem 1rem; /* Adjusted padding for better spacing */
      font-size: 0.9rem; /* Larger font size */
      font-weight: 400;
      line-height: 1.5;
      color: #6e707e;
      background-color: #fff;
      background-clip: padding-box;
      border: 1px solid #d1d3e2;
      border-radius: 10rem; /* Rounded corners */
      transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    /* Optional: Apply hover effect */
    .select-dropdown:hover {
      border-color: #5e72e4; /* Highlight border color on hover */
    }

    /* Optional: Focus state */
    .select-dropdown:focus {
      border-color: #5e72e4; /* Border color when focused */
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
      outline: none;
    }

    /* Mobile view adjustments */
    @media (max-width: 768px) {
      .card-body {
        padding: 0;
      }

      .p-5 {
        padding: 1.5rem !important;
      }

      .form-group {
        margin-bottom: 0.75rem;
      }

      .form-control-user {
        font-size: 0.8rem;
        padding: 0.75rem 1rem;
      }

      .btn-user {
        padding: 0.5rem 1rem;
      }

      .h4 {
        font-size: 1.25rem;
      }

      /* Stack form elements vertically on small screens */
      @media (max-width: 576px) {
        .form-group.row > div {
          margin-bottom: 0.75rem;
        }
      }
    }


  </style>
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
                    <?php if (isset($_POST['verify'])): ?>
                      window.location.href = 'index.php';
                    <?php endif; ?>
                  });
                </script>
              <?php endif; ?>

              <div class="text-center mb-4">
                <p class="text-gray-900">OTP has been sent to: <strong><?php echo $_SESSION['email']; ?></strong></p>
              </div>

              <form class="user" method="POST" action="">
                <div class="form-group d-flex justify-content-center">
                  <input type="text" class="otp-input form-control" name="otp[]" maxlength="1" required>
                  <input type="text" class="otp-input form-control" name="otp[]" maxlength="1" required>
                  <input type="text" class="otp-input form-control" name="otp[]" maxlength="1" required>
                  <input type="text" class="otp-input form-control" name="otp[]" maxlength="1" required>
                  <input type="text" class="otp-input form-control" name="otp[]" maxlength="1" required>
                  <input type="text" class="otp-input form-control" name="otp[]" maxlength="1" required>
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
                  id="resendBtn"
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

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var resendBtn = document.getElementById('resendBtn');
      var resendTime = <?php echo isset($_SESSION['resend_time']) ? (time() - $_SESSION['resend_time']) : 60; ?>;
      var cooldown = 60 - resendTime;

      if (cooldown > 0) {
        resendBtn.disabled = true;
        var interval = setInterval(function() {
          resendBtn.innerText = 'Resend OTP (' + cooldown + 's)';
          cooldown--;
          if (cooldown < 0) {
            clearInterval(interval);
            resendBtn.disabled = false;
            resendBtn.innerText = 'Resend OTP';
          }
        }, 1000);
      }

      // Handle OTP input focus
      var otpInputs = document.querySelectorAll('.otp-input');
      otpInputs.forEach(function(input, index) {
        input.addEventListener('input', function() {
          if (input.value.length === 1 && index < otpInputs.length - 1) {
            otpInputs[index + 1].focus();
          }
        });

        input.addEventListener('keydown', function(event) {
          if (event.key === 'Backspace' && input.value.length === 0 && index > 0) {
            otpInputs[index - 1].focus();
          }
        });
      });
    });
  </script>
</body>
</html>