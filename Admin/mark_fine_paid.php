<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include('../db.php');

try {
    // Check if the request is POST or GET
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Handle POST with form data
        if (!isset($_POST['fine_id'])) {
            throw new Exception("Fine ID is required");
        }
        
        $fine_id = intval($_POST['fine_id']);
        $payment_date = isset($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
        $invoice_sale = isset($_POST['invoice_sale']) ? floatval($_POST['invoice_sale']) : null;
        
    } else {
        // Handle the original GET request
        if (!isset($_GET['id'])) {
            throw new Exception("Fine ID is required");
        }
        
        $fine_id = intval($_GET['id']);
        $payment_date = isset($_GET['payment_date']) ? $_GET['payment_date'] : date('Y-m-d');
        $invoice_sale = isset($_GET['invoice_sale']) ? floatval($_GET['invoice_sale']) : null;
    }
    
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

    // Update fine status with payment_date and invoice_sale
    $sql = "UPDATE fines 
            SET status = 'Paid', 
                payment_date = ?, 
                invoice_sale = ? 
            WHERE id = ? AND status = 'Unpaid'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdi", $payment_date, $invoice_sale, $fine_id);
    
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
    if (isset($conn)) $conn->rollback();
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($conn)) $conn->close();
}
?>
