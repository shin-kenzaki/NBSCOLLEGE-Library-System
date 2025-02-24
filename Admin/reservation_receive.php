<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get input data - either from GET for single or POST for bulk
$ids = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $ids[] = intval($_GET['id']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (isset($data['ids']) && !empty($data['ids'])) {
        $ids = array_map('intval', $data['ids']);
    }
}

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No reservations selected']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    $ids_string = implode(',', $ids);
    
    // Get reservations that are in READY status
    $query = "SELECT r.id, r.book_id, r.user_id, b.title, u.firstname, u.lastname
              FROM reservations r
              JOIN books b ON r.book_id = b.id
              JOIN users u ON r.user_id = u.id
              WHERE r.id IN ($ids_string)
              AND r.status = 'READY'
              AND r.recieved_date IS NULL 
              AND r.cancel_date IS NULL";
    
    $result = $conn->query($query);
    
    if ($result->num_rows === 0) {
        throw new Exception("No valid reservations found to process");
    }

    $allowed_days = 7;

    while ($row = $result->fetch_assoc()) {
        // Update reservation
        $sql = "UPDATE reservations 
                SET recieved_date = NOW(),
                    status = 'Recieved'
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $row['id']);
        if (!$stmt->execute()) {
            throw new Exception("Error updating reservation");
        }

        // Update book status
        $sql = "UPDATE books 
                SET status = 'Borrowed'
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $row['book_id']);
        if (!$stmt->execute()) {
            throw new Exception("Error updating book status");
        }

        // Create borrowing record
        $sql = "INSERT INTO borrowings 
                (user_id, book_id, status, borrow_date, allowed_days, due_date)
                VALUES (?, ?, 'Active', NOW(), ?, DATE_ADD(NOW(), INTERVAL ? DAY))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $row['user_id'], $row['book_id'], $allowed_days, $allowed_days);
        if (!$stmt->execute()) {
            throw new Exception("Error creating borrowing record");
        }

        // Update user statistics
        $sql = "UPDATE users 
                SET borrowed_books = borrowed_books + 1
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $row['user_id']);
        if (!$stmt->execute()) {
            throw new Exception("Error updating user statistics");
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
