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

include('../db.php');
include('update_overdue_status.php');

// Update overdue status
updateOverdueStatus($conn);

// Fetch borrowed books data from the database
$query = "SELECT b.book_id, b.user_id, b.borrow_date, b.due_date, b.status,
          bk.title, bk.accession, 
          CONCAT(u.firstname, ' ', u.lastname) AS borrower
          FROM borrowings b
          JOIN books bk ON b.book_id = bk.id
          JOIN users u ON b.user_id = u.id
          WHERE b.status IN ('Active', 'Overdue')
          AND b.return_date IS NULL";
$result = $conn->query($query);
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Borrowed Books List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Accession No.</th>
                                <th>Book Title</th>
                                <th>Borrower's Name</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr data-book-id="<?php echo $row['book_id']; ?>" 
                                    data-book-title="<?php echo htmlspecialchars($row['title']); ?>"
                                    data-borrower="<?php echo htmlspecialchars($row['borrower']); ?>">
                                    <td><?php echo $row['accession']; ?></td>
                                    <td><?php echo $row['title']; ?></td>
                                    <td><?php echo $row['borrower']; ?></td>
                                    <td><?php echo $row['borrow_date']; ?></td>
                                    <td><?php echo $row['due_date']; ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'Overdue'): ?>
                                            <span class="badge badge-danger">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Active</span>
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

<!-- Add this right after the main table -->
<div class="context-menu" style="display: none; position: absolute; z-index: 1000;">
    <ul class="list-group">
        <li class="list-group-item" data-action="returned">Mark as Returned</li>
        <li class="list-group-item" data-action="lost">Mark as Lost</li>
        <li class="list-group-item" data-action="damaged">Mark as Damaged</li>
    </ul>
</div>

<?php include('inc/footer.php'); ?>

<!-- Add these before the closing </head> tag -->
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="inc/js/sb-admin-2.min.js"></script>
<script src="inc/assets/DataTables/datatables.min.js"></script>
<script>
    $(document).ready(function() {
        // Store references
        const contextMenu = $('.context-menu');
        let $selectedRow = null;

        // Initialize DataTable
        $('#dataTable').DataTable({
            "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
            "responsive": true,
            "scrollX": true,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search..."
            },
            "initComplete": function() {
                $('#dataTable_filter input').addClass('form-control form-control-sm');
                $('#dataTable_filter').addClass('d-flex align-items-center');
                $('#dataTable_filter label').append('<i class="fas fa-search ml-2"></i>');
                $('.dataTables_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
            }
        });

        // Right-click handler for table rows
        $('#dataTable tbody').on('contextmenu', 'tr', function(e) {
            e.preventDefault();
            
            $selectedRow = $(this);
            const status = $selectedRow.find('td:last').text().trim();

            // Don't show menu for books already marked as returned, lost, or damaged
            if (['Returned', 'Lost', 'Damaged'].includes(status)) {
                return;
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

            const bookId = $selectedRow.data('book-id');
            const bookTitle = $selectedRow.data('book-title');
            const borrower = $selectedRow.data('borrower');
            const action = $(this).data('action');
            let url = '';
            let confirmConfig = {};

            switch(action) {
                case 'returned':
                    url = 'book_returned.php';
                    confirmConfig = {
                        title: 'Return Book?',
                        html: `Are you sure this book has been returned?<br><br>
                              <b>Book:</b> ${bookTitle}<br>
                              <b>Borrower:</b> ${borrower}`,
                        icon: 'question',
                        confirmButtonText: 'Yes, Return it',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d'
                    };
                    break;
                case 'lost':
                    url = 'book_lost.php';
                    confirmConfig = {
                        title: 'Mark as Lost?',
                        html: `Are you sure you want to mark this book as lost?<br><br>
                              <b>Book:</b> ${bookTitle}<br>
                              <b>Borrower:</b> ${borrower}`,
                        icon: 'warning',
                        confirmButtonText: 'Yes, Mark as Lost',
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d'
                    };
                    break;
                case 'damaged':
                    url = 'book_damaged.php';
                    confirmConfig = {
                        title: 'Mark as Damaged?',
                        html: `Are you sure you want to mark this book as damaged?<br><br>
                              <b>Book:</b> ${bookTitle}<br>
                              <b>Borrower:</b> ${borrower}`,
                        icon: 'warning',
                        confirmButtonText: 'Yes, Mark as Damaged',
                        confirmButtonColor: '#ffc107',
                        cancelButtonColor: '#6c757d'
                    };
                    break;
            }

            if (url && confirmConfig) {
                Swal.fire({
                    ...confirmConfig,
                    showCancelButton: true,
                    cancelButtonText: 'Cancel',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return fetch(`${url}?id=${bookId}`)
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
                            text: 'The book status has been updated.',
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
            `)
            .appendTo('head');
    });
</script>
</body>
</html>
