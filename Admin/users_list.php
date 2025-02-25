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
include 'inc/auto_status_update.php';

// Auto update inactive users
updateInactiveUsers($conn);

// Fix the password generation function
function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) { // Fix the comparison operator
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

$errors = [
    'school_id' => '',
    'firstname' => '',
    'lastname' => '',
    'email' => '',
    'usertype' => '' // Remove password from errors array
];
$values = [
    'school_id' => '',
    'firstname' => '',
    'middle_init' => '',
    'lastname' => '',
    'email' => '',
    'contact_no' => '',
    'usertype' => '', // Remove password from values array
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = $_POST['school_id'] ?? ''; 
    $firstname = trim($_POST['firstname'] ?? '');
    $middle_init = trim($_POST['middle_init'] ?? '') ?: NULL;
    $lastname = trim($_POST['lastname'] ?? '');
    $email = $_POST['email'] ?? ''; 
    $contact_no = $_POST['contact_no'] ?? '';
    $usertype = $_POST['usertype'] ?? ''; // Add null coalescing operator
    $status = 1; // Automatically set status to Active (1)
    
    // Default values
    $user_image = 'inc/upload/default-avatar.jpg';    // Remove or comment out this line since usertype is now from input
    // $usertype = 'student'; 
    $borrowed_books = 0;
    $returned_books = 0;
    $damaged_books = 0;
    $lost_books = 0;
    $address = '';
    $id_type = '';
    $id_image = '/upload/default-id.png';

    // Store values to retain input data
    $values = compact('school_id', 'firstname', 'middle_init', 'lastname', 'email', 'contact_no', 'usertype');

    // Generate password automatically
    $password = generatePassword(12); // 12 characters long

    // Validation with more specific error messages
    if (empty($school_id)) {
        $errors['school_id'] = "Physical ID number is required.";
        $formSubmitted = true;
    }
    if (empty($firstname)) {
        $errors['firstname'] = "First name is required.";
        $formSubmitted = true;
    }
    if (empty($lastname)) {
        $errors['lastname'] = "Last name is required.";
        $formSubmitted = true;
    }
    if (empty($email)) {
        $errors['email'] = "Email is required.";
        $formSubmitted = true;
    }
    if (empty($usertype)) {
        $errors['usertype'] = "Please select a user type.";
        $formSubmitted = true;
    }

    // Check for duplicates
    if (!array_filter($errors)) {
        $sql_check = "SELECT school_id, email FROM users WHERE school_id = ? OR email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $school_id, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            while ($row = $result_check->fetch_assoc()) {
                if ($row['school_id'] == $school_id) {
                    $errors['school_id'] = "This ID number is already in use.";
                    $formSubmitted = true;
                }
                if ($row['email'] == $email) {
                    $errors['email'] = "This email is already taken.";
                    $formSubmitted = true;
                }
            }
        } else {
            // Insert new user with all required fields
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $image = '../Admin/inc/upload/default-avatar.jpg'; // Default image path
            $sql = "INSERT INTO users (
                school_id, firstname, middle_init, lastname, 
                email, password, contact_no, user_image, 
                usertype, borrowed_books, returned_books, 
                damaged_books, lost_books, address, 
                id_type, id_image, status, date_added, last_update
            ) VALUES (
                ?, ?, ?, ?, 
                ?, ?, ?, ?,
                ?, ?, ?, 
                ?, ?, ?,
                ?, ?, ?, NOW(), NOW()
            )";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssssssiiiisssi",
                $school_id, $firstname, $middle_init, $lastname,
                $email, $hashed_password, $contact_no, $image, // Added image parameter
                $usertype, $borrowed_books, $returned_books,
                $id_type, $id_image, $status
            );

            if ($stmt->execute()) {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'User Added Successfully',
                            html: `
                                <div class='text-center'>
                                    <p><strong>Physical ID Number:</strong> " . $school_id . "</p>
                                    <p><strong>Generated Password:</strong> " . $password . "</p>
                                    <p class='text-danger'><small>Please make sure to copy this information now!</small></p>
                                </div>
                            `,
                            icon: 'success',
                            allowOutsideClick: false,
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'users_list.php';
                            }
                        });
                    });
                </script>";
            } else {
                $errors['general'] = "Error adding user: " . $stmt->error;
                $formSubmitted = true;
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
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Users List</h6>
                <div>
                    <button id="bulkActivateBtn" class="btn btn-outline-success btn-sm mr-2" disabled>
                        Activate Selected (<span id="selectedActivateCount">0</span>)
                    </button>
                    <button id="bulkBanBtn" class="btn btn-warning btn-sm mr-2" disabled>
                        Ban Selected (<span id="selectedBanCount">0</span>)
                    </button>
                    <button id="bulkDisableBtn" class="btn btn-secondary btn-sm mr-2" disabled>
                        Disable Selected (<span id="selectedDisableCount">0</span>)
                    </button>
                    <button id="bulkDeleteBtn" class="btn btn-danger btn-sm mr-2" disabled>
                        Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                    <a href="#" class="btn btn-success btn-sm add-user-btn" data-toggle="modal" data-target="#addUserModal">
                        <i class="fas fa-plus"></i> Add New User
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="width: 30px;">
                                    <input type="checkbox" id="selectAll" class="select-all-checkbox">
                                </th>
                                <th>ID</th>
                                <th>Physical ID Number</th>
                                <th>Name</th>
                                <th>Email</th>
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
                            <?php
                            $query = "SELECT u.*, 
                                u.borrowed_books,
                                u.returned_books,
                                u.damaged_books,
                                u.lost_books
                                FROM users u
                                ORDER BY u.date_added DESC";
                            $result = $conn->query($query);

                            while ($row = $result->fetch_assoc()) {
                                $fullname = $row['firstname'] . ' ' . ($row['middle_init'] ? $row['middle_init'] . ' ' : '') . $row['lastname'];
                                list($status_class, $status_text) = getStatusDisplay($row['status']);
                                
                                echo "<tr>";
                                echo "<td><input type='checkbox' class='user-checkbox' data-user-id='{$row['id']}'></td>";
                                echo "<td>{$row['id']}</td>";
                                echo "<td>{$row['school_id']}</td>";
                                echo "<td>" . htmlspecialchars($fullname) . "</td>";
                                echo "<td>{$row['email']}</td>";
                                // Removed contact_no column
                                echo "<td><span class='badge badge-info'>{$row['borrowed_books']}</span></td>";
                                echo "<td><span class='badge badge-success'>{$row['returned_books']}</span></td>";
                                echo "<td><span class='badge badge-warning'>{$row['damaged_books']}</span></td>";
                                echo "<td><span class='badge badge-danger'>{$row['lost_books']}</span></td>";
                                echo "<td><span class='badge {$status_class}'>{$status_text}</span></td>";
                                echo "<td>" . date('M d, Y', strtotime($row['date_added'])) . "</td>";
                                echo "<td>" . ($row['last_update'] ? date('M d, Y', strtotime($row['last_update'])) : 'Never') . "</td>";
                                echo "</tr>";
                            }
                            ?>
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
    <a class="dropdown-item" href="#" id="updateUser">Update</a>
    <a class="dropdown-item" href="#" id="banUser">Ban User</a>
    <a class="dropdown-item" href="#" id="disableUser">Disable User</a>
    <a class="dropdown-item" href="#" id="deleteUser">Delete</a>
</div>

<!-- Add User Modal -->
<div class="modal fade <?php if (isset($formSubmitted)) echo 'show'; ?>" id="addUserModal" 
     tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" 
     aria-hidden="true" <?php if (isset($formSubmitted)) echo 'style="display: block;"'; ?>>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addUserForm" action="" method="POST" onsubmit="return confirmSubmit(event)">
                <div class="modal-body">
                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger"><?= $errors['general'] ?></div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Physical ID Number</label>
                                <input type="text" name="school_id" class="form-control" value="<?= htmlspecialchars($values['school_id'] ?? '') ?>">
                                <small class="text-danger"><?= $errors['school_id'] ?? '' ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($values['firstname'] ?? '') ?>">
                                <small class="text-danger"><?= $errors['firstname'] ?? '' ?></small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>M.I.</label>
                                <input type="text" name="middle_init" class="form-control" value="<?= htmlspecialchars($values['middle_init'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($values['lastname'] ?? '') ?>">
                                <small class="text-danger"><?= $errors['lastname'] ?? '' ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($values['email'] ?? '') ?>">
                        <small class="text-danger"><?= $errors['email'] ?? '' ?></small>
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
                        <small class="text-danger"><?= $errors['usertype'] ?? '' ?></small>
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

<!-- Add SweetAlert2 library before your closing body tag -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    var selectedUserIds = [];

    var table = $('#usersTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "order": [[10, "desc"]],
        "pageLength": 10,
        "responsive": true,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        }
    });

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
    // Remove the viewUser click handler
    
    $('#updateUser').click(function() {
        window.location.href = `edit_user.php?id=${selectedUserId}`;
    });

    $('#banUser').click(function() {
        handleStatusChange(selectedUserId, 2, 'ban');
    });

    $('#disableUser').click(function() {
        handleStatusChange(selectedUserId, 3, 'disable');
    });

    $('#deleteUser').click(function() {
        if (confirm('Are you sure you want to delete this user?')) {
            window.location.href = 'delete_user.php?id=' + selectedUserId;
        }
    });

    // Change this line to use a more specific selector
    $('.add-user-btn').attr('data-toggle', 'modal').attr('data-target', '#addUserModal');

    // Add this to handle modal state after form submission with errors
    <?php if (isset($formSubmitted)): ?>
    $('#addUserModal').modal('show');
    <?php endif; ?>

    // Enhanced form validation and submission
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        
        // Check required fields
        let form = this;
        let hasErrors = false;
        
        // Basic validation before showing confirmation
        if (!form.school_id.value) {
            $('[name="school_id"]').next('.text-danger').text('Physical ID number is required.');
            hasErrors = true;
        }
        if (!form.firstname.value) {
            $('[name="firstname"]').next('.text-danger').text('First name is required.');
            hasErrors = true;
        }
        if (!form.lastname.value) {
            $('[name="lastname"]').next('.text-danger').text('Last name is required.');
            hasErrors = true;
        }
        if (!form.email.value) {
            $('[name="email"]').next('.text-danger').text('Email is required.');
            hasErrors = true;
        }
        if (!form.usertype.value) {
            $('[name="usertype"]').next('.text-danger').text('Please select a user type.');
            hasErrors = true;
        }

        if (hasErrors) {
            return false;
        }

        // Updated confirmation message with centered content
        Swal.fire({
            title: 'Confirm User Addition',
            html: `
                <div class='text-center'>
                    <p>Are you sure you want to add this user?</p>
                    <p><strong>Physical ID Number:</strong> ${form.school_id.value}</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, add user',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Select All functionality
    $('#selectAll').change(function() {
        const isChecked = $(this).prop('checked');
        $('.user-checkbox').prop('checked', isChecked);
        updateSelectedCount();
    });

    // Individual checkbox handler
    $(document).on('change', '.user-checkbox', function() {
        const allChecked = $('.user-checkbox:checked').length === $('.user-checkbox').length;
        $('#selectAll').prop('checked', allChecked);
        updateSelectedCount();
    });

    // Update selected count and button state
    function updateSelectedCount() {
        const checkedBoxes = $('.user-checkbox:checked').length;
        $('#selectedCount').text(checkedBoxes);
        $('#selectedBanCount').text(checkedBoxes);
        $('#selectedDisableCount').text(checkedBoxes);
        $('#selectedActivateCount').text(checkedBoxes);
        $('#bulkDeleteBtn').prop('disabled', checkedBoxes === 0);
        $('#bulkBanBtn').prop('disabled', checkedBoxes === 0);
        $('#bulkDisableBtn').prop('disabled', checkedBoxes === 0);
        $('#bulkActivateBtn').prop('disabled', checkedBoxes === 0);
    }

    // Bulk delete handler
    $('#bulkDeleteBtn').click(function() {
        const selectedUsers = $('.user-checkbox:checked').map(function() {
            return $(this).data('user-id');
        }).get();

        if (selectedUsers.length === 0) return;

        Swal.fire({
            title: 'Delete Selected Users?',
            text: `Are you sure you want to delete ${selectedUsers.length} user(s)? This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'bulk_delete_users.php',
                    method: 'POST',
                    data: { userIds: selectedUsers },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                Swal.fire(
                                    'Deleted!',
                                    result.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    result.message,
                                    'error'
                                );
                            }
                        } catch (e) {
                            Swal.fire(
                                'Error!',
                                'An unexpected error occurred',
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'Failed to delete users',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // Bulk ban handler
    $('#bulkBanBtn').click(function() {
        handleBulkStatusChange(2, 'ban');
    });

    // Bulk disable handler
    $('#bulkDisableBtn').click(function() {
        handleBulkStatusChange(3, 'disable');
    });

    // Add bulk activate handler
    $('#bulkActivateBtn').click(function() {
        const selectedUsers = $('.user-checkbox:checked').map(function() {
            return $(this).data('user-id');
        }).get();

        if (selectedUsers.length === 0) return;

        Swal.fire({
            title: 'Activate Selected Users?',
            text: `Are you sure you want to activate ${selectedUsers.length} user(s)?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, activate them!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'bulk_update_status.php',
                    method: 'POST',
                    data: { 
                        userIds: selectedUsers,
                        status: 1  // 1 represents "Active" status
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                Swal.fire(
                                    'Activated!',
                                    result.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    result.message,
                                    'error'
                                );
                            }
                        } catch (e) {
                            Swal.fire(
                                'Error!',
                                'An unexpected error occurred',
                                'error'
                            );
                        }
                    }
                });
            }
        });
    });

    function handleStatusChange(userId, status, action) {
        const actionText = action === 'ban' ? 'ban' : 'disable';
        Swal.fire({
            title: `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} User?`,
            text: `Are you sure you want to ${actionText} this user?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: `Yes, ${actionText} them!`
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'update_user_status.php',
                    method: 'POST',
                    data: { 
                        userId: userId,
                        status: status
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                Swal.fire(
                                    `${actionText.charAt(0).toUpperCase() + actionText.slice(1)}ned!`,
                                    result.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    result.message,
                                    'error'
                                );
                            }
                        } catch (e) {
                            Swal.fire(
                                'Error!',
                                'An unexpected error occurred',
                                'error'
                            );
                        }
                    }
                });
            }
        });
    }

    function handleBulkStatusChange(status, action) {
        const selectedUsers = $('.user-checkbox:checked').map(function() {
            return $(this).data('user-id');
        }).get();

        if (selectedUsers.length === 0) return;

        const actionText = action === 'ban' ? 'ban' : 'disable';
        
        Swal.fire({
            title: `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Selected Users?`,
            text: `Are you sure you want to ${actionText} ${selectedUsers.length} user(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: `Yes, ${actionText} them!`
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'bulk_update_status.php',
                    method: 'POST',
                    data: { 
                        userIds: selectedUsers,
                        status: status
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                Swal.fire(
                                    `${actionText.charAt(0).toUpperCase() + actionText.slice(1)}ned!`,
                                    result.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    result.message,
                                    'error'
                                );
                            }
                        } catch (e) {
                            Swal.fire(
                                'Error!',
                                'An unexpected error occurred',
                                'error'
                            );
                        }
                    }
                });
            }
        });
    }

    // ...rest of existing code...
});
</script>

<?php include('../admin/inc/footer.php'); ?>
