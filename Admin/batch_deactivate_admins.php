<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get admin IDs from POST request
$admin_ids = isset($_POST['admin_ids']) ? $_POST['admin_ids'] : [];

if (empty($admin_ids)) {
    echo json_encode(['success' => false, 'message' => 'No admins selected']);
    exit();
}

try {
    // Convert array to comma-separated string and sanitize
    $ids = array_map('intval', $admin_ids);
    $ids_string = implode(',', $ids);
    
    // Update the status to inactive (0)
    $sql = "UPDATE admins SET status = 0, last_update = NOW() WHERE id IN ($ids_string)";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error updating admin status");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
