<?php
session_start();
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bookIds = isset($_POST['book_ids']) ? $_POST['book_ids'] : [];
    $publisherIds = isset($_POST['publisher_ids']) ? $_POST['publisher_ids'] : [];
    $publishDates = isset($_POST['publish_dates']) ? $_POST['publish_dates'] : [];
    
    if (empty($bookIds) || empty($publisherIds)) {
        $_SESSION['error'] = "No books or publisher selected.";
        header("Location: add_publication.php");
        exit();
    }

    $publisherId = $publisherIds[0]; // Get the selected publisher (radio button)
    $publishDate = $publishDates[$publisherId]; // Get the corresponding publish date
    
    $success = true;
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO publications (book_id, publisher_id, publish_date) VALUES (?, ?, ?)");
        
        foreach ($bookIds as $bookId) {
            // Check if publication already exists
            $checkStmt = $conn->prepare("SELECT id FROM publications WHERE book_id = ?");
            $checkStmt->bind_param("i", $bookId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows == 0) {
                $stmt->bind_param("iii", $bookId, $publisherId, $publishDate);
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting publication for book ID: " . $bookId);
                }
            }
        }
        
        $conn->commit();
        $_SESSION['success'] = "Publications added successfully";
        header("Location: publications_list.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: add_publication.php");
        exit();
    }
} else {
    header("Location: add_publication.php");
    exit();
}
?>