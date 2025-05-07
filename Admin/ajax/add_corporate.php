<?php
session_start();
require_once '../../db.php';

// Check if user is logged in with appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name']) || !isset($data['type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    // Sanitize inputs
    $name = mysqli_real_escape_string($conn, $data['name']);
    $type = mysqli_real_escape_string($conn, $data['type']);
    $location = isset($data['location']) ? mysqli_real_escape_string($conn, $data['location']) : '';
    $description = isset($data['description']) ? mysqli_real_escape_string($conn, $data['description']) : '';
    
    // Check if corporate entity already exists
    $check_query = "SELECT id FROM corporates WHERE name = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A corporate entity with this name already exists']);
        exit();
    }
    
    // Insert the new corporate entity
    $insert_query = "INSERT INTO corporates (name, type, location, description) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssss", $name, $type, $location, $description);
    
    if ($stmt->execute()) {
        $corporate_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Corporate entity added successfully',
            'corporate' => [
                'id' => $corporate_id,
                'name' => $name,
                'type' => $type,
                'location' => $location,
                'description' => $description
            ]
        ]);
    } else {
        throw new Exception("Error adding corporate entity: " . $conn->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>