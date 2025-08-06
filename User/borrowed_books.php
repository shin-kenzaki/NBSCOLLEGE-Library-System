<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header("Location: index.php");
    exit();
}

// Get user ID - check both possible session variables
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];

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
                         WHERE user_id = ? AND status = 'Active'";
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
    br.id, 
    b.id as book_id,
    b.title,
    b.ISBN,
    b.series,
    b.volume,
    b.part,
    b.edition,
    b.shelf_location,
    b.accession,
    br.issue_date,
    br.due_date,
    br.status,
    CONCAT(a.firstname, ' ', a.lastname) as issued_by_name
FROM borrowings br
JOIN books b ON br.book_id = b.id
LEFT JOIN admins a ON br.issued_by = a.id
WHERE br.user_id = ? AND br.status IN ('Active', 'Overdue')
ORDER BY br.due_date ASC";

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
        .table-responsive table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
        .empty-borrowing-message {
            text-align: center;
            padding: 20px;
            font-size: 1.1em;
            color: #666;
        }
        .table-responsive table td,
        .table-responsive table th {
            vertical-align: middle !important;
        }
        /* Fixed width for columns to improve layout consistency */
        .table-responsive table th.accession-column,
        .table-responsive table td.accession-column {
            width: 8% !important;
            max-width: 80px !important;
        }
        .table-responsive table th.details-column,
        .table-responsive table td.details-column {
            width: 30% !important;
        }
        .table-responsive table th.date-column,
        .table-responsive table td.date-column {
            width: 12% !important;
        }
        .table-responsive table th.status-column,
        .table-responsive table td.status-column {
            width: 15% !important;
        }
        .days-remaining {
            font-size: 16px;
            font-weight: bold;
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
    </style>
</head>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">My Borrowings</h1>
        </div>

        <!-- Borrowing Limit Alert -->
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle me-2"></i> 
            As a <?php echo $userType; ?>, you can borrow or reserve up to <?php echo $maxItems; ?> items at once.
            You currently have <?php echo $currentTotal; ?> active item(s) (borrowed or reserved).
            <?php if ($remainingSlots > 0): ?>
                You can borrow up to <?php echo $remainingSlots; ?> more item(s).
            <?php else: ?>
                You cannot borrow any more items until you return some.
            <?php endif; ?>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Current Borrowings</h6>
                <a href="searchbook.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Search Books
                </a>
            </div>
            <div class="card-body">
                <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-no-lines" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center accession-column">Accession</th>
                                <th class="text-center details-column">Book Details</th>
                                <th class="text-center date-column">Issue Date</th>
                                <th class="text-center date-column">Due Date</th>
                                <th class="text-center status-column">Days Remaining</th>
                                <th class="text-center">Issued By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                // Calculate days left
                                $due_date = new DateTime($row['due_date']);
                                $today = new DateTime(date('Y-m-d'));
                                $days_left = $today->diff($due_date)->days;
                                $is_overdue = $today > $due_date;
                                
                                // Format additional details
                                $detailsArray = [];
                                if (!empty($row['ISBN'])) $detailsArray[] = 'ISBN: ' . htmlspecialchars($row['ISBN']);
                                if (!empty($row['series'])) $detailsArray[] = 'Series: ' . htmlspecialchars($row['series']);
                                if (!empty($row['volume'])) $detailsArray[] = 'Vol: ' . htmlspecialchars($row['volume']);
                                if (!empty($row['part'])) $detailsArray[] = 'Part: ' . htmlspecialchars($row['part']);
                                if (!empty($row['edition'])) $detailsArray[] = 'Ed: ' . htmlspecialchars($row['edition']);
                                
                                $additionalDetails = !empty($detailsArray) ? implode(' | ', $detailsArray) : '';
                            ?>
                                <tr>
                                    <td class="text-center accession-column">
                                        <?php echo htmlspecialchars($row['accession']); ?>
                                    </td>
                                    <td class="book-details details-column">
                                        <div class="book-details-title">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </div>
                                        <?php if (!empty($additionalDetails)): ?>
                                        <div class="book-details-info">
                                            <?php echo $additionalDetails; ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="book-details-info">
                                            <strong>Location:</strong> <?php echo htmlspecialchars($row['shelf_location']); ?>
                                        </div>
                                    </td>
                                    <td class="text-center date-column">
                                        <?php echo date('M j, Y', strtotime($row['issue_date'])); ?>
                                        <br>
                                        <small><?php echo date('h:i A', strtotime($row['issue_date'])); ?></small>
                                    </td>
                                    <td class="text-center date-column">
                                        <?php echo date('M j, Y', strtotime($row['due_date'])); ?>
                                    </td>
                                    <td class="text-center status-column">
                                        <?php if ($is_overdue): ?>
                                            <div class="days-remaining text-danger">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                OVERDUE BY <?php echo $days_left; ?> DAY<?php echo $days_left > 1 ? 'S' : ''; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="days-remaining <?php echo $days_left <= 2 ? 'text-warning' : 'text-info'; ?>">
                                                <i class="fas fa-calendar-day"></i>
                                                <?php echo $days_left; ?> DAY<?php echo $days_left > 1 ? 'S' : ''; ?> LEFT
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo htmlspecialchars($row['issued_by_name']); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="empty-borrowing-message">
                        <div class="alert alert-info">
                            <i class="fas fa-book fa-lg mr-2"></i>
                            You don't have any active borrowings at the moment.
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

<script>
$(document).ready(function() {
    <?php if ($result && $result->num_rows > 0): ?>
    $('#dataTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search within results...",
            "emptyTable": "No active borrowings found",
            "zeroRecords": "No matching borrowings found"
        },
        "pageLength": 10,
        "order": [[3, 'asc']], // Sort by due date (closest due date first)
        "columnDefs": [
            { "width": "8%", "className": "accession-column", "targets": 0 },
            { "width": "30%", "className": "details-column", "targets": 1 },
            { "width": "12%", "className": "date-column", "targets": 2 },
            { "width": "12%", "className": "date-column", "targets": 3 },
            { "width": "15%", "className": "status-column", "targets": 4 }
        ],
        "responsive": true,
        "scrollX": true,
        "autoWidth": false,
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
        }
    });
    
    // Adjust table columns on window resize
    $(window).on('resize', function() {
        $('#dataTable').DataTable().columns.adjust();
    });
    <?php endif; ?>
});
</script>