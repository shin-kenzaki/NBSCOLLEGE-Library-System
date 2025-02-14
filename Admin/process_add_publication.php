<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../db.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $publisherIds = isset($_POST['publisher_ids']) ? $_POST['publisher_ids'] : [];
    $publishYears = isset($_POST['publish_dates']) ? $_POST['publish_dates'] : [];

    // Retrieve selected book IDs from session
    $bookIds = isset($_SESSION['selected_book_ids']) ? $_SESSION['selected_book_ids'] : [];

    if (!empty($bookIds) && !empty($publisherIds)) {
        foreach ($bookIds as $bookId) {
            // Check if the book already has a publisher
            $checkQuery = "SELECT * FROM publications WHERE book_id = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $checkResult = $stmt->get_result();

            if ($checkResult->num_rows == 0) {
                // Since we're using radio buttons, we only have one publisher and one year
                $publisherId = $publisherIds[0];  // Get the first (and only) publisher ID
                $publishYear = $publishYears[0];  // Get the first (and only) year
                
                // Use prepared statement to prevent SQL injection
                $query = "INSERT INTO publications (book_id, publisher_id, publish_date) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iii", $bookId, $publisherId, $publishYear);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Publication added successfully!";
                } else {
                    $_SESSION['error_message'] = "Error adding publication: " . $stmt->error;
                }
            } else {
                $_SESSION['error_message'] = "Book ID $bookId already has a publisher.";
            }
        }
    } else {
        $_SESSION['error_message'] = "Book IDs and Publisher IDs are required.";
    }

    header("Location: book_list.php");
    exit();
}
?>