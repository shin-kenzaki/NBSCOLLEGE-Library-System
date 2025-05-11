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
$name = trim($_POST['name'] ?? '');
$type = trim($_POST['type'] ?? '');
$location = trim($_POST['location'] ?? '');
$description = trim($_POST['description'] ?? '');

// Validate required fields
if (empty($name) || empty($type)) {
    echo json_encode(['success' => false, 'message' => 'Name and type are required']);
    exit();
}

try {
    // Check if corporate entity already exists
    $check_query = "SELECT id FROM corporates WHERE name = ? AND type = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $name, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'success' => false, 
            'message' => 'A corporate body with this name and type already exists',
            'corporate_id' => $row['id']
        ]);
        exit();
    }
    
    // Insert new corporate entity
    $insert_query = "INSERT INTO corporates (name, type, location, description) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssss", $name, $type, $location, $description);
    $stmt->execute();
    
    $corporate_id = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'Corporate body added successfully',
        'corporate_id' => $corporate_id,
        'name' => $name,
        'type' => $type
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error adding corporate body: ' . $e->getMessage()
    ]);
}
?>
