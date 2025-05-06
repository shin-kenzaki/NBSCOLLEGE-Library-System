<?php
session_start();
include '../../db.php';

// Check if user is logged in with appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: ../index.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid writer ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../step-by-step-writers.php");
    exit();
}

$writerId = intval($_GET['id']);

// Start transaction
$conn->begin_transaction();

try {
    // First check if this writer is used in any books
    $check_query = "SELECT COUNT(*) as count FROM contributors WHERE writer_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $writerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        // This writer is in use - can't delete
        throw new Exception("Cannot delete this writer because they are credited in " . $row['count'] . " books.");
    }
    
    // Delete the writer
    $delete_query = "DELETE FROM writers WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $writerId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error deleting writer: " . $conn->error);
    }
    
    // Check if there's session data for selected writers
    if (isset($_SESSION['book_shortcut']['selected_writers'])) {
        // Remove the deleted writer from selected writers
        foreach ($_SESSION['book_shortcut']['selected_writers'] as $key => $writer) {
            if ($writer['id'] == $writerId) {
                unset($_SESSION['book_shortcut']['selected_writers'][$key]);
                $_SESSION['book_shortcut']['selected_writers'] = array_values($_SESSION['book_shortcut']['selected_writers']);
                break;
            }
        }
    }
    
    $conn->commit();
    
    $_SESSION['message'] = "Writer deleted successfully.";
    $_SESSION['message_type'] = "success";
} catch (Exception $e) {
    $conn->rollback();
    
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

header("Location: ../step-by-step-writers.php");
exit();
?>
