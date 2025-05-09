<?php
session_start();
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($conn->real_escape_string($_POST['name']));
    $type = trim($conn->real_escape_string($_POST['type']));
    $location = trim($conn->real_escape_string($_POST['location']));
    $description = trim($conn->real_escape_string($_POST['description']));

    // Validate required fields
    if (empty($name) || empty($type) || empty($location)) {
        $_SESSION['error_message'] = "All fields (Name, Type, and Location) are required.";
        header("Location: corporates_list.php");
        exit();
    }

    // Check if the corporate with the same name and type already exists
    $checkQuery = "SELECT id FROM corporates WHERE name = ? AND type = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $name, $type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = "A corporate with the name '$name' and type '$type' already exists.";
        header("Location: corporates_list.php");
        exit();
    }

    // Insert the new corporate
    $query = "INSERT INTO corporates (name, type, location, description) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $name, $type, $location, $description);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Corporate '$name' of type '$type' added successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to add corporate: " . $conn->error;
    }

    header("Location: corporates_list.php");
    exit();
} else {
    header("Location: corporates_list.php");
    exit();
}
?>
