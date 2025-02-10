<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';

$errors = [
    'id' => '',
    'firstname' => '',
    'lastname' => '',
    'email' => '',
    'password' => '',
    'role' => ''
];

$values = [
    'id' => '',
    'firstname' => '',
    'middle_init' => '',
    'lastname' => '',
    'email' => '',
    'password' => '',
    'role' => ''
];



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $firstname = trim($_POST['firstname']);
    $middle_init = trim($_POST['middle_init']) ?? NULL;
    $lastname = trim($_POST['lastname']);
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = "Active";
    $image = '/upload/nbs-login.jpg'; // Default image

    // Store values to retain input data
    $values = compact('id', 'firstname', 'middle_init', 'lastname', 'username', 'password', 'role');

    // ✅ **VALIDATION RULES**
    if (empty($id)) $errors['id'] = "ID is required.";
    if (empty($firstname)) $errors['firstname'] = "First name is required.";
    if (empty($lastname)) $errors['lastname'] = "Last name is required.";
    if (empty($username)) $errors['username'] = "Username is required.";
    if (empty($password) || strlen($password) < 8) $errors['password'] = "Password must be at least 8 characters.";
    if (empty($role)) $errors['role'] = "Role is required.";

    // ✅ **CHECK FOR DUPLICATES**
    if (!array_filter($errors)) { // Proceed ONLY if no validation errors
        $sql_check = "SELECT id, username, firstname, lastname FROM admins WHERE id = ? OR username = ? OR (firstname = ? AND lastname = ?)";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("isss", $id, $username, $firstname, $lastname);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            while ($row = $result_check->fetch_assoc()) {
                if ($row['id'] == $id) $errors['id'] = "This ID is already in use.";
                if ($row['username'] == $username) $errors['username'] = "This username is already taken.";
                if ($row['firstname'] == $firstname && $row['lastname'] == $lastname) {
                    $errors['firstname'] = "An account with this name already exists.";
                    $errors['lastname'] = "An account with this name already exists.";
                }
            }
        } else {
            // ✅ **INSERT NEW USER**
            $hashed_password = $password;
            $sql = "INSERT INTO admins (id, firstname, middle_init, lastname, username, password, image, role, status, date_added)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssssss", $id, $firstname, $middle_init, $lastname, $username, $hashed_password, $image, $role, $status);

            if ($stmt->execute()) {
                // Set the session variable for success message
                $_SESSION['successMessage'] = "User has been added successfully.";
                echo "<script>window.location.href='admin_users.php';</script>"; // Redirect after success
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



$query = "SELECT id, firstname, middle_init, lastname, username, role, status FROM admins";
$result = mysqli_query($conn, $query);

?>


            <!-- Main Content -->
            <div id="content" class="d-flex flex-column min-vh-100">
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Admin Users</h1>

                    <?php if (!empty(array_filter($errors))) : ?>
    <div class="alert alert-danger mx-3 mt-2">
        <strong>Error!</strong> Failed to add user. Please check the form for errors and try again.
    </div>
<?php elseif (isset($_SESSION['successMessage'])) : ?>
    <div class="alert alert-success mx-3 mt-2">
        <strong>Success!</strong> <?= $_SESSION['successMessage'] ?>
    </div>
    <?php unset($_SESSION['successMessage']); // Unset the session variable after displaying the message ?>
<?php endif; ?>



                    <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
    <h6 class="m-0 font-weight-bold text-primary">List of Admins</h6>
    <button class="btn btn-success" data-toggle="modal" data-target="#addUserModal">
        <i class="fas fa-plus"></i> Add User
    </button>



        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Operation</th>
                    </tr>
                </thead>

                    <tbody>

                 <?php
                        while ($row = mysqli_fetch_assoc($result)) {
                            $full_name = $row['firstname'] . ' ' . $row['middle_init'] . ' ' . $row['lastname'];
                            echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$full_name}</td>
                                    <td>{$row['username']}</td>
                                    <td>{$row['role']}</td>
                                    <td>{$row['status']}</td>
                                    <td>

                                    </td>
                                </tr>";
                        }
                    ?>



                    </tbody>
                            </table>
                        </div>
                    </div>
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
<div class="modal fade <?php if ($formSubmitted) echo 'show d-block'; ?>" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
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
                        <label>ID</label>
                        <input type="number" name="id" class="form-control" value="<?= $values['id'] ?>">
                        <small class="text-danger"><?= $errors['id'] ?></small>
                    </div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="firstname" class="form-control" value="<?= $values['firstname'] ?>">
                        <small class="text-danger"><?= $errors['firstname'] ?></small>
                    </div>
                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" name="middle_init" class="form-control" value="<?= $values['middle_init'] ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lastname" class="form-control" value="<?= $values['lastname'] ?>">
                        <small class="text-danger"><?= $errors['lastname'] ?></small>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="<?= $values['username'] ?>">
                        <small class="text-danger"><?= $errors['username'] ?></small>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control">
                        <small class="text-danger"><?= $errors['password'] ?></small>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <option value="" disabled <?= empty($values['role']) ? 'selected' : '' ?>>Select Role</option>
                            <option value="Admin" <?= $values['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="Librarian" <?= $values['role'] == 'Librarian' ? 'selected' : '' ?>>Librarian</option>
                            <option value="Encoder" <?= $values['role'] == 'Encoder' ? 'selected' : '' ?>>Encoder</option>
                        </select>
                        <small class="text-danger"><?= $errors['role'] ?></small>
                    </div>
                    <input type="hidden" name="status" value="Active">
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
    $(document).ready(function () {
        var table = $('#dataTable').DataTable({
            "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
            "pagingType": "simple_numbers",
            "language": {
                "search": "Search:" // Keeps the default 'Search:' label
            }
        });

        // Style the search input with Bootstrap
        $('#dataTable_filter input')
            .addClass('form-control')
            .attr("placeholder", "Search...");

        // Add a trailing search icon inside the search field
        $('#dataTable_filter input').wrap('<div class="input-group"></div>');  // Wrap input field with input group
        $('#dataTable_filter').append('<div class="input-group-append"><span class="input-group-text"><i class="fa fa-search"></i></span></div>');

        // Add a label next to the search field (without removing the default "Search:" label)
        $('#dataTable_filter').append('<label class="ml-2 font-weight-bold">Search</label>');

        // Fix pagination buttons styling & spacing
        $('.dataTables_paginate .paginate_button')
            .addClass('btn btn-sm btn-outline-primary mx-1');
    });

</script>