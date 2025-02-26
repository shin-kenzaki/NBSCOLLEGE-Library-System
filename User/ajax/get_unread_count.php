<?php
session_start();
require_once('../../db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['usertype'] ?? '';

// Get count of unread messages with role verification
$query = "SELECT COUNT(*) as unread 
          FROM messages 
          WHERE receiver_id = ? 
          AND receiver_role = ?
          AND is_read = 0";

$stmt = $conn->prepare($query);
$stmt->bind_param('is', $user_id, $user_role);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['unread'];

echo json_encode(['count' => $count]);
