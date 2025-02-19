<?php
function updateOverdueStatus($conn) {
    $sql = "UPDATE borrowings 
            SET status = 'Overdue'
            WHERE due_date < CURDATE() 
            AND status = 'Active' 
            AND return_date IS NULL";
    
    $stmt = $conn->prepare($sql);
    return $stmt->execute();
}
