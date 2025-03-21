<?php
session_start();
include('inc/header.php');

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian') {
    header('Location: dashboard.php');
    exit();
}

include('../db.php');
include('update_overdue_status.php');

require 'mailer.php';

// Update overdue status
updateOverdueStatus($conn);

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$userFilter = isset($_GET['user']) ? $_GET['user'] : '';
$bookFilter = isset($_GET['book']) ? $_GET['book'] : '';
$shelfFilter = isset($_GET['shelf']) ? $_GET['shelf'] : '';

// Get total unpaid fines
$finesQuery = "SELECT SUM(amount) as total_unpaid FROM fines WHERE status = 'Unpaid'";
$finesResult = $conn->query($finesQuery);
$finesRow = $finesResult->fetch_assoc();
$totalUnpaidFines = $finesRow['total_unpaid'] ?? 0;
$formattedFines = '₱' . number_format($totalUnpaidFines, 2);

// Fetch statistics for the dashboard cards
// Get total borrowed books items
$totalBorrowedQuery = "SELECT COUNT(*) as total FROM borrowings";
$totalBorrowedResult = $conn->query($totalBorrowedQuery);
$totalBorrowedRow = $totalBorrowedResult->fetch_assoc();
$totalBorrowed = $totalBorrowedRow['total'];

// Get total borrowed books of the day
$todayBorrowedQuery = "SELECT COUNT(*) as total FROM borrowings WHERE DATE(issue_date) = CURDATE()";
$todayBorrowedResult = $conn->query($todayBorrowedQuery);
$todayBorrowedRow = $todayBorrowedResult->fetch_assoc();
$todayBorrowed = $todayBorrowedRow['total'];

// Get total active items
$activeItemsQuery = "SELECT COUNT(*) as total FROM borrowings WHERE status = 'Active'";
$activeItemsResult = $conn->query($activeItemsQuery);
$activeItemsRow = $activeItemsResult->fetch_assoc();
$activeItems = $activeItemsRow['total'];

// Get total overdue items
$overdueItemsQuery = "SELECT COUNT(*) as total FROM borrowings WHERE status = 'Overdue'";
$overdueItemsResult = $conn->query($overdueItemsQuery);
$overdueItemsRow = $overdueItemsResult->fetch_assoc();
$overdueItems = $overdueItemsRow['total'];

// Build the SQL WHERE clause for filtering
$whereClause = "WHERE b.status IN ('Active', 'Overdue') AND b.return_date IS NULL";
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

// Fetch borrowed books data for the table
$query = "SELECT b.id as borrow_id, b.book_id, b.user_id, b.issue_date, b.due_date, b.status,
          bk.title, bk.accession, bk.shelf_location,
          CONCAT(u.firstname, ' ', u.lastname) AS borrower,
          u.school_id,
          CONCAT(a.firstname, ' ', a.lastname) AS issued_by_name
          FROM borrowings b
          JOIN books bk ON b.book_id = bk.id
          JOIN users u ON b.user_id = u.id
          LEFT JOIN admins a ON b.issued_by = a.id
          $whereClause";
$result = $conn->query($query);

// Count total number of records for the filter summary
$countQuery = "SELECT COUNT(*) as total FROM borrowings b 
              JOIN books bk ON b.book_id = bk.id
              JOIN users u ON b.user_id = u.id
              LEFT JOIN admins a ON b.issued_by = a.id
              $whereClause";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];

// Get shelf locations for dropdown filter
$shelfQuery = "SELECT DISTINCT shelf_location FROM books ORDER BY shelf_location";
$shelfResult = $conn->query($shelfQuery);
$shelfLocations = [];
while($row = $shelfResult->fetch_assoc()) {
    $shelfLocations[] = $row['shelf_location'];
}

// NOTIFICATION LOGIC

// 🟢 Email Reminder Query (Exclude Shelf Location 'RES' & Check `reminder_sent`)
$emailQuery = "
    SELECT u.id AS user_id, u.email, u.firstname, u.lastname,
           GROUP_CONCAT(bk.title SEPARATOR '|') AS book_titles,
           b.due_date
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    JOIN books bk ON b.book_id = bk.id
    WHERE DATE(b.due_date) = CURDATE() + INTERVAL 1 DAY
    AND b.status = 'Active'
    AND bk.shelf_location != 'RES'
    AND b.reminder_sent = 0  -- 🔹 Only get entries that haven’t been marked yet
    GROUP BY u.id, b.due_date
";
$emailResult = $conn->query($emailQuery);

// Send Email Logic
while ($row = $emailResult->fetch_assoc()) {
    $userId = $row['user_id'];
    $email = $row['email'];
    $bookTitles = explode('|', $row['book_titles']);
    $dueDate = date('M d, Y', strtotime($row['due_date']));
    $borrowerName = $row['firstname'] . ' ' . $row['lastname'];

    $mail = require 'mailer.php';

    $bookList = "<ul>";
    foreach ($bookTitles as $title) {
        $bookList .= "<li>" . htmlspecialchars($title) . "</li>";
    }
    $bookList .= "</ul>";

    try {
        // ✅ Proper "No-Reply" Setup
        $mail->setFrom('noreply@nbs-library-system.com', 'Library System (No-Reply)');

        // ✅ Ensures replies go nowhere
        $mail->addReplyTo('noreply@nbs-library-system.com', 'No-Reply');

        // ✅ Ensures new email threads
        $uniqueId = uniqid() . "@nbs-library-system.com";
        $mail->MessageID = "<$uniqueId>";
        $mail->addCustomHeader("References", "");
        $mail->addCustomHeader("In-Reply-To", "");

        $mail->addAddress($email, $borrowerName);
        $mail->Subject = "Library Due Date Reminder - " . date('M d, Y H:i:s');

        $mail->Body = "
            Hi $borrowerName,<br><br>
            This is a reminder that the following books you borrowed are due tomorrow, <b>$dueDate</b>:<br>
            $bookList
            Please return them tomorrow before 4:00PM(library closing time) to avoid penalties.<br><br>
            <i><b>Note:</b> This is an automated email — please do not reply.</i><br><br>
            Thank you!
        ";

        if ($mail->send()) {
            $updateReminderQuery = "UPDATE borrowings
                                    SET reminder_sent = 1
                                    WHERE user_id = '$userId'
                                    AND DATE(due_date) = CURDATE() + INTERVAL 1 DAY";
            $conn->query($updateReminderQuery);
        }

    } catch (Exception $e) {
        echo "Email sending failed for {$email}. Error: {$mail->ErrorInfo}";
    }

}



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
    .info-card {
        border-left-color: #36b9cc;
    }
    .danger-card {
        border-left-color: #e74a3b;
    }
</style>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <h1 class="h3 mb-0 text-gray-800 mr-3">Borrowed Books</h1>
            <a href="fines.php" class="btn btn-danger ml-3">
                <i class="fas fa-exclamation-circle mr-1"></i>
                Unpaid Fines: <span class="font-weight-bold"><?php echo $formattedFines; ?></span>
            </a>
        </div>

        <!-- Generate Receipt Form -->
        <form action="borrowed-books-receipt.php" method="post" id="receiptForm" target="_blank" onsubmit="return validateForm()" class="d-flex align-items-center">
            <div class="col-auto p-2">
                <label for="school_id" class="col-form-label" style="font-size:medium;">Enter ID Number:</label>
            </div>
            <div class="col-auto p-2" style="width:200px;">
                <input type="text" name="school_id" id="school_id" class="form-control custom" placeholder="ID Number" required>
            </div>
            <div class="col-auto p-2">
                <button class="btn btn-danger btn-block" type="submit">Generate Loan Receipt</button>
            </div>
        </form>
    </div>

    <!-- Borrowed Books Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Filter Borrowed Books</h6>
            <button class="btn btn-sm btn-primary" id="toggleFilter">
                <i class="fas fa-filter"></i> Toggle Filter
            </button>
        </div>
        <div class="card-body <?= empty($filterParams) ? 'd-none' : '' ?>" id="filterForm">
            <form method="get" action="" class="mb-0" id="borrowingsFilterForm">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control form-control-sm" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="Active" <?= ($statusFilter == 'Active') ? 'selected' : '' ?>>Active</option>
                                <option value="Overdue" <?= ($statusFilter == 'Overdue') ? 'selected' : '' ?>>Overdue</option>
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

    <!-- Borrowed Books Table Card - Moved above statistics cards -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Borrowed Books List</h6>

            <div>
                <!-- Results summary -->
                <span id="filterSummary" class="mr-3 <?= empty($filterParams) ? 'd-none' : '' ?>">
                    <span class="text-primary font-weight-bold">Filter applied:</span> 
                    Showing <span id="totalResults"><?= $totalRecords ?></span> result<span id="pluralSuffix"><?= $totalRecords != 1 ? 's' : '' ?></span>
                </span>
                <button id="returnSelectedBtn" class="btn btn-success btn-sm mr-2" disabled>
                    Return Selected (<span id="selectedCount">0</span>)
                </button>
                <button id="updateDueDateBtn" class="btn btn-primary btn-sm">
                    Update Due Date (<span id="selectedCountDueDate">0</span>)
                </button>
            </div>
        </div>
        <div class="card-body">
            <style>
                .table-responsive {
                    overflow-x: auto;
                }
                .table td, .table th {
                    white-space: nowrap;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 30px;">
                                <input type="checkbox" id="selectAll" title="Select/Unselect All">
                            </th>
                            <th class="text-center">Accession No.</th>
                            <th class="text-center">Book Title</th>
                            <th class="text-center">Borrower's Name</th>
                            <th class="text-center">ID Number</th>
                            <th class="text-center">Borrow Date</th>
                            <th class="text-center">Due Date</th>
                            <th class="text-center">Shelf Location</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr data-book-id="<?php echo $row['book_id']; ?>"
                                data-book-title="<?php echo htmlspecialchars($row['title']); ?>"
                                data-borrower="<?php echo htmlspecialchars($row['borrower']); ?>">
                                <td class="text-center">
                                    <input type="checkbox" class="borrow-checkbox"
                                        data-borrow-id="<?php echo $row['borrow_id']; ?>"
                                        data-current-due-date="<?php echo $row['due_date']; ?>">
                                </td>
                                <td class="text-center"><?php echo $row['accession']; ?></td>
                                <td class="text-left"><?php echo htmlspecialchars($row['title']); ?></td>
                                <td class="text-left"><?php echo htmlspecialchars($row['borrower']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['school_id']); ?></td>
                                <td class="text-center"><?php echo date('M d, Y', strtotime($row['issue_date'])); ?></td>
                                <td class="text-center"><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['shelf_location']); ?></td>
                                <td class="text-center">
                                    <?php
                                    $status = htmlspecialchars($row['status']);
                                    $statusClass = '';
                                    if ($status === 'Active') {
                                        $statusClass = 'badge badge-success';
                                    } elseif ($status === 'Overdue') {
                                        $statusClass = 'badge badge-danger';
                                    }
                                    echo "<span class='$statusClass'>$status</span>";
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Statistics Cards - Moved below the table card -->
    <h4 class="mb-3 text-gray-800">Statistics Overview</h4>
    <div class="row mb-4">
        <!-- Total Borrowed Books -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 stats-card primary-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1 stats-title">
                                Total Borrowed Books</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $totalBorrowed; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300 stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Borrowed Today -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 stats-card success-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1 stats-title">
                                Borrowed Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $todayBorrowed; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300 stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Items -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 stats-card info-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1 stats-title">
                                Active Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $activeItems; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300 stats-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Items -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2 stats-card danger-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1 stats-title">
                                Overdue Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $overdueItems; ?></div>
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

<!-- Due Date Update Modal -->
<div class="modal fade" id="dueDateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Due Date</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="newDueDate">New Due Date</label>
                    <input type="date" class="form-control" id="newDueDate" min="">
                    <small class="text-muted">Due date must be at least 7 days after the borrow date</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmDueDate">Update</button>
            </div>
        </div>
    </div>
</div>

<!-- Add this right after the main table -->
<div class="context-menu" style="display: none; position: absolute; z-index: 1000;">
    <ul class="list-group">
        <li class="list-group-item" data-action="returned">Mark as Returned</li>
        <li class="list-group-item" data-action="lost">Mark as Lost</li>
        <li class="list-group-item" data-action="damaged">Mark as Damaged</li>
    </ul>
</div>

<?php include('inc/footer.php'); ?>

<!-- Add these before the closing </head> tag -->
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="inc/js/sb-admin-2.min.js"></script>
<script src="inc/assets/DataTables/datatables.min.js"></script>
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
                url: 'borrowed_books.php',
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
        $('#borrowingsFilterForm').on('submit', function(e) {
            e.preventDefault();
            
            // Store the current visibility state of the filter form
            const isFilterVisible = !$('#filterForm').hasClass('d-none');
            
            // Submit the form using AJAX
            $.ajax({
                url: 'borrowed_books.php',
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

        // Store references
        const contextMenu = $('.context-menu');
        let $selectedRow = null;

        // Add CSS to hide sorting icons for checkbox column BEFORE initializing DataTable
        $('<style>')
            .text(`
                #dataTable thead th:first-child.sorting::before,
                #dataTable thead th:first-child.sorting::after,
                #dataTable thead th:first-child.sorting_asc::before,
                #dataTable thead th:first-child.sorting_asc::after,
                #dataTable thead th:first-child.sorting_desc::before,
                #dataTable thead th:first-child.sorting_desc::after {
                    display: none !important;
                }
            `)
            .appendTo('head');

        // Initialize DataTable
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
            "order": [[1, 'asc']], // Start with sorting on the second column
            "columnDefs": [
                // Disable sorting for checkbox column and set minimal width
                { "orderable": false, "targets": 0, "searchable": false, "width": "30px" },
                // Accession No. - 10%
                { "targets": 1, "width": "10%" },
                // Book Title - 40%
                { "targets": 2, "width": "40%" },
                // Borrower's Name - 20%
                { "targets": 3, "width": "20%" },
                // ID Number - auto width
                { "targets": 4 },
                // Borrow Date - 10%
                { "targets": 5, "width": "10%" },
                // Due Date - 10%
                { "targets": 6, "width": "10%" },
                // Shelf Location - auto width
                { "targets": 7 },
                // Status - 10%
                { "targets": 8, "width": "10%" }
            ],
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search..."
            },
            "initComplete": function() {
                $('#dataTable_filter input').addClass('form-control form-control-sm');
                $('#dataTable_filter').addClass('d-flex align-items-center');
                $('#dataTable_filter label').append('<i class="fas fa-search ml-2"></i>');
                $('#dataTable_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
                
                // Force re-apply the CSS for checkbox column icons
                setTimeout(function() {
                    $('#dataTable thead th:first-child').removeClass('sorting').addClass('sorting_disabled');
                }, 0);
            }
        });

        // Add window resize handler
        $(window).on('resize', function() {
            table.columns.adjust().draw();
        });

        // Right-click handler for table rows
        $('#dataTable tbody').on('contextmenu', 'tr', function(e) {
            e.preventDefault();

            $selectedRow = $(this);
            const status = $selectedRow.find('td:last').text().trim();

            // Don't show menu for books already marked as returned, lost, or damaged
            if (['Returned', 'Lost', 'Damaged'].includes(status)) {
                return;
            }

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

            const bookId = $selectedRow.data('book-id');
            const bookTitle = $selectedRow.data('book-title');
            const borrower = $selectedRow.data('borrower');
            const action = $(this).data('action');
            let url = '';
            let confirmConfig = {};

            switch(action) {
                case 'returned':
                    url = 'book_returned.php';
                    confirmConfig = {
                        title: 'Return Book?',
                        html: `Are you sure this book has been returned?<br><br>
                              <b>Book:</b> ${bookTitle}<br>
                              <b>Borrower:</b> ${borrower}`,
                        icon: 'question',
                        confirmButtonText: 'Yes, Return it',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d'
                    };
                    break;
                case 'lost':
                    url = 'book_lost.php';
                    confirmConfig = {
                        title: 'Mark as Lost?',
                        html: `Are you sure you want to mark this book as lost?<br><br>
                              <b>Book:</b> ${bookTitle}<br>
                              <b>Borrower:</b> ${borrower}`,
                        icon: 'warning',
                        confirmButtonText: 'Yes, Mark as Lost',
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d'
                    };
                    break;
                case 'damaged':
                    url = 'book_damaged.php';
                    confirmConfig = {
                        title: 'Mark as Damaged?',
                        html: `Are you sure you want to mark this book as damaged?<br><br>
                              <b>Book:</b> ${bookTitle}<br>
                              <b>Borrower:</b> ${borrower}`,
                        icon: 'warning',
                        confirmButtonText: 'Yes, Mark as Damaged',
                        confirmButtonColor: '#ffc107',
                        cancelButtonColor: '#6c757d'
                    };
                    break;
            }

            if (url && confirmConfig) {
                Swal.fire({
                    ...confirmConfig,
                    showCancelButton: true,
                    cancelButtonText: 'Cancel',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return fetch(`${url}?id=${bookId}`)
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
                            text: 'The book status has been updated.',
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

        // Add to your existing document.ready function
        const selectAll = $('#selectAll');
        const borrowCheckboxes = $('.borrow-checkbox');
        const updateDueDateBtn = $('#updateDueDateBtn');
        const selectedCountSpan = $('#selectedCount');
        let selectedItems = [];

        // Handle select all checkbox
        selectAll.change(function() {
            borrowCheckboxes.prop('checked', this.checked);
            updateSelectedCount();
        });

        // Handle individual checkboxes
        borrowCheckboxes.change(function() {
            updateSelectedCount();
            if (!$(this).prop('checked')) {
                selectAll.prop('checked', false);
            }
        });

        // Update selected count and button state
        function updateSelectedCount() {
            selectedItems = $('.borrow-checkbox:checked').map(function() {
                return $(this).data('borrow-id');
            }).get();

            const count = selectedItems.length;
            $('#selectedCount, #selectedCountDueDate').text(count);
            $('#updateDueDateBtn, #returnSelectedBtn').prop('disabled', count === 0);
        }

        // Initialize the modal
        const dueDateModal = $('#dueDateModal');

        // Handle modal close buttons
        $('.close, button[data-dismiss="modal"]').on('click', function() {
            dueDateModal.modal('hide');
        });

        // Reset form when modal is closed
        dueDateModal.on('hidden.bs.modal', function () {
            $('#newDueDate').val('');
            selectedItems = [];
            updateSelectedCount();
            $('.borrow-checkbox, #selectAll').prop('checked', false);
        });

        // Handle update due date button click
        updateDueDateBtn.click(function() {
            // Get minimum issue date from selected books
            let minIssueDate = null;
            $('.borrow-checkbox:checked').each(function() {
                const row = $(this).closest('tr');
                const issueDate = new Date(row.find('td:eq(4)').text()); // Assuming issue date is in 5th column
                if (!minIssueDate || issueDate < minIssueDate) {
                    minIssueDate = issueDate;
                }
            });

            // Set minimum date to the earliest issue date
            if (minIssueDate) {
                const minDateStr = minIssueDate.toISOString().split('T')[0];
                $('#newDueDate').attr('min', minDateStr);
            }

            dueDateModal.modal('show');
        });

        // Handle due date update confirmation
        $('#confirmDueDate').click(function() {
            const newDueDate = $('#newDueDate').val();
            if (!newDueDate) {
                Swal.fire('Error', 'Please select a new due date', 'error');
                return;
            }

            Swal.fire({
                title: 'Update Due Dates?',
                text: `Are you sure you want to update the due date for ${selectedItems.length} items?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, update them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send AJAX request to update due dates
                    $.ajax({
                        url: 'update_due_dates.php',
                        method: 'POST',
                        data: {
                            borrowIds: selectedItems,
                            newDueDate: newDueDate
                        },
                        success: function(response) {
                            const result = JSON.parse(response);
                            if (result.success) {
                                Swal.fire('Success', result.message, 'success')
                                .then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error', result.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to update due dates', 'error');
                        }
                    });
                }
            });

            dueDateModal.modal('hide');
        });

        // Add bulk return handler
        $('#returnSelectedBtn').click(function() {
            const selectedBooks = $('.borrow-checkbox:checked').map(function() {
                const row = $(this).closest('tr');
                return {
                    id: $(this).data('borrow-id'),
                    title: row.data('book-title'),
                    borrower: row.data('borrower')
                };
            }).get();

            let booksListHtml = '<ul class="list-group mt-3">';
            selectedBooks.forEach(book => {
                booksListHtml += `<li class="list-group-item">${book.title} - ${book.borrower}</li>`;
            });
            booksListHtml += '</ul>';

            Swal.fire({
                title: 'Return Selected Books?',
                html: `Are you sure you want to return the following books?${booksListHtml}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Return All',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return $.ajax({
                        url: 'bulk_return_books.php',
                        method: 'POST',
                        data: { borrowIds: selectedItems },
                        dataType: 'json'
                    }).catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Success!',
                        text: result.value.message,
                        icon: 'success'
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        });

        // Update the select all checkbox handler
        $('#selectAll').change(function() {
            const isChecked = $(this).prop('checked');
            $('.borrow-checkbox').each(function() {
                const $row = $(this).closest('tr');
                const status = $row.find('td:eq(6) span').text().trim();
                // Only select checkboxes for Active or Overdue items
                if (status === 'Active' || status === 'Overdue') {
                    $(this).prop('checked', isChecked);
                }
            });
            updateSelectedCount();
        });

        // Update select all state when individual checkboxes change
        $(document).on('change', '.borrow-checkbox', function() {
            const totalEligible = $('.borrow-checkbox').filter(function() {
                const status = $(this).closest('tr').find('td:eq(6) span').text().trim();
                return status === 'Active' || status === 'Overdue';
            }).length;

            const totalChecked = $('.borrow-checkbox:checked').length;

            $('#selectAll').prop({
                'checked': totalChecked > 0 && totalChecked === totalEligible,
                'indeterminate': totalChecked > 0 && totalChecked < totalEligible
            });

            updateSelectedCount();
        });

        // Function to initialize DataTable with consistent settings
        function initializeDataTable() {
            if ($.fn.DataTable.isDataTable('#dataTable')) {
                $('#dataTable').DataTable().destroy();
            }
            
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
                "order": [[1, 'asc']], // Start with sorting on the second column
                "columnDefs": [
                    // Disable sorting for checkbox column and set minimal width
                    { "orderable": false, "targets": 0, "searchable": false, "width": "30px" },
                    // Column widths as in original code
                    { "targets": 1, "width": "10%" },
                    { "targets": 2, "width": "40%" },
                    { "targets": 3, "width": "20%" },
                    { "targets": 5, "width": "10%" },
                    { "targets": 6, "width": "10%" },
                    { "targets": 8, "width": "10%" }
                ],
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search..."
                },
                "initComplete": function() {
                    $('#dataTable_filter input').addClass('form-control form-control-sm');
                    $('#dataTable_filter').addClass('d-flex align-items-center');
                    $('#dataTable_filter label').append('<i class="fas fa-search ml-2"></i>');
                    $('#dataTable_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
                    
                    // Force re-apply the CSS for checkbox column icons
                    setTimeout(function() {
                        $('#dataTable thead th:first-child').removeClass('sorting').addClass('sorting_disabled');
                    }, 0);
                }
            });
            
            // Re-bind checkbox events
            $('#selectAll').change(function() {
                const isChecked = $(this).prop('checked');
                $('.borrow-checkbox').each(function() {
                    const $row = $(this).closest('tr');
                    const status = $row.find('td:eq(8) span').text().trim();
                    // Only select checkboxes for Active or Overdue items
                    if (status === 'Active' || status === 'Overdue') {
                        $(this).prop('checked', isChecked);
                    }
                });
                updateSelectedCount();
            });
            
            $(document).on('change', '.borrow-checkbox', function() {
                const totalEligible = $('.borrow-checkbox').filter(function() {
                    const status = $(this).closest('tr').find('td:eq(8) span').text().trim();
                    return status === 'Active' || status === 'Overdue';
                }).length;
                
                const totalChecked = $('.borrow-checkbox:checked').length;
                
                $('#selectAll').prop({
                    'checked': totalChecked > 0 && totalChecked === totalEligible,
                    'indeterminate': totalChecked > 0 && totalChecked < totalEligible
                });
                
                updateSelectedCount();
            });
        }

        // Initialize DataTable on page load
        initializeDataTable();

        // Add window resize handler
        $(window).on('resize', function() {
            table.columns.adjust().draw();
        });
    });
</script>
</body>
</html>
