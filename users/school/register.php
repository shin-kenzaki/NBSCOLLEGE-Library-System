<?php
require '../../db.php'; // Database connection

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
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = file_get_contents($_FILES['image']['tmp_name']);
    }

    // Insert into database
    $sql = "INSERT INTO school_users (id, firstname, middle_init, lastname, email, password, image, borrowed_books, returned_books, damaged_books, lost_books, date_added, status, last_update) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssssssssssss", $id, $firstname, $middle_init, $lastname, $email, $password, $image, $borrowed_books, $returned_books, $damaged_books, $lost_books, $date_added, $status, $last_update);
        if ($stmt->execute()) {
            echo "<p style='color:green;'>School user registered successfully! Redirecting to login...</p>";
            header("refresh:3;url=login.php");
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School User Registration</title>
</head>
<body>
    <h1>School User Registration</h1>
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="id">ID:</label><br>
        <input type="text" id="id" name="id" required><br><br>

        <label for="firstname">First Name:</label><br>
        <input type="text" id="firstname" name="firstname" required><br><br>

        <label for="middle_init">Middle Initial:</label><br>
        <input type="text" id="middle_init" name="middle_init"><br><br>

        <label for="lastname">Last Name:</label><br>
        <input type="text" id="lastname" name="lastname" required><br><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <label for="image">Upload Image (Optional):</label><br>
        <input type="file" id="image" name="image" accept="image/*"><br><br>

        <input type="submit" value="Register">
    </form>
    
    <br>
    
    <!-- Add Login Button -->
    <form action="login.php" method="GET">
        <button type="submit">Login</button>
    </form>
    
    <br>
    
    <form action="../select_usertype.php" method="GET">
        <button type="submit">Select Usertype</button>
    </form>

</body>
</html>
