<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Updated query to include ISBN, Series, Volume, Part, and Edition
$query = "SELECT 
    br.id, 
    b.title,
    b.ISBN,
    b.series,
    b.volume,
    b.part,
    b.edition,
    br.issue_date, 
    br.due_date, 
    br.return_date,
    br.status,
    DATEDIFF(IFNULL(br.return_date, CURRENT_DATE), br.issue_date) as days_borrowed,
    CASE 
        WHEN br.return_date > br.due_date THEN DATEDIFF(br.return_date, br.due_date)
        ELSE 0
    END as days_overdue
FROM borrowings br 
JOIN books b ON br.book_id = b.id
WHERE br.user_id = ? AND br.status != 'Active'
ORDER BY br.issue_date DESC";

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
        /* Allow book details column to wrap */
        .table-responsive table td.book-details {
            white-space: normal;
        }
        .book-details-title {
            font-weight: bold;
            color: #4e73df;
            margin-bottom: 5px;
        }
        .book-details-info {
            color: #666;
            font-size: 0.9em;
        }
        .table-responsive table td,
        .table-responsive table th {
            vertical-align: middle !important;
        }
        /* Table styling without vertical lines */
        .table-no-lines {
            border-collapse: collapse;
        }
        .table-no-lines th,
        .table-no-lines td {
            border: none;
            border-bottom: 1px solid #e3e6f0;
        }
        .table-no-lines thead th {
            border-bottom: 2px solid #e3e6f0;
            background-color: #f8f9fc;
        }
        /* Navigation pills styling - kept for reference */
        .nav-pills {
            margin-bottom: 15px;
        }
        .nav-pills .nav-link {
            border-radius: 0.25rem;
            margin-right: 5px;
        }
        .nav-pills .nav-link.active {
            background-color: #4e73df;
        }
    </style>
</head>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Borrowing History</h6>
                <div>
                    <a href="book_borrowing.php" class="btn btn-sm btn-info mr-2">
                        <i class="fas fa-book-reader"></i> Current Borrowings
                    </a>
                    <a href="searchbook.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-search"></i> Search Books
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Navigation pills removed -->
                
                <div class="table-responsive">
                    <table class="table table-no-lines" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center">ID</th>
                                <th class="text-center">Book Details</th>
                                <th class="text-center">Issue Date</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-center">Return Date</th>
                                <th class="text-center">Days Borrowed</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                // Determine row styling based on overdue status
                                $rowClass = $row['days_overdue'] > 0 ? 'table-danger' : '';

                                // Format additional details
                                $detailsArray = [];
                                if (!empty($row['ISBN'])) $detailsArray[] = 'ISBN: ' . htmlspecialchars($row['ISBN']);
                                if (!empty($row['series'])) $detailsArray[] = 'Series: ' . htmlspecialchars($row['series']);
                                if (!empty($row['volume'])) $detailsArray[] = 'Vol: ' . htmlspecialchars($row['volume']);
                                if (!empty($row['part'])) $detailsArray[] = 'Part: ' . htmlspecialchars($row['part']);
                                if (!empty($row['edition'])) $detailsArray[] = 'Ed: ' . htmlspecialchars($row['edition']);
                                
                                $additionalDetails = !empty($detailsArray) ? implode(' | ', $detailsArray) : '';
                            ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td class="text-center"><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td class="book-details">
                                        <div class="book-details-title">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </div>
                                        <?php if (!empty($additionalDetails)): ?>
                                        <div class="book-details-info">
                                            <?php echo $additionalDetails; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo date('M j, Y', strtotime($row['issue_date'])); ?></td>
                                    <td class="text-center"><?php echo date('M j, Y', strtotime($row['due_date'])); ?></td>
                                    <td class="text-center">
                                        <?php echo !empty($row['return_date']) ? date('M j, Y', strtotime($row['return_date'])) : 'Not returned'; ?>
                                    </td>
                                    <td class="text-center"><?php echo $row['days_borrowed']; ?> days</td>
                                    <td class="text-center">
                                        <?php if ($row['days_overdue'] > 0): ?>
                                            <span class="badge badge-danger"><?php echo $row['days_overdue']; ?> days overdue</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">On time</span>
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
            "searchPlaceholder": "Search within results...",
            "emptyTable": "No borrowing history found",
            "zeroRecords": "No matching borrowings found"
        },
        "pageLength": 10,
        "order": [[2, 'desc']], // Sort by issue date (newest first)
        "responsive": false,
        "scrollX": true,
        "autoWidth": false,
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
        }
    });
    
    // Adjust table columns on window resize
    $(window).on('resize', function () {
        $('#dataTable').DataTable().columns.adjust();
    });
});
</script>