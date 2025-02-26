<?php
session_start();
include('../db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = "SELECT 
    r.id,
    CONCAT(u.firstname, ' ', u.lastname) AS user_name,
    b.title AS book_title,
    r.reserve_date,
    TIMESTAMPDIFF(MINUTE, r.reserve_date, NOW()) as minutes_ago
FROM reservations r
JOIN users u ON r.user_id = u.id
JOIN books b ON r.book_id = b.id
WHERE r.status = 'PENDING'
AND r.cancel_date IS NULL
AND r.recieved_date IS NULL
ORDER BY r.reserve_date DESC
LIMIT 5";

$result = $conn->query($query);
$reservations = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Format the time ago
        $minutes = $row['minutes_ago'];
        if ($minutes < 60) {
            $time_ago = $minutes . " min ago";
        } elseif ($minutes < 1440) {
            $hours = floor($minutes / 60);
            $time_ago = $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
        } else {
            $days = floor($minutes / 1440);
            $time_ago = $days . " day" . ($days > 1 ? "s" : "") . " ago";
        }

        $reservations[] = [
            'id' => $row['id'],
            'user_name' => $row['user_name'],
            'book_title' => $row['book_title'],
            'time_ago' => $time_ago
        ];
    }
}

// Get total count of pending reservations
$countQuery = "SELECT COUNT(*) as total FROM reservations WHERE status = 'PENDING' AND cancel_date IS NULL AND recieved_date IS NULL";
$countResult = $conn->query($countQuery);
$totalCount = $countResult->fetch_assoc()['total'];

echo json_encode([
    'reservations' => $reservations,
    'total_count' => $totalCount
]);

$conn->close();
