<?php
session_start();
include('../db.php');

if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Check if ID parameter exists
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Book ID not provided']);
    exit();
}

$bookId = intval($_GET['id']);
$adminId = $_SESSION['admin_id'];
$currentDate = date('Y-m-d');

// Begin transaction
$conn->begin_transaction();

try {
    // Get borrowing information
    $getBorrowingQuery = "SELECT b.id as borrow_id, b.user_id, bk.title, bk.shelf_location, b.due_date
                        FROM borrowings b
                        JOIN books bk ON b.book_id = bk.id
                        WHERE b.book_id = ? AND (b.status = 'Active' OR b.status = 'Overdue')
                        AND b.return_date IS NULL
                        LIMIT 1";
    $stmt = $conn->prepare($getBorrowingQuery);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("No active borrowing found for this book");
    }

    $borrowing = $result->fetch_assoc();
    $borrowId = $borrowing['borrow_id'];
    $userId = $borrowing['user_id'];
    $bookTitle = $borrowing['title'];
    $shelfLocation = $borrowing['shelf_location'];
    $dueDate = $borrowing['due_date'];

    // Calculate fines if overdue
    $fineAmount = 0;
    if (strtotime($currentDate) > strtotime($dueDate)) {
        $daysOverdue = (strtotime($currentDate) - strtotime($dueDate)) / (60 * 60 * 24);
        if (in_array($shelfLocation, ['REF', 'RES'])) {
            $fineAmount = $daysOverdue * 30; // 30 pesos per day
        } else {
            $fineAmount = $daysOverdue * 5; // 5 pesos per day
        }

        // Insert fine into fines table
        $insertFineQuery = "INSERT INTO fines (borrowing_id, type, amount, status, date, reminder_sent)
                            VALUES (?, 'Overdue', ?, 'Unpaid', ?, 0)";
        $stmt = $conn->prepare($insertFineQuery);
        $stmt->bind_param("ids", $borrowId, $fineAmount, $currentDate);
        $stmt->execute();
    }

    // Update borrowing record
    $updateBorrowingQuery = "UPDATE borrowings
                          SET status = 'Returned',
                              return_date = ?,
                              recieved_by = ?
                          WHERE id = ?";
    $stmt = $conn->prepare($updateBorrowingQuery);
    $stmt->bind_param("sii", $currentDate, $adminId, $borrowId);
    $stmt->execute();

    // Update book status
    $updateBookQuery = "UPDATE books SET status = 'Available' WHERE id = ?";
    $stmt = $conn->prepare($updateBookQuery);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Return success response for API calls
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Book has been returned successfully']);
    } else {
        // For direct browser access, redirect back
        header("Location: borrowed_books.php?success=Book has been returned successfully");
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Return error response for API calls
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } else {
        // For direct browser access, redirect back with error
        header("Location: borrowed_books.php?error=" . urlencode($e->getMessage()));
    }
}

$conn->close();
?>
