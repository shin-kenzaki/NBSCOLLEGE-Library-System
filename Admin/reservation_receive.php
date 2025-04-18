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
$direct_issue = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $ids[] = intval($_GET['id']);
    $direct_issue = isset($_GET['direct']) && $_GET['direct'] === '1';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (isset($data['ids']) && !empty($data['ids'])) {
        $ids = array_map('intval', $data['ids']);
        $direct_issue = isset($data['direct']) && $data['direct'] === true;
    }
}

if (empty($ids)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header("Location: book_reservations.php?error=No reservations selected");
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'No reservations selected']);
        exit();
    }
}

// Start transaction
$conn->begin_transaction();

try {
    $ids_string = implode(',', $ids);
    
    // Get reservations that are in PENDING, ACTIVE or READY status and include shelf_location
    // Modified to include 'Pending' status
    $query = "SELECT r.id, r.book_id, r.user_id, b.title, u.firstname, u.lastname, b.shelf_location, r.status
              FROM reservations r
              JOIN books b ON r.book_id = b.id
              JOIN users u ON r.user_id = u.id
              WHERE r.id IN ($ids_string)
              AND r.recieved_date IS NULL 
              AND r.cancel_date IS NULL
              AND r.status IN ('Pending', 'Active', 'Ready')";
    
    $result = $conn->query($query);
    
    if ($result->num_rows === 0) {
        throw new Exception("No valid reservations found to process");
    }

    $success_count = 0;
    $errors = [];

    while ($row = $result->fetch_assoc()) {
        // For pending reservations, check if direct issue is allowed
        if ($row['status'] === 'Pending') {
            if (!$direct_issue) {
                $errors[] = "Reservation for '{$row['title']}' is pending and not ready for pickup. Mark as ready first.";
                continue;
            }
            
            // Check if book is available for direct issue
            $check_book = "SELECT status FROM books WHERE id = ?";
            $stmt = $conn->prepare($check_book);
            $stmt->bind_param("i", $row['book_id']);
            $stmt->execute();
            $book_result = $stmt->get_result();
            $book_status = $book_result->fetch_assoc();
            
            if ($book_status['status'] !== 'Available') {
                $errors[] = "Book '{$row['title']}' is not available for immediate borrowing";
                continue;
            }
            
            // For direct issue, first mark as ready
            $ready_sql = "UPDATE reservations 
                         SET status = 'Ready',
                             ready_date = NOW(),
                             ready_by = ?
                         WHERE id = ?";
            $stmt = $conn->prepare($ready_sql);
            $stmt->bind_param("ii", $admin_id, $row['id']);
            if (!$stmt->execute()) {
                $errors[] = "Error updating reservation status for '{$row['title']}'";
                continue;
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
            $errors[] = "Error updating reservation for '{$row['title']}'";
            continue;
        }

        // Update book status
        $sql = "UPDATE books 
                SET status = 'Borrowed'
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $row['book_id']);
        if (!$stmt->execute()) {
            $errors[] = "Error updating book status for '{$row['title']}'";
            continue;
        }

        // Create borrowing record using adjusted allowed_days
        $sql = "INSERT INTO borrowings 
                (user_id, book_id, status, issue_date, issued_by, due_date)
                VALUES (?, ?, 'Active', NOW(), ?, DATE_ADD(NOW(), INTERVAL ? DAY))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $row['user_id'], $row['book_id'], $admin_id, $allowed_days);
        if (!$stmt->execute()) {
            $errors[] = "Error creating borrowing record for '{$row['title']}'";
            continue;
        }

        $success_count++;
    }

    if (!empty($errors) && $success_count === 0) {
        // If nothing succeeded, roll back and return error
        $conn->rollback();
        $error_message = implode("\n", $errors);
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            header("Location: book_reservations.php?error=" . urlencode($error_message));
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        }
    } else {
        // Commit successful transactions
        $conn->commit();
        
        $message = "$success_count book(s) issued successfully";
        if (!empty($errors)) {
            $message .= ". However, there were some errors: " . implode("; ", $errors);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            header("Location: book_reservations.php?success=" . urlencode($message));
            exit();
        } else {
            echo json_encode(['success' => true, 'message' => $message, 'errors' => $errors]);
            exit();
        }
    }
} catch (Exception $e) {
    $conn->rollback();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header("Location: book_reservations.php?error=" . urlencode($e->getMessage()));
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

$conn->close();
