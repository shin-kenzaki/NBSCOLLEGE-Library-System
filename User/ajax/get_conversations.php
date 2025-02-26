<?php
session_start();
require_once('../../db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['usertype'] ?? '';

try {
    // Modified query to ensure all conversations are returned regardless of read status
    $query = "WITH RECURSIVE LastMessages AS (
        SELECT 
            CASE WHEN m.sender_id = ? AND m.sender_role = ? THEN m.receiver_id ELSE m.sender_id END as contact_id,
            CASE WHEN m.sender_id = ? AND m.sender_role = ? THEN m.receiver_role ELSE m.sender_role END as contact_role,
            m.timestamp,
            m.message,
            ROW_NUMBER() OVER (
                PARTITION BY 
                    CASE WHEN m.sender_id = ? AND m.sender_role = ? THEN m.receiver_id ELSE m.sender_id END,
                    CASE WHEN m.sender_id = ? AND m.sender_role = ? THEN m.receiver_role ELSE m.sender_role END
                ORDER BY m.timestamp DESC
            ) as rn
        FROM messages m
        WHERE (m.sender_id = ? AND m.sender_role = ?) 
           OR (m.receiver_id = ? AND m.receiver_role = ?)
    )
    SELECT DISTINCT
        lm.contact_id,
        lm.contact_role,
        lm.message as last_message_text,
        lm.timestamp as last_message_time,
        CASE 
            WHEN m.sender_id = ? AND m.sender_role = ? THEN 'You'
            ELSE 
                CASE 
                    WHEN m.sender_role IN ('Admin', 'Librarian', 'Assistant')
                    THEN (SELECT firstname FROM admins WHERE id = m.sender_id)
                    ELSE (SELECT firstname FROM users WHERE id = m.sender_id)
                END
        END as last_messenger,
        CASE 
            WHEN lm.contact_role IN ('Admin', 'Librarian', 'Assistant') 
            THEN CONCAT((SELECT CONCAT(firstname, ' ', lastname) FROM admins WHERE id = lm.contact_id), ' (', lm.contact_role, ')')
            ELSE CONCAT((SELECT CONCAT(firstname, ' ', lastname) FROM users WHERE id = lm.contact_id), ' (', lm.contact_role, ')')
        END as contact_name,
        CASE 
            WHEN lm.contact_role IN ('Admin', 'Librarian', 'Assistant') 
            THEN COALESCE((SELECT image FROM admins WHERE id = lm.contact_id), 'inc/img/default-avatar.jpg')
            ELSE COALESCE((SELECT user_image FROM users WHERE id = lm.contact_id), 'inc/img/default-avatar.jpg')
        END as contact_image,
        lm.timestamp as last_message_time,
        lm.message as last_message,
        COALESCE((
            SELECT COUNT(*) 
            FROM messages m2 
            WHERE m2.sender_id = lm.contact_id 
            AND m2.sender_role = lm.contact_role
            AND m2.receiver_id = ?
            AND m2.receiver_role = ?
            AND m2.is_read = 0
        ), 0) as unread_count
    FROM LastMessages lm
    LEFT JOIN messages m ON m.timestamp = lm.timestamp 
        AND ((m.sender_id = lm.contact_id AND m.sender_role = lm.contact_role)
        OR (m.receiver_id = lm.contact_id AND m.receiver_role = lm.contact_role))
    WHERE lm.rn = 1
    ORDER BY lm.timestamp DESC";

    $user_role = $_SESSION['usertype'];
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssssssssssssii', 
        $user_id, $user_role,
        $user_id, $user_role,
        $user_id, $user_role,
        $user_id, $user_role,
        $user_id, $user_role,
        $user_id, $user_role,
        $user_id, $user_role,
        $user_id, $user_role
    );

    $stmt->execute();
    $result = $stmt->get_result();

    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = [
            'id' => $row['contact_id'],
            'role' => $row['contact_role'],
            'name' => $row['contact_name'] ?? 'Unknown User',
            'image' => $row['contact_image'] ?? 'inc/img/default-avatar.jpg',
            'last_message' => $row['last_message_time'],
            'unread' => (int)$row['unread_count'],
            'last_message_text' => $row['last_message_text'],
            'last_messenger' => $row['last_messenger']
        ];
    }

    echo json_encode($conversations);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
