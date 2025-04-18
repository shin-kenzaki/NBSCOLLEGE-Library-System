<?php
session_start();
include '../db.php';

// Add error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user type to determine borrowing limit
$userTypeQuery = "SELECT usertype FROM users WHERE id = ?";
$stmt = $conn->prepare($userTypeQuery);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$userTypeResult = $stmt->get_result();
$userType = 'Student'; // Default to student (3 limit)
$maxItems = 3; // Default limit

if ($userTypeResult->num_rows > 0) {
    $userType = $userTypeResult->fetch_assoc()['usertype'];
    // If user is faculty or staff, set limit to 5
    if (strtolower($userType) == 'faculty' || strtolower($userType) == 'staff') {
        $maxItems = 5;
    }
}

// Get active borrowings and reservations count
$activeBorrowingsQuery = "SELECT COUNT(*) as count FROM borrowings 
                         WHERE user_id = ? AND status = 'Borrowed'";
$stmt = $conn->prepare($activeBorrowingsQuery);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$activeBorrowingsResult = $stmt->get_result();
$activeBorrowings = $activeBorrowingsResult->fetch_assoc()['count'];

$activeReservationsQuery = "SELECT COUNT(*) as count FROM reservations 
                          WHERE user_id = ? AND status IN ('Pending', 'Ready', 'Reserved')";
$stmt = $conn->prepare($activeReservationsQuery);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$activeReservationsResult = $stmt->get_result();
$activeReservations = $activeReservationsResult->fetch_assoc()['count'];

$currentTotal = $activeBorrowings + $activeReservations;
$remainingSlots = $maxItems - $currentTotal;

// Updated query to include ISBN, Series, Volume, Part, and Edition
$query = "SELECT 
    r.id,
    b.id as book_id,
    b.title,
    b.ISBN,
    b.series,
    b.volume,
    b.part,
    b.edition,
    b.shelf_location,
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
AND (r.status = 'Pending' OR r.status = 'Ready')
ORDER BY r.reserve_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

include 'inc/header.php';
?>

<!-- Include SweetAlert CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    /* Fix checkbox alignment in tables - improved centering */
    .table th.check-column,
    .table td.check-column {
        text-align: center;
        vertical-align: middle;
        width: 40px !important;
        min-width: 40px !important;
        padding: 0.75rem;
        cursor: pointer;
    }
    
    /* Remove absolute positioning that was causing issues */
    .table th input[type="checkbox"],
    .table td input[type="checkbox"] {
        cursor: pointer;
        position: relative;
        margin: 0 auto;
        display: block;
    }
    
    /* Enhanced checkbox cell styling */
    .checkbox-cell {
        text-align: center;
        vertical-align: middle;
        position: relative;
        width: 40px !important;
        min-width: 40px !important;
        padding: 0.75rem !important;
    }
    
    /* Improve checkbox visibility */
    .form-check-input {
        width: 20px;
        height: 20px;
        cursor: pointer;
        margin: 0 auto;
        display: block;
        border: 1px solid #d1d3e2;
    }
    
    /* Highlight checkbox row on hover */
    #dataTable tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.075);
    }
    
    /* Highlight selected rows */
    #dataTable tbody tr.selected-row {
        background-color: rgba(0, 123, 255, 0.15);
    }
    
    /* Book details styling */
    .book-details-title {
        font-weight: bold;
        color: #4e73df;
        margin-bottom: 5px;
    }
    
    .book-details-info {
        color: #666;
        font-size: 0.9em;
    }
    
    .empty-table-message {
        text-align: center;
        padding: 20px;
        font-size: 1.1em;
        color: #666;
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
    
    /* Fixed width for columns to improve layout consistency */
    .check-column {
        width: 5% !important;
        max-width: 40px !important;
    }
    
    .details-column {
        width: 35% !important;
    }

    /* Enhanced Status Styles */
    .status-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        border-radius: 8px;
        padding: 10px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .status-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .status-icon {
        font-size: 1.8rem;
        margin-bottom: 8px;
    }
    
    .status-title {
        font-weight: bold;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }
    
    .status-subtitle {
        font-size: 0.8rem;
        margin-bottom: 8px;
        text-align: center;
    }
    
    .time-indicator {
        background-color: rgba(0, 0, 0, 0.05);
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 8px;
    }
    
    /* Add progress bar for time indication */
    .progress-container {
        width: 100%;
        background-color: rgba(0, 0, 0, 0.1);
        border-radius: 4px;
        height: 4px;
        margin-top: 6px;
        overflow: hidden;
    }
    
    .progress-bar {
        height: 100%;
        border-radius: 4px;
    }
    
    /* Progress bar colors */
    .progress-normal {
        background-color: #28a745;
    }
    
    .progress-warning {
        background-color: #ffc107;
    }
    
    .progress-alert {
        background-color: #dc3545;
    }
    
    /* Improved time thresholds */
    .time-normal {
        color: #28a745;
    }
    
    .time-warning {
        color: #ffc107;
        font-weight: bold;
    }
    
    .time-alert {
        color: #dc3545;
        font-weight: bold;
    }
    
    .time-critical {
        color: #dc3545;
        font-weight: bold;
        animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    
    /* Time badge styles */
    .time-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 0.65rem;
        font-weight: bold;
        margin-left: 4px;
    }
    
    .time-badge-normal {
        background-color: rgba(40, 167, 69, 0.2);
        color: #28a745;
    }
    
    .time-badge-warning {
        background-color: rgba(255, 193, 7, 0.2);
        color: #856404;
    }
    
    .time-badge-alert {
        background-color: rgba(220, 53, 69, 0.2);
        color: #721c24;
    }
    
    .time-badge-critical {
        background-color: rgba(220, 53, 69, 0.3);
        color: #721c24;
        animation: pulse 1.5s infinite;
    }
    
    /* Table responsive style */
    .table-responsive table td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Allow details column to wrap */
    .table-responsive table td.book-details {
        white-space: normal;
    }
    
    /* Vertical alignment for all table cells */
    .table td, .table th {
        vertical-align: middle;
    }
    
    /* Special styling for date and status columns */
    .date-column, .status-column {
        vertical-align: middle !important;
        text-align: center;
        display: table-cell;
        height: 100%;
    }
    
    /* Ensure content in date column is centered vertically */
    .date-column * {
        margin: 0;
        padding: 0;
    }
    
    /* Add some breathing room between date and time */
    .date-column small {
        margin-top: 3px;
        display: block;
    }
    
    /* Make rows that are clickable have a pointer cursor */
    #dataTable tbody tr {
        cursor: pointer;
    }
    
    /* Fix checkbox cell alignment */
    .table td.check-column, 
    .table th.check-column {
        position: relative;
        padding: 0.75rem;
        text-align: center;
        vertical-align: middle;
    }
    
    /* Fix for DataTables integration */
    .dataTables_wrapper .check-column {
        width: 40px !important;
        min-width: 40px !important;
        max-width: 40px !important;
    }
    
    /* Hide sorting icons for checkbox column */
    th.check-column.sorting::before,
    th.check-column.sorting::after,
    th.check-column.sorting_asc::before,
    th.check-column.sorting_asc::after,
    th.check-column.sorting_desc::before,
    th.check-column.sorting_desc::after {
        display: none !important;
    }
    
    /* Override DataTables styling for checkbox column */
    .dataTables_wrapper th.check-column {
        padding-right: 0.75rem !important; 
        background-image: none !important;
    }
</style>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">My Reservations</h1>
        </div>

        <!-- Borrowing Limit Alert -->
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle me-2"></i> 
            As a <?php echo $userType; ?>, you can borrow or reserve up to <?php echo $maxItems; ?> items at once.
            You currently have <?php echo $currentTotal; ?> active item(s) (borrowed or reserved).
            <?php if ($remainingSlots > 0): ?>
                You can reserve up to <?php echo $remainingSlots; ?> more item(s).
            <?php else: ?>
                You cannot reserve any more items until you return some.
            <?php endif; ?>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Current Reservations</h6>
                <div class="d-flex">
                    <a href="searchbook.php" class="btn btn-primary btn-sm mr-2">
                        <i class="fas fa-search"></i> Search Books
                    </a>
                    <button id="bulkCancelBtn" class="btn btn-danger btn-sm" disabled>
                        <i class="fas fa-trash me-2"></i> Cancel Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-no-lines" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center check-column no-sort">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </th>
                                <th width="8%" class="text-center">ID</th>
                                <th class="text-center details-column">Book Details</th>
                                <th width="20%" class="text-center date-column">Reserved On</th>
                                <th width="25%" class="text-center status-column">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                // Format additional details
                                $detailsArray = [];
                                if (!empty($row['edition'])) $detailsArray[] = 'Edition: ' . htmlspecialchars($row['edition']);
                                if (!empty($row['series'])) $detailsArray[] = 'Series: ' . htmlspecialchars($row['series']);
                                if (!empty($row['volume'])) $detailsArray[] = 'Volume: ' . htmlspecialchars($row['volume']);
                                if (!empty($row['part'])) $detailsArray[] = 'Part: ' . htmlspecialchars($row['part']);
                                if (!empty($row['ISBN'])) $detailsArray[] = 'ISBN: ' . htmlspecialchars($row['ISBN']);
                            ?>
                                <tr>
                                    <td class="text-center checkbox-cell check-column">
                                        <input class="form-check-input reservation-checkbox" type="checkbox" data-id="<?php echo $row['id']; ?>">
                                    </td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td class="book-details">
                                        <div class="book-details-title"><?php echo htmlspecialchars($row['title']); ?></div>
                                        <div class="book-details-info">
                                            <?php echo !empty($detailsArray) ? implode("<br>", $detailsArray) : ''; ?>
                                            <?php if (!empty($row['shelf_location'])): ?>
                                                <?php echo !empty($detailsArray) ? '<br>' : ''; ?>
                                                <strong>Location:</strong> <?php echo htmlspecialchars($row['shelf_location']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center date-column">
                                        <?php echo date('M j, Y', strtotime($row['reserve_date'])); ?><br>
                                        <small><?php echo date('h:i A', strtotime($row['reserve_date'])); ?></small>
                                    </td>
                                    <td class="text-center status-column">
                                        <?php 
                                            // Calculate time differences with more precision
                                            $now = new DateTime();
                                            $reserveDate = new DateTime($row['reserve_date']);
                                            $interval = $now->diff($reserveDate);
                                            $diffInDays = $interval->days;
                                            $diffInHours = $interval->h + ($interval->days * 24);
                                            $diffInMinutes = $interval->i + ($diffInHours * 60);
                                            $diffInSeconds = $interval->s + ($diffInMinutes * 60);
                                            
                                            // Determine wait progress percentage (72 hours = 100%)
                                            $maxWaitHours = 72; // 3 days is considered max normal wait time
                                            $waitProgressPercent = min(100, ($diffInHours / $maxWaitHours) * 100);
                                            
                                            // Determine time threshold class with more granularity
                                            $timeThreshold = 'time-normal';
                                            $progressClass = 'progress-normal';
                                            
                                            if ($diffInHours >= 72) {
                                                $timeThreshold = 'time-critical';
                                                $progressClass = 'progress-alert';
                                                $timeBadgeClass = 'time-badge-critical';
                                            } else if ($diffInHours >= 48) {
                                                $timeThreshold = 'time-alert';
                                                $progressClass = 'progress-alert';
                                                $timeBadgeClass = 'time-badge-alert';
                                            } else if ($diffInHours >= 24) {
                                                $timeThreshold = 'time-warning';
                                                $progressClass = 'progress-warning';
                                                $timeBadgeClass = 'time-badge-warning';
                                            } else {
                                                $timeBadgeClass = 'time-badge-normal';
                                            }
                                            
                                            // Format time ago display with more precision
                                            if ($diffInDays > 1) {
                                                $timeAgo = $diffInDays . ' days ago';
                                                $timePriority = 'Long wait';
                                            } else if ($diffInDays == 1) {
                                                $timeAgo = 'Yesterday';
                                                $timePriority = '24h+ wait';
                                            } else if ($diffInHours > 0) {
                                                if ($diffInHours == 1) {
                                                    // Handle case for 1 hour with additional minutes
                                                    $remainingMinutes = $diffInMinutes - 60;
                                                    if ($remainingMinutes > 0) {
                                                        $timeAgo = '1 hour ' . $remainingMinutes . ' minute' . ($remainingMinutes > 1 ? 's' : '') . ' ago';
                                                    } else {
                                                        $timeAgo = '1 hour ago';
                                                    }
                                                    $timePriority = '1h wait';
                                                } else {
                                                    $timeAgo = $diffInHours . ' hours ago';
                                                    $timePriority = $diffInHours . 'h wait';
                                                }
                                            } else if ($diffInMinutes > 0) {
                                                $timeAgo = $diffInMinutes . ' minute' . ($diffInMinutes > 1 ? 's' : '') . ' ago';
                                                $timePriority = 'New';
                                            } else {
                                                $timeAgo = 'Just now';
                                                $timePriority = 'New';
                                            }
                                            
                                            // Generate detailed timestamp for tooltip
                                            $exactTimestamp = $reserveDate->format('M j, Y \a\t h:i:s A');
                                            $waitDuration = '';
                                            
                                            if ($diffInDays > 0) {
                                                $waitDuration .= $diffInDays . ' day' . ($diffInDays > 1 ? 's ' : ' ');
                                            }
                                            if ($interval->h > 0) {
                                                $waitDuration .= $interval->h . ' hour' . ($interval->h > 1 ? 's ' : ' ');
                                            }
                                            if ($interval->i > 0) {
                                                $waitDuration .= $interval->i . ' minute' . ($interval->i > 1 ? 's ' : ' ');
                                            }
                                            
                                            $waitDuration = trim($waitDuration) ?: '0 minutes';
                                            
                                            if ($row["status"] == 'Ready'): 
                                                // Calculate ready time with more precision
                                                $readyDate = new DateTime($row['ready_date']);
                                                $readyInterval = $now->diff($readyDate);
                                                $readyDiffInDays = $readyInterval->days;
                                                $readyDiffInHours = $readyInterval->h + ($readyInterval->days * 24);
                                                $readyDiffInMinutes = $readyInterval->i + ($readyDiffInHours * 60);
                                                
                                                // Format ready time ago with more precision
                                                if ($readyDiffInDays > 1) {
                                                    $readyTimeAgo = $readyDiffInDays . ' days';
                                                    $readyPriority = 'Pick up soon';
                                                } else if ($readyDiffInDays == 1) {
                                                    $readyTimeAgo = '1 day';
                                                    $readyPriority = 'Pick up soon';
                                                } else if ($readyDiffInHours > 0) {
                                                    if ($readyDiffInHours == 1) {
                                                        // Handle case for 1 hour with additional minutes
                                                        $remainingMinutes = $readyDiffInMinutes - 60;
                                                        if ($remainingMinutes > 0) {
                                                            $readyTimeAgo = '1 hour ' . $remainingMinutes . ' minute' . ($remainingMinutes > 1 ? 's' : '');
                                                        } else {
                                                            $readyTimeAgo = '1 hour';
                                                        }
                                                        $readyPriority = 'Recent';
                                                    } else {
                                                        $readyTimeAgo = $readyDiffInHours . ' hours';
                                                        $readyPriority = 'Recent';
                                                    }
                                                } else if ($readyDiffInMinutes > 0) {
                                                    $readyTimeAgo = $readyDiffInMinutes . ' minute' . ($readyDiffInMinutes > 1 ? 's' : '');
                                                    $readyPriority = 'Just ready';
                                                } else {
                                                    $readyTimeAgo = 'Just now';
                                                    $readyPriority = 'Just ready';
                                                }
                                                
                                                // Generate detailed ready timestamp for tooltip
                                                $exactReadyTimestamp = $readyDate->format('M j, Y \a\t h:i:s A');
                                                
                                                // Calculate time since reservation was created until marked ready
                                                $reserveToReadyInterval = $readyDate->diff($reserveDate);
                                                $processTime = '';
                                                
                                                if ($reserveToReadyInterval->days > 0) {
                                                    $processTime .= $reserveToReadyInterval->days . ' day' . ($reserveToReadyInterval->days > 1 ? 's ' : ' ');
                                                }
                                                if ($reserveToReadyInterval->h > 0) {
                                                    $processTime .= $reserveToReadyInterval->h . ' hour' . ($reserveToReadyInterval->h > 1 ? 's ' : ' ');
                                                }
                                                if ($reserveToReadyInterval->i > 0) {
                                                    $processTime .= $reserveToReadyInterval->i . ' minute' . ($reserveToReadyInterval->i > 1 ? 's ' : ' ');
                                                }
                                                
                                                $processTime = trim($processTime) ?: '0 minutes';
                                                
                                                $tooltipContent = 'Reserved on: ' . $exactTimestamp . 
                                                                 '<br>Made ready by: ' . htmlspecialchars($row["ready_by_name"]) . 
                                                                 '<br>Ready on: ' . $exactReadyTimestamp .
                                                                 '<br>Processing time: ' . $processTime;
                                        ?>
                                            <div class="status-card status-ready">
                                                <div class="status-icon">
                                                    <i class="fas fa-check-circle"></i>
                                                    <span class="time-badge <?= ($readyDiffInDays > 3) ? 'time-badge-warning' : 'time-badge-normal' ?>">
                                                        <?= $readyPriority ?>
                                                    </span>
                                                </div>
                                                <div class="status-title">READY FOR PICKUP</div>
                                                <div class="status-subtitle">Please proceed to the library</div>
                                                <div class="time-indicator">
                                                    <i class="far fa-clock"></i> Ready for <?= $readyTimeAgo ?>
                                                </div>
                                                <div class="progress-container" title="Book has been ready for <?= $readyTimeAgo ?>">
                                                    <div class="progress-bar <?= ($readyDiffInDays > 3) ? 'progress-warning' : 'progress-normal' ?>" 
                                                         style="width: <?= min(100, ($readyDiffInHours / 72) * 100) ?>%"></div>
                                                </div>
                                            </div>
                                        <?php elseif ($row["status"] == 'Pending'): 
                                            $tooltipContent = 'Reserved on: ' . $exactTimestamp . 
                                                             '<br>Waiting time: ' . $waitDuration;
                                        ?>
                                            <div class="status-card status-pending">
                                                <div class="status-icon">
                                                    <i class="fas fa-hourglass-half"></i>
                                                    <span class="time-badge <?= $timeBadgeClass ?>">
                                                        <?= $timePriority ?>
                                                    </span>
                                                </div>
                                                <div class="status-title">PENDING</div>
                                                <div class="status-subtitle">Waiting for librarian</div>
                                                <div class="time-indicator <?= $timeThreshold ?>">
                                                    <i class="far fa-clock"></i> Reserved <?= $timeAgo ?>
                                                </div>
                                                <div class="progress-container" title="Waiting for <?= $waitDuration ?>">
                                                    <div class="progress-bar <?= $progressClass ?>" style="width: <?= $waitProgressPercent ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="empty-table-message">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle fa-lg mr-2"></i>
                            You don't have any active reservations at the moment.
                            <div class="mt-3">
                                <a href="searchbook.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Browse Available Books
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>

<!-- Include SweetAlert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function() {
    let dataTable;
    
    // Initialize DataTable if there are reservations
    if ($('#dataTable').length > 0 && $('#dataTable tbody tr').length > 0) {
        dataTable = $('#dataTable').DataTable({
            "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search reservations...",
                "emptyTable": "You don't have any reservations",
                "zeroRecords": "No matching reservations found"
            },
            "pageLength": 10,
            "columnDefs": [
                { 
                    "orderable": false, 
                    "targets": 0,
                    "searchable": false,
                    "width": "40px",
                    "className": "check-column"
                }
            ],
            "order": [], // Disable initial sorting
            "initComplete": function() {
                // Remove sorting classes from checkbox column
                $('#dataTable thead th:first-child').removeClass('sorting sorting_asc sorting_desc').addClass('sorting_disabled');
                
                // Apply additional CSS to ensure the sort icon is gone
                $('#dataTable thead th:first-child').css({
                    'background-image': 'none'
                });
            },
            "drawCallback": function() {
                // Reapply row click handlers after DataTable redraws
                attachRowClickHandlers();
                // Reset Select All checkbox state
                updateSelectAllState();
            }
        });
    }
    
    // Function to attach row click handlers
    function attachRowClickHandlers() {
        // Enhanced checkbox cell click handler - more robust
        $('td.check-column').off('click').on('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = $(this).find('input[type="checkbox"]');
                if (checkbox.length && !checkbox.prop('disabled')) {
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
                e.stopPropagation();
            }
        });
        
        // Add click handler for the checkbox column header
        $('th.check-column').off('click').on('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = $(this).find('input[type="checkbox"]');
                if (checkbox.length) {
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
                e.stopPropagation();
            }
        });
        
        // Enhanced row click handler - more reliable targeting
        $('#dataTable tbody tr').off('click').on('click', function(e) {
            // Ignore clicks on interactive elements
            if ($(e.target).is('a, button, input, i, .badge') || 
                $(e.target).closest('a, button, td.check-column, .badge').length) {
                return;
            }
            
            const checkbox = $(this).find('.reservation-checkbox');
            if (checkbox.length && !checkbox.prop('disabled')) {
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            }
        });
    }
    
    // Handle select-all checkbox with better event binding
    $(document).on('click', '#selectAll', function(e) {
        // Prevent the event from triggering the th.check-column click handler
        e.stopPropagation();
    });
    
    $('#selectAll').on('change', function() {
        $('.reservation-checkbox').prop('checked', this.checked);
        
        // Highlight selected rows
        if (this.checked) {
            $('#dataTable tbody tr').addClass('selected-row');
        } else {
            $('#dataTable tbody tr').removeClass('selected-row');
        }
        
        updateCancelButtonState();
    });
    
    // Updated handling for individual reservation checkboxes
    $(document).on('change', '.reservation-checkbox', function() {
        const row = $(this).closest('tr');
        
        // Highlight/unhighlight the selected row
        if ($(this).prop('checked')) {
            row.addClass('selected-row');
        } else {
            row.removeClass('selected-row');
        }
        
        // Update select all checkbox state
        updateSelectAllState();
        
        // Update cancel button state
        updateCancelButtonState();
    });
    
    // Function to update select all checkbox state
    function updateSelectAllState() {
        const totalCheckboxes = $('.reservation-checkbox').length;
        const checkedCheckboxes = $('.reservation-checkbox:checked').length;
        
        if (totalCheckboxes === 0) {
            $('#selectAll').prop('checked', false);
            $('#selectAll').prop('indeterminate', false);
        } else if (checkedCheckboxes === 0) {
            $('#selectAll').prop('checked', false);
            $('#selectAll').prop('indeterminate', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#selectAll').prop('checked', true);
            $('#selectAll').prop('indeterminate', false);
        } else {
            $('#selectAll').prop('checked', false);
            $('#selectAll').prop('indeterminate', true);
        }
    }
    
    // Update cancel button state and selected count
    function updateCancelButtonState() {
        const selectedCount = $('.reservation-checkbox:checked').length;
        $('#selectedCount').text(selectedCount);
        $('#bulkCancelBtn').prop('disabled', selectedCount === 0);
    }
    
    // Handle the bulk cancel button click
    $('#bulkCancelBtn').on('click', function(e) {
        e.preventDefault();
        
        // Get all selected reservation IDs
        const selectedIds = [];
        $('.reservation-checkbox:checked').each(function() {
            selectedIds.push($(this).data('id'));
        });
        
        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Reservations Selected',
                text: 'Please select at least one reservation to cancel.'
            });
            return;
        }
        
        // Confirm cancellation
        Swal.fire({
            icon: 'question',
            title: 'Cancel Reservations',
            text: `Are you sure you want to cancel ${selectedIds.length} selected reservation(s)?`,
            showCancelButton: true,
            confirmButtonText: 'Yes, cancel them',
            cancelButtonText: 'No, keep them'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we process your request',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send AJAX request to cancel_reservation.php
                $.ajax({
                    url: 'cancel_reservation.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ ids: selectedIds }),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message || 'Reservations cancelled successfully'
                            }).then(() => {
                                // Reload the page to show updated status
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to cancel reservations'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Server error occurred. Please try again later.'
                        });
                    }
                });
            }
        });
    });
    
    // Initialize handlers
    attachRowClickHandlers();
    updateCancelButtonState();
    updateSelectAllState();
    
    // Initialize tooltips with HTML support and more options
    $('[data-toggle="tooltip"]').tooltip({
        html: true,
        container: 'body',
        boundary: 'window',
        template: '<div class="tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner p-2"></div></div>'
    });
    
    // Initialize tooltips for status cards with enhanced content
    $('.status-card').each(function() {
        $(this).attr('data-toggle', 'tooltip');
        $(this).attr('data-html', 'true');
        $(this).attr('title', $(this).find('.time-indicator').text());
        
        // Set tooltip content from PHP-generated content if available
        const tooltipContent = '<?= isset($tooltipContent) ? addslashes($tooltipContent) : "" ?>';
        if (tooltipContent) {
            $(this).attr('data-original-title', tooltipContent);
        }
    });
});
</script>
