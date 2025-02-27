<?php
session_start();

if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian')) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    include('../db.php');
    $conn->begin_transaction();

    try {
        $book_id = $_GET['id'];

        // Get borrowing record details with status check
        $sql = "SELECT id, user_id, due_date, status FROM borrowings 
                WHERE book_id = ? AND return_date IS NULL 
                AND status IN ('Active', 'Overdue')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $borrowing = $result->fetch_assoc();
        
        if (!$borrowing) {
            throw new Exception("Valid borrowing record not found");
        }

        // Handle fine calculation only for Overdue status
        if ($borrowing['status'] === 'Overdue') {
            $due_date = new DateTime($borrowing['due_date']);
            $return_date = new DateTime(date('Y-m-d'));
            $days_overdue = $due_date->diff($return_date)->days;
            $fine_amount = $days_overdue * 5; // 5 pesos per day

            // Insert fine record for overdue books
            $sql = "INSERT INTO fines (borrowing_id, type, amount, status, date, payment_date) 
                   VALUES (?, 'Overdue', ?, 'Unpaid', CURDATE(), '0000-00-00')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("id", $borrowing['id'], $fine_amount);
            $stmt->execute();
        }

        // Update borrowing record for both statuses
        $admin_id = $_SESSION['admin_id'];
        $sql = "UPDATE borrowings SET 
                return_date = NOW(), 
                recieved_by = ?,
                status = 'Returned' 
                WHERE book_id = ? 
                AND return_date IS NULL 
                AND status IN ('Active', 'Overdue')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $admin_id, $book_id);
        $stmt->execute();

        // Update book status
        $sql = "UPDATE books SET status = 'Available' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();

        // Update only returned_books count
        $sql = "UPDATE users SET returned_books = returned_books + 1 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $borrowing['user_id']);
        $stmt->execute();

        $conn->commit();
        
        // Set appropriate success message based on status
        $success_message = "Book marked as returned successfully";
        if (isset($fine_amount)) {
            $success_message .= ". Fine of ₱" . number_format($fine_amount, 2) . " has been recorded";
        }
        header("Location: borrowed_books.php?success=" . urlencode($success_message));
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: borrowed_books.php?error=Failed to mark book as returned");
    }
    
    $conn->close();
} else {
    header("Location: borrowed_books.php?error=No book ID provided");
}
