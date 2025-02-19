<?php
session_start();

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian')) {
    header("Location: login.php"); // Redirect to login page if not logged in or not an admin/librarian
    exit();
}

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "librarysystem";  // Changed from library_system

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if reservation ID is provided
if (isset($_GET['id'])) {
    $reservation_id = $_GET['id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get book info from reservation
        $sql = "SELECT book_id, status FROM reservations WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();

        // Update the cancel_date of the reservation
        $sql = "UPDATE reservations SET cancel_date = NOW(), status = 'Cancelled' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();

        // If reservation status was "Ready", update book status back to Available
        if ($reservation['status'] === 'Ready') {
            $sql = "UPDATE books SET status = 'Available' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $reservation['book_id']);
            $stmt->execute();
        }

        $conn->commit();
        header("Location: book_reservations.php?message=Reservation cancelled successfully");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: book_reservations.php?error=Failed to cancel reservation: " . $e->getMessage());
        exit();
    }

    $stmt->close();
} else {
    // Redirect back to the borrowing requests page with an error message if no reservation ID is provided
    header("Location: book_reservations.php?error=Reservation ID not provided");
    exit();
}

$conn->close();
?>
