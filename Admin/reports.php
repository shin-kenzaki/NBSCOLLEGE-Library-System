<?php
session_start();
include '../db.php';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin'])) {
    header("Location: index.php");
    exit();
}

// Get data for the past 7 days
$today = date('Y-m-d');
$past7Days = [];
$labels = [];

// Generate last 7 days dates
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $past7Days[] = $date;
    $labels[] = date('M d', strtotime("-$i days"));
}

// Get borrowings data for last 7 days - multiple metrics
$activeBorrowings = [];
$returnedBorrowings = [];
$damagedBorrowings = [];
$lostBorrowings = [];

foreach ($past7Days as $day) {
    // Active borrowings on this day
    $sql = "SELECT COUNT(*) AS count FROM borrowings WHERE DATE(issue_date) = '$day' AND status = 'Active'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $activeBorrowings[] = $row['count'];
    
    // Returned borrowings on this day
    $sql = "SELECT COUNT(*) AS count FROM borrowings WHERE DATE(return_date) = '$day' AND status = 'Returned'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $returnedBorrowings[] = $row['count'];
    
    // Damaged borrowings on this day
    $sql = "SELECT COUNT(*) AS count FROM borrowings WHERE DATE(report_date) = '$day' AND status = 'Damaged'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $damagedBorrowings[] = $row['count'];
    
    // Lost borrowings on this day
    $sql = "SELECT COUNT(*) AS count FROM borrowings WHERE DATE(report_date) = '$day' AND status = 'Lost'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $lostBorrowings[] = $row['count'];
}

// Fetch recent borrowings for the table with pagination and filtering
$page = isset($_GET['bpage']) ? intval($_GET['bpage']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Get filter parameters
$statusFilter = isset($_GET['bstatus']) ? $_GET['bstatus'] : '';
$dateStart = isset($_GET['bdate_start']) ? $_GET['bdate_start'] : '';
$dateEnd = isset($_GET['bdate_end']) ? $_GET['bdate_end'] : '';
$userFilter = isset($_GET['buser']) ? $_GET['buser'] : '';
$bookFilter = isset($_GET['bbook']) ? $_GET['bbook'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= $whereClause ? " AND b.status = '$statusFilter'" : "WHERE b.status = '$statusFilter'";
    $filterParams[] = "bstatus=$statusFilter";
}

if ($dateStart) {
    $whereClause .= $whereClause ? " AND b.issue_date >= '$dateStart'" : "WHERE b.issue_date >= '$dateStart'";
    $filterParams[] = "bdate_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= $whereClause ? " AND b.issue_date <= '$dateEnd'" : "WHERE b.issue_date <= '$dateEnd'";
    $filterParams[] = "bdate_end=$dateEnd";
}

if ($userFilter) {
    $whereClause .= $whereClause ? " AND (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')" : 
                                   "WHERE (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')";
    $filterParams[] = "buser=" . urlencode($userFilter);
}

if ($bookFilter) {
    $whereClause .= $whereClause ? " AND (bk.title LIKE '%$bookFilter%' OR bk.accession LIKE '%$bookFilter%')" : 
                                   "WHERE (bk.title LIKE '%$bookFilter%' OR bk.accession LIKE '%$bookFilter%')";
    $filterParams[] = "bbook=" . urlencode($bookFilter);
}

// Count total borrowings for pagination with filters
$countSql = "SELECT COUNT(*) as total FROM borrowings b 
             LEFT JOIN users u ON b.user_id = u.id
             LEFT JOIN books bk ON b.book_id = bk.id
             $whereClause";
             
$countResult = mysqli_query($conn, $countSql);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Create pagination URL parameter string
$filterQueryString = $filterParams ? "&" . implode("&", $filterParams) : "";

// Get recent borrowings with user and book details
$borrowingsSql = "SELECT b.id, b.status, b.issue_date, b.due_date, b.return_date, b.report_date, 
                  u.school_id, u.firstname as user_firstname, u.lastname as user_lastname, u.usertype,
                  bk.accession, bk.title, 
                  a.firstname as admin_firstname, a.lastname as admin_lastname, a.role as admin_role
                  FROM borrowings b
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN books bk ON b.book_id = bk.id
                  LEFT JOIN admins a ON b.issued_by = a.id
                  $whereClause
                  ORDER BY b.issue_date DESC
                  LIMIT $offset, $recordsPerPage";
$borrowingsResult = mysqli_query($conn, $borrowingsSql);

// Get all statuses for filter dropdown
$statusesSql = "SELECT DISTINCT status FROM borrowings ORDER BY status";
$statusesResult = mysqli_query($conn, $statusesSql);
$statuses = [];
while ($row = mysqli_fetch_assoc($statusesResult)) {
    $statuses[] = $row['status'];
}

// Get reservations data for last 7 days - multiple metrics
$pendingReservations = [];
$readyReservations = [];
$recievedReservations = [];
$cancelledReservations = [];

foreach ($past7Days as $day) {
    // Pending reservations made on this day
    $sql = "SELECT COUNT(*) AS count FROM reservations WHERE DATE(reserve_date) = '$day' AND status = 'Pending'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $pendingReservations[] = $row['count'];
    
    // Ready reservations made on this day
    $sql = "SELECT COUNT(*) AS count FROM reservations WHERE DATE(ready_date) = '$day' AND status = 'Ready'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $readyReservations[] = $row['count'];
    
     // Recieved reservations made on this day
    $sql = "SELECT COUNT(*) AS count FROM reservations WHERE DATE(recieved_date) = '$day' AND status = 'Recieved'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $recievedReservations[] = $row['count'];
    
    // Cancelled reservations made on this day
    $sql = "SELECT COUNT(*) AS count FROM reservations WHERE DATE(cancel_date) = '$day' AND status = 'Cancelled'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $cancelledReservations[] = $row['count'];
}

// Get users data for last 7 days - multiple metrics
$adminCounts = [];
$librarianCounts = [];
$assistantCounts = [];
$encoderCounts = [];
$studentCounts = [];
$facultyCounts = [];
$staffCounts = [];
$visitorCounts = [];

foreach ($past7Days as $day) {
    // Admin counts
    $sql = "SELECT COUNT(*) AS count FROM admins WHERE DATE(date_added) = '$day' AND role = 'Admin'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $adminCounts[] = $row['count'];

    // Librarian counts
    $sql = "SELECT COUNT(*) AS count FROM admins WHERE DATE(date_added) = '$day' AND role = 'Librarian'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $librarianCounts[] = $row['count'];

    // Assistant counts
    $sql = "SELECT COUNT(*) AS count FROM admins WHERE DATE(date_added) = '$day' AND role = 'Assistant'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $assistantCounts[] = $row['count'];

    // Encoder counts
    $sql = "SELECT COUNT(*) AS count FROM admins WHERE DATE(date_added) = '$day' AND role = 'Encoder'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $encoderCounts[] = $row['count'];
    
    // Student users added on this day
    $sql = "SELECT COUNT(*) AS count FROM users WHERE date_added = '$day' AND usertype = 'Student'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $studentCounts[] = $row['count'];
    
    // Faculty users added on this day
    $sql = "SELECT COUNT(*) AS count FROM users WHERE date_added = '$day' AND usertype = 'Faculty'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $facultyCounts[] = $row['count'];

    // Staff users added on this day
    $sql = "SELECT COUNT(*) AS count FROM users WHERE date_added = '$day' AND usertype = 'Staff'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $staffCounts[] = $row['count'];

    // Visitor users added on this day
    $sql = "SELECT COUNT(*) AS count FROM users WHERE date_added = '$day' AND usertype = 'Visitor'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $visitorCounts[] = $row['count'];
}

// Get books data for last 7 days - multiple metrics
$addedBooks = [];
foreach ($past7Days as $day) {
    // Count added books based on date_added
    $sql = "SELECT COUNT(*) AS count FROM books WHERE DATE(date_added) = '$day'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $addedBooks[] = $row['count'];
}

// Update Books JSON data with only the added books metric
$booksJSON = json_encode([
    'added' => $addedBooks
]);

// Get fines data for last 7 days - multiple metrics
$paidFines = [];
$unpaidFines = [];
foreach ($past7Days as $day) {
    // Paid fines added on this day
    $sql = "SELECT COUNT(*) AS count FROM fines WHERE DATE(date) = '$day' AND status = 'Paid'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $paidFines[] = $row['count'];
    
    // Unpaid fines added on this day
    $sql = "SELECT COUNT(*) AS count FROM fines WHERE DATE(date) = '$day' AND status = 'Unpaid'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $unpaidFines[] = $row['count'];
}

// Update fines JSON data with only paid and unpaid
$finesJSON = json_encode([
    'paid' => $paidFines,
    'unpaid' => $unpaidFines
]);

// Convert data arrays to JSON for JavaScript
$labelsJSON = json_encode($labels);

// Borrowings JSON data
$borrowingsJSON = json_encode([
    'active' => $activeBorrowings,
    'returned' => $returnedBorrowings,
    'damaged' => $damagedBorrowings,
    'lost' => $lostBorrowings
]);

// Reservations JSON data
$reservationsJSON = json_encode([
    'pending' => $pendingReservations,
    'ready' => $readyReservations,
    'recieved' => $recievedReservations,
    'cancelled' => $cancelledReservations
]);

// Users JSON data
$usersJSON = json_encode([
    'admin' => $adminCounts,
    'librarian' => $librarianCounts,
    'assistant' => $assistantCounts,
    'encoder' => $encoderCounts,
    'students' => $studentCounts,
    'faculty' => $facultyCounts,
    'staff' => $staffCounts,
    'visitor' => $visitorCounts
]);

// Books JSON data
$booksJSON = json_encode([
    'added' => $addedBooks
]);

// Fines JSON data
$finesJSON = json_encode([
    'paid' => $paidFines,
    'unpaid' => $unpaidFines
]);

// Fetch recent reservations for the table with pagination and filtering
$rpage = isset($_GET['rpage']) ? intval($_GET['rpage']) : 1;
$rrecordsPerPage = 10;
$roffset = ($rpage - 1) * $rrecordsPerPage;

// Get filter parameters for reservations
$rstatusFilter = isset($_GET['rstatus']) ? $_GET['rstatus'] : '';
$rdateStart = isset($_GET['rdate_start']) ? $_GET['rdate_start'] : '';
$rdateEnd = isset($_GET['rdate_end']) ? $_GET['rdate_end'] : '';
$ruserFilter = isset($_GET['ruser']) ? $_GET['ruser'] : '';
$rbookFilter = isset($_GET['rbook']) ? $_GET['rbook'] : '';

// Build the SQL WHERE clause for filtering reservations
$rwhereClause = "";
$rfilterParams = [];

if ($rstatusFilter) {
    $rwhereClause .= $rwhereClause ? " AND r.status = '$rstatusFilter'" : "WHERE r.status = '$rstatusFilter'";
    $rfilterParams[] = "rstatus=$rstatusFilter";
}

if ($rdateStart) {
    $rwhereClause .= $rwhereClause ? " AND DATE(r.reserve_date) >= '$rdateStart'" : "WHERE DATE(r.reserve_date) >= '$rdateStart'";
    $rfilterParams[] = "rdate_start=$rdateStart";
}

if ($rdateEnd) {
    $rwhereClause .= $rwhereClause ? " AND DATE(r.reserve_date) <= '$rdateEnd'" : "WHERE DATE(r.reserve_date) <= '$rdateEnd'";
    $rfilterParams[] = "rdate_end=$rdateEnd";
}

if ($ruserFilter) {
    $rwhereClause .= $rwhereClause ? " AND (u.firstname LIKE '%$ruserFilter%' OR u.lastname LIKE '%$ruserFilter%' OR u.school_id LIKE '%$ruserFilter%')" : 
                                   "WHERE (u.firstname LIKE '%$ruserFilter%' OR u.lastname LIKE '%$ruserFilter%' OR u.school_id LIKE '%$ruserFilter%')";
    $rfilterParams[] = "ruser=" . urlencode($ruserFilter);
}

if ($rbookFilter) {
    $rwhereClause .= $rwhereClause ? " AND (bk.title LIKE '%$rbookFilter%' OR bk.accession LIKE '%$rbookFilter%')" : 
                                   "WHERE (bk.title LIKE '%$rbookFilter%' OR bk.accession LIKE '%$rbookFilter%')";
    $rfilterParams[] = "rbook=" . urlencode($rbookFilter);
}

// Count total reservations for pagination with filters
$rcountSql = "SELECT COUNT(*) as total FROM reservations r 
             LEFT JOIN users u ON r.user_id = u.id
             LEFT JOIN books bk ON r.book_id = bk.id
             $rwhereClause";
             
$rcountResult = mysqli_query($conn, $rcountSql);
$rtotalRecords = mysqli_fetch_assoc($rcountResult)['total'];
$rtotalPages = ceil($rtotalRecords / $rrecordsPerPage);

// Create pagination URL parameter string for reservations
$rfilterQueryString = $rfilterParams ? "&" . implode("&", $rfilterParams) : "";

// Get recent reservations with user and book details
$reservationsSql = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date,
                  u.school_id, u.firstname as user_firstname, u.lastname as user_lastname, u.usertype,
                  bk.accession, bk.title, 
                  a1.firstname as ready_admin_firstname, a1.lastname as ready_admin_lastname, a1.role as ready_admin_role,
                  a2.firstname as issued_admin_firstname, a2.lastname as issued_admin_lastname, a2.role as issued_admin_role
                  FROM reservations r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books bk ON r.book_id = bk.id
                  LEFT JOIN admins a1 ON r.ready_by = a1.id
                  LEFT JOIN admins a2 ON r.issued_by = a2.id
                  $rwhereClause
                  ORDER BY r.reserve_date DESC
                  LIMIT $roffset, $rrecordsPerPage";
$reservationsResult = mysqli_query($conn, $reservationsSql);

// Get all reservation statuses for filter dropdown
$rstatusesSql = "SELECT DISTINCT status FROM reservations ORDER BY status";
$rstatusesResult = mysqli_query($conn, $rstatusesSql);
$rstatuses = [];
while ($row = mysqli_fetch_assoc($rstatusesResult)) {
    $rstatuses[] = $row['status'];
}

include 'inc/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Reports</h1>

    <ul class="nav nav-tabs" id="myTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="borrowings-tab" data-toggle="tab" href="#borrowings" role="tab" aria-controls="borrowings" aria-selected="true">Borrowings</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="reservations-tab" data-toggle="tab" href="#reservations" role="tab" aria-controls="reservations" aria-selected="false">Reservations</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="users-tab" data-toggle="tab" href="#users" role="tab" aria-controls="users" aria-selected="false">Admins/Users</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="books-tab" data-toggle="tab" href="#books" role="tab" aria-controls="books" aria-selected="false">Books</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="fines-tab" data-toggle="tab" href="#fines" role="tab" aria-controls="fines" aria-selected="false">Fines</a>
        </li>
    </ul>

    <div class="tab-content" id="myTabsContent">
        <div class="tab-pane fade show active" id="borrowings" role="tabpanel" aria-labelledby="borrowings-tab">
            <!-- Borrowings Filter -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Filter Borrowings</h6>
                    <button class="btn btn-sm btn-primary" id="toggleBorrowingsFilter">
                        <i class="fas fa-filter"></i> Toggle Filter
                    </button>
                </div>
                <div class="card-body <?= empty($filterParams) ? 'd-none' : '' ?>" id="borrowingsFilterForm">
                    <form method="get" action="" class="mb-0" id="borrowingsFilterFormElement">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="bstatus">Status</label>
                                    <select class="form-control form-control-sm" id="bstatus" name="bstatus">
                                        <option value="">All Statuses</option>
                                        <?php foreach($statuses as $status): ?>
                                            <option value="<?= $status ?>" <?= ($statusFilter == $status) ? 'selected' : '' ?>>
                                                <?= $status ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="bdate_start">From Date</label>
                                    <input type="date" class="form-control form-control-sm" id="bdate_start" 
                                           name="bdate_start" value="<?= $dateStart ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="bdate_end">To Date</label>
                                    <input type="date" class="form-control form-control-sm" id="bdate_end" 
                                           name="bdate_end" value="<?= $dateEnd ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="buser">Borrower</label>
                                    <input type="text" class="form-control form-control-sm" id="buser" 
                                           name="buser" placeholder="Name or ID" value="<?= htmlspecialchars($userFilter) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="bbook">Book</label>
                                    <input type="text" class="form-control form-control-sm" id="bbook" 
                                           name="bbook" placeholder="Title or Accession" value="<?= htmlspecialchars($bookFilter) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group d-flex justify-content-center" style="margin-top: 2rem">
                                    <button type="button" id="applyFilters" class="btn btn-primary btn-sm mr-2">
                                        <i class="fas fa-filter"></i> Apply
                                    </button>
                                    <button type="button" id="resetFilters" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Borrowings Table -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Borrowings</h6>
                    <div>
                        <button class="btn btn-sm btn-success" id="exportBorrowingsTable">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Results summary -->
                    <div id="filterSummary" class="mb-3 <?= empty($filterParams) ? 'd-none' : '' ?>">
                        <span class="text-primary font-weight-bold">Filter applied:</span> 
                        Showing <span id="totalResults"><?= $totalRecords ?></span> result<span id="pluralSuffix"><?= $totalRecords != 1 ? 's' : '' ?></span>
                    </div>
                
                    <div class="table-responsive" id="borrowingsTableContainer">
                        <!-- Table content will be loaded here -->
                        <table class="table table-bordered table-striped" id="borrowingsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Borrower</th>
                                    <th>Book</th>
                                    <th>Status</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Return Date</th>
                                    <th>Issued By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($borrowingsResult) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($borrowingsResult)): ?>
                                        <tr>
                                            <td><?= $row['id'] ?></td>
                                            <td>
                                                <?= htmlspecialchars($row['user_firstname'] . ' ' . $row['user_lastname']) ?>
                                                <br><small class="text-muted"><?= $row['school_id'] ?> (<?= $row['usertype'] ?>)</small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($row['title']) ?>
                                                <br><small class="text-muted">Accession: <?= $row['accession'] ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                    $statusClass = '';
                                                    switch($row['status']) {
                                                        case 'Active':
                                                            $statusClass = 'badge-primary';
                                                            break;
                                                        case 'Returned':
                                                            $statusClass = 'badge-success';
                                                            break;
                                                        case 'Damaged':
                                                            $statusClass = 'badge-warning';
                                                            break;
                                                        case 'Lost':
                                                            $statusClass = 'badge-danger';
                                                            break;
                                                        default:
                                                            $statusClass = 'badge-secondary';
                                                    }
                                                ?>
                                                <span class="badge <?= $statusClass ?>"><?= $row['status'] ?></span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($row['issue_date'])) ?></td>
                                            <td><?= $row['due_date'] ? date('M d, Y', strtotime($row['due_date'])) : '-' ?></td>
                                            <td><?= $row['return_date'] ? date('M d, Y', strtotime($row['return_date'])) : '-' ?></td>
                                            <td><?= htmlspecialchars($row['admin_firstname'] . ' ' . $row['admin_lastname']) ?>
                                                <br><small class="text-muted">(<?= $row['admin_role'] ?>)</small>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No borrowing records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination with filters -->
                    <div id="paginationContainer">
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Borrowings pagination">
                            <ul class="pagination justify-content-center mt-4">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link pagination-link" href="#" data-page="<?= $page-1 ?>" tabindex="-1">Previous</a>
                                </li>
                                
                                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link pagination-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link pagination-link" href="#" data-page="<?= $page+1 ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Keep the rest of your borrowings report content unchanged -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Borrowings Report - Last 7 Days</h6>
                    <div>
                        <button class="btn btn-sm btn-primary" id="downloadBorrowingsReport">Download Report</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="borrowingsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="reservations" role="tabpanel" aria-labelledby="reservations-tab">
            <!-- Reservations Filter -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Filter Reservations</h6>
                    <button class="btn btn-sm btn-primary" id="toggleReservationsFilter">
                        <i class="fas fa-filter"></i> Toggle Filter
                    </button>
                </div>
                <div class="card-body <?= empty($rfilterParams) ? 'd-none' : '' ?>" id="reservationsFilterForm">
                    <form method="get" action="" class="mb-0" id="reservationsFilterFormElement">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="rstatus">Status</label>
                                    <select class="form-control form-control-sm" id="rstatus" name="rstatus">
                                        <option value="">All Statuses</option>
                                        <?php foreach($rstatuses as $status): ?>
                                            <option value="<?= $status ?>" <?= ($rstatusFilter == $status) ? 'selected' : '' ?>>
                                                <?= $status ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="rdate_start">From Date</label>
                                    <input type="date" class="form-control form-control-sm" id="rdate_start" 
                                           name="rdate_start" value="<?= $rdateStart ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="rdate_end">To Date</label>
                                    <input type="date" class="form-control form-control-sm" id="rdate_end" 
                                           name="rdate_end" value="<?= $rdateEnd ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="ruser">User</label>
                                    <input type="text" class="form-control form-control-sm" id="ruser" 
                                           name="ruser" placeholder="Name or ID" value="<?= htmlspecialchars($ruserFilter) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="rbook">Book</label>
                                    <input type="text" class="form-control form-control-sm" id="rbook" 
                                           name="rbook" placeholder="Title or Accession" value="<?= htmlspecialchars($rbookFilter) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group d-flex justify-content-center" style="margin-top: 2rem">
                                    <button type="button" id="applyReservationsFilters" class="btn btn-primary btn-sm mr-2">
                                        <i class="fas fa-filter"></i> Apply
                                    </button>
                                    <button type="button" id="resetReservationsFilters" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Reservations Table -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Reservations</h6>
                    <div>
                        <button class="btn btn-sm btn-success" id="exportReservationsTable">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Results summary -->
                    <div id="rfilterSummary" class="mb-3 <?= empty($rfilterParams) ? 'd-none' : '' ?>">
                        <span class="text-primary font-weight-bold">Filter applied:</span> 
                        Showing <span id="rtotalResults"><?= $rtotalRecords ?></span> result<span id="rpluralSuffix"><?= $rtotalRecords != 1 ? 's' : '' ?></span>
                    </div>
                
                    <div class="table-responsive" id="reservationsTableContainer">
                        <!-- Table content will be loaded here -->
                        <table class="table table-bordered table-striped" id="reservationsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Book</th>
                                    <th>Status</th>
                                    <th>Reserved On</th>
                                    <th>Ready On</th>
                                    <th>Received On</th>
                                    <th>Staff</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($reservationsResult) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($reservationsResult)): ?>
                                        <tr>
                                            <td><?= $row['id'] ?></td>
                                            <td>
                                                <?= htmlspecialchars($row['user_firstname'] . ' ' . $row['user_lastname']) ?>
                                                <br><small class="text-muted"><?= $row['school_id'] ?> (<?= $row['usertype'] ?>)</small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($row['title']) ?>
                                                <br><small class="text-muted">Accession: <?= $row['accession'] ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                    $statusClass = '';
                                                    switch($row['status']) {
                                                        case 'Pending':
                                                            $statusClass = 'badge-warning';
                                                            break;
                                                        case 'Ready':
                                                            $statusClass = 'badge-info';
                                                            break;
                                                        case 'Recieved':
                                                            $statusClass = 'badge-success';
                                                            break;
                                                        case 'Cancelled':
                                                            $statusClass = 'badge-danger';
                                                            break;
                                                        default:
                                                            $statusClass = 'badge-secondary';
                                                    }
                                                ?>
                                                <span class="badge <?= $statusClass ?>"><?= $row['status'] ?></span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($row['reserve_date'])) ?></td>
                                            <td><?= $row['ready_date'] ? date('M d, Y', strtotime($row['ready_date'])) : '-' ?></td>
                                            <td><?= $row['recieved_date'] ? date('M d, Y', strtotime($row['recieved_date'])) : '-' ?></td>
                                            <td><?= $row['ready_date'] ? htmlspecialchars($row['ready_admin_firstname'] . ' ' . $row['ready_admin_lastname']) . 
                                                '<br><small class="text-muted">(' . $row['ready_admin_role'] . ')</small>' : '-' ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No reservation records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination with filters -->
                    <div id="rpaginationContainer">
                        <?php if ($rtotalPages > 1): ?>
                        <nav aria-label="Reservations pagination">
                            <ul class="pagination justify-content-center mt-4">
                                <li class="page-item <?= ($rpage <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link rpagination-link" href="#" data-page="<?= $rpage-1 ?>" tabindex="-1">Previous</a>
                                </li>
                                
                                <?php for($i = 1; $i <= $rtotalPages; $i++): ?>
                                    <li class="page-item <?= ($rpage == $i) ? 'active' : '' ?>">
                                        <a class="page-link rpagination-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= ($rpage >= $rtotalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link rpagination-link" href="#" data-page="<?= $rpage+1 ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Reservations Report - keeping the original chart -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Reservations Report - Last 7 Days</h6>
                    <div>
                        <button class="btn btn-sm btn-primary" id="downloadReservationsReport">Download Report</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="reservationsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
            <!-- Users Filter -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Filter Users</h6>
                    <button class="btn btn-sm btn-primary" id="toggleUsersFilter">
                        <i class="fas fa-filter"></i> Toggle Filter
                    </button>
                </div>
                <div class="card-body d-none" id="usersFilterForm">
                    <form method="get" action="" class="mb-0" id="usersFilterFormElement">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="urole">User Type</label>
                                    <select class="form-control form-control-sm" id="urole" name="urole">
                                        <option value="">All Types</option>
                                        <option value="Admin">Admin</option>
                                        <option value="Librarian">Librarian</option>
                                        <option value="Assistant">Assistant</option>
                                        <option value="Encoder">Encoder</option>
                                        <option value="Student">Student</option>
                                        <option value="Faculty">Faculty</option>
                                        <option value="Staff">Staff</option>
                                        <option value="Visitor">Visitor</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="udate_start">From Date</label>
                                    <input type="date" class="form-control form-control-sm" id="udate_start" 
                                           name="udate_start" value="">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="udate_end">To Date</label>
                                    <input type="date" class="form-control form-control-sm" id="udate_end" 
                                           name="udate_end" value="">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="usearch">Search</label>
                                    <input type="text" class="form-control form-control-sm" id="usearch" 
                                           name="usearch" placeholder="Name or ID" value="">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="ustatus">Status</label>
                                    <select class="form-control form-control-sm" id="ustatus" name="ustatus">
                                        <option value="">All Statuses</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group d-flex justify-content-center" style="margin-top: 2rem">
                                    <button type="button" id="applyUsersFilters" class="btn btn-primary btn-sm mr-2">
                                        <i class="fas fa-filter"></i> Apply
                                    </button>
                                    <button type="button" id="resetUsersFilters" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Users & Admins</h6>
                    <div>
                        <button class="btn btn-sm btn-success" id="exportUsersTable">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Results summary -->
                    <div id="ufilterSummary" class="mb-3 d-none">
                        <span class="text-primary font-weight-bold">Filter applied:</span> 
                        Showing <span id="utotalResults">0</span> result<span id="upluralSuffix">s</span>
                    </div>
                
                    <div class="table-responsive" id="usersTableContainer">
                        <!-- Table content will be loaded here -->
                        <div class="text-center my-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Loading users data...</p>
                        </div>
                    </div>
                    
                    <!-- Pagination container -->
                    <div id="upaginationContainer"></div>
                </div>
            </div>

            <!-- Users Report - keep the original chart -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Users Report - Last 7 Days</h6>
                    <div>
                        <button class="btn btn-sm btn-primary" id="downloadUsersReport">Download Report</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="usersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="books" role="tabpanel" aria-labelledby="books-tab">
            <!-- Books Filter -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Filter Books</h6>
                    <button class="btn btn-sm btn-primary" id="toggleBooksFilter">
                        <i class="fas fa-filter"></i> Toggle Filter
                    </button>
                </div>
                <div class="card-body d-none" id="booksFilterForm">
                    <form method="get" action="" class="mb-0" id="booksFilterFormElement">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="bookstatus">Status</label>
                                    <select class="form-control form-control-sm" id="bookstatus" name="bookstatus">
                                        <option value="">All Statuses</option>
                                        <option value="Available">Available</option>
                                        <option value="Borrowed">Borrowed</option>
                                        <option value="Reserved">Reserved</option>
                                        <option value="Damaged">Damaged</option>
                                        <option value="Lost">Lost</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="bookdate_start">From Date</label>
                                    <input type="date" class="form-control form-control-sm" id="bookdate_start" 
                                           name="bookdate_start" value="">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="bookdate_end">To Date</label>
                                    <input type="date" class="form-control form-control-sm" id="bookdate_end" 
                                           name="bookdate_end" value="">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="booktitle">Title/Accession</label>
                                    <input type="text" class="form-control form-control-sm" id="booktitle" 
                                           name="booktitle" placeholder="Title or Accession" value="">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="booklocation">Location</label>
                                    <input type="text" class="form-control form-control-sm" id="booklocation" 
                                           name="booklocation" placeholder="Shelf location" value="">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group d-flex justify-content-center" style="margin-top: 2rem">
                                    <button type="button" id="applyBooksFilters" class="btn btn-primary btn-sm mr-2">
                                        <i class="fas fa-filter"></i> Apply
                                    </button>
                                    <button type="button" id="resetBooksFilters" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Books Table -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Books Inventory</h6>
                    <div>
                        <button class="btn btn-sm btn-success" id="exportBooksTable">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Results summary -->
                    <div id="bookfilterSummary" class="mb-3 d-none">
                        <span class="text-primary font-weight-bold">Filter applied:</span> 
                        Showing <span id="booktotalResults">0</span> result<span id="bookpluralSuffix">s</span>
                    </div>
                
                    <div class="table-responsive" id="booksTableContainer">
                        <!-- Table content will be loaded here -->
                        <div class="text-center my-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Loading books data...</p>
                        </div>
                    </div>
                    
                    <!-- Pagination container -->
                    <div id="bookpaginationContainer"></div>
                </div>
            </div>

            <!-- Books Report - existing chart -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Books Report - Last 7 Days</h6>
                    <div>
                        <button class="btn btn-sm btn-primary" id="downloadBooksReport">Download Report</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="booksChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="fines" role="tabpanel" aria-labelledby="fines-tab">
            <!-- Fines Filter -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Filter Fines</h6>
                    <button class="btn btn-sm btn-primary" id="toggleFinesFilter">
                        <i class="fas fa-filter"></i> Toggle Filter
                    </button>
                </div>
                <div class="card-body d-none" id="finesFilterForm">
                    <form method="get" action="" class="mb-0" id="finesFilterFormElement">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="fstatus">Status</label>
                                    <select class="form-control form-control-sm" id="fstatus" name="fstatus">
                                        <option value="">All Statuses</option>
                                        <option value="Paid">Paid</option>
                                        <option value="Unpaid">Unpaid</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="fdate_start">From Date</label>
                                    <input type="date" class="form-control form-control-sm" id="fdate_start" 
                                           name="fdate_start" value="">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="fdate_end">To Date</label>
                                    <input type="date" class="form-control form-control-sm" id="fdate_end" 
                                           name="fdate_end" value="">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="fuser">User</label>
                                    <input type="text" class="form-control form-control-sm" id="fuser" 
                                           name="fuser" placeholder="Name or ID" value="">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="ftype">Fine Type</label>
                                    <select class="form-control form-control-sm" id="ftype" name="ftype">
                                        <option value="">All Types</option>
                                        <option value="Overdue">Overdue</option>
                                        <option value="Damaged">Damaged</option>
                                        <option value="Lost">Lost</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group d-flex justify-content-center" style="margin-top: 2rem">
                                    <button type="button" id="applyFinesFilters" class="btn btn-primary btn-sm mr-2">
                                        <i class="fas fa-filter"></i> Apply
                                    </button>
                                    <button type="button" id="resetFinesFilters" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Fines Table -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Fines Records</h6>
                    <div>
                        <button class="btn btn-sm btn-success" id="exportFinesTable">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Results summary -->
                    <div id="ffilterSummary" class="mb-3 d-none">
                        <span class="text-primary font-weight-bold">Filter applied:</span> 
                        Showing <span id="ftotalResults">0</span> result<span id="fpluralSuffix">s</span>
                    </div>
                
                    <div class="table-responsive" id="finesTableContainer">
                        <!-- Table content will be loaded here -->
                        <div class="text-center my-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Loading fines data...</p>
                        </div>
                    </div>
                    
                    <!-- Pagination container -->
                    <div id="fpaginationContainer"></div>
                </div>
            </div>

            <!-- Fines Report - Chart -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Fines Report - Last 7 Days</h6>
                    <div>
                        <button class="btn btn-sm btn-primary" id="downloadFinesReport">Download Report</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="finesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Updated Line chart configuration function for multiple datasets
function createMultiLineChart(canvasId, labels, datasets, title) {
    var ctx = document.getElementById(canvasId).getContext('2d');
    
    // Chart colors - fixed colors for borrowings
    const colors = {
        active: 'rgba(78, 115, 223, 1)',      // Primary (blue)
        returned: 'rgba(28, 200, 138, 1)',    // Success (green)
        damaged: 'rgba(246, 194, 62, 1)',     // Warning (yellow)
        lost: 'rgba(231, 74, 59, 1)',         // Danger (red)
        
        // Colors for other charts
        total: 'rgba(78, 115, 223, 1)',
        pending: 'rgba(246, 194, 62, 1)',
        ready: 'rgba(54, 185, 204, 1)',
        fulfilled: 'rgba(28, 200, 138, 1)',
        students: 'rgba(153, 102, 255, 1)',
        faculty: 'rgba(255, 159, 64, 1)',
        available: 'rgba(28, 200, 138, 1)',
        borrowed: 'rgba(78, 115, 223, 1)',
        paid: 'rgba(28, 200, 138, 1)',
        unpaid: 'rgba(231, 74, 59, 1)',
        amounts: 'rgba(54, 185, 204, 1)',
        overdue: 'rgba(231, 74, 59, 1)',
        recieved: 'rgba(28, 200, 138, 1)',
        cancelled: 'rgba(231, 74, 59, 1)',
        admin: 'rgba(78, 115, 223, 1)',
        librarian: 'rgba(28, 200, 138, 1)',
        assistant:  'rgba(246, 194, 62, 1)',
        encoder: 'rgba(54, 185, 204, 1)',
        staff: 'rgba(153, 102, 255, 1)',
        visitor: 'rgba(255, 159, 64, 1)'
    };
    
    // Create chart datasets array
    const chartDatasets = [];
    
    // Special handling for borrowings chart to ensure correct colors
    if (canvasId === 'borrowingsChart') {
        // Add datasets in specific order with specific colors
        if (datasets.active) {
            chartDatasets.push(createDataset('Active', datasets.active, colors.active));
        }
        if (datasets.returned) {
            chartDatasets.push(createDataset('Returned', datasets.returned, colors.returned));
        }
        if (datasets.damaged) {
            chartDatasets.push(createDataset('Damaged', datasets.damaged, colors.damaged));
        }
        if (datasets.lost) {
            chartDatasets.push(createDataset('Lost', datasets.lost, colors.lost));
        }
    } else if (canvasId === 'reservationsChart') {
        if (datasets.pending) {
            chartDatasets.push(createDataset('Pending', datasets.pending, colors.pending));
        }
        if (datasets.ready) {
            chartDatasets.push(createDataset('Ready', datasets.ready, colors.ready));
        }
         if (datasets.recieved) {
            chartDatasets.push(createDataset('Recieved', datasets.recieved, colors.recieved));
        }
        if (datasets.cancelled) {
            chartDatasets.push(createDataset('Cancelled', datasets.cancelled, colors.cancelled));
        }
    } else if (canvasId === 'usersChart') {
         if (datasets.admin) {
            chartDatasets.push(createDataset('Admin', datasets.admin, colors.admin));
        }
        if (datasets.librarian) {
            chartDatasets.push(createDataset('Librarian', datasets.librarian, colors.librarian));
        }
         if (datasets.assistant) {
            chartDatasets.push(createDataset('Assistant', datasets.assistant, colors.assistant));
        }
        if (datasets.encoder) {
            chartDatasets.push(createDataset('Encoder', datasets.encoder, colors.encoder));
        }
        if (datasets.students) {
            chartDatasets.push(createDataset('Students', datasets.students, colors.students));
        }
        if (datasets.faculty) {
            chartDatasets.push(createDataset('Faculty', datasets.faculty, colors.faculty));
        }
         if (datasets.staff) {
            chartDatasets.push(createDataset('Staff', datasets.staff, colors.staff));
        }
        if (datasets.visitor) {
            chartDatasets.push(createDataset('Visitor', datasets.visitor, colors.visitor));
        }
    } else if (canvasId === 'booksChart') {
        // ----- Modified Books Chart Section -----
        if (datasets.added) {
            chartDatasets.push(createDataset('Added Books', datasets.added, colors.total));
        }
    } else if (canvasId === 'finesChart') {
        // Only show Paid and Unpaid lines
        if (datasets.paid) {
            chartDatasets.push(createDataset('Paid Fines', datasets.paid, colors.paid));
        }
        if (datasets.unpaid) {
            chartDatasets.push(createDataset('Unpaid Fines', datasets.unpaid, colors.unpaid));
        }
    } else {
        // For other charts, use the general approach
        for (const [key, data] of Object.entries(datasets)) {
            // Capitalize first letter of key for label
            const label = key.charAt(0).toUpperCase() + key.slice(1);
            
            // Get color, use predefined if available or default to blue
            const color = colors[key] || 'rgba(78, 115, 223, 1)';
            
            chartDatasets.push(createDataset(label, data, color));
        }
    }
    
    function createDataset(label, data, color) {
        return {
            label: label,
            data: data,
            backgroundColor: color.replace("1)", "0.1)"),
            borderColor: color,
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: color,
            pointBorderColor: color,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: color,
            pointHitRadius: 10,
            pointBorderWidth: 2,
            tension: 0.3,
            fill: false
        };
    }
    
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: chartDatasets
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    time: {
                        unit: 'day'
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        precision: 0,
                        min: 0
                    },
                    gridLines: {
                        color: "rgba(234, 236, 244, 1)",
                        zeroLineColor: "rgba(234, 236, 244, 1)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }]
            },
            legend: {
                display: true,
                position: 'top'
            },
            title: {
                display: title ? true : false,
                text: title || ''
            },
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                titleMarginBottom: 10,
                titleFontColor: '#6e707e',
                titleFontSize: 14,
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: true,
                intersect: false,
                mode: 'index',
                caretPadding: 10
            }
        }
    });
}

// Data from PHP
var chartLabels = <?= $labelsJSON ?>;
var borrowingsData = <?= $borrowingsJSON ?>;
var reservationsData = <?= $reservationsJSON ?>;
var usersData = <?= $usersJSON ?>;
var booksData = <?= $booksJSON ?>;
var finesData = <?= $finesJSON ?>;

// Create charts when document is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Create multi-line charts for each category
    createMultiLineChart('borrowingsChart', chartLabels, borrowingsData, 'Borrowings Activity - Last 7 Days');
    createMultiLineChart('reservationsChart', chartLabels, reservationsData, 'Reservations Activity - Last 7 Days');
    createMultiLineChart('usersChart', chartLabels, usersData, 'New Users Registration - Last 7 Days');
    createMultiLineChart('booksChart', chartLabels, booksData, 'Book Additions - Last 7 Days');
    createMultiLineChart('finesChart', chartLabels, finesData, 'Fines Activity - Last 7 Days');
    
    // Load all tables on initial page load
    loadBorrowingsTable();
    loadReservationsTable();
    loadUsersTable();
    loadBooksTable();
    loadFinesTable();
    
    // Toggle filter form visibility
    document.getElementById('toggleBorrowingsFilter').addEventListener('click', function() {
        const filterForm = document.getElementById('borrowingsFilterForm');
        filterForm.classList.toggle('d-none');
    });
    
    // Export borrowings table to Excel
    document.getElementById('exportBorrowingsTable').addEventListener('click', function() {
        const statusFilter = document.getElementById('bstatus').value;
        const dateStart = document.getElementById('bdate_start').value;
        const dateEnd = document.getElementById('bdate_end').value;
        const userFilter = document.getElementById('buser').value;
        const bookFilter = document.getElementById('bbook').value;
        
        // Build query string with current filters
        let params = new URLSearchParams();
        if (statusFilter) params.append('status', statusFilter);
        if (dateStart) params.append('date_start', dateStart);
        if (dateEnd) params.append('date_end', dateEnd);
        if (userFilter) params.append('user', userFilter);
        if (bookFilter) params.append('book', bookFilter);
        
        // Create export URL with filters
        const exportUrl = 'fetch_borrowings_export.php?' + params.toString();
        
        // Navigate to the export URL (will download the file)
        window.location.href = exportUrl;
    });

    // AJAX function to load table data
    function loadBorrowingsTable(page = 1) {
        const statusFilter = document.getElementById('bstatus').value;
        const dateStart = document.getElementById('bdate_start').value;
        const dateEnd = document.getElementById('bdate_end').value;
        const userFilter = document.getElementById('buser').value;
        const bookFilter = document.getElementById('bbook').value;
        
        // Show loading indicator
        document.getElementById('borrowingsTableContainer').innerHTML = '<div class="text-center my-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading...</p></div>';
        
        // Build query string
        let params = new URLSearchParams();
        params.append('bpage', page);
        if (statusFilter) params.append('bstatus', statusFilter);
        if (dateStart) params.append('bdate_start', dateStart);
        if (dateEnd) params.append('bdate_end', dateEnd);
        if (userFilter) params.append('buser', userFilter);
        if (bookFilter) params.append('bbook', bookFilter);
        params.append('ajax', 'true'); // Indicate this is an AJAX request
        
        // Update URL with filters (without reloading page)
        let newUrl = window.location.pathname + '?' + params.toString() + '#borrowings';
        window.history.pushState({path: newUrl}, '', newUrl);
        
        // Fetch table data
        fetch('fetch_borrowings_table.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                // Update table and pagination
                document.getElementById('borrowingsTableContainer').innerHTML = data.tableHtml;
                document.getElementById('paginationContainer').innerHTML = data.paginationHtml;
                
                // Update filter summary
                if (statusFilter || dateStart || dateEnd || userFilter || bookFilter) {
                    document.getElementById('filterSummary').classList.remove('d-none');
                    document.getElementById('totalResults').textContent = data.totalRecords;
                    document.getElementById('pluralSuffix').textContent = data.totalRecords != 1 ? 's' : '';
                } else {
                    document.getElementById('filterSummary').classList.add('d-none');
                }
                
                // Rebind pagination events
                rebindPaginationEvents();
            })
            .catch(error => {
                console.error('Error loading table:', error);
                document.getElementById('borrowingsTableContainer').innerHTML = '<div class="alert alert-danger">Error loading table data. Please try again.</div>';
            });
    }
    
    // Apply filters button click
    document.getElementById('applyFilters').addEventListener('click', function() {
        loadBorrowingsTable(1); // Reset to first page when applying new filters
    });
    
    // Reset filters button click
    document.getElementById('resetFilters').addEventListener('click', function() {
        // Clear all filter values
        document.getElementById('bstatus').value = '';
        document.getElementById('bdate_start').value = '';
        document.getElementById('bdate_end').value = '';
        document.getElementById('buser').value = '';
        document.getElementById('bbook').value = '';
        
        // Load table with reset filters
        loadBorrowingsTable(1);
    });
    
    // Function to rebind pagination events after table reload
    function rebindPaginationEvents() {
        document.querySelectorAll('.pagination-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                if (!this.parentNode.classList.contains('disabled')) {
                    loadBorrowingsTable(page);
                }
            });
        });
    }
    
    // Initial binding of pagination events
    rebindPaginationEvents();

    // Toggle reservation filter form visibility
    document.getElementById('toggleReservationsFilter').addEventListener('click', function() {
        const filterForm = document.getElementById('reservationsFilterForm');
        filterForm.classList.toggle('d-none');
    });
    
    // Export reservations table to Excel
    document.getElementById('exportReservationsTable').addEventListener('click', function() {
        const statusFilter = document.getElementById('rstatus').value;
        const dateStart = document.getElementById('rdate_start').value;
        const dateEnd = document.getElementById('rdate_end').value;
        const userFilter = document.getElementById('ruser').value;
        const bookFilter = document.getElementById('rbook').value;
        
        // Build query string with current filters
        let params = new URLSearchParams();
        if (statusFilter) params.append('status', statusFilter);
        if (dateStart) params.append('date_start', dateStart);
        if (dateEnd) params.append('date_end', dateEnd);
        if (userFilter) params.append('user', userFilter);
        if (bookFilter) params.append('book', bookFilter);
        
        // Create export URL with filters
        const exportUrl = 'fetch_reservations_export.php?' + params.toString();
        
        // Navigate to the export URL (will download the file)
        window.location.href = exportUrl;
    });

    // AJAX function to load reservations table data
    function loadReservationsTable(page = 1) {
        const statusFilter = document.getElementById('rstatus').value;
        const dateStart = document.getElementById('rdate_start').value;
        const dateEnd = document.getElementById('rdate_end').value;
        const userFilter = document.getElementById('ruser').value;
        const bookFilter = document.getElementById('rbook').value;
        
        // Show loading indicator
        document.getElementById('reservationsTableContainer').innerHTML = '<div class="text-center my-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading...</p></div>';
        
        // Build query string
        let params = new URLSearchParams();
        params.append('rpage', page);
        if (statusFilter) params.append('rstatus', statusFilter);
        if (dateStart) params.append('rdate_start', dateStart);
        if (dateEnd) params.append('rdate_end', dateEnd);
        if (userFilter) params.append('ruser', userFilter);
        if (bookFilter) params.append('rbook', bookFilter);
        params.append('ajax', 'true'); // Indicate this is an AJAX request
        
        // Update URL with filters (without reloading page)
        let newUrl = window.location.pathname + '?' + params.toString() + '#reservations';
        window.history.pushState({path: newUrl}, '', newUrl);
        
        // Fetch table data
        fetch('fetch_reservations_table.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                // Update table and pagination
                document.getElementById('reservationsTableContainer').innerHTML = data.tableHtml;
                document.getElementById('rpaginationContainer').innerHTML = data.paginationHtml;
                
                // Update filter summary
                if (statusFilter || dateStart || dateEnd || userFilter || bookFilter) {
                    document.getElementById('rfilterSummary').classList.remove('d-none');
                    document.getElementById('rtotalResults').textContent = data.totalRecords;
                    document.getElementById('rpluralSuffix').textContent = data.totalRecords != 1 ? 's' : '';
                } else {
                    document.getElementById('rfilterSummary').classList.add('d-none');
                }
                
                // Rebind pagination events
                rebindReservationsPaginationEvents();
            })
            .catch(error => {
                console.error('Error loading table:', error);
                document.getElementById('reservationsTableContainer').innerHTML = '<div class="alert alert-danger">Error loading table data. Please try again.</div>';
            });
    }
    
    // Apply filters button click
    document.getElementById('applyReservationsFilters').addEventListener('click', function() {
        loadReservationsTable(1); // Reset to first page when applying new filters
    });
    
    // Reset filters button click
    document.getElementById('resetReservationsFilters').addEventListener('click', function() {
        // Clear all filter values
        document.getElementById('rstatus').value = '';
        document.getElementById('rdate_start').value = '';
        document.getElementById('rdate_end').value = '';
        document.getElementById('ruser').value = '';
        document.getElementById('rbook').value = '';
        
        // Load table with reset filters
        loadReservationsTable(1);
    });
    
    // Function to rebind pagination events after table reload
    function rebindReservationsPaginationEvents() {
        document.querySelectorAll('.rpagination-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                if (!this.parentNode.classList.contains('disabled')) {
                    loadReservationsTable(page);
                }
            });
        });
    }
    
    // Initial binding of pagination events
    rebindReservationsPaginationEvents();

    // Toggle users filter form visibility
    document.getElementById('toggleUsersFilter').addEventListener('click', function() {
        const filterForm = document.getElementById('usersFilterForm');
        filterForm.classList.toggle('d-none');
    });
    
    // Export users table to Excel
    document.getElementById('exportUsersTable').addEventListener('click', function() {
        const roleFilter = document.getElementById('urole').value;
        const dateStart = document.getElementById('udate_start').value;
        const dateEnd = document.getElementById('udate_end').value;
        const searchFilter = document.getElementById('usearch').value;
        const statusFilter = document.getElementById('ustatus').value;
        
        // Build query string with current filters
        let params = new URLSearchParams();
        if (roleFilter) params.append('role', roleFilter);
        if (dateStart) params.append('date_start', dateStart);
        if (dateEnd) params.append('date_end', dateEnd);
        if (searchFilter) params.append('search', searchFilter);
        if (statusFilter !== '') params.append('status', statusFilter);
        
        // Create export URL with filters
        const exportUrl = 'fetch_users_export.php?' + params.toString();
        
        // Navigate to the export URL (will download the file)
        window.location.href = exportUrl;
    });

    // AJAX function to load users table data
    function loadUsersTable(page = 1) {
        const roleFilter = document.getElementById('urole').value;
        const dateStart = document.getElementById('udate_start').value;
        const dateEnd = document.getElementById('udate_end').value;
        const searchFilter = document.getElementById('usearch').value;
        const statusFilter = document.getElementById('ustatus').value;
        
        // Show loading indicator
        document.getElementById('usersTableContainer').innerHTML = '<div class="text-center my-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading...</p></div>';
        
        // Build query string
        let params = new URLSearchParams();
        params.append('upage', page);
        if (roleFilter) params.append('urole', roleFilter);
        if (dateStart) params.append('udate_start', dateStart);
        if (dateEnd) params.append('udate_end', dateEnd);
        if (searchFilter) params.append('usearch', searchFilter);
        if (statusFilter) params.append('ustatus', statusFilter);
        params.append('ajax', 'true'); // Indicate this is an AJAX request
        
        // Update URL with filters (without reloading page)
        let newUrl = window.location.pathname + '?' + params.toString() + '#users';
        window.history.pushState({path: newUrl}, '', newUrl);
        
        // Fetch table data
        fetch('fetch_users_table.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                // Update table and pagination
                document.getElementById('usersTableContainer').innerHTML = data.tableHtml;
                document.getElementById('upaginationContainer').innerHTML = data.paginationHtml;
                
                // Update filter summary
                if (roleFilter || dateStart || dateEnd || searchFilter || statusFilter) {
                    document.getElementById('ufilterSummary').classList.remove('d-none');
                    document.getElementById('utotalResults').textContent = data.totalRecords;
                    document.getElementById('upluralSuffix').textContent = data.totalRecords != 1 ? 's' : '';
                } else {
                    document.getElementById('ufilterSummary').classList.add('d-none');
                }
                
                // Rebind pagination events
                rebindUsersPaginationEvents();
            })
            .catch(error => {
                console.error('Error loading table:', error);
                document.getElementById('usersTableContainer').innerHTML = '<div class="alert alert-danger">Error loading table data. Please try again.</div>';
            });
    }
    
    // Apply filters button click
    document.getElementById('applyUsersFilters').addEventListener('click', function() {
        loadUsersTable(1); // Reset to first page when applying new filters
    });
    
    // Reset filters button click
    document.getElementById('resetUsersFilters').addEventListener('click', function() {
        // Clear all filter values
        document.getElementById('urole').value = '';
        document.getElementById('udate_start').value = '';
        document.getElementById('udate_end').value = '';
        document.getElementById('usearch').value = '';
        document.getElementById('ustatus').value = '';
        
        // Load table with reset filters
        loadUsersTable(1);
    });
    
    // Function to rebind pagination events after table reload
    function rebindUsersPaginationEvents() {
        document.querySelectorAll('.upagination-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                if (!this.parentNode.classList.contains('disabled')) {
                    loadUsersTable(page);
                }
            });
        });
    }
    
    // Initial binding of pagination events
    rebindUsersPaginationEvents();

    // Toggle books filter form visibility
    document.getElementById('toggleBooksFilter').addEventListener('click', function() {
        const filterForm = document.getElementById('booksFilterForm');
        filterForm.classList.toggle('d-none');
    });
    
    // Export books table to Excel
    document.getElementById('exportBooksTable').addEventListener('click', function() {
        const statusFilter = document.getElementById('bookstatus').value;
        const dateStart = document.getElementById('bookdate_start').value;
        const dateEnd = document.getElementById('bookdate_end').value;
        const titleFilter = document.getElementById('booktitle').value;
        const locationFilter = document.getElementById('booklocation').value;
        
        // Build query string with current filters
        let params = new URLSearchParams();
        if (statusFilter) params.append('status', statusFilter);
        if (dateStart) params.append('date_start', dateStart);
        if (dateEnd) params.append('date_end', dateEnd);
        if (titleFilter) params.append('title', titleFilter);
        if (locationFilter) params.append('location', locationFilter);
        
        // Create export URL with filters
        const exportUrl = 'fetch_books_export.php?' + params.toString();
        
        // Navigate to the export URL (will download the file)
        window.location.href = exportUrl;
    });

    // AJAX function to load books table data
    function loadBooksTable(page = 1) {
        const statusFilter = document.getElementById('bookstatus').value;
        const dateStart = document.getElementById('bookdate_start').value;
        const dateEnd = document.getElementById('bookdate_end').value;
        const titleFilter = document.getElementById('booktitle').value;
        const locationFilter = document.getElementById('booklocation').value;
        
        // Show loading indicator
        document.getElementById('booksTableContainer').innerHTML = '<div class="text-center my-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading...</p></div>';
        
        // Build query string
        let params = new URLSearchParams();
        params.append('page', page);
        if (statusFilter) params.append('status', statusFilter);
        if (dateStart) params.append('date_start', dateStart);
        if (dateEnd) params.append('date_end', dateEnd);
        if (titleFilter) params.append('title', titleFilter);
        if (locationFilter) params.append('location', locationFilter);
        params.append('ajax', 'true'); // Indicate this is an AJAX request
        
        // Update URL with filters (without reloading page)
        let newUrl = window.location.pathname + '?' + params.toString() + '#books';
        window.history.pushState({path: newUrl}, '', newUrl);
        
        // Fetch table data
        fetch('fetch_books_table.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                // Update table and pagination
                document.getElementById('booksTableContainer').innerHTML = data.tableHtml;
                document.getElementById('bookpaginationContainer').innerHTML = data.paginationHtml;
                
                // Update filter summary
                if (statusFilter || dateStart || dateEnd || titleFilter || locationFilter) {
                    document.getElementById('bookfilterSummary').classList.remove('d-none');
                    document.getElementById('booktotalResults').textContent = data.totalRecords;
                    document.getElementById('bookpluralSuffix').textContent = data.totalRecords != 1 ? 's' : '';
                } else {
                    document.getElementById('bookfilterSummary').classList.add('d-none');
                }
                
                // Rebind pagination events
                rebindBooksPaginationEvents();
            })
            .catch(error => {
                console.error('Error loading table:', error);
                document.getElementById('booksTableContainer').innerHTML = '<div class="alert alert-danger">Error loading table data. Please try again.</div>';
            });
    }
    
    // Apply filters button click
    document.getElementById('applyBooksFilters').addEventListener('click', function() {
        loadBooksTable(1); // Reset to first page when applying new filters
    });
    
    // Reset filters button click
    document.getElementById('resetBooksFilters').addEventListener('click', function() {
        // Clear all filter values
        document.getElementById('bookstatus').value = '';
        document.getElementById('bookdate_start').value = '';
        document.getElementById('bookdate_end').value = '';
        document.getElementById('booktitle').value = '';
        document.getElementById('booklocation').value = '';
        
        // Load table with reset filters
        loadBooksTable(1);
    });
    
    // Function to rebind pagination events after table reload
    function rebindBooksPaginationEvents() {
        document.querySelectorAll('.bookpagination-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                if (!this.parentNode.classList.contains('disabled')) {
                    loadBooksTable(page);
                }
            });
        });
    }

    // Toggle fines filter form visibility
    document.getElementById('toggleFinesFilter').addEventListener('click', function() {
        const filterForm = document.getElementById('finesFilterForm');
        filterForm.classList.toggle('d-none');
    });
    
    // Export fines table to Excel
    document.getElementById('exportFinesTable').addEventListener('click', function() {
        const statusFilter = document.getElementById('fstatus').value;
        const dateStart = document.getElementById('fdate_start').value;
        const dateEnd = document.getElementById('fdate_end').value;
        const userFilter = document.getElementById('fuser').value;
        const typeFilter = document.getElementById('ftype').value;
        
        // Build query string with current filters
        let params = new URLSearchParams();
        if (statusFilter) params.append('status', statusFilter);
        if (dateStart) params.append('date_start', dateStart);
        if (dateEnd) params.append('date_end', dateEnd);
        if (userFilter) params.append('user', userFilter);
        if (typeFilter) params.append('type', typeFilter);
        
        // Create export URL with filters
        const exportUrl = 'fetch_fines_export.php?' + params.toString();
        
        // Navigate to the export URL (will download the file)
        window.location.href = exportUrl;
    });

    // AJAX function to load fines table data
    function loadFinesTable(page = 1) {
        const statusFilter = document.getElementById('fstatus').value;
        const dateStart = document.getElementById('fdate_start').value;
        const dateEnd = document.getElementById('fdate_end').value;
        const userFilter = document.getElementById('fuser').value;
        const typeFilter = document.getElementById('ftype').value;
        
        // Show loading indicator
        document.getElementById('finesTableContainer').innerHTML = '<div class="text-center my-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading...</p></div>';
        
        // Build query string
        let params = new URLSearchParams();
        params.append('fpage', page);
        if (statusFilter) params.append('fstatus', statusFilter);
        if (dateStart) params.append('fdate_start', dateStart);
        if (dateEnd) params.append('fdate_end', dateEnd);
        if (userFilter) params.append('fuser', userFilter);
        if (typeFilter) params.append('ftype', typeFilter);
        params.append('ajax', 'true');
        
        // Update URL with filters (without reloading page)
        let newUrl = window.location.pathname + '?' + params.toString() + '#fines';
        window.history.pushState({path: newUrl}, '', newUrl);
        
        // Fetch table data
        fetch('fetch_fines_table.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                // Update table and pagination
                document.getElementById('finesTableContainer').innerHTML = data.tableHtml;
                document.getElementById('fpaginationContainer').innerHTML = data.paginationHtml;
                
                // Update filter summary
                if (statusFilter || dateStart || dateEnd || userFilter || typeFilter) {
                    document.getElementById('ffilterSummary').classList.remove('d-none');
                    document.getElementById('ftotalResults').textContent = data.totalRecords;
                    document.getElementById('fpluralSuffix').textContent = data.totalRecords != 1 ? 's' : '';
                } else {
                    document.getElementById('ffilterSummary').classList.add('d-none');
                }
                
                // Rebind pagination events
                rebindFinesPaginationEvents();
            })
            .catch(error => {
                console.error('Error loading table:', error);
                document.getElementById('finesTableContainer').innerHTML = '<div class="alert alert-danger">Error loading table data. Please try again.</div>';
            });
    }
    
    // Apply filters button click
    document.getElementById('applyFinesFilters').addEventListener('click', function() {
        loadFinesTable(1); // Reset to first page when applying new filters
    });
    
    // Reset filters button click
    document.getElementById('resetFinesFilters').addEventListener('click', function() {
        // Clear all filter values
        document.getElementById('fstatus').value = '';
        document.getElementById('fdate_start').value = '';
        document.getElementById('fdate_end').value = '';
        document.getElementById('fuser').value = '';
        document.getElementById('ftype').value = '';
        
        // Load table with reset filters
        loadFinesTable(1);
    });
    
    // Function to rebind pagination events after table reload
    function rebindFinesPaginationEvents() {
        document.querySelectorAll('.fpagination-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                if (!this.parentNode.classList.contains('disabled')) {
                    loadFinesTable(page);
                }
            });
        });
    }
});

// Handle tab switching to refresh charts
$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    // Resize charts when tab is shown
    window.dispatchEvent(new Event('resize'));
});

// Function to export table to Excel
function exportTableToExcel(tableID, filename = '') {
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    // Specify file name
    filename = filename ? filename + '.xls' : 'excel_data.xls';
    
    // Create download link element
    downloadLink = document.createElement("a");
    
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob) {
        var blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob(blob, filename);
    } else {
        // Create a link to the file
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
    
        // Setting the file name
        downloadLink.download = filename;
        
        // Triggering the function
        downloadLink.click();
    }
}
</script>

<?php
include 'inc/footer.php';
?>