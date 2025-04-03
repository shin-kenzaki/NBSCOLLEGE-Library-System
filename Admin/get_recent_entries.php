<?php
require_once '../db.php';

// Return recent library entries (status = 1 for entrance)
$response = [
    'success' => false,
    'entries' => []
];

try {
    // Get the 10 most recent entries with status 1 (entrance)
    $sql = "SELECT lv.id, lv.student_number as student_id, lv.time as entry_time, 
            lv.purpose, plu.firstname, plu.lastname, plu.course, plu.year 
            FROM library_visits lv
            JOIN physical_login_users plu ON lv.student_number = plu.student_number
            WHERE lv.status = 1
            ORDER BY lv.time DESC
            LIMIT 10";
            
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $entries = [];
        
        while ($row = $result->fetch_assoc()) {
            $entries[] = [
                'id' => $row['id'],
                'student_id' => $row['student_id'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'course' => $row['course'],
                'year' => $row['year'],
                'entry_time' => $row['entry_time'],
                'purpose' => $row['purpose']
            ];
        }
        
        $response['success'] = true;
        $response['entries'] = $entries;
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
