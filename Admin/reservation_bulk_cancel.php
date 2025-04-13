<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: book_reservations.php?error=Unauthorized access");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Ensure we have reservation IDs
if (!isset($_POST['reservation_ids']) || !is_array($_POST['reservation_ids']) || empty($_POST['reservation_ids'])) {
    header("Location: book_reservations.php?error=No reservations selected");
    exit();
}

// Convert to integers to prevent SQL injection
$ids = array_map('intval', $_POST['reservation_ids']);
$ids_string = implode(',', $ids);

// Start a transaction for data consistency
$conn->begin_transaction();

try {
    // First get book IDs so we can update their status
    $bookIdsQuery = "SELECT book_id FROM reservations WHERE id IN ($ids_string) AND (status = 'Reserved' OR status = 'Ready')";
    $bookResult = $conn->query($bookIdsQuery);
    
    $bookIds = [];
    while ($row = $bookResult->fetch_assoc()) {
        $bookIds[] = $row['book_id'];
    }
    
    if (empty($bookIds)) {
        throw new Exception("No valid reservations found to cancel");
    }
    
    // Update reservation status
    $updateQuery = "UPDATE reservations SET 
                   status = 'Cancelled', 
                   cancel_date = NOW(), 
                   cancelled_by = ?, 
                   cancelled_by_role = 'Admin'
                   WHERE id IN ($ids_string) AND (status = 'Reserved' OR status = 'Ready')";
                   
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("i", $admin_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update reservations: " . $conn->error);
    }
    
    $affectedRows = $stmt->affected_rows;
    
    // Update book status back to Available
    if (!empty($bookIds)) {
        $bookIds_string = implode(',', $bookIds);
        $updateBooksQuery = "UPDATE books SET status = 'Available' WHERE id IN ($bookIds_string)";
        
        if (!$conn->query($updateBooksQuery)) {
            throw new Exception("Failed to update book status: " . $conn->error);
        }
    }
    
    $conn->commit();
    
    header("Location: book_reservations.php?success=$affectedRows reservation(s) cancelled successfully");
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    header("Location: book_reservations.php?error=" . urlencode($e->getMessage()));
    exit();
}
