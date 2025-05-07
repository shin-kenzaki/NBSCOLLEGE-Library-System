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

// Get JSON data from the request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!is_array($data) || empty($data)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No valid data received']);
    exit();
}

// Prepare response
$response = [
    'success' => true,
    'authors' => []
];

// Begin a transaction
mysqli_begin_transaction($conn);

try {
    foreach ($data as $author) {
        // Validate required fields
        if (empty($author['firstname']) || empty($author['lastname'])) {
            throw new Exception('First name and last name are required for all authors');
        }

        // Sanitize inputs
        $firstname = mysqli_real_escape_string($conn, $author['firstname']);
        $middle_init = mysqli_real_escape_string($conn, $author['middle_init'] ?? '');
        $lastname = mysqli_real_escape_string($conn, $author['lastname']);

        // Insert the writer
        $insert_query = "INSERT INTO writers (firstname, middle_init, lastname, date_added) 
                         VALUES ('$firstname', '$middle_init', '$lastname', NOW())";
        
        if (!mysqli_query($conn, $insert_query)) {
            throw new Exception('Error inserting author: ' . mysqli_error($conn));
        }

        $author_id = mysqli_insert_id($conn);
        $full_name = "$lastname, $firstname" . ($middle_init ? " $middle_init" : "");

        // Add to response
        $response['authors'][] = [
            'id' => $author_id,
            'name' => $full_name
        ];
    }

    // Commit the transaction
    mysqli_commit($conn);

    // Return success response
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    // Rollback the transaction on error
    mysqli_rollback($conn);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
