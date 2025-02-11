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
            // Check if the book already has an author
            $authorCheckQuery = "SELECT * FROM contributors WHERE book_id = '$bookId' AND role = 'Author'";
            $authorCheckResult = $conn->query($authorCheckQuery);

            if ($authorCheckResult->num_rows > 0) {
                $_SESSION['success_message'] = "Book ID $bookId already has an author.";
                continue; // Skip adding another author for this book
            }

            foreach ($writerIds as $index => $writerId) {
                $role = isset($roles[$index]) ? $roles[$index] : 'Author'; // Default role to 'Author' if not provided

                // Check if the writer is already associated with the book
                $checkQuery = "SELECT * FROM contributors WHERE book_id = '$bookId' AND writer_id = '$writerId'";
                $checkResult = $conn->query($checkQuery);

                if ($checkResult->num_rows == 0) {
                    // Insert the relationship into the database
                    $query = "INSERT INTO contributors (book_id, writer_id, role) VALUES ('$bookId', '$writerId', '$role')";
                    if ($conn->query($query) === TRUE) {
                        $_SESSION['success_message'] = "Contributor added successfully!";
                    } else {
                        echo "Error: " . $query . "<br>" . $conn->error;
                    }
                } else {
                    $_SESSION['success_message'] = "Some contributors were already added to the book.";
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