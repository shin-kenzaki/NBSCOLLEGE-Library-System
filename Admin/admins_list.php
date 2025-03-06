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



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id']; 
    $firstname = trim($_POST['firstname']);
    $middle_init = trim($_POST['middle_init']) ?? NULL;
    $lastname = trim($_POST['lastname']);
    $email = $_POST['email']; 
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = null;
    $image = '/upload/nbs-login.jpg';

    // Store values to retain input data
    $values = compact('employee_id', 'firstname', 'middle_init', 'lastname', 'email', 'password', 'role');

    // ✅ **VALIDATION RULES**
    if (empty($employee_id)) $errors['employee_id'] = "Employee ID is required.";
    if (empty($firstname)) $errors['firstname'] = "First name is required.";
    if (empty($lastname)) $errors['lastname'] = "Last name is required.";
    if (empty($email)) $errors['email'] = "Email is required.";
    if (empty($password) || strlen($password) < 8) $errors['password'] = "Password must be at least 8 characters.";
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
                echo "<script>
                    alert('User has been added successfully.');
                    window.location.href='admins_list.php';
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
</style>

            <!-- Main Content -->
            <div id="content" class="d-flex flex-column min-vh-100">
                <div class="container-fluid">
                    



                    <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <div class="title-section">
                        <h6 class="m-0 font-weight-bold text-primary">List of Admins</h6>
                    </div>
                    <div class="btn-group">
                        <button id="activateSelected" class="btn btn-outline-success btn-sm" disabled>
                            Activate (<span id="selectedActivateCount">0</span>)
                        </button>
                        <button id="deactivateSelected" class="btn btn-outline-secondary btn-sm" disabled>
                            Deactivate (<span id="selectedDeactivateCount">0</span>)
                        </button>
                        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addUserModal">
                            <i class="fas fa-plus"></i> Add Admin
                        </button>
                    </div>
                </div>
                <div class="card-body px-0">
                    <div class="table-responsive px-3">
                        <!-- Remove the custom search form -->
                        <table class="table table-bordered" id="adminsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
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
            </div>

            <!-- Context Menu -->
            <div id="contextMenu" class="dropdown-menu" style="display:none; position:absolute;">
                <a class="dropdown-item" href="#" id="viewAdmin">View Details</a>
                <a class="dropdown-item" href="#" id="updateAdmin">Update</a>
                <a class="dropdown-item" href="#" id="deleteAdmin">Delete</a>
            </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include '../Admin/inc/footer.php'?>
            <!-- End of Footer -->



    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

<!-- Add User Modal -->
<div class="modal fade <?php if (isset($formSubmitted) && $formSubmitted) echo 'show d-block'; ?>" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New Admin</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            <form id="addUserForm" action="" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="text" name="employee_id" class="form-control" value="<?= htmlspecialchars($values['employee_id'] ?? '') ?>">
                        <small class="text-danger"><?= $errors['employee_id'] ?? '' ?></small>
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
        "order": [[7, "desc"]], 
        "pageLength": 10,
        "responsive": false,
        "scrollX": true,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        },
        "initComplete": function() {
            // Style the search input with Bootstrap
            $('#adminsTable_filter input').addClass('form-control form-control-sm');
            // Add search icon
            $('#adminsTable_filter').addClass('d-flex align-items-center');
            $('#adminsTable_filter label').append('<i class="fas fa-search ml-2"></i>');
            // Fix pagination buttons styling & spacing
            $('.dataTables_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
        }
    });

    // Add window resize handler
    $(window).on('resize', function () {
        table.columns.adjust();
    });

    // Function to update selected admin IDs
    function updateSelectedAdminIds() {
        selectedAdminIds = [];
        $('.selectRow:checked').each(function() {
            var adminId = $(this).closest('tr').find('td:nth-child(2)').text();
            if (!selectedAdminIds.includes(adminId)) {
                selectedAdminIds.push(adminId);
            }
        });

        $.post('selected_admins.php', {
            selectedAdminIds: selectedAdminIds
        }, function(response) {
            fetchAdmins();
        }, 'json');
    }

    // Function to fetch and reload admin data
    function fetchAdmins() {
        var searchQuery = $('input[name="search"]').val();
        $.ajax({
            url: 'fetch_admins.php',
            type: 'GET',
            data: { search: searchQuery },
            success: function(response) {
                table.clear();
                $('#adminsTable tbody').html(response);
                table.draw();
                restoreSelectedState();
            }
        });
    }

    // Select All functionality
    $('#selectAll').click(function() {
        $('.selectRow').prop('checked', this.checked);
        updateSelectedCount();
    });

    $('.selectRow').click(function() {
        if ($('.selectRow:checked').length == $('.selectRow').length) {
            $('#selectAll').prop('checked', true);
        } else {
            $('#selectAll').prop('checked', false);
        }
        updateSelectedCount();
    });

    // Context Menu
    var selectedAdminId;

    $('#adminsTable tbody').on('contextmenu', 'tr', function(e) {
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
    $('#selectAll').click(function() {
        $('.selectRow').prop('checked', this.checked);
        updateSelectedCount();
    });

    $('.selectRow').click(function() {
        if ($('.selectRow:checked').length == $('.selectRow').length) {
            $('#selectAll').prop('checked', true);
        } else {
            $('#selectAll').prop('checked', false);
        }
        updateSelectedCount();
    });
});
</script>