<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT b.title, c.date, 
                 (SELECT CONCAT(w.firstname, ' ', w.lastname) 
                  FROM contributors con 
                  JOIN writers w ON con.writer_id = w.id 
                  WHERE con.book_id = b.id AND con.role = 'Author' 
                  ORDER BY con.id LIMIT 1) as author 
          FROM cart c 
          JOIN books b ON c.book_id = b.id 
          WHERE c.user_id = ? AND c.status = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cart</title>
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
        .selected-row {
            background-color: #f0f8ff; /* Light blue background for selected rows */
        }

        /* Add custom CSS for responsive table */
        .table-responsive {
            width: 100%;
            margin-bottom: 1rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Ensure minimum width for table columns */
        #dataTable th,
        #dataTable td {
            white-space: nowrap;
        }
        
        /* Specific column widths */
        #dataTable th:nth-child(1),
        #dataTable td:nth-child(1) {
            min-width: 50px; /* Checkbox column */
        }
        #dataTable th:nth-child(2),
        #dataTable td:nth-child(2) {
            min-width: 200px; /* Title column */
        }
        #dataTable th:nth-child(3),
        #dataTable td:nth-child(3) {
            min-width: 150px; /* Author column */
        }
        #dataTable th:nth-child(4),
        #dataTable td:nth-child(4) {
            min-width: 150px; /* Date Added column */
            text-align: center;
            vertical-align: middle;
        }
        #dataTable th:nth-child(5),
        #dataTable td:nth-child(5) {
            min-width: 100px; /* Actions column */
            text-align: center;
            vertical-align: middle;
        }
        
        /* Make the table stretch full width */
        #dataTable {
            width: 100% !important;
        }

        /* Center align all table cells vertically */
        #dataTable td, 
        #dataTable th {
            vertical-align: middle !important;
        }

        /* Actions column horizontal and vertical centering */
        #dataTable th:nth-child(5),
        #dataTable td:nth-child(5) {
            text-align: center;
            vertical-align: middle;
        }

        /* Center checkbox in first column */
        #dataTable th:first-child,
        #dataTable td:first-child {
            text-align: center;
            vertical-align: middle;
        }
    </style>
    <!-- Include SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
    <?php include '../user/inc/header.php'; ?>

    <!-- Main Content -->
    <div id="content" class="d-flex flex-column min-vh-100">
        <div class="container-fluid">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Cart</h6>
                    <div>
                        <span id="selectedCount">(0 items selected)</span>
                        <button class="btn btn-primary btn-sm" id="checkout">Checkout</button>
                    </div>
                </div>
                <div class="card-body px-0"> <!-- Remove padding for full-width scroll -->
                    <div class="table-responsive px-3"> <!-- Add padding inside scroll container -->
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Date Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                        <td><input type='checkbox' class='select-item' data-title='" . htmlspecialchars($row['title']) . "'></td>
                                        <td>" . htmlspecialchars($row['title']) . "</td>
                                        <td>" . htmlspecialchars($row['author'] ?? 'N/A') . "</td>
                                        <td>" . htmlspecialchars($row['date']) . "</td>
                                        <td>
                                            <button class='btn btn-danger btn-sm remove-from-cart' data-title='" . htmlspecialchars($row['title']) . "' title='Remove from Cart'>
                                                <i class='fas fa-trash'></i> <!-- Remove from Cart icon -->
                                            </button>
                                        </td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../Admin/inc/footer.php' ?>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

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
            "order": [[1, 'asc']], // Sort by title by default
            "responsive": false, // Disable DataTables responsive handling
            "scrollX": true, // Enable horizontal scrolling
            "autoWidth": false, // Disable auto-width calculation
            "initComplete": function() {
                $('#dataTable_filter input').addClass('form-control form-control-sm');
            }
        });

        // Adjust table columns on window resize
        $(window).on('resize', function () {
            $('#dataTable').DataTable().columns.adjust();
        });

        // Function to update the selected item count
        function updateSelectedItemCount() {
            var selectedCount = $('.select-item:checked').length;
            $('#selectedCount').text(`(${selectedCount} items selected)`);
        }

        // Select/Deselect all checkboxes
        $('#select-all').on('click', function() {
            var isChecked = this.checked;
            $('.select-item').prop('checked', isChecked).closest('tr').toggleClass('selected-row', isChecked);
            updateSelectedItemCount();
        });

        // Toggle row selection on individual checkbox change
        $('.select-item').on('change', function() {
            $(this).closest('tr').toggleClass('selected-row', this.checked);
            updateSelectedItemCount();
        });

        // Add click event listener to 'Remove from Cart' buttons
        $('.remove-from-cart').on('click', function(event) {
            event.stopPropagation();
            var title = $(this).data('title');
            Swal.fire({
                title: 'Are you sure?',
                text: 'You won\'t be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove it!',
                cancelButtonText: 'No, keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'remove_from_cart.php',
                        type: 'POST',
                        data: { title: title },
                        success: function(response) {
                            var res = JSON.parse(response);
                            Swal.fire('Removed!', res.message, 'success').then(() => {
                                if (res.success) {
                                    location.reload();
                                }
                            });
                        },
                        error: function() {
                            Swal.fire('Failed!', 'Failed to remove "' + title + '" from cart.', 'error');
                        }
                    });
                }
            });
        });

        // Add bulk remove functionality
        $('#bulk-remove').on('click', function() {
            var titles = [];
            $('.select-item:checked').each(function() {
                titles.push($(this).data('title'));
            });

            if (titles.length > 0) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, remove them!',
                    cancelButtonText: 'No, keep them'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'bulk_remove_from_cart.php',
                            type: 'POST',
                            data: { titles: titles },
                            success: function(response) {
                                var res = JSON.parse(response);
                                Swal.fire('Removed!', res.message, 'success').then(() => {
                                    if (res.success) {
                                        location.reload();
                                    }
                                });
                            },
                            error: function() {
                                Swal.fire('Failed!', 'Failed to remove selected items from cart.', 'error');
                            }
                        });
                    }
                });
            } else {
                Swal.fire('No items selected', 'Please select items to remove.', 'info');
            }
        });

        // Add checkout functionality
        $('#checkout').on('click', function() {
            var titles = [];
            $('.select-item:checked').each(function() {
                titles.push($(this).data('title'));
            });

            if (titles.length > 0) {
                var bookList = '<ul>' + titles.map(title => '<li>' + title + '</li>').join('') + '</ul>';
                Swal.fire({
                    title: 'Checkout',
                    html: 'The following books will be checked out:<br>' + bookList + '<br>Do you want to proceed?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, checkout!',
                    cancelButtonText: 'No, cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'checkout.php',
                            type: 'POST',
                            data: { titles: titles },
                            success: function(response) {
                                var res = JSON.parse(response);
                                Swal.fire('Checked out!', res.message, 'success').then(() => {
                                    if (res.success) {
                                        location.reload();
                                    }
                                });
                            },
                            error: function() {
                                Swal.fire('Failed!', 'Failed to checkout selected items.', 'error');
                            }
                        });
                    }
                });
            } else {
                Swal.fire('No items selected', 'Please select items to checkout.', 'info');
            }
        });

        // Initialize selected item count on page load
        updateSelectedItemCount();
    });
    </script>
</body>
</html>
