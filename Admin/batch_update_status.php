<?php
session_start();
include '../db.php';

// Check if user is logged in with appropriate role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
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

if (!isset($_POST['status']) || !in_array($_POST['status'], [0, 1, 2, 3])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

// Extract data
$userIds = array_map('intval', $_POST['user_ids']);
$status = intval($_POST['status']);

// Status names for logging
$statusNames = [
    0 => 'Inactive',
    1 => 'Active',
    2 => 'Banned',
    3 => 'Disabled'
];

// Begin transaction
$conn->begin_transaction();

try {
    // Prepare the update query
    $query = "UPDATE users SET status = ?, last_update = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    // Update status for each user
    $successCount = 0;
    
    foreach ($userIds as $userId) {
        $stmt->bind_param("ii", $status, $userId);
        if ($stmt->execute()) {
            $successCount++;
            
            // Log the status change in updates table
            $admin_id = $_SESSION['admin_employee_id'];
            $admin_role = $_SESSION['role'];
            $admin_fullname = $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname'];
            
            // Get user info for the log
            $user_query = "SELECT CONCAT(firstname, ' ', lastname) as fullname, usertype FROM users WHERE id = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param("i", $userId);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user_data = $user_result->fetch_assoc();
            
            if ($user_data) {
                $title = "User Status Updated";
                $message = "$admin_role $admin_fullname changed the status of {$user_data['usertype']} {$user_data['fullname']} to {$statusNames[$status]}";
                
                $log_query = "INSERT INTO updates (user_id, role, title, message, `update`) VALUES (?, ?, ?, ?, NOW())";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param("isss", $admin_id, $admin_role, $title, $message);
                $log_stmt->execute();
                $log_stmt->close();
            }
            
            $user_stmt->close();
        }
    }
    
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Return success
    if ($successCount > 0) {
        echo json_encode([
            'success' => true, 
            'message' => "Successfully updated status for $successCount user(s) to {$statusNames[$status]}"
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => "No users were updated"
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
