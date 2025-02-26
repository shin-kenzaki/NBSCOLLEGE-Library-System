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
    $query = "SELECT m.*, 
              CASE 
                WHEN m.sender_role IN ('Admin', 'Librarian', 'Assistant') 
                THEN (SELECT CONCAT(firstname, ' ', lastname) FROM admins WHERE id = m.sender_id)
                ELSE (SELECT CONCAT(firstname, ' ', lastname) FROM users WHERE id = m.sender_id)
              END as sender_name,
              CASE 
                WHEN m.sender_role IN ('Admin', 'Librarian', 'Assistant') 
                THEN (SELECT image FROM admins WHERE id = m.sender_id)
                ELSE (SELECT user_image FROM users WHERE id = m.sender_id)
              END as sender_image
              FROM messages m 
              WHERE m.receiver_id = ? 
              AND m.receiver_role = ?
              ORDER BY m.timestamp DESC 
              LIMIT 5";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $admin_id, $admin_role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = array();
    while($row = $result->fetch_assoc()) {
        $timeAgo = time() - strtotime($row['timestamp']);
        if($timeAgo < 60) {
            $timeStr = "Just now";
        } elseif($timeAgo < 3600) {
            $mins = floor($timeAgo/60);
            $timeStr = $mins . "m ago";
        } elseif($timeAgo < 86400) {
            $hours = floor($timeAgo/3600);
            $timeStr = $hours . "h ago";
        } else {
            $timeStr = date('M d', strtotime($row['timestamp']));
        }
        
        $messages[] = array(
            'id' => $row['id'],
            'sender_id' => $row['sender_id'],
            'sender_name' => $row['sender_name'],
            'sender_role' => $row['sender_role'],
            'message' => $row['message'],
            'timestamp' => $timeStr,
            'is_read' => $row['is_read'],
            'sender_image' => $row['sender_image'] ?? 'inc/img/default-avatar.jpg'
        );
    }
    
    echo json_encode(['messages' => $messages]);
    
} catch (Exception $e) {
    error_log("Error in get_latest_messages.php: " . $e->getMessage());
    echo json_encode(['messages' => [], 'error' => 'Failed to load messages']);
}
