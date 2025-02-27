<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$titles = $_POST['titles'];
$date = date('Y-m-d H:i:s');

try {
    $conn->begin_transaction();

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
        $book = $result->fetch_assoc();
        
        if (!$book) {
            throw new Exception("Book not found: " . $title);
        }
        $book_id = $book['id'];

        // Insert into reservations table with all new fields
        $query = "INSERT INTO reservations (
            user_id,
            book_id,
            reserve_date,
            ready_date,
            ready_by,
            issue_date,
            issued_by,
            cancel_date,
            cancelled_by,
            recieved_date,
            status
        ) VALUES (
            ?, ?, ?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending'
        )";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param('iis', $user_id, $book_id, $date);
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Reservations inserted successfully.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
