<?php
session_start();
include '../db.php';

$response = ['success' => false, 'message' => 'Failed to cancel reservation.'];

if (isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    $cancel_date = date('Y-m-d H:i:s');

    try {
        // Start transaction
        $conn->begin_transaction();

        // First check if the reservation was in 'Ready' status
        $check_query = "SELECT status, book_id FROM reservations WHERE id = ?";
        $stmt = $conn->prepare($check_query);
        if (!$stmt) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        $stmt->bind_param('i', $reservation_id);
        if (!$stmt->execute()) {
            throw new Exception('Execute statement failed: ' . $stmt->error);
        }
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();

        // If the reservation was 'Ready', update the book status to 'Available'
        if ($reservation && $reservation['status'] === 'Ready') {
            $update_book = "UPDATE books SET status = 'Available' WHERE id = ?";
            $stmt = $conn->prepare($update_book);
            if (!$stmt) {
                throw new Exception('Prepare statement failed: ' . $conn->error);
            }
            $stmt->bind_param('i', $reservation['book_id']);
            if (!$stmt->execute()) {
                throw new Exception('Execute statement failed: ' . $stmt->error);
            }
        }

        // Update reservation status
        $update_reservation = "UPDATE reservations SET status = '0', cancel_date = ? WHERE id = ?";
        $stmt = $conn->prepare($update_reservation);
        if (!$stmt) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        $stmt->bind_param('si', $cancel_date, $reservation_id);
        if (!$stmt->execute()) {
            throw new Exception('Execute statement failed: ' . $stmt->error);
        }

        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = 'Reservation cancelled successfully.';
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $response['message'] = $e->getMessage();
    }
}

echo json_encode($response);
?>
