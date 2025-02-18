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

        // Check if the book is already in the cart for the user
        $query = "SELECT * FROM cart WHERE book_id = ? AND user_id = ? AND status = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $book_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(['message' => 'You already have "' . htmlspecialchars($title) . '" in your cart.']);
        } else {
            // Insert into cart
            $query = "INSERT INTO cart (book_id, user_id, date, status) VALUES (?, ?, NOW(), 1)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ii', $book_id, $user_id);
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Added "' . htmlspecialchars($title) . '" to cart.']);
            } else {
                echo json_encode(['message' => 'Failed to add "' . htmlspecialchars($title) . '" to cart.']);
            }
        }
    } else {
        echo json_encode(['message' => 'Book not found.']);
    }
}
?>
