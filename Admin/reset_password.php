<?php
session_start();
require '../db.php';

$message = '';
$messageType = '';
$validToken = false;
$token = isset($_GET['token']) ? $_GET['token'] : '';

if ($token) {
    // Verify token is valid and not expired
    $sql = "SELECT * FROM admins WHERE reset_token = ? AND reset_expires > NOW()";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $validToken = true;
            $admin = $result->fetch_assoc();
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];
                
                if ($password === $confirm_password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Update password and clear reset token
                    $update_sql = "UPDATE admins SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?";
                    if ($update_stmt = $conn->prepare($update_sql)) {
                        $update_stmt->bind_param("ss", $hashed_password, $token);
                        if ($update_stmt->execute()) {
                            $message = "Password successfully updated. You can now login with your new password.";
                            $messageType = "success";
                            header("refresh:3;url=index.php");
                        } else {
                            $message = "Error updating password.";
                            $messageType = "danger";
                        }
                        $update_stmt->close();
                    }
                } else {
                    $message = "Passwords do not match.";
                    $messageType = "danger";
                }
            }
        } else {
            $message = "Invalid or expired reset token.";
            $messageType = "danger";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Reset Password - Library System</title>
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
                                        <h1 class="h4 text-gray-900 mb-4">Reset Your Password</h1>
                                    </div>
                                    
                                    <?php if ($message): ?>
                                        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                                            <?php echo $message; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($validToken): ?>
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
                                            <button type="submit" class="btn btn-primary btn-user btn-block">
                                                Reset Password
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <hr>
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
</body>
</html>
