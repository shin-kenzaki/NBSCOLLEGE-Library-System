<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['selectedWriterIds'])) {
        if (empty($_POST['selectedWriterIds'])) {
            unset($_SESSION['selectedWriterIds']);
        } else {
            $_SESSION['selectedWriterIds'] = $_POST['selectedWriterIds'];
        }
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No writer IDs provided']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>