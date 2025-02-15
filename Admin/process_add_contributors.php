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
    $bookIds = isset($_SESSION['selected_book_ids']) ? $_SESSION['selected_book_ids'] : [];

    if (!empty($bookIds) && !empty($writerIds)) {
        $conn->begin_transaction(); // Start transaction
        $success = true;
        $authorTracker = []; // Track which books already have authors

        try {
            // First, get existing authors for all selected books
            $authorCheckQuery = "SELECT book_id FROM contributors WHERE book_id IN (" . 
                implode(',', array_map('intval', $bookIds)) . ") AND role = 'Author'";
            $authorResult = $conn->query($authorCheckQuery);
            while ($row = $authorResult->fetch_assoc()) {
                $authorTracker[$row['book_id']] = true;
            }

            // Process one writer at a time for all books
            for ($i = 0; $i < count($writerIds); $i++) {
                $writerId = $writerIds[$i];
                $role = $roles[$i];
                $isAuthorRole = ($role === 'Author');

                // Process all books for this writer
                foreach ($bookIds as $bookId) {
                    // Check if this combination already exists
                    $checkQuery = "SELECT * FROM contributors WHERE book_id = ? AND writer_id = ?";
                    $stmt = $conn->prepare($checkQuery);
                    $stmt->bind_param('ii', $bookId, $writerId);
                    $stmt->execute();
                    $exists = $stmt->get_result()->num_rows > 0;
                    $stmt->close();

                    if (!$exists) {
                        // Skip if trying to add author to book that already has one
                        if ($isAuthorRole && isset($authorTracker[$bookId])) {
                            continue;
                        }

                        // Insert the new contributor
                        $insertQuery = "INSERT INTO contributors (book_id, writer_id, role) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($insertQuery);
                        $stmt->bind_param('iis', $bookId, $writerId, $role);
                        
                        if ($stmt->execute()) {
                            if ($isAuthorRole) {
                                $authorTracker[$bookId] = true;
                            }
                        } else {
                            $success = false;
                            throw new Exception("Error adding contributor: " . $conn->error);
                        }
                        $stmt->close();
                    }
                }
            }

            if ($success) {
                $conn->commit();
                $_SESSION['success_message'] = "Contributors added successfully!";
            } else {
                $conn->rollback();
                $_SESSION['error_message'] = "Some contributors could not be added.";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Book IDs and Writer IDs are required.";
    }

    header("Location: book_list.php");
    exit();
}
?>