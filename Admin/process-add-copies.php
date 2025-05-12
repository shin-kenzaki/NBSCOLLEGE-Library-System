<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Add debugging information
error_log("Process Add Copies - POST data: " . print_r($_POST, true));

// Add transaction support check
$transactionSupported = true;
try {
    $conn->begin_transaction();
    $conn->rollback();
} catch (Exception $e) {
    $transactionSupported = false;
}

// Handle form submission for adding copies
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['copies']) && isset($_POST['book_id'])) {

    if ($transactionSupported) {
        $conn->begin_transaction();
    }

    try {
        $firstBookId = intval($_POST['book_id']);
        $firstAccession = isset($_POST['accession']) ? intval($_POST['accession']) : 0;

        // Debug info
        error_log("Processing copies for book ID: $firstBookId, first accession: $firstAccession");

        // Validate input
        if (empty($_POST['copies'])) {
            throw new Exception("No copies provided");
        }

        // Determine which query to use based on available data
        if ($firstAccession > 0) {
            // If we have the first accession, use it directly for more reliable lookup
            $stmt = $conn->prepare("SELECT * FROM books WHERE accession = ?");
            $stmt->bind_param("i", $firstAccession);
            error_log("Looking up book by accession: $firstAccession");
        } else if ($firstBookId > 0) {
            // Fallback to using book ID if no accession is provided
            $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
            $stmt->bind_param("i", $firstBookId);
            error_log("Looking up book by ID: $firstBookId");
        } else {
            throw new Exception("Invalid book identification - need either ID or accession number");
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Book not found");
        }

        $firstBook = $result->fetch_assoc();
        error_log("Book found: " . $firstBook['title'] . " (ID: " . $firstBook['id'] . ", Accession: " . $firstBook['accession'] . ")");

        // Determine accession leading zeroes length from the first book's accession
        $accessionLength = 0;
        if (!empty($firstBook['accession']) && preg_match('/^\d+$/', $firstBook['accession'])) {
            $accessionLength = strlen($firstBook['accession']);
        }

        $successCount = 0;
        $errorMessages = [];

        foreach ($_POST['copies'] as $copy) {
            $newAccession = isset($copy['accession']) ? $copy['accession'] : '';
            // If accession is numeric and accessionLength > 0, pad with leading zeroes
            if ($accessionLength > 0 && preg_match('/^\d+$/', $newAccession)) {
                $newAccession = str_pad($newAccession, $accessionLength, '0', STR_PAD_LEFT);
            }
            $newSeries = isset($copy['series']) ? $copy['series'] : null;
            $newVolume = isset($copy['volume']) ? $copy['volume'] : null;
            $newPart = isset($copy['part']) ? $copy['part'] : null;
            $newEdition = isset($copy['edition']) ? $copy['edition'] : null;
            $newShelfLocation = isset($copy['shelf_location']) ? $copy['shelf_location'] : null;
            $newCallNumber = isset($copy['call_number']) ? $copy['call_number'] : null;
            $newCopyNumber = isset($copy['copy_number']) ? intval($copy['copy_number']) : null;

            $newProgram = $firstBook['program'];

            error_log("Processing new copy: Accession=$newAccession, Copy=$newCopyNumber, Program=$newProgram, Series=$newSeries, Volume=$newVolume, Part=$newPart, Edition=$newEdition, Shelf Location=$newShelfLocation, Call Number=$newCallNumber");

            // Check if accession number already exists
            $checkStmt = $conn->prepare("SELECT id FROM books WHERE accession = ?");
            $checkStmt->bind_param("s", $newAccession);
            $checkStmt->execute();

            if ($checkStmt->get_result()->num_rows > 0) {
                $errorMessages[] = "Accession number $newAccession already exists - skipping";
                error_log("Accession number $newAccession already exists - skipping");
                continue;
            }

            $query = "INSERT INTO books (
                accession, title, preferred_title, parallel_title, subject_category,
                program, subject_detail, summary, contents, front_image, back_image,
                dimension, series, volume, edition, part, copy_number, total_pages,
                supplementary_contents, ISBN, content_type, media_type, carrier_type,
                call_number, URL, language, shelf_location, entered_by, date_added,
                status, last_update
            ) SELECT 
                ?, title, preferred_title, parallel_title, subject_category,
                ?, subject_detail, summary, contents, front_image, back_image,
                dimension, ?, ?, ?, ?, ?,
                total_pages, supplementary_contents, ISBN, content_type, media_type, carrier_type,
                ?, URL, language, ?, entered_by, ?, 'Available', ?
            FROM books WHERE id = ?";

            $currentDate = date('Y-m-d');
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "sssssssssssi",
                $newAccession, $newProgram, $newSeries, $newVolume, $newEdition, $newPart, $newCopyNumber,
                $newCallNumber, $newShelfLocation, $currentDate, $currentDate, $firstBook['id']
            );

            if ($stmt->execute()) {
                $newBookId = $conn->insert_id;
                $successCount++;
                error_log("Successfully inserted new book with ID: $newBookId");

                // Duplicate publication
                $pubQuery = "INSERT INTO publications (book_id, publisher_id, publish_date)
                             SELECT ?, publisher_id, publish_date FROM publications WHERE book_id = ?";
                $pubStmt = $conn->prepare($pubQuery);
                $pubStmt->bind_param("ii", $newBookId, $firstBook['id']);
                $pubStmt->execute();
                error_log("Added publication data for book ID: $newBookId");

                // Duplicate contributors
                $contribQuery = "INSERT INTO contributors (book_id, writer_id, role)
                                 SELECT ?, writer_id, role FROM contributors WHERE book_id = ?";
                $contribStmt = $conn->prepare($contribQuery);
                $contribStmt->bind_param("ii", $newBookId, $firstBook['id']);
                $contribStmt->execute();
                error_log("Added contributor data for book ID: $newBookId");

                // Duplicate corporate contributors
                $corpContribQuery = "INSERT INTO corporate_contributors (book_id, corporate_id, role)
                                     SELECT ?, corporate_id, role FROM corporate_contributors WHERE book_id = ?";
                $corpContribStmt = $conn->prepare($corpContribQuery);
                $corpContribStmt->bind_param("ii", $newBookId, $firstBook['id']);
                $corpContribStmt->execute();
                error_log("Added corporate contributor data for book ID: $newBookId");
            } else {
                $errorMessage = "Error adding copy with accession $newAccession: " . $stmt->error;
                $errorMessages[] = $errorMessage;
                error_log($errorMessage);
            }
        }

        if ($transactionSupported) {
            $conn->commit();
            error_log("Transaction committed");
        }

        // Create session message
        if ($successCount > 0) {
            $_SESSION['success_message'] = "Successfully added $successCount new copies with status 'Available'!";
            if (!empty($errorMessages)) {
                $_SESSION['error_message'] = implode("<br>", $errorMessages);
            }
            error_log("Success message set: Added $successCount copies");
        } else {
            $_SESSION['error_message'] = "Failed to add any copies. " . implode("<br>", $errorMessages);
            error_log("Error message set: Failed to add any copies");
        }

        // Redirect back to book list
        header("Location: book_list.php");
        exit();

    } catch (Exception $e) {
        if ($transactionSupported) {
            $conn->rollback();
            error_log("Transaction rolled back due to error");
        }
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        error_log("Exception caught: " . $e->getMessage());
        header("Location: book_list.php");
        exit();
    }
} else {
    // If accessed directly without proper parameters
    error_log("Invalid request to process-add-copies.php: " . print_r($_POST, true));
    $_SESSION['error_message'] = "Invalid request";
    header("Location: book_list.php");
    exit();
}
?>