<?php
session_start();
include('inc/header.php');

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Check if the user has the correct role
if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian') {
    header('Location: dashboard.php'); // Redirect to a page appropriate for their role or an error page
    exit();
}

// Fetch reservations data from the database
include('../db.php');
$query = "SELECT 
    r.id AS reservation_id,
    CONCAT(u.firstname, ' ', u.lastname) AS user_name,
    b.title AS book_title,
    b.accession AS accession,
    r.reserve_date,
    UPPER(r.status) as status,
    r.cancel_date,
    r.recieved_date
FROM reservations r
JOIN users u ON r.user_id = u.id
JOIN books b ON r.book_id = b.id
WHERE r.recieved_date IS NULL 
AND r.cancel_date IS NULL";
$result = $conn->query($query);
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Book Reservations List</h6>
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
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Book</th>
                                <th>Accession No.</th>
                                <th>Reserve Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result->num_rows > 0): 
                                while ($row = $result->fetch_assoc()): 
                            ?>
                                <tr data-reservation-id='<?php echo $row["reservation_id"]; ?>' data-status='<?php echo $row["status"]; ?>'>
                                    <td><?php echo $row["user_name"]; ?></td>
                                    <td><?php echo $row["book_title"]; ?></td>
                                    <td><?php echo $row["accession"]; ?></td>
                                    <td><?php echo $row["reserve_date"]; ?></td>
                                    <?php
                                    $status = 'Pending';
                                    $statusClass = 'text-warning';
                                    
                                    if ($row["cancel_date"] !== null) {
                                        $status = 'Cancelled';
                                        $statusClass = 'text-danger';
                                    } elseif ($row["recieved_date"] !== null) {
                                        $status = 'Received';
                                        $statusClass = 'text-success';
                                    } elseif ($row["status"] === 'READY') {
                                        $status = 'Ready';
                                        $statusClass = 'text-primary';
                                    }
                                    ?>
                                    <td><span class='font-weight-bold <?php echo $statusClass; ?>'><?php echo $status; ?></span></td>
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
    </div>
</div>
<!-- End of Main Content -->

<!-- Footer -->
<?php include('inc/footer.php'); ?>
<!-- End of Footer -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<div class="context-menu" style="display: none; position: absolute; z-index: 1000;">
    <ul class="list-group">
        <li class="list-group-item" data-action="ready">Mark as Ready</li>
        <li class="list-group-item" data-action="received">Mark as Received</li>
        <li class="list-group-item" data-action="cancel">Cancel Reservation</li>
    </ul>
</div>

<!-- Add these before the closing </head> tag -->
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        const table = $('#dataTable').DataTable({
            "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
            "responsive": true,
            "scrollY": "60vh",
            "scrollCollapse": true,
            "fixedHeader": true,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search..."
            },
            "initComplete": function() {
                $('#dataTable_filter input').addClass('form-control form-control-sm');
                $('#dataTable_filter').addClass('d-flex align-items-center');
                $('#dataTable_filter label').append('<i class="fas fa-search ml-2"></i>');
                $('#dataTable_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
            }
        });

        // Add window resize handler
        $(window).on('resize', function() {
            table.columns.adjust().draw();
        });

        const contextMenu = $('.context-menu');
        let $selectedRow = null;

        // Right-click handler for table rows
        $('#dataTable tbody').on('contextmenu', 'tr', function(e) {
            e.preventDefault();
            
            // Ensure the row has a reservation ID
            const reservationId = $(this).data('reservation-id');
            if (!reservationId) return;

            $selectedRow = $(this);
            const currentStatus = $selectedRow.find('td:eq(4) span').text().trim();

            // Don't show menu for completed states
            if (currentStatus === 'Cancelled' || currentStatus === 'Received') {
                return;
            }

            // Show/hide menu items based on status
            $(".context-menu .list-group-item").hide(); // Hide all items by default

            if (currentStatus === 'Pending') {
                // For pending items, show only Ready and Cancel options
                $(".context-menu .list-group-item[data-action='ready']").show();
                $(".context-menu .list-group-item[data-action='cancel']").show();
            } else if (currentStatus === 'Ready') {
                // For ready items, show only Received and Cancel options
                $(".context-menu .list-group-item[data-action='received']").show();
                $(".context-menu .list-group-item[data-action='cancel']").show();
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

            const reservationId = $selectedRow.data('reservation-id');
            const action = $(this).data('action');
            let url = '';
            let confirmConfig = {};

            switch(action) {
                case 'ready':
                    url = 'reservation_ready.php';
                    confirmConfig = {
                        title: 'Mark as Ready?',
                        text: 'Are you sure you want to mark this reservation as ready?',
                        icon: 'question',
                        confirmButtonText: 'Yes, Mark as Ready',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#6c757d'
                    };
                    break;
                case 'received':
                    url = 'reservation_recieved.php';
                    confirmConfig = {
                        title: 'Mark as Received?',
                        text: 'Are you sure you want to mark this reservation as received? This action cannot be undone.',
                        icon: 'warning',
                        confirmButtonText: 'Yes, Mark as Received',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d'
                    };
                    break;
                case 'cancel':
                    url = 'reservation_cancelled.php';
                    confirmConfig = {
                        title: 'Cancel Reservation?',
                        text: 'Are you sure you want to cancel this reservation?',
                        icon: 'warning',
                        confirmButtonText: 'Yes, Cancel It',
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d'
                    };
                    break;
            }

            if (url && confirmConfig) {
                Swal.fire({
                    ...confirmConfig,
                    showCancelButton: true,
                    cancelButtonText: 'No, Keep It',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return fetch(`${url}?id=${reservationId}`)
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
                            text: 'The reservation status has been updated.',
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
                tr[data-reservation-id] {
                    cursor: context-menu;
                }
            `)
            .appendTo('head');
    });
</script>
</body>
</html>