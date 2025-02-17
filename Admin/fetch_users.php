<?php
session_start();
include '../db.php';
include 'inc/status_helper.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    die('Not authorized');
}

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT u.*, 
    u.borrowed_books,
    u.returned_books,
    u.damaged_books,
    u.lost_books
    FROM users u";

if (!empty($searchQuery)) {
    $query .= " WHERE u.firstname LIKE '%$searchQuery%' 
                OR u.lastname LIKE '%$searchQuery%' 
                OR u.email LIKE '%$searchQuery%' 
                OR u.school_id LIKE '%$searchQuery%'
                OR u.contact_no LIKE '%$searchQuery%'";
}

$query .= " ORDER BY u.date_added DESC";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $fullname = $row['firstname'] . ' ' . ($row['middle_init'] ? $row['middle_init'] . ' ' : '') . $row['lastname'];
    list($status_class, $status_text) = getStatusDisplay($row['status']);
    
    echo "<tr>";
    echo "<td><input type='checkbox' class='selectRow'></td>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['school_id']}</td>";
    echo "<td>" . htmlspecialchars($fullname) . "</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>{$row['contact_no']}</td>";
    echo "<td><span class='badge badge-info'>{$row['borrowed_books']}</span></td>";
    echo "<td><span class='badge badge-success'>{$row['returned_books']}</span></td>";
    echo "<td><span class='badge badge-warning'>{$row['damaged_books']}</span></td>";
    echo "<td><span class='badge badge-danger'>{$row['lost_books']}</span></td>";
    echo "<td><span class='badge {$status_class}'>{$status_text}</span></td>";
    echo "<td>" . date('M d, Y', strtotime($row['date_added'])) . "</td>";
    echo "<td>" . ($row['last_update'] ? date('M d, Y', strtotime($row['last_update'])) : 'Never') . "</td>";
    echo "</tr>";
}
?>
