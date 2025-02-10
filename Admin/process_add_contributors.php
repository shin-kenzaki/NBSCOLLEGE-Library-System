<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../db.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $writerIds = isset($_POST['writer_ids']) ? $_POST['writer_ids'] : [];
    $roles = isset($_POST['roles']) ? $_POST['roles'] : [];

    // Retrieve selected book IDs from session
    $bookIds = isset($_SESSION['selected_book_ids']) ? $_SESSION['selected_book_ids'] : [];

    if (!empty($bookIds) && !empty($writerIds)) {
        foreach ($bookIds as $bookId) {
            foreach ($writerIds as $index => $writerId) {
                $role = isset($roles[$index]) ? $roles[$index] : 'Author'; // Default role to 'Author' if not provided
                // Insert the relationship into the database
                $query = "INSERT INTO contributors (book_id, writer_id, role) VALUES ('$bookId', '$writerId', '$role')";
                if ($conn->query($query) === TRUE) {
                    echo "Contributor added successfully!";
                } else {
                    echo "Error: " . $query . "<br>" . $conn->error;
                }
            }
        }
    } else {
        echo "Book IDs and Writer IDs are required.";
    }

    header("Location: book_list.php");
    exit();
}
?>