<?php
session_start();
include '../db.php';

// Check if user is logged in with appropriate role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate input
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['user_ids']) || !is_array($_POST['user_ids']) || empty($_POST['user_ids'])) {
    echo json_encode(['success' => false, 'message' => 'No users selected']);
    exit;
}

// Extract data
$userIds = array_map('intval', $_POST['user_ids']);

// Begin transaction
$conn->begin_transaction();

try {
    // First check if any users have active borrowings
    $check_query = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) as fullname 
                   FROM users u 
                   JOIN borrowings b ON u.id = b.user_id 
                   WHERE u.id IN (" . implode(',', $userIds) . ") 
                   AND b.status IN ('Borrowed', 'Overdue', 'Damaged', 'Lost') 
                   GROUP BY u.id";
                   
    $check_result = $conn->query($check_query);
    
    if ($check_result->num_rows > 0) {
        $users_with_borrowings = [];
        while ($row = $check_result->fetch_assoc()) {
            $users_with_borrowings[] = $row['fullname'];
        }
        
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete the following users with active borrowings: " . implode(', ', $users_with_borrowings)
        ]);
        exit;
    }
    
    // Gather user information before deletion (for logging)
    $user_info_query = "SELECT id, firstname, middle_init, lastname, usertype FROM users WHERE id IN (" . implode(',', $userIds) . ")";
    $user_info_result = $conn->query($user_info_query);
    $users_info = [];
    
    while ($user = $user_info_result->fetch_assoc()) {
        $users_info[$user['id']] = [
            'fullname' => $user['firstname'] . ' ' . ($user['middle_init'] ? $user['middle_init'] . ' ' : '') . $user['lastname'],
            'usertype' => $user['usertype']
        ];
    }
    
    // Prepare the delete query
    $delete_query = "DELETE FROM users WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    
    // Delete each user
    $successCount = 0;
    
    foreach ($userIds as $userId) {
        $delete_stmt->bind_param("i", $userId);
        if ($delete_stmt->execute()) {
            $successCount++;
            
            // Log the deletion
            if (isset($users_info[$userId])) {
                $admin_id = $_SESSION['admin_employee_id'];
                $admin_role = $_SESSION['role'];
                $admin_fullname = $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname'];
                $user_fullname = $users_info[$userId]['fullname'];
                $user_type = $users_info[$userId]['usertype'];
                
                $title = "$admin_role $admin_fullname Deleted a User";
                $message = "$admin_role $admin_fullname Deleted $user_type $user_fullname";
                
                $log_query = "INSERT INTO updates (user_id, role, title, message, `update`) VALUES (?, ?, ?, ?, NOW())";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param("isss", $admin_id, $admin_role, $title, $message);
                $log_stmt->execute();
                $log_stmt->close();
            }
        }
    }
    
    $delete_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Return success
    if ($successCount > 0) {
        echo json_encode([
            'success' => true, 
            'message' => "Successfully deleted $successCount user(s)"
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => "No users were deleted"
        ]);
    }
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode([
        'success' => false, 
        'message' => "Error: " . $e->getMessage()
    ]);
}

$conn->close();
?>
