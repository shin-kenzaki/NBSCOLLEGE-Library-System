<?php
session_start();
require_once('../../db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Get all admins with masked IDs
$admin_query = "SELECT id, 
                      firstname,
                      lastname,
                      CONCAT(firstname, ' ', lastname) as name, 
                      role as usertype, 
                      image,
                      CONCAT('STAFF-', LPAD(id, 4, '0')) as masked_id
               FROM admins 
               WHERE status != '0' OR status IS NULL 
               ORDER BY role, firstname, lastname";
$admin_result = $conn->query($admin_query);
$users = [];

while ($row = $admin_result->fetch_assoc()) {
    $users[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'firstname' => $row['firstname'],
        'lastname' => $row['lastname'],
        'role' => $row['usertype'], // Use usertype consistently
        'display_id' => $row['masked_id'],
        'image' => $row['image'] ?? 'inc/img/default-avatar.jpg',
        'unique_key' => $row['id'] . '_' . $row['usertype'] . '_' . $row['masked_id']
    ];
}

// Get all other users with masked IDs
$user_query = "SELECT id, 
                      firstname,
                      lastname,
                      CONCAT(firstname, ' ', lastname) as name, 
                      usertype, 
                      user_image as image,
                      CONCAT('STUDENT-', LPAD(id, 4, '0')) as masked_id
               FROM users 
               WHERE id != ? AND (status != '0' OR status IS NULL)
               ORDER BY firstname, lastname";
$stmt = $conn->prepare($user_query);
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'firstname' => $row['firstname'],
        'lastname' => $row['lastname'],
        'role' => $row['usertype'], // Use usertype consistently
        'display_id' => $row['masked_id'], // Use masked ID instead of school_id
        'image' => $row['image'] ?? 'inc/img/default-avatar.jpg',
        'unique_key' => $row['id'] . '_' . $row['usertype'] . '_' . $row['masked_id']
    ];
}

echo json_encode($users);
