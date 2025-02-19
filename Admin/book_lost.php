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

        // Update borrowing record
        $sql = "UPDATE borrowings SET status = 'Lost', report_date = CURDATE() WHERE book_id = ? AND return_date IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();

        // Update book status
        $sql = "UPDATE books SET status = 'Lost' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();

        // Update user statistics
        $sql = "UPDATE users SET 
                lost_books = lost_books + 1
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $borrowing['user_id']);
        $stmt->execute();

        $conn->commit();
        header("Location: borrowed_books.php?success=Book marked as lost");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: borrowed_books.php?error=Failed to mark book as lost");
    }
    
    $conn->close();
} else {
    header("Location: borrowed_books.php?error=No book ID provided");
}
