<?php
include '../db.php';
include '../inc/status_helper.php';
session_start();

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT * FROM users";
if (!empty($searchQuery)) {
    $query .= " AND (firstname LIKE '%$searchQuery%' 
                OR lastname LIKE '%$searchQuery%' 
                OR email LIKE '%$searchQuery%' 
                OR school_id LIKE '%$searchQuery%')";
}
$query .= " ORDER BY date_added DESC";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        list($status_class, $status_text) = getStatusDisplay($row['status']);
        
        echo "<tr>";
        echo "<td><input type='checkbox' class='selectRow'></td>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['school_id']}</td>";
        echo "<td>{$row['firstname']} {$row['middle_init']} {$row['lastname']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['contact_no']}</td>";
        echo "<td class='text-center'>{$row['borrowed_books']}</td>";
        echo "<td class='text-center'>{$row['returned_books']}</td>";
        echo "<td class='text-center'>{$row['damaged_books']}</td>";
        echo "<td class='text-center'>{$row['lost_books']}</td>";
        echo "<td><span class='badge {$status_class}'>{$status_text}</span></td>";
        echo "<td>" . date('M d, Y', strtotime($row['date_added'])) . "</td>";
        echo "<td>" . ($row['last_update'] ? date('M d, Y', strtotime($row['last_update'])) : 'Never') . "</td>";
        echo "</tr>";
    }
}
?>
