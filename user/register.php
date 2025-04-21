<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get form data
    $school_id = mysqli_real_escape_string($conn, $_POST['school_id']);
    // Capitalize all characters for names
    $firstname = strtoupper(mysqli_real_escape_string($conn, $_POST['firstname']));
    $middle_init = strtoupper(mysqli_real_escape_string($conn, $_POST['middle_init']));
    $lastname = strtoupper(mysqli_real_escape_string($conn, $_POST['lastname']));
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $usertype = mysqli_real_escape_string($conn, $_POST['usertype']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $image = '../Images/Profile/default-avatar.jpg';

    // Store user data in session
    $_SESSION['school_id'] = $school_id;
    $_SESSION['firstname'] = $firstname;
    $_SESSION['middle_init'] = $middle_init;
    $_SESSION['lastname'] = $lastname;
    $_SESSION['email'] = $email;
    $_SESSION['hashed_password'] = $hashed_password;
    $_SESSION['usertype'] = $usertype;
    $_SESSION['department'] = $department;
    $_SESSION['image'] = $image;

    $_SESSION['otp_allowed'] = true;

    // Redirect to OTP sending page
    header("Location: send_otp.php");
    exit();
}

// Update user_image paths to be consistent
$update_image_query = "UPDATE users SET user_image = CONCAT('../Images/Profile/', SUBSTRING_INDEX(user_image, '/', -1)) WHERE user_image IS NOT NULL AND user_image != ''";
$conn->query($update_image_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="description" content="" />
  <meta name="author" content="" />

  <title>SB Admin 2 - Register</title>

  <!-- Custom fonts for this template-->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css" />
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet" />

  <!-- Custom styles for this template-->
  <link href="inc/css/sb-admin-2.min.css" rel="stylesheet" />

  <style>
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.querySelector('form.user');

      form.addEventListener('submit', async function (event) {
        event.preventDefault(); // Prevent form submission by default

        // Perform all validations
        const isEmailValid = validateEmailDomain();
        const isPasswordValid = validatePasswordLength();
        const isUserUnique = await validateUserUniqueness();

        // If any validation fails, stop further execution
        if (!isEmailValid || !isPasswordValid || !isUserUnique) {
          return;
        }

        // If all validations pass, submit the form
        form.submit();
      });

      // Capitalize all characters for names
      const firstNameInput = document.querySelector('input[name="firstname"]');
      const middleInitInput = document.querySelector('input[name="middle_init"]');
      const lastNameInput = document.querySelector('input[name="lastname"]');

      function capitalizeAll(input) {
        input.value = input.value.toUpperCase();
      }

      firstNameInput.addEventListener('input', function () {
        capitalizeAll(firstNameInput);
      });

      middleInitInput.addEventListener('input', function () {
        capitalizeAll(middleInitInput);
      });

      lastNameInput.addEventListener('input', function () {
        capitalizeAll(lastNameInput);
      });
    });

    // Function to validate email domain
    function validateEmailDomain() {
      const emailInput = document.querySelector('input[name="email"]');
      const userTypeSelect = document.querySelector('select[name="usertype"]');
      const email = emailInput.value;
      const userType = userTypeSelect.value;
      let validDomain = false;
      let validUserType = false;

      if (email.endsWith('@student.nbscollege.edu.ph') || email.endsWith('@nbscollege.edu.ph')) {
        validDomain = true;
      }

      if (userType === 'Student' && email.endsWith('@student.nbscollege.edu.ph')) {
        validUserType = true;
      } else if ((userType === 'Faculty' || userType === 'Staff') && email.endsWith('@nbscollege.edu.ph')) {
        validUserType = true;
      }

      if (!validDomain) {
        Swal.fire({
          title: 'Error!',
          text: 'Please input a valid school email address.',
          icon: 'error'
        });
        return false;
      } else if (!validUserType) {
        Swal.fire({
          title: 'Error!',
          text: 'Invalid email domain for the selected user type.',
          icon: 'error'
        });
        return false;
      }
      return true;
    }

    // Function to validate password length
    function validatePasswordLength() {
      const passwordInput = document.querySelector('input[name="password"]');
      const password = passwordInput.value;

      if (password.length < 8) {
        Swal.fire({
          title: 'Error!',
          text: 'Password must be at least 8 characters long.',
          icon: 'error'
        });
        return false;
      }
      return true;
    }

    // Function to validate user uniqueness (school ID, email, and name)
    async function validateUserUniqueness() {
      const schoolIdInput = document.querySelector('input[name="school_id"]');
      const firstNameInput = document.querySelector('input[name="firstname"]');
      const lastNameInput = document.querySelector('input[name="lastname"]');
      const emailInput = document.querySelector('input[name="email"]');

      const schoolId = schoolIdInput.value;
      const firstName = firstNameInput.value;
      const lastName = lastNameInput.value;
      const email = emailInput.value;

      try {
        const response = await fetch('validate_user.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ school_id: schoolId, firstname: firstName, lastname: lastName, email: email })
        });

        const result = await response.json();

        if (!result.success) {
          Swal.fire({
            title: 'Error!',
            text: result.message,
            icon: 'error'
          });
          return false;
        }

        return true;
      } catch (error) {
        Swal.fire({
          title: 'Error!',
          text: 'An error occurred while validating user data. Please try again.',
          icon: 'error'
        });
        return false;
      }
    }
  </script>
</head>

<body class="bg-gradient-primary">
  <div class="container">
    <div class="card o-hidden border-0 shadow-lg my-5">
      <div class="card-body p-0">
        <!-- Nested Row within Card Body -->
        <div class="row">
          <div class="col-lg-5 d-none d-lg-block bg-login-image"></div>

          <div class="col-lg-7">
            <div class="p-5">
              <div class="text-center">
                <h1 class="h4 text-gray-900 mb-4">Create User Account</h1>
              </div>

              <?php if(isset($error)): ?>
                <script>
                  Swal.fire({
                    title: 'Error!',
                    text: '<?php echo $error; ?>',
                    icon: 'error'
                  });
                </script>
              <?php endif; ?>

              <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <script>
                  Swal.fire({
                    title: 'Success!',
                    text: 'OTP has been sent to your email. Please verify.',
                    icon: 'success'
                  });
                </script>
              <?php endif; ?>

              <?php if(isset($_GET['status']) && $_GET['status'] == 'error' && isset($_GET['message'])): ?>
                <script>
                  Swal.fire({
                    title: 'Error!',
                    text: '<?php echo urldecode($_GET['message']); ?>',
                    icon: 'error'
                  });
                </script>
              <?php endif; ?>

              <form class="user" method="POST" action="">
                <div class="form-group row">
                  <div class="col-sm-6 mb-3 mb-sm-0">
                    <input
                      type="text"
                      class="form-control form-control-user"
                      name="school_id"
                      placeholder="ID Number"
                      required
                    />
                  </div>
                  <div class="col-sm-6">
                    <input
                      type="text"
                      class="form-control form-control-user"
                      name="firstname"
                      placeholder="First Name"
                      required
                    />
                  </div>
                </div>

                <div class="form-group row">
                  <div class="col-sm-6">
                    <input
                      type="text"
                      class="form-control form-control-user"
                      name="middle_init"
                      placeholder="Middle Initial"
                    />
                  </div>

                  <div class="col-sm-6">
                    <input
                      type="text"
                      class="form-control form-control-user"
                      name="lastname"
                      placeholder="Last Name"
                      required
                    />
                  </div>
                </div>

                <div class="form-group">
                  <input
                    type="email"
                    class="form-control form-control-user"
                    name="email"
                    placeholder="School Email Address"
                    required
                  />
                </div>

                <div class="form-group row">
                  <div class="col-sm-6">
                    <input
                      type="password"
                      class="form-control form-control-user"
                      name="password"
                      placeholder="Password"
                      required
                    />
                  </div>
                  <div class="col-sm-6">
                    <select
                      name="usertype"
                      class="form-control select-dropdown"
                      required
                    >
                      <option value="">Select User Type</option>
                      <option value="Student">Student</option>
                      <option value="Faculty">Faculty</option>
                      <option value="Staff">Staff</option>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <select
                    name="department"
                    class="form-control select-dropdown"
                    required
                  >
                    <option value="">Select Department</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Accounting Information System">Accounting Information System</option>
                    <option value="Accountancy">Accountancy</option>
                    <option value="Entrepreneurship">Entrepreneurship</option>
                    <option value="Tourism Management">Tourism Management</option>
                  </select>
                </div>

                <button
                  type="submit"
                  class="btn btn-primary btn-user btn-block"
                >
                  Register Account
                </button>

              </form>

              <hr />
              <div class="text-center">
                <a class="small" href="forgot-password.php">Forgot Password?</a>
              </div>
              <div class="text-center">
                <a class="small" href="index.php">Already have an account? Login!</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap core JavaScript-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Custom scripts for all pages-->
  <script src="../inc/js/sb-admin-2.min.js"></script>
</body>
</html>