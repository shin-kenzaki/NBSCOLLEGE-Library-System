<?php
session_start();

// Check authentication and authorization
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian')) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    include('../db.php');
    
    try {
        $fine_id = $_GET['id'];

        // Update fine status and payment date
        $sql = "UPDATE fines 
                SET status = 'Paid', 
                    payment_date = CURDATE() 
                WHERE id = ? AND status = 'Unpaid'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $fine_id);
        
        if ($stmt->execute()) {
            header("Location: fines.php?success=Fine has been marked as paid successfully");
        } else {
            throw new Exception("Failed to update fine status");
        }
    } catch (Exception $e) {
        header("Location: fines.php?error=Failed to mark fine as paid");
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: fines.php?error=No fine ID provided");
}
?>
