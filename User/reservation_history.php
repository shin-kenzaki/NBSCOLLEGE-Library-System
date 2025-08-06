<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userType = $_SESSION['usertype']; // Use session directly
$maxItems = (in_array(strtolower($userType), ['faculty', 'staff'])) ? 5 : 3;

// Get active borrowings count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'Active'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$activeBorrowings = $stmt->get_result()->fetch_assoc()['count'] ?? 0;

// Get active reservations count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM reservations WHERE user_id = ? AND status IN ('Pending', 'Reserved', 'Ready')");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$activeReservations = $stmt->get_result()->fetch_assoc()['count'] ?? 0;

$currentTotal = $activeBorrowings + $activeReservations;
$remainingSlots = $maxItems - $currentTotal;

// Reservation history query
$query = "SELECT 
    r.id, 
    b.title,
    b.ISBN,
    b.series,
    b.volume,
    b.part,
    b.edition,
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
ORDER BY r.reserve_date DESC";

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

<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Reservation History</h6>
                <div>
                    <a href="book_reservations.php" class="btn btn-sm btn-info mr-2">
                        <i class="fas fa-bookmark"></i> Current Reservations
                    </a>
                    <a href="searchbook.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-search"></i> Search Books
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    As a <?php echo htmlspecialchars($userType); ?>, you can borrow or reserve up to <?php echo $maxItems; ?> items at once.
                    You currently have <?php echo $currentTotal; ?> active item(s) (borrowed or reserved).
                    <?php if ($remainingSlots > 0): ?>
                        You can borrow up to <?php echo $remainingSlots; ?> more item(s).
                    <?php else: ?>
                        You cannot borrow any more items until you return some.
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-no-lines" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center">ID</th>
                                <th class="text-center">Book Details</th>
                                <th class="text-center">Reserve Date</th>
                                <th class="text-center">Ready Date</th>
                                <th class="text-center">Issue Date</th>
                                <th class="text-center">Cancel Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()):
                                $detailsArray = [];
                                if (!empty($row['ISBN'])) $detailsArray[] = 'ISBN: ' . htmlspecialchars($row['ISBN']);
                                if (!empty($row['series'])) $detailsArray[] = 'Series: ' . htmlspecialchars($row['series']);
                                if (!empty($row['volume'])) $detailsArray[] = 'Vol: ' . htmlspecialchars($row['volume']);
                                if (!empty($row['part'])) $detailsArray[] = 'Part: ' . htmlspecialchars($row['part']);
                                if (!empty($row['edition'])) $detailsArray[] = 'Ed: ' . htmlspecialchars($row['edition']);
                                $additionalDetails = implode(' | ', $detailsArray);
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td class="book-details">
                                        <div class="book-details-title"><?php echo htmlspecialchars($row['title']); ?></div>
                                        <?php if (!empty($additionalDetails)): ?>
                                            <div class="book-details-info"><?php echo $additionalDetails; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo date('M j, Y', strtotime($row['reserve_date'])); ?><br>
                                        <small><?php echo date('h:i A', strtotime($row['reserve_date'])); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['ready_date']): ?>
                                            <?php echo date('M j, Y', strtotime($row['ready_date'])); ?><br>
                                            <small><?php echo date('h:i A', strtotime($row['ready_date'])); ?></small>
                                            <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['issue_date']): ?>
                                            <?php echo date('M j, Y', strtotime($row['issue_date'])); ?><br>
                                            <small><?php echo date('h:i A', strtotime($row['issue_date'])); ?></small>
                                            <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['cancel_date']): ?>
                                            <?php echo date('M j, Y', strtotime($row['cancel_date'])); ?><br>
                                            <small><?php echo date('h:i A', strtotime($row['cancel_date'])); ?></small>
                                            <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row["status"] == 'Ready'): ?>
                                            <span class="badge badge-success p-1" data-toggle="tooltip"
                                                title="Ready by: <?php echo htmlspecialchars($row['ready_by_name']); ?> on <?php echo date('M j, Y h:i A', strtotime($row['ready_date'])); ?>">
                                                <i class="fas fa-check"></i> Ready
                                                <small>Book ready for pickup</small>
                                            </span>
                                        <?php elseif ($row["status"] == 'Cancelled'): ?>
                                            <span class="badge badge-danger p-1" data-toggle="tooltip"
                                                title="Cancelled by: <?php echo htmlspecialchars($row['cancelled_by_name']); ?> (<?php echo $row['cancelled_by_role']; ?>) on <?php echo date('M j, Y h:i A', strtotime($row['cancel_date'])); ?>">
                                                <i class="fas fa-times"></i> Cancelled
                                            </span>
                                        <?php elseif ($row["status"] == 'Issued'): ?>
                                            <span class="badge badge-primary p-1" data-toggle="tooltip"
                                                title="Issued by: <?php echo htmlspecialchars($row['issued_by_name']); ?> on <?php echo date('M j, Y h:i A', strtotime($row['issue_date'])); ?>">
                                                <i class="fas fa-book"></i> Issued
                                            </span>
                                        <?php elseif ($row["status"] == 'Pending'): ?>
                                            <span class="badge badge-warning p-1">
                                                <i class="fas fa-clock"></i> Pending
                                                <small>Awaiting librarian</small>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary p-1">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
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
                "emptyTable": "No reservation history found",
                "zeroRecords": "No matching reservations found"
            },
            "pageLength": 10,
            "order": [
                [2, 'desc']
            ],
            "responsive": false,
            "scrollX": true,
            "autoWidth": false,
            "initComplete": function() {
                $('#dataTable_filter input').addClass('form-control form-control-sm');
                $('[data-toggle="tooltip"]').tooltip();
            }
        });

        $(window).on('resize', function() {
            $('#dataTable').DataTable().columns.adjust();
        });
    });
</script>