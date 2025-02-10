<?php
include '../db.php'; // Database connection
session_start();

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$selectedPublisherIds = isset($_SESSION['selectedPublisherIds']) ? $_SESSION['selectedPublisherIds'] : [];

// Fetch selected publishers first
$selectedPublishers = [];
if (!empty($selectedPublisherIds)) {
    $selectedPublishersQuery = "SELECT * FROM publishers WHERE id IN (" . implode(',', array_map('intval', $selectedPublisherIds)) . ")";
    $selectedPublishersResult = $conn->query($selectedPublishersQuery);
    while ($row = $selectedPublishersResult->fetch_assoc()) {
        $selectedPublishers[] = $row;
    }
}

// Fetch other publishers
$query = "SELECT * FROM publishers";
if (!empty($searchQuery)) {
    $query .= " WHERE (company LIKE '%$searchQuery%' OR place LIKE '%$searchQuery%')";
    if (!empty($selectedPublisherIds)) {
        $query .= " AND id NOT IN (" . implode(',', array_map('intval', $selectedPublisherIds)) . ")";
    }
} else {
    if (!empty($selectedPublisherIds)) {
        $query .= " WHERE id NOT IN (" . implode(',', array_map('intval', $selectedPublisherIds)) . ")";
    }
}
$query .= " ORDER BY id DESC";
$result = $conn->query($query);

// Display selected publishers first
foreach ($selectedPublishers as $row) {
    $isChecked = in_array($row['id'], $selectedPublisherIds) ? 'checked' : '';
    echo "<tr>
        <td><input type='checkbox' class='selectPublisher' name='publisher_ids[]' value='{$row['id']}' $isChecked></td>
        <td>{$row['id']}</td>
        <td>{$row['company']}</td>
        <td>{$row['place']}</td>
    </tr>";
}

// Display other publishers
while ($row = $result->fetch_assoc()) {
    $isChecked = in_array($row['id'], $selectedPublisherIds) ? 'checked' : '';
    echo "<tr>
        <td><input type='checkbox' class='selectPublisher' name='publisher_ids[]' value='{$row['id']}' $isChecked></td>
        <td>{$row['id']}</td>
        <td>{$row['company']}</td>
        <td>{$row['place']}</td>
    </tr>";
}
?>