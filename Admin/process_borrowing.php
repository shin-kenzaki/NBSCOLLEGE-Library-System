<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian')) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_id = $_POST['book_id'];
    $user_id = $_POST['user_id'];
    
    // Set the values as specified
    $status = 'Active';
    $borrow_date = date('Y-m-d H:i:s'); // current timestamp
    $allowed_days = 7;
    $due_date = date('Y-m-d H:i:s', strtotime("+{$allowed_days} days")); // calculate due date

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get the book title first
        $get_title = $conn->prepare("SELECT title FROM books WHERE id = ?");
        $get_title->bind_param("i", $book_id);
        $get_title->execute();
        $title_result = $get_title->get_result();
        $book_title = $title_result->fetch_assoc()['title'];

        // Check if user already has borrowed the same book title
        $check_duplicate = $conn->prepare("
            SELECT b1.title 
            FROM books b1
            JOIN borrowings br ON b1.id = br.book_id
            WHERE br.user_id = ? 
            AND b1.title = ?
            AND br.status = 'Active'
        ");
        $check_duplicate->bind_param("is", $user_id, $book_title);
        $check_duplicate->execute();
        $duplicate_result = $check_duplicate->get_result();

        if ($duplicate_result->num_rows > 0) {
            throw new Exception("You already have an active loan for the book titled: " . $book_title);
        }

        // Check if book is available
        $check_book = $conn->prepare("SELECT status FROM books WHERE id = ? AND status = 'Available'");
        $check_book->bind_param("i", $book_id);
        $check_book->execute();
        $result = $check_book->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Book is not available for borrowing");
        }

        // Insert borrowing record
        $insert_query = $conn->prepare("INSERT INTO borrowings (
            book_id, 
            user_id, 
            status, 
            borrow_date, 
            allowed_days, 
            due_date
        ) VALUES (?, ?, ?, ?, ?, ?)");
        
        $insert_query->bind_param("iissis", 
            $book_id, 
            $user_id, 
            $status, 
            $borrow_date, 
            $allowed_days, 
            $due_date
        );
        
        if (!$insert_query->execute()) {
            throw new Exception("Error creating borrowing record");
        }

        // Update book status
        $update_book = $conn->prepare("UPDATE books SET status = 'Borrowed' WHERE id = ?");
        $update_book->bind_param("i", $book_id);
        
        if (!$update_book->execute()) {
            throw new Exception("Error updating book status");
        }

        // Commit transaction
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Book borrowed successfully']);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
