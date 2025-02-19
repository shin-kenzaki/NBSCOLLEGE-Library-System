<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian'])) {
    http_response_code(403);
    exit('Unauthorized');
}

if (!isset($_GET['id']) || !isset($_GET['type']) || !isset($_GET['isbn'])) {
    http_response_code(400);
    exit('Missing parameters');
}

$borrowId = intval($_GET['id']);
$type = $_GET['type'];
$isbn = $_GET['isbn'];
$currentDate = date('Y-m-d');

// Start transaction
$conn->begin_transaction();

try {
    // Get the book details and verify ISBN
    $stmt = $conn->prepare("
        SELECT b.book_id, b.user_id, bk.isbn 
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $borrowId);
    $stmt->execute();
    $result = $stmt->get_result();
    $borrowing = $result->fetch_assoc();
    
    if (!$borrowing) {
        throw new Exception("Borrowing record not found");
    }

    // Update the book's status and ISBN
    $stmt = $conn->prepare("UPDATE books SET status = 'Available', isbn = ? WHERE id = ?");
    $stmt->bind_param("si", $isbn, $borrowing['book_id']);
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
