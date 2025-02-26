<?php
session_start();
require_once('../../db.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit();
}

$current_admin_id = $_SESSION['admin_id'];

// Get all users with masked IDs
$user_query = "SELECT id, 
                      firstname,
                      lastname,
                      CONCAT(firstname, ' ', lastname) as name, 
                      usertype, 
                      user_image as image,
                      CONCAT('USER-', LPAD(id, 4, '0')) as masked_id
               FROM users 
               WHERE status != '0' OR status IS NULL
               ORDER BY firstname, lastname";
$result = $conn->query($user_query);
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'role' => $row['usertype'],
        'display_id' => $row['masked_id'],
        'image' => $row['image'] ?? 'inc/img/default-avatar.jpg',
        'unique_key' => $row['id'] . '_' . $row['usertype'] . '_' . $row['masked_id']
    ];
}

// Get other admins except current admin
$admin_query = "SELECT id, 
                      firstname,
                      lastname,
                      CONCAT(firstname, ' ', lastname) as name, 
                      role as usertype, 
                      image,
                      CONCAT('STAFF-', LPAD(id, 4, '0')) as masked_id
               FROM admins 
               WHERE id != ? AND (status != '0' OR status IS NULL)
               ORDER BY role, firstname, lastname";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param('i', $current_admin_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'role' => $row['usertype'],
        'display_id' => $row['masked_id'],
        'image' => $row['image'] ?? 'inc/img/default-avatar.jpg',
        'unique_key' => $row['id'] . '_' . $row['usertype'] . '_' . $row['masked_id']
    ];
}

echo json_encode($users);
