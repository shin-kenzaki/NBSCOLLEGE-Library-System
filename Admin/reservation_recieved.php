<?php
session_start();

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian')) {
    header("Location: login.php"); // Redirect to login page if not logged in or not an admin/librarian
    exit();
}

// Check if reservation ID is provided
if (isset($_GET['id'])) {
    $reservation_id = $_GET['id'];

    // Database connection details
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "librarysystem"; 

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get the book_id and user_id from the reservation
        $sql = "SELECT book_id, user_id FROM reservations WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $book_id = $row['book_id'];
        $user_id = $row['user_id'];

        // Update reservation: set received_date and status
        $sql = "UPDATE reservations 
                SET recieved_date = NOW(), 
                    status = 'Received' 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();

        // Update book status to borrowed
        $sql = "UPDATE books 
                SET status = 'Borrowed' 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();

        // Update user statistics
        $sql = "UPDATE users 
                SET borrowed_books = borrowed_books + 1 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Insert new borrowing record
        $allowed_days = 1;
        $sql = "INSERT INTO borrowings (user_id, book_id, status, borrow_date, allowed_days, due_date) 
                VALUES (?, ?, 'Active', NOW(), ?, DATE_ADD(NOW(), INTERVAL ? DAY))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $user_id, $book_id, $allowed_days, $allowed_days);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        header("Location: book_reservations.php?message=Reservation marked as received successfully");
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header("Location: book_reservations.php?error=Failed to mark reservation as received");
    }

    $stmt->close();
    $conn->close();
} else {
    // Redirect back to the borrowing requests page with an error message if no reservation ID is provided
    header("Location: book_reservations.php?error=Reservation ID not provided");
    exit();
}
?>
