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
    
    /* Status badges styling */
    .badge {
        display: inline-block;
        white-space: normal;
        text-align: center;
        line-height: 1.2;
        padding: 8px;
    }
    
    .badge br {
        display: block;
    }
    
    .badge small {
        display: block;
        margin-top: 3px;
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
                                <th class="details-column">Book Details</th>
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
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
