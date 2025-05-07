<?php
session_start();
require_once '../../db.php';

// Check if user is logged in with appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

try {
    // Fetch all corporate entities
    $query = "SELECT id, name, type, location FROM corporates ORDER BY name ASC";
    $result = $conn->query($query);
    
    $corporates = [];
    while ($row = $result->fetch_assoc()) {
        $corporates[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'type' => $row['type'],
            'location' => $row['location']
        ];
    }
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($corporates);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>