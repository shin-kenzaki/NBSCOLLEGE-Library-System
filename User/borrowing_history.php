<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT b.title, br.issue_date, br.due_date, br.return_date, 
                 br.report_date, br.replacement_date, br.status,
                 CONCAT(a1.firstname, ' ', a1.lastname) AS issued_by_name, 
                 a2.firstname AS received_by_name
          FROM borrowings br 
          JOIN books b ON br.book_id = b.id 
          LEFT JOIN admins a1 ON br.issued_by = a1.id
          LEFT JOIN admins a2 ON br.recieved_by = a2.id
          WHERE br.user_id = ? AND br.status != 'Active'";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

include 'inc/header.php';
?>

<head>
    <style>
        .dataTables_filter input {
            width: 400px; 
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-bottom: 1rem; 
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }
        table.dataTable {
            width: 100% !important;
        }
        @media screen and (max-width: 767px) {
            .table-responsive {
                border: none;
                margin-bottom: 15px;
            }
        }
        .table-responsive table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        table.dataTable td:nth-child(4) span {
            display: inline-block;
        }
        /* Change the way Return/Report Date is displayed to be single line */
        table.dataTable td br {
            display: none;
        }
        table.dataTable td:nth-child(4) {
            white-space: nowrap;
        }
        .table-responsive table td,
        .table-responsive table th {
            vertical-align: middle !important;
        }
    </style>
</head>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Borrowing History</h6>
                <a href="searchbook.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-search"></i> Search Books
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center">Title</th>
                                <th class="text-center">Issue Date</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-center">Return/Report Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Issued By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td class="text-center"><?php echo date('M d, Y', strtotime($row['issue_date'])); ?></td>
                                    <td class="text-center"><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                    <td class="text-center">
                                        <?php 
                                        if ($row['status'] == 'Lost' || $row['status'] == 'Damaged') {
                                            echo 'Reported: ' . date('M d, Y', strtotime($row['report_date']));
                                            if ($row['replacement_date']) {
                                                echo ' | Replaced: ' . date('M d, Y', strtotime($row['replacement_date']));
                                            }
                                        } else {
                                            echo 'Returned: ' . date('M d, Y', strtotime($row['return_date']));
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['status']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['issued_by_name']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search within results..."
        },
        "pageLength": 10,
        "order": [[1, 'desc']],
        "responsive": false, // Disable responsive mode to remove dropdown arrows
        "scrollX": true, // Keep horizontal scrolling
        "autoWidth": false, // Fixed width columns for better control
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
        }
    });
});
</script>