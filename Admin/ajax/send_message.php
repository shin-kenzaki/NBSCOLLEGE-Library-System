<?php
session_start();
require_once('../../db.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$sender_id = $_SESSION['admin_id'];
$sender_role = $_SESSION['role']; // Get role from session (Admin, Librarian, Assistant)
$message = trim($data['message'] ?? '');
$receiver_role = $data['receiver_role'] ?? '';
$receiver_id = $data['receiver_id'] ?? '';

// Validate inputs
if (empty($message) || empty($receiver_role) || empty($receiver_id)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Insert message
$query = "INSERT INTO messages (
            sender_id, receiver_id, sender_role, receiver_role, 
            message, timestamp, is_read
          ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, 0)";

$stmt = $conn->prepare($query);
$stmt->bind_param("iisss", 
    $sender_id, $receiver_id, $sender_role, $receiver_role, $message
);

$response = ['success' => false];

if ($stmt->execute()) {
    $response = [
        'success' => true,
        'message_id' => $stmt->insert_id,
        'timestamp' => date('Y-m-d H:i:s'),
        'receiver_id' => $receiver_id,
        'receiver_role' => $receiver_role
    ];
}

echo json_encode($response);
