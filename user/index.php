<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

include('../db.php');

// Initialize error message
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $school_id = $_POST['school_id'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE school_id = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // First check the account status
            switch ($user['status']) {
                case 2: // Banned
                    $error = "This account has been banned. Please contact the administrator.";
                    break;
                case 3: // Disabled
                    $error = "This account has been disabled. Please contact the administrator.";
                    break;
                case 1: // Active
                case 0: // Inactive
                case null: // Treat null as inactive
                    // Only validate password if account status is acceptable
                    if (password_verify($password, $user['password'])) {
                        // Log the successful login in updates table with title and message
                        $log_query = "INSERT INTO updates (user_id, role, title, message, `update`) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
                        if ($log_stmt = $conn->prepare($log_query)) {
                            $login_title = "User Logged In";
                            $full_name = $user['firstname'] . ' ' . $user['lastname'];
                            
                            // Set login status message based on user status
                            if ($user['status'] == 1) {
                                $login_message = $user['usertype'] . " " . $full_name . " Logged In as Active";
                            } else if ($user['status'] == 0 || $user['status'] === null) {
                                $login_message = $user['usertype'] . " " . $full_name . " Logged In as Inactive";
                            } else if ($user['status'] == 2) {
                                $login_message = $user['usertype'] . " " . $full_name . " Logged In as Banned";
                            } else if ($user['status'] == 3) {
                                $login_message = $user['usertype'] . " " . $full_name . " Logged In as Disabled";
                            } else {
                                $login_message = $user['usertype'] . " " . $full_name . " Logged In with Unknown Status";
                            }
                            
                            $log_stmt->bind_param("ssss", $user['school_id'], $user['usertype'], $login_title, $login_message);
                            
                            if (!$log_stmt->execute()) {
                                // Handle logging error if needed
                                error_log("Failed to log login attempt: " . $log_stmt->error);
                            }
                            $log_stmt->close();
                        }

                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['school_id'] = $user['school_id'];
                        $_SESSION['firstname'] = $user['firstname'];
                        $_SESSION['lastname'] = $user['lastname'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_image'] = !empty($user['user_image']) ? $user['user_image'] : 'upload/default-profile.png';
                        $_SESSION['usertype'] = $user['usertype'];
                        $_SESSION['status'] = $user['status'];
                        
                        $error = "success"; // Use error to trigger SweetAlert
                    } else {
                        $error = "Invalid password";
                    }
                    break;
                default:
                    $error = "Invalid account status";
                    break;
            }
        } else {
            $error = "School ID not found";
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Library System - User Login</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="inc/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .bg-login-image {
            background: url('../Images/BG/bg-login.JPG') center center no-repeat;
            background-size: cover;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .card {
                margin: 1rem !important;
            }
            .p-5 {
                padding: 2rem !important;
            }
            .my-5 {
                margin-top: 2rem !important;
                margin-bottom: 2rem !important;
            }
            /* Show background image on small screens too */
            .bg-login-mobile {
                min-height: 180px;
                background: url('../Images/BG/bg-login.JPG') center center no-repeat;
                background-size: cover;
                border-radius: 5px 5px 0 0;
            }
        }

        /* Centering the card */
        .row.justify-content-center {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Make sure the row takes at least the full viewport height */
        }
    </style>
</head>

<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <!-- Mobile image that shows only on small screens -->
                            <div class="d-block d-lg-none w-100 bg-login-mobile"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Welcome Back!</h1>
                                    </div>
                                    <?php if(!empty($error) && $error !== "success"): ?>
                                        <div class="alert alert-danger">
                                            <?php echo $error; ?>
                                        </div>
                                    <?php endif; ?>
                                    <form class="user" method="POST" action="">
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user"
                                                placeholder="School ID"
                                                id="school_id" name="school_id" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" class="form-control form-control-user"
                                                id="exampleInputPassword" placeholder="Password" name="password" required>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox small">
                                                <input type="checkbox" class="custom-control-input" id="customCheck">
                                                <label class="custom-control-label" for="customCheck">Remember Me</label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Login
                                        </button>
                                    </form>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="forgot-password.php">Forgot Password?</a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="register.php">Create an Account!</a>
                                    </div>
                                </div>
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
    <script src="inc/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if($error === "success"): ?>
            Swal.fire({
                title: 'Welcome Back!',
                text: 'Successfully logged in',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(function() {
                window.location.href = 'dashboard.php';
            });
        <?php endif; ?>
    </script>
</body>
</html>