<?php
session_start();

if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrowIds']) && isset($_POST['newDueDate'])) {
    $borrowIds = array_map('intval', $_POST['borrowIds']);
    $newDueDate = $_POST['newDueDate'];
    
    // Validate date format
    if (!strtotime($newDueDate)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit();
    }

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First get the borrow dates and calculate new allowed_days for each borrowing
        $stmt = $conn->prepare("SELECT id, borrow_date FROM borrowings WHERE id = ?");
        $updateStmt = $conn->prepare("UPDATE borrowings SET due_date = ?, allowed_days = ? WHERE id = ?");
        
        foreach ($borrowIds as $borrowId) {
            // Get the borrow date
            $stmt->bind_param('i', $borrowId);
            $stmt->execute();
            $result = $stmt->get_result();
            $borrowing = $result->fetch_assoc();
            
            if (!$borrowing) {
                throw new Exception("Borrowing record not found for ID: $borrowId");
            }

            // Calculate days difference
            $borrowDate = new DateTime($borrowing['borrow_date']);
            $dueDate = new DateTime($newDueDate);
            $interval = $borrowDate->diff($dueDate);
            $allowedDays = $interval->days;

            // Check if new due date is at least 7 days after borrow date
            if ($allowedDays < 7) {
                throw new Exception("Due date must be at least 7 days after the borrow date. Book borrowed on: " . $borrowing['borrow_date']);
            }

            // Update both due_date and allowed_days
            $updateStmt->bind_param('sii', $newDueDate, $allowedDays, $borrowId);
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update borrow ID: $borrowId");
            }
        }
        
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Due dates and allowed days updated successfully!'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request parameters'
    ]);
}
