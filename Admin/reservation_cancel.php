<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get input data - either from GET for single or POST for bulk
$ids = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $ids[] = intval($_GET['id']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (isset($data['ids']) && !empty($data['ids'])) {
        $ids = array_map('intval', $data['ids']);
    }
}

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No reservations selected']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    $ids_string = implode(',', $ids);
    
    // Get all reservations that are in READY status to update their books
    $query = "SELECT id, book_id, status 
             FROM reservations 
             WHERE id IN ($ids_string) 
             AND status = 'READY'
             AND recieved_date IS NULL 
             AND cancel_date IS NULL";
    
    $result = $conn->query($query);
    $book_ids = [];
    
    while ($row = $result->fetch_assoc()) {
        $book_ids[] = $row['book_id'];
    }

    // Update reservations
    $query = "UPDATE reservations 
              SET status = 'Cancelled', 
                  cancel_date = NOW() 
              WHERE id IN ($ids_string) 
              AND recieved_date IS NULL 
              AND cancel_date IS NULL";
    
    if (!$conn->query($query)) {
        throw new Exception("Error updating reservations");
    }

    // Update books status back to Available only for those that were in Ready status
    if (!empty($book_ids)) {
        $book_ids_string = implode(',', $book_ids);
        $query = "UPDATE books 
                  SET status = 'Available' 
                  WHERE id IN ($book_ids_string)";
        
        if (!$conn->query($query)) {
            throw new Exception("Error updating books");
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
