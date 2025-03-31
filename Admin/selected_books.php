<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['selectedBookIds'])) {
        // Ensure we only store valid numeric IDs
        $selectedIds = array_map(function($id) {
            return is_numeric($id) ? (string)$id : null;
        }, $_POST['selectedBookIds']);
        
        // Remove any null values
        $selectedIds = array_filter($selectedIds);
        
        $_SESSION['selectedBookIds'] = array_values($selectedIds);
        
        // Store the return URL if provided
        if (isset($_POST['returnUrl'])) {
            $_SESSION['return_to_form'] = ($_POST['returnUrl'] === 'form');
        }
        
        echo json_encode(['status' => 'success', 'count' => count($selectedIds)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No book IDs provided']);
    }
} else {
    $_SESSION['selectedBookIds'] = [];
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>