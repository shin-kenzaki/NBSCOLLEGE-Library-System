<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

include('../db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $school_id = mysqli_real_escape_string($conn, $_POST['school_id']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE school_id = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Compare plain text passwords (Consider using password_hash for security)
            if ($password === $user['password']) {
                if ($user['status'] === 'active' || $user['status'] === null) {
                    // If status is null, update it to active
                    if ($user['status'] === null) {
                        $update_query = "UPDATE users SET status = 'active' WHERE id = ?";
                        if ($update_stmt = $conn->prepare($update_query)) {
                            $update_stmt->bind_param("i", $user['id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                    }
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['school_id'] = $user['school_id'];
                    $_SESSION['firstname'] = $user['firstname'];
                    $_SESSION['lastname'] = $user['lastname'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_image'] = !empty($user['user_image']) ? $user['user_image'] : 'upload/default-profile.png';
                    $_SESSION['usertype'] = $user['usertype'];
                    $_SESSION['status'] = 'active';
                    
                    header("Location: dashboard.php");
                    exit();
                } else if ($user['status'] === 'inactive') {
                    $error = "Your account is not active. Please contact the administrator.";
                }
            } else {
                $error = "Invalid password";
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
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../user/inc/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .bg-login-image {
    background: url('../user/inc/img/bg-login.JPG') center center no-repeat;
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
                                        <h1 class="h4 text-gray-900 mb-4">Welcome Student!</h1>
                                    </div>
                                    <?php if(isset($error)): ?>
                                        <div class="alert alert-danger">
                                            <?php echo $error; ?>
                                        </div>
                                    <?php endif; ?>
                                    <form class="user" method="POST" action="">
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user"
                                                placeholder="School ID"
                                                name="school_id" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" class="form-control form-control-user"
                                                placeholder="Password" name="password" required>
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
    <script src="../../user/inc/js/sb-admin-2.min.js"></script>

</body>

</html>