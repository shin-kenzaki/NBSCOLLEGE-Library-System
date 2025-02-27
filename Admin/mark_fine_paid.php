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
        $fine_id = intval($_GET['id']);
        
        // Start transaction
        $conn->begin_transaction();

        // First check if the fine exists and is unpaid
        $check_sql = "SELECT id, status FROM fines WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $fine_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Fine not found");
        }
        
        $fine = $result->fetch_assoc();
        if ($fine['status'] === 'Paid') {
            throw new Exception("Fine is already paid");
        }

        // Update fine status
        $sql = "UPDATE fines 
                SET status = 'Paid', 
                    payment_date = CURDATE() 
                WHERE id = ? AND status = 'Unpaid'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $fine_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $conn->commit();
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Fine has been marked as paid successfully'
                ]);
            } else {
                throw new Exception("Failed to update fine status");
            }
        } else {
            throw new Exception("Database error occurred");
        }
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    } finally {
        if (isset($stmt)) $stmt->close();
        if (isset($check_stmt)) $check_stmt->close();
        $conn->close();
    }
} else {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'No fine ID provided'
    ]);
}
?>
