<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];

// For JSON POST requests (bulk operation)
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if ($contentType === "application/json") {
    // Handle bulk cancel
    $content = trim(file_get_contents("php://input"));
    $decoded = json_decode($content, true);
    
    if(isset($decoded['ids']) && is_array($decoded['ids'])) {
        $ids = $decoded['ids'];
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
            header('Content-Type: application/json');
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
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Some reservations cannot be cancelled due to their status']);
            exit();
        }
        
        // Proceed with cancellation
        $cancelQuery = "UPDATE reservations SET status = 'Cancelled', cancel_date = NOW(), cancelled_by = ?, cancelled_by_role = 'User' 
                        WHERE id IN ($placeholders)";
        $cancelStmt = $conn->prepare($cancelQuery);
        
        // Create array with user_id at the start followed by all reservation ids
        $cancelParams = array_merge([$user_id], $ids);
        $cancelStmt->bind_param('i' . str_repeat('i', count($ids)), ...$cancelParams);
        
        if($cancelStmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Reservations cancelled successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error cancelling reservations: ' . $conn->error]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request format']);
    }
} else {
    // Handle single reservation cancel (for backward compatibility)
    if(isset($_POST['reservation_id'])) {
        $reservation_id = $_POST['reservation_id'];
        
        // Check if the reservation belongs to the current user
        $checkQuery = "SELECT status FROM reservations WHERE id = ? AND user_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param('ii', $reservation_id, $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if($checkResult->num_rows === 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You can only cancel your own reservations']);
            exit();
        }
        
        $row = $checkResult->fetch_assoc();
        if($row['status'] !== 'Pending' && $row['status'] !== 'Ready') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Only pending or ready reservations can be cancelled']);
            exit();
        }
        
        // Proceed with cancellation
        $cancelQuery = "UPDATE reservations SET status = 'Cancelled', cancel_date = NOW(), cancelled_by = ?, cancelled_by_role = 'User' WHERE id = ?";
        $cancelStmt = $conn->prepare($cancelQuery);
        $cancelStmt->bind_param('ii', $user_id, $reservation_id);
        
        if($cancelStmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Reservation cancelled successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error cancelling reservation: ' . $conn->error]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Reservation ID not provided']);
    }
}
?>
