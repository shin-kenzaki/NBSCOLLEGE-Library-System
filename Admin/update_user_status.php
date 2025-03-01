<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (empty($userId) || !in_array($status, [0, 1, 2, 3])) {
        die(json_encode(['success' => false, 'message' => 'Invalid parameters']));
    }

    $query = "UPDATE users SET status = ?, last_update = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $status, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
