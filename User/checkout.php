<?php
session_start();
include '../db.php';

$user_id = $_SESSION['user_id'];
$titles = $_POST['titles'];
$date = date('Y-m-d H:i:s');

$response = ['success' => false, 'message' => 'Failed to checkout selected items.'];

try {
    foreach ($titles as $title) {
        // Get book ID by title
        $query = "SELECT id FROM books WHERE title = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param('s', $title);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Get result failed: " . $stmt->error);
        }
        $book = $result->fetch_assoc();
        if (!$book) {
            throw new Exception("Book not found: " . $title);
        }
        $book_id = $book['id'];

        // Check if the book is already reserved by the user
        $query = "SELECT COUNT(*) as count FROM reservations WHERE user_id = ? AND book_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param('ii', $user_id, $book_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Get result failed: " . $stmt->error);
        }
        $reservation = $result->fetch_assoc();
        if ($reservation['count'] > 0) {
            throw new Exception("Book already reserved: " . $title);
        }

        // Insert into reservations table
        $query = "INSERT INTO reservations (user_id, book_id, reserve_date, cancel_date, recieved_date, status) VALUES (?, ?, ?, NULL, NULL, 1)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param('iis', $user_id, $book_id, $date);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }

        // Update cart status to inactive
        $query = "UPDATE cart SET status = 0 WHERE user_id = ? AND book_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param('ii', $user_id, $book_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
    }
    $response['success'] = true;
    $response['message'] = 'Books checked out successfully.';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
