<?php
session_start();

$token = $_POST["token"];

$token_hash = hash("sha256", $token);

$conn = require __DIR__ . "/../db.php";  // ✅ Corrected path and variable

$sql = "SELECT * FROM users
        WHERE reset_token = ?";

$stmt = $conn->prepare($sql);  // ✅ Changed from $mysqli to $conn

$stmt->bind_param("s", $token_hash);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

if ($user === null) {
    die("Token not found");
}

if (strtotime($user["reset_expires"]) <= time()) {
    die("Token has expired");
}

if (strlen($_POST["password"]) < 8) {
    die("Password must be at least 8 characters");
}

if (!preg_match("/[a-z]/i", $_POST["password"])) {
    die("Password must contain at least one letter");
}

if (!preg_match("/[0-9]/", $_POST["password"])) {
    die("Password must contain at least one number");
}

if ($_POST["password"] !== $_POST["confirm_password"]) {
    die("Passwords must match");
}

$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

$sql = "UPDATE users
        SET password = ?,
            reset_token = NULL,
            reset_expires = NULL
        WHERE id = ?";

$stmt = $conn->prepare($sql);  // ✅ Changed from $mysqli to $conn

$stmt->bind_param("ss", $password_hash, $user["id"]);

$stmt->execute();

echo "Password updated. You can now log in. <a href='index.php'>Click here to login</a>";
?>
