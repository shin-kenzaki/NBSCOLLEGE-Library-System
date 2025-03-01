<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

include('../db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_id = $_POST['book_id'];
    $user_id = $_POST['user_id'];
    $admin_id = $_SESSION['admin_id'];
    
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
        $sql = "INSERT INTO borrowings (user_id, book_id, issued_by, issue_date, due_date, status) 
                VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), 'Active')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $user_id, $book_id, $admin_id, $allowed_days);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating borrowing record");
        }

        // Update book status
        $update_book = $conn->prepare("UPDATE books SET status = 'Borrowed' WHERE id = ?");
        $update_book->bind_param("i", $book_id);
        
        if (!$update_book->execute()) {
            throw new Exception("Error updating book status");
        }

        // Increment user's borrowed_books count
        $update_user = $conn->prepare("UPDATE users SET borrowed_books = borrowed_books + 1 WHERE id = ?");
        $update_user->bind_param("i", $user_id);
        
        if (!$update_user->execute()) {
            throw new Exception("Error updating user's borrow count");
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
