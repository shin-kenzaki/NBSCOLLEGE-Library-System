<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['selectedBookIds'])) {
        $_SESSION['selectedBookIds'] = $_POST['selectedBookIds'];
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No book IDs provided']);
    }
} else {
    // Clear the session array when the user refreshes the page
    $_SESSION['selectedBookIds'] = [];
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>