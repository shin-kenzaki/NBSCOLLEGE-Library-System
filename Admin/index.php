<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Check if user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['role'])) {
    switch($_SESSION['role']) {
        case 'Admin':
            header("Location: dashboard.php");
            break;
        case 'Librarian':
        case 'Assistant':
            header("Location: librarian/librarian_dashboard.php");
            break;
        case 'Encoder':
            header("Location: encoder/encoder_dashboard.php");
            break;
    }
    exit();
}

require '../db.php'; // Database connection

// Initialize error message
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input
    $employee_id = $_POST['employee_id'];
    $password = $_POST['password'];

    // Query to check if the user exists
    $sql = "SELECT * FROM admins WHERE employee_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // If the employee_id exists, check the password
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // Check status first
            if ($admin['status'] === 'Disabled') {
                $error_message = "This account has been disabled. Please contact the administrator.";
            } else {
                // Use password_verify for hashed password comparison
                if (password_verify($password, $admin['password'])) {
                    // Set session variables
                    $_SESSION['admin_id'] = $admin['employee_id'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_firstname'] = $admin['firstname'];
                    $_SESSION['admin_lastname'] = $admin['lastname'];
                    $_SESSION['admin_image'] = !empty($admin['image']) ? $admin['image'] : 'upload/default-profile.png';
                    $_SESSION['role'] = $admin['role'];  // Will now be 1 or 0
                    $_SESSION['admin_date_added'] = $admin['date_added'];
                    $_SESSION['admin_status'] = $admin['status'];
                    $_SESSION['admin_last_update'] = $admin['last_update'];

                    // Log the successful login in updates table
                    $log_query = "INSERT INTO updates (user_id, role, status, `update`) VALUES (?, ?, ?, NOW())";
                    if ($log_stmt = $conn->prepare($log_query)) {
                        $login_status = "Active login";
                        $log_stmt->bind_param("sss", $admin['employee_id'], $admin['role'], $login_status);
                        $log_stmt->execute();
                        $log_stmt->close();
                    }

                    // Redirect based on role
                    switch($admin['role']) {
                        case 'Admin':
                            header("Location: dashboard.php");
                            break;
                        case 'Librarian':
                        case 'Assistant':
                            header("Location: librarian_dashboard.php");
                            break;
                        case 'Encoder':
                            header("Location: encoder_dashboard.php");
                            break;
                        default:
                            $error_message = "Invalid role assigned.";
                            break;
                    }
                    exit();
                } else {
                    $error_message = "Invalid credentials"; 
                }
            }
        } else {
            $error_message = "Invalid credentials"; 
        }

        $stmt->close();
    } else {
        $error_message = "Error preparing query: " . $conn->error;
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

    <title>Library System - Admin Login</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="inc/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .bg-login-image {
    background: url('inc/img/bg-login.JPG') center center no-repeat;
    background-size: cover;
}
    </style>

</head>

<body class="bg-gradient-primary">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center">

            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->

                        <div class="row">
                        <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Welcome Back!</h1>
                                    </div>
                                    <?php if(!empty($error_message)): ?>
                                        <div class="alert alert-danger">
                                            <?php echo $error_message; ?>
                                        </div>
                                    <?php endif; ?>
                                    <form class="user" method="POST" action="">
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user"
                                            placeholder="ID"
                                            id="employee_id" name="employee_id" required>
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
                                        <a class="small" href="forgot-password.html">Forgot Password?</a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="admin_registration.php">Create an Account!</a>
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

</body>

</html>