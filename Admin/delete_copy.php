<?php
session_start();

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Include database connection
include '../db.php';

// Get the JSON input
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Check if bookId is provided
if (!isset($data['bookId']) || empty($data['bookId'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Book ID is required']);
    exit;
}

$bookId = intval($data['bookId']);

// Start transaction
$conn->begin_transaction();

try {
    // Check if the book exists
    $check_book = "SELECT * FROM books WHERE id = ?";
    $stmt = $conn->prepare($check_book);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $book_result = $stmt->get_result();
    
    if ($book_result->num_rows === 0) {
        throw new Exception("Book not found");
    }
    
    $book = $book_result->fetch_assoc();
    
    // Check if the book is being borrowed
    $check_borrowed = "SELECT id FROM borrowings WHERE book_id = ? AND return_date IS NULL";
    $stmt = $conn->prepare($check_borrowed);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $borrowed_result = $stmt->get_result();
    
    if ($borrowed_result->num_rows > 0) {
        throw new Exception("Cannot delete this book because it is currently borrowed");
    }
    
    // Check if the book has active reservations
    $check_reserved = "SELECT id FROM reservations WHERE book_id = ? AND status = 'Pending' AND cancel_date IS NULL AND recieved_date IS NULL";
    $stmt = $conn->prepare($check_reserved);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $reserved_result = $stmt->get_result();
    
    if ($reserved_result->num_rows > 0) {
        throw new Exception("Cannot delete this book because it has active reservations");
    }
    
    // Delete from contributors table
    $delete_contributors = "DELETE FROM contributors WHERE book_id = ?";
    $stmt = $conn->prepare($delete_contributors);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    
    // Delete from publications table
    $delete_publications = "DELETE FROM publications WHERE book_id = ?";
    $stmt = $conn->prepare($delete_publications);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    
    // Delete the book
    $delete_book = "DELETE FROM books WHERE id = ?";
    $stmt = $conn->prepare($delete_book);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();

    // Check if there are remaining copies of the book
    $remaining_copies_query = "SELECT id FROM books WHERE title = ? AND status = 'Available' LIMIT 1";
    $stmt = $conn->prepare($remaining_copies_query);
    $stmt->bind_param("s", $book['title']);
    $stmt->execute();
    $remaining_copies_result = $stmt->get_result();

    if ($remaining_copies_result->num_rows > 0) {
        $remaining_copy = $remaining_copies_result->fetch_assoc();
        $selectedBookId = $remaining_copy['id'];
    } else {
        // No copies remain, navigate to book list
        $conn->commit();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Book copy deleted successfully. Redirecting to book list.',
            'redirect' => 'book_list.php'
        ]);
        exit;
    }

    // Commit the transaction
    $conn->commit();

    // Return success response with the selected book ID
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Book copy deleted successfully',
        'selectedBookId' => $selectedBookId
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction
    $conn->rollback();
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}

// Close connection
$conn->close();
?>
