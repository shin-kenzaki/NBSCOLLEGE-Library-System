<?php
session_start();
include '../db.php';

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Enable CORS for debugging
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// For handling preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Set content type for all responses
header('Content-Type: application/json');

// For JSON POST requests (bulk operation)
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

// Handle JSON request
if (strpos($contentType, 'application/json') !== false) {
    // Get the raw JSON content
    $content = file_get_contents("php://input");
    $decoded = json_decode($content, true);
    
    // Debug logging for troubleshooting
    error_log("Received content: " . $content);
    error_log("Decoded data: " . print_r($decoded, true));
    
    if(isset($decoded['ids']) && is_array($decoded['ids']) && !empty($decoded['ids'])) {
        $ids = array_map('intval', $decoded['ids']); // Ensure all ids are integers
        
        // Modified to handle single parameter binding
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        // Check if all reservations belong to the current user
        $checkQuery = "SELECT COUNT(*) AS count FROM reservations WHERE id IN ($placeholders) AND user_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        
        $types = str_repeat('i', count($ids)) . 'i';
        $checkParams = array_merge($ids, [$user_id]);
        $checkStmt->bind_param($types, ...$checkParams);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $row = $checkResult->fetch_assoc();
        
        if($row['count'] != count($ids)) {
            echo json_encode(['success' => false, 'message' => 'You can only cancel your own reservations']);
            exit();
        }
        
        // Check current status (can only cancel Pending or Ready)
        $statusQuery = "SELECT COUNT(*) AS count FROM reservations WHERE id IN ($placeholders) AND (status != 'Pending' AND status != 'Ready')";
        $statusStmt = $conn->prepare($statusQuery);
        $statusStmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $statusStmt->execute();
        $statusResult = $statusStmt->get_result();
        $statusRow = $statusResult->fetch_assoc();
        
        if($statusRow['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Some reservations cannot be cancelled due to their status']);
            exit();
        }
        
        // Get book IDs for each reservation to update their status
        $booksQuery = "SELECT book_id FROM reservations WHERE id IN ($placeholders)";
        $booksStmt = $conn->prepare($booksQuery);
        $booksStmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $booksStmt->execute();
        $booksResult = $booksStmt->get_result();
        $bookIds = [];
        while($book = $booksResult->fetch_assoc()) {
            $bookIds[] = $book['book_id'];
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Proceed with cancellation
            $cancelQuery = "UPDATE reservations SET status = 'Cancelled', cancel_date = NOW(), cancelled_by = ?, cancelled_by_role = 'User' 
                            WHERE id IN ($placeholders)";
            $cancelStmt = $conn->prepare($cancelQuery);
            
            // Create array with user_id at the start followed by all reservation ids
            $cancelParams = array_merge([$user_id], $ids);
            $cancelStmt->bind_param('i' . str_repeat('i', count($ids)), ...$cancelParams);
            $cancelStmt->execute();
            
            // Update book status to Available
            if (!empty($bookIds)) {
                $bookPlaceholders = implode(',', array_fill(0, count($bookIds), '?'));
                $updateBooksQuery = "UPDATE books SET status = 'Available' WHERE id IN ($bookPlaceholders)";
                $updateBooksStmt = $conn->prepare($updateBooksQuery);
                $updateBooksStmt->bind_param(str_repeat('i', count($bookIds)), ...$bookIds);
                $updateBooksStmt->execute();
            }
            
            // Commit the transaction
            $conn->commit();
            
            echo json_encode(['success' => true, 'message' => 'Reservations cancelled successfully']);
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error cancelling reservations: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid or missing reservation IDs']);
    }
} else {
    // Handle single reservation cancel (for backward compatibility)
    if(isset($_POST['reservation_id'])) {
        $reservation_id = $_POST['reservation_id'];
        
        // Check if the reservation belongs to the current user
        $checkQuery = "SELECT status, book_id FROM reservations WHERE id = ? AND user_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param('ii', $reservation_id, $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if($checkResult->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'You can only cancel your own reservations']);
            exit();
        }
        
        $row = $checkResult->fetch_assoc();
        if($row['status'] !== 'Pending' && $row['status'] !== 'Ready') {
            echo json_encode(['success' => false, 'message' => 'Only pending or ready reservations can be cancelled']);
            exit();
        }
        
        $book_id = $row['book_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update reservation status
            $cancelQuery = "UPDATE reservations SET status = 'Cancelled', cancel_date = NOW(), cancelled_by = ?, cancelled_by_role = 'User' WHERE id = ?";
            $cancelStmt = $conn->prepare($cancelQuery);
            $cancelStmt->bind_param('ii', $user_id, $reservation_id);
            $cancelStmt->execute();
            
            // Update book status to Available
            $updateBookQuery = "UPDATE books SET status = 'Available' WHERE id = ?";
            $updateBookStmt = $conn->prepare($updateBookQuery);
            $updateBookStmt->bind_param('i', $book_id);
            $updateBookStmt->execute();
            
            // Commit the transaction
            $conn->commit();
            
            echo json_encode(['success' => true, 'message' => 'Reservation cancelled successfully']);
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error cancelling reservation: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Reservation ID not provided']);
    }
}
?>
