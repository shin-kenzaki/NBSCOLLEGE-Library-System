<?php
session_start();
require_once '../../db.php';

// Check if user is logged in with appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

try {
    // Query to get all writers
    $writers_query = "SELECT id, CONCAT(lastname, ', ', firstname, ' ', middle_init) AS name FROM writers ORDER BY lastname, firstname";
    $writers_result = mysqli_query($conn, $writers_query);

    if (!$writers_result) {
        throw new Exception('Failed to retrieve writers: ' . mysqli_error($conn));
    }

    // Fetch writers data
    $writers = [];
    while ($row = mysqli_fetch_assoc($writers_result)) {
        $writers[] = [
            'id' => (int)$row['id'],
            'name' => $row['name']
        ];
    }

    // Return the writers data
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'writers' => $writers]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
