<?php
session_start();
include '../db.php';

// Add error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// Debug the query
$query = "SELECT r.id, b.title, r.reserve_date, r.status 
          FROM reservations r 
          JOIN books b ON r.book_id = b.id 
          WHERE r.user_id = ? AND (r.status = 'Pending' OR r.status = 'Ready')";

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
    </style>
    <!-- Include SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Current Reservations</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Reserve Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result && $result->num_rows > 0): 
                                while ($row = $result->fetch_assoc()): 
                                    // Debug output
                                    echo "<!-- Debug: Row data: " . print_r($row, true) . " -->";
                            ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo date('Y-m-d h:i A', strtotime($row['reserve_date'])); ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'Ready'): ?>
                                                <span class="badge badge-success p-2">
                                                    <i class="fas fa-check"></i> READY
                                                    <br>
                                                    <small>Your book is ready for pickup</small>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning p-2">
                                                    <i class="fas fa-clock"></i> Pending
                                                    <br>
                                                    <small>Waiting for librarian to process</small>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-danger btn-sm cancel-reservation" data-id="<?php echo $row['id']; ?>" title="Cancel Reservation">
                                                Cancel
                                            </button>
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
        "order": [[0, 'asc']],
        "responsive": true,
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
        }
    });

    $('.cancel-reservation').on('click', function() {
        var reservationId = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'You won\'t be able to revert this!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'cancel_reservation.php',
                    type: 'POST',
                    data: { reservation_id: reservationId },
                    success: function(response) {
                        var res = JSON.parse(response);
                        Swal.fire('Cancelled!', res.message, 'success').then(() => {
                            if (res.success) {
                                location.reload();
                            }
                        });
                    },
                    error: function() {
                        Swal.fire('Failed!', 'Failed to cancel reservation.', 'error');
                    }
                });
            }
        });
    });
});
</script>
