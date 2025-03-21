<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT r.id, b.title, r.reserve_date, r.recieved_date, r.cancel_date,
          CASE
              WHEN r.recieved_date IS NOT NULL THEN 'Received'
              WHEN r.cancel_date IS NOT NULL THEN 'Cancelled'
              ELSE 'Unknown'
          END AS status_text
          FROM reservations r
          JOIN books b ON r.book_id = b.id
          WHERE r.user_id = ? AND (r.recieved_date IS NOT NULL OR r.cancel_date IS NOT NULL)";
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
    </style>
</head>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Reservation History</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center">Title</th>
                                <th class="text-center">Reserve Date</th>
                                <th class="text-center">Received Date</th>
                                <th class="text-center">Cancel Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td class="text-center"><?php echo date('Y-m-d h:i A', strtotime($row['reserve_date'])); ?></td>
                                    <td class="text-center"><?php echo $row['recieved_date'] ? date('Y-m-d h:i A', strtotime($row['recieved_date'])) : '-'; ?></td>
                                    <td class="text-center"><?php echo $row['cancel_date'] ? date('Y-m-d h:i A', strtotime($row['cancel_date'])) : '-'; ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['status_text']); ?></td>
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
        "order": [[0, 'asc']], // Sort by ID by default
        "responsive": false, // Disable responsive mode to remove dropdown arrows
        "scrollX": true, // Keep horizontal scrolling
        "autoWidth": false, // Fixed width columns for better control
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
        }
    });
});
</script>
</body>
</html>