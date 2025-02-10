<?php
include '../db.php'; // Database connection
session_start();

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$selectedBookIds = isset($_SESSION['selectedBookIds']) ? $_SESSION['selectedBookIds'] : [];

// Fetch selected books first
$selectedBooks = [];
if (!empty($selectedBookIds)) {
    $selectedBooksQuery = "SELECT * FROM books WHERE id IN (" . implode(',', array_map('intval', $selectedBookIds)) . ")";
    $selectedBooksResult = $conn->query($selectedBooksQuery);
    while ($row = $selectedBooksResult->fetch_assoc()) {
        $selectedBooks[] = $row;
    }
}

// Fetch other books
$query = "SELECT * FROM books";
if (!empty($searchQuery)) {
    $query .= " WHERE (title LIKE '%$searchQuery%' OR preferred_title LIKE '%$searchQuery%' OR parallel_title LIKE '%$searchQuery%' OR ISBN LIKE '%$searchQuery%')";
    if (!empty($selectedBookIds)) {
        $query .= " AND id NOT IN (" . implode(',', array_map('intval', $selectedBookIds)) . ")";
    }
} else {
    if (!empty($selectedBookIds)) {
        $query .= " WHERE id NOT IN (" . implode(',', array_map('intval', $selectedBookIds)) . ")";
    }
}
$query .= " ORDER BY id DESC";
$result = $conn->query($query);

// Display selected books first
foreach ($selectedBooks as $row) {
    $isChecked = in_array($row['id'], $selectedBookIds) ? 'checked' : '';
    echo "<tr>
        <td><input type='checkbox' class='selectRow' $isChecked></td>
        <td>{$row['id']}</td>
        <td>{$row['accession']}</td>
        <td>{$row['title']}</td>
        <td>{$row['preferred_title']}</td>
        <td>{$row['parallel_title']}</td>
        <td>";
    if (!empty($row['front_image'])) {
        echo "<img src='../inc/book-image/{$row['front_image']}' alt='Front Image' width='50'>";
    } else {
        echo "No Image";
    }
    echo "</td>
        <td>";
    if (!empty($row['back_image'])) {
        echo "<img src='../inc/book-image/{$row['back_image']}' alt='Back Image' width='50'>";
    } else {
        echo "No Image";
    }
    echo "</td>
        <td>{$row['height']}</td>
        <td>{$row['width']}</td>
        <td>{$row['total_pages']}</td>
        <td>{$row['call_number']}</td>
        <td>{$row['copy_number']}</td>
        <td>{$row['language']}</td>
        <td>{$row['shelf_location']}</td>
        <td>{$row['entered_by']}</td>
        <td>{$row['date_added']}</td>
        <td>{$row['status']}</td>
        <td>{$row['last_update']}</td>
        <td>{$row['series']}</td>
        <td>{$row['volume']}</td>
        <td>{$row['edition']}</td>
        <td>{$row['content_type']}</td>
        <td>{$row['media_type']}</td>
        <td>{$row['carrier_type']}</td>
        <td>{$row['ISBN']}</td>
        <td>";
    echo !empty($row['URL']) ? "<a href='{$row['URL']}' target='_blank'>View</a>" : "N/A";
    echo "</td>
    </tr>";
}

// Display other books
while ($row = $result->fetch_assoc()) {
    $isChecked = in_array($row['id'], $selectedBookIds) ? 'checked' : '';
    echo "<tr>
        <td><input type='checkbox' class='selectRow' $isChecked></td>
        <td>{$row['id']}</td>
        <td>{$row['accession']}</td>
        <td>{$row['title']}</td>
        <td>{$row['preferred_title']}</td>
        <td>{$row['parallel_title']}</td>
        <td>";
    if (!empty($row['front_image'])) {
        echo "<img src='../inc/book-image/{$row['front_image']}' alt='Front Image' width='50'>";
    } else {
        echo "No Image";
    }
    echo "</td>
        <td>";
    if (!empty($row['back_image'])) {
        echo "<img src='../inc/book-image/{$row['back_image']}' alt='Back Image' width='50'>";
    } else {
        echo "No Image";
    }
    echo "</td>
        <td>{$row['height']}</td>
        <td>{$row['width']}</td>
        <td>{$row['total_pages']}</td>
        <td>{$row['call_number']}</td>
        <td>{$row['copy_number']}</td>
        <td>{$row['language']}</td>
        <td>{$row['shelf_location']}</td>
        <td>{$row['entered_by']}</td>
        <td>{$row['date_added']}</td>
        <td>{$row['status']}</td>
        <td>{$row['last_update']}</td>
        <td>{$row['series']}</td>
        <td>{$row['volume']}</td>
        <td>{$row['edition']}</td>
        <td>{$row['content_type']}</td>
        <td>{$row['media_type']}</td>
        <td>{$row['carrier_type']}</td>
        <td>{$row['ISBN']}</td>
        <td>";
    echo !empty($row['URL']) ? "<a href='{$row['URL']}' target='_blank'>View</a>" : "N/A";
    echo "</td>
    </tr>";
}
?>