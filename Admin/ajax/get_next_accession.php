<?php
session_start();

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Include database connection
include '../../db.php';

// Function to get the next available accession number
function getNextAccessionNumber($conn) {
    // Query to find the highest numeric accession number
    $query = "SELECT accession FROM books WHERE accession REGEXP '^[0-9]+$' ORDER BY CAST(accession AS UNSIGNED) DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $highest_accession = intval($row['accession']);
        return $highest_accession + 1;
    } else {
        // If no numeric accessions found, start from 1
        return 1;
    }
}

try {
    $next_accession = getNextAccessionNumber($conn);
    
    echo json_encode([
        'success' => true,
        'next_accession' => $next_accession,
        'formatted_accession' => str_pad($next_accession, 4, '0', STR_PAD_LEFT)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
