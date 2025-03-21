<?php
session_start();
include('inc/header.php');

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include('../db.php');

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$userFilter = isset($_GET['user']) ? $_GET['user'] : '';
$bookFilter = isset($_GET['book']) ? $_GET['book'] : '';
$shelfFilter = isset($_GET['shelf']) ? $_GET['shelf'] : '';

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

// Build the SQL WHERE clause for filtering
$whereClause = "WHERE b.status != 'Active' AND b.status != 'Over Due'";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= " AND b.status = '$statusFilter'";
    $filterParams[] = "status=$statusFilter";
}

if ($dateStart) {
    $whereClause .= " AND DATE(b.issue_date) >= '$dateStart'";
    $filterParams[] = "date_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= " AND DATE(b.issue_date) <= '$dateEnd'";
    $filterParams[] = "date_end=$dateEnd";
}

if ($userFilter) {
    $whereClause .= " AND (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')";
    $filterParams[] = "user=" . urlencode($userFilter);
}

if ($bookFilter) {
    $whereClause .= " AND (bk.title LIKE '%$bookFilter%' OR bk.accession LIKE '%$bookFilter%')";
    $filterParams[] = "book=" . urlencode($bookFilter);
}

if ($shelfFilter) {
    $whereClause .= " AND bk.shelf_location = '$shelfFilter'";
    $filterParams[] = "shelf=$shelfFilter";
}

// Get shelf locations for dropdown filter
$shelfQuery = "SELECT DISTINCT shelf_location FROM books ORDER BY shelf_location";
$shelfResult = $conn->query($shelfQuery);
$shelfLocations = [];
while($row = $shelfResult->fetch_assoc()) {
    $shelfLocations[] = $row['shelf_location'];
}

// Count total number of records for the filter summary
$countQuery = "SELECT COUNT(*) as total FROM borrowings b 
              JOIN books bk ON b.book_id = bk.id
              JOIN users u ON b.user_id = u.id
              LEFT JOIN admins a1 ON b.issued_by = a1.id
              LEFT JOIN admins a2 ON b.recieved_by = a2.id
              $whereClause";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];

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
            bk.shelf_location,
            u.school_id,
            CONCAT(u.firstname, ' ', u.lastname) as borrower_name,
            CONCAT(a1.firstname, ' ', a1.lastname) as issued_by_name,
            CONCAT(a2.firstname, ' ', a2.lastname) as recieved_by_name
          FROM borrowings b
          JOIN books bk ON b.book_id = bk.id
          JOIN users u ON b.user_id = u.id
          LEFT JOIN admins a1 ON b.issued_by = a1.id
          LEFT JOIN admins a2 ON b.recieved_by = a2.id
          $whereClause
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

        <!-- Borrowing History Filter Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Filter Borrowing History</h6>
                <button class="btn btn-sm btn-primary" id="toggleFilter">
                    <i class="fas fa-filter"></i> Toggle Filter
                </button>
            </div>
            <div class="card-body <?= empty($filterParams) ? 'd-none' : '' ?>" id="filterForm">
                <form method="get" action="" class="mb-0" id="historyFilterForm">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control form-control-sm" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="Returned" <?= ($statusFilter == 'Returned') ? 'selected' : '' ?>>Returned</option>
                                    <option value="Damaged" <?= ($statusFilter == 'Damaged') ? 'selected' : '' ?>>Damaged</option>
                                    <option value="Lost" <?= ($statusFilter == 'Lost') ? 'selected' : '' ?>>Lost</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="date_start">From Date</label>
                                <input type="date" class="form-control form-control-sm" id="date_start" 
                                       name="date_start" value="<?= $dateStart ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="date_end">To Date</label>
                                <input type="date" class="form-control form-control-sm" id="date_end" 
                                       name="date_end" value="<?= $dateEnd ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="user">Borrower</label>
                                <input type="text" class="form-control form-control-sm" id="user" 
                                       name="user" placeholder="Name or ID" value="<?= htmlspecialchars($userFilter) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="book">Book</label>
                                <input type="text" class="form-control form-control-sm" id="book" 
                                       name="book" placeholder="Title or Accession" value="<?= htmlspecialchars($bookFilter) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="shelf">Shelf Location</label>
                                <select class="form-control form-control-sm" id="shelf" name="shelf">
                                    <option value="">All Locations</option>
                                    <?php foreach($shelfLocations as $shelf): ?>
                                    <option value="<?= $shelf ?>" <?= ($shelfFilter == $shelf) ? 'selected' : '' ?>><?= $shelf ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" id="applyFilters" class="btn btn-primary btn-sm mr-2">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <button type="button" id="resetFilters" class="btn btn-secondary btn-sm">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Borrowing Records</h6>
                
                <!-- Results summary - Updated to match borrowed_books.php styling -->
                <div>
                    <span id="filterSummary" class="mr-3 <?= empty($filterParams) ? 'd-none' : '' ?>">
                        <span class="text-primary font-weight-bold">Filter applied:</span> 
                        Showing <span id="totalResults"><?= $totalRecords ?></span> result<span id="pluralSuffix"><?= $totalRecords != 1 ? 's' : '' ?></span>
                    </span>
                </div>
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
    </div>
</div>

<?php include('inc/footer.php'); ?>

<script>
$(document).ready(function() {
    // Toggle filter form visibility
    $('#toggleFilter').on('click', function() {
        $('#filterForm').toggleClass('d-none');
    });

    // Reset filters
    $('#resetFilters').on('click', function(e) {
        // Prevent default form submission
        e.preventDefault();
        
        // Store the current visibility state of the filter form
        const isFilterVisible = !$('#filterForm').hasClass('d-none');
        
        // Clear all filter values
        $('#status').val('');
        $('#date_start').val('');
        $('#date_end').val('');
        $('#user').val('');
        $('#book').val('');
        $('#shelf').val('');
        
        // Update the filter summary to indicate no filters
        $('#filterSummary').addClass('d-none');
        
        // Use AJAX to reload content instead of full page reload
        $.ajax({
            url: 'borrowing_history.php',
            type: 'GET',
            success: function(data) {
                // Parse the response HTML
                const $data = $(data);
                
                // Extract the table content
                let tableHtml = $data.find('#dataTable').parent().html();
                // Update just the table content
                $('.table-responsive').html(tableHtml);
                
                // Update statistics cards
                let statsHtml = $data.find('.row.mb-4').html();
                $('.row.mb-4').html(statsHtml);
                
                // Reinitialize DataTable
                if ($.fn.DataTable.isDataTable('#dataTable')) {
                    $('#dataTable').DataTable().destroy();
                }
                
                initializeDataTable();
                
                // Restore the filter form visibility state
                if (isFilterVisible) {
                    $('#filterForm').removeClass('d-none');
                }
            }
        });
    });

    // Handle form submission (Apply filters)
    $('#historyFilterForm').on('submit', function(e) {
        e.preventDefault();
        
        // Store the current visibility state of the filter form
        const isFilterVisible = !$('#filterForm').hasClass('d-none');
        
        // Submit the form using AJAX
        $.ajax({
            url: 'borrowing_history.php',
            type: 'GET',
            data: $(this).serialize(),
            success: function(data) {
                // Parse the response HTML
                const $data = $(data);
                
                // Extract the table content
                let tableHtml = $data.find('#dataTable').parent().html();
                // Update just the table content
                $('.table-responsive').html(tableHtml);
                
                // Update filter summary with total results count
                let totalResults = $data.find('#totalResults').text();
                $('#totalResults').text(totalResults);
                $('#pluralSuffix').text(totalResults != 1 ? 's' : '');
                
                // Update statistics cards
                let statsHtml = $data.find('.row.mb-4').html();
                $('.row.mb-4').html(statsHtml);
                
                // Show or hide the filter summary based on whether filters are applied
                if ($('#status').val() || $('#date_start').val() || $('#date_end').val() || 
                    $('#user').val() || $('#book').val() || $('#shelf').val()) {
                    $('#filterSummary').removeClass('d-none');
                } else {
                    $('#filterSummary').addClass('d-none');
                }
                
                // Reinitialize DataTable
                if ($.fn.DataTable.isDataTable('#dataTable')) {
                    $('#dataTable').DataTable().destroy();
                }
                
                initializeDataTable();
                
                // Restore the filter form visibility state
                if (isFilterVisible) {
                    $('#filterForm').removeClass('d-none');
                }
            }
        });
    });

    // Function to initialize DataTable with consistent settings
    function initializeDataTable() {
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
    }

    // Initialize DataTable on page load
    initializeDataTable();
});
</script>
