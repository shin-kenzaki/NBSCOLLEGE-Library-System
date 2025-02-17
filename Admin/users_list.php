<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';
include 'inc/status_helper.php';

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

$errors = [
    'school_id' => '',
    'firstname' => '',
    'lastname' => '',
    'email' => '',
    'password' => '',
];
$values = [
    'school_id' => '',
    'firstname' => '',
    'middle_init' => '',
    'lastname' => '',
    'email' => '',
    'password' => '',
    'contact_no' => '',
    'usertype' => '', // Add this line
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = $_POST['school_id']; 
    $firstname = trim($_POST['firstname']);
    $middle_init = trim($_POST['middle_init']) ?? NULL;
    $lastname = trim($_POST['lastname']);
    $email = $_POST['email']; 
    $password = $_POST['password'];
    $contact_no = $_POST['contact_no'];
    $usertype = $_POST['usertype']; // Add this line
    $status = $_POST['status'] ?? 0;
    
    // Default values
    $user_image = '/upload/default-avatar.png';
    // Remove or comment out this line since usertype is now from input
    // $usertype = 'student'; 
    $borrowed_books = 0;
    $returned_books = 0;
    $damaged_books = 0;
    $lost_books = 0;
    $address = '';
    $id_type = '';
    $id_image = '/upload/default-id.png';

    // Store values to retain input data
    $values = compact('school_id', 'firstname', 'middle_init', 'lastname', 'email', 'password', 'contact_no', 'usertype');

    // Validation
    if (empty($school_id)) $errors['school_id'] = "School ID is required.";
    if (empty($firstname)) $errors['firstname'] = "First name is required.";
    if (empty($lastname)) $errors['lastname'] = "Last name is required.";
    if (empty($email)) $errors['email'] = "Email is required.";
    if (empty($password) || strlen($password) < 8) $errors['password'] = "Password must be at least 8 characters.";

    // Check for duplicates
    if (!array_filter($errors)) {
        $sql_check = "SELECT school_id, email FROM users WHERE school_id = ? OR email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $school_id, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            while ($row = $result_check->fetch_assoc()) {
                if ($row['school_id'] == $school_id) $errors['school_id'] = "This School ID is already in use.";
                if ($row['email'] == $email) $errors['email'] = "This email is already taken.";
            }
        } else {
            // Insert new user with all required fields
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (
                school_id, firstname, middle_init, lastname, 
                email, password, contact_no, user_image, 
                usertype, borrowed_books, returned_books, 
                damaged_books, lost_books, address, 
                id_type, id_image, status, date_added
            ) VALUES (
                ?, ?, ?, ?, 
                ?, ?, ?, ?,
                ?, ?, ?, 
                ?, ?, ?,
                ?, ?, ?, NOW()
            )";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssssssiiiisssi",
                $school_id, $firstname, $middle_init, $lastname,
                $email, $hashed_password, $contact_no, $user_image,
                $usertype, $borrowed_books, $returned_books,
                $damaged_books, $lost_books, $address,
                $id_type, $id_image, $status
            );

            if ($stmt->execute()) {
                echo "<script>
                    alert('User has been added successfully.');
                    window.location.href='users_list.php';
                </script>";
                exit;
            } else {
                echo "<script>alert('Error adding user: " . $stmt->error . "');</script>";
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
        $formSubmitted = true; // Set flag to keep modal open
    }
}
?>

<!-- Begin Page Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <!-- Page Heading -->
        <h1 class="h3 mb-4 text-gray-800">User Management</h1>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Users List</h6>
                <a href="#" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addUserModal">
                    <i class="fas fa-plus"></i> Add New User
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>School ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Borrowed</th>
                                <th>Returned</th>
                                <th>Damaged</th>
                                <th>Lost</th>
                                <th>Status</th>
                                <th>Date Added</th>
                                <th>Last Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End of Page Content -->

<!-- Context Menu -->
<div id="contextMenu" class="dropdown-menu" style="display:none; position:absolute;">
    <a class="dropdown-item" href="#" id="viewUser">View Details</a>
    <a class="dropdown-item" href="#" id="updateUser">Update</a>
    <a class="dropdown-item" href="#" id="deleteUser">Delete</a>
</div>

<!-- Add User Modal -->
<div class="modal fade <?php if (isset($formSubmitted) && $formSubmitted) echo 'show d-block'; ?>" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addUserForm" action="" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>School ID</label>
                        <input type="text" name="school_id" class="form-control" value="<?= htmlspecialchars($values['school_id'] ?? '') ?>">
                        <small class="text-danger"><?= $errors['school_id'] ?? '' ?></small>
                    </div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($values['firstname'] ?? '') ?>">
                        <small class="text-danger"><?= $errors['firstname'] ?? '' ?></small>
                    </div>
                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" name="middle_init" class="form-control" value="<?= htmlspecialchars($values['middle_init'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($values['lastname'] ?? '') ?>">
                        <small class="text-danger"><?= $errors['lastname'] ?? '' ?></small>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($values['email'] ?? '') ?>">
                        <small class="text-danger"><?= $errors['email'] ?? '' ?></small>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control">
                        <small class="text-danger"><?= $errors['password'] ?? '' ?></small>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_no" class="form-control" value="<?= htmlspecialchars($values['contact_no'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>User Type</label>
                        <select name="usertype" class="form-control">
                            <option value="" disabled <?= empty($values['usertype']) ? 'selected' : '' ?>>Select User Type</option>
                            <option value="student" <?= ($values['usertype'] ?? '') == 'student' ? 'selected' : '' ?>>Student</option>
                            <option value="faculty" <?= ($values['usertype'] ?? '') == 'faculty' ? 'selected' : '' ?>>Faculty</option>
                            <option value="staff" <?= ($values['usertype'] ?? '') == 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="visitor" <?= ($values['usertype'] ?? '') == 'visitor' ? 'selected' : '' ?>>Visitor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="0" selected>Inactive</option>
                            <option value="1">Active</option>
                            <option value="2">Banned</option>
                            <option value="3">Disabled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var selectedUserIds = [];

    var table = $('#usersTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "order": [[11, "desc"]], 
        "pageLength": 10,
        "responsive": true,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        }
    });

    function fetchUsers() {
        $.ajax({
            url: 'fetch_users.php',
            type: 'GET',
            success: function(response) {
                $('#usersTable tbody').html(response);
            }
        });
    }

    fetchUsers();

    // Context Menu functionality
    var selectedUserId;

    $('#usersTable tbody').on('contextmenu', 'tr', function(e) {
        e.preventDefault();
        selectedUserId = $(this).find('td:nth-child(2)').text();
        $('#contextMenu').css({
            display: 'block',
            left: e.pageX,
            top: e.pageY
        });
        return false;
    });

    // Hide context menu on click outside
    $(document).click(function() {
        $('#contextMenu').hide();
    });

    // Context Menu Actions
    $('#viewUser').click(function() {
        window.location.href = `view_user.php?id=${selectedUserId}`;
    });

    $('#updateUser').click(function() {
        window.location.href = `edit_user.php?id=${selectedUserId}`;
    });

    // Update the delete action in the context menu handler
    $('#deleteUser').click(function() {
        if (confirm('Are you sure you want to delete this user?')) {
            window.location.href = 'delete_user.php?id=' + selectedUserId;
        }
    });

    // Change the "Add User" button to trigger the modal
    $('.card-header .btn-success').attr('data-toggle', 'modal').attr('data-target', '#addUserModal');
});
</script>

<?php include('../admin/inc/footer.php'); ?>
