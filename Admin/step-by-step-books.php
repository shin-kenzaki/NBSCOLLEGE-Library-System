<?php
// Start session first
session_start();
include '../db.php';

// Check if user is logged in with correct privileges BEFORE including header
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Initialize the shortcut session if not exists
if (!isset($_SESSION['book_shortcut'])) {
    header("Location: step-by-step-add-book.php");
    exit();
}

// Store the referrer information - check if we came from the form
if (!isset($_SESSION['return_to_form'])) {
    $_SESSION['return_to_form'] = (isset($_SERVER['HTTP_REFERER']) &&
                                  strpos($_SERVER['HTTP_REFERER'], 'step-by-step-add-book-form.php') !== false);
}

// Check if previous steps were completed
if (!$_SESSION['book_shortcut']['steps_completed']['writer'] || !$_SESSION['book_shortcut']['steps_completed']['publisher']) {
    $_SESSION['error'] = "Please complete the previous steps first.";
    header("Location: step-by-step-add-book.php");
    exit();
}

// Handle book title selection
if (isset($_POST['copy_title'])) {
    $_SESSION['book_shortcut']['book_title'] = $_POST['book_title'];
    $_SESSION['book_shortcut']['steps_completed']['title'] = true;

    // Always redirect to the form page when a title is selected via copy
    header("Location: step-by-step-add-book-form.php");
    exit();
}

// Handle proceed to add book
if (isset($_POST['proceed_to_add'])) {
    // Validate required data
    if (empty($_POST['new_title'])) {
        echo "<script>alert('Book title is required.');</script>";
    } else {
        $_SESSION['book_shortcut']['steps_completed']['title'] = true;
        $_SESSION['book_shortcut']['book_title'] = $_POST['new_title'];

        // Check the referrer to determine where to redirect
        if ($_SESSION['return_to_form']) {
            // User came from the form, so send them back there
            header("Location: step-by-step-add-book-form.php");
        } else {
            // User came from the progress page, so send them back there
            header("Location: step-by-step-add-book.php");
        }
        exit();
    }
}

// Get the search query if it exists - keep for URL parameter compatibility
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Only include header AFTER all potential redirects
include 'inc/header.php';

// Fetch ALL books from database for the table - we'll filter client-side
$booksQuery = "SELECT
    b.id,
    b.title,
    GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS author_name,
    p.publisher AS publisher_name
    FROM books b
    LEFT JOIN contributors c ON b.id = c.book_id AND c.role = 'Author'
    LEFT JOIN writers w ON c.writer_id = w.id
    LEFT JOIN publications pub ON b.id = pub.book_id
    LEFT JOIN publishers p ON pub.publisher_id = p.id
    GROUP BY b.id ORDER BY b.title";

$booksResult = $conn->query($booksQuery);
?>

<!-- Main Content -->
<div id="content">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Check Book Title & Add New Book</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <a href="step-by-step-add-book.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Progress Form
                    </a>
                </div>

                <div class="alert alert-info">
                    <p><strong>Step 3:</strong> Check if the book title already exists in the system. You can copy an existing title or enter a new one.</p>
                </div>

                <div class="alert alert-info">
                    <p><strong>Instructions:</strong> Check if your book title already exists in the system or add a new book title.</p>
                    <hr>
                    <p><strong>How to use this page:</strong></p>
                    <ul>
                        <li><strong>Search:</strong> Use the search box to check if your book title already exists.</li>
                        <li><strong>Copy existing title:</strong> If your book already exists, click the "Copy Title" button.</li>
                        <li><strong>Enter new title:</strong> If your book doesn't exist, enter the title in the form above.</li>
                        <li><strong>Next steps:</strong> After entering or copying a title, click "Save Book Title" to continue.</li>
                    </ul>
                    <p>After selecting a title, you'll be taken to a form where you can complete all the book details before saving.</p>
                </div>

                <!-- New Title Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">Add New Book</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="step-by-step-books.php">
                            <div class="row align-items-end">
                                <div class="col-md-8 mb-3 mb-md-0">
                                    <div class="form-group mb-0">
                                        <label for="new_title">Book Title:</label>
                                        <input type="text" class="form-control" id="new_title" name="new_title" value="<?php echo htmlspecialchars($_SESSION['book_shortcut']['book_title']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="proceed_to_add" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Save Book Title
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Search Existing Books -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">Check Existing Books</h6>
                    </div>
                    <div class="card-body px-0"> <!-- Remove padding for full-width scroll -->
                        <!-- Replace search form with real-time search input -->
                        <div class="form-group px-3 mb-3">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" id="bookSearch" class="form-control" placeholder="Search for existing books..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                        </div>

                        <!-- Books Table -->
                        <div class="table-responsive px-3"> <!-- Add padding inside scroll container -->
                            <table class="table table-bordered" id="booksTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th style="text-align: center">ID</th>
                                        <th style="text-align: center">Title</th>
                                        <th style="text-align: center">Author</th>
                                        <th style="text-align: center">Publisher</th>
                                        <th style="text-align: center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($booksResult && $booksResult->num_rows > 0) {
                                        while ($book = $booksResult->fetch_assoc()) {
                                            echo "<tr>
                                                <td style=\"text-align: center\">{$book['id']}</td>
                                                <td>{$book['title']}</td>
                                                <td>{$book['author_name']}</td>
                                                <td>{$book['publisher_name']}</td>
                                                <td style=\"text-align: center\">
                                                    <button type='button' class='btn btn-info btn-sm copy-title'
                                                            data-title='" . htmlspecialchars($book['title'], ENT_QUOTES) . "'>
                                                        <i class='fas fa-copy'></i> Copy Title
                                                    </button>
                                                </td>
                                            </tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Add DataTables styling similar to book_list.php */
#booksTable th,
#booksTable td {
    min-width: 100px;
    white-space: nowrap;
}

/* Make the title column wider */
#booksTable th:nth-child(2),
#booksTable td:nth-child(2) {
    min-width: 250px;
}

/* Author column */
#booksTable th:nth-child(3),
#booksTable td:nth-child(3) {
    min-width: 180px;
}

/* Publisher column */
#booksTable th:nth-child(4),
#booksTable td:nth-child(4) {
    min-width: 180px;
}

/* Make sure the table stretches full width */
#booksTable {
    width: 100% !important;
}

/* Add responsive table handling */
.table-responsive {
    width: 100%;
    margin-bottom: 1rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
</style>

<script>
$(document).ready(function() {
    // Initialize DataTable with search enabled
    var booksTable = $('#booksTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7'p>>",
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
        "responsive": false, // Disable DataTables responsive handling
        "scrollX": true,     // Enable horizontal scrolling
        "order": [[1, "asc"]], // Sort by title by default
        "columnDefs": [
            { "orderable": true, "targets": [0, 1, 2, 3] },
            { "orderable": false, "targets": 4 } // Action column not sortable
        ],
        "searching": true,  // Enable client-side searching
        "info": true
    });

    // Link our custom search box to DataTables search
    $('#bookSearch').on('keyup', function() {
        booksTable.search(this.value).draw();
    });

    // Set initial search value if provided
    if ($('#bookSearch').val()) {
        booksTable.search($('#bookSearch').val()).draw();
    }

    <?php if (isset($_POST['copy_title'])): ?>
    // If a title was copied, update the input field
    $('#new_title').val('<?php echo addslashes($_SESSION['book_shortcut']['book_title']); ?>');
    // Show alert
    Swal.fire({
        icon: 'success',
        title: 'Title Copied',
        text: 'The book title has been copied to the form.',
        timer: 2000,
        showConfirmButton: false
    });
    <?php endif; ?>
});

// Handle title copy clicks
$(document).on('click', '.copy-title', function() {
    const title = $(this).data('title');

    // Send AJAX request
    $.ajax({
        url: 'step-by-step-books.php',
        method: 'POST',
        data: {
            copy_title: true,
            book_title: title
        },
        success: function() {
            // Show brief success message
            Swal.fire({
                icon: 'success',
                title: 'Title Copied',
                text: 'Redirecting...',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                // Check where to redirect based on the return_to_form value
                const returnToForm = <?php echo json_encode($_SESSION['return_to_form']); ?>;
                window.location.href = returnToForm ? 'step-by-step-add-book-form.php' : 'step-by-step-add-book.php';
            });
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to copy title. Please try again.',
                confirmButtonColor: '#3085d6'
            });
        }
    });
});
</script>

<?php include 'inc/footer.php'; ?>
