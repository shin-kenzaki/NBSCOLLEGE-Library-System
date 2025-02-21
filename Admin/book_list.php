<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../db.php'; // Database connection

// Handle book deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book_id'])) {
    $bookId = intval($_POST['delete_book_id']);

    // Start transaction
    $conn->begin_transaction();
    try {
        // Delete related contributors first
        $contribQuery = "DELETE FROM contributors WHERE book_id = ?";
        $contribStmt = $conn->prepare($contribQuery);
        $contribStmt->bind_param('i', $bookId);
        $contribStmt->execute();

        // Delete related publications first
        $pubQuery = "DELETE FROM publications WHERE book_id = ?";
        $pubStmt = $conn->prepare($pubQuery);
        $pubStmt->bind_param('i', $bookId);
        $pubStmt->execute();

        // Delete the book
        $bookQuery = "DELETE FROM books WHERE id = ?";
        $bookStmt = $conn->prepare($bookQuery);
        $bookStmt->bind_param('i', $bookId);
        $bookStmt->execute();

        if ($bookStmt->affected_rows > 0) {
            $conn->commit();
            $response = ['message' => 'Book and all related records deleted successfully!'];
        } else {
            $conn->rollback();
            $response = ['message' => 'Failed to delete the book.'];
        }
    } catch (Exception $e) {
        $conn->rollback();
        $response = ['message' => 'Error: ' . $e->getMessage()];
    }

    echo json_encode($response);
    exit();
}

// Handle batch book deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_delete'])) {
    $bookIds = json_decode($_POST['book_ids']);
    $success = true;
    $deleted = 0;

    // Start transaction
    $conn->begin_transaction();
    try {
        foreach ($bookIds as $bookId) {
            // Delete related contributors first
            $contribQuery = "DELETE FROM contributors WHERE book_id = ?";
            $contribStmt = $conn->prepare($contribQuery);
            $contribStmt->bind_param('i', $bookId);
            $contribStmt->execute();

            // Delete related publications first
            $pubQuery = "DELETE FROM publications WHERE book_id = ?";
            $pubStmt = $conn->prepare($pubQuery);
            $pubStmt->bind_param('i', $bookId);
            $pubStmt->execute();

            // Delete the book
            $bookQuery = "DELETE FROM books WHERE id = ?";
            $bookStmt = $conn->prepare($bookQuery);
            $bookStmt->bind_param('i', $bookId);
            $bookStmt->execute();
            
            if ($bookStmt->affected_rows > 0) {
                $deleted++;
            } else {
                $success = false;
            }
        }

        if ($success && $deleted > 0) {
            $conn->commit();
        } else {
            $conn->rollback();
        }

        $response = [
            'success' => $success,
            'message' => $deleted . " book(s) and all related records deleted successfully!"
        ];
    } catch (Exception $e) {
        $conn->rollback();
        $response = [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }

    echo json_encode($response);
    exit();
}

// Check for success message
$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Initialize search query
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book List</title>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var successMessage = "<?php echo $successMessage; ?>";
            if (successMessage) {
                alert(successMessage);
            }
        });
    </script>
</head>
<body>
    <?php include '../admin/inc/header.php'; ?>

    <!-- Main Content -->
    <div id="content" class="d-flex flex-column min-vh-100">
        <div class="container-fluid">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="m-0 font-weight-bold text-primary">Book List</h6>
                        <small id="selectedCount" class="text-muted">(0 books selected)</small>
                    </div>
                    <a href="add-book.php" class="btn btn-primary">Add Book</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <!-- Remove the custom search form and keep only the action buttons -->
                        <div class="mb-3" id="addContributorsIcons">
                            <button class="btn btn-success btn-sm mx-1" id="addContributorsPerson"><i class="fas fa-user-plus"></i> Add Contributors</button>
                            <button class="btn btn-success btn-sm mx-1" id="addPublisher"><i class="fas fa-building"></i> Add Publication</button>
                            <button class="btn btn-danger btn-sm mx-1" id="batchDelete"><i class="fas fa-trash"></i> Delete Selected</button>
                        </div>
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>ID</th>
                                    <th>Accession</th>
                                    <th>Title</th>
                                    <th>Preferred Title</th>
                                    <th>Parallel Title</th>
                                    <th>Front Image</th>
                                    <th>Back Image</th>
                                    <th>Dimension(cm)</th>
                                    <th>Total Pages</th>
                                    <th>Call Number</th>
                                    <th>Copy Number</th>
                                    <th>Language</th>
                                    <th>Shelf Location</th>
                                    <th>Entered By</th>
                                    <th>Date Added</th>
                                    <th>Status</th>
                                    <th>Last Update</th>
                                    <th>Series</th>
                                    <th>Volume</th>
                                    <th>Edition</th>
                                    <th>Content Type</th>
                                    <th>Media Type</th>
                                    <th>Carrier Type</th>
                                    <th>ISBN</th>
                                    <th>URL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch books from database
                                $query = "SELECT * FROM books";
                                if (!empty($searchQuery)) {
                                    $query .= " WHERE title LIKE '%$searchQuery%' OR preferred_title LIKE '%$searchQuery%' OR parallel_title LIKE '%$searchQuery%' OR ISBN LIKE '%$searchQuery%'";
                                }
                                $query .= " ORDER BY id DESC";
                                $result = $conn->query($query);

                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                        <td><input type='checkbox' class='selectRow' value='{$row['id']}'></td>
                                        <td>{$row['id']}</td>
                                        <td>{$row['accession']}</td>
                                        <td>{$row['title']}</td>
                                        <td>{$row['preferred_title']}</td>
                                        <td>{$row['parallel_title']}</td>
                                        <td>";
                                    if (!empty($row['front_image'])) {
                                        echo "<img src='../inc/book-image/{$row['front_image']}' alt='Front Image' width='50'>";
                                    } else {
                                        echo "No Image";
                                    }
                                    echo "</td>
                                        <td>";
                                    if (!empty($row['back_image'])) {
                                        echo "<img src='../inc/book-image/{$row['back_image']}' alt='Back Image' width='50'>";
                                    } else {
                                        echo "No Image";
                                    }
                                    echo "</td>
                                        <td>{$row['dimension']}</td>
                                        <td>{$row['total_pages']}</td>
                                        <td>{$row['call_number']}</td>
                                        <td>{$row['copy_number']}</td>
                                        <td>{$row['language']}</td>
                                        <td>{$row['shelf_location']}</td>
                                        <td>{$row['entered_by']}</td>
                                        <td>{$row['date_added']}</td>
                                        <td>{$row['status']}</td>
                                        <td>{$row['last_update']}</td>
                                        <td>{$row['series']}</td>
                                        <td>{$row['volume']}</td>
                                        <td>{$row['edition']}</td>
                                        <td>{$row['content_type']}</td>
                                        <td>{$row['media_type']}</td>
                                        <td>{$row['carrier_type']}</td>
                                        <td>{$row['ISBN']}</td>
                                        <td>";
                                    echo !empty($row['URL']) ? "<a href='{$row['URL']}' target='_blank'>View</a>" : "N/A";
                                    echo "</td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                            </div>
        </div>
        <!-- /.container-fluid -->

        <!-- Add Contributors Modal -->
        <div class="modal fade" id="addContributorsModal" tabindex="-1" role="dialog" aria-labelledby="addContributorsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addContributorsModalLabel">Add Contributors</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Search Form -->
                        <form id="searchContributorsForm" method="GET" action="book_list.php">
                            <div class="input-group mb-3">
                                <input type="text" id="searchContributors" name="search_contributors" class="form-control" placeholder="Search Contributors...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">Search</button>
                                </div>
                            </div>
                        </form>
                        <!-- Contributors List -->
                        <div class="table-responsive">
                            <table class="table table-bordered" id="contributorsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Writer ID</th>
                                        <th>First Name</th>
                                        <th>Middle Initial</th>
                                        <th>Last Name</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch contributors from database
                                    $contributorsQuery = "SELECT * FROM writers";
                                    if (isset($_GET['search_contributors']) && !empty($_GET['search_contributors'])) {
                                        $searchContributors = $conn->real_escape_string($_GET['search_contributors']);
                                        $contributorsQuery .= " WHERE firstname LIKE '%$searchContributors%' OR middle_init LIKE '%$searchContributors%' OR lastname LIKE '%$searchContributors%'";
                                    }
                                    $contributorsResult = $conn->query($contributorsQuery);

                                    while ($contributor = $contributorsResult->fetch_assoc()) {
                                        echo "<tr>
                                            <td>{$contributor['id']}</td>
                                            <td>{$contributor['firstname']}</td>
                                            <td>{$contributor['middle_init']}</td>
                                            <td>{$contributor['lastname']}</td>
                                            <td><button class='btn btn-success btn-sm addContributor' data-id='{$contributor['id']}'>Add</button></td>
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

        <!-- Context Menu -->
        <div id="contextMenu" class="dropdown-menu" style="display:none; position:absolute;">
            <a class="dropdown-item" href="#" id="updateBook">Update</a>
            <a class="dropdown-item" href="#" id="deleteBook">Delete</a>
        </div>

        <!-- Footer -->
        <?php include '../Admin/inc/footer.php' ?>
        <!-- End of Footer -->

        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

        <script>
        $(document).ready(function () {
            var selectedBookIds = <?php echo json_encode(isset($_SESSION['selectedBookIds']) ? $_SESSION['selectedBookIds'] : []); ?>;

            // Function to update the selected book IDs in the session
            function updateSelectedBookIds() {
                selectedBookIds = [];
                $('.selectRow:checked').each(function() {
                    selectedBookIds.push($(this).val());
                });
                
                // Update the counter display
                $('#selectedCount').text(`(${selectedBookIds.length} books selected)`);
                
                // Store selected book IDs in session
                $.post('selected_books.php', {
                    selectedBookIds: selectedBookIds
                });

                // Update select all checkbox state
                updateSelectAllState();
            }

            // Function to update select all checkbox state
            function updateSelectAllState() {
                var totalCheckboxes = $('.selectRow').length;
                var checkedCheckboxes = $('.selectRow:checked').length;
                $('#selectAll').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
            }

            // Select/Deselect all checkboxes
            $('#selectAll').click(function() {
                $('.selectRow').prop('checked', this.checked);
                updateSelectedBookIds();
            });

            // Individual checkbox click handler
            $(document).on('click', '.selectRow', function() {
                updateSelectedBookIds();
            });

            // Restore the selected state on page load
            function restoreSelectedState() {
                $('.selectRow').each(function() {
                    var bookId = $(this).val();
                    $(this).prop('checked', selectedBookIds.includes(bookId));
                });
                updateSelectAllState();
                $('#selectedCount').text(`(${selectedBookIds.length} books selected)`);
            }

            // Call restoreSelectedState on page load
            restoreSelectedState();

            // Function to fetch and reload the table data
            function fetchBooks() {
                var searchQuery = $('input[name="search"]').val();
                $.ajax({
                    url: 'fetch_books.php',
                    type: 'GET',
                    data: {
                        search: searchQuery
                    },
                    success: function(response) {
                        $('#dataTable tbody').html(response);
                        restoreSelectedState(); // Restore the selected state after search results are loaded
                    }
                });
            }

            // Function to toggle the visibility of the add contributors icons
            function toggleAddContributorsIcons() {
                // if ($('.selectRow:checked').length > 0) {
                //     $('#addContributorsIcons').removeClass('d-none');
                // } else {
                //     $('#addContributorsIcons').addClass('d-none');
                // }
            }

            // Redirect to add_contributors.php when "Add Contributors (Person)" button is clicked
            $('#addContributorsPerson').click(function (e) {
                e.preventDefault();
                var queryString = selectedBookIds.map(id => `book_ids[]=${id}`).join('&');
                window.location.href = `add_contributors.php?${queryString}`;
            });

            // Redirect to add_publication.php when "Add Publication" button is clicked
            $('#addPublisher').click(function (e) {
                e.preventDefault();
                var queryString = selectedBookIds.map(id => `book_ids[]=${id}`).join('&');
                window.location.href = `add_publication.php?${queryString}`;
            });

            // Handle batch delete
            $('#batchDelete').click(function() {
                if (selectedBookIds.length === 0) {
                    alert('Please select books to delete.');
                    return;
                }

                if (confirm('Are you sure you want to delete ' + selectedBookIds.length + ' selected book(s)?')) {
                    $.ajax({
                        url: 'book_list.php',
                        type: 'POST',
                        data: {
                            batch_delete: true,
                            book_ids: JSON.stringify(selectedBookIds)
                        },
                        dataType: 'json',
                        success: function(response) {
                            alert(response.message);
                            location.reload();
                        },
                        error: function() {
                            alert('Error occurred while deleting books.');
                        }
                    });
                }
            });

            // Handle search form submission
            $('#searchForm').submit(function(event) {
                event.preventDefault();
                fetchBooks();
            });
        });
        </script>
        <script>
        $(document).ready(function () {
            var selectedBookId;

            // Add click handler for viewing book details
            $('#dataTable tbody').on('click', 'tr', function(e) {
                // Don't trigger on checkbox click
                if ($(e.target).is('input[type="checkbox"]')) {
                    return;
                }
                var bookId = $(this).find('td:nth-child(2)').text();
                window.location.href = `opac.php?book_id=${bookId}`;
            });

            // Show context menu on right-click
            $('#dataTable tbody').on('contextmenu', 'tr', function(e) {
                e.preventDefault();
                $('#dataTable tbody tr').removeClass('context-menu-active');
                $(this).addClass('context-menu-active');
                selectedBookId = $(this).find('td:nth-child(2)').text();
                $('#contextMenu').css({
                    display: 'block',
                    left: e.pageX,
                    top: e.pageY
                });
                return false;
            });

            // Hide context menu on click outside
            $(document).click(function() {
                $('#contextMenu').hide();
            });

            // Handle context menu actions
            $('#updateBook').click(function() {
                window.location.href = `update_book.php?book_id=${selectedBookId}`;
            });

            $('#deleteBook').click(function() {
                if (confirm('Are you sure you want to delete this book?')) {
                    $.post('book_list.php', { delete_book_id: selectedBookId }, function(response) {
                        alert(response.message);
                        location.reload();
                    }, 'json');
                }
            });
        });
        </script>
        <script>
        $(document).ready(function () {
            var table = $('#dataTable').DataTable({
                "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
                       "<'row'<'col-sm-12'tr>>" +
                       "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
                "responsive": false, // Disable responsive feature
                "scrollX": true,    // Enable horizontal scroll
                "columnDefs": [
                    { 
                        "orderable": false, 
                        "searchable": false,
                        "targets": 0 
                    }
                ],
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

            // Keep the original checkbox click handler for direct checkbox clicks
            $(document).on('click', '.selectRow', function(e) {
                // Stop propagation to prevent the td click handler from firing
                e.stopPropagation();
                updateSelectedBookIds();
            });
        });
        </script>
    </div>
</body>
</html>
