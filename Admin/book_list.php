<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../db.php'; // Database connection

// Count total books in database
$totalBooksQuery = "SELECT COUNT(*) as total FROM books";
$totalBooksResult = $conn->query($totalBooksQuery);
$totalBooks = $totalBooksResult->fetch_assoc()['total'];

// Handle book deletion - keeping only single book deletion
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

// Add copies functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['num_copies']) && isset($_POST['book_id'])) {
    $numCopiesToAdd = intval($_POST['num_copies']);
    $firstBookId = intval($_POST['book_id']);

    // Fetch the first book's details
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $firstBookId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['message' => 'Book not found.']);
        exit();
    }

    $firstBook = $result->fetch_assoc();

    // Get the current max copy number and accession for this title
    $stmt = $conn->prepare("SELECT MAX(copy_number) as max_copy, MAX(accession) as max_accession FROM books WHERE title = ?");
    $stmt->bind_param("s", $firstBook['title']);
    $stmt->execute();
    $maxInfo = $stmt->get_result()->fetch_assoc();
    $currentCopy = $maxInfo['max_copy'] ?: 0;
    $currentAccession = $maxInfo['max_accession'] ?: 0;

    $successCount = 0;

    for ($i = 1; $i <= $numCopiesToAdd; $i++) {
        $newCopyNumber = $currentCopy + $i;
        $newAccession = $currentAccession + $i;

        $query = "INSERT INTO books (
            accession, title, preferred_title, parallel_title, subject_category,
            subject_detail, summary, contents, front_image, back_image,
            dimension, series, volume, edition, copy_number, total_pages,
            supplementary_contents, ISBN, content_type, media_type, carrier_type,
            call_number, URL, language, shelf_location, entered_by, date_added,
            status, last_update
        ) SELECT 
            ?, title, preferred_title, parallel_title, subject_category,
            subject_detail, summary, contents, front_image, back_image,
            dimension, series, volume, edition, ?,
            total_pages, supplementary_contents, ISBN, content_type, media_type, carrier_type,
            call_number, URL, language, shelf_location, entered_by, ?, 'Available', ?
        FROM books WHERE id = ?";

        $currentDate = date('Y-m-d');
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iissi", $newAccession, $newCopyNumber, $currentDate, $currentDate, $firstBookId);

        if ($stmt->execute()) {
            $successCount++;
        }
    }

    echo json_encode(['message' => "Successfully added $successCount copies with status 'Available'."]);
    exit();
}

// Check for success message and book details
$successMessage = '';
$addedBookTitle = '';
$addedBookCopies = 0;

if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
    
    // Get added book details if available
    if (isset($_SESSION['added_book_title']) && isset($_SESSION['added_book_copies'])) {
        $addedBookTitle = $_SESSION['added_book_title'];
        $addedBookCopies = intval($_SESSION['added_book_copies']);
        unset($_SESSION['added_book_title']);
        unset($_SESSION['added_book_copies']);
    }
}

// Initialize search query
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Modified query to group books by title with better grouping
$query = "SELECT 
    title,
    GROUP_CONCAT(DISTINCT id ORDER BY id) as id_range,
    GROUP_CONCAT(DISTINCT accession ORDER BY accession) as accession_range,
    GROUP_CONCAT(CONCAT(call_number, '|', copy_number) ORDER BY copy_number) as call_number_data,
    GROUP_CONCAT(DISTINCT copy_number ORDER BY copy_number) as copy_number_range,
    GROUP_CONCAT(DISTINCT shelf_location ORDER BY shelf_location) as shelf_locations,
    GROUP_CONCAT(DISTINCT program ORDER BY program) as programs,
    ISBN,
    series,
    volume,
    edition,
    part,
    COUNT(*) as total_copies
    FROM books ";

if (!empty($searchQuery)) {
    $query .= " WHERE title LIKE ? ";
    $stmt = $conn->prepare($query);
    $searchParam = "%$searchQuery%";
    $stmt->bind_param("s", $searchParam);
} else {
    $stmt = $conn->prepare($query);
}

$query .= " GROUP BY title, ISBN, series, volume, edition, part ORDER BY title";
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book List</title>
    <style>
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
            min-width: 100px; /* Adjust this value based on your content */
            white-space: nowrap;
        }
        
        /* Specific column widths */
        #dataTable th:nth-child(2),
        #dataTable td:nth-child(2) {
            min-width: 200px; /* Title column wider */
        }
        
        /* Make the table stretch full width */
        #dataTable {
            width: 100% !important;
        }
        
        /* Prevent text wrapping in cells */
        .table td, .table th {
            white-space: nowrap;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Display success message with book details if available
            <?php if ($successMessage && $addedBookTitle && $addedBookCopies > 0): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    html: '<strong><?php echo htmlspecialchars($addedBookTitle); ?></strong><br>' +
                          'Successfully added <?php echo $addedBookCopies; ?> copy/copies.',
                    confirmButtonColor: '#3085d6'
                });
            <?php elseif ($successMessage): ?>
                // Fallback to simple success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?php echo $successMessage; ?>',
                    confirmButtonColor: '#3085d6'
                });
            <?php endif; ?>
        });
    </script>
    <style>
    @media (max-width: 575.98px) {
        .card-header .btn-group {
            display: flex;
            width: 100%;
        }
        
        .card-header .btn-group .btn {
            flex: 1;
        }
        
        .card-header .btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
            white-space: nowrap;
        }
    }
</style>
    <style>
    /* Add this to your existing styles */
    .card-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    
    .book-stats {
        display: flex;
        align-items: center;
    }
    
    @media (max-width: 575.98px) {
        .card-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .card-header .btn-group {
            display: flex;
            width: 100%;
        }
        
        .card-header .btn-group .btn {
            flex: 1;
        }
    }
</style>
</head>
<body>
    <?php include '../admin/inc/header.php'; ?>

    <!-- Main Content -->
    <div id="content" class="d-flex flex-column min-vh-100">
        <div class="container-fluid">
            <h1 class="h3 mb-2 text-gray-800">Books Management</h1>
            <p class="mb-4">Manage all books in the system.</p>

            <!-- Action Buttons -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div class="btn-group">
                    <a href="add-book.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-plus-circle"></i> Quick Add
                    </a>
                    <a href="step-by-step-add-book.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-list-ol"></i> Step-by-Step
                    </a>
                </div>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#instructionsModal">
                    <i class="fas fa-question-circle"></i> Instructions
                </button>
            </div>

            <!-- Books Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="text-align: center">ID Range</th>
                            <th style="text-align: center">Accession Range</th>
                            <th style="text-align: center">Title</th>
                            <th style="text-align: center">Call Number Range</th>
                            <th style="text-align: center">Copy Number Range</th>
                            <th style="text-align: center">Shelf Locations</th>
                            <th style="text-align: center">Program</th>
                            <th style="text-align: center">ISBN</th>
                            <th style="text-align: center">Series</th>
                            <th style="text-align: center">Volume</th>
                            <th style="text-align: center">Edition</th>
                            <th style="text-align: center">Part</th>
                            <th style="text-align: center">Total Copies</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT 
                            title,
                            GROUP_CONCAT(DISTINCT id ORDER BY id) as id_range,
                            GROUP_CONCAT(DISTINCT accession ORDER BY accession) as accession_range,
                            GROUP_CONCAT(CONCAT(call_number, '|', copy_number) ORDER BY copy_number) as call_number_data,
                            GROUP_CONCAT(DISTINCT copy_number ORDER BY copy_number) as copy_number_range,
                            GROUP_CONCAT(DISTINCT shelf_location ORDER BY shelf_location) as shelf_locations,
                            GROUP_CONCAT(DISTINCT program ORDER BY program) as programs,
                            ISBN,
                            series,
                            volume,
                            edition,
                            part,
                            COUNT(*) as total_copies
                            FROM books ";
                        
                        if (!empty($searchQuery)) {
                            $query .= " WHERE title LIKE '%$searchQuery%' ";
                        }
                        
                        $query .= " GROUP BY title, ISBN, series, volume, edition, part ORDER BY title";
                        
                        $result = $conn->query($query);

                        while ($row = $result->fetch_assoc()) {
                            // Process IDs
                            $ids = explode(',', $row['id_range']);
                            $id_range = formatRange($ids);

                            // Process accessions
                            $accessions = explode(',', $row['accession_range']);
                            $accession_range = formatRange($accessions);

                            // Process call numbers
                            $call_number_data = explode(',', $row['call_number_data']);
                            $call_numbers = [];
                            $current_base = '';
                            $current_sequence = [];

                            foreach ($call_number_data as $data) {
                                // Fix: Safely handle data without a pipe character
                                $parts = explode('|', $data);
                                
                                // Make sure we have both parts (call number and copy number)
                                if (count($parts) >= 2) {
                                    $call_num = $parts[0];
                                    $copy_num = $parts[1];
                                } else {
                                    // If we don't have both parts, just use the data as the call number
                                    $call_num = $data;
                                    $copy_num = "1"; // Default copy number
                                }
                                
                                $base_call = preg_replace('/\s*c\d+$/', '', $call_num);

                                if ($base_call !== $current_base) {
                                    if (!empty($current_sequence)) {
                                        $call_numbers[] = implode('<br>', $current_sequence);
                                    }
                                    $current_base = $base_call;
                                    $current_sequence = [];
                                }
                                $current_sequence[] = $call_num;
                            }
                            
                            if (!empty($current_sequence)) {
                                $call_numbers[] = implode('<br>', $current_sequence);
                            }

                            // Process copy numbers
                            $copy_numbers = explode(',', $row['copy_number_range']);
                            $copy_range = formatRange($copy_numbers);

                            // Process shelf locations
                            $shelf_locations = array_unique(explode(',', $row['shelf_locations']));
                            $formatted_shelf_locations = implode(', ', $shelf_locations);

                            // Process program data
                            $programs = !empty($row['programs']) ? array_unique(explode(',', $row['programs'])) : ['N/A'];
                            $formatted_programs = implode(', ', $programs);

                            // Format all data for display - reordered columns to put accession first, and add program
                            echo "<tr data-book-id='" . $ids[0] . "'>
                                <td style='text-align: center'>{$id_range}</td>
                                <td style='text-align: center'>{$accession_range}</td>
                                <td>{$row['title']}</td>
                                <td style='text-align: center'>" . implode('<br>', $call_numbers) . "</td>
                                <td style='text-align: center'>{$copy_range}</td>
                                <td style='text-align: center'>{$formatted_shelf_locations}</td>
                                <td style='text-align: center'>{$formatted_programs}</td>
                                <td style='text-align: center'>" . ($row['ISBN'] ?: 'N/A') . "</td>
                                <td style='text-align: center'>" . ($row['series'] ?: 'N/A') . "</td>
                                <td style='text-align: center'>" . ($row['volume'] ?: 'N/A') . "</td>
                                <td style='text-align: center'>" . ($row['edition'] ?: 'N/A') . "</td>
                                <td style='text-align: center'>" . ($row['part'] ?: 'N/A') . "</td>
                                <td style='text-align: center'>{$row['total_copies']}</td>
                            </tr>";
                        }

                        // Helper function to format ranges smartly
                        function formatRange($numbers) {
                            if (empty($numbers)) return 'N/A';
                            
                            $numbers = array_map('intval', $numbers);
                            sort($numbers);
                            
                            $ranges = [];
                            $start = $numbers[0];
                            $prev = $start;
                            
                            for ($i = 1; $i <= count($numbers); $i++) {
                                if ($i == count($numbers) || $numbers[$i] - $prev > 1) {
                                    if ($start == $prev) {
                                        $ranges[] = $start;
                                    } else {
                                        $ranges[] = "$start-$prev";
                                    }
                                    if ($i < count($numbers)) {
                                        $start = $numbers[$i];
                                        $prev = $start;
                                    }
                                } else {
                                    $prev = $numbers[$i];
                                }
                            }
                            
                            return implode(', ', $ranges);
                        }

                        // Keep the existing formatCallNumberSequence function
                        function formatCallNumberSequence($base_call, $copies) {
                            sort($copies, SORT_NUMERIC);
                            $ranges = [];
                            $start = $copies[0];
                            $prev = $start;
                            
                            for ($i = 1; $i <= count($copies); $i++) {
                                if ($i == count($copies) || $copies[$i] - $prev > 1) {
                                    if ($start == $prev) {
                                        $ranges[] = $base_call . " c" . $start;
                                    } else {
                                        $ranges[] = $base_call . " c" . $start . " - " . $base_call . " c" . $prev;
                                    }
                                    if ($i < count($copies)) {
                                        $start = $copies[$i];
                                        $prev = $start;
                                    }
                                } else {
                                    $prev = $copies[$i];
                                }
                            }
                            return implode('<br>', $ranges); 
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- End of Main Content -->

    <!-- Custom Context Menu -->
    <div id="contextMenu" class="dropdown-menu shadow-sm custom-context-menu" style="display:none; position:absolute; z-index:1000;">
        <a class="dropdown-item context-update" href="#"><i class="fas fa-edit fa-sm fa-fw mr-2 text-gray-400"></i> Update Books</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item context-add-copies" href="#"><i class="fas fa-plus fa-sm fa-fw mr-2 text-gray-400"></i> Add Copies</a>
    </div>

    <!-- Footer -->
    <?php include '../Admin/inc/footer.php' ?>
    <!-- End of Footer -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Add Copies Modal -->
    <div class="modal fade" id="addCopiesModal" tabindex="-1" aria-labelledby="addCopiesModalLabel" aria-hidden="true">
        <form action="process-add-copies.php" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCopiesModalLabel">Add More Copies</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Add additional copies of <strong id="copyBookTitle"></strong></p>
                    <div class="form-group">
                        <label for="num_copies">Number of copies to add:</label>
                        <input type="number" class="form-control" id="num_copies" name="num_copies" min="1" value="1" required>
                    </div>
                    <input type="hidden" name="book_id" id="copyBookId" value="">
                    <input type="hidden" name="accession" id="copyBookAccession" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Copies</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Instructions Modal -->
    <div class="modal fade" id="instructionsModal" tabindex="-1" aria-labelledby="instructionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="instructionsModalLabel">Book Management Instructions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs" id="instructionTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">Overview</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="add-book-tab" data-bs-toggle="tab" data-bs-target="#add-book" type="button" role="tab" aria-controls="add-book" aria-selected="false">Add Book</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="add-copies-tab" data-bs-toggle="tab" data-bs-target="#add-copies" type="button" role="tab" aria-controls="add-copies" aria-selected="false">Add Copies</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="update-tab" data-bs-toggle="tab" data-bs-target="#update" type="button" role="tab" aria-controls="update" aria-selected="false">Update Books</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="delete-tab" data-bs-toggle="tab" data-bs-target="#delete" type="button" role="tab" aria-controls="delete" aria-selected="false">Delete Books</button>
                        </li>
                    </ul>
                    
                    <!-- Tabs Content -->
                    <div class="tab-content mt-3" id="instructionTabsContent">
                        <!-- Overview Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                            <h5>Book Management System</h5>
                            <p>This system allows you to manage the library's book collection through the following operations:</p>
                            <ul class="instruction-steps">
                                <li><strong>Add Book</strong> - Add a new book title to the library collection</li>
                                <li><strong>Add Copies</strong> - Add multiple copies of an existing book</li>
                                <li><strong>Update Books</strong> - Modify information for existing books</li>
                                <li><strong>Delete Books</strong> - Remove specific copies or all copies of a book</li>
                            </ul>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Select the appropriate tab above for detailed instructions on each operation.
                            </div>
                        </div>
                        
                        <!-- Add Book Tab -->
                        <div class="tab-pane fade" id="add-book" role="tabpanel" aria-labelledby="add-book-tab">
                            <h5>Adding a New Book</h5>
                            <ol class="instruction-steps">
                                <li>Click the <span class="badge bg-secondary text-white">Add Book</span> button in the top-right corner of the Book List page.</li>
                                <li>Complete the form with book details:
                                    <ul>
                                        <li><strong>Title Proper</strong> - Enter the main title of the book</li>
                                        <li><strong>Authors</strong> - Select or add author information</li>
                                        <li><strong>ISBN</strong> - Enter the ISBN number (if available)</li>
                                        <li><strong>Publication</strong> - Enter publication details</li>
                                        <li><strong>Call Number</strong> - System will suggest a call number based on other details</li>
                                        <li><strong>Subject Information</strong> - Categorize the book</li>
                                    </ul>
                                </li>
                                <li>Click <span class="badge bg-primary text-white">Next</span> to move through the form tabs.</li>
                                <li>In the Accession section, specify the number of copies and starting accession number.</li>
                                <li>Click <span class="badge bg-danger text-white">Submit</span> to save the book to the library system.</li>
                            </ol>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Be careful with accession numbers - they must be unique across the entire library system.
                            </div>
                        </div>
                        
                        <!-- Add Copies Tab -->
                        <div class="tab-pane fade" id="add-copies" role="tabpanel" aria-labelledby="add-copies-tab">
                            <h5>Adding Copies of an Existing Book</h5>
                            <ol class="instruction-steps">
                                <li>Locate the book in the Book List for which you want to add copies.</li>
                                <li>Right-click on the book's row to open the context menu.</li>
                                <li>Select <strong>Add Copies</strong> from the context menu.</li>
                                <li>In the modal dialog that appears:
                                    <ul>
                                        <li>Confirm the book title is correct</li>
                                        <li>Enter the number of copies to add</li>
                                        <li>Review the starting accession number (automatically calculated)</li>
                                    </ul>
                                </li>
                                <li>Click <span class="badge bg-danger text-white">Add Copies</span> to create the additional copies.</li>
                            </ol>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> The system will automatically assign consecutive accession numbers and copy numbers based on existing data.
                            </div>
                        </div>
                        
                        <!-- Update Tab -->
                        <div class="tab-pane fade" id="update" role="tabpanel" aria-labelledby="update-tab">
                            <h5>Updating Existing Books</h5>
                            <ol class="instruction-steps">
                                <li>Locate the book in the Book List that you want to update.</li>
                                <li>Right-click on the book's row to open the context menu.</li>
                                <li>Select <strong>Update Books</strong> from the context menu.</li>
                                <li>In the update form:
                                    <ul>
                                        <li>Modify any information that needs to be changed</li>
                                        <li>You can update details for all copies simultaneously</li>
                                        <li>Individual copy details can be modified separately (status, shelf location, etc.)</li>
                                    </ul>
                                </li>
                                <li>Click <span class="badge bg-danger text-white">Update Books</span> to save your changes.</li>
                            </ol>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> When updating multiple copies, be careful with fields like call numbers and accession numbers which should remain unique.
                            </div>
                        </div>
                        
                        <!-- Delete Tab -->
                        <div class="tab-pane fade" id="delete" role="tabpanel" aria-labelledby="delete-tab">
                            <h5>Deleting Books</h5>
                            <p><strong>Option 1: Delete a Specific Copy</strong></p>
                            <ol class="instruction-steps">
                                <li>Click on a book title to view its details in the OPAC view.</li>
                                <li>Scroll to the "Holdings Information" table showing all copies.</li>
                                <li>Locate the specific copy you want to delete.</li>
                                <li>Click the <span class="badge bg-danger text-white">Delete</span> button for that copy.</li>
                                <li>Confirm the deletion when prompted.</li>
                            </ol>
                            
                            <p><strong>Option 2: Delete All Copies</strong></p>
                            <ol class="instruction-steps">
                                <li>Click on a book title to view its details in the OPAC view.</li>
                                <li>Click the <span class="badge bg-danger text-white">Delete All Copies</span> button near the top of the page.</li>
                                <li>Review the confirmation dialog showing the number of copies that will be deleted.</li>
                                <li>Click <span class="badge bg-danger text-white">Yes, delete all!</span> to confirm.</li>
                            </ol>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Deleting books is permanent and will also remove all related contributor and publication records. Ensure there are no active borrowings before deletion.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add custom styles for the instructions -->
    <style>
        .instruction-steps {
            padding-left: 1.5rem;
        }
        
        .instruction-steps li {
            margin-bottom: 0.75rem;
        }
        
        .instruction-steps ul {
            padding-left: 1.5rem;
        }
        
        .instruction-steps ul li {
            margin-bottom: 0.5rem;
        }
        
        .badge {
            font-weight: normal;
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }
        
        #instructionTabs .nav-link {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            #instructionTabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            #instructionTabs .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
        }
    </style>

    <!-- Script for instruction tabs -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tabs
            var instructionTabs = document.querySelectorAll('#instructionTabs button');
            instructionTabs.forEach(function(tab) {
                tab.addEventListener('click', function(event) {
                    event.preventDefault();
                    
                    // Remove active class from all tabs
                    instructionTabs.forEach(function(t) {
                        t.classList.remove('active');
                        t.setAttribute('aria-selected', 'false');
                    });
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    this.setAttribute('aria-selected', 'true');
                    
                    // Hide all tab content
                    document.querySelectorAll('.tab-pane').forEach(function(pane) {
                        pane.classList.remove('show', 'active');
                    });
                    
                    // Show clicked tab content
                    var target = document.querySelector(this.getAttribute('data-bs-target'));
                    if (target) {
                        target.classList.add('show', 'active');
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function () {
            // Keep only the click handler for viewing book details
            $('#dataTable tbody').on('click', 'tr', function(e) {
                // Get the book ID from the data attribute
                var bookId = $(this).data('book-id');
                if (!bookId) {
                    // If data attribute isn't available, get ID from the ID range column
                    var idRange = $(this).find('td:eq(0)').text();
                    // Extract the first ID from the range (in case it's a range like "1-5")
                    bookId = idRange.split('-')[0].split(',')[0].trim();
                }
                
                window.location.href = `opac.php?book_id=${bookId}`;
            });

            // Improved right-click context menu handler
            $('#dataTable tbody').on('contextmenu', 'tr', function(e) {
                e.preventDefault();
                
                // Clear previous data to avoid stale values
                $('#contextMenu').removeData();
                
                // Get the row data
                let row = $(this);
                let idRangeCell = row.find('td:eq(0)'); // First column (0-indexed) - ID Range
                let accessionRangeCell = row.find('td:eq(1)'); // Second column (1-indexed) - Accession Range
                let titleCell = row.find('td:eq(1)'); // Third column (2-indexed) - Title
                
                // Extract the data
                let idRange = idRangeCell.text().trim();
                let accessionRange = accessionRangeCell.text().trim();
                let title = titleCell.text().trim();
                
                // Get the first ID and first accession from the ranges
                let firstId = idRange.split('-')[0].split(',')[0].trim();
                let firstAccession = accessionRange.split('-')[0].split(',')[0].trim();
                
                console.log('Context menu data:', {
                    idRange: idRange,
                    accessionRange: accessionRange,
                    title: title,
                    firstId: firstId,
                    firstAccession: firstAccession
                });
                
                // Verify we have the necessary data before proceeding
                if (!firstAccession || !firstId) {
                    console.error('Missing required data for context menu', { firstAccession, firstId });
                    return false;
                }
                
                // Store data in the context menu
                $('#contextMenu').data('accession-range', accessionRange);
                $('#contextMenu').data('first-id', firstId);
                $('#contextMenu').data('first-accession', firstAccession);
                $('#contextMenu').data('title', title);
                
                // Position and show the menu
                $('#contextMenu').css({
                    top: e.pageY + 'px',
                    left: e.pageX + 'px'
                }).show();
                
                // Add a click event to the document to hide the menu when clicking elsewhere
                $(document).one('click', function() {
                    $('#contextMenu').hide();
                });
                
                return false;
            });

            // Use event delegation for context menu actions
            $(document).on('click', '.context-update', function() {
                let accessionRange = $('#contextMenu').data('accession-range');
                
                // Verify data before redirecting
                if (!accessionRange) {
                    console.error('Missing required data for update action', { accessionRange });
                    return;
                }
                
                // Log the URL we're about to navigate to
                let url = `update_books.php?accession_range=${encodeURIComponent(accessionRange)}`;
                console.log('Navigating to:', url);
                
                window.location.href = url;
            });
            
            // Add handler for Add Copies option
            $(document).on('click', '.context-add-copies', function() {
                let firstId = $('#contextMenu').data('first-id');
                let firstAccession = $('#contextMenu').data('first-accession');
                let title = $('#contextMenu').data('title');
                
                if (!firstId || !firstAccession) {
                    console.error('Missing book ID or accession for adding copies');
                    return;
                }
                
                // Open modal dialog for adding copies
                $('#addCopiesModal').modal('show');
                $('#copyBookTitle').text(title);
                $('#copyBookId').val(firstId);
                $('#copyBookAccession').val(firstAccession);
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
                "lengthMenu": [[10, 25, 50, 100, 500, -1], [10, 25, 50, 100, 500, "All"]],
                "responsive": false, // Disable DataTables responsive handling
                "scrollX": true, // Enable horizontal scrolling
                "columnDefs": [
                    {
                        "targets": [0], // First column - ID Range
                        "visible": true,
                        "searchable": true
                    }
                ],
                "order": [[2, "asc"]], // Sort by Title column
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search..."
                }
            });

            // Add a confirmation dialog when "All" option is selected
            $('#dataTable').on('length.dt', function ( e, settings, len ) {
                if (len === -1) {
                    Swal.fire({
                        title: 'Display All Entries?',
                        text: "Are you sure you want to display all entries? This may cause performance issues.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, display all!'
                    }).then((result) => {
                        if (result.dismiss === Swal.DismissReason.cancel) {
                            // If the user cancels, reset the page length to the previous value
                            table.page.len(settings._iDisplayLength).draw();
                        }
                    });
                }
            });
            
            // Adjust table columns on window resize
            $(window).on('resize', function () {
                table.columns.adjust();
            });
        });
    </script>
    <style>
        /* Add CSS to hide ID range columns but keep them accessible for JavaScript */
        .hidden-column {
            display: none;
        }
        
        /* Style for the custom context menu */
        .custom-context-menu {
            min-width: 180px;
            padding: 0.5rem 0;
            background-color: #fff;
            border: 1px solid rgba(0,0,0,.15);
        }
        
        .custom-context-menu .dropdown-item {
            padding: 0.5rem 1rem;
            color: #3a3b45;
            font-weight: 400;
            font-size: 0.85rem;
        }
        
        .custom-context-menu .dropdown-item:hover {
            background-color: #4e73df;
            color: white;
        }
        
        .custom-context-menu .dropdown-item:hover i {
            color: white !important;
        }
        
        /* Add visual cue for right-clickable rows */
        #dataTable tbody tr {
            cursor: context-menu;
        }
        
        /* Add selected row styling */
        #dataTable tbody tr.selected {
            background-color: rgba(0, 123, 255, 0.1) !important;
        }
        
        /* Override striped table styling for selected rows */
        #dataTable.table-striped tbody tr.selected:nth-of-type(odd),
        #dataTable.table-striped tbody tr.selected:nth-of-type(even) {
            background-color: rgba(0, 123, 255, 0.1) !important;
        }

        /* Enhance alternating row colors with hover effect preservation */
        #dataTable.table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.03);
        }

        #dataTable.table-striped tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
    </style>
</body>
</html>
