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

// Get parameters
$title = isset($_POST['title']) ? $_POST['title'] : '';
$isbn = isset($_POST['isbn']) ? $_POST['isbn'] : '';
$series = isset($_POST['series']) ? $_POST['series'] : '';
$volume = isset($_POST['volume']) ? $_POST['volume'] : '';
$part = isset($_POST['part']) ? $_POST['part'] : '';
$edition = isset($_POST['edition']) ? $_POST['edition'] : '';

// Validate if book title is provided
if (empty($title)) {
    echo json_encode(array('success' => false, 'message' => 'Invalid book information provided.'));
    exit;
}

// Format book details for messages
function formatBookDetails($title, $isbn, $series, $volume, $part, $edition) {
    $details = [$title];
    $metaDetails = [];
    
    if (!empty($edition)) $metaDetails[] = "Edition: $edition";
    if (!empty($series)) $metaDetails[] = "Series: $series";
    if (!empty($volume)) $metaDetails[] = "Volume: $volume";
    if (!empty($part)) $metaDetails[] = "Part: $part";
    if (!empty($isbn)) $metaDetails[] = "ISBN: $isbn";
    
    if (!empty($metaDetails)) {
        $details[] = implode(" | ", $metaDetails);
    }
    
    return implode("<br>", $details);
}

// Build the WHERE clause based on parameters
$whereClause = "title = ?";
$queryParams = [$title];
$types = "s";

if (!empty($isbn)) {
    $whereClause .= " AND ISBN = ?";
    $queryParams[] = $isbn;
    $types .= "s";
} else {
    $whereClause .= " AND (ISBN IS NULL OR ISBN = '')";
}

if (!empty($series)) {
    $whereClause .= " AND series = ?";
    $queryParams[] = $series;
    $types .= "s";
} else {
    $whereClause .= " AND (series IS NULL OR series = '')";
}

if (!empty($volume)) {
    $whereClause .= " AND volume = ?";
    $queryParams[] = $volume;
    $types .= "s";
} else {
    $whereClause .= " AND (volume IS NULL OR volume = '')";
}

if (!empty($part)) {
    $whereClause .= " AND part = ?";
    $queryParams[] = $part;
    $types .= "s";
} else {
    $whereClause .= " AND (part IS NULL OR part = '')";
}

if (!empty($edition)) {
    $whereClause .= " AND edition = ?";
    $queryParams[] = $edition;
    $types .= "s";
} else {
    $whereClause .= " AND (edition IS NULL OR edition = '')";
}

// Check if user already has a reservation for this book
$reservationCheckQuery = "SELECT r.id FROM reservations r 
                         JOIN books b ON r.book_id = b.id 
                         WHERE r.user_id = ? AND b.$whereClause 
                         AND r.status IN ('Pending', 'Reserved', 'Ready')";

$stmt = $conn->prepare($reservationCheckQuery);
$bindParams = array_merge([$userId], $queryParams);
$stmt->bind_param("i" . $types, ...$bindParams);
$stmt->execute();
$reservationResult = $stmt->get_result();

if ($reservationResult->num_rows > 0) {
    $formattedDetails = formatBookDetails($title, $isbn, $series, $volume, $part, $edition);
    echo json_encode(array('success' => false, 'message' => 'You already have an active reservation for:<br><strong>' . $formattedDetails . '</strong>'));
    exit;
}

// Check if user already has borrowed this book
$borrowingCheckQuery = "SELECT br.id FROM borrowings br 
                       JOIN books b ON br.book_id = b.id 
                       WHERE br.user_id = ? AND b.$whereClause 
                       AND br.status = 'Active'";

$stmt = $conn->prepare($borrowingCheckQuery);
$bindParams = array_merge([$userId], $queryParams);
$stmt->bind_param("i" . $types, ...$bindParams);
$stmt->execute();
$borrowingResult = $stmt->get_result();

if ($borrowingResult->num_rows > 0) {
    echo json_encode(array('success' => false, 'message' => 'You already have an active borrowing for: ' . $title));
    exit;
}

// Check current active borrowings count
$activeBorrowingsQuery = "SELECT COUNT(*) as count FROM borrowings 
                         WHERE user_id = ? AND status = 'Active'";
$stmt = $conn->prepare($activeBorrowingsQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$activeBorrowingsResult = $stmt->get_result();
$activeBorrowings = $activeBorrowingsResult->fetch_assoc()['count'];

// Limit to 3 active borrowings only (not combined with reservations)
if ($activeBorrowings >= 3) {
    echo json_encode(array('success' => false, 'message' => 'You have reached the maximum limit of 3 active borrowings.'));
    exit;
}

// Find available copy of the book
$availableBookQuery = "SELECT id FROM books 
                      WHERE $whereClause AND status = 'Available' 
                      ORDER BY id ASC LIMIT 1";

$stmt = $conn->prepare($availableBookQuery);
$stmt->bind_param($types, ...$queryParams);
$stmt->execute();
$availableBookResult = $stmt->get_result();

if ($availableBookResult->num_rows == 0) {
    $formattedDetails = formatBookDetails($title, $isbn, $series, $volume, $part, $edition);
    echo json_encode(array('success' => false, 'message' => 'Sorry, this book is not available at the moment:<br><strong>' . $formattedDetails . '</strong>'));
    exit;
}

$bookId = $availableBookResult->fetch_assoc()['id'];
$currentTime = date('Y-m-d H:i:s');

// Start transaction for consistency
$conn->begin_transaction();

try {
    // Create reservation with status "Pending" instead of "Reserved"
    $createReservationQuery = "INSERT INTO reservations 
                             (user_id, book_id, reserve_date, status) 
                             VALUES (?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($createReservationQuery);
    $stmt->bind_param("iis", $userId, $bookId, $currentTime);
    $stmt->execute();
    
    // Remove book status update - we no longer update the book's status when borrowing
    /* 
    // This code has been removed to maintain book status
    $updateBookQuery = "UPDATE books SET status = 'Reserved' WHERE id = ?";
    $stmt = $conn->prepare($updateBookQuery);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    */
    
    // Commit the transaction
    $conn->commit();
    
    $formattedDetails = formatBookDetails($title, $isbn, $series, $volume, $part, $edition);
    echo json_encode(array('success' => true, 'message' => 'Book reserved successfully:<br><strong>' . $formattedDetails . '</strong>'));
} catch (Exception $e) {
    // Rollback in case of error
    $conn->rollback();
    echo json_encode(array('success' => false, 'message' => 'Error creating reservation. Please try again.'));
}
?>
