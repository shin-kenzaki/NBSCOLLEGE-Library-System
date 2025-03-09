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
        // First get the borrow dates
        $stmt = $conn->prepare("SELECT id, issue_date FROM borrowings WHERE id = ?");
        $updateStmt = $conn->prepare("UPDATE borrowings SET due_date = ?, reminder_sent = 0 WHERE id = ?");
        
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
            $borrowDate = new DateTime($borrowing['issue_date']);
            $dueDate = new DateTime($newDueDate);
            $interval = $borrowDate->diff($dueDate);
            $allowedDays = $interval->days;

            // Check if new due date is at least 7 days after borrow date
            if ($allowedDays < 7) {
                throw new Exception("Due date must be at least 7 days after the borrow date. Book borrowed on: " . $borrowing['issue_date']);
            }

            // Update due_date
            $updateStmt->bind_param('si', $newDueDate, $borrowId);
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update borrow ID: $borrowId");
            }
        }
        
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Due dates updated successfully!'
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
?>
