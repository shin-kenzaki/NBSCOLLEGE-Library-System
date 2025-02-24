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
    
    // First, get the reservations and related book information
    $query = "SELECT r.id, r.book_id, r.user_id, b.title, b.status as book_status,
              CONCAT(u.firstname, ' ', u.lastname) as borrower_name
              FROM reservations r
              JOIN books b ON r.book_id = b.id
              JOIN users u ON r.user_id = u.id
              WHERE r.id IN ($ids_string)
              AND r.status = 'PENDING'
              AND r.recieved_date IS NULL 
              AND r.cancel_date IS NULL";
    
    $result = $conn->query($query);
    $updates = [];
    $errors = [];

    while ($reservation = $result->fetch_assoc()) {
        $book_id_to_update = $reservation['book_id'];
        
        // If current book is not available, look for another copy
        if ($reservation['book_status'] !== 'Available') {
            $sql = "SELECT id FROM books WHERE title = ? AND status = 'Available' AND id != ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $reservation['title'], $reservation['book_id']);
            $stmt->execute();
            $alt_result = $stmt->get_result();

            if ($alt_result->num_rows > 0) {
                $new_book = $alt_result->fetch_assoc();
                $book_id_to_update = $new_book['id'];
                
                // Update reservation with new book_id
                $updates[] = [
                    'reservation_id' => $reservation['id'],
                    'book_id' => $book_id_to_update,
                    'needs_book_update' => true
                ];
            } else {
                $errors[] = sprintf(
                    "Cannot mark %s's reservation as ready: No available copies of '%s'",
                    htmlspecialchars($reservation['borrower_name']),
                    htmlspecialchars($reservation['title'])
                );
                continue;
            }
        } else {
            $updates[] = [
                'reservation_id' => $reservation['id'],
                'book_id' => $book_id_to_update,
                'needs_book_update' => false
            ];
        }
    }

    // If there are any errors, don't proceed with updates
    if (!empty($errors)) {
        throw new Exception(implode("\n", $errors));
    }

    // Perform the updates
    foreach ($updates as $update) {
        // Update reservation status
        $sql = "UPDATE reservations SET status = 'Ready'";
        if ($update['needs_book_update']) {
            $sql .= ", book_id = " . $update['book_id'];
        }
        $sql .= " WHERE id = " . $update['reservation_id'];
        
        if (!$conn->query($sql)) {
            throw new Exception("Error updating reservation status");
        }

        // Update book status
        $sql = "UPDATE books SET status = 'Reserved' WHERE id = " . $update['book_id'];
        if (!$conn->query($sql)) {
            throw new Exception("Error updating book status");
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
