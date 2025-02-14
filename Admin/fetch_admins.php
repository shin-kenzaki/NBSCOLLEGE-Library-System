<?php
include '../db.php';
include '../inc/status_helper.php';
session_start();

// Clear the session array when the page is refreshed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['selectedAdminIds'] = [];
}

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$selectedAdminIds = isset($_SESSION['selectedAdminIds']) ? $_SESSION['selectedAdminIds'] : [];

// Fetch selected admins first
$selectedAdmins = [];
if (!empty($selectedAdminIds)) {
    $selectedAdminsQuery = "SELECT * FROM admins WHERE id IN (" . implode(',', array_map('intval', $selectedAdminIds)) . ")";
    $selectedAdminsResult = $conn->query($selectedAdminsQuery);
    while ($row = $selectedAdminsResult->fetch_assoc()) {
        $selectedAdmins[] = $row;
    }
}

// Fetch other admins
$query = "SELECT * FROM admins";
if (!empty($searchQuery)) {
    $query .= " WHERE (firstname LIKE '%$searchQuery%' OR lastname LIKE '%$searchQuery%' OR email LIKE '%$searchQuery%' OR employee_id LIKE '%$searchQuery%')";
    if (!empty($selectedAdminIds)) {
        $query .= " AND id NOT IN (" . implode(',', array_map('intval', $selectedAdminIds)) . ")";
    }
} else {
    if (!empty($selectedAdminIds)) {
        $query .= " WHERE id NOT IN (" . implode(',', array_map('intval', $selectedAdminIds)) . ")";
    }
}
$query .= " ORDER BY date_added DESC";
$result = $conn->query($query);

// Output rows
foreach ($selectedAdmins as $row) {
    outputAdminRow($row, true);
}

while ($row = $result->fetch_assoc()) {
    outputAdminRow($row, false);
}

function outputAdminRow($row, $isChecked) {
    $fullname = $row['firstname'] . ' ' . $row['middle_init'] . ' ' . $row['lastname'];
    list($status_class, $status_text) = getStatusDisplay($row['status']);
    
    echo "<tr>";
    echo "<td><input type='checkbox' class='selectRow' " . ($isChecked ? 'checked' : '') . "></td>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['employee_id']}</td>";
    echo "<td>{$fullname}</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>{$row['role']}</td>";
    echo "<td><span class='badge {$status_class}'>{$status_text}</span></td>";
    echo "<td>" . date('M d, Y', strtotime($row['date_added'])) . "</td>";
    echo "<td>" . ($row['last_update'] ? date('M d, Y', strtotime($row['last_update'])) : 'Never') . "</td>";
    echo "</tr>";
}
?>
