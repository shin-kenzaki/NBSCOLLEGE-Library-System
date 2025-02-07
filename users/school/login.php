<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School User Login</title>
</head>
<body>

    <h1>School User Login</h1>

    <?php
    session_start();
    if (isset($_SESSION['schooluser_id'])) {
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
