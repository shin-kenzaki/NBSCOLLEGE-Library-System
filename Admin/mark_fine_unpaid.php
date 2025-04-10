<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

include '../db.php';

if (isset($_GET['id'])) {
    $fineId = intval($_GET['id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update fine status and reset payment_date and invoice_sale to NULL
        $sql = "UPDATE fines SET status = 'Unpaid', payment_date = NULL, invoice_sale = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $fineId);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $conn->commit();
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Fine marked as unpaid successfully'
                ]);
            } else {
                throw new Exception("No changes were made. Fine may not exist or is already unpaid.");
            }
        } else {
            throw new Exception("Database error occurred: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error', 
            'message' => $e->getMessage()
        ]);
    } finally {
        $stmt->close();
        $conn->close();
    }
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request: Fine ID is required'
    ]);
}
?>
