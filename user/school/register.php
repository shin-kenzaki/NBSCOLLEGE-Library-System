<?php
    session_start();

    require '../../db.php'; // Database connection

    $errors = [
        'id' => '',
        'email' => '',
        'password' => '',
        'firstname' => '',
        'lastname' => ''
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'];
        $firstname = $_POST['firstname'];
        $middle_init = $_POST['middle_init'];
        $lastname = $_POST['lastname'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $date_added = date('Y-m-d H:i:s');
        $status = 'active';
        $last_update = $date_added;
        $borrowed_books = null;
        $returned_books = null;
        $damaged_books = null;
        $lost_books = null;

        // Image upload handling (can be null)
        $image = 'inc/upload/default-avatar.jpg'; // Default value if no image is uploaded


        // Insert into database
        $sql = "INSERT INTO school_users (id, firstname, middle_init, lastname, email, password, image, borrowed_books, returned_books, damaged_books, lost_books, date_added, status, last_update)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssssssssssss", $id, $firstname, $middle_init, $lastname, $email, $password, $image, $borrowed_books, $returned_books, $damaged_books, $lost_books, $date_added, $status, $last_update);
            if ($stmt->execute()) {
                echo "<p style='color:green;'>School user registered successfully! Redirecting to login...</p>";
                header("refresh:3;url=index.php");
                exit;
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing query: " . $conn->error;
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

    <title>SB Admin 2 - Register</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../inc/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .bg-login-image {
    background: url('../inc/img/bg-login.JPG') center center no-repeat;
    background-size: cover;
        }

        /* Style for the select dropdown with larger size */
        .select-dropdown {
            display: block;
            width: 100%;
            height: 3.5em; /* Increased height for bigger dropdown */
            padding: 0.5rem 1rem; /* Adjusted padding for better spacing */
            font-size: .90rem; /* Larger font size */
            font-weight: 400;
            line-height: 1.5;
            color: #6e707e;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #d1d3e2;
            border-radius: 10rem; /* Rounded corners */
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
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
                                <h1 class="h4 text-gray-900 mb-4">Create an Account!</h1>
                            </div>

                            <form class="user" action="" method="POST" enctype="multipart/form-data">


                                <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <input type="text" class="form-control form-control-user" id="id" name="id" placeholder="ID" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>" required>
                                        <span style="color:red;"><?= $errors['id'] ?></span>
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control form-control-user" name="firstname" placeholder="First Name" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" required>
                                        <span style="color:red;"><?= $errors['firstname'] ?></span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control form-control-user" name="middle_init" placeholder="Middle Initial" value="<?= htmlspecialchars($_POST['middle_init'] ?? '') ?>">
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control form-control-user" name="lastname" placeholder="Last Name" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" required>
                                        <span style="color:red;"><?= $errors['lastname'] ?></span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <input type="text" class="form-control form-control-user" name="email" placeholder="email" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                                    <span style="color:red;"><?= $errors['email'] ?></span>
                                </div>

                                <!-- <div class="form-group row"> -->
                                <div class="form-group">
                                    <!-- <div class="col-sm-6"> -->
                                        <input type="password" class="form-control form-control-user" name="password" placeholder="Password" required>
                                        <span style="color:red;"><?= $errors['password'] ?></span>
                                    <!-- </div> -->
                                    <!-- <div class="col-sm-6">
                                        <select name="role" class="form-control user select-dropdown" required>
                                            <option value="">Select Role</option>
                                            <option value="Admin" <?= isset($_POST['role']) && $_POST['role'] == "Admin" ? "selected" : "" ?>>Admin</option>
                                            <option value="Librarian" <?= isset($_POST['role']) && $_POST['role'] == "Librarian" ? "selected" : "" ?>>Librarian</option>
                                            <option value="Encoder" <?= isset($_POST['role']) && $_POST['role'] == "Encoder" ? "selected" : "" ?>>Encoder</option>
                                        </select>
                                    </div> -->
                                </div>

                                <button type="submit" name="submit" class="btn btn-primary btn-user btn-block">
                                    Register Account
                                </button>

                                <hr>

                            </form>


                            <div class="text-center">
                                <a class="small" href="forgot-password.html">Forgot Password?</a>
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
    <script src="inc/js/sb-admin-2.min.js"></script>

</body>

</html>