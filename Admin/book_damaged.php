<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    include('../db.php');
    $conn->begin_transaction();

    try {
        $book_id = $_GET['id'];

        // Get user_id from borrowing record
        $sql = "SELECT user_id FROM borrowings WHERE book_id = ? AND return_date IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $borrowing = $result->fetch_assoc();

        if (!$borrowing) {
            throw new Exception("Borrowing record not found");
        }

        // Update borrowing record including return_date
        $admin_id = $_SESSION['admin_id'];
        $sql = "UPDATE borrowings SET status = 'Damaged', report_date = CURDATE(), recieved_by = ?, return_date = CURDATE() WHERE book_id = ? AND return_date IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $admin_id, $book_id);
        $stmt->execute();

        // Update book status
        $sql = "UPDATE books SET status = 'Damaged' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();

        // Update user statistics
        $sql = "UPDATE users SET damaged_books = damaged_books + 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $borrowing['user_id']);
        $stmt->execute();

        $conn->commit();
        header("Location: borrowed_books.php?success=Book marked as damaged");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: borrowed_books.php?error=Failed to mark book as damaged");
    }
    
    $conn->close();
} else {
    header("Location: borrowed_books.php?error=No book ID provided");
}
