<?php
session_start();

// Check if the user is logged in with admin permissions
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include the database connection
require_once '../../db.php';

// Query to get all writers
$writers_query = "SELECT id, CONCAT(lastname, ', ', firstname, ' ', middle_init) AS name FROM writers ORDER BY lastname, firstname";
$writers_result = mysqli_query($conn, $writers_query);

if (!$writers_result) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve writers: ' . mysqli_error($conn)]);
    exit();
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
?>
