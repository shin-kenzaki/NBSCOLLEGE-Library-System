<?php
session_start();
include '../db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    echo json_encode(array('success' => false, 'message' => 'You need to be logged in to add books to cart.'));
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

// Check if this book is already in the user's cart
$cartCheckQuery = "SELECT c.id FROM cart c 
                   JOIN books b ON c.book_id = b.id 
                   WHERE c.user_id = ? AND b.$whereClause AND c.status = 1";

$stmt = $conn->prepare($cartCheckQuery);
$bindParams = array_merge([$userId], $queryParams);
$stmt->bind_param("i" . $types, ...$bindParams);
$stmt->execute();
$cartResult = $stmt->get_result();

if ($cartResult->num_rows > 0) {
    $formattedDetails = formatBookDetails($title, $isbn, $series, $volume, $part, $edition);
    echo json_encode(array('success' => false, 'message' => 'You already have "' . $title . '" in your cart.'));
    exit;
}

// Find any copy of the book - doesn't have to be available
$bookQuery = "SELECT id FROM books 
              WHERE $whereClause 
              ORDER BY id ASC LIMIT 1";  // Remove status = 'Available' check

$stmt = $conn->prepare($bookQuery);
$stmt->bind_param($types, ...$queryParams);
$stmt->execute();
$bookResult = $stmt->get_result();

if ($bookResult->num_rows == 0) {
    $formattedDetails = formatBookDetails($title, $isbn, $series, $volume, $part, $edition);
    echo json_encode(array('success' => false, 'message' => 'Sorry, this book does not exist in our database.'));
    exit;
}

$bookId = $bookResult->fetch_assoc()['id'];
$currentTime = date('Y-m-d H:i:s');

// Add book to cart
$addToCartQuery = "INSERT INTO cart (book_id, user_id, date, status) VALUES (?, ?, ?, 1)";
$stmt = $conn->prepare($addToCartQuery);
$stmt->bind_param("iis", $bookId, $userId, $currentTime);

if ($stmt->execute()) {
    $formattedDetails = formatBookDetails($title, $isbn, $series, $volume, $part, $edition);
    echo json_encode(array('success' => true, 'message' => 'Book added to cart successfully:<br><strong>' . $formattedDetails . '</strong>'));
} else {
    echo json_encode(array('success' => false, 'message' => 'Error adding book to cart. Please try again.'));
}
?>