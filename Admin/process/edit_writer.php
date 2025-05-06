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
    if (empty($_POST['writer_id']) || empty($_POST['firstname']) || empty($_POST['lastname'])) {
        $_SESSION['message'] = "Writer ID, first name, and last name are required.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../step-by-step-writers.php");
        exit();
    }
    
    // Sanitize inputs
    $writerId = intval($_POST['writer_id']);
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $middle_init = isset($_POST['middle_init']) ? mysqli_real_escape_string($conn, $_POST['middle_init']) : '';
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    
    // Check if writer with same name already exists (excluding the current writer)
    $check_query = "SELECT id FROM writers WHERE firstname = ? AND middle_init = ? AND lastname = ? AND id != ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("sssi", $firstname, $middle_init, $lastname, $writerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['message'] = "Another writer with this name already exists.";
        $_SESSION['message_type'] = "warning";
        header("Location: ../step-by-step-writers.php");
        exit();
    }
    
    // Update the writer
    $query = "UPDATE writers SET firstname = ?, middle_init = ?, lastname = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $firstname, $middle_init, $lastname, $writerId);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Writer updated successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating writer: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: ../step-by-step-writers.php");
    exit();
} else {
    // If accessed directly without POST data
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../step-by-step-writers.php");
    exit();
}
?>
