<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Get input data - either from GET for single or POST for bulk
$ids = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $ids[] = intval($_GET['id']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle JSON formatted request
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (isset($data['ids']) && !empty($data['ids'])) {
            $ids = array_map('intval', $data['ids']);
        }
    } else {
        // Handle standard form post
        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = array_map('intval', $_POST['ids']);
        }
    }
}

if (empty($ids)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header("Location: book_reservations.php?error=No reservations selected");
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No reservations selected']);
        exit();
    }
}

// Start transaction
$conn->begin_transaction();

try {
    $ids_string = implode(',', $ids);
    
    // First, get the reservations and related book information
    $query = "SELECT r.id, r.book_id, r.user_id, b.title, b.status as book_status,
              CONCAT(u.firstname, ' ', u.lastname) as borrower_name, u.email as borrower_email,
              r.status as reservation_status
              FROM reservations r
              JOIN books b ON r.book_id = b.id
              JOIN users u ON r.user_id = u.id
              WHERE r.id IN ($ids_string)
              AND r.status = 'Pending' 
              AND r.recieved_date IS NULL
              AND r.cancel_date IS NULL";

    $result = $conn->query($query);
    $updates = [];
    $errors = [];
    $userReservations = [];

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
                    'needs_book_update' => true,
                    'borrower_name' => $reservation['borrower_name'],
                    'borrower_email' => $reservation['borrower_email'],
                    'book_title' => $reservation['title'],
                    'user_id' => $reservation['user_id']
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
                'needs_book_update' => false,
                'borrower_name' => $reservation['borrower_name'],
                'borrower_email' => $reservation['borrower_email'],
                'book_title' => $reservation['title'],
                'user_id' => $reservation['user_id']
            ];
        }

        // Group reservations by user
        $userReservations[$reservation['user_id']]['borrower_name'] = $reservation['borrower_name'];
        $userReservations[$reservation['user_id']]['borrower_email'] = $reservation['borrower_email'];
        $userReservations[$reservation['user_id']]['books'][] = $reservation['title'];
    }

    // If there are any errors, don't proceed with updates
    if (!empty($errors)) {
        $conn->rollback();
        $errorMessage = implode("\n", $errors);
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            header("Location: book_reservations.php?error=" . urlencode($errorMessage));
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit();
        }
    }

    // Perform the updates
    foreach ($updates as $update) {
        // Update reservation status from "Pending" to "Ready" (skipping "Reserved" status)
        $sql = "UPDATE reservations SET
                status = 'Ready',
                ready_date = NOW(),
                ready_by = ?";
        if ($update['needs_book_update']) {
            $sql .= ", book_id = " . $update['book_id'];
        }
        $sql .= " WHERE id = " . $update['reservation_id'];

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $admin_id);
        if (!$stmt->execute()) {
            throw new Exception("Error updating reservation status");
        }

        // Update book status
        $sql = "UPDATE books SET status = 'Reserved' WHERE id = " . $update['book_id'];
        if (!$conn->query($sql)) {
            throw new Exception("Error updating book status");
        }
    }

    // Send email notifications if mailer.php is available
    if (file_exists('mailer.php')) {
        require_once 'mailer.php';
        
        foreach ($userReservations as $userId => $userData) {
            $borrowerName = $userData['borrower_name'];
            $borrowerEmail = $userData['borrower_email'];
            $books = $userData['books'];

            try {
                // Email functionality here
                // (Assuming the mailer setup from existing code)
            } catch (Exception $e) {
                // Log email errors but don't fail the transaction
                error_log("Email sending failed for {$borrowerEmail}. Error: {$e->getMessage()}");
            }
        }
    }

    $conn->commit();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header("Location: book_reservations.php?success=Reservation(s) marked as ready for pickup");
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Reservation(s) marked as ready for pickup']);
        exit();
    }
} catch (Exception $e) {
    $conn->rollback();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header("Location: book_reservations.php?error=" . urlencode($e->getMessage()));
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

$conn->close();
?>