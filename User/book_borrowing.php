<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch active and overdue borrowings
$query = "SELECT b.id, bk.accession, bk.title, bk.shelf_location, b.issue_date, b.due_date, b.status,
                 a1.firstname AS issued_by_name, a2.firstname AS received_by_name 
          FROM borrowings b 
          JOIN books bk ON b.book_id = bk.id 
          LEFT JOIN admins a1 ON b.issued_by = a1.id
          LEFT JOIN admins a2 ON b.recieved_by = a2.id
          WHERE b.user_id = ? AND b.status IN ('Active', 'Overdue')";

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
    </style>
</head>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Active Borrowings</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center">Accession</th>
                                <th class="text-center">Title</th>
                                <th class="text-center">Shelf Location</th>
                                <th class="text-center">Issue Date</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-center">Days Remaining</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Issued By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): 
                                while ($row = $result->fetch_assoc()): 
                                    $due_date = new DateTime($row['due_date']);
                                    $today = new DateTime();
                                    $days_remaining = $today->diff($due_date)->days;
                                    $is_overdue = $today > $due_date;
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo htmlspecialchars($row['accession']); ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['shelf_location']); ?></td>
                                    <td class="text-center"><?php echo date('Y-m-d', strtotime($row['issue_date'])); ?></td>
                                    <td class="text-center"><?php echo date('Y-m-d', strtotime($row['due_date'])); ?></td>
                                    <td class="text-center">
                                        <?php if ($is_overdue): ?>
                                            <span class="text-danger">Overdue by <?php echo $days_remaining; ?> day(s)</span>
                                        <?php else: ?>
                                            <?php echo $days_remaining; ?> days
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($is_overdue): ?>
                                            <span class="badge badge-danger">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['issued_by_name']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                            <?php endif; ?>
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
        "responsive": true,
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
        }
    });
});
</script>