<?php
session_start();
include('inc/header.php');

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include('../db.php');
$query = "SELECT 
            b.id as borrow_id,
            b.issue_date,
            b.report_date,
            b.replacement_date,
            bk.title as book_title,
            bk.accession,
            CONCAT(u.firstname, ' ', u.lastname) as borrower_name
          FROM borrowings b
          JOIN books bk ON b.book_id = bk.id
          JOIN users u ON b.user_id = u.id
          WHERE b.status = 'Damaged'
          ORDER BY b.report_date DESC";
$result = $conn->query($query);
?>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    .table td, .table th {
        white-space: nowrap;
    }
</style>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Damaged Books</h1>
        </div>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Damaged Book Records</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center">Book Title</th>
                                <th class="text-center">Accession No.</th>
                                <th class="text-center">Borrower</th>
                                <th class="text-center">Borrow Date</th>
                                <th class="text-center">Report Date</th>
                                <th class="text-center">Replaced Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr data-borrow-id="<?php echo $row['borrow_id']; ?>" 
                                data-book-title="<?php echo htmlspecialchars($row['book_title']); ?>">
                                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['accession']); ?></td>
                                <td><?php echo htmlspecialchars($row['borrower_name']); ?></td>
                                <td class="text-center"><?php echo date('Y-m-d', strtotime($row['issue_date'])); ?></td>
                                <td class="text-center"><?php echo date('Y-m-d', strtotime($row['report_date'])); ?></td>
                                <td class="text-center"><?php echo $row['replacement_date'] ? date('Y-m-d', strtotime($row['replacement_date'])) : '-'; ?></td>
                                <td class="text-center">
                                    <?php 
                                        if ($row['replacement_date']) {
                                            echo '<span class="badge badge-success">Replaced</span>';
                                        } else {
                                            echo '<span class="badge badge-warning">Pending Replacement</span>';
                                        }
                                    ?>
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

<!-- Add context menu before footer -->
<div class="context-menu" style="display: none; position: absolute; z-index: 1000;">
    <ul class="list-group">
        <li class="list-group-item" data-action="replace">Mark as Replaced</li>
    </ul>
</div>

<?php include('inc/footer.php'); ?>

<!-- Add SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    const table = $('#dataTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "pagingType": "simple_numbers",
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
        "responsive": false,
        "scrollY": "60vh",
        "scrollCollapse": true,
        "fixedHeader": true,
        "order": [[4, "desc"]], // Sort by damage date by default
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

    // Add window resize handler
    $(window).on('resize', function() {
        table.columns.adjust().draw();
    });

    // Store references
    const contextMenu = $('.context-menu');
    let $selectedRow = null;

    // Right-click handler for table rows
    $('#dataTable tbody').on('contextmenu', 'tr', function(e) {
        e.preventDefault();
        $selectedRow = $(this);
        
        // Only show context menu if book hasn't been replaced
        const replacementDate = $selectedRow.find('td:eq(5)').text().trim();
        if (replacementDate === '-') {
            contextMenu.css({
                top: e.pageY + "px",
                left: e.pageX + "px",
                display: "block"
            });
        }
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

        const borrowId = $selectedRow.data('borrow-id');
        const bookTitle = $selectedRow.data('book-title');
        
        // First dialog to input ISBN
        Swal.fire({
            title: 'Enter Replacement Book ISBN',
            html: `Please verify the replacement book by entering its ISBN:<br><br>
                  <b>Book:</b> ${bookTitle}`,
            input: 'text',
            inputPlaceholder: 'Enter ISBN',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Verify',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            allowOutsideClick: false,
            allowEscapeKey: false,
            inputValidator: (value) => {
                if (!value) {
                    return 'Please enter the ISBN'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Second confirmation dialog
                Swal.fire({
                    title: 'Mark as Replaced?',
                    html: `Are you sure this damaged book has been replaced?<br><br>
                          <b>Book:</b> ${bookTitle}<br>
                          <b>ISBN:</b> ${result.value}`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Mark as Replaced',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return fetch(`book_replaced.php?id=${borrowId}&type=damaged&isbn=${result.value}`)
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
                            text: 'The book has been marked as replaced.',
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                });
            }
        });
        
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
