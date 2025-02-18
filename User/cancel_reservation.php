<?php
session_start();
include '../db.php';

$response = ['success' => false, 'message' => 'Failed to cancel reservation.'];

if (isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    $cancel_date = date('Y-m-d H:i:s');

    // Update reservation status to inactive and set cancel_date
    $query = "UPDATE reservations SET status = 0, cancel_date = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('si', $cancel_date, $reservation_id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Reservation cancelled successfully.';
        } else {
            $response['message'] = 'Execute statement failed: ' . $stmt->error;
        }
    } else {
        $response['message'] = 'Prepare statement failed: ' . $conn->error;
    }
}

echo json_encode($response);
?>
