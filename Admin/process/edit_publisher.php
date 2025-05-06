<?php
session_start();
include '../../db.php';

// Check if user is logged in with appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    if (empty($_POST['publisher_id']) || empty($_POST['publisher_name']) || empty($_POST['publisher_place'])) {
        $_SESSION['message'] = "Publisher ID, name, and place are required.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../step-by-step-publishers.php");
        exit();
    }
    
    // Sanitize inputs
    $publisherId = intval($_POST['publisher_id']);
    $publisher_name = mysqli_real_escape_string($conn, $_POST['publisher_name']);
    $publisher_place = mysqli_real_escape_string($conn, $_POST['publisher_place']);
    
    // Check if publisher with same name already exists (excluding the current publisher)
    $check_query = "SELECT id FROM publishers WHERE publisher = ? AND id != ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("si", $publisher_name, $publisherId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['message'] = "Another publisher with this name already exists.";
        $_SESSION['message_type'] = "warning";
        header("Location: ../step-by-step-publishers.php");
        exit();
    }
    
    // Update the publisher
    $query = "UPDATE publishers SET publisher = ?, place = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $publisher_name, $publisher_place, $publisherId);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Publisher updated successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating publisher: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: ../step-by-step-publishers.php");
    exit();
} else {
    // If accessed directly without POST data
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../step-by-step-publishers.php");
    exit();
}
?>
