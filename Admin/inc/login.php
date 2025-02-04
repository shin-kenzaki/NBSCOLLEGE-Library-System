<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_POST['userId'];
    $password = $_POST['password'];

    // Validate user credentials
    $sql = "SELECT * FROM admins WHERE id = '$userId' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['userId'] = $user['id'];
        $_SESSION['firstname'] = $user['firstname'];
        $_SESSION['lastname'] = $user['lastname'];
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<script>alert('Invalid User ID or Password');</script>";
    }
}
?>