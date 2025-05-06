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
    if (empty($_POST['firstname']) || empty($_POST['lastname'])) {
        $_SESSION['message'] = "First name and last name are required.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../step-by-step-writers.php");
        exit();
    }
    
    // Sanitize inputs
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $middle_init = isset($_POST['middle_init']) ? mysqli_real_escape_string($conn, $_POST['middle_init']) : '';
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    
    // Check if writer with same name already exists
    $check_query = "SELECT id FROM writers WHERE firstname = ? AND middle_init = ? AND lastname = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("sss", $firstname, $middle_init, $lastname);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['message'] = "A writer with this name already exists.";
        $_SESSION['message_type'] = "warning";
        header("Location: ../step-by-step-writers.php");
        exit();
    }
    
    // Insert the new writer
    $query = "INSERT INTO writers (firstname, middle_init, lastname) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $firstname, $middle_init, $lastname);
    
    if ($stmt->execute()) {
        $writerId = $conn->insert_id;
        
        // If this is part of the step-by-step book addition process
        // and we're returning to the form, add this writer to selected list
        if (isset($_SESSION['return_to_form']) && $_SESSION['return_to_form'] && isset($_SESSION['book_shortcut'])) {
            // Create a new entry in selected writers with default role
            $newWriter = [
                'id' => $writerId,
                'role' => 'Author'
            ];
            
            // Add to selected writers
            if (!isset($_SESSION['book_shortcut']['selected_writers'])) {
                $_SESSION['book_shortcut']['selected_writers'] = [];
            }
            $_SESSION['book_shortcut']['selected_writers'][] = $newWriter;
        }
        
        $_SESSION['message'] = "Writer added successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding writer: " . $conn->error;
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
