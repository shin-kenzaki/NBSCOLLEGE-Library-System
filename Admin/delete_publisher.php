<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publisher_id'])) {
    try {
        $publisher_id = intval($conn->real_escape_string($_POST['publisher_id']));
        
        // Verify publisher exists
        $checkPublisher = $conn->query("SELECT id FROM publishers WHERE id = $publisher_id");
        if ($checkPublisher->num_rows === 0) {
            throw new Exception('Publisher not found');
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // First delete related publications
            $deletePublications = "DELETE FROM publications WHERE publisher_id = $publisher_id";
            if (!$conn->query($deletePublications)) {
                throw new Exception('Error deleting related publications: ' . $conn->error);
            }

            // Then delete the publisher
            $deletePublisher = "DELETE FROM publishers WHERE id = $publisher_id";
            if (!$conn->query($deletePublisher)) {
                throw new Exception('Error deleting publisher: ' . $conn->error);
            }

            // Commit the transaction
            $conn->commit();
            $response = array('success' => true, 'message' => 'Publisher and related publications deleted successfully');

        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Error in delete_publisher.php: " . $e->getMessage());
        $response = array('success' => false, 'message' => $e->getMessage());
    }
} else {
    $response = array('success' => false, 'message' => 'Invalid request');
}

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>
