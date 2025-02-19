<?php
session_start();
include '../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Book</title>
    <style>
        .book-details-link {
            color: #4e73df;
            text-decoration: none;
        }
        .book-details-link:hover {
            text-decoration: underline;
        }
        .dataTables_filter input {
            width: 400px; 
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-bottom: 1rem; 
        }
        .clickable-row {
            cursor: pointer;
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
                    <h6 class="m-0 font-weight-bold text-primary">Search Book</h6>
                </div>
                <div class="card-body">
                    <!-- Books Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Call Number</th>
                                    <th>Total Copies</th>
                                    <th>Total Available</th>
                                    <th>Total Borrowed</th>
                                    <th>Actions</th> <!-- Added Actions column -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Modified query to use only the books table
                                $query = "SELECT b.title, 
                                                (SELECT call_number FROM books ORDER BY id LIMIT 1) as call_number,
                                                COUNT(*) as total_copies,
                                                SUM(CASE WHEN b.status = 'Available' THEN 1 ELSE 0 END) as total_in_shelf,
                                                SUM(CASE WHEN b.status = 'Borrowed' THEN 1 ELSE 0 END) as total_borrowed,
                                                (SELECT CONCAT(w.firstname, ' ', w.lastname) 
                                                 FROM contributors c 
                                                 JOIN writers w ON c.writer_id = w.id 
                                                 WHERE c.book_id = b.id AND c.role = 'Author' 
                                                 ORDER BY c.id LIMIT 1) as author
                                         FROM books b
                                         GROUP BY b.title
                                         ORDER BY b.title";

                                $result = $conn->query($query);

                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr class='clickable-row' data-href='view_book.php?title=" . urlencode($row['title']) . "'>
                                        <td>" . htmlspecialchars($row['title']) . "</td>
                                        <td>" . htmlspecialchars($row['author'] ?? 'N/A') . "</td>
                                        <td>" . htmlspecialchars($row['call_number']) . "</td>
                                        <td>" . htmlspecialchars($row['total_copies']) . "</td>
                                        <td>" . htmlspecialchars($row['total_in_shelf']) . "</td>
                                        <td>" . htmlspecialchars($row['total_borrowed']) . "</td>
                                        <td>
                                            <button class='btn btn-primary btn-sm add-to-cart' data-title='" . htmlspecialchars($row['title']) . "' title='Add to Cart'>
                                                <i class='fas fa-cart-plus'></i> <!-- Add to Cart icon -->
                                            </button>
                                            <button class='btn btn-success btn-sm borrow-book' data-title='" . htmlspecialchars($row['title']) . "' title='Borrow'>
                                                <i class='fas fa-book'></i> <!-- Borrow icon -->
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
            "order": [[0, 'asc']], // Sort by title by default
            "responsive": true,
            "initComplete": function() {
                $('#dataTable_filter input').addClass('form-control form-control-sm');
            }
        });

        // Add click event listener to table rows
        $('#dataTable tbody').on('click', 'tr.clickable-row', function() {
            window.location.href = $(this).data('href');
        });

        // Function to add book to cart
        function addToCart(title) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to add "' + title + '" to the cart?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, add it!',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'add_to_cart.php',
                        type: 'POST',
                        data: { title: title },
                        success: function(response) {
                            var res = JSON.parse(response);
                            Swal.fire('Added!', res.message, 'success').then(() => {
                                if (res.success) {
                                    location.reload();
                                }
                            });
                        },
                        error: function() {
                            Swal.fire('Failed!', 'Failed to add "' + title + '" to cart.', 'error');
                        }
                    });
                }
            });
        }

        // Function to borrow book
        function borrowBook(title) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to borrow "' + title + '"?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, borrow it!',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'reserve_book.php',
                        type: 'POST',
                        data: { title: title },
                        success: function(response) {
                            var res = JSON.parse(response);
                            Swal.fire('Reserved!', res.message, 'success').then(() => {
                                if (res.success) {
                                    location.reload();
                                }
                            });
                        },
                        error: function() {
                            Swal.fire('Failed!', 'Failed to reserve "' + title + '".', 'error');
                        }
                    });
                }
            });
        }

        // Add click event listener to 'Add to Cart' buttons
        $('.add-to-cart').on('click', function(event) {
            event.stopPropagation();
            var title = $(this).data('title');
            addToCart(title);
        });

        // Add click event listener to 'Borrow' buttons
        $('.borrow-book').on('click', function(event) {
            event.stopPropagation();
            var title = $(this).data('title');
            borrowBook(title);
        });
    });
    </script>
</body>
</html>
