<?php
function updateInactiveUsers($conn) {
    // Get users who haven't been updated in 15 days and are still active
    $sql = "UPDATE users 
            SET status = '0', 
                last_update = CURRENT_DATE 
            WHERE DATEDIFF(CURRENT_DATE, last_update) >= 15 
            AND (status = '1' OR status IS NULL)";
            
    $conn->query($sql);
}
