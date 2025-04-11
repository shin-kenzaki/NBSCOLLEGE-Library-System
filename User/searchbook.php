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
        .edition-line {
            color: #555;
            font-size: 0.9em;
            font-style: italic;
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
                    <div class="d-flex align-items-center">
                        <div class="mr-3 text-info">
                            <small><i class="fas fa-info-circle"></i> You can add unavailable books to cart for future reservation</small>
                        </div>
                        <div class="bulk-actions">
                            <button class="btn btn-primary btn-sm mr-2" id="bulk-cart">
                                Add to Cart (<span id="selectedCount">0</span>)
                            </button>
                            <button class="btn btn-success btn-sm" id="bulk-reserve">
                                Reserve Selected (<span id="selectedCount2">0</span>)
                            </button>
                        </div>
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
                                // Updated query to group by title, ISBN, series, volume, part, and edition
                                $query = "SELECT
                                    b1.title,
                                    b1.content_type,
                                    b1.media_type,
                                    MIN(b1.shelf_location) as shelf_location,
                                    b1.front_image,
                                    b1.ISBN,
                                    b1.series,
                                    b1.volume,
                                    b1.part,
                                    b1.edition,
                                    p.publisher,
                                    pub.publish_date as publication_year,
                                    (SELECT COUNT(*)
                                     FROM books b2
                                     WHERE b2.title = b1.title 
                                     AND COALESCE(b2.ISBN,'') = COALESCE(b1.ISBN,'')
                                     AND COALESCE(b2.series,'') = COALESCE(b1.series,'')
                                     AND COALESCE(b2.volume,'') = COALESCE(b1.volume,'')
                                     AND COALESCE(b2.part,'') = COALESCE(b1.part,'')
                                     AND COALESCE(b2.edition,'') = COALESCE(b1.edition,'')) as total_copies,
                                    (SELECT COUNT(*)
                                     FROM books b3
                                     WHERE b3.title = b1.title
                                     AND COALESCE(b3.ISBN,'') = COALESCE(b1.ISBN,'')
                                     AND COALESCE(b3.series,'') = COALESCE(b1.series,'')
                                     AND COALESCE(b3.volume,'') = COALESCE(b1.volume,'')
                                     AND COALESCE(b3.part,'') = COALESCE(b1.part,'')
                                     AND COALESCE(b3.edition,'') = COALESCE(b1.edition,'')
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
                                FROM books b1
                                LEFT JOIN contributors c ON b1.id = c.book_id
                                LEFT JOIN writers w ON c.writer_id = w.id
                                LEFT JOIN publications pub ON b1.id = pub.book_id
                                LEFT JOIN publishers p ON pub.publisher_id = p.id
                                GROUP BY b1.title, b1.ISBN, b1.series, b1.volume, b1.part, b1.edition
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
                                    
                                    // Format edition line with distinctive elements
                                    $editionLine = '';
                                    $editionParts = [];
                                    if (!empty($row['edition'])) {
                                        $editionParts[] = 'Edition: ' . htmlspecialchars($row['edition']);
                                    }
                                    if (!empty($row['series'])) {
                                        $editionParts[] = 'Series: ' . htmlspecialchars($row['series']);
                                    }
                                    if (!empty($row['volume'])) {
                                        $editionParts[] = 'Volume: ' . htmlspecialchars($row['volume']);
                                    }
                                    if (!empty($row['part'])) {
                                        $editionParts[] = 'Part: ' . htmlspecialchars($row['part']);
                                    }
                                    if (!empty($row['ISBN'])) {
                                        $editionParts[] = 'ISBN: ' . htmlspecialchars($row['ISBN']);
                                    }
                                    
                                    if (!empty($editionParts)) {
                                        $editionLine = implode(' | ', $editionParts);
                                    }

                                    // Create data attributes for book identification
                                    $dataAttrs = 'data-title="' . htmlspecialchars($row['title']) . '"';
                                    if (!empty($row['ISBN'])) {
                                        $dataAttrs .= ' data-isbn="' . htmlspecialchars($row['ISBN']) . '"';
                                    }
                                    if (!empty($row['series'])) {
                                        $dataAttrs .= ' data-series="' . htmlspecialchars($row['series']) . '"';
                                    }
                                    if (!empty($row['volume'])) {
                                        $dataAttrs .= ' data-volume="' . htmlspecialchars($row['volume']) . '"';
                                    }
                                    if (!empty($row['part'])) {
                                        $dataAttrs .= ' data-part="' . htmlspecialchars($row['part']) . '"';
                                    }
                                    if (!empty($row['edition'])) {
                                        $dataAttrs .= ' data-edition="' . htmlspecialchars($row['edition']) . '"';
                                    }
                                    
                                    // Generate URL for book details page with all identifying parameters
                                    $bookDetailsUrl = 'view_book.php?title=' . urlencode($row['title']);
                                    if (!empty($row['ISBN'])) {
                                        $bookDetailsUrl .= '&isbn=' . urlencode($row['ISBN']);
                                    }
                                    if (!empty($row['series'])) {
                                        $bookDetailsUrl .= '&series=' . urlencode($row['series']);
                                    }
                                    if (!empty($row['volume'])) {
                                        $bookDetailsUrl .= '&volume=' . urlencode($row['volume']);
                                    }
                                    if (!empty($row['part'])) {
                                        $bookDetailsUrl .= '&part=' . urlencode($row['part']);
                                    }
                                    if (!empty($row['edition'])) {
                                        $bookDetailsUrl .= '&edition=' . urlencode($row['edition']);
                                    }

                                    echo "<tr class='clickable-row' data-href='$bookDetailsUrl'>
                                        <td style='width: 5%; text-align: center;'>
                                            <input type='checkbox' class='book-select' $dataAttrs>
                                        </td>
                                        <td style='width: 75%'>
                                            <div class='book-entry'>
                                                <p class='title-line'>" . $firstLine . "</p>
                                                <p class='contributors-line'>" . $byLine . "</p>";
                                                
                                    if (!empty($editionLine)) {
                                        echo "<p class='edition-line'>" . $editionLine . "</p>";
                                    }
                                                
                                    echo "<p class='type-line'>Content type: " . htmlspecialchars($row['content_type']) .
                                                " | Media type: " . htmlspecialchars($row['media_type']) .
                                                " | Shelf Location: " . htmlspecialchars($row['shelf_location']) . "</p>
                                                <p class='publication-line'>Publication Details: " .
                                                htmlspecialchars($row['publisher']) . ", " .
                                                htmlspecialchars($row['publication_year']) . "</p>
                                                <p class='availability-line'>Availability: " .
                                                "<span class='" . ($row['available_copies'] > 0 ? "text-success" : "text-danger") . "'>" .
                                                htmlspecialchars($row['available_copies']) . " out of " .
                                                htmlspecialchars($row['total_copies']) . " copies available</span></p>
                                            </div>
                                            <div class='action-buttons'>
                                                <button class='btn btn-primary btn-sm add-to-cart' $dataAttrs 
                                                    data-toggle='tooltip' 
                                                    title='" . ($row['available_copies'] == 0 ? "Add to cart for when it becomes available" : "Add to cart") . "'>
                                                    <i class='fas fa-cart-plus'></i> Add to Cart
                                                </button>
                                                <button class='btn " . ($row['available_copies'] > 0 ? "btn-success" : "btn-secondary") . " btn-sm borrow-book' $dataAttrs " .
                                                ($row['available_copies'] == 0 ? "disabled" : "") . ">
                                                    <i class='fas fa-book'></i> " . ($row['available_copies'] > 0 ? "Borrow Book" : "Unavailable") . "
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
    // Check login status
    var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    
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

    // Helper function to format book details for display
    function formatBookDetails(data) {
        let details = [data.title];
        let metaDetails = [];
        
        if (data.edition) metaDetails.push("Edition: " + data.edition);
        if (data.series) metaDetails.push("Series: " + data.series);
        if (data.volume) metaDetails.push("Volume: " + data.volume);
        if (data.part) metaDetails.push("Part: " + data.part);
        if (data.isbn) metaDetails.push("ISBN: " + data.isbn);
        
        if (metaDetails.length > 0) {
            details.push(metaDetails.join(" | "));
        }
        
        return details.join("<br>");
    }

    // Function to add book to cart
    function addToCart(element) {
        if (!isLoggedIn) {
            Swal.fire({
                title: 'Please Login',
                text: 'You need to be logged in to add books to the cart.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        const data = {
            title: element.dataset.title,
            isbn: element.dataset.isbn || '',
            series: element.dataset.series || '',
            volume: element.dataset.volume || '',
            part: element.dataset.part || '',
            edition: element.dataset.edition || ''
        };

        Swal.fire({
            title: 'Are you sure?',
            html: 'Do you want to add this book to the cart?<br><br><strong>' + formatBookDetails(data) + '</strong>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, add it!',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'add_to_cart.php',
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        var res = JSON.parse(response);

                        if (res.success) {
                            Swal.fire({
                                title: 'Added!', 
                                html: res.message,
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            // Show error with specific message
                            Swal.fire({
                                title: 'Failed!',
                                html: res.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire('Failed!', 'Failed to add book to cart.', 'error');
                    }
                });
            }
        });
    }

    // Function to borrow book
    function borrowBook(element) {
        if (!isLoggedIn) {
            Swal.fire({
                title: 'Please Login',
                text: 'You need to be logged in to reserve books.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        const data = {
            title: element.dataset.title,
            isbn: element.dataset.isbn || '',
            series: element.dataset.series || '',
            volume: element.dataset.volume || '',
            part: element.dataset.part || '',
            edition: element.dataset.edition || ''
        };

        Swal.fire({
            title: 'Are you sure?',
            html: 'Do you want to reserve this book?<br><br><strong>' + formatBookDetails(data) + '</strong>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, reserve it!',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'reserve_book.php',
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        var res = JSON.parse(response);

                        if (res.success) {
                            Swal.fire({
                                title: 'Reserved!',
                                html: res.message,
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            // Show error with specific message
                            Swal.fire({
                                title: 'Failed!',
                                html: res.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire('Failed!', 'Failed to reserve book.', 'error');
                    }
                });
            }
        });
    }

    // Add click event listener to 'Add to Cart' buttons
    $('.add-to-cart').on('click', function(event) {
        event.stopPropagation();
        addToCart(this);
    });

    // Add click event listener to 'Borrow' buttons
    $('.borrow-book').on('click', function(event) {
        event.stopPropagation();
        borrowBook(this);
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
        const selectedBooks = [];
        $('.book-select:checked').each(function() {
            selectedBooks.push({
                title: $(this).data('title'),
                isbn: $(this).data('isbn') || '',
                series: $(this).data('series') || '',
                volume: $(this).data('volume') || '',
                part: $(this).data('part') || '',
                edition: $(this).data('edition') || ''
            });
        });

        if (selectedBooks.length === 0) return;

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
            html: 'Add these ' + selectedBooks.length + ' book(s) to cart?<br><br>' + 
                  selectedBooks.map(book => '<strong>' + formatBookDetails(book) + '</strong>').join('<br><br>'),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, add them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                let successes = [];
                let failures = [];
                let processed = 0;

                selectedBooks.forEach(book => {
                    $.ajax({
                        url: 'add_to_cart.php',
                        type: 'POST',
                        data: book,
                        success: function(response) {
                            processed++;
                            var res = JSON.parse(response);

                            if (res.success) {
                                // Pass the entire book object instead of just the title
                                successes.push(book);
                            } else {
                                failures.push({title: book.title, reason: res.message});
                            }

                            // When all requests are processed, show summary
                            if (processed === selectedBooks.length) {
                                showResultSummary(successes, failures, 'cart');
                            }
                        },
                        error: function() {
                            processed++;
                            failures.push({title: book.title, reason: "Network error occurred"});

                            if (processed === selectedBooks.length) {
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
        const selectedBooks = [];
        $('.book-select:checked').each(function() {
            selectedBooks.push({
                title: $(this).data('title'),
                isbn: $(this).data('isbn') || '',
                series: $(this).data('series') || '',
                volume: $(this).data('volume') || '',
                part: $(this).data('part') || '',
                edition: $(this).data('edition') || ''
            });
        });

        if (selectedBooks.length === 0) return;

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
            html: 'Reserve these ' + selectedBooks.length + ' book(s)?<br><br>' +
                  selectedBooks.map(book => '<strong>' + formatBookDetails(book) + '</strong>').join('<br><br>'),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, reserve them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                let successes = [];
                let failures = [];
                let processed = 0;

                selectedBooks.forEach(book => {
                    $.ajax({
                        url: 'reserve_book.php',
                        type: 'POST',
                        data: book,
                        success: function(response) {
                            processed++;
                            var res = JSON.parse(response);

                            if (res.success) {
                                // Pass the entire book object instead of just the title
                                successes.push(book);
                            } else {
                                failures.push({title: book.title, reason: res.message});
                            }

                            // When all requests are processed, show summary
                            if (processed === selectedBooks.length) {
                                showResultSummary(successes, failures, 'reserve');
                            }
                        },
                        error: function() {
                            processed++;
                            failures.push({title: book.title, reason: "Network error occurred"});

                            if (processed === selectedBooks.length) {
                                showResultSummary(successes, failures, 'reserve');
                            }
                        }
                    });
                });
            }
        });
    });

    // Update showResultSummary function to show detailed book info
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

        // Generate success message with detailed book information
        if (successes.length > 0) {
            successText = `<p><strong>${successes.length} book(s) successfully ${actionText}:</strong></p>
                          <ul>${successes.map(book => `<li>${formatBookDetails(book)}</li>`).join('')}</ul>`;
        }

        // Generate failure messages by reason
        if (failures.length > 0) {
            failureText = `<p><strong>${failures.length} book(s) could not be ${actionText}:</strong></p>`;

            for (const reason in reasonGroups) {
                failureText += `<p class="text-danger">${reason}</p>`;
                
                // Skip listing redundant titles for common error types
                if (!['Already in cart', 'Already reserved', 'Already borrowed/reserved', 'Maximum limit reached (3 books)'].includes(reason)) {
                    failureText += `<ul>${reasonGroups[reason].map(title => `<li>${title}</li>`).join('')}</ul>`;
                }
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

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
</body>
</html>
