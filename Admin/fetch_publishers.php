<?php
include '../db.php'; // Database connection

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT id, company, place FROM publishers";
if (!empty($searchQuery)) {
    $sql .= " WHERE company LIKE '%$searchQuery%' OR place LIKE '%$searchQuery%'";
}
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['company']}</td>
                <td>{$row['place']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='3'>No publishers found</td></tr>";
}
?>