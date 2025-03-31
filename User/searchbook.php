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
        .bulk-actions {
            display: inline-flex;
            align-items: center;
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
                    <h6 class="m-0 font-weight-bold text-primary">Search Book</h6>
                    <div class="bulk-actions">
                        <button class="btn btn-primary btn-sm mr-2" id="bulk-cart">
                            Add to Cart (<span id="selectedCount">0</span>)
                        </button>
                        <button class="btn btn-success btn-sm" id="bulk-reserve">
                            Reserve Selected (<span id="selectedCount2">0</span>)
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Books Table -->
                    <div class="table-responsive">
                        <table class="table" id="dataTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%">Select</th>
                                    <th style="width: 75%">Book Details</th>
                                    <th style="width: 20%">Image</th>
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
                                        <td style='width: 5%; text-align: center;'>
                                            <input type='checkbox' class='book-select' data-title='" . htmlspecialchars($row['title']) . "'>
                                        </td>
                                        <td style='width: 75%'>
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
        "order": [[1, 'asc']], // Sort by book details column by default
        "responsive": false,
        "scrollX": true,
        "autoWidth": false,
        "columns": [
            { "orderable": false, "width": "5%" },  // Select checkbox column
            { "orderable": true, "width": "75%" },  // Book details column
            { "orderable": false, "width": "20%" }  // Image column
        ],
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
        }
    });

    // Add click event listener to table rows
    $('#dataTable tbody').on('click', 'tr.clickable-row', function(event) {
        // Prevent navigation if the click is on a checkbox or its parent cell
        if ($(event.target).is('input[type="checkbox"], td:first-child')) {
            return;
        }
        window.location.href = $(this).data('href');
    });

    // Function to add book to cart
    function addToCart(title) {
        if (!isLoggedIn) {
            Swal.fire({
                title: 'Please Login',
                text: 'You need to be logged in to add books to the cart.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

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

                        if (res.success) {
                            Swal.fire('Added!', `"${title}" added to cart.`, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            // Show error with specific message
                            Swal.fire({
                                title: 'Failed!',
                                html: `<p>${res.message}</p>`,
                                icon: 'error',
                                confirmButtonText: 'OK'
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
        if (!isLoggedIn) {
            Swal.fire({
                title: 'Please Login',
                text: 'You need to be logged in to reserve books.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to reserve "' + title + '"?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, reserve it!',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'reserve_book.php',
                    type: 'POST',
                    data: { title: title },
                    success: function(response) {
                        var res = JSON.parse(response);

                        if (res.success) {
                            Swal.fire('Reserved!', `"${title}" reserved successfully.`, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            // Show error with specific message
                            Swal.fire({
                                title: 'Failed!',
                                html: `<p>${res.message}</p>`,
                                icon: 'error',
                                confirmButtonText: 'OK'
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

    // Handle checkbox clicks without triggering row click
    $('.book-select').on('click', function(e) {
        e.stopPropagation();
        updateSelectedCount();
    });

    // Update the selected count and toggle bulk actions visibility
    function updateSelectedCount() {
        const selectedCount = $('.book-select:checked').length;
        $('#selectedCount, #selectedCount2').text(selectedCount);
        $('.bulk-actions').toggleClass('visible', selectedCount > 0);
    }

    // Bulk add to cart - enhanced version
    $('#bulk-cart').on('click', function() {
        const titles = [];
        $('.book-select:checked').each(function() {
            titles.push($(this).data('title'));
        });

        if (titles.length === 0) return;

        if (!isLoggedIn) {
            Swal.fire({
                title: 'Please Login',
                text: 'You need to be logged in to add books to the cart.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        Swal.fire({
            title: 'Add to Cart',
            html: 'Add these ' + titles.length + ' book(s) to cart?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, add them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                let successes = [];
                let failures = [];
                let processed = 0;

                titles.forEach(title => {
                    $.ajax({
                        url: 'add_to_cart.php',
                        type: 'POST',
                        data: { title: title },
                        success: function(response) {
                            processed++;
                            var res = JSON.parse(response);

                            if (res.success) {
                                successes.push(title);
                            } else {
                                failures.push({title: title, reason: res.message});
                            }

                            // When all requests are processed, show summary
                            if (processed === titles.length) {
                                showResultSummary(successes, failures, 'cart');
                            }
                        },
                        error: function() {
                            processed++;
                            failures.push({title: title, reason: "Network error occurred"});

                            if (processed === titles.length) {
                                showResultSummary(successes, failures, 'cart');
                            }
                        }
                    });
                });
            }
        });
    });

    // Bulk reserve - updated version
    $('#bulk-reserve').on('click', function() {
        const titles = [];
        $('.book-select:checked').each(function() {
            titles.push($(this).data('title'));
        });

        if (titles.length === 0) return;

        if (!isLoggedIn) {
            Swal.fire({
                title: 'Please Login',
                text: 'You need to be logged in to reserve books.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        Swal.fire({
            title: 'Reserve Books',
            html: 'Reserve these ' + titles.length + ' book(s)?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, reserve them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                let successes = [];
                let failures = [];
                let processed = 0;

                titles.forEach(title => {
                    $.ajax({
                        url: 'reserve_book.php',
                        type: 'POST',
                        data: { title: title },
                        success: function(response) {
                            processed++;
                            var res = JSON.parse(response);

                            if (res.success) {
                                successes.push(title);
                            } else {
                                failures.push({title: title, reason: res.message});
                            }

                            // When all requests are processed, show summary
                            if (processed === titles.length) {
                                showResultSummary(successes, failures, 'reserve');
                            }
                        },
                        error: function() {
                            processed++;
                            failures.push({title: title, reason: "Network error occurred"});

                            if (processed === titles.length) {
                                showResultSummary(successes, failures, 'reserve');
                            }
                        }
                    });
                });
            }
        });
    });

    // Update showResultSummary function to handle new error messages
    function showResultSummary(successes, failures, action) {
        let actionText = action === 'cart' ? 'added to cart' : 'reserved';
        let successText = '';
        let failureText = '';

        // Group failures by reason
        const reasonGroups = {};
        failures.forEach(failure => {
            // Clean the reason message once before grouping
            let cleanReason = failure.reason
                .replace(/^You already have ".*" in your cart\./, 'Already in cart')
                .replace(/^You already have a reservation for this book: .*/, 'Already reserved')
                .replace(/^You already have an active reservation for: .*/, 'Already reserved')
                .replace(/^You already have this book borrowed or reserved: .*/, 'Already borrowed/reserved')
                .replace(/^You have reached the maximum limit of 3 books.*/, 'Maximum limit reached (3 books)');

            if (!reasonGroups[cleanReason]) {
                reasonGroups[cleanReason] = [];
            }
            reasonGroups[cleanReason].push(failure.title);
        });

        // Generate success message
        if (successes.length > 0) {
            successText = `<p><strong>${successes.length} book(s) successfully ${actionText}:</strong></p>
                          <ul>${successes.map(title => `<li>${title}</li>`).join('')}</ul>`;
        }

        // Generate failure messages by reason
        if (failures.length > 0) {
            failureText = `<p><strong>${failures.length} book(s) could not be ${actionText}:</strong></p>`;

            for (const reason in reasonGroups) {
                failureText += `<p class="text-danger">${reason}</p>
                              <ul>${reasonGroups[reason].map(title => `<li>${title}</li>`).join('')}</ul>`;
            }
        }

        // Show the summary
        if (successes.length > 0) {
            Swal.fire({
                title: successes.length > 0 ? 'Operation Completed' : 'Operation Failed',
                html: successText + failureText,
                icon: failures.length === 0 ? 'success' : 'info',
                confirmButtonText: 'OK'
            }).then(() => {
                if (successes.length > 0) {
                    location.reload();
                }
            });
        } else {
            Swal.fire({
                title: 'Operation Failed',
                html: failureText,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    }

    // Handle checkbox clicks and ensure the checkbox is toggled when clicking the cell
    $('#dataTable tbody').on('click', 'td:first-child', function(event) {
        const checkbox = $(this).find('input[type="checkbox"]');
        checkbox.prop('checked', !checkbox.prop('checked'));
        updateSelectedCount();
        event.stopPropagation(); // Prevent triggering row navigation
    });
});
</script>
</body>
</html>
