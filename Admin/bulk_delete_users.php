<?php
session_start();
include '../db.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userIds'])) {
    $userIds = array_map('intval', $_POST['userIds']);
    
    if (empty($userIds)) {
        die(json_encode(['success' => false, 'message' => 'No users selected']));
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Create placeholders for the IN clause
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        
        // Delete users
        $stmt = $conn->prepare("DELETE FROM users WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => "$affected user(s) deleted successfully"
            ]);
        } else {
            throw new Exception("Failed to delete users");
        }
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
        'message' => 'Invalid request'
    ]);
}

$conn->close();
?>
