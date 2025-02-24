<?php
session_start();
include('../db.php');

// Check if the user is logged in and has appropriate permissions
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['userIds'])) {
    $userIds = array_map('intval', $_POST['userIds']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if any of these users have borrowed books
        $checkQuery = "SELECT id FROM users WHERE id IN (" . implode(',', $userIds) . ") AND borrowed_books > 0";
        $checkResult = $conn->query($checkQuery);
        
        if ($checkResult->num_rows > 0) {
            throw new Exception("Cannot delete users who have borrowed books");
        }
        
        // Delete the users
        $deleteQuery = "DELETE FROM users WHERE id IN (" . implode(',', $userIds) . ")";
        if (!$conn->query($deleteQuery)) {
            throw new Exception("Error deleting users");
        }
        
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => count($userIds) . ' user(s) successfully deleted'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'No users selected for deletion'
    ]);
}

$conn->close();
?>
