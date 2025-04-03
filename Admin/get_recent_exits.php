<?php
require_once '../db.php';

// Return recent library exits (status = 0 for exit)
$response = [
    'success' => false,
    'exits' => []
];

try {
    // Get the 10 most recent exits with status 0 (exit)
    // For each exit, find the most recent entry time for the same student
    $sql = "SELECT lv.id, lv.student_number as student_id, lv.time as exit_time, 
            (SELECT MAX(time) FROM library_visits 
             WHERE student_number = lv.student_number AND status = 1 AND time < lv.time) as entry_time,
            plu.firstname, plu.lastname, plu.course, plu.year 
            FROM library_visits lv
            JOIN physical_login_users plu ON lv.student_number = plu.student_number
            WHERE lv.status = 0
            ORDER BY lv.time DESC
            LIMIT 10";
            
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $exits = [];
        
        while ($row = $result->fetch_assoc()) {
            $exits[] = [
                'id' => $row['id'],
                'student_id' => $row['student_id'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'course' => $row['course'],
                'year' => $row['year'],
                'exit_time' => $row['exit_time'],
                'entry_time' => $row['entry_time']
            ];
        }
        
        $response['success'] = true;
        $response['exits'] = $exits;
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
