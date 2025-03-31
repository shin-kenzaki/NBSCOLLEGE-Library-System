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

// Check for success message
$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
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
    ISBN,
    series,
    volume,
    edition,
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

$query .= " GROUP BY title, ISBN, series, volume, edition ORDER BY title";
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
            var successMessage = "<?php echo $successMessage; ?>";
            if (successMessage) {
                alert(successMessage);
            }
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
    
    .total-books-display {
        font-size: 0.9rem;
        color: #4e73df;
        font-weight: 600;
        margin-right: 10px;
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
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Book List</h6>
                    <div class="d-flex align-items-center">
                        <span class="mr-2 total-books-display">
                            Total Books: <?php echo number_format($totalBooks); ?>
                        </span>
                        <a href="add-book.php" class="btn btn-primary btn-sm">Add Book</a>
                    </div>
                </div>
                <div class="card-body px-0"> <!-- Remove padding for full-width scroll -->
                    <div class="table-responsive px-3"> <!-- Add padding inside scroll container -->
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
    <thead>
        <tr>
            <th style="text-align: center">ID Range</th>
            <th style="text-align: center">Accession Range</th>
            <th style="text-align: center">Title</th>
            <th style="text-align: center">Call Number Range</th>
            <th style="text-align: center">Copy Number Range</th>
            <th style="text-align: center">Shelf Locations</th>
            <th style="text-align: center">ISBN</th>
            <th style="text-align: center">Series</th>
            <th style="text-align: center">Volume</th>
            <th style="text-align: center">Edition</th>
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
            ISBN,
            series,
            volume,
            edition,
            COUNT(*) as total_copies
            FROM books ";
        
        if (!empty($searchQuery)) {
            $query .= " WHERE title LIKE '%$searchQuery%' ";
        }
        
        $query .= " GROUP BY title, ISBN, series, volume, edition ORDER BY title";
        
        $result = $conn->query($query);

        while ($row = $result->fetch_assoc()) {
            // Process IDs
            $ids = explode(',', $row['id_range']);
            $id_range = formatRange($ids);

            // Process accessions
            $accessions = explode(',', $row['accession_range']);
            $accession_range = formatRange($accessions);

            // Process call numbers (using existing formatCallNumberSequence function)
            $call_number_data = explode(',', $row['call_number_data']);
            $call_numbers = [];
            $current_base = '';
            $current_sequence = [];

            foreach ($call_number_data as $data) {
                list($call_num, $copy_num) = explode('|', $data);
                $base_call = preg_replace('/\s*c\d+$/', '', $call_num);

                if ($base_call !== $current_base) {
                    if (!empty($current_sequence)) {
                        $call_numbers[] = formatCallNumberSequence($current_base, $current_sequence);
                    }
                    $current_base = $base_call;
                    $current_sequence = [];
                }
                $current_sequence[] = $copy_num;
            }
            
            if (!empty($current_sequence)) {
                $call_numbers[] = formatCallNumberSequence($current_base, $current_sequence);
            }

            // Process copy numbers
            $copy_numbers = explode(',', $row['copy_number_range']);
            $copy_range = formatRange($copy_numbers);

            // Process shelf locations
            $shelf_locations = array_unique(explode(',', $row['shelf_locations']));
            $formatted_shelf_locations = implode(', ', $shelf_locations);

            // Format all data for display - reordered columns to put accession first
            echo "<tr data-book-id='" . $ids[0] . "'>
                <td style='text-align: center'>{$id_range}</td>
                <td style='text-align: center'>{$accession_range}</td>
                <td>{$row['title']}</td>
                <td style='text-align: center'>" . implode('<br>', $call_numbers) . "</td>
                <td style='text-align: center'>{$copy_range}</td>
                <td style='text-align: center'>{$formatted_shelf_locations}</td>
                <td style='text-align: center'>" . ($row['ISBN'] ?: 'N/A') . "</td>
                <td style='text-align: center'>" . ($row['series'] ?: 'N/A') . "</td>
                <td style='text-align: center'>" . ($row['volume'] ?: 'N/A') . "</td>
                <td style='text-align: center'>" . ($row['edition'] ?: 'N/A') . "</td>
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
        </div>
        <!-- /.container-fluid -->

        <!-- Custom Context Menu -->
        <div id="contextMenu" class="dropdown-menu shadow-sm custom-context-menu" style="display:none; position:absolute; z-index:1000;">
            <a class="dropdown-item context-update" href="#"><i class="fas fa-edit fa-sm fa-fw mr-2 text-gray-400"></i> Update Books</a>
            <a class="dropdown-item context-view" href="#"><i class="fas fa-eye fa-sm fa-fw mr-2 text-gray-400"></i> View Book Details</a>
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
                let accessionRangeCell = row.find('td:nth-child(1)'); // Accession Range is in the 2nd column (1-based index)
                
                // Extract the data
                let accessionRange = accessionRangeCell.text().trim();
                
                // Verify we have the necessary data before proceeding
                if (!accessionRange) {
                    console.error('Missing required data for context menu', { accessionRange });
                    return false;
                }
                
                // Store data in the context menu
                $('#contextMenu').data('accession-range', accessionRange);
                
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
                "responsive": false, // Disable DataTables responsive handling
                "scrollX": true, // Enable horizontal scrolling
                "columnDefs": [
                    {
                        "targets": 0, 
                        "visible": false,
                        "searchable": false
                    }
                ],
                "order": [[2, "asc"]], 
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search..."
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
        </style>
    </div>
</body>
</html>
