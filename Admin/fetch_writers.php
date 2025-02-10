<?php
include '../db.php'; // Database connection

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT id, firstname, middle_init, lastname FROM writers";
if (!empty($searchQuery)) {
    $sql .= " WHERE firstname LIKE '%$searchQuery%' OR middle_init LIKE '%$searchQuery%' OR lastname LIKE '%$searchQuery%'";
}
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['firstname']}</td>
                <td>{$row['middle_init']}</td>
                <td>{$row['lastname']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='4'>No writers found</td></tr>";
}
?>