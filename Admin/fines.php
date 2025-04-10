<?php
session_start();
include('inc/header.php');

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Check if the user has the correct role
if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian') {
    header('Location: dashboard.php');
    exit();
}

include('../db.php');

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$userFilter = isset($_GET['user']) ? $_GET['user'] : '';
$bookFilter = isset($_GET['book']) ? $_GET['book'] : '';
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "WHERE 1=1";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= " AND f.status = '$statusFilter'";
    $filterParams[] = "status=$statusFilter";
}

if ($dateStart) {
    $whereClause .= " AND DATE(f.date) >= '$dateStart'";
    $filterParams[] = "date_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= " AND DATE(f.date) <= '$dateEnd'";
    $filterParams[] = "date_end=$dateEnd";
}

if ($userFilter) {
    $whereClause .= " AND (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')";
    $filterParams[] = "user=" . urlencode($userFilter);
}

if ($bookFilter) {
    $whereClause .= " AND bk.title LIKE '%$bookFilter%'";
    $filterParams[] = "book=" . urlencode($bookFilter);
}

if ($typeFilter) {
    $whereClause .= " AND f.type = '$typeFilter'";
    $filterParams[] = "type=$typeFilter";
}

// Fetch fines with related information
$query = "SELECT f.id, f.type, f.amount, f.status, f.date, f.payment_date,
          f.reminder_sent, -- Include reminder_sent column
          b.issue_date, b.due_date, b.return_date,
          bk.title AS book_title,
          CONCAT(u.firstname, ' ', u.lastname) AS borrower_name,
          u.school_id
          FROM fines f
          JOIN borrowings b ON f.borrowing_id = b.id
          JOIN books bk ON b.book_id = bk.id
          JOIN users u ON b.user_id = u.id
          $whereClause
          ORDER BY f.date DESC";

// Run the query and store the result
$result = $conn->query($query);

// Count total number of records for the filter summary
$countQuery = "SELECT COUNT(*) as total FROM fines f
              JOIN borrowings b ON f.borrowing_id = b.id
              JOIN books bk ON b.book_id = bk.id
              JOIN users u ON b.user_id = u.id
              $whereClause";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];

// Get distinct fine types for dropdown filter
$typeQuery = "SELECT DISTINCT type FROM fines ORDER BY type";
$typeResult = $conn->query($typeQuery);
$fineTypes = [];
while($row = $typeResult->fetch_assoc()) {
    $fineTypes[] = $row['type'];
}

// Fetch total number of unpaid fines and total value of unpaid fines
$unpaidFinesQuery = "SELECT COUNT(*) as total_unpaid_fines, SUM(amount) as total_unpaid_value FROM fines WHERE status = 'Unpaid'";
$unpaidFinesResult = $conn->query($unpaidFinesQuery);
$unpaidFinesRow = $unpaidFinesResult->fetch_assoc();
$totalUnpaidFines = $unpaidFinesRow['total_unpaid_fines'] ?: 0;
$totalUnpaidValue = $unpaidFinesRow['total_unpaid_value'] ?: 0;

// Fetch total number of paid fines and total value of paid fines
$paidFinesQuery = "SELECT COUNT(*) as total_paid_fines, SUM(amount) as total_paid_value FROM fines WHERE status = 'Paid'";
$paidFinesResult = $conn->query($paidFinesQuery);
$paidFinesRow = $paidFinesResult->fetch_assoc();
$totalPaidFines = $paidFinesRow['total_paid_fines'] ?: 0;
$totalPaidValue = $paidFinesRow['total_paid_value'] ?: 0;
?>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    .table td, .table th {
        white-space: nowrap;
    }
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
    .unpaid-card {
        border-left-color: #e74a3b;
    }
    .paid-card {
        border-left-color: #1cc88a;
    }
</style>

<!-- Main Content -->
    <div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Fines</h1>

            <!-- Generate Receipt Form -->
            <form action="fine-receipt.php" method="post" id="receiptForm" target="_blank" onsubmit="return validateForm()" class="d-flex align-items-center">
                <div class="col-auto p-2">
                    <label for="school_id" class="col-form-label" style="font-size:medium;">Enter ID Number:</label>
                </div>
                <div class="col-auto p-2" style="width:200px;">
                    <input type="text" name="school_id" id="school_id" class="form-control custom" placeholder="ID Number" required>
                </div>
                <div class="col-auto p-2">
                    <button class="btn btn-danger btn-block" type="submit">Generate Fine Receipt</button>
                </div>
            </form>
        </div>

        <!-- Fines Filter Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Filter Fines</h6>
                <button class="btn btn-sm btn-primary" id="toggleFilter">
                    <i class="fas fa-filter"></i> Toggle Filter
                </button>
            </div>
            <div class="card-body <?= empty($filterParams) ? 'd-none' : '' ?>" id="filterForm">
                <form method="get" action="" class="mb-0" id="finesFilterForm">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control form-control-sm" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="Paid" <?= ($statusFilter == 'Paid') ? 'selected' : '' ?>>Paid</option>
                                    <option value="Unpaid" <?= ($statusFilter == 'Unpaid') ? 'selected' : '' ?>>Unpaid</option>
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
                                       name="book" placeholder="Title" value="<?= htmlspecialchars($bookFilter) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="type">Fine Type</label>
                                <select class="form-control form-control-sm" id="type" name="type">
                                    <option value="">All Types</option>
                                    <?php foreach($fineTypes as $type): ?>
                                    <option value="<?= $type ?>" <?= ($typeFilter == $type) ? 'selected' : '' ?>><?= $type ?></option>
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
                <h6 class="m-0 font-weight-bold text-primary">Fines List</h6>
                <div>
                    <!-- Results summary -->
                    <span id="filterSummary" class="mr-3 <?= empty($filterParams) ? 'd-none' : '' ?>">
                        <span class="text-primary font-weight-bold">Filter applied:</span>
                        Showing <span id="totalResults"><?= $totalRecords ?></span> result<span id="pluralSuffix"><?= $totalRecords != 1 ? 's' : '' ?></span>
                    </span>
                    <button id="remindAllBtn" class="btn btn-warning btn-sm mr-2">Remind All</button>

                    <button id="exportPaidFinesBtn" class="btn btn-success btn-sm mr-2">Export Paid Fines</button>
                    <button id="exportUnpaidFinesBtn" class="btn btn-danger btn-sm">Export Unpaid Fines</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="finesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center">Borrower ID</th>
                                <th class="text-center">Borrower</th>
                                <th class="text-center">Book</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Amount</th>
                                <th class="text-center">Fine Date</th>
                                <th class="text-center">Issue Date</th>
                                <th class="text-center">Return Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Payment Date</th>
                                <th class="text-center">Reminder</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                                <tr data-fine-id="<?php echo $row['id']; ?>"
                                    data-amount="<?php echo $row['amount']; ?>"
                                    data-borrower="<?php echo htmlspecialchars($row['borrower_name']); ?>"
                                    data-status="<?php echo $row['status']; ?>">
                                    <td class="text-center"><?php echo htmlspecialchars($row['school_id']); ?></td>
                                    <td class="text-left"><?php echo htmlspecialchars($row['borrower_name']); ?></td>
                                    <td class="text-left"><?php echo htmlspecialchars($row['book_title']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['type']); ?></td>
                                    <td class="text-center">₱<?php echo number_format($row['amount'], 2); ?></td>
                                    <td class="text-center"><?php echo date('Y-m-d', strtotime($row['date'])); ?></td>
                                    <td class="text-center"><?php echo date('Y-m-d', strtotime($row['issue_date'])); ?></td>
                                    <td class="text-center">
                                        <?php
                                        echo ($row['return_date'] !== null && $row['return_date'] !== '0000-00-00')
                                            ? date('Y-m-d', strtotime($row['return_date']))
                                            : '-';
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['status'] === 'Unpaid'): ?>
                                            <span class="badge badge-danger">Unpaid</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Paid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        echo ($row['payment_date'] !== null && $row['payment_date'] !== '0000-00-00')
                                            ? date('Y-m-d', strtotime($row['payment_date']))
                                            : '-';
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo ($row['reminder_sent'] == 1) ? '<span class="badge badge-success">Reminder Sent</span>' : '<span class="badge badge-warning">Not Reminded</span>'; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Fines Statistics -->
        <div class="row mb-4">
            <!-- Total Unpaid Fines Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2 stats-card unpaid-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1 stats-title">
                                    Total Unpaid Fines</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $totalUnpaidFines; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-circle text-danger stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Paid Fines Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2 stats-card paid-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1 stats-title">
                                    Total Paid Fines</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $totalPaidFines; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle text-success stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Value of Unpaid Fines Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2 stats-card unpaid-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1 stats-title">
                                    Value of Unpaid Fines</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number">₱<?php echo number_format($totalUnpaidValue, 2); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-money-bill-wave text-danger stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Value of Paid Fines Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2 stats-card paid-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1 stats-title">
                                    Value of Paid Fines</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number">₱<?php echo number_format($totalPaidValue, 2); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-coins text-success stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('inc/footer.php'); ?>

<!-- Context Menu -->
<div class="context-menu" style="display: none; position: absolute; z-index: 1000;">
    <ul class="list-group">
        <li class="list-group-item" data-action="mark-paid">Mark as Paid</li>
    </ul>
</div>

<!-- Add SweetAlert2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Store references
    const contextMenu = $('.context-menu');
    let $selectedRow = null;

    // Toggle filter form visibility
    $('#toggleFilter').on('click', function() {
        $('#filterForm').toggleClass('d-none');
    });

    // Reset filters
    $('#resetFilters').on('click', function(e) {
        e.preventDefault();

        // Store the current visibility state of the filter form
        const isFilterVisible = !$('#filterForm').hasClass('d-none');

        // Clear all filter values
        $('#status').val('');
        $('#date_start').val('');
        $('#date_end').val('');
        $('#user').val('');
        $('#book').val('');
        $('#type').val('');

        // Update the filter summary to indicate no filters
        $('#filterSummary').addClass('d-none');

        // Use AJAX to reload content with explicitly empty parameters
        $.ajax({
            url: 'fines.php',
            type: 'GET',
            // Explicitly send empty parameters to override any existing URL parameters
            data: {
                status: '',
                date_start: '',
                date_end: '',
                user: '',
                book: '',
                type: ''
            },
            success: function(data) {
                // Parse the response HTML
                const $data = $(data);

                // Extract the table content
                let tableHtml = $data.find('#finesTable').parent().html();
                // Update just the table content
                $('.table-responsive').html(tableHtml);

                // Update statistics cards
                let statsHtml = $data.find('.row.mb-4').html();
                $('.row.mb-4').html(statsHtml);

                // Update browser URL to remove query parameters without reloading
                const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.pushState({path: newUrl}, '', newUrl);

                // Reinitialize DataTable
                if ($.fn.DataTable.isDataTable('#finesTable')) {
                    $('#finesTable').DataTable().destroy();
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
    $('#finesFilterForm').on('submit', function(e) {
        e.preventDefault();

        // Store the current visibility state of the filter form
        const isFilterVisible = !$('#filterForm').hasClass('d-none');

        // Submit the form using AJAX
        $.ajax({
            url: 'fines.php',
            type: 'GET',
            data: $(this).serialize(),
            success: function(data) {
                // Parse the response HTML
                const $data = $(data);

                // Extract the table content
                let tableHtml = $data.find('#finesTable').parent().html();
                // Update just the table content
                $('.table-responsive').html(tableHtml);

                // Update filter summary
                let filterSummaryHtml = $data.find('#filterSummary').html();
                $('#filterSummary').html(filterSummaryHtml);

                // Update statistics cards
                let statsHtml = $data.find('.row.mb-4').html();
                $('.row.mb-4').html(statsHtml);

                // Show or hide the filter summary based on whether filters are applied
                if ($('#status').val() || $('#date_start').val() || $('#date_end').val() ||
                    $('#user').val() || $('#book').val() || $('#type').val()) {
                    $('#filterSummary').removeClass('d-none');
                } else {
                    $('#filterSummary').addClass('d-none');
                }

                // Reinitialize DataTable
                if ($.fn.DataTable.isDataTable('#finesTable')) {
                    $('#finesTable').DataTable().destroy();
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
        const table = $('#finesTable').DataTable({
            "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
            "pagingType": "simple_numbers",
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
            "responsive": false,
            "scrollY": "60vh",
            "scrollCollapse": true,
            "fixedHeader": true,
            "order": [[5, "desc"]],
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search..."
            },
            "initComplete": function() {
                $('#finesTable_filter input').addClass('form-control form-control-sm');
                $('#finesTable_filter').addClass('d-flex align-items-center');
                $('#finesTable_filter label').append('<i class="fas fa-search ml-2"></i>');
                $('.dataTables_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
            }
        });

        // Rebind context menu on newly loaded table rows
        $('#finesTable tbody').on('contextmenu', 'tr', function(e) {
            e.preventDefault();

            $selectedRow = $(this);
            const status = $selectedRow.data('status');

            contextMenu.find('li').data('action', status === 'Unpaid' ? 'mark-paid' : 'mark-unpaid').text(status === 'Unpaid' ? 'Mark as Paid' : 'Mark as Unpaid');

            contextMenu.css({
                top: e.pageY + "px",
                left: e.pageX + "px",
                display: "block"
            });
        });

        // Add window resize handler for the table
        $(window).on('resize', function() {
            table.columns.adjust().draw();
        });
    }

    // Initialize DataTable on page load
    initializeDataTable();

    // Right-click handler for table rows
    $('#finesTable tbody').on('contextmenu', 'tr', function(e) {
        e.preventDefault();

        $selectedRow = $(this);
        const status = $selectedRow.data('status');

        contextMenu.find('li').data('action', status === 'Unpaid' ? 'mark-paid' : 'mark-unpaid').text(status === 'Unpaid' ? 'Mark as Paid' : 'Mark as Unpaid');

        contextMenu.css({
            top: e.pageY + "px",
            left: e.pageX + "px",
            display: "block"
        });
    });

    // Hide context menu on document click
    $(document).on('click', function() {
        contextMenu.hide();
    });

    // Prevent hiding when clicking menu items
    $('.context-menu').on('click', function(e) {
        e.stopPropagation();
    });

    // Handle menu item clicks
    $(".context-menu li").on('click', function() {
        if (!$selectedRow) return;

        const fineId = $selectedRow.data('fine-id');
        const amount = $selectedRow.data('amount');
        const borrower = $selectedRow.data('borrower');
        const action = $(this).data('action');
        let url = '';

        if (action === 'mark-paid') {
            url = 'mark_fine_paid.php';
        } else if (action === 'mark-unpaid') {
            url = 'mark_fine_unpaid.php';
        }

        // Sweet Alert confirmation
        Swal.fire({
            title: 'Confirm Payment',
            html: `
                <div class="text-left">
                    <p class="mb-2"><strong>Borrower:</strong> ${borrower}</p>
                    <p class="mb-2"><strong>Amount:</strong> ₱${parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                    <p class="mt-3">Are you sure you want to mark this fine as ${action === 'mark-paid' ? 'paid' : 'unpaid'}?</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: `<i class="fas fa-check"></i> Yes, Mark as ${action === 'mark-paid' ? 'Paid' : 'Unpaid'}`,
            cancelButtonText: '<i class="fas fa-times"></i> Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showLoaderOnConfirm: true,
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-danger'
            },
            preConfirm: () => {
                return fetch(`${url}?id=${fineId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'error') {
                            throw new Error(data.message);
                        }
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Error: ${error.message}`);
                    });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Payment Recorded!',
                    text: `The fine has been successfully marked as ${action === 'mark-paid' ? 'paid' : 'unpaid'}.`,
                    confirmButtonColor: '#28a745',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.reload();
                });
            }
        });

        contextMenu.hide();
    });

    // Add custom styles for the context menu
    $('<style>')
        .text(`
            .context-menu {
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
            }
            .context-menu .list-group-item {
                cursor: pointer;
                padding: 8px 20px;
            }
            .context-menu .list-group-item:hover {
                background-color: #f8f9fa;
            }
            tr[data-fine-id] {
                cursor: context-menu;
            }
        `)
        .appendTo('head');

    // Export Paid Fines
    $('#exportPaidFinesBtn').click(function() {
        window.location.href = 'export_fines.php?status=Paid';
    });

    // Export Unpaid Fines
    $('#exportUnpaidFinesBtn').click(function() {
        window.location.href = 'export_fines.php?status=Unpaid';
    });
});
</script>
<script>
$(document).ready(function() {
    // Handle "Remind All" button click
    $('#remindAllBtn').on('click', function() {
        Swal.fire({
            title: 'Send Reminders to All Unpaid Fines',
            text: `Are you sure you want to send reminders to all users with unpaid fines?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Send',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: 'send_fine_reminders.php',
                    type: 'POST',
                    data: {
                        action: 'remind_all'
                    },
                    dataType: 'json'
                }).then(response => {
                    if (response.status === 'success') {
                        return response;
                    } else {
                        throw new Error(response.message || 'Failed to send reminders.');
                    }
                }).catch(error => {
                    Swal.showValidationMessage(`Error: ${error.message}`);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Reminders Sent!',
                    text: result.value.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    });
});
</script>