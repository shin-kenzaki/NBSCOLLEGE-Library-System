<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../db.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $publisherIds = isset($_POST['publisher_ids']) ? $_POST['publisher_ids'] : [];
    $publishDates = isset($_POST['publish_dates']) ? $_POST['publish_dates'] : [];

    // Retrieve selected book IDs from session
    $bookIds = isset($_SESSION['selected_book_ids']) ? $_SESSION['selected_book_ids'] : [];

    if (!empty($bookIds) && !empty($publisherIds)) {
        foreach ($bookIds as $bookId) {
            // Check if the book already has a publisher
            $checkQuery = "SELECT * FROM publications WHERE book_id = '$bookId'";
            $checkResult = $conn->query($checkQuery);

            if ($checkResult->num_rows == 0) {
                foreach ($publisherIds as $index => $publisherId) {
                    $publishDate = isset($publishDates[$index]) ? $publishDates[$index] : date('Y-m-d'); // Default to today's date if not provided
                    // Insert the relationship into the database
                    $query = "INSERT INTO publications (book_id, publisher_id, publish_date) VALUES ('$bookId', '$publisherId', '$publishDate')";
                    if ($conn->query($query) === TRUE) {
                        $_SESSION['success_message'] = "Publication added successfully!";
                    } else {
                        echo "Error: " . $query . "<br>" . $conn->error;
                    }
                }
            } else {
                $_SESSION['success_message'] = "Some books already have a publisher.";
            }
        }
    } else {
        echo "Book IDs and Publisher IDs are required.";
    }

    header("Location: book_list.php");
    exit();
}
?>