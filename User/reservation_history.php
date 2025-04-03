<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT 
    r.id, 
    b.title, 
    r.reserve_date, 
    r.ready_date,
    CONCAT(a1.firstname, ' ', a1.lastname) as ready_by_name,
    r.issue_date,
    CONCAT(a2.firstname, ' ', a2.lastname) as issued_by_name,
    r.cancel_date,
    CONCAT(COALESCE(a3.firstname, u2.firstname), ' ', COALESCE(a3.lastname, u2.lastname)) AS cancelled_by_name,
    r.cancelled_by_role,
    r.status 
FROM reservations r 
JOIN books b ON r.book_id = b.id 
LEFT JOIN admins a1 ON r.ready_by = a1.id
LEFT JOIN admins a2 ON r.issued_by = a2.id
LEFT JOIN admins a3 ON (r.cancelled_by = a3.id AND r.cancelled_by_role = 'Admin')
LEFT JOIN users u2 ON (r.cancelled_by = u2.id AND r.cancelled_by_role = 'User')
WHERE r.user_id = ? 
ORDER BY r.reserve_date DESC";

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
        .table-responsive table td,
        .table-responsive table th {
            vertical-align: middle !important;
        }
        .badge {
            display: inline-block;
            white-space: normal;
            text-align: center;
            line-height: 1.2;
        }
        .badge br {
            display: block;
        }
        .badge small {
            display: block;
            margin-top: 3px;
        }
    </style>
</head>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Reservation History</h6>
                <a href="searchbook.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-search"></i> Search Books
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center">ID</th>
                                <th class="text-center">Title</th>
                                <th class="text-center">Reserve Date</th>
                                <th class="text-center">Ready Date</th>
                                <th class="text-center">Issue Date</th>
                                <th class="text-center">Cancel Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-center"><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td class="text-center"><?php echo date('Y-m-d h:i A', strtotime($row['reserve_date'])); ?></td>
                                    <td class="text-center"><?php echo $row['ready_date'] ? date('Y-m-d h:i A', strtotime($row['ready_date'])) : '-'; ?></td>
                                    <td class="text-center"><?php echo $row['issue_date'] ? date('Y-m-d h:i A', strtotime($row['issue_date'])) : '-'; ?></td>
                                    <td class="text-center"><?php echo $row['cancel_date'] ? date('Y-m-d h:i A', strtotime($row['cancel_date'])) : '-'; ?></td>
                                    <td class="text-center">
                                        <?php if ($row["status"] == 'Ready'): ?>
                                            <span class="badge badge-success p-2" 
                                                  data-toggle="tooltip" 
                                                  title="Made ready by: <?php echo htmlspecialchars($row["ready_by_name"]); ?> on <?php echo date('Y-m-d h:i A', strtotime($row["ready_date"])); ?>">
                                                <i class="fas fa-check"></i> READY
                                                <small>Book is ready for pickup</small>
                                            </span>
                                        <?php elseif ($row["status"] == 'Cancelled'): ?>
                                            <span class="badge badge-danger p-2" 
                                                  data-toggle="tooltip" 
                                                  title="Cancelled by: <?php echo htmlspecialchars($row["cancelled_by_name"]); ?> (<?php echo $row["cancelled_by_role"]; ?>)
                                                         on <?php echo date('Y-m-d h:i A', strtotime($row["cancel_date"])); ?>">
                                                <i class="fas fa-times"></i> CANCELLED
                                            </span>
                                        <?php elseif ($row["status"] == 'Issued'): ?>
                                            <span class="badge badge-primary p-2"
                                                  data-toggle="tooltip"
                                                  title="Issued by: <?php echo htmlspecialchars($row["issued_by_name"]); ?> on <?php echo date('Y-m-d h:i A', strtotime($row["issue_date"])); ?>">
                                                <i class="fas fa-book"></i> ISSUED
                                            </span>
                                        <?php elseif ($row["status"] == 'Pending'): ?>
                                            <span class="badge badge-warning p-2">
                                                <i class="fas fa-clock"></i> Pending
                                                <small>Waiting for librarian</small>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary p-2">
                                                <?php echo htmlspecialchars($row["status"]); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
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
        "order": [[2, 'desc']], // Sort by reserve date (newest first)
        "responsive": false,
        "scrollX": true,
        "autoWidth": false,
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
            
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        }
    });
    
    // Adjust table columns on window resize
    $(window).on('resize', function () {
        $('#dataTable').DataTable().columns.adjust();
    });
});
</script>
</body>
</html>