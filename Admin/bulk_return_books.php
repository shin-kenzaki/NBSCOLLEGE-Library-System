<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrowIds'])) {
    $borrowIds = array_map('intval', $_POST['borrowIds']);
    $today = date('Y-m-d');
    $returnedCount = 0;
    $admin_id = $_SESSION['admin_id']; // Get current admin ID

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get borrowing details for updating user stats
        $stmt = $conn->prepare("SELECT b.user_id, b.book_id, DATEDIFF(?, b.due_date) as days_overdue 
                               FROM borrowings b 
                               WHERE b.id = ?");
        
        // Update borrowing record
        $updateBorrowing = $conn->prepare("UPDATE borrowings 
                                         SET status = 'Returned', 
                                             return_date = ?,
                                             recieved_by = ?
                                         WHERE id = ?");
        
        // Update user stats (only returned_books)
        $updateUser = $conn->prepare("UPDATE users 
                                    SET returned_books = returned_books + 1
                                    WHERE id = ?");

        // Update book status
        $updateBook = $conn->prepare("UPDATE books 
                                    SET status = 'Available'
                                    WHERE id = ?");

        foreach ($borrowIds as $borrowId) {
            // Get borrowing details
            $stmt->bind_param('si', $today, $borrowId);
            $stmt->execute();
            $result = $stmt->get_result();
            $borrowing = $result->fetch_assoc();

            if (!$borrowing) {
                throw new Exception("Borrowing record not found for ID: $borrowId");
            }

            // Update borrowing status - FIXED PARAMETER BINDING
            $updateBorrowing->bind_param('sii', $today, $admin_id, $borrowId);
            if (!$updateBorrowing->execute()) {
                throw new Exception("Failed to update borrowing status for ID: $borrowId");
            }

            // Update book status to Available
            $updateBook->bind_param('i', $borrowing['book_id']);
            if (!$updateBook->execute()) {
                throw new Exception("Failed to update book status for book ID: " . $borrowing['book_id']);
            }

            // Update user stats
            $updateUser->bind_param('i', $borrowing['user_id']);
            if (!$updateUser->execute()) {
                throw new Exception("Failed to update user stats for user ID: " . $borrowing['user_id']);
            }

            // Check if book was overdue and create fine if needed
            if ($borrowing['days_overdue'] > 0) {
                $fineAmount = $borrowing['days_overdue'] * 5.00; // ₱5 per day
                $insertFine = $conn->prepare("INSERT INTO fines (borrowing_id, type, amount, status, date) 
                                           VALUES (?, 'Overdue', ?, 'Unpaid', ?)");
                $insertFine->bind_param('ids', $borrowId, $fineAmount, $today);
                $insertFine->execute();
            }

            $returnedCount++;
        }

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => "$returnedCount book(s) returned successfully!"
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
