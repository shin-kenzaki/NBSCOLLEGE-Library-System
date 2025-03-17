<?php
session_start();
include('inc/header.php');

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include('../db.php');

// Statistics queries
// Get total borrowed books count (all time)
$totalBorrowedQuery = "SELECT COUNT(*) as total FROM borrowings";
$totalBorrowedResult = $conn->query($totalBorrowedQuery);
$totalBorrowedRow = $totalBorrowedResult->fetch_assoc();
$totalBorrowed = $totalBorrowedRow['total'];

// Get total returned books count
$returnedBooksQuery = "SELECT COUNT(*) as total FROM borrowings WHERE status = 'Returned'";
$returnedBooksResult = $conn->query($returnedBooksQuery);
$returnedBooksRow = $returnedBooksResult->fetch_assoc();
$returnedBooks = $returnedBooksRow['total'];

// Get total damaged books count
$damagedBooksQuery = "SELECT COUNT(*) as total FROM borrowings WHERE status = 'Damaged'";
$damagedBooksResult = $conn->query($damagedBooksQuery);
$damagedBooksRow = $damagedBooksResult->fetch_assoc();
$damagedBooks = $damagedBooksRow['total'];

// Get total lost books count
$lostBooksQuery = "SELECT COUNT(*) as total FROM borrowings WHERE status = 'Lost'";
$lostBooksResult = $conn->query($lostBooksQuery);
$lostBooksRow = $lostBooksResult->fetch_assoc();
$lostBooks = $lostBooksRow['total'];

$query = "SELECT 
            b.id as borrow_id,
            b.issue_date,
            b.due_date,
            b.return_date,
            b.report_date,
            b.replacement_date,
            b.status,
            bk.title as book_title,
            bk.accession,
            CONCAT(u.firstname, ' ', u.lastname) as borrower_name,
            CONCAT(a1.firstname, ' ', a1.lastname) as issued_by_name,
            CONCAT(a2.firstname, ' ', a2.lastname) as recieved_by_name
          FROM borrowings b
          JOIN books bk ON b.book_id = bk.id
          JOIN users u ON b.user_id = u.id
          LEFT JOIN admins a1 ON b.issued_by = a1.id
          LEFT JOIN admins a2 ON b.recieved_by = a2.id
          WHERE b.status != 'Active' AND b.status != 'Over Due'
          ORDER BY b.issue_date DESC";
$result = $conn->query($query);
?>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    .table td, .table th {
        white-space: nowrap;
    }
    /* Add hover effect styles */
    .stats-card {
        transition: all 0.3s;
        border-left: 4px solid;
    }
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .stats-icon {
        font-size: 2rem;
        opacity: 0.6;
    }
    .stats-title {
        font-size: 0.9rem;
        font-weight: bold;
        text-transform: uppercase;
    }
    .stats-number {
        font-size: 1.5rem;
        font-weight: bold;
    }
    .primary-card {
        border-left-color: #4e73df;
    }
    .success-card {
        border-left-color: #1cc88a;
    }
    .danger-card {
        border-left-color: #e74a3b;
    }
    .warning-card {
        border-left-color: #f6c23e;
    }
</style>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid px-4">
        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">Borrowing History</h1>
        <p class="mb-4">Complete history of all book borrowing transactions including returns, losses, and damages.</p>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <!-- Total Borrowed Books -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2 stats-card primary-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1 stats-title">
                                    Overall Borrowed Items</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $totalBorrowed; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-book fa-2x text-gray-300 stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Returned Books -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2 stats-card success-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1 stats-title">
                                    Overall Returned Books</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $returnedBooks; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300 stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lost Books -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2 stats-card danger-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1 stats-title">
                                    Overall Lost Books</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $lostBooks; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-times-circle fa-2x text-gray-300 stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Damaged Books -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2 stats-card warning-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1 stats-title">
                                    Overall Damaged Books</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $damagedBooks; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-gray-300 stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Borrowing Records</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center">Book Title</th>
                                <th class="text-center">Accession No.</th>
                                <th class="text-center">Borrower</th>
                                <th class="text-center">Issue Date</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-center">Issued By</th>
                                <th class="text-center">Received By</th>
                                <th class="text-center">Return/Report Date</th>
                                <th class="text-center">Replaced Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['accession']); ?></td>
                                <td><?php echo htmlspecialchars($row['borrower_name']); ?></td>
                                <td class="text-center"><?php echo date('M d, Y', strtotime($row['issue_date'])); ?></td>
                                <td class="text-center"><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                <td class="text-center"><?php echo $row['issued_by_name']; ?></td>
                                <td class="text-center"><?php echo $row['recieved_by_name'] ?? '-'; ?></td>
                                <td class="text-center"><?php 
                                    if ($row['return_date']) {
                                        echo date('M d, Y', strtotime($row['return_date']));
                                    } elseif ($row['report_date']) {
                                        echo date('M d, Y', strtotime($row['report_date']));
                                    } else {
                                        echo 'N/A';
                                    }
                                ?></td>
                                <td class="text-center"><?php echo $row['replacement_date'] ? date('M d, Y', strtotime($row['replacement_date'])) : '-'; ?></td>
                                <td class="text-center"><?php 
                                    $status = '';
                                    $status_color = '';
                                    if ($row['replacement_date']) {
                                        $status = $row['status'] . ' (Replaced)';
                                        $status_color = 'success';
                                    } else if ($row['report_date'] && $row['status'] == 'Lost') {
                                        $status = 'Lost';
                                        $status_color = 'danger';
                                    } else if ($row['report_date'] && $row['status'] == 'Damaged') {
                                        $status = 'Damaged';
                                        $status_color = 'warning';
                                    } else if ($row['return_date']) {
                                        $status = 'Returned';
                                        $status_color = 'primary';
                                    } else {
                                        $status = $row['status'];
                                        $status_color = 'secondary';
                                    }
                                    echo "<span class=\"badge badge-$status_color\">" . $status . "</span>";
                                ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('inc/footer.php'); ?>

<script>
$(document).ready(function() {
    const table = $('#dataTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "pagingType": "simple_numbers",
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
        "responsive": false,
        "scrollY": "60vh",
        "scrollCollapse": true,
        "fixedHeader": true,
        "order": [[3, "desc"]],
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        },
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
            $('#dataTable_filter').addClass('d-flex align-items-center');
            $('#dataTable_filter label').append('<i class="fas fa-search ml-2"></i>');
            $('.dataTables_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
        }
    });

    // Add window resize handler
    $(window).on('resize', function() {
        table.columns.adjust().draw();
    });
});
</script>
