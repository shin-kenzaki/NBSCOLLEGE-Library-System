<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No user ID specified']);
    exit();
}

$user_id = $_GET['id'];

// Check if user has any active borrowings
$check_borrowings = $conn->prepare("SELECT COUNT(*) as active_borrowings FROM borrowings WHERE user_id = ? AND status = 'borrowed'");
$check_borrowings->bind_param("i", $user_id);
$check_borrowings->execute();
$result = $check_borrowings->get_result()->fetch_assoc();

if ($result['active_borrowings'] > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Cannot delete user with active borrowings']);
    exit();
}

// Retrieve user details before deletion
$stmt = $conn->prepare("SELECT firstname, middle_init, lastname, usertype FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit();
}

// Delete the user
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // Insert update for deleted user
    $logged_in_admin_id = $_SESSION['admin_employee_id'];
    $logged_in_admin_role = $_SESSION['role'];
    $logged_in_admin_fullname = $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname'];
    $deleted_user_fullname = $user['firstname'] . ' ' . ($user['middle_init'] ? $user['middle_init'] . ' ' : '') . $user['lastname'];
    $update_title = "$logged_in_admin_role $logged_in_admin_fullname Deleted a User";
    $update_message = "$logged_in_admin_role $logged_in_admin_fullname Deleted {$user['usertype']} $deleted_user_fullname";
    $update_sql = "INSERT INTO updates (user_id, role, title, message, `update`) VALUES (?, ?, ?, ?, NOW())";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("isss", $logged_in_admin_id, $logged_in_admin_role, $update_title, $update_message);
    $update_stmt->execute();
    $update_stmt->close();

    echo json_encode(['status' => 'success', 'message' => 'User has been deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error deleting user: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
