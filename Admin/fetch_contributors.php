<?php
include '../db.php';

$query = "SELECT 
            GROUP_CONCAT(c.id ORDER BY c.id) as id_ranges,
            b.title as book_title,
            CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) as writer_name,
            c.role
          FROM contributors c
          JOIN books b ON c.book_id = b.id
          JOIN writers w ON c.writer_id = w.id
          GROUP BY b.title, w.id, c.role
          ORDER BY b.title";

$result = $conn->query($query);
$data = array();

while ($row = $result->fetch_assoc()) {
    // Format ID ranges
    $ids = explode(',', $row['id_ranges']);
    $ranges = [];
    $start = $ids[0];
    $prev = $ids[0];
    
    for ($i = 1; $i < count($ids); $i++) {
        if ($ids[$i] - $prev > 1) {
            $ranges[] = $start == $prev ? $start : "$start-$prev";
            $start = $ids[$i];
        }
        $prev = $ids[$i];
    }
    $ranges[] = $start == $prev ? $start : "$start-$prev";
    
    $row['id_ranges'] = implode(', ', $ranges);
    $data[] = $row;
}

echo json_encode(array("data" => $data));
