<?php
session_start();
require '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match!";
        header("Location: forgot-reset-password.php?token=" . urlencode($token));
        exit();
    }

    $token_hash = hash("sha256", $token);

    $sql = "SELECT * FROM users WHERE reset_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user === null) {
        $_SESSION['error'] = "Invalid token!";
        header("Location: forgot-reset-password.php?token=" . urlencode($token));
        exit();
    }

    if (strtotime($user["reset_expires"]) <= time()) {
        $_SESSION['error'] = "Token has expired!";
        header("Location: forgot-reset-password.php?token=" . urlencode($token));
        exit();
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $password_hash, $user['id']);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Password has been reset successfully!";
        header("Location: success-reset-password.php");
        exit();
    } else {
        $_SESSION['error'] = "Something went wrong! Please try again.";
        header("Location: forgot-reset-password.php?token=" . urlencode($token));
        exit();
    }
}
?>