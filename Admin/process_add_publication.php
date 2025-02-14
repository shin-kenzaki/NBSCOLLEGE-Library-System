<?php
session_start();
include '../db.php';

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bookIds = isset($_POST['book_ids']) ? $_POST['book_ids'] : [];
    $publisherIds = isset($_POST['publisher_ids']) ? $_POST['publisher_ids'] : [];
    $publishDates = isset($_POST['publish_dates']) ? $_POST['publish_dates'] : [];
    
    if (empty($bookIds) || empty($publisherIds)) {
        $response['message'] = "No books or publisher selected.";
        echo json_encode($response);
        exit();
    }

    $publisherId = $publisherIds[0];
    $publishDate = $publishDates[$publisherId];
    
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO publications (book_id, publisher_id, publish_date) VALUES (?, ?, ?)");
        $successCount = 0;
        $skipCount = 0;
        
        foreach ($bookIds as $bookId) {
            // Check if publication already exists
            $checkStmt = $conn->prepare("SELECT id FROM publications WHERE book_id = ?");
            $checkStmt->bind_param("i", $bookId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows == 0) {
                $stmt->bind_param("iii", $bookId, $publisherId, $publishDate);
                if ($stmt->execute()) {
                    $successCount++;
                }
            } else {
                $skipCount++;
            }
        }
        
        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Successfully added $successCount publication(s)." 
                           . ($skipCount > 0 ? " Skipped $skipCount existing publication(s)." : "");
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = "Error: " . $e->getMessage();
    }
} else {
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
?>