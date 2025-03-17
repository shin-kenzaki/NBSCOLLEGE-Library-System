<?php
function updateOverdueStatus($conn) {
    // Get current date in Y-m-d format without time component
    $currentDate = date('Y-m-d');
    
    // Update the status to 'Overdue' only if the current date is strictly greater than the due date
    // This ensures items are only marked overdue after the due date has passed
    $query = "UPDATE borrowings SET status = 'Overdue' 
              WHERE DATE(due_date) < '$currentDate' 
              AND (status = 'Active' OR status = 'Overdue') 
              AND return_date IS NULL";
    
    // Reset any items that might have been incorrectly marked
    $resetQuery = "UPDATE borrowings SET status = 'Active' 
                  WHERE DATE(due_date) >= '$currentDate' 
                  AND status = 'Overdue'
                  AND return_date IS NULL";
                  
    $conn->query($query);
    $conn->query($resetQuery);
}
?>
