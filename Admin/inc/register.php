<?php
    if (isset($_POST["submit"])) {
        $userID = $_POST["id"];
        $firstname = $_POST["firstname"];
        $lastname = $_POST["lastname"];
        $username = $_POST["username"];
        $password = $_POST["password"];  // Directly store password
        $role = $_POST["role"];

        $date_added = date("Y-m-d H:i:s");

        $image = "../Admin/inc/upload/bg-login.JPG";
        $status = "active";

        if (empty($userID) || empty($firstname) || empty($lastname) || empty($username) || empty($password) || empty($role)) {
            $error_m = "Error! <span>Field mustn't be empty</span>";
        } else {
            $check_userID = mysqli_query($conn, "SELECT * FROM admins WHERE id = '$userID'");

            if (mysqli_num_rows($check_userID) > 0) {
                $error_m = "Error! <span>UserID is already taken</span>";
            } else {
                $query = "INSERT INTO admins (id, firstname, lastname, username, password, role, date_added, image, status)
                          VALUES ('$userID', '$firstname', '$lastname', '$username', '$password', '$role', '$date_added', '$image', '$status')";

                if (mysqli_query($conn, $query)) {
                    $_SESSION['success'] = "Account successfully registered!";
                    header("Location: index.php");
                    exit();
                } else {
                    $error_m = "Error! <span>Failed to register user</span>";
                }
            }
        }
    }
?>
