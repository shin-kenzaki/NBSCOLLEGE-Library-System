<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

function expandIdRanges($idRanges) {
    $ids = [];
    $ranges = explode(',', $idRanges);
    
    foreach ($ranges as $range) {
        $range = trim($range);
        if (strpos($range, '-') !== false) {
            list($start, $end) = explode('-', $range);
            for ($i = (int)$start; $i <= (int)$end; $i++) {
                $ids[] = $i;
            }
        } else {
            $ids[] = (int)$range;
        }
    }
    return $ids;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
    try {
        $idRanges = $_POST['ids'];
        $allIds = expandIdRanges($idRanges);
        
        // Convert array to string for SQL IN clause
        $idList = implode(',', $allIds);
        
        // Delete the publications
        $query = "DELETE FROM publications WHERE id IN ($idList)";
        if ($conn->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Publications deleted successfully']);
        } else {
            throw new Exception('Failed to delete publications');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
