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
    $_SESSION['message'] = "Invalid publisher ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../step-by-step-publishers.php");
    exit();
}

$publisherId = intval($_GET['id']);

// Start transaction
$conn->begin_transaction();

try {
    // First check if this publisher is used in any books
    $check_query = "SELECT COUNT(*) as count FROM publications WHERE publisher_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $publisherId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        // This publisher is in use - can't delete
        throw new Exception("Cannot delete this publisher because it is used in " . $row['count'] . " books.");
    }
    
    // Delete the publisher
    $delete_query = "DELETE FROM publishers WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $publisherId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error deleting publisher: " . $conn->error);
    }
    
    // Check if the deleted publisher was selected in the session
    if (isset($_SESSION['book_shortcut']['publisher_id']) && $_SESSION['book_shortcut']['publisher_id'] == $publisherId) {
        $_SESSION['book_shortcut']['publisher_id'] = null;
        $_SESSION['book_shortcut']['publish_year'] = null;
        $_SESSION['book_shortcut']['steps_completed']['publisher'] = false;
    }
    
    $conn->commit();
    
    $_SESSION['message'] = "Publisher deleted successfully.";
    $_SESSION['message_type'] = "success";
} catch (Exception $e) {
    $conn->rollback();
    
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

header("Location: ../step-by-step-publishers.php");
exit();
?>
