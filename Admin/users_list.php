<?php
session_start();

// Function to send email to users
function sendUserEmail($email, $schoolId, $password, $firstname, $lastname)
{
    $mail = require __DIR__ . '/mailer.php'; // Include the PHPMailer instance

    try {
        $mail->setFrom('library@nbscollege.edu.ph', 'Library System');
        $mail->addAddress($email);
        $mail->Subject = 'NBS College Library System - Account Created';
        $mail->Body = "
            <p>Dear $firstname $lastname,</p>
            <p>We are pleased to inform you that your account has been successfully created in the NBS College Library System. Below are your login credentials:</p>
            <p><strong>ID Number:</strong> $schoolId</p>
            <p><strong>Password:</strong> $password</p>
            <p>Please visit the library PC to log in and change your password immediately for security purposes. You may also access our library system through our school wifi at: <a href='http://192.168.8.26/library-system/user/'>192.168.8.26/library-system/user/</a></p>
            <p>Note: This is an auto-generated email. Please do not reply to this email address.</p>
            <p>Thank you for using the NBS College Library System.</p>
            <p>Best regards,</p>
            <p><strong>NBS College Library System Team</strong></p>
        ";
        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent to $email. Error: {$mail->ErrorInfo}");
    }
}

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
function generatePassword($length = 10)
{
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
                status, date_added, department
            ) VALUES (
                ?, ?, ?, ?, 
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, NOW(), ?
            )";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssssssssssss",
                $school_id,
                $firstname,
                $middle_init,
                $lastname,
                $email,
                $hashed_password,
                $contact_no,
                $image,
                $usertype,
                $address,
                $id_type,
                $id_image,
                $status,
                $department
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

                // Send email to the user
                sendUserEmail($email, $schoolId, $password, $firstname, $lastname);
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

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">Users List</h1>
        <p class="mb-4">Manage all user accounts in the system.</p>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between align-items-center mb-3">
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
            </div>
            <div>
                <a href="import_users.php" class="btn btn-info btn-sm mr-2">
                    <i class="fas fa-file-import"></i> Import Users
                </a>
                <a href="#" class="btn btn-success btn-sm add-user-btn" data-toggle="modal" data-target="#addUserModal">
                    <i class="fas fa-plus"></i> Add New User
                </a>
            </div>
        </div>

        <!-- Users Table -->
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
<!-- End of container-fluid -->

<!-- Context Menu -->
<div id="contextMenu" class="dropdown-menu" style="display:none; position:absolute;">
    <a class="dropdown-item" href="#" id="viewUser">View Details</a>
    <a class="dropdown-item" href="#" id="updateUser">Update</a>
    <a class="dropdown-item" href="#" id="generatePassword">Generate New Password</a>
    <a class="dropdown-item" href="#" id="banUser">Ban User</a>
    <a class="dropdown-item" href="#" id="disableUser">Disable User</a>
    <a class="dropdown-item" href="#" id="deleteUser">Delete</a>
</div>

</div>
<!-- End of Main Content -->

<!-- Footer -->
<?php include '../Admin/inc/footer.php'; ?>
<!-- End of Footer -->

</div>
<!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

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
                                    <option value="General Education" <?= ($values['department'] ?? '') == 'General Education' ? 'selected' : '' ?>>General Education</option>
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
<!-- Add SheetJS (for Excel export) and jsPDF (for PDF export) before your closing body tag -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<script>
    $(document).ready(function() {
        var selectedUserIds = [];

        var table = $('#usersTable').DataTable({
            "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
            "order": [
                [10, "desc"]
            ],
            "pageLength": 10,
            "lengthMenu": [
                [10, 25, 50, 100, 500, -1],
                [10, 25, 50, 100, 500, "All"]
            ],
            "responsive": false,
            "scrollX": true,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search...",
                "zeroRecords": "No matching users found"
            },
            "columnDefs": [{
                    "orderable": false,
                    "targets": 0
                } // Disable sorting on checkbox column
            ],
            "initComplete": function() {
                // Apply Bootstrap styling to search input
                $('#usersTable_filter input').addClass('form-control form-control-sm');
                $('#usersTable_filter').addClass('d-flex align-items-center');
                $('#usersTable_filter label').append('<i class="fas fa-search ml-2"></i>');

                // Style pagination buttons
                $('.dataTables_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
            },
            "drawCallback": function() {
                // Re-apply row selection highlights after table redraw
                updateRowSelectionState();
            }
        });

        // Add a confirmation dialog when "All" option is selected
        $('#usersTable').on('length.dt', function(e, settings, len) {
            if (len === -1) {
                Swal.fire({
                    title: 'Display All Entries?',
                    text: "Are you sure you want to display all entries? This may cause performance issues.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, display all!'
                }).then((result) => {
                    if (result.dismiss === Swal.DismissReason.cancel) {
                        // If the user cancels, reset the page length to the previous value
                        table.page.len(settings._iDisplayLength).draw();
                    }
                });
            }
        });

        // Force adjusting column widths after initialization
        setTimeout(function() {
            table.columns.adjust();
        }, 100);

        // Add window resize handler to maintain proper column widths
        $(window).on('resize', function() {
            table.columns.adjust();
        });

        var selectedUserId;
        var selectedUserRow;

        $(document).off('contextmenu', '#usersTable tbody tr');
        $(document).off('click', '#contextMenu a');

        $(document).on('contextmenu', '#usersTable tbody tr', function(e) {
            // Prevent context menu on empty placeholder rows (e.g., "No matching users found")
            if ($(this).find('td').length === 1 && $(this).find('td').attr('colspan')) {
                return; // Do nothing if it's a placeholder row
            }
            e.preventDefault();
            selectedUserRow = $(this);
            selectedUserId = $(this).find('td:eq(1)').text().trim();

            $('#contextMenu').css({
                display: 'block',
                left: e.pageX,
                top: e.pageY
            });

            $('#usersTable tbody tr').removeClass('context-active');
            $(this).addClass('context-active');

            return false;
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#contextMenu').length) {
                $('#contextMenu').hide();
                $('#usersTable tbody tr').removeClass('context-active');
            }
        });

        $(document).on('click', '#viewUser', function(e) {
            e.preventDefault();
            window.location.href = `view_user.php?id=${selectedUserId}`;
        });

        $(document).on('click', '#updateUser', function(e) {
            e.preventDefault();
            window.location.href = `edit_user.php?id=${selectedUserId}`;
        });

        $(document).on('click', '#banUser', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Ban User?',
                text: 'Are you sure you want to ban this user?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, ban user'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'update_user_status.php',
                        method: 'POST',
                        data: {
                            userId: selectedUserId,
                            status: 2
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'User Banned!',
                                    text: response.message,
                                    icon: 'success'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                            Swal.fire('Error!', 'An error occurred while processing your request.', 'error');
                        }
                    });
                }
            });
        });

        $(document).on('click', '#disableUser', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Disable User?',
                text: 'Are you sure you want to disable this user?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#6c757d',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, disable user'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'update_user_status.php',
                        method: 'POST',
                        data: {
                            userId: selectedUserId,
                            status: 3
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'User Disabled!',
                                    text: response.message,
                                    icon: 'success'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                            Swal.fire('Error!', 'An error occurred while processing your request.', 'error');
                        }
                    });
                }
            });
        });

        $(document).on('click', '#generatePassword', function(e) {
            e.preventDefault();
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
                        data: {
                            userId: selectedUserId
                        },
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
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
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

        $(document).on('click', '#deleteUser', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Delete User?',
                text: 'Are you sure you want to delete this user? This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'delete_user.php',
                        method: 'GET',
                        data: {
                            id: selectedUserId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message,
                                    icon: 'success'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                            Swal.fire('Error!', 'An error occurred while deleting the user.', 'error');
                        }
                    });
                }
            });
        });

        $('#usersTable tbody').off('click', 'tr');
        $('#usersTable tbody').off('click', 'td');

        $(document).on('click', '#usersTable tbody tr', function(e) {
            if (e.target.type === 'checkbox' || $(e.target).hasClass('btn') ||
                $(e.target).closest('.btn').length || $(e.target).is('a') ||
                $(e.target).closest('.dropdown-menu').length) {
                return;
            }

            const checkbox = $(this).find('.user-checkbox');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        });

        $(document).on('change', '.user-checkbox', function() {
            const userId = parseInt($(this).data('user-id'));

            if ($(this).prop('checked')) {
                if (!selectedUserIds.includes(userId)) {
                    selectedUserIds.push(userId);
                }
            } else {
                selectedUserIds = selectedUserIds.filter(id => id !== userId);
            }

            updateRowSelectionState();
        });

        $('#bulkDeleteBtn').off('click').on('click', function() {
            handleBulkDelete();
        });

        $('#bulkActivateBtn').off('click').on('click', function() {
            handleBulkStatus(1, 'Activate');
        });

        $('#bulkBanBtn').off('click').on('click', function() {
            handleBulkStatus(2, 'Ban');
        });

        $('#bulkDisableBtn').off('click').on('click', function() {
            handleBulkStatus(3, 'Disable');
        });

        function handleBulkDelete() {
            if (selectedUserIds.length === 0) {
                Swal.fire({
                    title: 'No Users Selected',
                    text: 'Please select at least one user to delete.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }

            Swal.fire({
                title: 'Delete Selected Users?',
                text: `Are you sure you want to delete ${selectedUserIds.length} selected user(s)? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'batch_delete_users.php',
                        method: 'POST',
                        data: {
                            user_ids: selectedUserIds
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while processing your request.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        }

        function handleBulkStatus(statusCode, actionName) {
            if (selectedUserIds.length === 0) {
                Swal.fire({
                    title: 'No Users Selected',
                    text: `Please select at least one user to ${actionName.toLowerCase()}.`,
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }

            let confirmButtonColor = '#28a745';
            if (statusCode === 2) confirmButtonColor = '#ffc107';
            if (statusCode === 3) confirmButtonColor = '#6c757d';

            Swal.fire({
                title: `${actionName} Selected Users?`,
                text: `Are you sure you want to ${actionName.toLowerCase()} ${selectedUserIds.length} selected user(s)?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${actionName.toLowerCase()} them!`
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'batch_update_status.php',
                        method: 'POST',
                        data: {
                            user_ids: selectedUserIds,
                            status: statusCode
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: response.message,
                                    icon: 'success'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message,
                                    icon: 'error'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while processing your request.',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        }

        function updateRowSelectionState() {
            $('#usersTable tbody tr').each(function() {
                const checkbox = $(this).find('.user-checkbox');
                const isChecked = checkbox.prop('checked');
                $(this).toggleClass('selected', isChecked);
            });

            const count = selectedUserIds.length;
            $('#selectedCount').text(count);
            $('#selectedActivateCount').text(count);
            $('#selectedBanCount').text(count);
            $('#selectedDisableCount').text(count);

            $('#bulkActivateBtn').prop('disabled', count === 0);
            $('#bulkBanBtn').prop('disabled', count === 0);
            $('#bulkDisableBtn').prop('disabled', count === 0);
            $('#bulkDeleteBtn').prop('disabled', count === 0);
        }

        updateRowSelectionState();

        // Export to Excel
        $('#exportExcel').on('click', function(e) {
            e.preventDefault();
            exportTableToExcel();
        });

        // Export to CSV
        $('#exportCSV').on('click', function(e) {
            e.preventDefault();
            exportTableToCSV();
        });

        // Export to PDF
        $('#exportPDF').on('click', function(e) {
            e.preventDefault();
            exportTableToPDF();
        });

        // Function to export table to Excel
        function exportTableToExcel() {
            // Clone the table to modify it without affecting the original
            const table = $('#usersTable').clone();

            // Remove the checkbox column and any action buttons
            table.find('th:first-child, td:first-child').remove();

            const wb = XLSX.utils.table_to_book(table[0], {
                sheet: "Users"
            });
            XLSX.writeFile(wb, 'users_list_' + new Date().toISOString().slice(0, 10) + '.xlsx');

            Swal.fire({
                title: 'Success!',
                text: 'Users list exported to Excel',
                icon: 'success',
                confirmButtonText: 'OK'
            });
        }

        // Function to export table to CSV
        function exportTableToCSV() {
            // Clone the table to modify it without affecting the original
            const table = $('#usersTable').clone();

            // Remove the checkbox column and any action buttons
            table.find('th:first-child, td:first-child').remove();

            let csv = [];

            // Get headers
            let headers = [];
            table.find('thead th').each(function() {
                headers.push($(this).text().trim());
            });
            csv.push(headers.join(','));

            // Get data rows
            table.find('tbody tr').each(function() {
                let row = [];
                $(this).find('td').each(function() {
                    // Remove any commas from the cell text to avoid CSV parsing issues
                    let text = $(this).text().trim().replace(/,/g, ' ');
                    row.push(text);
                });
                csv.push(row.join(','));
            });

            // Create CSV file and download
            let csvContent = csv.join('\n');
            let blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            let url = URL.createObjectURL(blob);

            let link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'users_list_' + new Date().toISOString().slice(0, 10) + '.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            Swal.fire({
                title: 'Success!',
                text: 'Users list exported to CSV',
                icon: 'success',
                confirmButtonText: 'OK'
            });
        }

        // Function to export table to PDF
        function exportTableToPDF() {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF('landscape');

            // Set document properties
            doc.setProperties({
                title: 'Users List - NBSC Library System',
                subject: 'Library Users',
                author: 'NBSC Library System',
                creator: 'NBSC Library System'
            });

            // Get page dimensions
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const marginLeft = 10;
            const marginRight = 10;
            const availableWidth = pageWidth - marginLeft - marginRight;

            // Draw a colored header background for the entire width of the page
            doc.setFillColor(78, 115, 223);
            doc.rect(0, 0, pageWidth, 20, 'F');

            // Add title with proper styling
            doc.setFontSize(18);
            doc.setFont(undefined, 'bold');
            doc.setTextColor(255, 255, 255); // White text on blue background
            doc.text('NBSC Library - Users List', pageWidth / 2, 14, {
                align: 'center'
            });

            // Add date text
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.setTextColor(0, 0, 0);
            doc.text('Generated on ' + new Date().toLocaleString(), pageWidth / 2, 25, {
                align: 'center'
            });

            // Get table data
            let tableData = [];
            let headers = [];

            // Collect headers (excluding Select and ID columns)
            $('#usersTable thead th').each(function(i) {
                // Skip the Select checkbox column (i=0) and the ID column (i=1)
                if (i !== 0 && i !== 1 && $(this).text() !== 'Actions') {
                    headers.push($(this).text());
                }
            });

            // Get table data from DataTable API
            const dataTable = $('#usersTable').DataTable();
            dataTable.rows({
                search: 'applied'
            }).every(function() {
                const rowData = this.data();
                let row = [];

                // Loop through each cell, starting from column 2 (Physical ID Number)
                // Skip column 0 (checkbox) and column 1 (ID)
                for (let i = 2; i < rowData.length; i++) {
                    // Clean the HTML to get just the text content
                    let cellContent = $('<div>').html(rowData[i]).text().trim();

                    // For Status column, extract the status text
                    if (headers[i - 2] === 'Status') {
                        const statusMatch = rowData[i].match(/<span class="[^"]*">([^<]+)<\/span>/);
                        if (statusMatch && statusMatch[1]) {
                            cellContent = statusMatch[1].trim();
                        }
                    }

                    row.push(cellContent);
                }
                tableData.push(row);
            });

            // Calculate proportional column widths for full page width
            const colCount = headers.length;
            const colWidths = {};

            // Set appropriate column widths based on content type
            for (let i = 0; i < colCount; i++) {
                // Adjust column proportions based on content type
                if (headers[i] === 'Physical ID Number') {
                    colWidths[i] = availableWidth * 0.08;
                } else if (headers[i] === 'Name' || headers[i] === 'Email') {
                    colWidths[i] = availableWidth * 0.22;
                } else if (headers[i].includes('Borrowing') || headers[i].includes('Returned') ||
                    headers[i].includes('Damaged') || headers[i].includes('Lost')) {
                    colWidths[i] = availableWidth * 0.07;
                } else if (headers[i] === 'Status') {
                    colWidths[i] = availableWidth * 0.08;
                } else if (headers[i].includes('Date')) {
                    colWidths[i] = availableWidth * 0.12;
                } else {
                    colWidths[i] = availableWidth * 0.10;
                }
            }

            // Add table to PDF
            doc.autoTable({
                head: [headers],
                body: tableData,
                startY: 35,
                theme: 'striped',
                margin: {
                    left: marginLeft,
                    right: marginRight
                },
                styles: {
                    fontSize: 8,
                    cellPadding: 2,
                    overflow: 'linebreak',
                    halign: 'left'
                },
                headStyles: {
                    fillColor: [78, 115, 223],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                columnStyles: colWidths,
                alternateRowStyles: {
                    fillColor: [240, 240, 240]
                }
            });

            // Add footer with page numbers
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(100);
                doc.text(
                    'Page ' + i + ' of ' + pageCount,
                    doc.internal.pageSize.getWidth() / 2,
                    doc.internal.pageSize.getHeight() - 10, {
                        align: 'center'
                    }
                );
                doc.text(
                    'NBSC Library System - Generated on ' + new Date().toISOString().slice(0, 10),
                    doc.internal.pageSize.getWidth() / 2,
                    doc.internal.pageSize.getHeight() - 5, {
                        align: 'center'
                    }
                );
            }

            // Save the PDF file
            doc.save('users_list_' + new Date().toISOString().slice(0, 10) + '.pdf');
        }
    });

    function copyPassword() {
        const passwordField = document.getElementById('newPassword');
        if (passwordField) {
            passwordField.select();
            document.execCommand('copy');

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
    }
</script>

<style>
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

    .table td,
    .table th {
        white-space: nowrap;
    }

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

    #usersTable th:first-child,
    #usersTable td:first-child {
        text-align: center;
        vertical-align: middle;
    }

    #usersTable .select-all-checkbox,
    #usersTable .user-checkbox {
        margin: 0 auto;
        display: block;
    }

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

    #usersTable th:first-child,
    #usersTable td:first-child {
        width: 40px !important;
        min-width: 40px !important;
    }

    #usersTable tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }

    #usersTable tbody tr {
        cursor: pointer;
    }

    #usersTable tbody tr.context-active {
        background-color: rgba(0, 123, 255, 0.15) !important;
    }

    #contextMenu {
        z-index: 1000;
        min-width: 200px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    #contextMenu .dropdown-item {
        padding: 8px 16px;
        cursor: pointer;
    }

    #contextMenu .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    .user-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
</style>