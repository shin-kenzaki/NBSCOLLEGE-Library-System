<?php
include '../db.php';

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $book_title = $conn->real_escape_string($_POST['book_title']);
    $writer_id = intval($_POST['writer_id']);
    $role = $conn->real_escape_string($_POST['role']);
    $update_all = intval($_POST['update_all']);

    if ($update_all) {
        // Update contributors for all books with the same title
        $query = "SELECT id FROM books WHERE title = (SELECT title FROM books WHERE id = 
                  (SELECT book_id FROM contributors WHERE id = $id))";
        $result = $conn->query($query);
        
        $success = true;
        while ($row = $result->fetch_assoc()) {
            $book_id = $row['id'];
            $updateQuery = "UPDATE contributors SET 
                           writer_id = $writer_id,
                           role = '$role' 
                           WHERE book_id = $book_id 
                           AND role = (SELECT role FROM contributors WHERE id = $id)";
            if (!$conn->query($updateQuery)) {
                $success = false;
                break;
            }
        }
    } else {
        // Update only the selected contributor
        $updateQuery = "UPDATE contributors SET 
                       writer_id = $writer_id,
                       role = '$role' 
                       WHERE id = $id";
        $success = $conn->query($updateQuery);
    }

    $response['success'] = $success;
    $response['message'] = $success ? 'Updated successfully' : 'Update failed';
}

echo json_encode($response);
