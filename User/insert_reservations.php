<?php
session_start();
include '../db.php';

$user_id = $_SESSION['user_id'];
$titles = $_POST['titles'];
$date = date('Y-m-d H:i:s');

foreach ($titles as $title) {
    // Get book ID by title
    $query = "SELECT id FROM books WHERE title = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $title);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $book_id = $book['id'];

    // Insert into reservations table with status
    $query = "INSERT INTO reservations (user_id, book_id, reserve_date, cancel_date, recieved_date, status) VALUES (?, ?, ?, NULL, NULL, 1)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iis', $user_id, $book_id, $date);
    $stmt->execute();
}

echo json_encode(['success' => true, 'message' => 'Reservations inserted successfully.']);
?>
