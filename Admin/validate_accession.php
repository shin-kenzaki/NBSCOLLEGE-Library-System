<?php
session_start();

// Check if the user is logged in with appropriate role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header('Content-Type: application/json');
    echo json_encode(['valid' => false, 'error' => 'Unauthorized access']);
    exit();
}

include '../db.php';

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Get accession and book ID
$accession = $data['accession'] ?? '';
$bookId = $data['bookId'] ?? '';

// Default response
$response = ['valid' => false];

// Validate accession number
if (!empty($accession) && !empty($bookId)) {
    // Check if accession exists in other books
    $query = "SELECT id FROM books WHERE accession = ? AND id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $accession, $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If no other book has this accession, it's valid
    if ($result->num_rows === 0) {
        $response['valid'] = true;
    } else {
        $response['error'] = 'This accession number is already in use by another book.';
    }
    
    $stmt->close();
} else {
    $response['error'] = 'Missing required data.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
