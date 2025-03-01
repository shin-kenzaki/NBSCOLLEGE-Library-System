<?php
session_start();
include('../db.php');

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !isset($_GET['type'])) {
    http_response_code(400);
    exit('Missing parameters');
}

$borrowId = intval($_GET['id']);
$type = $_GET['type'];
$currentDate = date('Y-m-d');

// Start transaction
$conn->begin_transaction();

try {
    // Get the book details
    $stmt = $conn->prepare("
        SELECT b.book_id, b.user_id
        FROM borrowings b
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $borrowId);
    $stmt->execute();
    $result = $stmt->get_result();
    $borrowing = $result->fetch_assoc();
    
    if (!$borrowing) {
        throw new Exception("Borrowing record not found");
    }

    // Update the book's status
    $stmt = $conn->prepare("UPDATE books SET status = 'Available' WHERE id = ?");
    $stmt->bind_param("i", $borrowing['book_id']);
    $stmt->execute();

    // Update the borrowing record with replacement date
    $stmt = $conn->prepare("UPDATE borrowings SET replacement_date = ? WHERE id = ?");
    $stmt->bind_param("si", $currentDate, $borrowId);
    $stmt->execute();

    $conn->commit();
    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
