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
    if (empty($_POST['corporate_id']) || empty($_POST['corporate_name']) || empty($_POST['corporate_type'])) {
        $_SESSION['message'] = "Corporate ID, name, and type are required.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../step-by-step-corporates.php");
        exit();
    }
    
    // Sanitize inputs
    $id = intval($_POST['corporate_id']);
    $name = mysqli_real_escape_string($conn, $_POST['corporate_name']);
    $type = mysqli_real_escape_string($conn, $_POST['corporate_type']);
    $location = isset($_POST['corporate_location']) ? mysqli_real_escape_string($conn, $_POST['corporate_location']) : '';
    $description = isset($_POST['corporate_description']) ? mysqli_real_escape_string($conn, $_POST['corporate_description']) : '';
    
    // Check if corporate with same name already exists (excluding the current one)
    $check_query = "SELECT id FROM corporates WHERE name = ? AND id != ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("si", $name, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['message'] = "Another corporate entity with this name already exists.";
        $_SESSION['message_type'] = "warning";
        header("Location: ../step-by-step-corporates.php");
        exit();
    }
    
    // Update the corporate entity
    $query = "UPDATE corporates SET name = ?, type = ?, location = ?, description = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssi", $name, $type, $location, $description, $id);
    
    if ($stmt->execute()) {
        // If this is part of the step-by-step book addition process
        // update the corporate in the selected list
        if (isset($_SESSION['book_shortcut']['selected_corporates']) && !empty($_SESSION['book_shortcut']['selected_corporates'])) {
            foreach ($_SESSION['book_shortcut']['selected_corporates'] as $key => $corporate) {
                if ($corporate['id'] == $id) {
                    // Keep the same role, just update the ID in case it was changed
                    $_SESSION['book_shortcut']['selected_corporates'][$key]['id'] = $id;
                    break;
                }
            }
        }
        
        $_SESSION['message'] = "Corporate entity updated successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating corporate entity: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: ../step-by-step-corporates.php");
    exit();
} else {
    // If accessed directly without POST data
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../step-by-step-corporates.php");
    exit();
}
?>
