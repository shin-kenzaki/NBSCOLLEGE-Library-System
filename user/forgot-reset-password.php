<?php
session_start();
require '../db.php';

$token = $_GET["token"];

$token_hash = hash("sha256", $token);

$conn = require __DIR__ . "/../db.php";  // ✅ Fixed variable

$sql = "SELECT * FROM users WHERE reset_token = ?";

$stmt = $conn->prepare($sql);  // ✅ Updated to use $conn

$stmt->bind_param("s", $token_hash);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

if ($user === null) {
    die("Token not found");
}

if (strtotime($user["reset_expires"]) <= time()) {
    die("Token has expired");
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

                                    <form class="user" method="POST" action="process-reset-password.php">
                                         <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

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
