<?php
    session_start();
    if (isset($_SESSION['schooluser_id'])) {
        header("Location: dashboard.php");
        exit;
    }
    require '../../db.php'; // Database connection

    // Initialize error message
    $error_message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve and sanitize input
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Query to check if the user exists
        $sql = "SELECT * FROM school_users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            // If the email exists, check the password
            if ($result->num_rows > 0) {
                $schooluser = $result->fetch_assoc();
                // Compare plain text passwords directly
                if ($password === $schooluser['password']) {
                    // Login successful, store session data
                    $_SESSION['schooluser_id'] = $schooluser['id'];
                    $_SESSION['schooluser_firstname'] = $schooluser['firstname'];
                    $_SESSION['schooluser_lastname'] = $schooluser['lastname'];
                    $_SESSION['schooluser_email'] = $schooluser['email'];
                    $_SESSION['schooluser_borrowed_books'] = $schooluser['borrowed_books'];
                    $_SESSION['schooluser_returned_books'] = $schooluser['returned_books'];
                    $_SESSION['schooluser_damaged_books'] = $schooluser['damaged_books'];
                    $_SESSION['schooluser_lost_books'] = $schooluser['lost_books'];
                    // Store schooluser image in session (Convert binary data to base64)
                    $_SESSION['schooluser_image'] = base64_encode($schooluser['image']);
                    $_SESSION['schooluser_date_added'] = $schooluser['date_added'];
                    $_SESSION['status'] = $schooluser['status'];
                    $_SESSION['schooluser_last_update'] = $schooluser['last_update'];

                    // Redirect based on status
                    switch ($_SESSION['status']) {
                        case 'active':
                            echo "<p style='color: green;'>Logging In... Redirecting to User Page...</p>";

                            header("refresh:3;url=../dashboard.php");
                            break;
                        default:
                            $error_message = "Inactive status, contact admin.";
                    }
                    exit;
                } else {
                    $error_message = "Invalid password.";
                }
            } else {
                $error_message = "No such schooluser found.";
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

    <title>SB Admin 2 - Login</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../../user/inc/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .bg-login-image {
    background: url('../../user/inc/img/bg-login.JPG') center center no-repeat;
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
                                    <form class="user" method="POST" action="">
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user"
                                            placeholder="Email"
                                            id="email" name="email" required
                                                >
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

                                        <?php if ($error_message): ?>
                                            <p style="color:red;"> <?= htmlspecialchars($error_message) ?> </p>
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Login
                                        </button>
                                    </form>

                                        <!-- <a href="index.html" class="btn btn-google btn-user btn-block">
                                            <i class="fab fa-google fa-fw"></i> Login with Google
                                        </a>
                                        <a href="index.html" class="btn btn-facebook btn-user btn-block">
                                            <i class="fab fa-facebook-f fa-fw"></i> Login with Facebook
                                        </a> -->
                                    </form>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="forgot-password.html">Forgot Password?</a>
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