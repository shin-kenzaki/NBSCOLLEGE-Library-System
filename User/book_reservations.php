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

<head>
    <!-- Include SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .table-responsive table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Allow details column to wrap */
        .table-responsive table td.book-details {
            white-space: normal;
        }
        /* Keep status badge content together */
        .badge {
            display: inline-block;
            white-space: normal;
            text-align: center;
            line-height: 1.2;
            padding: 8px;
        }
        .badge br {
            display: block; /* Allow line breaks in badges */
        }
        .badge small {
            display: block;
            margin-top: 3px;
        }
        .table-responsive table td,
        .table-responsive table th {
            vertical-align: middle !important;
        }
        .checkbox-cell {
            cursor: pointer;
            text-align: center;
            vertical-align: middle;
        }
        .checkbox-cell:hover {
            background-color: rgba(0, 123, 255, 0.1); /* Light blue hover effect */
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
        .empty-table-message {
            text-align: center;
            padding: 20px;
            font-size: 1.1em;
            color: #666;
        }
        /* Fixed width for columns to improve layout consistency */
        .table-responsive table th.check-column,
        .table-responsive table td.check-column {
            width: 5% !important;
            max-width: 40px !important;
        }
        .table-responsive table th.id-column,
        .table-responsive table td.id-column {
            width: 8% !important;
            max-width: 80px !important;
        }
        .table-responsive table th.details-column,
        .table-responsive table td.details-column {
            width: 42% !important;
        }
        .table-responsive table th.date-column,
        .table-responsive table td.date-column {
            width: 20% !important;
        }
        .table-responsive table th.status-column,
        .table-responsive table td.status-column {
            width: 25% !important;
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
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Current Reservations</h6>
                <div class="d-flex">
                    <a href="searchbook.php" class="btn btn-primary btn-sm mr-2">
                        <i class="fas fa-search"></i> Search Books
                    </a>
                    <button id="bulkCancelBtn" class="btn btn-danger btn-sm" disabled>
                        Cancel Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-no-lines" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center check-column" style="width: 5%;">
                                    <input type="checkbox" id="selectAll" title="Select/Unselect All">
                                </th>
                                <th class="text-center id-column" style="width: 8%;">Reservation ID</th>
                                <th class="text-center details-column" style="width: 42%;">Book Details</th>
                                <th class="text-center date-column" style="width: 20%;">Reservation Date</th>
                                <th class="text-center status-column" style="width: 25%;">Status</th>
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
                                
                                $additionalDetails = !empty($detailsArray) ? implode(' | ', $detailsArray) : '';
                            ?>
                                <tr data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                    <td class="text-center checkbox-cell check-column">
                                        <input type="checkbox" class="reservation-checkbox" data-id="<?php echo $row['id']; ?>">
                                    </td>
                                    <td class="text-center id-column"><?php echo htmlspecialchars($row['id']); ?></td>
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
                                        <?php echo date('M j, Y', strtotime($row['reserve_date'])); ?><br>
                                        <small><?php echo date('h:i A', strtotime($row['reserve_date'])); ?></small>
                                    </td>
                                    <td class="text-center status-column">
                                        <?php if ($row["status"] == 'Ready'): ?>
                                            <span class="badge badge-success" 
                                                  data-toggle="tooltip" 
                                                  title="Made ready by: <?php echo htmlspecialchars($row["ready_by_name"]); ?> on <?php echo date('Y-m-d h:i A', strtotime($row["ready_date"])); ?>">
                                                <i class="fas fa-check"></i> READY FOR PICKUP
                                                <small>Please proceed to the library</small>
                                            </span>
                                        <?php elseif ($row["status"] == 'Pending'): ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> PENDING
                                                <small>Waiting for librarian to process</small>
                                            </span>
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
    if ($('#dataTable').length > 0) {
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
                { "orderable": false, "targets": 0 } // Disable sorting for checkbox column
            ]
        });
    }
    
    // Handle select-all checkbox
    $('#selectAll').on('click', function() {
        $('.reservation-checkbox').prop('checked', this.checked);
        updateCancelButtonState();
    });
    
    // Handle individual reservation checkboxes
    $(document).on('change', '.reservation-checkbox', function() {
        const allChecked = $('.reservation-checkbox:checked').length === $('.reservation-checkbox').length;
        $('#selectAll').prop('checked', allChecked);
        updateCancelButtonState();
    });
    
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
    
    // Initialize the cancel button state
    updateCancelButtonState();

    // Enhanced checkbox cell click handler
    $(document).on('click', '.checkbox-cell', function(e) {
        // Only handle if the click wasn't directly on the checkbox
        if (e.target.type !== 'checkbox') {
            const checkbox = $(this).find('.reservation-checkbox');
            checkbox.prop('checked', !checkbox.prop('checked'));
            checkbox.trigger('change'); // Trigger change event to update UI
        }
        e.stopPropagation(); // Prevent the event from bubbling up
    });
});
</script>
