<?php
session_start();
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $user_id = $_SESSION['user_id'];

    // Get the book ID based on the title
    $query = "SELECT id FROM books WHERE title = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $title);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();

    if ($book) {
        $book_id = $book['id'];

        // Remove the book from the cart
        $query = "DELETE FROM cart WHERE book_id = ? AND user_id = ? AND status = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $book_id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Removed "' . htmlspecialchars($title) . '" from cart.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove "' . htmlspecialchars($title) . '" from cart.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Book not found.']);
    }
}
?>
