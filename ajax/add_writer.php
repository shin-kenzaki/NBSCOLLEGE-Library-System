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
$firstname = trim($_POST['firstname'] ?? '');
$middle_init = trim($_POST['middle_init'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');

// Validate required fields
if (empty($firstname) || empty($lastname)) {
    echo json_encode(['success' => false, 'message' => 'First name and last name are required']);
    exit();
}

try {
    // Check if writer already exists
    $check_query = "SELECT id FROM writers WHERE firstname = ? AND middle_init = ? AND lastname = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("sss", $firstname, $middle_init, $lastname);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'success' => false, 
            'message' => 'A contributor with this name already exists',
            'writer_id' => $row['id']
        ]);
        exit();
    }
    
    // Insert new writer
    $insert_query = "INSERT INTO writers (firstname, middle_init, lastname) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sss", $firstname, $middle_init, $lastname);
    $stmt->execute();
    
    $writer_id = $conn->insert_id;
    
    // Format the name for display
    $writer_name = $firstname;
    if (!empty($middle_init)) {
        $writer_name .= " " . $middle_init;
    }
    $writer_name .= " " . $lastname;
    
    echo json_encode([
        'success' => true,
        'message' => 'Contributor added successfully',
        'writer_id' => $writer_id,
        'writer_name' => $writer_name
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error adding contributor: ' . $e->getMessage()
    ]);
}
?>
