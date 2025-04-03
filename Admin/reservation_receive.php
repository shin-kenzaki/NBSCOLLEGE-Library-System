<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$admin_id = $_SESSION['admin_id'];

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
    
    // Get reservations that are in ACTIVE or READY status and include shelf_location
    $query = "SELECT r.id, r.book_id, r.user_id, b.title, u.firstname, u.lastname, b.shelf_location, r.status
              FROM reservations r
              JOIN books b ON r.book_id = b.id
              JOIN users u ON r.user_id = u.id
              WHERE r.id IN ($ids_string)
              AND r.recieved_date IS NULL 
              AND r.cancel_date IS NULL
              AND r.status IN ('Active', 'Ready')";
    
    $result = $conn->query($query);
    
    if ($result->num_rows === 0) {
        throw new Exception("No valid reservations found to process");
    }

    // Remove the READY status validation since we now accept PENDING too
    $result->data_seek(0);

    while ($row = $result->fetch_assoc()) {
        // Check if book is available when reservation is in PENDING status
        if ($row['status'] === 'Pending') {
            $check_book = "SELECT status FROM books WHERE id = ?";
            $stmt = $conn->prepare($check_book);
            $stmt->bind_param("i", $row['book_id']);
            $stmt->execute();
            $book_result = $stmt->get_result();
            $book_status = $book_result->fetch_assoc();
            
            if ($book_status['status'] !== 'Available') {
                throw new Exception("Book '{$row['title']}' is not available for immediate borrowing");
            }
        }

        // Adjust allowed days based on shelf location
        if ($row['shelf_location'] == 'RES') {
            $allowed_days = 1;
        } elseif ($row['shelf_location'] == 'REF') {
            $allowed_days = 0;
        } else {
            $allowed_days = 7;
        }

        // Update reservation
        $sql = "UPDATE reservations 
                SET recieved_date = NOW(),
                    issue_date = NOW(),
                    issued_by = ?,
                    status = 'Received'
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $admin_id, $row['id']);
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

        // Create borrowing record using adjusted allowed_days
        $sql = "INSERT INTO borrowings 
                (user_id, book_id, status, issue_date, issued_by, due_date)
                VALUES (?, ?, 'Active', NOW(), ?, DATE_ADD(NOW(), INTERVAL ? DAY))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $row['user_id'], $row['book_id'], $admin_id, $allowed_days);
        if (!$stmt->execute()) {
            throw new Exception("Error creating borrowing record");
        }

        // Removed user statistics update since borrowed_books column no longer exists
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
