<?php
session_start();
require_once '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables
$message = '';
$status = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Initialize selected users array in session if not exists
if (!isset($_SESSION['selectedUserIds'])) {
    $_SESSION['selectedUserIds'] = [];
}

// Handle AJAX request to update selected users
if (isset($_POST['action']) && $_POST['action'] == 'updateSelectedUsers') {
    $_SESSION['selectedUserIds'] = isset($_POST['selectedIds']) ? $_POST['selectedIds'] : [];
    echo json_encode(['success' => true, 'count' => count($_SESSION['selectedUserIds'])]);
    exit;
}

// Handle AJAX bulk delete request
if (isset($_POST['action']) && $_POST['action'] == 'bulkDelete' && isset($_POST['ids'])) {
    $selectedIds = $_POST['ids'];
    $response = ['success' => false, 'message' => 'No users selected for deletion.'];
    
    if (!empty($selectedIds)) {
        // Start transaction to ensure data integrity
        $conn->begin_transaction();
        try {
            $deleteCount = 0;
            foreach ($selectedIds as $id) {
                $id = (int)$id; // Ensure it's an integer
                
                // Delete the user
                $deleteUserSql = "DELETE FROM physical_login_users WHERE id = $id";
                if ($conn->query($deleteUserSql)) {
                    $deleteCount++;
                }
            }
            
            // Commit the transaction
            $conn->commit();
            
            if ($deleteCount > 0) {
                $response = [
                    'success' => true, 
                    'message' => "$deleteCount user(s) deleted successfully.",
                    'deletedIds' => $selectedIds
                ];
                
                // Clear the selected IDs from session
                $_SESSION['selectedUserIds'] = array_values(array_diff($_SESSION['selectedUserIds'], $selectedIds));
            } else {
                $response = ['success' => false, 'message' => "Failed to delete users."];
            }
        } catch (Exception $e) {
            // An error occurred, rollback the transaction
            $conn->rollback();
            $response = ['success' => false, 'message' => "Error deleting users: " . $e->getMessage()];
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle AJAX single user delete request
if (isset($_POST['action']) && $_POST['action'] == 'deleteUser' && isset($_POST['userId'])) {
    $userId = (int)$_POST['userId'];
    $response = ['success' => false, 'message' => 'Invalid user ID.'];
    
    if ($userId > 0) {
        // Delete the user
        $delete_sql = "DELETE FROM physical_login_users WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $userId);
        
        if ($delete_stmt->execute()) {
            $response = [
                'success' => true, 
                'message' => "User deleted successfully.",
                'deletedId' => $userId
            ];
            
            // Remove from session if it exists
            if (in_array($userId, $_SESSION['selectedUserIds'] ?? [])) {
                $_SESSION['selectedUserIds'] = array_values(array_diff($_SESSION['selectedUserIds'], [$userId]));
            }
        } else {
            $response = ['success' => false, 'message' => "Error deleting user: " . $conn->error];
        }
        $delete_stmt->close();
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle bulk action requests
if (isset($_POST['bulk_action']) && isset($_POST['selected_ids'])) {
    $selectedIds = $_POST['selected_ids'];
    $action = $_POST['bulk_action'];
    
    if (empty($selectedIds)) {
        $_SESSION['error_message'] = "No users selected for action.";
    } else {
        // Process bulk actions
        switch ($action) {
            case 'delete':
                // Start transaction to ensure data integrity
                $conn->begin_transaction();
                try {
                    $deleteCount = 0;
                    foreach ($selectedIds as $id) {
                        $id = (int)$id; // Ensure it's an integer
                        
                        // Delete the user
                        $deleteUserSql = "DELETE FROM physical_login_users WHERE id = $id";
                        if ($conn->query($deleteUserSql)) {
                            $deleteCount++;
                        }
                    }
                    
                    // Commit the transaction
                    $conn->commit();
                    
                    if ($deleteCount > 0) {
                        $_SESSION['success_message'] = "$deleteCount user(s) deleted successfully.";
                    } else {
                        $_SESSION['error_message'] = "Failed to delete users.";
                    }
                } catch (Exception $e) {
                    // An error occurred, rollback the transaction
                    $conn->rollback();
                    $_SESSION['error_message'] = "Error deleting users: " . $e->getMessage();
                }
                break;
                
            // Add more bulk actions here if needed
        }
    }
    
    // Clear selected IDs after processing
    $_SESSION['selectedUserIds'] = [];
    
    // Redirect to refresh the page
    header("Location: manage_library_users.php");
    exit;
}

// Process add/edit user form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user']) || isset($_POST['update_user'])) {
        $student_number = $_POST['student_number'];
        $course = $_POST['course'];
        $year = $_POST['year'];
        $firstname = $_POST['firstname'];
        $middle_init = $_POST['middle_init'];
        $lastname = $_POST['lastname'];
        $gender = $_POST['gender'];
        
        // Validate form data
        $errors = [];
        
        if (empty($student_number) || !is_numeric($student_number)) {
            $errors[] = "Student number must be a valid number";
        }
        
        if (empty($firstname)) {
            $errors[] = "First name is required";
        }
        
        if (empty($lastname)) {
            $errors[] = "Last name is required";
        }
        
        // If no errors, proceed with add/update
        if (empty($errors)) {
            if (isset($_POST['add_user'])) {
                // Check if student number already exists
                $check_sql = "SELECT id FROM physical_login_users WHERE student_number = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $student_number);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $status = "error";
                    $message = "Student number already exists";
                } else {
                    // Insert new user
                    $insert_sql = "INSERT INTO physical_login_users (student_number, course, year, firstname, middle_init, lastname, gender) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("issssss", $student_number, $course, $year, $firstname, $middle_init, $lastname, $gender);
                    
                    if ($insert_stmt->execute()) {
                        $status = "success";
                        $message = "User added successfully";
                        
                        // Reset form fields
                        $student_number = $course = $year = $firstname = $middle_init = $lastname = $gender = '';
                    } else {
                        $status = "error";
                        $message = "Error adding user: " . $conn->error;
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            } else if (isset($_POST['update_user'])) {
                $user_id = $_POST['user_id'];
                
                // Update existing user
                $update_sql = "UPDATE physical_login_users SET 
                              student_number = ?, course = ?, year = ?, firstname = ?, 
                              middle_init = ?, lastname = ?, gender = ? 
                              WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("issssssi", $student_number, $course, $year, $firstname, $middle_init, $lastname, $gender, $user_id);
                
                if ($update_stmt->execute()) {
                    $status = "success";
                    $message = "User updated successfully";
                    
                    // Redirect to list view
                    header("Location: manage_library_users.php?status=success&message=" . urlencode("User updated successfully"));
                    exit();
                } else {
                    $status = "error";
                    $message = "Error updating user: " . $conn->error;
                }
                $update_stmt->close();
            }
        } else {
            $status = "error";
            $message = implode("<br>", $errors);
        }
    } else if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        // Delete user
        $delete_sql = "DELETE FROM physical_login_users WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            $status = "success";
            $message = "User deleted successfully";
            header("Location: manage_library_users.php?status=success&message=" . urlencode("User deleted successfully"));
            exit();
        } else {
            $status = "error";
            $message = "Error deleting user: " . $conn->error;
        }
        $delete_stmt->close();
    }
}

// Get user data for edit
if ($action === 'edit' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    $user_sql = "SELECT * FROM physical_login_users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        
        // Extract user data
        $student_number = $user_data['student_number'];
        $course = $user_data['course'];
        $year = $user_data['year'];
        $firstname = $user_data['firstname'];
        $middle_init = $user_data['middle_init'];
        $lastname = $user_data['lastname'];
        $gender = $user_data['gender'];
    } else {
        $status = "error";
        $message = "User not found";
        $action = 'list';
    }
    $user_stmt->close();
}

// Handle GET messages
if (isset($_GET['status']) && isset($_GET['message'])) {
    $status = $_GET['status'];
    $message = $_GET['message'];
}

// Get selected user IDs from session if they exist
$selectedUserIds = isset($_SESSION['selectedUserIds']) ? $_SESSION['selectedUserIds'] : [];

// Get users list for display
$users = [];
if ($action === 'list') {
    // Define sort parameters with defaults
    $sort_field = isset($_GET['sort']) ? $_GET['sort'] : 'id';
    $sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
    
    // Validate sort parameters to prevent SQL injection
    $allowed_fields = ['id', 'student_number', 'firstname', 'lastname', 'course', 'year'];
    if (!in_array($sort_field, $allowed_fields)) {
        $sort_field = 'id';
    }
    if ($sort_order !== 'ASC' && $sort_order !== 'DESC') {
        $sort_order = 'DESC';
    }
    
    // Get search parameter
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Build the SQL query
    $sql = "SELECT * FROM physical_login_users";
    
    // Add search condition if search parameter exists
    if (!empty($search)) {
        $search_param = "%$search%";
        $sql .= " WHERE 
            student_number LIKE ? OR 
            firstname LIKE ? OR 
            lastname LIKE ? OR 
            course LIKE ? OR
            CONCAT(firstname, ' ', lastname) LIKE ?";
    }
    
    // Add ordering for selected IDs to appear first, then by the selected sort field
    $sql .= " ORDER BY CASE WHEN id IN (" . 
        (!empty($selectedUserIds) ? implode(',', array_map('intval', $selectedUserIds)) : "0") . 
        ") THEN 0 ELSE 1 END, $sort_field $sort_order";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    
    if (!empty($search)) {
        $stmt->bind_param("sssss", $search_param, $search_param, $search_param, $search_param, $search_param);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}

// Get total users count
$count_sql = "SELECT COUNT(*) as total FROM physical_login_users";
$count_result = $conn->query($count_sql);
$count_data = $count_result->fetch_assoc();
$total_users = $count_data['total'];

// Display success or error message from session if available
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $status = 'success';
    unset($_SESSION['success_message']);
} elseif (isset($_SESSION['error_message'])) {
    $message = $_SESSION['error_message'];
    $status = 'error';
    unset($_SESSION['error_message']);
}

include '../admin/inc/header.php';
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <?php echo $action === 'add' ? 'Add New Library User' : ($action === 'edit' ? 'Edit Library User' : 'Manage Library Users'); ?>
            </h1>
            <div>
                <?php if ($action === 'list'): ?>
                    <a href="import_users.php" class="btn btn-info btn-sm shadow-sm mr-2">
                        <i class="fas fa-file-import fa-sm"></i> Import Users
                    </a>
                    <a href="?action=add" class="btn btn-primary btn-sm shadow-sm">
                        <i class="fas fa-user-plus fa-sm"></i> Add New User
                    </a>
                <?php else: ?>
                    <a href="manage_library_users.php" class="btn btn-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left fa-sm"></i> Back to List
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alert container for AJAX notifications -->
        <div id="alertContainer"></div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $status === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit User Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php echo $action === 'add' ? 'New User Details' : 'Edit User Details'; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="user_id" value="<?php echo $user_data['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="student_number">Student/Employee Number*</label>
                                    <input type="text" class="form-control" id="student_number" name="student_number" 
                                           value="<?php echo isset($student_number) ? htmlspecialchars($student_number) : ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select class="form-control" id="gender" name="gender">
                                        <option value="Male" <?php echo (isset($gender) && $gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($gender) && $gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstname">First Name*</label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" 
                                           value="<?php echo isset($firstname) ? htmlspecialchars($firstname) : ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="middle_init">Middle Initial</label>
                                    <input type="text" class="form-control" id="middle_init" name="middle_init" 
                                           value="<?php echo isset($middle_init) ? htmlspecialchars($middle_init) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastname">Last Name*</label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" 
                                           value="<?php echo isset($lastname) ? htmlspecialchars($lastname) : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="course">Course/Department</label>
                                    <select class="form-control" id="course" name="course">
                                        <option value="" <?php echo !isset($course) || empty($course) ? 'selected' : ''; ?>>Select Course</option>
                                        <option value="Computer Science" <?php echo (isset($course) && $course === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                                        <option value="Accountancy" <?php echo (isset($course) && $course === 'Accountancy') ? 'selected' : ''; ?>>Accountancy</option>
                                        <option value="Accounting Information System" <?php echo (isset($course) && $course === 'Accounting Information System') ? 'selected' : ''; ?>>Accounting Information System</option>
                                        <option value="Entrepreneurship" <?php echo (isset($course) && $course === 'Entrepreneurship') ? 'selected' : ''; ?>>Entrepreneurship</option>
                                        <option value="Tourism Management" <?php echo (isset($course) && $course === 'Tourism Management') ? 'selected' : ''; ?>>Tourism Management</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="year">Year Level</label>
                                    <select class="form-control" id="year" name="year">
                                        <option value="" <?php echo !isset($year) || empty($year) ? 'selected' : ''; ?>>Select Year</option>
                                        <option value="1st Year" <?php echo (isset($year) && $year === '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2nd Year" <?php echo (isset($year) && $year === '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3rd Year" <?php echo (isset($year) && $year === '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4th Year" <?php echo (isset($year) && $year === '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                        <option value="Faculty" <?php echo (isset($year) && $year === 'Faculty') ? 'selected' : ''; ?>>Faculty</option>
                                        <option value="Staff" <?php echo (isset($year) && $year === 'Staff') ? 'selected' : ''; ?>>Staff</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" name="<?php echo $action === 'add' ? 'add_user' : 'update_user'; ?>" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> <?php echo $action === 'add' ? 'Add User' : 'Update User'; ?>
                            </button>
                            <a href="manage_library_users.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Users List -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Library Users</h6>
                    <div class="d-flex align-items-center">
                        <span class="mr-3 total-users-display">
                            Total Users: <span id="totalUsersCount"><?php echo number_format($total_users); ?></span>
                        </span>
                        <button id="deleteSelectedBtn" class="btn btn-danger btn-sm mr-2 bulk-delete-btn" disabled>
                            <i class="fas fa-trash"></i>
                            <span>Delete Selected</span>
                            <span class="badge badge-light ml-1">0</span>
                        </button>
                    </div>
                </div>
                <div class="card-body px-0">
                    <div class="table-responsive px-3">
                        <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th style="cursor: pointer; text-align: center;" id="checkboxHeader">
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th>
                                        <a href="?sort=id&order=<?php echo $sort_field === 'id' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            ID
                                            <?php if ($sort_field === 'id'): ?>
                                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?sort=student_number&order=<?php echo $sort_field === 'student_number' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            Student/Employee #
                                            <?php if ($sort_field === 'student_number'): ?>
                                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?sort=firstname&order=<?php echo $sort_field === 'firstname' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            Name
                                            <?php if ($sort_field === 'firstname'): ?>
                                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?sort=course&order=<?php echo $sort_field === 'course' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            Course/Dept
                                            <?php if ($sort_field === 'course'): ?>
                                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?sort=year&order=<?php echo $sort_field === 'year' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            Year
                                            <?php if ($sort_field === 'year'): ?>
                                                <i class="fas fa-sort-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>Gender</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr id="user-row-<?php echo $user['id']; ?>" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>">
                                            <td style="text-align: center;">
                                                <input type="checkbox" class="row-checkbox" value="<?php echo $user['id']; ?>">
                                            </td>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['student_number']); ?></td>
                                            <td>
                                                <?php 
                                                echo htmlspecialchars($user['firstname']) . ' ';
                                                if (!empty($user['middle_init'])) {
                                                    echo htmlspecialchars($user['middle_init']) . '. ';
                                                }
                                                echo htmlspecialchars($user['lastname']);
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['course']); ?></td>
                                            <td><?php echo htmlspecialchars($user['year']); ?></td>
                                            <td><?php echo htmlspecialchars($user['gender']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Delete User Modal -->
            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete <span id="deleteUserName"></span>?
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
    <!-- /.container-fluid -->
</div>
<!-- End of Main Content -->

<!-- Context Menu -->
<div class="context-menu" id="contextMenu">
    <ul class="list-group">
        <li class="list-group-item context-menu-item" data-action="edit"><i class="fas fa-edit mr-2"></i>Edit User</li>
        <li class="list-group-item context-menu-item" data-action="delete"><i class="fas fa-trash-alt mr-2"></i>Delete User</li>
    </ul>
</div>

<?php include '../admin/inc/footer.php'; ?>

<style>
    /* Add checkbox cell styles */
    .checkbox-cell {
        cursor: pointer;
        text-align: center;
        vertical-align: middle;
        width: 50px !important; /* Fixed width for uniformity */
    }
    .checkbox-cell:hover {
        background-color: rgba(0, 123, 255, 0.1);
    }
    .checkbox-cell input[type="checkbox"] {
        margin: 0 auto;
        display: block;
    }
    
    /* Table responsive styles */
    .table-responsive {
        width: 100%;
        margin-bottom: 1rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Ensure minimum width for table columns */
    #usersTable th,
    #usersTable td {
        min-width: 100px;
        white-space: nowrap;
    }
    
    /* Make the table stretch full width */
    #usersTable {
        width: 100% !important;
    }
    
    /* Prevent text wrapping in cells */
    .table td, .table th {
        white-space: nowrap;
    }
    
    /* Add styles for user stats */
    .total-users-display {
        font-size: 0.9rem;
        color: #4e73df;
        font-weight: 600;
        margin-left: 10px;
    }

    /* Add button badge styles */
    .bulk-delete-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .bulk-delete-btn .badge {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    /* Success animation for deletions */
    @keyframes fadeOut {
        0% { opacity: 1; }
        50% { opacity: 0.5; background-color: rgba(220, 53, 69, 0.1); }
        100% { opacity: 0; height: 0; padding: 0; margin: 0; border: 0; }
    }

    .deleting-row {
        animation: fadeOut 0.8s ease forwards;
    }
    
    /* Hide sorting icons in checkbox column */
    #usersTable th:first-child .sorting_asc,
    #usersTable th:first-child .sorting_desc,
    #usersTable th:first-child .sorting {
        background-image: none !important;
    }
    
    /* Context menu styling */
    .context-menu {
        position: absolute;
        display: none;
        z-index: 1000;
        min-width: 180px;
        padding: 0;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        border-radius: 0.35rem;
        overflow: hidden;
    }
    
    .context-menu .list-group {
        margin-bottom: 0;
    }
    
    .context-menu-item {
        cursor: pointer;
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        transition: background-color 0.2s;
    }
    
    .context-menu-item:hover {
        background-color: #f8f9fc;
        color: #4e73df;
    }
    
    .context-menu-item i {
        width: 20px;
        text-align: center;
    }
    
    /* Highlight selected row */
    #usersTable tbody tr.context-menu-active {
        background-color: rgba(78, 115, 223, 0.1);
    }
</style>

<script>
$(document).ready(function() {
    var table = $('#usersTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
        "responsive": false,
        "scrollX": true,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search...",
            "emptyTable": "No users found",
            "zeroRecords": "No matching users found"
        },
        "columnDefs": [
            { 
                "orderable": false, 
                "searchable": false,
                "targets": 0,
                "className": "checkbox-cell",
                "width": "30px"
            }
        ],
        "order": [[1, 'desc']], // Set initial sort to second column (ID) instead of the checkbox column
        "initComplete": function() {
            $('#usersTable_filter input').addClass('form-control form-control-sm');
            $('#usersTable_filter').addClass('d-flex align-items-center');
            $('#usersTable_filter label').append('<i class="fas fa-search ml-2"></i>');
            $('.dataTables_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
        }
    });

    // Remove search button functionality which is no longer needed
    var selectedIds = <?php echo json_encode($_SESSION['selectedUserIds'] ?? []); ?>;
    // Track selected rows
    // Make the entire checkbox cell clickable
    $(document).on('click', 'td:first-child', function(e) {
        // Prevent triggering if clicking directly on the checkbox
        if (e.target.type !== 'checkbox') {
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            checkbox.trigger('change'); // Trigger change event
        }
    });
    
    // Delete confirmation for single user
    $(document).on('click', '.delete-user-btn', function() {
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        
        Swal.fire({
            title: 'Delete User?',
            html: `Are you sure you want to delete <strong>${userName}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteUser(userId);
            }
        });
    });
    
    // Function to delete a single user via AJAX
    function deleteUser(userId) {
        $.ajax({
            url: 'manage_library_users.php',
            type: 'POST',
            data: {
                action: 'deleteUser',
                userId: userId
            },
            dataType: 'json',
            success: function(response) {
                $('#deleteModal').modal('hide');
                
                if (response.success) {
                    // Animate and remove the row
                    var row = $('#user-row-' + response.deletedId);
                    row.addClass('deleting-row');
                    
                    setTimeout(function() {
                        // Remove from DataTable
                        table.row(row).remove().draw(false);
                        
                        // Update total count
                        updateTotalCount(-1);
                        
                        // Show success message
                        showAlert('success', response.message);
                    }, 800); // Match the animation duration
                    
                    // Remove from selected IDs if it exists
                    selectedIds = selectedIds.filter(id => id != response.deletedId);
                    saveSelectedIds();
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                $('#deleteModal').modal('hide');
                showAlert('danger', 'An error occurred while deleting the user. Please try again.');
            }
        });
    }
    
    // Function to show alerts
    function showAlert(type, message) {
        var alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        
        $('#alertContainer').html(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
    
    // Function to update the total users count
    function updateTotalCount(change) {
        var currentCount = parseInt($('#totalUsersCount').text().replace(/,/g, ''));
        var newCount = currentCount + change;
        $('#totalUsersCount').text(newCount.toLocaleString());
    }
    
    // Initialize checkboxes based on session data
    function initializeCheckboxes() {
        $('.row-checkbox').each(function() {
            var id = $(this).val();
            if (selectedIds.includes(id)) {
                $(this).prop('checked', true);
            }
        });
        
        // Update select all checkbox
        updateSelectAllCheckbox();
    }
    
    // Update the select all checkbox state
    function updateSelectAllCheckbox() {
        var allChecked = $('.row-checkbox:checked').length === $('.row-checkbox').length && $('.row-checkbox').length > 0;
        $('#selectAll').prop('checked', allChecked);
    }
    
    // Save selected IDs to session via AJAX
    function saveSelectedIds() {
        updateDeleteButton();
        $.ajax({
            url: 'manage_library_users.php',
            type: 'POST',
            data: {
                action: 'updateSelectedUsers',
                selectedIds: selectedIds
            },
            dataType: 'json',
            success: function(response) {
                console.log('Saved ' + response.count + ' selected users');
            }
        });
    }
    
    // Initialize checkboxes on page load
    initializeCheckboxes();
    
    // Handle row clicks to select checkbox
    $('#usersTable tbody').on('click', 'tr', function(e) {
        // Ignore clicks on checkbox itself and on action buttons
        if (e.target.type === 'checkbox' || $(e.target).hasClass('btn') || $(e.target).parent().hasClass('btn')) {
            return;
        }
        
        var checkbox = $(this).find('.row-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });
    
    // Handle checkbox change events
    $('#usersTable tbody').on('change', '.row-checkbox', function() {
        var id = $(this).val();
        
        if ($(this).prop('checked')) {
            if (!selectedIds.includes(id)) {
                selectedIds.push(id);
            }
        } else {
            selectedIds = selectedIds.filter(item => item !== id);
        }
        
        updateSelectAllCheckbox();
        saveSelectedIds();
    });
    
    // Handle select all checkbox
    $('#selectAll').on('change', function() {
        var isChecked = $(this).prop('checked');
        
        $('.row-checkbox').each(function() {
            $(this).prop('checked', isChecked);
            
            var id = $(this).val();
            if (isChecked && !selectedIds.includes(id)) {
                selectedIds.push(id);
            }
        });
        
        if (!isChecked) {
            selectedIds = [];
        }
        
        saveSelectedIds();
    });
    
    // Handle header cell click for select all
    $('#checkboxHeader').on('click', function(e) {
        // If clicking directly on the checkbox, don't execute this
        if (e.target.type === 'checkbox') return;
        
        $('#selectAll').trigger('click');
    });
    
    // Handle bulk delete button
    $('#deleteSelectedBtn').on('click', function(e) {
        e.preventDefault();
        
        if (selectedIds.length === 0) {
            showAlert('warning', 'Please select at least one user to delete.');
            return;
        }
        
        if (confirm('Are you sure you want to delete ' + selectedIds.length + ' selected user(s)?')) {
            bulkDeleteUsers(selectedIds);
        }
    });
    
    // Function to perform bulk delete via AJAX
    function bulkDeleteUsers(ids) {
        $.ajax({
            url: 'manage_library_users.php',
            type: 'POST',
            data: {
                action: 'bulkDelete',
                ids: ids
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Process each deleted ID
                    response.deletedIds.forEach(function(id) {
                        var row = $('#user-row-' + id);
                        row.addClass('deleting-row');
                    });
                    
                    setTimeout(function() {
                        // Remove the rows from DataTable
                        response.deletedIds.forEach(function(id) {
                            var row = $('#user-row-' + id);
                            table.row(row).remove();
                        });
                        
                        // Redraw the table to update pagination etc.
                        table.draw(false);
                        
                        // Update total count
                        updateTotalCount(-response.deletedIds.length);
                        
                        // Clear selected IDs
                        selectedIds = [];
                        saveSelectedIds();
                        
                        // Show success message
                        showAlert('success', response.message);
                    }, 800); // Match the animation duration
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while deleting users. Please try again.');
            }
        });
    }
    
    // Update delete button with count
    function updateDeleteButton() {
        const count = selectedIds.length;
        const deleteBtn = $('.bulk-delete-btn');
        deleteBtn.find('.badge').text(count);
        deleteBtn.prop('disabled', count === 0);
    }
    
    // Make the entire checkbox cell clickable
    $(document).on('click', '.checkbox-cell', function(e) {
        // Prevent triggering if clicking directly on the checkbox
        if (e.target.type !== 'checkbox') {
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            checkbox.trigger('change'); // Trigger change event
        }
    });
    
    // Make sure row and table cell event handlers don't conflict
    $('#usersTable tbody').off('click', 'td:first-child');
    $('#usersTable tbody').off('click', 'tr');
    
    // Re-implement row click for checkbox toggle while avoiding action buttons
    $('#usersTable tbody').on('click', 'tr', function(e) {
        // Ignore clicks on checkbox itself and on action buttons
        if (e.target.type === 'checkbox' || 
            $(e.target).hasClass('btn') || 
            $(e.target).parent().hasClass('btn') ||
            $(e.target).hasClass('context-menu-item') ||
            $(e.target).parent().hasClass('context-menu-item')) {
            return;
        }
        
        // Toggle the checkbox
        var checkbox = $(this).find('.row-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });
});
</script>
