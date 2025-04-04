<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

try {
    // Join with user table to get names
    $sql = "SELECT v.student_number, v.time as entry_time, v.purpose, 
            u.firstname, u.lastname
            FROM library_visits v
            JOIN users u ON v.student_number = u.school_id
            WHERE v.status = 1
            ORDER BY v.time DESC LIMIT 10";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $entries = array();
        
        while ($row = $result->fetch_assoc()) {
            $entries[] = array(
                'student_id' => $row['student_number'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'entry_time' => $row['entry_time'],
                'purpose' => $row['purpose']
            );
        }
        
        echo json_encode(array('success' => true, 'entries' => $entries));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Database query failed'));
    }
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => 'Error: ' . $e->getMessage()));
}

$conn->close();
?>
