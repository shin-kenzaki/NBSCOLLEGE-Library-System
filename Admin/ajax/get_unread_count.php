<?php
session_start();
require_once('../../db.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin_role = $_SESSION['role'];

try {
    $query = "SELECT COUNT(*) as count 
              FROM messages 
              WHERE receiver_id = ? 
              AND receiver_role = ? 
              AND is_read = 0";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $admin_id, $admin_role);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode(['count' => (int)$row['count']]);
    
} catch (Exception $e) {
    error_log("Error in get_unread_count.php: " . $e->getMessage());
    echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
}
