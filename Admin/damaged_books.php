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
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$userFilter = isset($_GET['user']) ? $_GET['user'] : '';
$bookFilter = isset($_GET['book']) ? $_GET['book'] : '';
$replacementStatus = isset($_GET['replacement_status']) ? $_GET['replacement_status'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "WHERE b.status = 'Damaged'";
$filterParams = [];

if ($dateStart) {
    $whereClause .= " AND DATE(b.report_date) >= '$dateStart'";
    $filterParams[] = "date_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= " AND DATE(b.report_date) <= '$dateEnd'";
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

if ($replacementStatus) {
    if ($replacementStatus == 'replaced') {
        $whereClause .= " AND b.replacement_date IS NOT NULL";
    } else {
        $whereClause .= " AND b.replacement_date IS NULL";
    }
    $filterParams[] = "replacement_status=$replacementStatus";
}

$query = "SELECT 
            b.id as borrow_id,
            b.issue_date,
            b.report_date,
            b.replacement_date,
            bk.title as book_title,
            bk.accession,
            CONCAT(u.firstname, ' ', u.lastname) as borrower_name,
            u.school_id
          FROM borrowings b
          JOIN books bk ON b.book_id = bk.id
          JOIN users u ON b.user_id = u.id
          $whereClause
          ORDER BY b.report_date DESC";
$result = $conn->query($query);

// Count total number of records for the filter summary
$countQuery = "SELECT COUNT(*) as total FROM borrowings b 
              JOIN books bk ON b.book_id = bk.id
              JOIN users u ON b.user_id = u.id
              $whereClause";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
?>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    .table td, .table th {
        white-space: nowrap;
    }
</style>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Damaged Books</h1>
        </div>

        <!-- Damaged Books Filter Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Filter Damaged Books</h6>
                <button class="btn btn-sm btn-primary" id="toggleFilter">
                    <i class="fas fa-filter"></i> Toggle Filter
                </button>
            </div>
            <div class="card-body <?= empty($filterParams) ? 'd-none' : '' ?>" id="filterForm">
                <form method="get" action="" class="mb-0" id="damagedBooksFilterForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_start">Report Date From</label>
                                <input type="date" class="form-control form-control-sm" id="date_start" 
                                       name="date_start" value="<?= $dateStart ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_end">Report Date To</label>
                                <input type="date" class="form-control form-control-sm" id="date_end" 
                                       name="date_end" value="<?= $dateEnd ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="user">Borrower</label>
                                <input type="text" class="form-control form-control-sm" id="user" 
                                       name="user" placeholder="Name or ID" value="<?= htmlspecialchars($userFilter) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="book">Book</label>
                                <input type="text" class="form-control form-control-sm" id="book" 
                                       name="book" placeholder="Title or Accession" value="<?= htmlspecialchars($bookFilter) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="replacement_status">Replacement Status</label>
                                <select class="form-control form-control-sm" id="replacement_status" name="replacement_status">
                                    <option value="">All Statuses</option>
                                    <option value="replaced" <?= ($replacementStatus == 'replaced') ? 'selected' : '' ?>>Replaced</option>
                                    <option value="pending" <?= ($replacementStatus == 'pending') ? 'selected' : '' ?>>Pending Replacement</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-9 d-flex align-items-end justify-content-end">
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
                <h6 class="m-0 font-weight-bold text-primary">Damaged Book Records</h6>
                <!-- Results summary -->
                <span id="filterSummary" class="mr-3 <?= empty($filterParams) ? 'd-none' : '' ?>">
                    <span class="text-primary font-weight-bold">Filter applied:</span> 
                    Showing <span id="totalResults"><?= $totalRecords ?></span> result<span id="pluralSuffix"><?= $totalRecords != 1 ? 's' : '' ?></span>
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center">Book Title</th>
                                <th class="text-center">Accession No.</th>
                                <th class="text-center">Borrower</th>
                                <th class="text-center">Student/Staff ID</th>
                                <th class="text-center">Borrow Date</th>
                                <th class="text-center">Report Date</th>
                                <th class="text-center">Replaced Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr data-borrow-id="<?php echo $row['borrow_id']; ?>" 
                                data-book-title="<?php echo htmlspecialchars($row['book_title']); ?>">
                                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['accession']); ?></td>
                                <td><?php echo htmlspecialchars($row['borrower_name']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['school_id']); ?></td>
                                <td class="text-center"><?php echo date('Y-m-d', strtotime($row['issue_date'])); ?></td>
                                <td class="text-center"><?php echo date('Y-m-d', strtotime($row['report_date'])); ?></td>
                                <td class="text-center"><?php echo $row['replacement_date'] ? date('Y-m-d', strtotime($row['replacement_date'])) : '-'; ?></td>
                                <td class="text-center">
                                    <?php 
                                        if ($row['replacement_date']) {
                                            echo '<span class="badge badge-success">Replaced</span>';
                                        } else {
                                            echo '<span class="badge badge-warning">Pending Replacement</span>';
                                        }
                                    ?>
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

<!-- Add context menu before footer -->
<div class="context-menu" style="display: none; position: absolute; z-index: 1000;">
    <ul class="list-group">
        <li class="list-group-item" data-action="replace">Mark as Replaced</li>
        <li class="list-group-item" data-action="unreplace">Mark as Not Replaced</li>
    </ul>
</div>

<?php include('inc/footer.php'); ?>

<!-- Add SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Toggle filter form visibility
    $('#toggleFilter').on('click', function() {
        $('#filterForm').toggleClass('d-none');
    });

    // Handle form submission (Apply filters)
    $('#damagedBooksFilterForm').on('submit', function(e) {
        e.preventDefault();
        
        // Store the current visibility state of the filter form
        const isFilterVisible = !$('#filterForm').hasClass('d-none');
        
        // Submit the form using AJAX
        $.ajax({
            url: 'damaged_books.php',
            type: 'GET',
            data: $(this).serialize(),
            success: function(data) {
                // Parse the response HTML
                const $data = $(data);
                
                // Extract the table content
                let tableHtml = $data.find('#dataTable').parent().html();
                // Update just the table content
                $('.table-responsive').html(tableHtml);
                
                // Update filter summary
                let filterSummaryHtml = $data.find('#filterSummary').html();
                $('#filterSummary').html(filterSummaryHtml);
                $('#filterSummary').removeClass('d-none');
                
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

    // Reset filters
    $('#resetFilters').on('click', function(e) {
        // Prevent default form submission
        e.preventDefault();
        
        // Store the current visibility state of the filter form
        const isFilterVisible = !$('#filterForm').hasClass('d-none');
        
        // Clear all filter values
        $('#date_start').val('');
        $('#date_end').val('');
        $('#user').val('');
        $('#book').val('');
        $('#replacement_status').val('');
        
        // Update the filter summary to indicate no filters
        $('#filterSummary').addClass('d-none');
        
        // Use AJAX to reload content instead of full page reload
        $.ajax({
            url: 'damaged_books.php',
            type: 'GET',
            success: function(data) {
                // Parse the response HTML
                const $data = $(data);
                
                // Extract the table content
                let tableHtml = $data.find('#dataTable').parent().html();
                // Update just the table content
                $('.table-responsive').html(tableHtml);
                
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

    // Store references
    const contextMenu = $('.context-menu');
    let $selectedRow = null;

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
            "order": [[5, "desc"]], // Sort by report date by default
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search..."
            },
            "initComplete": function() {
                $('#dataTable_filter input').addClass('form-control form-control-sm');
                $('#dataTable_filter').addClass('d-flex align-items-center');
                $('#dataTable_filter label').append('<i class="fas fa-search ml-2"></i>');
                $('#dataTable_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
            }
        });
        
        // Add window resize handler
        $(window).on('resize', function() {
            table.columns.adjust().draw();
        });
        
        // Right-click handler for table rows - Moved inside initializeDataTable
        $(document).off('contextmenu', '#dataTable tbody tr');
        $(document).on('contextmenu', '#dataTable tbody tr', function(e) {
            e.preventDefault();
            $selectedRow = $(this);
            
            // Show appropriate context menu options based on replacement status
            const replacementDate = $selectedRow.find('td:eq(6)').text().trim();
            contextMenu.find('li[data-action="replace"]').toggle(replacementDate === '-');
            contextMenu.find('li[data-action="unreplace"]').toggle(replacementDate !== '-');
            
            contextMenu.css({
                top: e.pageY + "px",
                left: e.pageX + "px",
                display: "block"
            });
        });
    }

    // Initialize DataTable on page load
    initializeDataTable();

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

        const borrowId = $selectedRow.data('borrow-id');
        const bookTitle = $selectedRow.data('book-title');
        const action = $(this).data('action');
        
        // First dialog to input ISBN for replacement
        if (action === 'replace') {
            Swal.fire({
                title: 'Enter Replacement Book ISBN',
                html: `Please verify the replacement book by entering its ISBN:<br><br>
                      <b>Book:</b> ${bookTitle}`,
                input: 'text',
                inputPlaceholder: 'Enter ISBN',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Verify',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: false,
                allowEscapeKey: false,
                inputValidator: (value) => {
                    if (!value) {
                        return 'Please enter the ISBN'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Second confirmation dialog
                    Swal.fire({
                        title: 'Mark as Replaced?',
                        html: `Are you sure this damaged book has been replaced?<br><br>
                              <b>Book:</b> ${bookTitle}<br>
                              <b>ISBN:</b> ${result.value}`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Mark as Replaced',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            return fetch(`book_replaced.php?id=${borrowId}&type=damaged&isbn=${result.value}`)
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(response.statusText);
                                    }
                                    return response;
                                })
                                .catch(error => {
                                    Swal.showValidationMessage(`Request failed: ${error}`);
                                });
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'The book has been marked as replaced.',
                                icon: 'success',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    });
                }
            });
        } else if (action === 'unreplace') {
            // Confirmation dialog for unreplacing
            Swal.fire({
                title: 'Mark as Not Replaced?',
                html: `Are you sure you want to revert the replacement status of this book?<br><br>
                      <b>Book:</b> ${bookTitle}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Revert Status',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch(`book_unreplaced.php?id=${borrowId}&type=damaged`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(response.statusText);
                            }
                            return response;
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error}`);
                        });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'The book is now marked as not replaced.',
                        icon: 'success',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        }
        
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
        `)
        .appendTo('head');
});
</script>
