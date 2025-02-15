<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    $response = array('success' => false, 'message' => 'Unauthorized access');
    echo json_encode($response);
    exit();
}

require_once '../db.php';

// Check database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    $response = array('success' => false, 'message' => 'Database connection error');
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['writer_id'])) {
    try {
        $writer_id = intval($conn->real_escape_string($_POST['writer_id']));
        
        // Verify writer exists
        $checkWriter = $conn->query("SELECT id FROM writers WHERE id = $writer_id");
        if ($checkWriter->num_rows === 0) {
            throw new Exception('Writer not found');
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Delete associated contributors first
            $deleteContributors = "DELETE FROM contributors WHERE writer_id = $writer_id";
            if (!$conn->query($deleteContributors)) {
                throw new Exception('Error deleting associated contributors: ' . $conn->error);
            }

            // Now delete the writer
            $deleteWriter = "DELETE FROM writers WHERE id = $writer_id";
            if (!$conn->query($deleteWriter)) {
                throw new Exception('Error deleting writer: ' . $conn->error);
            }

            // Commit the transaction
            $conn->commit();
            $response = array('success' => true, 'message' => 'Writer and associated contributions deleted successfully');

        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Error in delete_writer.php: " . $e->getMessage());
        $response = array('success' => false, 'message' => $e->getMessage());
    }
} else {
    $response = array('success' => false, 'message' => 'Invalid request');
}

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>
