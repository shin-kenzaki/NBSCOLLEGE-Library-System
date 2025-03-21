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
        .book-entry {
            padding: 10px 0;
        }
        .book-entry p {
            margin: 5px 0;
        }
        .title-line {
            font-weight: bold;
            color: #4e73df;
        }
        .contributors-line {
            font-style: italic;
        }
        .type-line, .publication-line, .availability-line {
            color: #666;
            font-size: 0.9em;
        }
        .action-buttons {
            margin-top: 10px;
        }
        .action-buttons button {
            margin-right: 5px;
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
                        <table class="table" id="dataTable">
                            <thead style="display: none;">
                                <tr>
                                    <th>Book Details</th>
                                    <th>Image</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT 
                                    b1.title,
                                    b1.content_type,
                                    b1.media_type,
                                    MIN(b1.shelf_location) as shelf_location,
                                    b1.front_image,
                                    p.publisher,
                                    pub.publish_date as publication_year,
                                    (SELECT COUNT(*) 
                                     FROM books b2 
                                     WHERE b2.title = b1.title) as total_copies,
                                    (SELECT COUNT(*) 
                                     FROM books b3 
                                     WHERE b3.title = b1.title 
                                     AND b3.status = 'Available') as available_copies,
                                    GROUP_CONCAT(DISTINCT 
                                        CONCAT(
                                            c.role, ':', 
                                            w.lastname, ', ', 
                                            w.firstname
                                        ) 
                                        ORDER BY 
                                            FIELD(c.role, 'Author', 'Co-Author', 'Editor'),
                                            w.lastname, 
                                            w.firstname 
                                        SEPARATOR '|'
                                    ) as contributors
                                FROM (
                                    SELECT DISTINCT title, MIN(id) as id
                                    FROM books
                                    GROUP BY title
                                ) AS unique_books
                                JOIN books b1 ON b1.id = unique_books.id
                                LEFT JOIN contributors c ON b1.id = c.book_id
                                LEFT JOIN writers w ON c.writer_id = w.id
                                LEFT JOIN publications pub ON b1.id = pub.book_id
                                LEFT JOIN publishers p ON pub.publisher_id = p.id
                                GROUP BY b1.title
                                ORDER BY b1.title";

                                $result = $conn->query($query);

                                while ($row = $result->fetch_assoc()) {
                                    // Process contributors
                                    $contributors = [];
                                    $contributorsByRole = [
                                        'Author' => [],
                                        'Co-Author' => [],
                                        'Editor' => []
                                    ];

                                    if ($row['contributors']) {
                                        foreach (explode('|', $row['contributors']) as $contributor) {
                                            list($role, $name) = explode(':', $contributor);
                                            $nameParts = explode(', ', $name);
                                            $lastName = $nameParts[0];
                                            $firstNameParts = explode(' ', $nameParts[1]);
                                            $firstName = $firstNameParts[0];
                                            $middleInit = isset($firstNameParts[1]) ? $firstNameParts[1][0] . '.' : '';
                                            $formattedName = $firstName . ' ' . $middleInit . ' ' . $lastName;
                                            $formattedByLineName = $lastName . ', ' . $firstName . ' ' . $middleInit;
                                            $contributorsByRole[$role][] = $formattedName;
                                            $contributors[] = $formattedByLineName . ' (' . $role . ')';
                                        }
                                    }

                                    // Format first line (Title and Contributors)
                                    $allContributors = array_merge(
                                        $contributorsByRole['Author'],
                                        $contributorsByRole['Co-Author'],
                                        $contributorsByRole['Editor']
                                    );
                                    if (count($allContributors) > 1) {
                                        $lastContributor = array_pop($allContributors);
                                        $firstLineContributors = implode(', ', $allContributors) . ' and ' . $lastContributor;
                                    } else {
                                        $firstLineContributors = implode(', ', $allContributors);
                                    }
                                    $firstLine = htmlspecialchars($row['title']) . ' / ' . $firstLineContributors;

                                    // Format second line (By Contributors)
                                    $byLine = 'By ' . implode('; ', $contributors);

                                    echo "<tr class='clickable-row' data-href='view_book.php?title=" . urlencode($row['title']) . "'>
                                        <td style='width: 80%'>
                                            <div class='book-entry'>
                                                <p class='title-line'>" . $firstLine . "</p>
                                                <p class='contributors-line'>" . $byLine . "</p>
                                                <p class='type-line'>Content type: " . htmlspecialchars($row['content_type']) . 
                                                " | Media type: " . htmlspecialchars($row['media_type']) . 
                                                " | Shelf Location: " . htmlspecialchars($row['shelf_location']) . "</p>
                                                <p class='publication-line'>Publication Details: " . 
                                                htmlspecialchars($row['publisher']) . ", " . 
                                                htmlspecialchars($row['publication_year']) . "</p>
                                                <p class='availability-line'>Availability: " . 
                                                htmlspecialchars($row['available_copies']) . " out of " . 
                                                htmlspecialchars($row['total_copies']) . " copies available</p>
                                            </div>
                                            <div class='action-buttons'>
                                                <button class='btn btn-primary btn-sm add-to-cart' data-title='" . htmlspecialchars($row['title']) . "'>
                                                    <i class='fas fa-cart-plus'></i> Add to Cart
                                                </button>
                                                <button class='btn btn-success btn-sm borrow-book' data-title='" . htmlspecialchars($row['title']) . "'>
                                                    <i class='fas fa-book'></i> Borrow Book
                                                </button>
                                            </div>
                                        </td>
                                        <td style='width: 20%; vertical-align: middle; text-align: center;'>
                                            <img src='" . (empty($row['front_image']) ? '../Admin/inc/upload/default-book.jpg' : $row['front_image']) . "' 
                                                 alt='Book Cover' 
                                                 style='max-width: 120px; max-height: 180px; object-fit: contain;'>
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
            "order": [[0, 'asc']],
            "responsive": true,
            "columns": [
                { "orderable": true },
                { "orderable": false }
            ],
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
                            
                            // Check if the message is about reaching the maximum limit
                            if (res.message && res.message.includes('maximum limit of 3 books')) {
                                Swal.fire({
                                    title: 'Limit Reached!', 
                                    text: 'You can only have 3 books borrowed or reserved at once. You may add more books to your cart, but will be limited when checking out.',
                                    icon: 'warning'
                                });
                            } else {
                                Swal.fire('Added!', res.message, res.success ? 'success' : 'error').then(() => {
                                    if (res.success) {
                                        location.reload();
                                    }
                                });
                            }
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
                            
                            // Check if the message is about reaching the maximum limit
                            if (res.message && res.message.includes('maximum limit of 3 books')) {
                                Swal.fire({
                                    title: 'Limit Reached!', 
                                    text: 'You can only have 3 books borrowed or reserved at once. You may add more books to your cart, but will be limited when checking out.',
                                    icon: 'warning'
                                });
                            } else {
                                Swal.fire('Reserved!', res.message, res.success ? 'success' : 'error').then(() => {
                                    if (res.success) {
                                        location.reload();
                                    }
                                });
                            }
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
