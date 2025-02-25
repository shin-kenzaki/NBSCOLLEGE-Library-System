<?php
session_start();
require '../db.php';

$message = '';
$messageType = '';
$verified = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify'])) {
        // Step 1: Verify School ID and email
        $school_id = trim($_POST['school_id']);
        $email = trim($_POST['email']);

        $sql = "SELECT * FROM users WHERE school_id = ? AND email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $school_id, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $_SESSION['reset_school_id'] = $school_id;
                $verified = true;
                $message = "Identity verified. Please enter your new password.";
                $messageType = "success";
            } else {
                $message = "Invalid school ID or email.";
                $messageType = "danger";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['reset'])) {
        // Step 2: Reset Password
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $school_id = $_SESSION['reset_school_id'];

        if ($password === $confirm_password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $update_sql = "UPDATE users SET password = ? WHERE school_id = ?";
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param("ss", $hashed_password, $school_id);
                if ($update_stmt->execute()) {
                    // Remove the direct header redirect
                    $message = "Password successfully updated.";
                    $messageType = "success";
                    unset($_SESSION['reset_school_id']);
                } else {
                    $message = "Error updating password.";
                    $messageType = "danger";
                    $verified = true; // Keep the password form visible
                }
                $update_stmt->close();
            }
        } else {
            $message = "Passwords do not match.";
            $messageType = "danger";
            $verified = true; // Keep the password form visible
        }
    }
}

// Check if user is already verified from previous step
if (isset($_SESSION['reset_school_id'])) {
    $verified = true;
}
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

<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-password-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Forgot Your Password?</h1>
                                    </div>
                                    
                                    <?php if ($message): ?>
                                        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                                            <?php echo $message; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!$verified): ?>
                                        <!-- Step 1: Verification Form -->
                                        <form class="user" method="POST">
                                            <div class="form-group">
                                                <input type="text" class="form-control form-control-user" 
                                                       id="school_id" name="school_id" 
                                                       placeholder="Enter School ID" required>
                                            </div>
                                            <div class="form-group">
                                                <input type="email" class="form-control form-control-user" 
                                                       id="email" name="email" 
                                                       placeholder="Enter Email Address" required>
                                            </div>
                                            <button type="submit" name="verify" class="btn btn-primary btn-user btn-block">
                                                Verify Identity
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <!-- Step 2: Password Reset Form -->
                                        <form class="user" method="POST">
                                            <div class="form-group">
                                                <input type="password" class="form-control form-control-user"
                                                       id="password" name="password"
                                                       placeholder="Enter New Password" required>
                                            </div>
                                            <div class="form-group">
                                                <input type="password" class="form-control form-control-user"
                                                       id="confirm_password" name="confirm_password"
                                                       placeholder="Confirm New Password" required>
                                            </div>
                                            <button type="submit" name="reset" class="btn btn-primary btn-user btn-block">
                                                Reset Password
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
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
        <?php if ($messageType === 'success' && strpos($message, 'successfully') !== false): ?>
        Swal.fire({
            title: 'Success!',
            text: 'Password has been successfully updated',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        }).then(() => {
            window.location.href = 'index.php';
        });
        <?php endif; ?>
    </script>
</body>
</html>
