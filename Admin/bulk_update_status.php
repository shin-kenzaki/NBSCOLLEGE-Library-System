<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userIds = $_POST['userIds'] ?? [];
    $status = $_POST['status'] ?? '';
    
    if (empty($userIds) || !is_array($userIds) || !in_array($status, [0, 1, 2, 3])) {
        die(json_encode(['success' => false, 'message' => 'Invalid parameters']));
    }

    // Convert array to comma-separated string for the IN clause
    $userIdsStr = implode(',', array_map('intval', $userIds));
    
    $query = "UPDATE users SET status = ?, last_update = NOW() WHERE id IN ($userIdsStr)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $status);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        echo json_encode([
            'success' => true,
            'message' => "Successfully updated status for $affected user(s)"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
