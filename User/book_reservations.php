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

// Debug the query
$query = "SELECT 
    r.id,
    b.title,
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
AND (r.status = 'Pending' OR r.status = 'Ready')";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Debug output
echo "<!-- Debug: Number of reservations found: " . $result->num_rows . " -->";
echo "<!-- Debug: User ID: " . $user_id . " -->";

if (!$result) {
    echo "Query failed: " . $conn->error;
}

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
        /* Allow status column to wrap */
        .table-responsive table td:last-child {
            white-space: normal;
        }
        /* Keep status badge content together */
        .badge {
            display: inline-block;
            white-space: normal;
            text-align: center;
            line-height: 1.2;
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
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center checkbox-cell" style="width: 10%;">
                                    <input type="checkbox" id="selectAll" title="Select/Unselect All">
                                </th>
                                <th class="text-center" style="width: 10%;">ID</th>
                                <th class="text-center" style="width: 40%;">Title</th>
                                <th class="text-center" style="width: 20%;">Reserve Date</th>
                                <th class="text-center" style="width: 20%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result && $result->num_rows > 0): 
                                while ($row = $result->fetch_assoc()): 
                            ?>
                                    <tr data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                        <td class="text-center checkbox-cell">
                                            <input type="checkbox" class="reservation-checkbox" data-id="<?php echo $row['id']; ?>">
                                        </td>
                                        <td class="text-center"><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td class="text-center"><?php echo date('Y-m-d h:i A', strtotime($row['reserve_date'])); ?></td>
                                        <td class="text-center">
                                            <?php if ($row["status"] == 'Ready'): ?>
                                                <span class="badge badge-success p-2" 
                                                      data-toggle="tooltip" 
                                                      title="Made ready by: <?php echo htmlspecialchars($row["ready_by_name"]); ?> on <?php echo date('Y-m-d h:i A', strtotime($row["ready_date"])); ?>">
                                                    <i class="fas fa-check"></i> READY
                                                    <small>Your book is ready for pickup</small>
                                                </span>
                                            <?php elseif ($row["status"] == 'Cancelled'): ?>
                                                <span class="badge badge-danger p-2" 
                                                      data-toggle="tooltip" 
                                                      title="Cancelled by: <?php echo htmlspecialchars($row["cancelled_by_name"]); ?> (<?php echo $row["cancelled_by_role"]; ?>)
                                                             on <?php echo date('Y-m-d h:i A', strtotime($row["cancel_date"])); ?>">
                                                    <i class="fas fa-times"></i> CANCELLED
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning p-2">
                                                    <i class="fas fa-clock"></i> Pending
                                                    <small>Waiting for librarian to process</small>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>

<!-- Include SweetAlert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search within results..."
        },
        "pageLength": 10,
        "order": [[1, 'asc']], // Changed from column 0 to 1 since we added checkbox
        "columnDefs": [
            { "orderable": false, "targets": 0 } // Disable sorting for checkbox column
        ],
        "responsive": false,
        "scrollX": true,
        "autoWidth": false,
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
        }
    });
    
    // Adjust table columns on window resize
    $(window).on('resize', function () {
        $('#dataTable').DataTable().columns.adjust();
    });

    // Handle select all checkbox
    $('#selectAll').change(function() {
        const isChecked = $(this).prop('checked');
        $('.reservation-checkbox').prop('checked', isChecked);
        updateBulkButtons();
    });

    // Handle individual checkboxes
    $(document).on('change', '.reservation-checkbox', function() {
        const totalCheckable = $('.reservation-checkbox').length;
        const totalChecked = $('.reservation-checkbox:checked').length;
        
        $('#selectAll').prop({
            'checked': totalChecked > 0 && totalChecked === totalCheckable,
            'indeterminate': totalChecked > 0 && totalChecked < totalCheckable
        });
        
        updateBulkButtons();
    });

    // Update bulk cancel button visibility
    function updateBulkButtons() {
        const checkedBoxes = $('.reservation-checkbox:checked').length;
        $('#selectedCount').text(checkedBoxes);
        $('#bulkCancelBtn').prop('disabled', checkedBoxes === 0);
    }

    // Handle bulk cancel button click
    $('#bulkCancelBtn').click(function() {
        const selectedIds = [];
        
        $('.reservation-checkbox:checked').each(function() {
            selectedIds.push($(this).data('id'));
        });

        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Cancel Multiple Reservations?',
            text: `Are you sure you want to cancel ${selectedIds.length} reservation(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Cancel Them',
            cancelButtonText: 'No, Keep Them',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('cancel_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ids: selectedIds })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Error cancelling reservations');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error}`);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Success!',
                    text: 'The selected reservations have been cancelled.',
                    icon: 'success',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    });

    // Enable row selection by clicking on the checkbox cell
    $('.checkbox-cell').on('click', function(e) {
        if (e.target.tagName !== 'INPUT') { // Prevent double toggling when clicking directly on the checkbox
            const checkbox = $(this).find('.reservation-checkbox');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        }
    });
});
</script>
