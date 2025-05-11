<?php
session_start();
header('Content-Type: application/json');

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
include '../db.php';

// Get form data
$publisher = trim($_POST['publisher'] ?? '');
$place = trim($_POST['place'] ?? '');

// Validate required fields
if (empty($publisher) || empty($place)) {
    echo json_encode(['success' => false, 'message' => 'Publisher name and place are required']);
    exit();
}

try {
    // Check if publisher already exists
    $check_query = "SELECT id FROM publishers WHERE publisher = ? AND place = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $publisher, $place);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'success' => false, 
            'message' => 'A publisher with this name and place already exists',
            'publisher_id' => $row['id']
        ]);
        exit();
    }
    
    // Insert new publisher
    $insert_query = "INSERT INTO publishers (publisher, place) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ss", $publisher, $place);
    $stmt->execute();
    
    $publisher_id = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'Publisher added successfully',
        'publisher_id' => $publisher_id,
        'publisher_name' => $publisher,
        'publisher_place' => $place
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error adding publisher: ' . $e->getMessage()
    ]);
}
?>
