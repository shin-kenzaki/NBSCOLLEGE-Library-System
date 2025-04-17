<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accession'])) {
    $accession = trim($_POST['accession']);
    
    // Enhanced query to fetch all required book details
    $query = "SELECT b.id, b.title, b.accession, b.shelf_location, 
                     b.series, b.volume, b.part, b.edition, b.copy_number, 
                     b.content_type, b.media_type, b.call_number, 
                     GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS authors,
                     GROUP_CONCAT(DISTINCT p.publisher SEPARATOR ', ') AS publishers,
                     MAX(pub.publish_date) AS publish_year
              FROM books b
              LEFT JOIN contributors c ON b.id = c.book_id
              LEFT JOIN writers w ON c.writer_id = w.id
              LEFT JOIN publications pub ON b.id = pub.book_id
              LEFT JOIN publishers p ON pub.publisher_id = p.id
              WHERE b.accession = ? AND b.status = 'Available'
              GROUP BY b.id, b.title, b.accession";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $accession);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'book' => [
                'id' => $book['id'],
                'title' => $book['title'],
                'accession' => $book['accession'],
                'shelf_location' => $book['shelf_location'],
                'series' => $book['series'],
                'volume' => $book['volume'],
                'part' => $book['part'],
                'edition' => $book['edition'],
                'copy_number' => $book['copy_number'],
                'call_number' => $book['call_number'],
                'content_type' => $book['content_type'],
                'media_type' => $book['media_type'],
                'authors' => $book['authors'],
                'publishers' => $book['publishers'],
                'publish_year' => $book['publish_year']
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Book not found or not available']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
