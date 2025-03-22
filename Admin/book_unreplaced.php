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

// Start transaction
$conn->begin_transaction();

try {
    // Get the book details
    $stmt = $conn->prepare("
        SELECT b.book_id, b.user_id, b.replacement_date 
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
    
    // Check if the book is marked as replaced
    if ($borrowing['replacement_date'] === null) {
        throw new Exception("This book is not marked as replaced");
    }

    // Update the book's status back to Damaged or Lost
    $status = ($type == 'damaged') ? 'Damaged' : 'Lost';
    $stmt = $conn->prepare("UPDATE books SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $borrowing['book_id']);
    $stmt->execute();

    // Remove the replacement date from the borrowing record
    $stmt = $conn->prepare("UPDATE borrowings SET replacement_date = NULL WHERE id = ?");
    $stmt->bind_param("i", $borrowId);
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
