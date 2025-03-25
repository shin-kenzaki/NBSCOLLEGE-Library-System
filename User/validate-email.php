<?php
require '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];

    // Check if the email exists in the database
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Email address not found.']);
    }
}
?>