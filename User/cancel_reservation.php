<?php
session_start();
include '../db.php';

$response = ['success' => false, 'message' => 'Failed to cancel reservation.'];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

if (isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    $user_id = $_SESSION['user_id'];
    $cancel_date = date('Y-m-d H:i:s');

    try {
        // Start transaction
        $conn->begin_transaction();

        // First check if the reservation belongs to the user and was in 'Ready' status
        $check_query = "SELECT status, book_id FROM reservations 
                       WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($check_query);
        if (!$stmt) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        $stmt->bind_param('ii', $reservation_id, $user_id);
        if (!$stmt->execute()) {
            throw new Exception('Execute statement failed: ' . $stmt->error);
        }
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();

        if (!$reservation) {
            throw new Exception('Reservation not found or unauthorized access.');
        }

        // If the reservation was 'Ready', update the book status to 'Available'
        if ($reservation['status'] === 'Ready') {
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

        // Update reservation status with cancelled_by field
        $update_reservation = "UPDATE reservations 
                             SET status = 'Cancelled',
                                 cancel_date = ?,
                                 cancelled_by = ?,
                                 cancelled_by_role = 'User'
                             WHERE id = ? 
                             AND user_id = ?";
        $stmt = $conn->prepare($update_reservation);
        if (!$stmt) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        $stmt->bind_param('siii', $cancel_date, $user_id, $reservation_id, $user_id);
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
