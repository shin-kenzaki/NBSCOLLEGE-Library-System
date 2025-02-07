<?php
// Connection to the database (assuming you have db.php for connection)
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the data from the form
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $contact_no = $_POST['contact_no'];
    $address = $_POST['address'];
    $id_type = $_POST['id_type'];
    $id_image = $_FILES['id_image']['name']; // Handle file upload
    $date_added = date("Y-m-d H:i:s"); // Current timestamp for date_added
    $status = "active"; // Default status set to active
    $last_update = $date_added; // Last update will also be the same as date_added

    // Handle file upload for the ID image
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($id_image);
    move_uploaded_file($_FILES["id_image"]["tmp_name"], $target_file);

    // Insert the data into the outsiders table
    $sql = "INSERT INTO outsiderusers (email, password, contact_no, address, id_type, id_image, date_added, status, last_update) 
            VALUES ('$email', '$password', '$contact_no', '$address', '$id_type', '$id_image', '$date_added', '$status', '$last_update')";

    if ($conn->query($sql) === TRUE) {
        echo "Outsider registered successfully";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outsider Registration</title>
</head>
<body>
    <h1>Outsider Registration</h1>
    <form action="outsider_register.php" method="POST" enctype="multipart/form-data">
        <!-- Common Fields -->
        <label for="email">Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label for="password">Password:</label><br>
        <input type="password" name="password" required><br><br>

        <!-- Contact No -->
        <label for="contact_no">Contact Number:</label><br>
        <input type="text" name="contact_no" required><br><br>

        <!-- Address -->
        <label for="address">Address:</label><br>
        <textarea name="address" required></textarea><br><br>

        <!-- ID Type (legal ID) -->
        <label for="id_type">ID Type (Legal ID):</label><br>
        <input type="text" name="id_type" required><br><br>

        <!-- ID Image -->
        <label for="id_image">Upload ID Image:</label><br>
        <input type="file" name="id_image" required><br><br>

        <input type="submit" value="Register">
    </form>
    <br>
    <a href="register.php">Register as School User</a>
</body>
</html>
