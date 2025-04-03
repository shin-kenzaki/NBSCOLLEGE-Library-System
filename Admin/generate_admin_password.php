<?php
session_start();
include '../db.php';

// Check if user is logged in with correct privileges
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'Admin') {
    $response = [
        'success' => false,
        'message' => 'Unauthorized access. Only Admins can reset admin passwords.'
    ];
    echo json_encode($response);
    exit;
}

// Validate input
if (!isset($_POST['adminId']) || empty($_POST['adminId'])) {
    $response = [
        'success' => false,
        'message' => 'Admin ID is required'
    ];
    echo json_encode($response);
    exit;
}

$adminId = intval($_POST['adminId']);

// Check if trying to reset own password or another admin's password
if ($adminId == $_SESSION['admin_id']) {
    $response = [
        'success' => false,
        'message' => 'You cannot reset your own password using this method. Please use the profile settings.'
    ];
    echo json_encode($response);
    exit;
}

// Generate a new password
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

$newPassword = generatePassword();
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update the admin's password in the database
$sql = "UPDATE admins SET password = ?, last_update = NOW() WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $hashedPassword, $adminId);

if ($stmt->execute()) {
    // Record this action in updates table
    $admin_id = $_SESSION['admin_employee_id'];
    $admin_role = $_SESSION['role'];
    $admin_fullname = $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname'];
    
    // Get admin information for the log
    $target_sql = "SELECT firstname, lastname, role, employee_id FROM admins WHERE id = ?";
    $target_stmt = $conn->prepare($target_sql);
    $target_stmt->bind_param("i", $adminId);
    $target_stmt->execute();
    $target_result = $target_stmt->get_result();
    $target_data = $target_result->fetch_assoc();
    $target_fullname = $target_data['firstname'] . ' ' . $target_data['lastname'];
    $target_role = $target_data['role'];
    $employee_id = $target_data['employee_id'];
    
    $update_title = "Admin Password Reset";
    $update_message = "$admin_role $admin_fullname generated a new password for $target_role $target_fullname";
    
    $update_sql = "INSERT INTO updates (user_id, role, title, message, `update`) VALUES (?, ?, ?, ?, NOW())";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("isss", $admin_id, $admin_role, $update_title, $update_message);
    $update_stmt->execute();
    
    $response = [
        'success' => true,
        'message' => 'Password updated successfully',
        'password' => $newPassword,
        'employee_id' => $employee_id,
        'admin_name' => $target_fullname
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Failed to update password: ' . $stmt->error
    ];
}

$stmt->close();
echo json_encode($response);
?>
