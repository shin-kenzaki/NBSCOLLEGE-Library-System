<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
</head>
<body>

    <h1>Admin Registration</h1>

    <?php
    $errors = [
        'id' => '',
        'username' => '',
        'password' => '',
        'firstname' => '',
        'lastname' => ''
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require '../db.php'; // Database connection
    
        // Retrieve and sanitize form input
        $id = $_POST['id'];
        $firstname = trim($_POST['firstname']);
        $middle_init = $_POST['middle_init'] ?? NULL; // Optional field
        $lastname = trim($_POST['lastname']);
        $username = $_POST['username'];
        $password = $_POST['password']; // Raw password input
        $role = $_POST['role'];
        $status = "Active"; // Automatically set to Active
        $image = NULL; // Default value if no image is uploaded

        // Validate password length
        if (strlen($password) < 8) {
            $errors['password'] = "Password must be at least 8 characters long.";
        }

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = file_get_contents($_FILES['image']['tmp_name']);
        }

        // Check for duplicate ID, username, or (firstname + lastname)
        $sql_check = "SELECT id, username, firstname, lastname FROM admins 
                      WHERE id = ? OR username = ? OR (firstname = ? AND lastname = ?)";
        
        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("isss", $id, $username, $firstname, $lastname);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                while ($row = $result_check->fetch_assoc()) {
                    if ($row['id'] == $id) {
                        $errors['id'] = "This ID is already in use.";
                    }
                    if ($row['username'] == $username) {
                        $errors['username'] = "This username is already taken.";
                    }
                    if ($row['firstname'] == $firstname && $row['lastname'] == $lastname) {
                        $errors['firstname'] = "An account with this First Name and Last Name already exists.";
                        $errors['lastname'] = "An account with this First Name and Last Name already exists.";
                    }
                }
            } else {
                // Proceed if no errors exist
                if (empty($errors['id']) && empty($errors['username']) && empty($errors['password']) && empty($errors['firstname']) && empty($errors['lastname'])) {
                    // Insert the new admin into the database
                    $sql = "INSERT INTO admins (id, firstname, middle_init, lastname, username, password, image, role, status, date_added) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("issssssss", $id, $firstname, $middle_init, $lastname, $username, $password, $image, $role, $status);
    
                        if ($stmt->execute()) {
                            echo "<p style='color:green;'>Admin registered successfully! Redirecting to login...</p>";
                            header("refresh:3;url=login.php");
                            exit;
                        } else {
                            echo "<p style='color:red;'>Error: " . $stmt->error . "</p>";
                        }
    
                        $stmt->close();
                    } else {
                        echo "<p style='color:red;'>Error preparing query: " . $conn->error . "</p>";
                    }
                }
            }

            $stmt_check->close();
        } else {
            echo "<p style='color:red;'>Error preparing duplicate check query: " . $conn->error . "</p>";
        }

        $conn->close();
    }
    ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <label for="id">Admin ID:</label><br>
        <input type="text" id="id" name="id" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>" required><br>
        <span style="color:red;"><?= $errors['id'] ?></span><br><br>

        <label for="firstname">First Name:</label><br>
        <input type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" required><br>
        <span style="color:red;"><?= $errors['firstname'] ?></span><br><br>

        <label for="middle_init">Middle Initial:</label><br>
        <input type="text" id="middle_init" name="middle_init" value="<?= htmlspecialchars($_POST['middle_init'] ?? '') ?>"><br><br>

        <label for="lastname">Last Name:</label><br>
        <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" required><br>
        <span style="color:red;"><?= $errors['lastname'] ?></span><br><br>

        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required><br>
        <span style="color:red;"><?= $errors['username'] ?></span><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br>
        <span style="color:red;"><?= $errors['password'] ?></span><br><br>

        <label for="image">Upload Image (optional):</label><br>
        <input type="file" id="image" name="image" accept="image/*"><br><br>

        <label for="role">Role:</label><br>
        <select id="role" name="role" required>
            <option value="Admin" <?= isset($_POST['role']) && $_POST['role'] == "Admin" ? "selected" : "" ?>>Admin</option>
            <option value="Librarian" <?= isset($_POST['role']) && $_POST['role'] == "Librarian" ? "selected" : "" ?>>Librarian</option>
            <option value="Assistant" <?= isset($_POST['role']) && $_POST['role'] == "Assistant" ? "selected" : "" ?>>Assistant</option>
        </select><br><br>

        <input type="submit" value="Register">
    </form>

    <br>
    
    <form action="login.php" method="GET">
        <button type="submit">Login</button>
    </form>

</body>
</html>
