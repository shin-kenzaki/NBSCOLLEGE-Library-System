<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header("Location: index.php");
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
        .selected-row {
            background-color: #f0f8ff; /* Light blue background for selected rows */
        }

        #select-all,
        .select-item {
            margin: 0;
            vertical-align: middle;
            position: relative;
            cursor: pointer;
        }

        .table-responsive table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table-responsive table td,
        .table-responsive table th {
            vertical-align: middle !important;
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
                        <button class="btn btn-danger btn-sm mr-2" id="bulk-remove" disabled>
                            Remove Selected (<span id="selectedCount">0</span>)
                        </button>
                        <button class="btn btn-primary btn-sm" id="checkout">Checkout</button>
                    </div>
                </div>
                <div class="card-body px-0"> <!-- Remove padding for full-width scroll -->
                    <div class="table-responsive px-3"> <!-- Add padding inside scroll container -->
                        <table class="table table-bordered" id="dataTable" width="30px" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 10%"><input type="checkbox" id="select-all"></th>
                                    <th class="text-center" style="width: 40%">Title</th>
                                    <th class="text-center" style="width: 30%">Author</th>
                                    <th class="text-center" style="width: 20%">Date Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while ($row = $result->fetch_assoc()) {
                                    // Format the date to Month Abbrev, date, year and 12hr format time
                                    $formattedDate = date('M j, Y h:i A', strtotime($row['date']));

                                    echo "<tr>
                                        <td class=\"text-center\" style=\"width: 10%\"><input type='checkbox' class='select-item' data-title='" . htmlspecialchars($row['title']) . "'></td>
                                        <td style=\"width: 40%\">" . htmlspecialchars($row['title']) . "</td>
                                        <td style=\"width: 30%\">" . htmlspecialchars($row['author'] ?? 'N/A') . "</td>
                                        <td class=\"text-center\" style=\"width: 20%\">" . htmlspecialchars($formattedDate) . "</td>
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
        // Add CSS to hide sorting icons for checkbox column
        $('<style>')
            .text(`
                #dataTable thead th:first-child.sorting::before,
                #dataTable thead th:first-child.sorting::after,
                #dataTable thead th:first-child.sorting_asc::before,
                #dataTable thead th:first-child.sorting_asc::after,
                #dataTable thead th:first-child.sorting_desc::before,
                #dataTable thead th:first-child.sorting_desc::after {
                    display: none !important;
                }
            `)
            .appendTo('head');

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
            "columnDefs": [
                { "orderable": false, "targets": 0, "searchable": false } // Disable sorting & searching for checkbox
            ],
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

        // Function to update the selected item count and button state
        function updateSelectedItemCount() {
            var selectedCount = $('.select-item:checked').length;
            $('#selectedCount').text(selectedCount);

            // Enable or disable the bulk remove button based on selections
            $('#bulk-remove').prop('disabled', selectedCount === 0);
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

        // Add bulk remove functionality
        $('#bulk-remove').on('click', function() {
            var titles = [];
            $('.select-item:checked').each(function() {
                titles.push($(this).data('title'));
            });

            if (titles.length > 0) {
                var bookList = '<ul>' + titles.map(title => '<li>' + title + '</li>').join('') + '</ul>';

                Swal.fire({
                    title: 'Remove from Cart',
                    html: 'Are you sure you want to remove these items from your cart?<br>' + bookList,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, remove them!',
                    cancelButtonText: 'No, keep them',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d'
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

                                // Check if the message is about overdue books
                                if (!res.success && res.message && res.message.includes('overdue book(s)')) {
                                    // Use error icon for overdue books warning
                                    Swal.fire('Cannot Checkout', res.message, 'error');
                                } else {
                                    // Regular success/error styling
                                    Swal.fire('Checked out!', res.message, res.success ? 'success' : 'error').then(() => {
                                        if (res.success) {
                                            location.reload();
                                        }
                                    });
                                }
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
