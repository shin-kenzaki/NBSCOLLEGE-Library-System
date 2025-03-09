<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

include('../db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_ids = $_POST['book_id']; // Array of book IDs
    $user_id = $_POST['user_id'];
    $admin_id = $_SESSION['admin_id'];

    // Set the default values
    $status = 'Active';
    $borrow_date = date('Y-m-d H:i:s'); // current timestamp
    $allowed_days = 7; // default allowed days

    // Start transaction
    $conn->begin_transaction();

    try {
        $processed_titles = []; // Track processed book titles

        foreach ($book_ids as $book_id) {
            // Get the book title and accession first
            $get_book = $conn->prepare("SELECT title, accession FROM books WHERE id = ?");
            $get_book->bind_param("i", $book_id);
            $get_book->execute();
            $book_result = $get_book->get_result();
            $book = $book_result->fetch_assoc();
            $book_title = $book['title'];
            $book_accession = $book['accession'];

            // Skip if the book title has already been processed
            if (in_array($book_title, $processed_titles)) {
                continue;
            }
            
            // Add to processed titles
            $processed_titles[] = $book_title;

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

            // Check the shelf location using the accession
            $get_shelf_location = $conn->prepare("SELECT shelf_location FROM books WHERE accession = ?");
            $get_shelf_location->bind_param("s", $book_accession);
            $get_shelf_location->execute();
            $shelf_result = $get_shelf_location->get_result();
            $shelf_location = $shelf_result->fetch_assoc()['shelf_location'];

            // Adjust allowed days based on shelf location
            if ($shelf_location == 'RES') {
                $allowed_days = 1;
            } elseif ($shelf_location == 'REF') {
                $allowed_days = 0; // current day only
            }

            $due_date = date('Y-m-d H:i:s', strtotime("+{$allowed_days} days")); // calculate due date

            // Insert borrowing record
            $sql = "INSERT INTO borrowings (user_id, book_id, issued_by, issue_date, due_date, status) 
                    VALUES (?, ?, ?, NOW(), ?, 'Active')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiis", $user_id, $book_id, $admin_id, $due_date);
            
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
        }

        // Commit transaction after all books are processed successfully
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Books borrowed successfully']);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
