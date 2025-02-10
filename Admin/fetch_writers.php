<?php
include '../db.php'; // Database connection
session_start();

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$selectedWriterIds = isset($_SESSION['selectedWriterIds']) ? $_SESSION['selectedWriterIds'] : [];

// Fetch selected writers first
$selectedWriters = [];
if (!empty($selectedWriterIds)) {
    $selectedWritersQuery = "SELECT * FROM writers WHERE id IN (" . implode(',', array_map('intval', $selectedWriterIds)) . ")";
    $selectedWritersResult = $conn->query($selectedWritersQuery);
    while ($row = $selectedWritersResult->fetch_assoc()) {
        $selectedWriters[] = $row;
    }
}

// Fetch other writers
$query = "SELECT * FROM writers";
if (!empty($searchQuery)) {
    $query .= " WHERE (firstname LIKE '%$searchQuery%' OR middle_init LIKE '%$searchQuery%' OR lastname LIKE '%$searchQuery%')";
    if (!empty($selectedWriterIds)) {
        $query .= " AND id NOT IN (" . implode(',', array_map('intval', $selectedWriterIds)) . ")";
    }
} else {
    if (!empty($selectedWriterIds)) {
        $query .= " WHERE id NOT IN (" . implode(',', array_map('intval', $selectedWriterIds)) . ")";
    }
}
$query .= " ORDER BY id DESC";
$result = $conn->query($query);

// Display selected writers first
foreach ($selectedWriters as $row) {
    $isChecked = in_array($row['id'], $selectedWriterIds) ? 'checked' : '';
    echo "<tr>
        <td><input type='checkbox' class='selectWriter' name='writer_ids[]' value='{$row['id']}' $isChecked></td>
        <td>{$row['id']}</td>
        <td>{$row['firstname']}</td>
        <td>{$row['middle_init']}</td>
        <td>{$row['lastname']}</td>
        <td>
            <select name='roles[]' class='form-control'>
                <option value='Author'>Author</option>
                <option value='Co-Author'>Co-Author</option>
                <option value='Editor'>Editor</option>
            </select>
        </td>
    </tr>";
}

// Display other writers
while ($row = $result->fetch_assoc()) {
    $isChecked = in_array($row['id'], $selectedWriterIds) ? 'checked' : '';
    echo "<tr>
        <td><input type='checkbox' class='selectWriter' name='writer_ids[]' value='{$row['id']}' $isChecked></td>
        <td>{$row['id']}</td>
        <td>{$row['firstname']}</td>
        <td>{$row['middle_init']}</td>
        <td>{$row['lastname']}</td>
        <td>
            <select name='roles[]' class='form-control'>
                <option value='Author'>Author</option>
                <option value='Co-Author'>Co-Author</option>
                <option value='Editor'>Editor</option>
            </select>
        </td>
    </tr>";
}
?>