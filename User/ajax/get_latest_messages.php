<?php
session_start();
require_once('../../db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['usertype'];

// Get latest 5 unread messages
$query = "SELECT 
    m.*,
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
    AND m.is_read = 0
    ORDER BY m.timestamp DESC
    LIMIT 5";

$stmt = $conn->prepare($query);
$stmt->bind_param('is', $user_id, $user_role);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'message_id' => $row['id'],
        'sender_id' => $row['sender_id'],
        'sender_role' => $row['sender_role'],
        'sender_name' => $row['sender_name'],
        'sender_image' => $row['sender_image'] ?? 'img/undraw_profile.svg',
        'message' => htmlspecialchars($row['message']),
        'timestamp' => $row['timestamp'],
        'is_read' => (bool)$row['is_read']
    ];
}

echo json_encode(['messages' => $messages]);
