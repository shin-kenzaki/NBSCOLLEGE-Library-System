<?php
session_start();

// Check if the user is logged in with appropriate role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

include '../db.php';

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Get book ID
$bookId = isset($data['bookId']) ? (int)$data['bookId'] : 0;

// Default response
$response = ['success' => false];

if ($bookId > 0) {
    try {
        $conn->begin_transaction();
        
        // Check if the book can be deleted (not borrowed, not in reservation)
        $check_query = "SELECT b.id FROM borrowings b WHERE b.book_id = ? AND b.status IN ('Borrowed', 'Overdue')";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Cannot delete: Book is currently borrowed.");
        }
        
        // Check reservations
        $check_reservations = "SELECT r.id FROM reservations r WHERE r.book_id = ? AND r.status = 'Pending'";
        $stmt = $conn->prepare($check_reservations);
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Cannot delete: Book has pending reservations.");
        }
        
        // Delete from contributors
        $del_contributors = "DELETE FROM contributors WHERE book_id = ?";
        $stmt = $conn->prepare($del_contributors);
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        
        // Delete from publications
        $del_publications = "DELETE FROM publications WHERE book_id = ?";
        $stmt = $conn->prepare($del_publications);
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        
        // Delete from cart
        $del_cart = "DELETE FROM cart WHERE book_id = ?";
        $stmt = $conn->prepare($del_cart);
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        
        // Delete the book
        $del_book = "DELETE FROM books WHERE id = ?";
        $stmt = $conn->prepare($del_book);
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        
        // Modify the success response to include book title
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            $response = [
                'success' => true,
                'message' => 'Book copy deleted successfully.'
            ];
        } else {
            throw new Exception("Book not found or already deleted.");
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $response['error'] = $e->getMessage();
    }
} else {
    $response['error'] = 'Invalid book ID.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

$stmt->close();
$conn->close();
?>
