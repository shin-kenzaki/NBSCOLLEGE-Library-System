<?php
session_start();
include '../db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    echo json_encode(array('success' => false, 'message' => 'You need to be logged in to checkout.'));
    exit;
}

// Get user ID - check both possible session variables
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];

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

// Check if selected cart IDs are provided
$selectedCartIds = isset($_POST['selected_cart_ids']) ? $_POST['selected_cart_ids'] : array();

// Build query based on selection
if (!empty($selectedCartIds)) {
    // Checkout only selected books by cart ID
    $placeholders = str_repeat('?,', count($selectedCartIds) - 1) . '?';
    $cartQuery = "SELECT c.id, c.book_id, b.title, b.ISBN, b.series, b.volume, b.part, b.edition 
                 FROM cart c 
                 JOIN books b ON c.book_id = b.id 
                 WHERE c.user_id = ? AND c.status = 1 AND c.id IN ($placeholders)";
    
    $types = "i" . str_repeat("i", count($selectedCartIds));
    $params = array_merge([$userId], $selectedCartIds);
    $stmt = $conn->prepare($cartQuery);
    $stmt->bind_param($types, ...$params);
} else {
    // Checkout all books in cart
    $cartQuery = "SELECT c.id, c.book_id, b.title, b.ISBN, b.series, b.volume, b.part, b.edition 
                 FROM cart c 
                 JOIN books b ON c.book_id = b.id 
                 WHERE c.user_id = ? AND c.status = 1";
    $stmt = $conn->prepare($cartQuery);
    $stmt->bind_param('i', $userId);
}

$stmt->execute();
$cartResult = $stmt->get_result();

// Check if cart is empty
if ($cartResult->num_rows === 0) {
    echo json_encode(array('success' => false, 'message' => 'Your cart is empty or the selected books are not found.'));
    exit;
}

// Check if user has reached the maximum limit of active borrowings
$activeBorrowingsQuery = "SELECT COUNT(*) as count FROM borrowings 
                         WHERE user_id = ? AND status = 'Active'";
$stmt = $conn->prepare($activeBorrowingsQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$activeBorrowingsResult = $stmt->get_result();
$activeBorrowings = $activeBorrowingsResult->fetch_assoc()['count'];

// Get active reservations count (already issued but not yet returned)
$activeReservationsQuery = "SELECT COUNT(*) as count FROM reservations 
                          WHERE user_id = ? AND status IN ('Pending', 'Reserved', 'Ready')";
$stmt = $conn->prepare($activeReservationsQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$activeReservationsResult = $stmt->get_result();
$activeReservations = $activeReservationsResult->fetch_assoc()['count'];

$cartCount = $cartResult->num_rows;

// Check if adding these items would exceed the limit
// The combined total of active borrowings + active reservations + new cart items should not exceed 3
if ($activeBorrowings + $activeReservations + $cartCount > 3) {
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

// Check if any selected books are already reserved by the user
$alreadyReservedBooks = [];

// Store book IDs from cart to check
$cartBookIds = [];
while ($cartItem = $cartResult->fetch_assoc()) {
    $cartBookIds[] = [
        'id' => $cartItem['book_id'],
        'title' => $cartItem['title'],
        'ISBN' => $cartItem['ISBN'],
        'series' => $cartItem['series'],
        'volume' => $cartItem['volume'],
        'part' => $cartItem['part'],
        'edition' => $cartItem['edition']
    ];
}

// Reset the result pointer
$cartResult->data_seek(0);

// Check for already reserved books with same identifying characteristics
foreach ($cartBookIds as $book) {
    $whereClause = "title = ?";
    $queryParams = [$book['title']];
    $types = "s";
    
    if (!empty($book['ISBN'])) {
        $whereClause .= " AND ISBN = ?";
        $queryParams[] = $book['ISBN'];
        $types .= "s";
    } else {
        $whereClause .= " AND (ISBN IS NULL OR ISBN = '')";
    }
    
    if (!empty($book['series'])) {
        $whereClause .= " AND series = ?";
        $queryParams[] = $book['series'];
        $types .= "s";
    } else {
        $whereClause .= " AND (series IS NULL OR series = '')";
    }
    
    if (!empty($book['volume'])) {
        $whereClause .= " AND volume = ?";
        $queryParams[] = $book['volume'];
        $types .= "s";
    } else {
        $whereClause .= " AND (volume IS NULL OR volume = '')";
    }
    
    if (!empty($book['part'])) {
        $whereClause .= " AND part = ?";
        $queryParams[] = $book['part'];
        $types .= "s";
    } else {
        $whereClause .= " AND (part IS NULL OR part = '')";
    }
    
    if (!empty($book['edition'])) {
        $whereClause .= " AND edition = ?";
        $queryParams[] = $book['edition'];
        $types .= "s";
    } else {
        $whereClause .= " AND (edition IS NULL OR edition = '')";
    }
    
    // Check for existing reservations
    $reservationCheckQuery = "SELECT r.id FROM reservations r 
                            JOIN books b ON r.book_id = b.id 
                            WHERE r.user_id = ? AND b.$whereClause 
                            AND r.status IN ('Pending', 'Reserved', 'Ready')
                            LIMIT 1";
    
    $stmt = $conn->prepare($reservationCheckQuery);
    $bindParams = array_merge([$userId], $queryParams);
    $stmt->bind_param("s" . $types, ...$bindParams);
    $stmt->execute();
    $reservationResult = $stmt->get_result();
    
    // Check for existing borrowings
    $borrowingCheckQuery = "SELECT br.id FROM borrowings br 
                          JOIN books b ON br.book_id = b.id 
                          WHERE br.user_id = ? AND b.$whereClause 
                          AND br.status = 'Active'
                          LIMIT 1";
                          
    $stmt = $conn->prepare($borrowingCheckQuery);
    $bindParams = array_merge([$userId], $queryParams);
    $stmt->bind_param("s" . $types, ...$bindParams);
    $stmt->execute();
    $borrowingResult = $stmt->get_result();
    
    if ($reservationResult->num_rows > 0 || $borrowingResult->num_rows > 0) {
        $alreadyReservedBooks[] = formatBookDetails(
            $book['title'],
            $book['ISBN'],
            $book['series'],
            $book['volume'],
            $book['part'],
            $book['edition']
        );
    }
}

// If there are already reserved books, return error
if (!empty($alreadyReservedBooks)) {
    $message = "You already have active reservations or borrowings for the following books:<br><br>";
    foreach ($alreadyReservedBooks as $index => $book) {
        $message .= ($index + 1) . ". " . $book . "<br><br>";
    }
    $message .= "Please remove these books from your cart before proceeding.";
    
    echo json_encode(array('success' => false, 'message' => $message));
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    $currentTime = date('Y-m-d H:i:s');
    $successCount = 0;
    $reservedBooks = array();
    
    // Process each book
    while ($cartItem = $cartResult->fetch_assoc()) {
        $bookId = $cartItem['book_id'];
        $cartId = $cartItem['id'];
        
        // Create reservation
        $insertReservationQuery = "INSERT INTO reservations 
                                (user_id, book_id, reserve_date, status) 
                                VALUES (?, ?, ?, 'Pending')";
        $stmt = $conn->prepare($insertReservationQuery);
        $stmt->bind_param('iis', $userId, $bookId, $currentTime);
        $stmt->execute();
        
        // Remove from cart
        $deleteCartItemQuery = "DELETE FROM cart WHERE id = ?";
        $stmt = $conn->prepare($deleteCartItemQuery);
        $stmt->bind_param('i', $cartId);
        $stmt->execute();
        
        // Format book details for output
        $formattedBookDetails = formatBookDetails(
            $cartItem['title'],
            $cartItem['ISBN'],
            $cartItem['series'],
            $cartItem['volume'],
            $cartItem['part'],
            $cartItem['edition']
        );
        $reservedBooks[] = $formattedBookDetails;
        $successCount++;
    }
    
    // Commit transaction
    $conn->commit();
    
    // Build success message with detailed book info
    if ($successCount > 0) {
        $message = "Successfully reserved $successCount book(s):<br><br>";
        foreach ($reservedBooks as $index => $book) {
            $message .= ($index + 1) . ". <strong>" . $book . "</strong><br><br>";
        }
        $message .= "Please visit the library counter to collect your items.";
        
        echo json_encode(array('success' => true, 'message' => $message));
    } else {
        echo json_encode(array('success' => false, 'message' => 'No books were checked out. Please try again.'));
    }
    
} catch (Exception $e) {
    // Roll back transaction on error
    $conn->rollback();
    echo json_encode(array('success' => false, 'message' => 'Error during checkout: ' . $e->getMessage()));
}
?>
