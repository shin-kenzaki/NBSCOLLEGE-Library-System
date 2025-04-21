<?php
session_start();
require '../db.php';
require __DIR__ . "/mailer.php";

$success = $error = '';

// Redirect the user if OTP is not set (to prevent returning to the OTP page)
if (!isset($_SESSION['otp'])) {
    header("Location: index.php"); // Redirect to the login or home page
    exit();
}

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
            $department = $_SESSION['department']; // <-- Add this line
            $image = $_SESSION['image'];

            // If all checks pass, proceed with insert
            $sql = "INSERT INTO users (school_id, firstname, middle_init, lastname, email, password,
                    user_image, usertype, department, date_added)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param(
                    "sssssssss",
                    $school_id,
                    $firstname,
                    $middle_init,
                    $lastname,
                    $email,
                    $hashed_password,
                    $image,
                    $usertype,
                    $department // <-- Add this parameter
                );

                if ($stmt->execute()) {
                    // Use school_id instead of auto-incremented ID
                    $user_id = $school_id;

                    // Construct full name (including middle initial if exists)
                    $full_name = $firstname;
                    if (!empty($middle_init)) {
                        $full_name .= " " . $middle_init . ".";
                    }
                    $full_name .= " " . $lastname;

                    // Insert into updates table
                    $update_title = "User Registered";
                    $update_message = $full_name . " Registered as " . $usertype;

                    $update_sql = "INSERT INTO updates (user_id, role, title, message, `update`)
                                   VALUES (?, ?, ?, ?, NOW())";

                    if ($update_stmt = $conn->prepare($update_sql)) {
                        $update_stmt->bind_param("isss", $user_id, $usertype, $update_title, $update_message);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }

                    $_SESSION['success'] = "Registration successful! You can now login with your School ID and password.";
                    $success = "Registration successful! You will be redirected to the login page.";

                    // Invalidate the OTP after successful verification
                    unset($_SESSION['otp']);
                } else {
                    $error = "Something went wrong! Please try again.";
                }
            }
            $stmt->close();
        } else {
            $error = "Invalid OTP!";
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
                <button
                  type="button"
                  id="resendBtn"
                  class="btn btn-secondary btn-user btn-block"
                >
                  Resend OTP
                </button>

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

       // Handle paste event for OTP inputs
    otpInputs[0].addEventListener('paste', function(event) {
      event.preventDefault();
      var pasteData = (event.clipboardData || window.clipboardData).getData('text');
      if (pasteData.length === otpInputs.length) {
        otpInputs.forEach(function(input, index) {
          input.value = pasteData[index] || '';
        });
        otpInputs[otpInputs.length - 1].focus(); // Focus the last input
      }
    });

       // Handle Resend OTP button click
    resendBtn.addEventListener('click', function(event) {
      event.preventDefault(); // Prevent default form submission

      Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to resend the OTP to your email?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, resend it!',
        cancelButtonText: 'No, cancel',
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Sending OTP...',
            text: 'Please wait while we resend the OTP to your email.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Send AJAX request to resend_otp.php
          fetch('resend_otp.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.success) {
                Swal.fire({
                  title: 'Success!',
                  text: data.message,
                  icon: 'success',
                });
                // Start cooldown timer
                resendBtn.disabled = true;
                var cooldown = 60;
                var interval = setInterval(function() {
                  resendBtn.innerText = 'Resend OTP (' + cooldown + 's)';
                  cooldown--;
                  if (cooldown < 0) {
                    clearInterval(interval);
                    resendBtn.disabled = false;
                    resendBtn.innerText = 'Resend OTP';
                  }
                }, 1000);
              } else {
                Swal.fire({
                  title: 'Error!',
                  text: data.message,
                  icon: 'error',
                });
              }
            })
            .catch((error) => {
              Swal.fire({
                title: 'Error!',
                text: 'An error occurred while resending the OTP.',
                icon: 'error',
              });
            });
        }
      });
    });
    });


  </script>
</body>
</html>