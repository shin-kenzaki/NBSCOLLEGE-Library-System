<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accession = isset($_POST['accession']) ? $_POST['accession'] : '';
    $book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
    
    if (empty($accession)) {
        header('Content-Type: application/json');
        echo json_encode(['exists' => false]);
        exit();
    }
    
    // Check if accession exists excluding the current book
    $query = "SELECT id FROM books WHERE accession = ? AND id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $accession, $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $exists = $result->num_rows > 0;
    
    header('Content-Type: application/json');
    echo json_encode(['exists' => $exists]);
    exit();
}

header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid request method']);
exit();
