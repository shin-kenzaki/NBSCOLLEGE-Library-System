<?php
session_start();
include '../db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian'])) {
    $response = [
        'success' => false,
        'message' => 'Unauthorized access'
    ];
    echo json_encode($response);
    exit;
}

// Validate input
if (!isset($_POST['userId']) || empty($_POST['userId'])) {
    $response = [
        'success' => false,
        'message' => 'User ID is required'
    ];
    echo json_encode($response);
    exit;
}

$userId = intval($_POST['userId']);

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

// Update the user's password in the database
$sql = "UPDATE users SET password = ?, last_update = NOW() WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $hashedPassword, $userId);

if ($stmt->execute()) {
    // Record this action in updates table
    $admin_id = $_SESSION['admin_employee_id'];
    $admin_role = $_SESSION['role'];
    $admin_fullname = $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname'];
    
    // Get user information for the log
    $user_sql = "SELECT firstname, lastname, school_id FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $userId);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $user_fullname = $user_data['firstname'] . ' ' . $user_data['lastname'];
    $school_id = $user_data['school_id'];
    
    $update_title = "Password Reset";
    $update_message = "$admin_role $admin_fullname generated a new password for user $user_fullname";
    
    $update_sql = "INSERT INTO updates (user_id, role, title, message, `update`) VALUES (?, ?, ?, ?, NOW())";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("isss", $admin_id, $admin_role, $update_title, $update_message);
    $update_stmt->execute();
    
    $response = [
        'success' => true,
        'message' => 'Password updated successfully',
        'password' => $newPassword,
        'school_id' => $school_id,
        'user_name' => $user_fullname
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
