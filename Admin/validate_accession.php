<?php
include '../db.php';

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

$accession = $data['accession'] ?? '';
$bookId = $data['bookId'] ?? '';

// Validate accession number
$query = "SELECT id FROM books WHERE accession = ? AND id != ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $accession, $bookId);
$stmt->execute();
$result = $stmt->get_result();

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'valid' => ($result->num_rows === 0)
]);
