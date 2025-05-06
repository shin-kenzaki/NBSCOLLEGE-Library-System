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
    if (empty($_POST['publisher_name']) || empty($_POST['publisher_place'])) {
        $_SESSION['message'] = "Publisher name and place are required.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../step-by-step-publishers.php");
        exit();
    }
    
    // Sanitize inputs
    $publisher_name = mysqli_real_escape_string($conn, $_POST['publisher_name']);
    $publisher_place = mysqli_real_escape_string($conn, $_POST['publisher_place']);
    
    // Check if publisher with same name already exists
    $check_query = "SELECT id FROM publishers WHERE publisher = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $publisher_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['message'] = "A publisher with this name already exists.";
        $_SESSION['message_type'] = "warning";
        header("Location: ../step-by-step-publishers.php");
        exit();
    }
    
    // Insert the new publisher
    $query = "INSERT INTO publishers (publisher, place) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $publisher_name, $publisher_place);
    
    if ($stmt->execute()) {
        $publisherId = $conn->insert_id;
        
        // If this is part of the step-by-step book addition process
        // and we're returning to the form, select this publisher
        if (isset($_SESSION['return_to_form']) && $_SESSION['return_to_form'] && isset($_SESSION['book_shortcut'])) {
            $_SESSION['book_shortcut']['publisher_id'] = $publisherId;
        }
        
        $_SESSION['message'] = "Publisher added successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding publisher: " . $conn->error;
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
