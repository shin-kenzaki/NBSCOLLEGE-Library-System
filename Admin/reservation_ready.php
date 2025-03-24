<?php
session_start();
include('../db.php');
require 'mailer.php'; // Include the mailer library

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
    
    // First, get the reservations and related book information
    $query = "SELECT r.id, r.book_id, r.user_id, b.title, b.status as book_status,
              CONCAT(u.firstname, ' ', u.lastname) as borrower_name, u.email as borrower_email
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
                    'book_title' => $reservation['title']
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
                'book_title' => $reservation['title']
            ];
        }

        // Group reservations by user
        $userReservations[$reservation['user_id']]['borrower_name'] = $reservation['borrower_name'];
        $userReservations[$reservation['user_id']]['borrower_email'] = $reservation['borrower_email'];
        $userReservations[$reservation['user_id']]['books'][] = $reservation['title'];
    }

    // If there are any errors, don't proceed with updates
    if (!empty($errors)) {
        throw new Exception(implode("\n", $errors));
    }

    // Perform the updates
    foreach ($updates as $update) {
        // Update reservation status and add ready_date and ready_by
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

    // Send email notifications
    foreach ($userReservations as $userId => $userData) {
        $borrowerName = $userData['borrower_name'];
        $borrowerEmail = $userData['borrower_email'];
        $books = $userData['books'];

        $mail = require 'mailer.php';

        try {
            $mail->setFrom('noreply@nbs-library-system.com', 'Library System (No-Reply)');
            $mail->addReplyTo('noreply@nbs-library-system.com', 'No-Reply');
            $mail->addAddress($borrowerEmail, $borrowerName);

            if (count($books) == 1) {
                $bookTitle = $books[0];
                $mail->Subject = "Your Reserved Book is Ready for Pickup";
                $mail->Body = "
                    Hi $borrowerName,<br><br>
                    Your reserved book titled <b>$bookTitle</b> is now ready for pickup.<br>
                    Please visit the library to collect your book.<br><br>
                    <i><b>Note:</b> This is an automated email — please do not reply.</i><br><br>
                    Thank you!
                ";
            } else {
                $bookList = "<ul>";
                foreach ($books as $bookTitle) {
                    $bookList .= "<li>" . htmlspecialchars($bookTitle) . "</li>";
                }
                $bookList .= "</ul>";

                $mail->Subject = "Your Reserved Books are Ready for Pickup";
                $mail->Body = "
                    Hi $borrowerName,<br><br>
                    Your reserved books are now ready for pickup:<br>
                    $bookList
                    Please visit the library to collect your books.<br><br>
                    <i><b>Note:</b> This is an automated email — please do not reply.</i><br><br>
                    Thank you!
                ";
            }

            if (!$mail->send()) {
                throw new Exception("Failed to send email to {$borrowerEmail}");
            }
        } catch (Exception $e) {
            throw new Exception("Email sending failed for {$borrowerEmail}. Error: {$mail->ErrorInfo}");
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