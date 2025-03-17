<?php
session_start();
require '../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Forgot Password - Library System</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="inc/css/sb-admin-2.min.css" rel="stylesheet">
</head>
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

<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Forgot Your Password?</h1>
                                    </div>

                                    <!-- Step 1: Verification Form -->
                                    <form class="user" method="POST" action="forgot-password-send.php">
                                        <div class="form-group">
                                            <input type="email" class="form-control form-control-user"
                                                   id="email" name="email"
                                                   placeholder="Enter Email Address" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Reset Password
                                        </button>
                                    </form>

                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="register.php">Create an Account!</a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="index.php">Back to Login</a>
                                    </div>
                                </div>
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
    <script src="inc/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (isset($_SESSION['alert_type']) && isset($_SESSION['alert_message'])): ?>
            Swal.fire({
                icon: "<?php echo $_SESSION['alert_type']; ?>",
                title: "<?php echo ucfirst($_SESSION['alert_type']); ?>",
                text: "<?php echo $_SESSION['alert_message']; ?>",
                showConfirmButton: true
            });

            <?php
            // Clear session data after displaying the alert
            unset($_SESSION['alert_type']);
            unset($_SESSION['alert_message']);
            ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>