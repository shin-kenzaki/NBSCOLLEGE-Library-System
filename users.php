<?php
if ($request_method == "GET") {
    $sql = "SELECT * FROM school_users";
    $result = $conn->query($sql);
    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users);
}
?>
