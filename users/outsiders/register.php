<?php
require '../../db.php'; // Database connection

$errors = [
    'email' => '',
    'contact_no' => '',
    'firstname' => '',
    'lastname' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $middle_init = $_POST['middle_init'] ?? NULL;
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $contact_no = trim($_POST['contact_no']);
    $date_added = date('Y-m-d H:i:s');
    $status = 'active';
    $last_update = $date_added;
    $address = trim($_POST['address']);
    $id_type = $_POST['id_type'];

    // User Image upload handling (can be null)
    $user_image = NULL;
    if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] === UPLOAD_ERR_OK) {
        $user_image = file_get_contents($_FILES['user_image']['tmp_name']);
    }

    // ID Image upload handling (can be null)
    $id_image = NULL;
    if (isset($_FILES['id_image']) && $_FILES['id_image']['error'] === UPLOAD_ERR_OK) {
        $id_image = file_get_contents($_FILES['id_image']['tmp_name']);
    }

    // Check for duplicate email, contact number, or (firstname + lastname)
    $sql_check = "SELECT email, contact_no, firstname, lastname FROM outside_users 
                  WHERE email = ? OR contact_no = ? OR (firstname = ? AND lastname = ?)";

    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("ssss", $email, $contact_no, $firstname, $lastname);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        // Check for duplicates and set error messages
        if ($result_check->num_rows > 0) {
            while ($row = $result_check->fetch_assoc()) {
                if ($row['email'] === $email) {
                    $errors['email'] = "This email is already registered.";
                }
                if ($row['contact_no'] === $contact_no) {
                    $errors['contact_no'] = "This contact number is already in use.";
                }
                if ($row['firstname'] === $firstname && $row['lastname'] === $lastname) {
                    $errors['firstname'] = "An account with this First Name and Last Name already exists.";
                    $errors['lastname'] = "An account with this First Name and Last Name already exists.";
                }
            }
        } 
        
        // Only insert if no errors were found
        if (empty($errors['email']) && empty($errors['contact_no']) && empty($errors['firstname']) && empty($errors['lastname'])) {
            $sql = "INSERT INTO outside_users (firstname, middle_init, lastname, email, password, contact_no, address, id_type, user_image, id_image, date_added, status, last_update) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sssssssssssss", $firstname, $middle_init, $lastname, $email, $password, $contact_no, $address, $id_type, $user_image, $id_image, $date_added, $status, $last_update);

                if ($stmt->execute()) {
                    echo "<p style='color:green;'>User registered successfully! Redirecting to login...</p>";
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

        $stmt_check->close();
    } else {
        echo "<p style='color:red;'>Error preparing duplicate check query: " . $conn->error . "</p>";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Non-School User Registration</title>
</head>
<body>
    <h1>Non-School User Registration</h1>
    <form action="" method="POST" enctype="multipart/form-data">

        <label for="firstname">First Name:</label><br>
        <input type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" required><br>
        <span style="color:red;"><?= $errors['firstname'] ?></span><br><br>

        <label for="middle_init">Middle Initial:</label><br>
        <input type="text" id="middle_init" name="middle_init" value="<?= htmlspecialchars($_POST['middle_init'] ?? '') ?>"><br><br>

        <label for="lastname">Last Name:</label><br>
        <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" required><br>
        <span style="color:red;"><?= $errors['lastname'] ?></span><br><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required><br>
        <span style="color:red;"><?= $errors['email'] ?></span><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <label for="contact_no">Contact No:</label><br>
        <input type="text" id="contact_no" name="contact_no" value="<?= htmlspecialchars($_POST['contact_no'] ?? '') ?>" required><br>
        <span style="color:red;"><?= $errors['contact_no'] ?></span><br><br>

        <label for="address">Address:</label><br>
        <input type="text" id="address" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required><br><br>

        <label for="id_type">ID Type:</label><br>
        <select id="id_type" name="id_type" required>
            <option value="">Select ID Type</option>
            <option value="Student ID" <?= isset($_POST['id_type']) && $_POST['id_type'] == "Student ID" ? "selected" : "" ?>>Student ID</option>
            <option value="Driver's License" <?= isset($_POST['id_type']) && $_POST['id_type'] == "Driver's License" ? "selected" : "" ?>>Driver's License</option>
            <option value="Passport" <?= isset($_POST['id_type']) && $_POST['id_type'] == "Passport" ? "selected" : "" ?>>Passport</option>
            <option value="Other" <?= isset($_POST['id_type']) && $_POST['id_type'] == "Other" ? "selected" : "" ?>>Other</option>
        </select><br><br>

        <label for="user_image">Upload User Image (Optional):</label><br>
        <input type="file" id="user_image" name="user_image" accept="image/*"><br><br>

        <label for="id_image">Upload ID Image (Optional):</label><br>
        <input type="file" id="id_image" name="id_image" accept="image/*"><br><br>

        <input type="submit" value="Register">
    </form>

    <br>
    
    <form action="login.php" method="GET">
        <button type="submit">Login</button>
    </form>
    
    <br>
    
    <form action="../select_usertype.php" method="GET">
        <button type="submit">Select Usertype</button>
    </form>

</body>
</html>
