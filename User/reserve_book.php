<?php
session_start();
include '../db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    echo json_encode(array('success' => false, 'message' => 'You need to be logged in to reserve books.'));
    exit;
}

// Get user ID - check both possible session variables
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];

// Get book_id parameter
$bookId = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;

// Validate if book_id is provided
if ($bookId <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid book ID provided.'));
    exit;
}

// Check for overdue books
$overdueCheckQuery = "SELECT COUNT(*) AS overdue_count 
                     FROM borrowings 
                     WHERE user_id = ? 
                     AND status = 'Borrowed' 
                     AND due_date < CURRENT_DATE()";
$stmt = $conn->prepare($overdueCheckQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$overdueResult = $stmt->get_result();
$overdueCount = $overdueResult->fetch_assoc()['overdue_count'];

if ($overdueCount > 0) {
    echo json_encode(array(
        'success' => false, 
        'message' => "You have $overdueCount overdue book(s). Please return them before making new reservations."
    ));
    exit;
}

// Check if the book exists and is available
$bookQuery = "SELECT id, title, status FROM books WHERE id = ?";
$stmt = $conn->prepare($bookQuery);
$stmt->bind_param("i", $bookId);
$stmt->execute();
$bookResult = $stmt->get_result();

if ($bookResult->num_rows == 0) {
    echo json_encode(array('success' => false, 'message' => 'Sorry, this book does not exist in our database.'));
    exit;
}

$book = $bookResult->fetch_assoc();
if ($book['status'] !== 'Available') {
    echo json_encode(array('success' => false, 'message' => 'Sorry, "' . $book['title'] . '" is currently not available for reservation.'));
    exit;
}

// Check if user has reached the maximum limit of active borrowings and reservations
$activeBorrowingsQuery = "SELECT COUNT(*) as count FROM borrowings 
                         WHERE user_id = ? AND status = 'Active'";
$stmt = $conn->prepare($activeBorrowingsQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$activeBorrowingsResult = $stmt->get_result();
$activeBorrowings = $activeBorrowingsResult->fetch_assoc()['count'];

// Get active reservations count
$activeReservationsQuery = "SELECT COUNT(*) as count FROM reservations 
                          WHERE user_id = ? AND status IN ('Pending', 'Reserved', 'Ready')";
$stmt = $conn->prepare($activeReservationsQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$activeReservationsResult = $stmt->get_result();
$activeReservations = $activeReservationsResult->fetch_assoc()['count'];

// Check if adding this item would exceed the limit
if ($activeBorrowings + $activeReservations + 1 > 3) {
    $currentTotal = $activeBorrowings + $activeReservations;
    $remainingSlots = 3 - $currentTotal;
    
    if ($remainingSlots <= 0) {
        echo json_encode(array(
            'success' => false, 
            'message' => "You can have a maximum of 3 active items (borrowed or reserved). You currently have $currentTotal active items."
        ));
    } else {
        echo json_encode(array(
            'success' => false, 
            'message' => "You can have a maximum of 3 active items (borrowed or reserved). You currently have $currentTotal active items, so you can only checkout $remainingSlots more items."
        ));
    }
    exit;
}

// Check if book is already reserved by the user
$checkReservationQuery = "SELECT id FROM reservations WHERE user_id = ? AND book_id = ? AND status IN ('Pending', 'Reserved', 'Ready')";
$stmt = $conn->prepare($checkReservationQuery);
$stmt->bind_param("ii", $userId, $bookId);
$stmt->execute();
$reservationResult = $stmt->get_result();

if ($reservationResult->num_rows > 0) {
    echo json_encode(array('success' => false, 'message' => 'You have already reserved this book.'));
    exit;
}

// Check if book is already borrowed by the user
$checkBorrowingQuery = "SELECT id FROM borrowings WHERE user_id = ? AND book_id = ? AND status = 'Borrowed'";
$stmt = $conn->prepare($checkBorrowingQuery);
$stmt->bind_param("ii", $userId, $bookId);
$stmt->execute();
$borrowingResult = $stmt->get_result();

if ($borrowingResult->num_rows > 0) {
    echo json_encode(array('success' => false, 'message' => 'You already have this book borrowed.'));
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    $currentTime = date('Y-m-d H:i:s');
    
    // Create reservation
    $insertReservationQuery = "INSERT INTO reservations 
                             (user_id, book_id, reserve_date, status) 
                             VALUES (?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($insertReservationQuery);
    $stmt->bind_param('iis', $userId, $bookId, $currentTime);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(array(
        'success' => true, 
        'message' => 'Book "' . $book['title'] . '" has been reserved successfully. You will be notified when it\'s ready for pickup.'
    ));
    
} catch (Exception $e) {
    // Roll back transaction on error
    $conn->rollback();
    echo json_encode(array(
        'success' => false, 
        'message' => 'Error during reservation: ' . $e->getMessage()
    ));
}
?>
