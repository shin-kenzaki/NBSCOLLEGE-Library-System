<?php
session_start();
require_once('../../db.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit();
}

$admin_id = $_SESSION['admin_id'];
$receiver_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null; // Changed from admin_id to user_id
$receiver_role = isset($_GET['role']) ? $_GET['role'] : null;

if (!$receiver_id || !$receiver_role) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing receiver information',
        'messages' => []
    ]);
    exit();
}

try {
    // Simplified query to get all messages between admin and user/other admin
    $query = "SELECT m.*, 
              CASE 
                WHEN m.sender_id = ? AND m.sender_role = ? THEN 'You'
                ELSE CONCAT(
                    CASE 
                        WHEN m.sender_role IN ('Admin', 'Librarian', 'Assistant') 
                        THEN (SELECT CONCAT(firstname, ' ', lastname) FROM admins WHERE id = m.sender_id)
                        ELSE (SELECT CONCAT(firstname, ' ', lastname) FROM users WHERE id = m.sender_id)
                    END,
                    ' (', m.sender_role, ')'
                )
              END as sender_name,
              m.timestamp as send_time
              FROM messages m 
              WHERE (
                (m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?) 
                OR 
                (m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?)
              )
              ORDER BY m.timestamp ASC";

    $admin_role = $_SESSION['role'];
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isisisisis", 
        $admin_id, $admin_role,
        $admin_id, $admin_role, $receiver_id, $receiver_role,
        $receiver_id, $receiver_role, $admin_id, $admin_role
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $conn->error);
    }
    
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);

    // Mark messages as read
    $update_query = "UPDATE messages 
                    SET is_read = 1 
                    WHERE receiver_id = ? 
                    AND receiver_role = ?
                    AND sender_id = ? 
                    AND sender_role = ?
                    AND is_read = 0";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("isis", $admin_id, $admin_role, $receiver_id, $receiver_role);
    $update_stmt->execute();

    echo json_encode([
        'success' => true, 
        'messages' => $messages,
        'current_chat' => [
            'user_id' => $receiver_id,
            'role' => $receiver_role
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_messages.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load messages',
        'messages' => []
    ]);
}
