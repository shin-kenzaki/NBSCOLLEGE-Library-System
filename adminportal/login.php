<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
</head>
<body>

    <h1>Admin Login</h1>

    <?php
    session_start();
    if (isset($_SESSION['admin_id'])) {
        header("Location: admin_dashboard.php");
        exit;
    }    
    require '../db.php'; // Database connection

    // Initialize error message
    $error_message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve and sanitize input
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Query to check if the user exists
        $sql = "SELECT * FROM admins WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            // If the username exists, check the password
            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                // Compare plain text passwords directly
                if ($password === $admin['password']) {
                    // Login successful, store session data
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_firstname'] = $admin['firstname'];
                    $_SESSION['admin_lastname'] = $admin['lastname'];
                    // Store admin image in session (Convert binary data to base64)
                    $_SESSION['admin_image'] = base64_encode($admin['image']);
                    $_SESSION['role'] = strtolower($admin['role']); // Convert role to lowercase for consistency
                    $_SESSION['admin_date_added'] = $admin['date_added'];
                    $_SESSION['admin_status'] = $admin['status'];
                    $_SESSION['admin_last_update'] = $admin['last_update'];

                    // Redirect based on role
                    switch ($_SESSION['role']) {
                        case 'admin':
                            echo "<p style='color: green;'>Logging In... Redirecting to Admin Page...</p>";
                            header("refresh:3;url=admin_dashboard.php");
                            break;
                        case 'librarian':
                            echo "<p style='color: green;'>Logging In... Redirecting to Librarian Page...</p>";
                            header("refresh:3;url=librarian/librarian_dashboard.php");
                            break;
                        case 'assistant':
                            echo "<p style='color: green;'>Logging In... Redirecting to Assistant Page...</p>";
                            header("refresh:3;url=assistant/assistant_dashboard.php");
                            break;
                        default:
                            $error_message = "Invalid role assigned.";
                    }
                    exit;
                } else {
                    $error_message = "Invalid password.";
                }
            } else {
                $error_message = "No such admin found.";
            }

            $stmt->close();
        } else {
            $error_message = "Error preparing query: " . $conn->error;
        }

        $conn->close();
    }
    ?>

    <form action="" method="POST">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username" required><br><br>

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

</body>
</html>
