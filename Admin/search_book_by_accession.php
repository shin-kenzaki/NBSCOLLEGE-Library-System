<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accession'])) {
    $accession = $_POST['accession'];
    
    // Search for the book with the given accession number
    $stmt = $conn->prepare("SELECT id, title, accession FROM books WHERE accession = ? AND status = 'Available'");
    $stmt->bind_param("s", $accession);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'book' => $book
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Book not found or not available'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
}

$conn->close();
