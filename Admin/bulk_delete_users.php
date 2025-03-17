<?php
session_start();
include '../db.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userIds'])) {
    $userIds = array_map('intval', $_POST['userIds']);
    
    if (empty($userIds)) {
        die(json_encode(['success' => false, 'message' => 'No users selected']));
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Create placeholders for the IN clause
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        
        // Retrieve user details before deletion
        $stmt = $conn->prepare("SELECT id, firstname, middle_init, lastname, usertype FROM users WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Delete users
        $stmt = $conn->prepare("DELETE FROM users WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            
            // Insert updates for each deleted user
            $logged_in_admin_id = $_SESSION['admin_employee_id'];
            $logged_in_admin_role = $_SESSION['role'];
            $logged_in_admin_fullname = $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname'];
            $update_sql = "INSERT INTO updates (user_id, role, title, message, `update`) VALUES (?, ?, ?, ?, NOW())";
            $update_stmt = $conn->prepare($update_sql);

            foreach ($users as $user) {
                $deleted_user_fullname = $user['firstname'] . ' ' . ($user['middle_init'] ? $user['middle_init'] . ' ' : '') . $user['lastname'];
                $update_title = "$logged_in_admin_role $logged_in_admin_fullname Deleted a User";
                $update_message = "$logged_in_admin_role $logged_in_admin_fullname Deleted {$user['usertype']} $deleted_user_fullname";
                $update_stmt->bind_param("isss", $logged_in_admin_id, $logged_in_admin_role, $update_title, $update_message);
                $update_stmt->execute();
            }

            $update_stmt->close();
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => "$affected user(s) deleted successfully"
            ]);
        } else {
            throw new Exception("Failed to delete users");
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}

$conn->close();
?>
