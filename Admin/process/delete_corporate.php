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
    $_SESSION['message'] = "Invalid corporate ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../step-by-step-corporates.php");
    exit();
}

$corporateId = intval($_GET['id']);

// Start transaction
$conn->begin_transaction();

try {
    // First check if this corporate is used in any books
    $check_query = "SELECT COUNT(*) as count FROM corporate_contributors WHERE corporate_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $corporateId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        // This corporate is in use - can't delete
        throw new Exception("Cannot delete this corporate entity because it is used as a contributor in " . $row['count'] . " books.");
    }
    
    // Delete the corporate entity
    $delete_query = "DELETE FROM corporates WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $corporateId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error deleting corporate entity: " . $conn->error);
    }
    
    // Check if there's session data for selected corporates
    if (isset($_SESSION['book_shortcut']['selected_corporates'])) {
        // Remove the deleted corporate from selected corporates
        foreach ($_SESSION['book_shortcut']['selected_corporates'] as $key => $corporate) {
            if ($corporate['id'] == $corporateId) {
                unset($_SESSION['book_shortcut']['selected_corporates'][$key]);
                $_SESSION['book_shortcut']['selected_corporates'] = array_values($_SESSION['book_shortcut']['selected_corporates']);
                break;
            }
        }
    }
    
    $conn->commit();
    
    $_SESSION['message'] = "Corporate entity deleted successfully.";
    $_SESSION['message_type'] = "success";
} catch (Exception $e) {
    $conn->rollback();
    
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

header("Location: ../step-by-step-corporates.php");
exit();
?>
