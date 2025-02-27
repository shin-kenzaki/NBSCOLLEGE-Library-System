<?php
session_start();
require '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Sanitize and get form data
  $school_id = mysqli_real_escape_string($conn, $_POST['school_id']);
  $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
  $middle_init = mysqli_real_escape_string($conn, $_POST['middle_init']);
  $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  // Hash the password
  $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $usertype = mysqli_real_escape_string($conn, $_POST['usertype']);
  // Update default image path to be consistent
  $image = '../Images/Profile/default-avatar.jpg'; 
  
  // Create directory if it doesn't exist
  if (!file_exists('../Images/Profile')) {
    mkdir('../Images/Profile', 0777, true);
  }
  
  // Ensure default avatar exists
  if (!file_exists($image)) {
    copy('../Images/default-avatar.jpg', $image);
  }
  
  // Check if school_id already exists
  $check_id_query = "SELECT school_id FROM users WHERE school_id = ?";
  $stmt = $conn->prepare($check_id_query);
  $stmt->bind_param("s", $school_id);
  $stmt->execute();
  if($stmt->get_result()->num_rows > 0) {
      $error = "School ID is already registered!";
  } else {
      // Check if full name already exists
      $check_name_query = "SELECT id FROM users WHERE firstname = ? AND lastname = ?";
      $stmt = $conn->prepare($check_name_query);
      $stmt->bind_param("ss", $firstname, $lastname);
      $stmt->execute();
      if($stmt->get_result()->num_rows > 0) {
          $error = "A user with this name already exists!";
      } else {
          // Check if email already exists
          $check_email_query = "SELECT id FROM users WHERE email = ?";
          $stmt = $conn->prepare($check_email_query);
          $stmt->bind_param("s", $email);
          $stmt->execute();
          if($stmt->get_result()->num_rows > 0) {
              $error = "Email address is already registered!";
          } else {
              // If all checks pass, proceed with insert
              $sql = "INSERT INTO users (school_id, firstname, middle_init, lastname, email, password, 
                      user_image, usertype, date_added) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
              
              if($stmt = $conn->prepare($sql)) {
                  $stmt->bind_param("ssssssss", 
                      $school_id, $firstname, $middle_init, $lastname, $email, $hashed_password, $image, $usertype);
                  
                  if($stmt->execute()) {
                      $_SESSION['success'] = "Registration successful! You can now login with your School ID and password.";
                      echo "<script>
                          alert('Registration successful! You will be redirected to the login page.');
                          window.location.href = 'index.php';
                      </script>";
                      exit();
                  } else {
                      $error = "Something went wrong! Please try again.";
                  }
              }
          }
      }
  }
  $stmt->close();
}

// Update user_image paths to be consistent
$update_image_query = "UPDATE users SET user_image = CONCAT('../Images/Profile/', SUBSTRING_INDEX(user_image, '/', -1))
WHERE user_image IS NOT NULL AND user_image != ''";
$conn->query($update_image_query);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1, shrink-to-fit=no"
    />
    <meta name="description" content="" />
    <meta name="author" content="" />

    <title>SB Admin 2 - Register</title>

    <!-- Custom fonts for this template-->
    <link
      href="vendor/fontawesome-free/css/all.min.css"
      rel="stylesheet"
      type="text/css"
    />
    <link
      href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
      rel="stylesheet"
    />

    <!-- Custom styles for this template-->
    <link href="inc/css/sb-admin-2.min.css" rel="stylesheet" />

    <style>
      .bg-login-image {
        background: url("inc/img/bg-login.JPG") center center no-repeat;
        background-size: cover;
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
    </style>
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
                  <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form class="user" method="POST" action="">
                  <div class="form-group row">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                      <input
                        type="text"
                        class="form-control form-control-user"
                        name="school_id"
                        placeholder="School ID"
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
                        maxlength="1"
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
                        <option value="student">Student</option>
                        <option value="faculty">Faculty</option>
                        <option value="staff">Staff</option>
                      </select>
                    </div>
                  </div>

                  <button
                    type="submit"
                    class="btn btn-primary btn-user btn-block"
                  >
                    Register Account
                  </button>
                  <hr />
                </form>

                <hr />
                <div class="text-center">
                  <a class="small" href="forgot-password.php"
                    >Forgot Password?</a
                  >
                </div>
                <div class="text-center">
                  <a class="small" href="index.php"
                    >Already have an account? Login!</a
                  >
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
