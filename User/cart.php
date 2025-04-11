<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Updated query to include ISBN, Series, Volume, Part, and Edition
$query = "SELECT 
    b.id AS book_id, 
    b.title, 
    b.ISBN, 
    b.series, 
    b.volume, 
    b.part, 
    b.edition, 
    b.shelf_location,
    c.id AS cart_id,
    c.date,
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
        .checkbox-cell {
            cursor: pointer;
            text-align: center;
            vertical-align: middle;
        }
        .checkbox-cell:hover {
            background-color: rgba(0, 123, 255, 0.1); /* Light blue hover effect */
        }
        .table-responsive table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Allow book details column to wrap */
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
        .empty-cart-message {
            text-align: center;
            padding: 20px;
            font-size: 1.1em;
            color: #666;
        }
        .table-responsive table td,
        .table-responsive table th {
            vertical-align: middle !important;
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
            max-width: 60px !important;
        }
        .table-responsive table th.details-column,
        .table-responsive table td.details-column {
            width: 52% !important;
        }
        .table-responsive table th.author-column,
        .table-responsive table td.author-column {
            width: 15% !important;
        }
        .table-responsive table th.date-column,
        .table-responsive table td.date-column {
            width: 20% !important;
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
                    <h6 class="m-0 font-weight-bold text-primary">Your Cart</h6>
                    <div class="d-flex">
                        <a href="searchbook.php" class="btn btn-primary btn-sm mr-2">
                            <i class="fas fa-search"></i> Search Books
                        </a>
                        <button class="btn btn-danger btn-sm mr-2" id="bulk-remove" disabled>
                            Remove Selected (<span id="selectedCount">0</span>)
                        </button>
                        <button class="btn btn-success btn-sm" id="checkout">
                            <i class="fas fa-check-circle"></i> Checkout
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
                                        <input type="checkbox" id="select-all" title="Select/Deselect All">
                                    </th>
                                    <th class="text-center id-column" style="width: 8%;">Cart ID</th>
                                    <th class="text-center details-column" style="width: 52%;">Book Details</th>
                                    <th class="text-center author-column" style="width: 15%;">Author</th>
                                    <th class="text-center date-column" style="width: 20%;">Date Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): 
                                    // Format the date to Month Abbrev, date, year and 12hr format time
                                    $formattedDate = date('M j, Y', strtotime($row['date']));
                                    $formattedTime = date('h:i A', strtotime($row['date']));
                                    
                                    // Initialize detailsArray first
                                    $detailsArray = [];
                                    if (!empty($row['edition'])) $detailsArray[] = 'Edition: ' . htmlspecialchars($row['edition']);
                                    if (!empty($row['series'])) $detailsArray[] = 'Series: ' . htmlspecialchars($row['series']);
                                    if (!empty($row['volume'])) $detailsArray[] = 'Volume: ' . htmlspecialchars($row['volume']);
                                    if (!empty($row['part'])) $detailsArray[] = 'Part: ' . htmlspecialchars($row['part']);
                                    if (!empty($row['ISBN'])) $detailsArray[] = 'ISBN: ' . htmlspecialchars($row['ISBN']);
                                    
                                    $additionalDetails = !empty($detailsArray) ? implode(' | ', $detailsArray) : '';
                                ?>
                                    <tr>
                                        <td class="text-center checkbox-cell check-column">
                                            <input type="checkbox" class="select-item" 
                                                data-book-id="<?php echo htmlspecialchars($row['book_id']); ?>" 
                                                data-title="<?php echo htmlspecialchars($row['title']); ?>">
                                        </td>
                                        <td class="text-center id-column"><?php echo htmlspecialchars($row['cart_id']); ?></td>
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
                                        <td class="author-column"><?php echo htmlspecialchars($row['author'] ?? 'N/A'); ?></td>
                                        <td class="text-center date-column">
                                            <?php echo $formattedDate; ?><br>
                                            <small><?php echo $formattedTime; ?></small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div class="empty-cart-message">
                            <div class="alert alert-info">
                                <i class="fas fa-shopping-cart fa-lg mr-2"></i>
                                Your cart is empty.
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
        <?php if ($result && $result->num_rows > 0): ?>
        $('#dataTable').DataTable({
            "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search within results...",
                "emptyTable": "Your cart is empty",
                "zeroRecords": "No matching books found"
            },
            "pageLength": 10,
            "order": [[4, 'desc']], // Sort by date added by default (newest first)
            "columnDefs": [
                { "orderable": false, "targets": 0 }, // Disable sorting for checkbox column
                { "width": "5%", "className": "check-column", "targets": 0 },
                { "width": "8%", "className": "id-column", "targets": 1 },
                { "width": "52%", "className": "details-column", "targets": 2 },
                { "width": "15%", "className": "author-column", "targets": 3 },
                { "width": "20%", "className": "date-column", "targets": 4 }
            ],
            "responsive": true,
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

        // Handle individual checkboxes
        $(document).on('change', '.select-item', function() {
            const totalCheckable = $('.select-item').length;
            const totalChecked = $('.select-item:checked').length;
            
            $('#select-all').prop({
                'checked': totalChecked > 0 && totalChecked === totalCheckable,
                'indeterminate': totalChecked > 0 && totalChecked < totalCheckable
            });
            $(this).closest('tr').toggleClass('selected-row', this.checked);
            updateSelectedItemCount();
        });

        // Enable row selection by clicking on the checkbox cell
        $('.checkbox-cell').on('click', function(e) {
            if (e.target.tagName !== 'INPUT') { // Prevent double toggling when clicking directly on the checkbox
                const checkbox = $(this).find('.select-item');
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            }
        });

        // Add bulk remove functionality
        $('#bulk-remove').on('click', function() {
            var bookIds = [];
            $('.select-item:checked').each(function() {
                bookIds.push($(this).data('book-id')); // Use data-book-id to get the book ID
            });

            if (bookIds.length > 0) {
                Swal.fire({
                    title: 'Remove from Cart',
                    text: `Are you sure you want to remove ${bookIds.length} selected item(s) from your cart?`,
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
                            data: { book_ids: bookIds },
                            success: function(response) {
                                var res = JSON.parse(response);
                                Swal.fire({
                                    title: res.success ? 'Removed!' : 'Failed!',
                                    text: res.message,
                                    icon: res.success ? 'success' : 'error'
                                }).then(() => {
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
            }
        });

        // Add checkout functionality
        $('#checkout').on('click', function() {
            var selectedItems = [];
            $('.select-item:checked').each(function() {
                selectedItems.push({
                    cartId: $(this).closest('tr').find('td:eq(1)').text().trim(),
                    title: $(this).data('title')
                });
            });
            
            var bookCount = selectedItems.length;
            var message;
            
            if (bookCount > 0) {
                message = `You've selected ${bookCount} book(s) to check out. Do you want to proceed?`;
                if (bookCount > 0) {
                    message += '<br><br><ul class="text-left">' + selectedItems.map(item => `<li>${item.title}</li>`).join('') + '</ul>';
                }
            } else {
                message = 'No books selected. All items in your cart will be checked out. Continue?';
            }

            Swal.fire({
                title: 'Checkout',
                html: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, checkout!',
                cancelButtonText: 'No, cancel',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create proper data object based on selection
                    let requestData = {};
                    
                    // If specific books are selected, include their cart IDs in the request
                    if (bookCount > 0) {
                        requestData.selected_cart_ids = selectedItems.map(item => item.cartId);
                    }
                    
                    $.ajax({
                        url: 'checkout.php',
                        type: 'POST',
                        data: requestData,
                        success: function(response) {
                            try {
                                // Handle the response as JSON
                                var res = (typeof response === 'object') ? response : JSON.parse(response);
                                
                                // Check if the message is about overdue books
                                if (!res.success && res.message && res.message.includes('overdue book(s)')) {
                                    // Use error icon for overdue books warning
                                    Swal.fire('Cannot Checkout', res.message, 'error');
                                } else {
                                    // Regular success/error styling
                                    Swal.fire({
                                        title: res.success ? 'Checked Out!' : 'Checkout Failed',
                                        html: res.message, // Changed from text to html to properly render HTML tags
                                        icon: res.success ? 'success' : 'error'
                                    }).then(() => {
                                        if (res.success) {
                                            location.reload();
                                        }
                                    });
                                }
                            } catch (e) {
                                // Handle unexpected response format
                                Swal.fire('Error', 'Received invalid response from server', 'error');
                                console.error("Response parse error:", e, response);
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire('Failed!', 'Failed to complete checkout. Please try again. Error: ' + error, 'error');
                            console.error("AJAX error:", status, error);
                        }
                    });
                }
            });
        });

        // Initialize selected item count on page load
        updateSelectedItemCount();
        <?php endif; ?>
    });
    </script>
</body>
</html>
