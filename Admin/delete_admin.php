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
    echo json_encode(['status' => 'error', 'message' => 'No admin ID specified']);
    exit();
}

$admin_id = $_GET['id'];

// Prevent admin from deleting their own account
if ($_SESSION['admin_id'] == $admin_id) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account']);
    exit();
}

// Retrieve admin details before deletion
$stmt = $conn->prepare("SELECT firstname, middle_init, lastname, role FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    echo json_encode(['status' => 'error', 'message' => 'Admin not found']);
    exit();
}

// Delete the admin
$stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);

if ($stmt->execute()) {
    // Insert update for deleted admin
    $logged_in_admin_id = $_SESSION['admin_employee_id'];
    $logged_in_admin_role = $_SESSION['role'];
    $logged_in_admin_fullname = $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname'];
    $deleted_admin_fullname = $admin['firstname'] . ' ' . ($admin['middle_init'] ? $admin['middle_init'] . ' ' : '') . $admin['lastname'];
    $update_title = "$logged_in_admin_role $logged_in_admin_fullname Deleted an Admin";
    $update_message = "$logged_in_admin_role $logged_in_admin_fullname Deleted {$admin['role']} $deleted_admin_fullname";
    $update_sql = "INSERT INTO updates (user_id, role, title, message, `update`) VALUES (?, ?, ?, ?, NOW())";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("isss", $logged_in_admin_id, $logged_in_admin_role, $update_title, $update_message);
    $update_stmt->execute();
    $update_stmt->close();

    echo json_encode(['status' => 'success', 'message' => 'Admin has been deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error deleting admin: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
