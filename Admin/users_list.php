<?php
session_start();
include('inc/header.php');
include('../db.php');

if (!isset($_SESSION['admin_id'])) {
    header('location:login.php');
    exit();
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
                <a href="add_user.php" class="btn btn-success btn-sm">
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

    $('#deleteUser').click(function() {
        if (confirm('Are you sure you want to delete this user?')) {
            window.location.href = `delete_user.php?id=${selectedUserId}`;
        }
    });
});
</script>

<?php include('inc/footer.php'); ?>
