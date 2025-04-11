<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include('../db.php');

try {
    // Check if the request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    // Check if fine_ids are provided
    if (!isset($_POST['fine_ids'])) {
        throw new Exception("Fine IDs are required");
    }

    // Decode the fine_ids JSON string
    $fineIds = json_decode($_POST['fine_ids'], true);
    if (!is_array($fineIds) || empty($fineIds)) {
        throw new Exception("Invalid or empty Fine IDs");
    }

    // Start transaction
    $conn->begin_transaction();

    // Update all selected fines as unpaid
    $updateQuery = "UPDATE fines
                    SET status = 'Unpaid',
                        payment_date = NULL,
                        invoice_sale = NULL
                    WHERE id IN (" . implode(',', array_map('intval', $fineIds)) . ") AND status = 'Paid'";
    $stmt = $conn->prepare($updateQuery);

    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => 'Selected fines have been marked as unpaid successfully'
        ]);
    } else {
        throw new Exception("Failed to update fine statuses");
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
    if (isset($conn)) $conn->close();
}
?>