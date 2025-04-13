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
    header('Location: dashboard.php'); // Redirect to a page appropriate for their role or an error page
    exit();
}

// Fetch reservations data from the database
include('../db.php');

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$userFilter = isset($_GET['user']) ? $_GET['user'] : '';
$bookFilter = isset($_GET['book']) ? $_GET['book'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "WHERE r.recieved_date IS NULL AND r.cancel_date IS NULL";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= " AND r.status = '$statusFilter'";
    $filterParams[] = "status=$statusFilter";
}

if ($dateStart) {
    $whereClause .= " AND DATE(r.reserve_date) >= '$dateStart'";
    $filterParams[] = "date_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= " AND DATE(r.reserve_date) <= '$dateEnd'";
    $filterParams[] = "date_end=$dateEnd";
}

if ($userFilter) {
    $whereClause .= " AND (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')";
    $filterParams[] = "user=" . urlencode($userFilter);
}

if ($bookFilter) {
    $whereClause .= " AND (b.title LIKE '%$bookFilter%' OR b.accession LIKE '%$bookFilter%')";
    $filterParams[] = "book=" . urlencode($bookFilter);
}

// Updated query to include ISBN, Series, Volume, Part, and Edition
$query = "SELECT 
    r.id AS reservation_id,
    r.user_id,
    r.book_id,
    u.school_id,
    u.usertype,
    CONCAT(u.firstname, ' ', u.lastname) AS user_name,
    b.title AS book_title,
    b.accession AS accession,
    b.ISBN,
    b.series,
    b.volume,
    b.part,
    b.edition,
    b.shelf_location,
    r.reserve_date,
    r.ready_date,
    CONCAT(a1.firstname, ' ', a1.lastname) AS ready_by_name,
    r.issue_date,
    CONCAT(a2.firstname, ' ', a2.lastname) AS issued_by_name,
    r.cancel_date,
    CONCAT(COALESCE(a3.firstname, u2.firstname), ' ', COALESCE(a3.lastname, u2.lastname)) AS cancelled_by_name,
    r.cancelled_by_role,
    r.status
FROM reservations r
JOIN users u ON r.user_id = u.id
JOIN books b ON r.book_id = b.id
LEFT JOIN admins a1 ON r.ready_by = a1.id
LEFT JOIN admins a2 ON r.issued_by = a2.id
LEFT JOIN admins a3 ON (r.cancelled_by = a3.id AND r.cancelled_by_role = 'Admin')
LEFT JOIN users u2 ON (r.cancelled_by = u2.id AND r.cancelled_by_role = 'User')
$whereClause
ORDER BY r.reserve_date DESC";

$result = $conn->query($query);

// Count total number of records for the filter summary
$countQuery = "SELECT COUNT(*) as total FROM reservations r 
              JOIN users u ON r.user_id = u.id
              JOIN books b ON r.book_id = b.id
              LEFT JOIN admins a1 ON r.ready_by = a1.id
              LEFT JOIN admins a2 ON r.issued_by = a2.id
              LEFT JOIN admins a3 ON (r.cancelled_by = a3.id AND r.cancelled_by_role = 'Admin')
              LEFT JOIN users u2 ON (r.cancelled_by = u2.id AND r.cancelled_by_role = 'User')
              $whereClause";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];

// Statistics queries
// Current status statistics
$pendingQuery = "SELECT COUNT(*) AS count FROM reservations WHERE status = 'Pending'";
$pendingResult = $conn->query($pendingQuery);
$pendingCount = $pendingResult->fetch_assoc()['count'];

$readyQuery = "SELECT COUNT(*) AS count FROM reservations WHERE status = 'Ready'";
$readyResult = $conn->query($readyQuery);
$readyCount = $readyResult->fetch_assoc()['count'];

// Today's statistics
$todayReceivedQuery = "SELECT COUNT(*) AS count FROM reservations WHERE status = 'Received' AND DATE(recieved_date) = CURDATE()";
$todayReceivedResult = $conn->query($todayReceivedQuery);
$todayReceivedCount = $todayReceivedResult->fetch_assoc()['count'];

$todayCancelledQuery = "SELECT COUNT(*) AS count FROM reservations WHERE status = 'Cancelled' AND DATE(cancel_date) = CURDATE()";
$todayCancelledResult = $conn->query($todayCancelledQuery);
$todayCancelledCount = $todayCancelledResult->fetch_assoc()['count'];

$todayReservationsQuery = "SELECT COUNT(*) AS count FROM reservations WHERE DATE(reserve_date) = CURDATE()";
$todayReservationsResult = $conn->query($todayReservationsQuery);
$todayReservationsCount = $todayReservationsResult->fetch_assoc()['count'];

// Overall statistics
$totalReservationsQuery = "SELECT COUNT(*) AS count FROM reservations";
$totalReservationsResult = $conn->query($totalReservationsQuery);
$totalReservationsCount = $totalReservationsResult->fetch_assoc()['count'];

$totalReceivedQuery = "SELECT COUNT(*) AS count FROM reservations WHERE status = 'Received'";
$totalReceivedResult = $conn->query($totalReceivedQuery);
$totalReceivedCount = $totalReceivedResult->fetch_assoc()['count'];

$totalCancelledQuery = "SELECT COUNT(*) AS count FROM reservations WHERE status = 'Cancelled'";
$totalCancelledResult = $conn->query($totalCancelledQuery);
$totalCancelledCount = $totalCancelledResult->fetch_assoc()['count'];

// Define styles as PHP variables to use inline
$cardStyles = "transition: all 0.3s; border-left: 4px solid;";
$cardHoverStyles = "transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);";
$iconStyles = "font-size: 2rem; opacity: 0.6;";
$titleStyles = "font-size: 0.9rem; font-weight: bold; text-transform: uppercase;";
$numberStyles = "font-size: 1.5rem; font-weight: bold;";

$primaryCardBorder = "#4e73df";
$successCardBorder = "#1cc88a";
$infoCardBorder = "#36b9cc";
$dangerCardBorder = "#e74a3b";
$warningCardBorder = "#f6c23e";

$tableResponsiveStyles = "overflow-x: auto;";
$tableCellStyles = "white-space: nowrap;";
$tableCenterStyles = "text-align: center;";
$checkboxColumnStyles = "text-align: center; width: 40px; padding-left: 15px;";
$checkboxStyles = "margin: 0; vertical-align: middle;";
?>

<!-- Main Content -->
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Book Reservations</h1>
    </div>

    <!-- Reservations Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Filter Reservations</h6>
            <button class="btn btn-sm btn-primary" id="toggleFilter">
                <?php echo empty($filterParams) ? '<i class="fas fa-filter"></i> Show Filter' : '<i class="fas fa-times"></i> Hide Filter'; ?>
            </button>
        </div>
        <div class="card-body <?= empty($filterParams) ? 'd-none' : '' ?>" id="filterForm">
            <form method="get" action="" class="mb-0" id="reservationsFilterForm">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All</option>
                                <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Ready" <?= $statusFilter === 'Ready' ? 'selected' : '' ?>>Ready</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_start">From Date</label>
                            <input type="date" class="form-control" id="date_start" name="date_start" value="<?= $dateStart ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_end">To Date</label>
                            <input type="date" class="form-control" id="date_end" name="date_end" value="<?= $dateEnd ?>">
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="user">User (Name or ID)</label>
                            <input type="text" class="form-control" id="user" name="user" value="<?= htmlspecialchars($userFilter) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="book">Book (Title or Accession)</label>
                            <input type="text" class="form-control" id="book" name="book" value="<?= htmlspecialchars($bookFilter) ?>">
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 text-end">
                        <button type="button" id="resetFilters" class="btn btn-secondary">Reset</button>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Book Reservations List</h6>
            <div class="d-flex">
                <button id="bulkReadyBtn" class="btn btn-info btn-sm mr-2" disabled>
                    <i class="fas fa-check"></i> Mark as Ready (<span id="selectedCountReady">0</span>)
                </button>
                <button id="bulkReceiveBtn" class="btn btn-success btn-sm mr-2" disabled>
                    <i class="fas fa-book"></i> Issue Books (<span id="selectedCountReceive">0</span>)
                </button>
                <button id="bulkCancelBtn" class="btn btn-danger btn-sm" disabled>
                    <i class="fas fa-times"></i> Cancel (<span id="selectedCount">0</span>)
                </button>
            </div>
        </div>
        <!-- Add alert section -->
        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mx-4 mt-3" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mx-4 mt-3" role="alert">
            <?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        <div class="card-body">
            <!-- Filter summary -->
            <?php if (!empty($filterParams)): ?>
            <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
                <div>
                    <strong><i class="fas fa-filter"></i> Active Filters:</strong>
                    <?php
                    $filterStrings = [];
                    if ($statusFilter) $filterStrings[] = "Status: $statusFilter";
                    if ($dateStart) $filterStrings[] = "From: " . date('M j, Y', strtotime($dateStart));
                    if ($dateEnd) $filterStrings[] = "To: " . date('M j, Y', strtotime($dateEnd));
                    if ($userFilter) $filterStrings[] = "User: " . htmlspecialchars($userFilter);
                    if ($bookFilter) $filterStrings[] = "Book: " . htmlspecialchars($bookFilter);
                    echo implode(' | ', $filterStrings);
                    ?>
                    | Showing <?= $totalRecords ?> result<?= $totalRecords !== 1 ? 's' : '' ?>
                </div>
                <a href="book_reservations.php" class="btn btn-sm btn-outline-secondary">
                    Clear Filters
                </a>
            </div>
            <?php endif; ?>
            
            <style>
                tr.selected {
                    background-color: rgba(0, 123, 255, 0.1);
                }
                #checkboxHeader {
                    cursor: pointer;
                }
                .book-details-title {
                    font-weight: bold;
                    color: #4e73df;
                }
                .book-details-info {
                    color: #666;
                    font-size: 0.9em;
                }
                .table-responsive table td.book-details {
                    white-space: normal;
                }
            </style>
            
            <div class="table-responsive" style="<?php echo $tableResponsiveStyles; ?>">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" id="checkboxHeader" style="width: 5%;">
                                <input type="checkbox" id="selectAll" title="Select/Unselect All">
                            </th>
                            <th style="width: 15%;">User</th>
                            <th style="width: 40%;">Book</th>
                            <th style="width: 15%;">Reserve Date</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 15%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): 
                            while ($row = $result->fetch_assoc()): 
                                // Format additional book details
                                $detailsArray = [];
                                if (!empty($row['ISBN'])) $detailsArray[] = 'ISBN: ' . htmlspecialchars($row['ISBN']);
                                if (!empty($row['series'])) $detailsArray[] = 'Series: ' . htmlspecialchars($row['series']);
                                if (!empty($row['volume'])) $detailsArray[] = 'Vol: ' . htmlspecialchars($row['volume']);
                                if (!empty($row['part'])) $detailsArray[] = 'Part: ' . htmlspecialchars($row['part']);
                                if (!empty($row['edition'])) $detailsArray[] = 'Ed: ' . htmlspecialchars($row['edition']);
                                
                                $additionalDetails = !empty($detailsArray) ? implode(' | ', $detailsArray) : '';
                        ?>
                            <tr data-reservation-id="<?= $row['reservation_id'] ?>" data-status="<?= htmlspecialchars($row['status']) ?>">
                                <td class="text-center">
                                    <input type="checkbox" class="reservation-checkbox" 
                                           data-id="<?= $row['reservation_id'] ?>"
                                           data-user-id="<?= $row['user_id'] ?>"
                                           data-book-id="<?= $row['book_id'] ?>"
                                           data-status="<?= htmlspecialchars($row['status']) ?>">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($row['user_name']) ?></strong><br>
                                    <small>ID: <?= htmlspecialchars($row['school_id']) ?></small><br>
                                    <small>User Type: <?= htmlspecialchars($row['usertype']) ?></small>
                                </td>
                                <td class="book-details">
                                    <div class="book-details-title">
                                        <?= htmlspecialchars($row['book_title']) ?>
                                    </div>
                                    <?php if (!empty($additionalDetails)): ?>
                                    <div class="book-details-info">
                                        <?= $additionalDetails ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="book-details-info">
                                        <strong>Accession:</strong> <?= htmlspecialchars($row['accession']) ?>
                                        <strong>Location:</strong> <?= htmlspecialchars($row['shelf_location']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?= date('M j, Y', strtotime($row['reserve_date'])) ?><br>
                                    <small><?= date('h:i A', strtotime($row['reserve_date'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Pending'): ?>
                                        <span class="badge badge-warning p-2 w-100">Pending</span>
                                    <?php elseif ($row['status'] === 'Ready'): ?>
                                        <span class="badge badge-info p-2 w-100" 
                                              data-toggle="tooltip" 
                                              title="Made ready by: <?= htmlspecialchars($row['ready_by_name']) ?> on <?= date('Y-m-d h:i A', strtotime($row['ready_date'])) ?>">
                                            Ready for Pickup
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <button class="btn btn-sm btn-info mark-ready-btn" 
                                                    data-id="<?= $row['reservation_id'] ?>"
                                                    title="Mark as Ready">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php elseif ($row['status'] === 'Ready'): ?>
                                            <button class="btn btn-sm btn-success issue-book-btn"
                                                    data-id="<?= $row['reservation_id'] ?>"
                                                    data-user-id="<?= $row['user_id'] ?>"
                                                    data-book-id="<?= $row['book_id'] ?>"
                                                    title="Issue Book">
                                                <i class="fas fa-book"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-danger cancel-btn"
                                                data-id="<?= $row['reservation_id'] ?>"
                                                data-book-id="<?= $row['book_id'] ?>"
                                                title="Cancel Reservation">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php 
endwhile;
endif;
                            $conn->close();
?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Statistics Dashboard -->
    <h4 class="mb-3 text-gray-800">Statistics Overview</h4>
    <div class="row mb-4">
        <!-- Pending Reservations -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow h-100 py-2" style="<?php echo $cardStyles; ?> border-left-color: <?php echo $warningCardBorder; ?>;" 
                 onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 0.5rem 1rem rgba(0, 0, 0, 0.15)';" 
                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1" style="<?php echo $titleStyles; ?>">
                                Reserved Books
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" style="<?php echo $numberStyles; ?>">
                                <?php echo $pendingCount; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300" style="<?php echo $iconStyles; ?>"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="book_reservations.php?status=Reserved" class="text-warning small">
                        View Details <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Ready Reservations -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow h-100 py-2" style="<?php echo $cardStyles; ?> border-left-color: <?php echo $infoCardBorder; ?>;"
                 onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 0.5rem 1rem rgba(0, 0, 0, 0.15)';" 
                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1" style="<?php echo $titleStyles; ?>">
                                Ready for Pickup
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" style="<?php echo $numberStyles; ?>">
                                <?php echo $readyCount; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check fa-2x text-gray-300" style="<?php echo $iconStyles; ?>"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="book_reservations.php?status=Ready" class="text-info small">
                        View Details <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Overall Received -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow h-100 py-2" style="<?php echo $cardStyles; ?> border-left-color: <?php echo $successCardBorder; ?>;"
                 onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 0.5rem 1rem rgba(0, 0, 0, 0.15)';" 
                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1" style="<?php echo $titleStyles; ?>">
                                Total Received Books
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" style="<?php echo $numberStyles; ?>">
                                <?php echo $totalReceivedCount; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300" style="<?php echo $iconStyles; ?>"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="reservation_history.php?status=Received" class="text-success small">
                        View Details <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Overall Cancelled -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow h-100 py-2" style="<?php echo $cardStyles; ?> border-left-color: <?php echo $dangerCardBorder; ?>;"
                 onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 0.5rem 1rem rgba(0, 0, 0, 0.15)';" 
                 onmouseout="this.style.transform=''; this.style.boxShadow='';">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1" style="<?php echo $titleStyles; ?>">
                                Cancelled Reservations
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" style="<?php echo $numberStyles; ?>">
                                <?php echo $totalCancelledCount; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ban fa-2x text-gray-300" style="<?php echo $iconStyles; ?>"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="reservation_history.php?status=Cancelled" class="text-danger small">
                        View Details <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- End Statistics Dashboard -->
</div>
<!-- End of Main Content -->

<!-- Footer -->
<?php include('inc/footer.php'); ?>
<!-- End of Footer -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Context Menu -->
<div class="context-menu" style="display: none; position: absolute; z-index: 1000; background: white; border: 1px solid #ddd; border-radius: 4px; box-shadow: 2px 2px 5px rgba(0,0,0,0.1);">
    <ul class="list-group">
        <li class="list-group-item" data-action="ready" style="cursor: pointer; padding: 8px 20px;">Mark as Ready</li>
        <li class="list-group-item" data-action="received" style="cursor: pointer; padding: 8px 20px;">Issue Book</li>
        <li class="list-group-item" data-action="cancel" style="cursor: pointer; padding: 8px 20px;">Cancel Reservation</li>
    </ul>
</div>

<!-- Add these before the closing </head> tag -->
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Toggle filter form visibility
        $('#toggleFilter').on('click', function() {
            $('#filterForm').toggleClass('d-none');
            
            // Update button text based on visibility
            const isVisible = !$('#filterForm').hasClass('d-none');
            $(this).html(isVisible ? 
                '<i class="fas fa-times"></i> Hide Filter' : 
                '<i class="fas fa-filter"></i> Show Filter');
        });

        // Reset filters
        $('#resetFilters').on('click', function(e) {
            e.preventDefault();
            
            // Clear all filter values
            $('#status').val('');
            $('#date_start').val('');
            $('#date_end').val('');
            $('#user').val('');
            $('#book').val('');
            
            // Submit the form
            $('#reservationsFilterForm').submit();
        });
        
        // Initialize DataTable with consistent settings
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
                "scrollX": true,
                "scrollCollapse": true,
                "ordering": true,
                "order": [[3, 'desc']], // Default sort by reserve date (newest first)
                "columnDefs": [
                    { "orderable": false, "targets": 0, "searchable": false }, // Disable sorting for checkbox column
                    { "orderable": false, "targets": 5, "searchable": false }  // Disable sorting for actions column
                ],
                "language": {
                    "searchPlaceholder": "Search...",
                    "emptyTable": "No reservations found",
                    "zeroRecords": "No matching reservations found"
                },
                "initComplete": function() {
                    $('#dataTable_filter input').addClass('form-control form-control-sm');
                    $('[data-toggle="tooltip"]').tooltip();
                    updateRowSelectionState();
                }
            });
            
            return table;
        }

        // Initialize the DataTable
        const table = initializeDataTable();

        // Add window resize handler
        $(window).on('resize', function() {
            table.columns.adjust().draw();
        });

        // Style for all action buttons
        const contextMenu = $('.context-menu');
        let $selectedRow = null;

        // Handle Mark as Ready button clicks
        $(document).on('click', '.mark-ready-btn', function(e) {
            e.preventDefault();
            const reservationId = $(this).data('id');
            
            Swal.fire({
                title: 'Mark as Ready?',
                text: 'Do you want to mark this reservation as ready for pickup?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Mark as Ready',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `reservation_ready.php?id=${reservationId}`;
                }
            });
        });

        // Handle Issue Book button clicks
        $(document).on('click', '.issue-book-btn', function(e) {
            e.preventDefault();
            const reservationId = $(this).data('id');
            
            Swal.fire({
                title: 'Issue Book?',
                text: 'Do you want to issue this book to the user?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Issue Book',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `reservation_receive.php?id=${reservationId}`;
                }
            });
        });

        // Handle Cancel Reservation button clicks
        $(document).on('click', '.cancel-btn', function(e) {
            e.preventDefault();
            const reservationId = $(this).data('id');
            
            Swal.fire({
                title: 'Cancel Reservation?',
                text: 'Do you want to cancel this reservation?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Cancel It',
                cancelButtonText: 'No, Keep It',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `reservation_cancel.php?id=${reservationId}&admin=1`;
                }
            });
        });

        // Add inline styles for selection visual feedback
        $('<style>')
            .text(`
                .selected { background-color: rgba(0, 123, 255, 0.1); }
                #checkboxHeader { cursor: pointer; }
                .reservation-checkbox { cursor: pointer; }
            `)
            .appendTo('head');

        // Handle select all checkbox
        $('#selectAll').change(function() {
            const isChecked = $(this).prop('checked');
            $('.reservation-checkbox').each(function() {
                $(this).prop('checked', isChecked);
            });
            updateRowSelectionState();
            updateBulkButtons();
        });

        // Handle individual checkboxes
        $(document).on('change', '.reservation-checkbox', function() {
            updateRowSelectionState();
            updateBulkButtons();
        });

        // Update bulk button states based on selections
        function updateBulkButtons() {
            const total = $('.reservation-checkbox:checked').length;
            const totalPending = $('.reservation-checkbox:checked[data-status="Pending"]').length;
            const totalReady = $('.reservation-checkbox:checked[data-status="Ready"]').length;
            
            $('#selectedCount').text(total);
            $('#selectedCountReady').text(totalPending);
            $('#selectedCountReceive').text(totalReady);
            
            $('#bulkCancelBtn').prop('disabled', total === 0);
            $('#bulkReadyBtn').prop('disabled', totalPending === 0);
            $('#bulkReceiveBtn').prop('disabled', totalReady === 0);
            
            // Log for debugging
            console.log(`Total selected: ${total}, Pending: ${totalPending}, Ready: ${totalReady}`);
        }

        // Function to update the row selection visual state
        function updateRowSelectionState() {
            $('#dataTable tbody tr').removeClass('selected');
            
            $('.reservation-checkbox:checked').each(function() {
                $(this).closest('tr').addClass('selected');
            });
            
            // Update select all checkbox state
            const totalCheckboxes = $('.reservation-checkbox').length;
            const totalChecked = $('.reservation-checkbox:checked').length;
            
            $('#selectAll').prop({
                'checked': totalChecked > 0 && totalChecked === totalCheckboxes,
                'indeterminate': totalChecked > 0 && totalChecked < totalCheckboxes
            });
        }

        // Handle row clicks to toggle checkbox
        $('#dataTable tbody').on('click', 'tr', function(e) {
            // Ignore if clicked on a button or checkbox
            if ($(e.target).is('button, input, a, .btn, i') || $(e.target).parents('button, a, .btn').length) {
                return;
            }
            
            const checkbox = $(this).find('.reservation-checkbox');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        });
        
        // Handle checkbox header click
        $('#checkboxHeader').on('click', function(e) {
            if (e.target.type !== 'checkbox') {
                $('#selectAll').prop('checked', !$('#selectAll').prop('checked')).trigger('change');
            }
        });

        // Handle bulk cancel button
        $('#bulkCancelBtn').click(function() {
            const selectedIds = [];
            const selectedBookDetails = [];
            
            $('.reservation-checkbox:checked').each(function() {
                const $row = $(this).closest('tr');
                selectedIds.push($(this).data('id'));
                const bookTitle = $row.find('.book-details-title').text().trim();
                const userName = $row.find('td:eq(1) strong').text().trim();
                selectedBookDetails.push(`${bookTitle} (${userName})`);
            });
            
            if (selectedIds.length === 0) return;
            
            let message = `<p>Are you sure you want to cancel ${selectedIds.length} reservation(s)?</p>`;
            
            if (selectedBookDetails.length > 0) {
                message += `<div style="max-height: 200px; overflow-y: auto; margin-top: 10px; text-align: left;">
                    <ul>
                        ${selectedBookDetails.map(detail => `<li>${detail}</li>`).join('')}
                    </ul>
                </div>`;
            }
            
            Swal.fire({
                title: 'Cancel Multiple Reservations',
                html: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Cancel Them',
                cancelButtonText: 'No, Keep Them',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create a form to submit the IDs
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'reservation_bulk_cancel.php';
                    
                    // Add IDs as hidden fields
                    selectedIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'reservation_ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        // Handle bulk ready button
        $('#bulkReadyBtn').click(function() {
            const selectedIds = [];
            const selectedBooks = [];
            
            $('.reservation-checkbox:checked').each(function() {
                const status = $(this).data('status');
                if (status === 'Pending') {
                    selectedIds.push($(this).data('id'));
                    const $row = $(this).closest('tr');
                    const bookTitle = $row.find('.book-details-title').text().trim();
                    const userName = $row.find('td:eq(1) strong').text().trim();
                    selectedBooks.push({ title: bookTitle, borrower: userName });
                }
            });
            
            if (selectedIds.length === 0) {
                Swal.fire({
                    title: 'No Eligible Reservations',
                    text: 'Please select reservations with "Pending" status.',
                    icon: 'info'
                });
                return;
            }
            
            let booksListHtml = '<ul class="text-left mt-3">';
            selectedBooks.forEach(book => {
                booksListHtml += `<li>${book.title} - ${book.borrower}</li>`;
            });
            booksListHtml += '</ul>';
            
            Swal.fire({
                title: 'Mark Books as Ready for Pickup?',
                html: `Are you sure you want to mark ${selectedIds.length} book(s) as ready for pickup?${booksListHtml}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Mark as Ready',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Use AJAX to send the IDs for marking as ready
                    $.ajax({
                        url: 'reservation_ready.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ ids: selectedIds }),
                        success: function(response) {
                            try {
                                const result = typeof response === 'object' ? response : JSON.parse(response);
                                if (result.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Books marked as ready for pickup',
                                        icon: 'success'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error',
                                        text: result.message || 'Failed to mark books as ready',
                                        icon: 'error'
                                    });
                                }
                            } catch (e) {
                                console.error("Error processing response:", e, response);
                                Swal.fire({
                                    title: 'Error',
                                    text: 'An unexpected error occurred',
                                    icon: 'error'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", status, error);
                            Swal.fire({
                                title: 'Error',
                                text: 'Failed to communicate with the server',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

        // Handle bulk receive button
        $('#bulkReceiveBtn').click(function() {
            const selectedIds = [];
            const selectedBooks = [];
            
            $('.reservation-checkbox:checked').each(function() {
                const status = $(this).data('status');
                if (status === 'Ready') {
                    selectedIds.push($(this).data('id'));
                    const $row = $(this).closest('tr');
                    const bookTitle = $row.find('.book-details-title').text().trim();
                    const userName = $row.find('td:eq(1) strong').text().trim();
                    selectedBooks.push({ title: bookTitle, borrower: userName });
                }
            });
            
            if (selectedIds.length === 0) {
                Swal.fire({
                    title: 'No Eligible Reservations',
                    text: 'Please select reservations with "Ready" status.',
                    icon: 'info'
                });
                return;
            }
            
            let booksListHtml = '<ul class="text-left mt-3">';
            selectedBooks.forEach(book => {
                booksListHtml += `<li>${book.title} - ${book.borrower}</li>`;
            });
            booksListHtml += '</ul>';
            
            Swal.fire({
                title: 'Issue Books to Users?',
                html: `Are you sure you want to issue ${selectedIds.length} book(s) to the users?${booksListHtml}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Issue Books',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Use AJAX to send the IDs for issuing books
                    $.ajax({
                        url: 'reservation_receive.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ ids: selectedIds }),
                        success: function(response) {
                            try {
                                const result = typeof response === 'object' ? response : JSON.parse(response);
                                if (result.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Books issued to users successfully',
                                        icon: 'success'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error',
                                        text: result.message || 'Failed to issue books',
                                        icon: 'error'
                                    });
                                }
                            } catch (e) {
                                console.error("Error processing response:", e, response);
                                Swal.fire({
                                    title: 'Error',
                                    text: 'An unexpected error occurred',
                                    icon: 'error'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", status, error);
                            Swal.fire({
                                title: 'Error',
                                text: 'Failed to communicate with the server',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Initialize row selection state on page load
        updateRowSelectionState();
        updateBulkButtons();
    });
</script>
</body>
</html>