<?php
session_start();
include '../db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    echo json_encode(array('success' => false, 'message' => 'You need to be logged in to add books to cart.'));
    exit;
}

// Get user ID - check both possible session variables
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];

// Get book_id parameter
$bookId = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;

// Validate if book_id is provided
if ($bookId <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid book ID provided.'));
    exit;
}

// Check if this book is already in the user's cart
$cartCheckQuery = "SELECT id FROM cart WHERE user_id = ? AND book_id = ? AND status = 1";
$stmt = $conn->prepare($cartCheckQuery);
$stmt->bind_param("ii", $userId, $bookId);
$stmt->execute();
$cartResult = $stmt->get_result();

if ($cartResult->num_rows > 0) {
    // Get book title for the error message
    $bookTitleQuery = "SELECT title FROM books WHERE id = ?";
    $titleStmt = $conn->prepare($bookTitleQuery);
    $titleStmt->bind_param("i", $bookId);
    $titleStmt->execute();
    $titleResult = $titleStmt->get_result();
    $bookTitle = ($titleResult->num_rows > 0) ? $titleResult->fetch_assoc()['title'] : 'This book';
    
    echo json_encode(array('success' => false, 'message' => 'You already have "' . $bookTitle . '" in your cart.'));
    exit;
}

// Check if the book exists and is available
$bookQuery = "SELECT id, title, status FROM books WHERE id = ?";
$stmt = $conn->prepare($bookQuery);
$stmt->bind_param("i", $bookId);
$stmt->execute();
$bookResult = $stmt->get_result();

if ($bookResult->num_rows == 0) {
    echo json_encode(array('success' => false, 'message' => 'Sorry, this book does not exist in our database.'));
    exit;
}

$book = $bookResult->fetch_assoc();
if ($book['status'] !== 'Available') {
    echo json_encode(array('success' => false, 'message' => 'Sorry, "' . $book['title'] . '" is currently not available for borrowing.'));
    exit;
}

$currentTime = date('Y-m-d H:i:s');

// Add book to cart
$addToCartQuery = "INSERT INTO cart (book_id, user_id, date, status) VALUES (?, ?, ?, 1)";
$stmt = $conn->prepare($addToCartQuery);
$stmt->bind_param("iis", $bookId, $userId, $currentTime);

if ($stmt->execute()) {
    echo json_encode(array('success' => true, 'message' => 'Book added to cart successfully.'));
} else {
    echo json_encode(array('success' => false, 'message' => 'Error adding book to cart. Please try again.'));
}
?>