<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_ids = $_POST['book_ids'] ?? [];
    $response = ['success' => false, 'message' => 'Failed to remove items from the cart.'];

    if (!empty($book_ids)) {
        try {
            // Prepare the query to update the cart status
            $placeholders = implode(',', array_fill(0, count($book_ids), '?'));
            $query = "UPDATE cart SET status = 0 WHERE user_id = ? AND book_id IN ($placeholders)";
            $stmt = $conn->prepare($query);

            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }

            // Bind parameters dynamically
            $types = str_repeat('i', count($book_ids) + 1);
            $params = array_merge([$user_id], $book_ids);
            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }

            $response['success'] = true;
            $response['message'] = 'Selected items removed from the cart successfully.';
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
    } else {
        $response['message'] = 'No items selected for removal.';
    }

    echo json_encode($response);
    exit();
}
?>
