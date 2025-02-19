<?php
session_start();

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian')) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    $reservation_id = $_GET['id'];
    
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "librarysystem";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Debug log
        error_log("Processing reservation ID: " . $reservation_id);

        // First check if reservation can be marked as ready
        $sql = "SELECT r.status, r.book_id, r.user_id, b.title, b.status as book_status, 
                CONCAT(u.firstname, ' ', u.lastname) as borrower_name
                FROM reservations r 
                JOIN books b ON r.book_id = b.id 
                JOIN users u ON r.user_id = u.id
                WHERE r.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();

        // Check if reservation status is valid for marking as ready
        if (strtolower($reservation['status']) === 'ready' || strtolower($reservation['status']) === 'borrowed') {
            throw new Exception("This reservation cannot be marked as ready - current status: " . $reservation['status']);
        }

        $book_id_to_update = $reservation['book_id'];
        
        // Only look for another book if current book is not available
        if ($reservation['book_status'] !== 'Available') {
            // Look for another available copy of the same book
            $sql = "SELECT id FROM books WHERE title = ? AND status = 'Available' AND id != ? LIMIT 1";  
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $reservation['title'], $reservation['book_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Get the new book_id
                $new_book = $result->fetch_assoc();
                $book_id_to_update = $new_book['id'];

                // Update the reservation with the new book_id
                $sql = "UPDATE reservations SET book_id = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $book_id_to_update, $reservation_id);
                $stmt->execute();
            } else {
                // No available copies available - throw a more descriptive error
                throw new Exception(sprintf(
                    "Cannot mark %s's reservation as ready: No available copies of '%s' available.",
                    htmlspecialchars($reservation['borrower_name']),
                    htmlspecialchars($reservation['title'])
                ));
            }
        }

        // Update reservation status to Ready
        $sql = "UPDATE reservations SET status = 'Ready' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reservation_id);
        
        if (!$stmt->execute()) {
            error_log("SQL Error: " . $stmt->error);
            throw new Exception("Failed to update status: " . $stmt->error);
        }

        // Update book status to Reserved
        $sql = "UPDATE books SET status = 'Reserved' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id_to_update);
        
        if (!$stmt->execute()) {
            error_log("SQL Error: " . $stmt->error);
            throw new Exception("Failed to update book status: " . $stmt->error);
        }

        $conn->commit();
        header('Location: book_reservations.php?success=' . urlencode(
            sprintf("%s's reservation has been marked as ready", $reservation['borrower_name'])
        ));

    } catch (Exception $e) {
        error_log("Error in mark_ready.php: " . $e->getMessage());
        $conn->rollback();
        header('Location: book_reservations.php?error=' . urlencode($e->getMessage()));
    }

    $stmt->close();
    $conn->close();
} else {
    header('Location: book_reservations.php');
}
?>
