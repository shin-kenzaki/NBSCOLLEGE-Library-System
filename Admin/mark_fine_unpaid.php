<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

include '../db.php';

if (isset($_GET['id'])) {
    $fineId = intval($_GET['id']);

    $sql = "UPDATE fines SET status = 'Unpaid', payment_date = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fineId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Fine marked as unpaid']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to mark fine as unpaid']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
