<?php
session_start();
include '../../db.php';

// Check if the user is logged in and has appropriate permissions
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON data from request
$json_data = file_get_contents('php://input');
$authors_data = json_decode($json_data, true);

if (!$authors_data || !is_array($authors_data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data format']);
    exit();
}

$added_authors = [];
$has_errors = false;
$error_message = '';

// Process each author
foreach ($authors_data as $author) {
    $firstname = mysqli_real_escape_string($conn, $author['firstname']);
    $middle_init = mysqli_real_escape_string($conn, $author['middle_init']);
    $lastname = mysqli_real_escape_string($conn, $author['lastname']);
    
    // Check if author already exists
    $check_query = "SELECT id FROM writers WHERE 
                    firstname = '$firstname' AND 
                    middle_init = '$middle_init' AND 
                    lastname = '$lastname'";
    $result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($result) > 0) {
        // Author already exists
        $row = mysqli_fetch_assoc($result);
        $author_id = $row['id'];
        $added_authors[] = [
            'id' => $author_id,
            'name' => "$firstname $middle_init $lastname",
            'status' => 'existing'
        ];
    } else {
        // Add new author
        $insert_query = "INSERT INTO writers (firstname, middle_init, lastname) 
                        VALUES ('$firstname', '$middle_init', '$lastname')";
        if (mysqli_query($conn, $insert_query)) {
            $author_id = mysqli_insert_id($conn);
            $added_authors[] = [
                'id' => $author_id,
                'name' => "$firstname $middle_init $lastname",
                'status' => 'new'
            ];
        } else {
            $has_errors = true;
            $error_message .= "Error adding author $firstname $lastname: " . mysqli_error($conn) . "; ";
        }
    }
}

if ($has_errors) {
    echo json_encode([
        'success' => false,
        'message' => $error_message,
        'authors' => $added_authors // Return any authors that were successfully added
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'All authors added successfully',
        'authors' => $added_authors
    ]);
}
?>
