<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accession'])) {
    $accession = $_POST['accession'];
    
    // Prepare query to find book by accession number
    $query = "SELECT id, title, accession, shelf_location FROM books 
              WHERE accession = ? AND status = 'Available'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $accession);
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
            'message' => 'No available book found with this accession number'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
}
?>
