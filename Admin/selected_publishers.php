<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['selectedPublisherIds'])) {
        if (empty($_POST['selectedPublisherIds'])) {
            $_SESSION['selectedPublisherIds'] = null;
        } else {
            $_SESSION['selectedPublisherIds'] = $_POST['selectedPublisherIds'];
        }
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No publisher IDs provided']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>