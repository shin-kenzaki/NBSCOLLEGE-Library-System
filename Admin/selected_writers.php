<?php
session_start();

// This file handles AJAX requests to update selected writers in the session

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selectedWriterIds'])) {
    $_SESSION['selected_writer_ids'] = $_POST['selectedWriterIds'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>