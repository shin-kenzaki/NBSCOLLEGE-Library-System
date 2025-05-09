<?php
session_start();
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
    $ids = $_POST['ids'];

    // Ensure all IDs are integers
    $ids = array_map('intval', $ids);
    $idsString = implode(',', $ids);

    $query = "DELETE FROM corporates WHERE id IN ($idsString)";
    if ($conn->query($query) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Selected corporates deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete selected corporates.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
