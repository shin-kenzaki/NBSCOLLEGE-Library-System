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
    if (empty($_POST['corporate_name']) || empty($_POST['corporate_type'])) {
        $_SESSION['message'] = "Corporate name and type are required.";
        $_SESSION['message_type'] = "danger";
        header("Location: ../step-by-step-corporates.php");
        exit();
    }
    
    // Sanitize inputs
    $name = mysqli_real_escape_string($conn, $_POST['corporate_name']);
    $type = mysqli_real_escape_string($conn, $_POST['corporate_type']);
    $location = isset($_POST['corporate_location']) ? mysqli_real_escape_string($conn, $_POST['corporate_location']) : '';
    $description = isset($_POST['corporate_description']) ? mysqli_real_escape_string($conn, $_POST['corporate_description']) : '';
    
    // Check if corporate with same name already exists
    $check_query = "SELECT id FROM corporates WHERE name = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['message'] = "A corporate entity with this name already exists.";
        $_SESSION['message_type'] = "warning";
        header("Location: ../step-by-step-corporates.php");
        exit();
    }
    
    // Insert the new corporate entity
    $query = "INSERT INTO corporates (name, type, location, description) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $name, $type, $location, $description);
    
    if ($stmt->execute()) {
        $corporateId = $conn->insert_id;
        
        // If this is part of the step-by-step book addition process
        // and we're returning to the form, add this corporate to selected list
        if (isset($_SESSION['return_to_form']) && $_SESSION['return_to_form'] && isset($_SESSION['book_shortcut'])) {
            // Create a new entry in selected corporates with default role
            $newCorporate = [
                'id' => $corporateId,
                'role' => 'Corporate Contributor'
            ];
            
            // Add to selected corporates
            if (!isset($_SESSION['book_shortcut']['selected_corporates'])) {
                $_SESSION['book_shortcut']['selected_corporates'] = [];
            }
            $_SESSION['book_shortcut']['selected_corporates'][] = $newCorporate;
        }
        
        $_SESSION['message'] = "Corporate entity added successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding corporate entity: " . $conn->error;
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
