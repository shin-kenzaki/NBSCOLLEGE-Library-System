<?php
include '../db.php';

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $book_title = $conn->real_escape_string($_POST['book_title']);
    $publisher_id = intval($_POST['publisher_id']);
    $publish_date = intval($_POST['publish_date']);

    // Get all books with the same title
    $query = "SELECT id FROM books WHERE title = (SELECT title FROM books WHERE id = 
              (SELECT book_id FROM publications WHERE id = $id))";
    $result = $conn->query($query);
    
    $success = true;
    while ($row = $result->fetch_assoc()) {
        $book_id = $row['id'];
        $updateQuery = "UPDATE publications SET 
                       publisher_id = $publisher_id,
                       publish_date = $publish_date 
                       WHERE book_id = $book_id";
        if (!$conn->query($updateQuery)) {
            $success = false;
            break;
        }
    }

    $response['success'] = $success;
    $response['message'] = $success ? 'Updated successfully' : 'Update failed';
}

echo json_encode($response);
