<?php
session_start();
require_once '../../db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if the user is logged in and has appropriate permissions
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if request is POST and contains JSON data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data || !is_array($data)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data format. Expected JSON array of publishers.'
    ]);
    exit;
}

// Initialize response array
$response = [
    'success' => true,
    'message' => '',
    'publishers' => []
];

// Process each publisher in the array
foreach ($data as $publisher) {
    // Validate required fields
    if (empty($publisher['publisher']) || empty($publisher['place'])) {
        continue; // Skip entries with missing required fields
    }
    
    // Prepare data for insertion
    $publisher_name = mysqli_real_escape_string($conn, $publisher['publisher']);
    $place = mysqli_real_escape_string($conn, $publisher['place']);
    
    // Check if publisher already exists
    $check_query = "SELECT id FROM publishers WHERE publisher = '$publisher_name' AND place = '$place'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Publisher already exists, get its ID
        $row = mysqli_fetch_assoc($check_result);
        $publisher_id = $row['id'];
        
        // Add to response
        $response['publishers'][] = [
            'id' => $publisher_id,
            'publisher' => $publisher_name,
            'place' => $place,
            'status' => 'existing'
        ];
    } else {
        // Insert the new publisher
        $insert_query = "INSERT INTO publishers (publisher, place) 
                        VALUES ('$publisher_name', '$place')";
                        
        if (mysqli_query($conn, $insert_query)) {
            $publisher_id = mysqli_insert_id($conn);
            
            // Add to response
            $response['publishers'][] = [
                'id' => $publisher_id,
                'publisher' => $publisher_name,
                'place' => $place,
                'status' => 'new'
            ];
        } else {
            // Log error but continue with other publishers
            error_log("Error adding publisher: " . mysqli_error($conn));
        }
    }
}

// Set success message based on number of publishers added
$count = count($response['publishers']);
if ($count > 0) {
    $response['message'] = "Successfully added $count publisher(s)";
} else {
    $response['success'] = false;
    $response['message'] = "No publishers were added. Please check your input.";
}

// Return JSON response
echo json_encode($response);
?>
