<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Non-school User Login</title>
</head>
<body>

    <h1>Non-school User Login</h1>

    <?php
    session_start();
    if (isset($_SESSION['outsider_id'])) {
        header("Location: ../dashboard.php");
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
        $sql = "SELECT * FROM outside_users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            // If the email exists, check the password
            if ($result->num_rows > 0) {
                $outsider = $result->fetch_assoc();
                // Compare plain text passwords directly
                if ($password === $outsider['password']) {
                    // Login successful, store session data
                    $_SESSION['outsider_id'] = $outsider['id'];
                    $_SESSION['outsider_firstname'] = $outsider['firstname'];
                    $_SESSION['outsider_lastname'] = $outsider['lastname'];
                    $_SESSION['outsider_email'] = $outsider['email'];
                    $_SESSION['outsider_borrowed_books'] = $outsider['borrowed_books'];
                    $_SESSION['outsider_returned_books'] = $outsider['returned_books'];
                    $_SESSION['outsider_damaged_books'] = $outsider['damaged_books'];
                    $_SESSION['outsider_lost_books'] = $outsider['lost_books'];
                    // Store outsider image in session (Convert binary data to base64)
                    $_SESSION['outsider_user_image'] = base64_encode($outsider['user_image']);
                    $_SESSION['outsider_id_image'] = base64_encode($outsider['id_image']);
                    $_SESSION['outsider_date_added'] = $outsider['date_added'];
                    $_SESSION['status'] = $outsider['status'];
                    $_SESSION['outsider_last_update'] = $outsider['last_update'];

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
                $error_message = "No such outsider found.";
            }

            $stmt->close();
        } else {
            $error_message = "Error preparing query: " . $conn->error;
        }

        $conn->close();
    }
    ?>

    <form action="" method="POST">
        <label for="email">School Email:</label><br>
        <input type="text" id="email" name="email" required><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <?php if ($error_message): ?>
            <p style="color:red;"> <?= htmlspecialchars($error_message) ?> </p>
        <?php endif; ?>

        <input type="submit" value="Login">
    </form>

    <br>

    <!-- Add Registration Button -->
    <form action="register.php" method="GET">
        <button type="submit">Register</button>
    </form>

    <br>
    
    <form action="../select_usertype.php" method="GET">
        <button type="submit">Select Usertype</button>
    </form>

</body>
</html>
