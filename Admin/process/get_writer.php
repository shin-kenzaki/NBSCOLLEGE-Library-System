<?php
session_start();
include '../../db.php';

// Check if user is logged in with appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid writer ID']);
    exit();
}

$writerId = intval($_GET['id']);

// Fetch writer details
$query = "SELECT id, firstname, middle_init, lastname FROM writers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $writerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Writer not found']);
    exit();
}

// Return writer data as JSON
$writer = $result->fetch_assoc();
echo json_encode($writer);
exit();
?>
