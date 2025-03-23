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

// Get book title
$title = isset($data['title']) ? $data['title'] : '';

// Default response
$response = ['success' => false];

if (!empty($title)) {
    try {
        $conn->begin_transaction();
        
        // Check if any copies are borrowed or reserved
        $check_query = "SELECT b.id 
                       FROM books bk 
                       LEFT JOIN borrowings b ON b.book_id = bk.id 
                       WHERE bk.title = ? 
                       AND b.status IN ('Borrowed', 'Overdue')";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $title);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Cannot delete: Some copies are currently borrowed.");
        }
        
        // Check reservations
        $check_reservations = "SELECT r.id 
                             FROM books bk 
                             LEFT JOIN reservations r ON r.book_id = bk.id 
                             WHERE bk.title = ? 
                             AND r.status = 'Pending'";
        $stmt = $conn->prepare($check_reservations);
        $stmt->bind_param("s", $title);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Cannot delete: Some copies have pending reservations.");
        }
        
        // Get all book IDs for this title
        $get_ids = "SELECT id FROM books WHERE title = ?";
        $stmt = $conn->prepare($get_ids);
        $stmt->bind_param("s", $title);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $book_ids = [];
        while ($row = $result->fetch_assoc()) {
            $book_ids[] = $row['id'];
        }
        
        if (empty($book_ids)) {
            throw new Exception("No books found with this title.");
        }
        
        // Delete from contributors
        $del_contributors = "DELETE FROM contributors WHERE book_id IN (" . implode(',', $book_ids) . ")";
        $conn->query($del_contributors);
        
        // Delete from publications
        $del_publications = "DELETE FROM publications WHERE book_id IN (" . implode(',', $book_ids) . ")";
        $conn->query($del_publications);
        
        // Delete from cart
        $del_cart = "DELETE FROM cart WHERE book_id IN (" . implode(',', $book_ids) . ")";
        $conn->query($del_cart);
        
        // Delete all copies
        $del_books = "DELETE FROM books WHERE title = ?";
        $stmt = $conn->prepare($del_books);
        $stmt->bind_param("s", $title);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            $response = [
                'success' => true,
                'message' => 'All copies deleted successfully.'
            ];
        } else {
            throw new Exception("No books were deleted.");
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $response = ['success' => false, 'error' => $e->getMessage()];
    }
} else {
    $response = ['success' => false, 'error' => 'Invalid book title.'];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

$stmt->close();
$conn->close();