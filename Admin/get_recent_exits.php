<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

try {
    // Join with user table to get names
    $sql = "SELECT v1.student_number, v1.time as exit_time, v2.time as entry_time, 
            u.firstname, u.lastname
            FROM library_visits v1
            JOIN users u ON v1.student_number = u.school_id
            LEFT JOIN (
                SELECT student_number, MAX(time) as time
                FROM library_visits
                WHERE status = 1
                GROUP BY student_number
            ) v2 ON v1.student_number = v2.student_number
            WHERE v1.status = 0
            ORDER BY v1.time DESC LIMIT 10";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $exits = array();
        
        while ($row = $result->fetch_assoc()) {
            $exits[] = array(
                'student_id' => $row['student_number'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'exit_time' => $row['exit_time'],
                'entry_time' => $row['entry_time']
            );
        }
        
        echo json_encode(array('success' => true, 'exits' => $exits));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Database query failed'));
    }
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => 'Error: ' . $e->getMessage()));
}

$conn->close();
?>
