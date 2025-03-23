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
$publishers_data = json_decode($json_data, true);

if (!$publishers_data || !is_array($publishers_data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data format']);
    exit();
}

$added_publishers = [];
$has_errors = false;
$error_message = '';

// Process each publisher
foreach ($publishers_data as $publisher) {
    $publisher_name = mysqli_real_escape_string($conn, $publisher['publisher']);
    $place = mysqli_real_escape_string($conn, $publisher['place']);
    
    // Check if publisher already exists
    $check_query = "SELECT id FROM publishers WHERE publisher = '$publisher_name'";
    $result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($result) > 0) {
        // Publisher already exists
        $row = mysqli_fetch_assoc($result);
        $publisher_id = $row['id'];
        $added_publishers[] = [
            'id' => $publisher_id,
            'publisher' => $publisher_name,
            'place' => $place,
            'status' => 'existing'
        ];
    } else {
        // Add new publisher
        $insert_query = "INSERT INTO publishers (publisher, place) 
                        VALUES ('$publisher_name', '$place')";
        if (mysqli_query($conn, $insert_query)) {
            $publisher_id = mysqli_insert_id($conn);
            $added_publishers[] = [
                'id' => $publisher_id,
                'publisher' => $publisher_name,
                'place' => $place,
                'status' => 'new'
            ];
        } else {
            $has_errors = true;
            $error_message .= "Error adding publisher $publisher_name: " . mysqli_error($conn) . "; ";
        }
    }
}

if ($has_errors) {
    echo json_encode([
        'success' => false,
        'message' => $error_message,
        'publishers' => $added_publishers // Return any publishers that were successfully added
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'All publishers added successfully',
        'publishers' => $added_publishers
    ]);
}
?>
