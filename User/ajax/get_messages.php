<?php
session_start();
require_once('../../db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit();
}

$user_id = $_SESSION['user_id'];
$receiver_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
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
    // Updated query to include role in the comparison
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
              m.timestamp as send_time,
              m.sender_role as original_sender_role  /* Add this line to track original role */
              FROM messages m 
              WHERE (
                (m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?) 
                OR 
                (m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?)
              )
              ORDER BY m.timestamp ASC";

    $user_role = $_SESSION['usertype']; // Get current user's role
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isisisisis", 
        $user_id, $user_role,
        $user_id, $user_role, $receiver_id, $receiver_role,
        $receiver_id, $receiver_role, $user_id, $user_role
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed");
    }
    
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        // Add this check to properly identify messages
        $isCurrentUser = ($row['sender_id'] == $user_id && $row['original_sender_role'] == $_SESSION['usertype']);
        $messages[] = array_merge($row, ['is_current_user' => $isCurrentUser]);
    }

    // Only mark messages as read if the current user is the receiver
    $update_query = "UPDATE messages 
                    SET is_read = 1 
                    WHERE receiver_id = ? 
                    AND sender_id = ? 
                    AND is_read = 0
                    AND receiver_role = ?"; // Added receiver_role check
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("iis", $user_id, $receiver_id, $_SESSION['usertype']);
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
?>
