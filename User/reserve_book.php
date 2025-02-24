<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$title = $_POST['title'];
$date = date('Y-m-d H:i:s');

$response = ['success' => false, 'message' => 'Failed to reserve the book.'];

try {
    // Check if the user already has this book borrowed with status 'Active' or reserved with status 'Pending'
    $query = "SELECT COUNT(*) as count 
              FROM borrowings br
              JOIN books b ON br.book_id = b.id 
              WHERE br.user_id = ? 
              AND b.title = ? 
              AND br.status = 'Active'
              UNION ALL
              SELECT COUNT(*) as count
              FROM reservations r
              JOIN books b ON r.book_id = b.id
              WHERE r.user_id = ?
              AND b.title = ?
              AND r.status = 'Pending'";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param('isis', $user_id, $title, $user_id, $title);
    if (!$stmt->execute()) {
        throw new Exception("Execute statement failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $borrowing = $result->fetch_assoc();
    if ($borrowing['count'] > 0) {
        throw new Exception("You already have this book borrowed or reserved: " . $title);
    }

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

    // Check if the user already has a reservation for this book
    $query = "SELECT COUNT(*) as count 
              FROM reservations 
              WHERE user_id = ? 
              AND book_id = ? 
              AND status = 'Pending'";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param('ii', $user_id, $book_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute statement failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    if ($reservation['count'] > 0) {
        throw new Exception("You already have a reservation for this book: " . $title);
    }

    // Insert into reservations table with status 'Pending'
    $query = "INSERT INTO reservations (user_id, book_id, reserve_date, cancel_date, recieved_date, status) VALUES (?, ?, ?, NULL, NULL, 'Pending')";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param('iis', $user_id, $book_id, $date);
    if (!$stmt->execute()) {
        throw new Exception("Execute statement failed: " . $stmt->error);
    }

    // Update user status
    $currentDate = date('Y-m-d');
    $updateUserQuery = "UPDATE users SET status = '1', last_update = ? WHERE id = ?";
    $stmt = $conn->prepare($updateUserQuery);
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param('si', $currentDate, $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute statement failed: " . $stmt->error);
    }

    $response['success'] = true;
    $response['message'] = 'Book reserved successfully.';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
