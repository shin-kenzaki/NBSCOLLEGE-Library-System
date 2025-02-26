<?php
session_start();
require_once('../../db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$sender_id = $_SESSION['user_id'];
$sender_role = $_SESSION['usertype']; // Get role from session usertype
$message = trim($data['message'] ?? '');
$receiver_role = $data['receiver_role'] ?? '';
$receiver_id = $data['receiver_id'] ?? '';

// Validate inputs
if (empty($message) || empty($receiver_role) || empty($receiver_id)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Validate receiver exists based on role type
$check_query = "SELECT id FROM " . 
               ($receiver_role === 'Admin' || $receiver_role === 'Librarian' || $receiver_role === 'Assistant' 
                ? "admins WHERE id = ? AND role = ?" 
                : "users WHERE id = ? AND usertype = ?");
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("is", $receiver_id, $receiver_role);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid recipient']);
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
