<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No admin ID specified']);
    exit();
}

$admin_id = $_GET['id'];

// Prevent admin from deleting their own account
if ($_SESSION['admin_id'] == $admin_id) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account']);
    exit();
}

// Delete the admin
$stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Admin has been deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error deleting admin: ' . $conn->error]);
}

$stmt->close();
$conn->close();
