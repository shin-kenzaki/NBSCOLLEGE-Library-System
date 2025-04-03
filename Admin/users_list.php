<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
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
    'usertype' => '', 
    'department' => '', // Add department to values array
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = $_POST['school_id'] ?? ''; 
    $firstname = trim($_POST['firstname'] ?? '');
    $middle_init = trim($_POST['middle_init'] ?? '') ?: NULL;
    $lastname = trim($_POST['lastname'] ?? '');
    $email = $_POST['email'] ?? ''; 
    $contact_no = $_POST['contact_no'] ?? '';
    $usertype = $_POST['usertype'] ?? ''; 
    $department = $_POST['department'] ?? ''; // Get department from POST data
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
    $values = compact('school_id', 'firstname', 'middle_init', 'lastname', 'email', 'contact_no', 'usertype', 'department');

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
            $image = '../Images/Profile/default-avatar.jpg';
            $sql = "INSERT INTO users (
                school_id, firstname, middle_init, lastname, 
                email, password, contact_no, user_image, 
                usertype, address, id_type, id_image, 
                status, date_added, last_update, department
            ) VALUES (
                ?, ?, ?, ?, 
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, NOW(), NOW(), ?
            )";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssssssssssss",
                $school_id, $firstname, $middle_init, $lastname,
                $email, $hashed_password, $contact_no, $image,
                $usertype, $address, $id_type, $id_image,
                $status, $department
            );

            if ($stmt->execute()) {
                // Insert update for new user
                $admin_id = $_SESSION['admin_employee_id'];
                $admin_role = $_SESSION['role'];
                $admin_fullname = $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname'];
                $user_fullname = $firstname . ' ' . ($middle_init ? $middle_init . ' ' : '') . $lastname;
                $update_title = "$admin_role $admin_fullname Registered a User";
                $update_message = "$admin_role $admin_fullname Registered $user_fullname as $usertype";
                $update_sql = "INSERT INTO updates (user_id, role, title, message, `update`) VALUES (?, ?, ?, ?, NOW())";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("isss", $admin_id, $admin_role, $update_title, $update_message);
                $update_stmt->execute();
                $update_stmt->close();

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
            <div class="card-body px-0">
                <div class="table-responsive px-3">
                    <table class="table table-bordered table-striped" id="usersTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 30px;" id="selectAllHeader">Select</th>
                                <th class="text-center">ID</th>
                                <th class="text-center">Physical ID Number</th>
                                <th class="text-center">Name</th>
                                <th class="text-center">Email</th>
                                <th class="text-center">Borrowing</th>
                                <th class="text-center">Returned</th>
                                <th class="text-center">Damaged</th>
                                <th class="text-center">Lost</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Date Added</th>
                                <th class="text-center">Last Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Modified query to get user data and calculate borrowing stats from the borrowings table
                            $query = "SELECT u.*, 
                                (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status IN ('Borrowed', 'Overdue')) AS borrowed_books,
                                (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status = 'Returned') AS returned_books,
                                (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status = 'Damaged') AS damaged_books,
                                (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status = 'Lost') AS lost_books
                                FROM users u
                                ORDER BY u.date_added DESC";
                            $result = $conn->query($query);

                            while ($row = $result->fetch_assoc()) {
                                $fullname = $row['firstname'] . ' ' . ($row['middle_init'] ? $row['middle_init'] . ' ' : '') . $row['lastname'];
                                list($status_class, $status_text) = getStatusDisplay($row['status']);
                                
                                echo "<tr>";
                                echo "<td><input type='checkbox' class='user-checkbox' data-user-id='{$row['id']}'></td>";
                                echo "<td class='text-center'>{$row['id']}</td>";
                                echo "<td class='text-center'>{$row['school_id']}</td>";
                                echo "<td>" . htmlspecialchars($fullname) . "</td>";
                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                echo "<td class='text-center'><span class='badge badge-info'>{$row['borrowed_books']}</span></td>";
                                echo "<td class='text-center'><span class='badge badge-success'>{$row['returned_books']}</span></td>";
                                echo "<td class='text-center'><span class='badge badge-warning'>{$row['damaged_books']}</span></td>";
                                echo "<td class='text-center'><span class='badge badge-danger'>{$row['lost_books']}</span></td>";
                                echo "<td class='text-center'><span class='badge {$status_class}'>{$status_text}</span></td>";
                                echo "<td class='text-center'>" . date('M d, Y', strtotime($row['date_added'])) . "</td>";
                                echo "<td class='text-center'>" . ($row['last_update'] ? date('M d, Y', strtotime($row['last_update'])) : 'Never') . "</td>";
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
    <a class="dropdown-item" href="#" id="viewUser">View Details</a>
    <a class="dropdown-item" href="#" id="updateUser">Update</a>
    <a class="dropdown-item" href="#" id="generatePassword">Generate New Password</a>
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
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department" class="form-control">
                                    <option value="" <?= empty($values['department']) ? 'selected' : '' ?>>Select Department</option>
                                    <option value="Computer Science" <?= ($values['department'] ?? '') == 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                                    <option value="Accounting Information System" <?= ($values['department'] ?? '') == 'Accounting Information System' ? 'selected' : '' ?>>Accounting Information System</option>
                                    <option value="Accountancy" <?= ($values['department'] ?? '') == 'Accountancy' ? 'selected' : '' ?>>Accountancy</option>
                                    <option value="Entrepreneurship" <?= ($values['department'] ?? '') == 'Entrepreneurship' ? 'selected' : '' ?>>Entrepreneurship</option>
                                    <option value="Tourism Management" <?= ($values['department'] ?? '') == 'Tourism Management' ? 'selected' : '' ?>>Tourism Management</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>User Type</label>
                                <select name="usertype" class="form-control">
                                    <option value="" disabled <?= empty($values['usertype']) ? 'selected' : '' ?>>Select User Type</option>
                                    <option value="Student" <?= ($values['usertype'] ?? '') == 'Student' ? 'selected' : '' ?>>Student</option>
                                    <option value="Faculty" <?= ($values['usertype'] ?? '') == 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                                    <option value="Staff" <?= ($values['usertype'] ?? '') == 'Staff' ? 'selected' : '' ?>>Staff</option>
                                    <option value="Visitor" <?= ($values['usertype'] ?? '') == 'Visitor' ? 'selected' : '' ?>>Visitor</option>
                                </select>
                                <small class="text-danger"><?= $errors['usertype'] ?? '' ?></small>
                            </div>
                        </div>
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
        "responsive": false,
        "scrollX": true,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        },
        "columnDefs": [
            { "orderable": false, "targets": 0 } // Disable sorting on checkbox column
        ]
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
    $('#viewUser').click(function() {
        window.location.href = `view_user.php?id=${selectedUserId}`;
    });

    $('#updateUser').click(function() {
        window.location.href = `edit_user.php?id=${selectedUserId}`;
    });
    
    // Add new generate password action
    $('#generatePassword').click(function() {
        Swal.fire({
            title: 'Generate New Password?',
            text: 'Are you sure you want to generate a new password for this user?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, generate new password'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'generate_user_password.php',
                    method: 'POST',
                    data: { userId: selectedUserId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Password Generated!',
                                html: `
                                    <div class='text-center'>
                                        <p>A new password has been generated.</p>
                                        <p><strong>ID Number:</strong> ${response.school_id}</p>
                                        <p><strong>User:</strong> ${response.user_name}</p>
                                        <div class="input-group mb-3">
                                            <input type="text" id="newPassword" class="form-control" value="${response.password}" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" onclick="copyPassword()">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <p class='text-danger'><small>Please make sure to copy this password now!</small></p>
                                    </div>
                                `,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'Failed to generate new password',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // ...existing code for banUser, disableUser, deleteUser...
    
    // ...existing code...
});

// Function to copy the generated password
function copyPassword() {
    const passwordField = document.getElementById('newPassword');
    passwordField.select();
    document.execCommand('copy');
    
    // Show a small tooltip/notification that password was copied
    const tooltip = document.createElement('div');
    tooltip.textContent = 'Password copied!';
    tooltip.style.position = 'absolute';
    tooltip.style.backgroundColor = 'rgba(0,0,0,0.7)';
    tooltip.style.color = 'white';
    tooltip.style.padding = '5px 10px';
    tooltip.style.borderRadius = '3px';
    tooltip.style.fontSize = '12px';
    tooltip.style.zIndex = '9999';
    tooltip.style.left = '50%';
    tooltip.style.top = '50%';
    tooltip.style.transform = 'translate(-50%, -50%)';
    
    document.body.appendChild(tooltip);
    
    setTimeout(() => {
        document.body.removeChild(tooltip);
    }, 1500);
}
</script>

<style>
    /* Add responsive table styles */
    .table-responsive {
        width: 100%;
        margin-bottom: 1rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    #usersTable th,
    #usersTable td {
        min-width: 100px;
        white-space: nowrap;
    }
    
    #usersTable {
        width: 100% !important;
    }
    
    .table td, .table th {
        white-space: nowrap;
    }

    /* Add to existing styles */
    .card-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }
    
    .card-header .btn-group {
        display: inline-flex;
        flex-wrap: nowrap;
        gap: 5px;
    }
    
    @media (max-width: 768px) {
        .card-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .card-header .btn-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            width: 100%;
        }
        
        .card-header .btn {
            margin: 2px !important;
            white-space: nowrap;
            justify-content: center;
            padding: .25rem .5rem;
            font-size: .875rem;
        }
        
        .card-header h6 {
            text-align: center;
            margin-bottom: 10px !important;
        }
    }
    
    /* Center checkboxes in cells */
    #usersTable th:first-child,
    #usersTable td:first-child {
        text-align: center;
        vertical-align: middle;
    }
    
    /* Make sure checkbox inputs are centered */
    #usersTable .select-all-checkbox,
    #usersTable .user-checkbox {
        margin: 0 auto;
        display: block;
    }
    
    /* Enhanced hover effect for checkbox column */
    #usersTable tbody tr td:first-child,
    #usersTable thead tr th:first-child {
        cursor: pointer;
        background-clip: padding-box;
        transition: background-color 0.2s ease;
    }
    
    #usersTable tbody tr td:first-child:hover,
    #usersTable thead tr th:first-child:hover {
        background-color: rgba(0, 123, 255, 0.15);
    }
    
    /* Checkbox cell base style */
    #usersTable th:first-child,
    #usersTable td:first-child {
        width: 40px !important;
        min-width: 40px !important;
    }
    
    /* Highlight the entire row on hover */
    #usersTable tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    /* Enhanced table striping with hover preservation */
    #usersTable.table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.03);
    }
    
    #usersTable.table-striped tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05) !important;
    }
    
    /* Maintain highlight on checkbox cells even with striping */
    #usersTable tbody tr td:first-child:hover,
    #usersTable thead tr th:first-child:hover {
        background-color: rgba(0, 123, 255, 0.15) !important;
    }
    
    /* Make header row stand out more */
    #usersTable thead th {
        background-color: #f8f9fc;
        border-bottom: 2px solid #e3e6f0;
    }
</style>

<?php include('../admin/inc/footer.php'); ?>
