<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo "<script>
        alert('No user ID specified');
        window.location.href='users_list.php';
    </script>";
    exit();
}

$user_id = $_GET['id'];

// Check if user has any active borrowings
$check_borrowings = $conn->prepare("SELECT COUNT(*) as active_borrowings FROM borrowings WHERE user_id = ? AND status = 'borrowed'");
$check_borrowings->bind_param("i", $user_id);
$check_borrowings->execute();
$result = $check_borrowings->get_result()->fetch_assoc();

if ($result['active_borrowings'] > 0) {
    echo "<script>
        alert('Cannot delete user with active borrowings');
        window.location.href='users_list.php';
    </script>";
    exit();
}

// Delete the user
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo "<script>
        alert('User has been deleted successfully');
        window.location.href='users_list.php';
    </script>";
} else {
    echo "<script>
        alert('Error deleting user: " . $conn->error . "');
        window.location.href='users_list.php';
    </script>";
}

$stmt->close();
$conn->close();
?>
