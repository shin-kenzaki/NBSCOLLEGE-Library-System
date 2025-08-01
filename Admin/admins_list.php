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
    'employee_id' => '',
    'firstname' => '',
    'lastname' => '',
    'email' => '',
    'password' => '',
    'role' => '',
];
$values = [
    'employee_id' => '',
    'firstname' => '',
    'middle_init' => '',
    'lastname' => '',
    'email' => '',
    'password' => '',
    'role' => '',
];

// Fix the password generation function
function generatePassword($length = 10)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $length > $i; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'];
    $firstname = trim($_POST['firstname']);
    $middle_init = trim($_POST['middle_init']) ?? NULL;
    $lastname = trim($_POST['lastname']);
    $email = $_POST['email'];
    $role = $_POST['role'];
    $status = 1; // Set status to active by default
    $image = '/upload/nbs-login.jpg';

    // Generate password automatically
    $password = generatePassword(12); // 12 characters long

    // Store values to retain input data
    $values = compact('employee_id', 'firstname', 'middle_init', 'lastname', 'email', 'role');

    // ✅ **VALIDATION RULES**
    if (empty($employee_id)) $errors['employee_id'] = "Employee ID is required.";
    if (empty($firstname)) $errors['firstname'] = "First name is required.";
    if (empty($lastname)) $errors['lastname'] = "Last name is required.";
    if (empty($email)) $errors['email'] = "Email is required.";
    if (empty($role)) $errors['role'] = "Role is required.";

    // ✅ **CHECK FOR DUPLICATES**
    if (!array_filter($errors)) { // Proceed ONLY if no validation errors
        $sql_check = "SELECT employee_id, email, firstname, lastname FROM admins WHERE employee_id = ? OR email = ? OR (firstname = ? AND lastname = ?)";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ssss", $employee_id, $email, $firstname, $lastname);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            while ($row = $result_check->fetch_assoc()) {
                if ($row['employee_id'] == $employee_id) $errors['employee_id'] = "This Employee ID is already in use.";
                if ($row['email'] == $email) $errors['email'] = "This email is already taken.";
                if ($row['firstname'] == $firstname && $row['lastname'] == $lastname) {
                    $errors['firstname'] = "An account with this name already exists.";
                    $errors['lastname'] = "An account with this name already exists.";
                }
            }
        } else {
            // ✅ **INSERT NEW USER**
            $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password
            $sql = "INSERT INTO admins (employee_id, firstname, middle_init, lastname, email, password, image, role, status, date_added) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssi", $employee_id, $firstname, $middle_init, $lastname, $email, $hashed_password, $image, $role, $status);

            if ($stmt->execute()) {
                // Insert update for new admin
                $admin_id = $_SESSION['admin_employee_id'];
                $admin_role = $_SESSION['role'];
                $admin_fullname = $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname'];
                $new_admin_fullname = $firstname . ' ' . ($middle_init ? $middle_init . ' ' : '') . $lastname;
                $update_title = "$admin_role $admin_fullname Registered an Admin";
                $update_message = "$admin_role $admin_fullname Registered $new_admin_fullname as $role";
                $update_sql = "INSERT INTO updates (user_id, role, title, message, `update`) VALUES (?, ?, ?, ?, NOW())";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("isss", $admin_id, $admin_role, $update_title, $update_message);
                $update_stmt->execute();
                $update_stmt->close();

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        html: `
                            <div class='text-center'>
                                <p>Admin added successfully!</p>
                                <p><strong>Employee ID:</strong> " . $employee_id . "</p>
                                <p><strong>Generated Password:</strong> " . $password . "</p>
                                <p class='text-danger'><small>Please make sure to copy this information now!</small></p>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'admins_list.php';
                        }
                    });
                </script>";
                exit;
            } else {
                echo "<script>alert('Error adding user.');</script>";
            }

            $stmt->close();
        }

        $stmt_check->close();
    } else {
        $formSubmitted = true; // Set flag to keep modal open
    }
}

$query = "SELECT id, employee_id, firstname, middle_init, lastname, email, role, status, date_added, last_update FROM admins";
$result = mysqli_query($conn, $query);

?>

<style>
    /* Add responsive table styles */
    .table-responsive {
        width: 100%;
        margin-bottom: 1rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #adminsTable th,
    #adminsTable td {
        min-width: 100px;
        white-space: nowrap;
    }

    #adminsTable {
        width: 100% !important;
    }

    .table td,
    .table th {
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
            grid-template-columns: 1fr 1fr;
            width: 100%;
        }

        .card-header .btn {
            margin: 2px !important;
            white-space: nowrap;
            justify-content: center;
        }

        .card-header h6 {
            text-align: center;
            margin-bottom: 10px !important;
        }
    }

    /* Updated card header styles */
    .card-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        padding: 1rem;
    }

    .card-header .title-section {
        flex: 1;
        min-width: 200px;
    }

    .card-header .btn-group {
        display: flex;
        gap: 5px;
        flex-wrap: nowrap;
    }

    @media (max-width: 768px) {
        .card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .card-header .title-section {
            text-align: center;
            margin-bottom: 10px;
        }

        .card-header .btn-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            width: 100%;
        }

        .card-header .btn {
            margin: 2px !important;
            padding: 0.375rem 0.5rem;
            font-size: 0.875rem;
            white-space: nowrap;
            width: 100%;
            justify-content: center;
        }
    }

    /* Center checkboxes in cells */
    #adminsTable th:first-child,
    #adminsTable td:first-child {
        text-align: center;
        vertical-align: middle;
    }

    /* Make sure checkbox inputs are centered */
    #adminsTable #selectAll,
    #adminsTable .selectRow {
        margin: 0 auto;
        display: block;
    }

    /* Enhanced hover effect for checkbox column */
    #adminsTable tbody tr td:first-child,
    #adminsTable thead tr th:first-child {
        cursor: pointer;
        transition: background-color 0.2s ease;
        width: 40px !important;
        min-width: 40px !important;
    }

    #adminsTable tbody tr td:first-child:hover,
    #adminsTable thead tr th:first-child:hover {
        background-color: rgba(0, 123, 255, 0.15);
    }

    /* Add a hover effect to the entire row */
    #adminsTable tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }

    /* Style for active/selected rows */
    #adminsTable tbody tr.selected td:first-child,
    #adminsTable tbody tr.context-menu-active td:first-child {
        background-color: rgba(0, 123, 255, 0.25);
    }

    /* Enhanced table striping with hover preservation */
    #adminsTable.table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.03);
    }

    #adminsTable.table-striped tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05) !important;
    }

    /* Ensure checkbox cell hover takes precedence over striping */
    #adminsTable tbody tr td:first-child:hover,
    #adminsTable thead tr th:first-child:hover {
        background-color: rgba(0, 123, 255, 0.15) !important;
    }

    /* Make header row stand out more */
    #adminsTable thead th {
        background-color: #f8f9fc;
        border-bottom: 2px solid #e3e6f0;
    }

    /* Maintain style for active/selected rows */
    #adminsTable tbody tr.selected,
    #adminsTable tbody tr.context-menu-active {
        background-color: rgba(0, 123, 255, 0.08) !important;
    }

    /* Fix empty table messages styling */
    .dataTables_empty {
        padding: 50px 0 !important;
        text-align: center !important;
        font-weight: 500 !important;
        color: #6c757d !important;
        background-color: #f8f9fc !important;
        border-bottom: none !important;
    }

    /* Selected row styling */
    #adminsTable tbody tr.selected {
        background-color: rgba(0, 123, 255, 0.1) !important;
    }

    #adminsTable.table-striped tbody tr.selected:nth-of-type(odd),
    #adminsTable.table-striped tbody tr.selected:nth-of-type(even) {
        background-color: rgba(0, 123, 255, 0.1) !important;
    }
</style>

<!-- Main Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Admin Management</h1>
    <p class="mb-4">Manage all admin accounts in the system.</p>

    <!-- Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <button id="activateSelected" class="btn btn-outline-success btn-sm" disabled>
                Activate (<span id="selectedActivateCount">0</span>)
            </button>
            <button id="deactivateSelected" class="btn btn-outline-secondary btn-sm" disabled>
                Deactivate (<span id="selectedDeactivateCount">0</span>)
            </button>
        </div>
        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addUserModal">
            <i class="fas fa-plus"></i> Add Admin
        </button>
    </div>

    <!-- Admins Table -->
    <div class="table-responsive px-3">
        <table class="table table-bordered table-striped" id="adminsTable" width="100%" cellspacing="0">
            <thead>
                <tr>
                    <th class="text-center" id="selectAllHeader" style="width: 40px;">Select</th>
                    <th class="text-center">ID</th>
                    <th class="text-center">Employee ID</th>
                    <th class="text-center">Name</th>
                    <th class="text-center">Email</th>
                    <th class="text-center">Role</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Date Added</th>
                    <th class="text-center">Last Update</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Direct admin retrieval
                $query = "SELECT * FROM admins ORDER BY date_added DESC";
                $result = $conn->query($query);

                while ($row = $result->fetch_assoc()) {
                    $fullname = $row['firstname'] . ' ' . ($row['middle_init'] ? $row['middle_init'] . ' ' : '') . $row['lastname'];
                    list($status_class, $status_text) = getStatusDisplay($row['status']);

                    echo "<tr>";
                    echo "<td style='text-align: center;'><input type='checkbox' class='selectRow'></td>";
                    echo "<td style='text-align: center;'>{$row['id']}</td>";
                    echo "<td style='text-align: center;'>{$row['employee_id']}</td>";
                    echo "<td>" . htmlspecialchars($fullname) . "</td>";
                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                    echo "<td style='text-align: center;'>{$row['role']}</td>";
                    echo "<td style='text-align: center;'><span class='badge {$status_class}'>{$status_text}</span></td>";
                    echo "<td style='text-align: center;'>" . date('M d, Y', strtotime($row['date_added'])) . "</td>";
                    echo "<td style='text-align: center;'>" . ($row['last_update'] ? date('M d, Y', strtotime($row['last_update'])) : 'Never') . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>
<!-- End of container-fluid -->

<!-- Context Menu -->
<div id="contextMenu" class="dropdown-menu" style="display:none; position:absolute;">
    <a class="dropdown-item" id="viewAdmin">View Details</a>
    <a class="dropdown-item" id="updateAdmin">Update</a>
    <a class="dropdown-item" id="generatePassword">Generate New Password</a>
    <a class="dropdown-item" id="deleteAdmin">Delete</a>
</div>

<!-- Footer -->
<?php include '../Admin/inc/footer.php' ?>
<!-- End of Footer -->

</div>
<!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Add User Modal -->
<div class="modal fade <?php if (isset($formSubmitted) && $formSubmitted) echo 'show d-block'; ?>" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New Admin</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addUserForm" action="" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="text" name="employee_id" class="form-control" value="<?= htmlspecialchars($values['employee_id'] ?? '') ?>">
                        <small class="text-danger"><?= $errors['employee_id'] ?? '' ?></small>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($values['firstname'] ?? '') ?>">
                                <small class="text-danger"><?= $errors['firstname'] ?? '' ?></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Middle Initial</label>
                                <input type="text" name="middle_init" class="form-control" value="<?= htmlspecialchars($values['middle_init'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
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
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <option value="" disabled <?= empty($values['role']) ? 'selected' : '' ?>>Select Role</option>
                            <option value="Admin" <?= ($values['role'] ?? '') == 'Admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="Librarian" <?= ($values['role'] ?? '') == 'Librarian' ? 'selected' : '' ?>>Librarian</option>
                            <option value="Assistant" <?= ($values['role'] ?? '') == 'Assistant' ? 'selected' : '' ?>>Assistant</option>
                            <option value="Encoder" <?= ($values['role'] ?? '') == 'Encoder' ? 'selected' : '' ?>>Encoder</option>
                        </select>
                        <small class="text-danger"><?= $errors['role'] ?? '' ?></small>
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
        var selectedAdminIds = <?php echo json_encode(isset($_SESSION['selectedAdminIds']) ? $_SESSION['selectedAdminIds'] : []); ?>;

        // Revert to default DataTables with search
        var table = $('#adminsTable').DataTable({
            "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
            "order": [
                [7, "desc"]
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
                "emptyTable": "No admin accounts found in the database",
                "zeroRecords": "No matching admin accounts found"
            },
            "columnDefs": [{
                    "orderable": false,
                    "targets": 0
                } // Disable sorting on checkbox column
            ],
            "initComplete": function() {
                // Style the search input with Bootstrap
                $('#adminsTable_filter input').addClass('form-control form-control-sm');
                // Add search icon
                $('#adminsTable_filter').addClass('d-flex align-items-center');
                $('#adminsTable_filter label').append('<i class="fas fa-search ml-2"></i>');
                // Fix pagination buttons styling & spacing
                $('.dataTables_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
            },
            "drawCallback": function() {
                // Update row selection visuals after table redraw
                updateAdminRowSelectionVisuals();
            }
        });

        // Add a confirmation dialog when "All" option is selected
        $('#adminsTable').on('length.dt', function(e, settings, len) {
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

        // Add window resize handler
        $(window).on('resize', function() {
            table.columns.adjust();
        });

        // Function to fetch and reload admin data
        function fetchAdmins() {
            var searchQuery = $('input[name="search"]').val();
            $.ajax({
                url: 'fetch_admins.php',
                type: 'GET',
                data: {
                    search: searchQuery
                },
                success: function(response) {
                    table.clear();
                    $('#adminsTable tbody').html(response);
                    table.draw();
                    restoreSelectedState();
                }
            });
        }

        // Keep context menu functionality
        var selectedAdminId;

        $('#adminsTable tbody').on('contextmenu', 'tr', function(e) {
            // Prevent context menu on empty placeholder rows (e.g., "No admin accounts found")
            if ($(this).find('td').length === 1 && $(this).find('td').attr('colspan')) {
                return; // Do nothing if it's a placeholder row
            }
            e.preventDefault();
            $('#adminsTable tbody tr').removeClass('context-menu-active');
            $(this).addClass('context-menu-active');
            selectedAdminId = $(this).find('td:nth-child(2)').text();
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
            $('#adminsTable tbody tr').removeClass('context-menu-active');
        });

        // Context Menu Actions
        $('#viewAdmin').click(function() {
            window.location.href = `view_admin.php?id=${selectedAdminId}`;
        });

        $('#updateAdmin').click(function() {
            window.location.href = `edit_admin.php?id=${selectedAdminId}`;
        });

        // Add new generate password action
        $('#generatePassword').click(function() {
            Swal.fire({
                title: 'Generate New Password?',
                text: 'Are you sure you want to generate a new password for this admin?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, generate new password'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'generate_admin_password.php',
                        method: 'POST',
                        data: {
                            adminId: selectedAdminId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Password Generated!',
                                    html: `
                                    <div class='text-center'>
                                        <p>A new password has been generated.</p>
                                        <p><strong>Employee ID:</strong> ${response.employee_id}</p>
                                        <p><strong>Admin:</strong> ${response.admin_name}</p>
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

        // Update delete admin with SweetAlert
        $('#deleteAdmin').click(function() {
            Swal.fire({
                title: 'Delete Admin?',
                text: 'Are you sure you want to delete this admin? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `delete_admin.php?id=${selectedAdminId}`,
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message,
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error occurred while deleting admin'
                            });
                        }
                    });
                }
            });
        });

        // Batch Actions
        $('#batchDelete').click(function() {
            var selectedIds = [];
            $('.selectRow:checked').each(function() {
                selectedIds.push($(this).closest('tr').find('td:nth-child(2)').text());
            });

            if (selectedIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select admins to delete.'
                });
                return;
            }

            Swal.fire({
                title: 'Delete Selected Admins?',
                text: `Are you sure you want to delete ${selectedIds.length} selected admin(s)? This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'batch_delete_admins.php',
                        type: 'POST',
                        data: {
                            admin_ids: selectedIds
                        },
                        success: function(response) {
                            try {
                                const result = JSON.parse(response);
                                if (result.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: result.message,
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: result.message
                                    });
                                }
                            } catch (e) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An unexpected error occurred'
                                });
                            }
                        }
                    });
                }
            });
        });

        // Add new Activate Selected functionality
        $('#activateSelected').click(function() {
            var selectedIds = [];
            $('.selectRow:checked').each(function() {
                selectedIds.push($(this).closest('tr').find('td:nth-child(2)').text());
            });

            if (selectedIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select admins to activate.'
                });
                return;
            }

            Swal.fire({
                title: 'Activate Selected Admins?',
                text: `Are you sure you want to activate ${selectedIds.length} selected admin(s)?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, activate them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'batch_activate_admins.php',
                        type: 'POST',
                        data: {
                            admin_ids: selectedIds
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Activated!',
                                    text: 'Selected admins have been activated successfully.',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while processing your request.'
                            });
                        }
                    });
                }
            });
        });

        // Add deactivate functionality
        $('#deactivateSelected').click(function() {
            var selectedIds = [];
            $('.selectRow:checked').each(function() {
                selectedIds.push($(this).closest('tr').find('td:nth-child(2)').text());
            });

            if (selectedIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select admins to deactivate.'
                });
                return;
            }

            Swal.fire({
                title: 'Deactivate Selected Admins?',
                text: `Are you sure you want to deactivate ${selectedIds.length} selected admin(s)?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6c757d',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, deactivate them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'batch_deactivate_admins.php',
                        type: 'POST',
                        data: {
                            admin_ids: selectedIds
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deactivated!',
                                    text: 'Selected admins have been deactivated successfully.',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while processing your request.'
                            });
                        }
                    });
                }
            });
        });

        // Update selected count function
        function updateSelectedCount() {
            const checkedBoxes = $('.selectRow:checked').length;
            $('#selectedActivateCount').text(checkedBoxes);
            $('#selectedDeactivateCount').text(checkedBoxes);
            $('#activateSelected').prop('disabled', checkedBoxes === 0);
            $('#deactivateSelected').prop('disabled', checkedBoxes === 0);
        }

        // Update checkbox handlers to use the new count function
        $('.selectRow').click(function() {
            updateSelectedCount();
        });

        // Add row click functionality for selection
        $('#adminsTable tbody').on('click', 'tr', function(e) {
            // Skip if clicking directly on checkbox or interactive elements
            if (e.target.type === 'checkbox' || $(e.target).hasClass('btn') ||
                $(e.target).closest('.btn').length || $(e.target).is('a')) {
                return;
            }

            // Toggle the row's checkbox
            const checkbox = $(this).find('.selectRow');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        });

        // Enable cell clicks for row selection
        $('#adminsTable tbody').on('click', 'td', function(e) {
            // Skip if clicking directly on the checkbox or interactive elements
            if (e.target.type === 'checkbox' || $(e.target).hasClass('btn') ||
                $(e.target).closest('.btn').length || $(e.target).is('a')) {
                return;
            }

            // Prevent triggering multiple handlers
            e.stopPropagation();

            // Toggle the row's checkbox
            const checkbox = $(this).closest('tr').find('.selectRow');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        });

        // Add visual styling for selectable rows
        $('<style>')
            .text(`
            #adminsTable tbody tr {
                cursor: pointer;
            }
            #adminsTable tbody tr.selected {
                background-color: rgba(0, 123, 255, 0.1) !important;
            }
            #adminsTable tbody tr.selected td {
                background-color: rgba(0, 123, 255, 0.05);
            }
        `)
            .appendTo('head');

        // Function to update row selection visual state
        function updateAdminRowSelectionVisuals() {
            $('#adminsTable tbody tr').each(function() {
                const isChecked = $(this).find('.selectRow').prop('checked');
                $(this).toggleClass('selected', isChecked);
            });
        }

        // Listen for checkbox changes to update visuals
        $(document).on('change', '.selectRow', function() {
            updateAdminRowSelectionVisuals();
            updateSelectedCount();
        });

        // Initialize selection visuals on page load
        updateAdminRowSelectionVisuals();
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