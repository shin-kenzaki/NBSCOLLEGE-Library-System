<?php
session_start();
include '../db.php';

// Check if user is logged in with correct privileges
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian'])) {
    $_SESSION['writer_alert'] = [
        'type' => 'error',
        'message' => "You don't have permission to delete writers"
    ];
    header("Location: shortcut_writers.php");
    exit();
}

// Check if delete_writers array is set in POST
if (isset($_POST['delete_writers']) && is_array($_POST['delete_writers'])) {
    $writers_to_delete = $_POST['delete_writers'];
    $deleted_count = 0;
    $error_count = 0;
    
    foreach ($writers_to_delete as $writer_id) {
        // Convert to integer to prevent SQL injection
        $writer_id = (int)$writer_id;
        
        // First check if the writer is used in any books
        $checkSql = "SELECT COUNT(*) as count FROM contributors WHERE writer_id = $writer_id";
        $checkResult = $conn->query($checkSql);
        $row = $checkResult->fetch_assoc();
        
        if ($row['count'] > 0) {
            // Writer is used in books, don't delete
            $error_count++;
            continue;
        }
        
        // Delete the writer
        $deleteSql = "DELETE FROM writers WHERE id = $writer_id";
        if ($conn->query($deleteSql)) {
            $deleted_count++;
        } else {
            $error_count++;
        }
    }
    
    // Set appropriate message based on results
    if ($deleted_count > 0 && $error_count > 0) {
        $_SESSION['writer_alert'] = [
            'type' => 'info',
            'message' => "Successfully deleted $deleted_count writer(s). $error_count writer(s) could not be deleted because they are associated with books."
        ];
    } elseif ($deleted_count > 0) {
        $_SESSION['writer_alert'] = [
            'type' => 'success',
            'message' => "Successfully deleted $deleted_count writer(s)"
        ];
    } else {
        $_SESSION['writer_alert'] = [
            'type' => 'error',
            'message' => "No writers could be deleted. Writers may be associated with books."
        ];
    }
} else {
    $_SESSION['writer_alert'] = [
        'type' => 'error',
        'message' => "No writers selected for deletion"
    ];
}

// Redirect back to the writers page
header("Location: shortcut_writers.php");
exit();