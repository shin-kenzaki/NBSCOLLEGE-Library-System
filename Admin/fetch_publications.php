<?php
include '../db.php';

$query = "SELECT 
            GROUP_CONCAT(p.id ORDER BY p.id) as id_ranges,
            b.title as book_title,
            pb.publisher,
            pb.place,
            p.publish_date
          FROM publications p 
          JOIN books b ON p.book_id = b.id 
          JOIN publishers pb ON p.publisher_id = pb.id
          GROUP BY b.title, pb.id, p.publish_date
          ORDER BY b.title, p.publish_date";

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
    
    $row['id'] = implode(', ', $ranges);
    $data[] = $row;
}

echo json_encode(array("data" => $data));
