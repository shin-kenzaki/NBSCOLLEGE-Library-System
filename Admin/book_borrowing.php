<?php
session_start();
include('inc/header.php');
include('../db.php');

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant'])) {
    header("Location: index.php");
    exit();
}
?>

<!-- Main Content -->
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Book Borrowing</h1>
        </div>
        
        <!-- Instructions Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle mr-2"></i>Instructions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="font-weight-bold">Borrowing Rules:</h6>
                        <ul class="mb-0">
                            <li>Students can borrow up to 3 books</li>
                            <li>Reference (REF) books are for same-day use only</li>
                            <li>Reserved (RES) books must be returned next day</li>
                            <li>Regular books can be borrowed for 7 days</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold">How to use:</h6>
                        <ol class="mb-0">
                            <li>Search for borrower using ID or name</li>
                            <li>Scan book barcode or search by title</li>
                            <li>Verify selected items are correct</li>
                            <li>Click "Process Borrowing" to complete</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Book Borrowing Form</h6>
                <button type="submit" form="borrowingForm" class="btn btn-primary">
                    <i class="fas fa-check-circle mr-2"></i>Process Borrowing
                </button>
            </div>
            <div class="card-body">
                <form id="borrowingForm" method="POST" action="process_borrowing.php">
                    <div class="row">
                        <!-- Borrower Section -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Borrower Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="userSearch" class="form-label">
                                            <i class="fas fa-search mr-2"></i>Search Borrower
                                            <i class="fas fa-question-circle text-muted" data-toggle="tooltip" 
                                               title="Enter ID number or name. You can also scan the borrower's ID card."></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="userSearch" 
                                                   placeholder="Scan ID or enter ID number/name">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="user_id" class="form-label">
                                            <i class="fas fa-user-check mr-2"></i>Select Borrower
                                        </label>
                                        <select class="form-control" id="user_id" name="user_id" required>
                                            <option value="" disabled selected>Choose a borrower...</option>
                                            <?php
                                            $users_query = "SELECT id, school_id, firstname, middle_init, lastname FROM users WHERE status IS NULL OR status IN ('1', '0')";
                                            $users_result = $conn->query($users_query);
                                            while($user = $users_result->fetch_assoc()): ?>
                                                <option value="<?php echo $user['id']; ?>" 
                                                        data-school-id="<?php echo $user['school_id']; ?>">
                                                    <?php echo htmlspecialchars($user['school_id'] . ' - ' . 
                                                                            $user['firstname'] . ' ' . 
                                                                            ($user['middle_init'] ? $user['middle_init'] . '. ' : '') . 
                                                                            $user['lastname']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <!-- Borrower Preview Card -->
                                    <div id="borrowerPreview" class="mt-3 d-none">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-subtitle mb-2 text-muted">Selected Borrower</h6>
                                                <div id="borrowerDetails"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Book Section -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-book mr-2"></i>Book Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="bookSearch" class="form-label">
                                            <i class="fas fa-search mr-2"></i>Search Book
                                            <i class="fas fa-question-circle text-muted" data-toggle="tooltip" 
                                               title="Scan book barcode or enter book title/accession number"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="bookSearch" 
                                                   placeholder="Scan barcode or enter book details">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="book_id" class="form-label">
                                            <i class="fas fa-books mr-2"></i>Select Books
                                        </label>
                                        <select class="form-control" id="book_id" name="book_id[]" multiple required>
                                            <option value="" disabled selected>Choose a book...</option>
                                            <?php
                                            $books_query = "SELECT id, title, accession, shelf_location FROM books WHERE status = 'Available'";
                                            $books_result = $conn->query($books_query);
                                            while($book = $books_result->fetch_assoc()): ?>
                                                <option value="<?php echo $book['id']; ?>">
                                                    <?php echo htmlspecialchars($book['accession'] . ' (' . $book['shelf_location'] . ') - ' . $book['title']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Hold Ctrl/Cmd to select multiple books
                                        </small>
                                    </div>
                                    <!-- Selected Books Preview -->
                                    <div class="mt-3">
                                        <h6 class="text-muted mb-2">Selected Books</h6>
                                        <div id="selectedBooksPreview" class="border rounded p-2 min-height-100">
                                            <div class="text-muted text-center" id="noBooksSelected">
                                                No books selected
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('inc/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Focus on book search by default
    $('#bookSearch').focus();

    // Initialize barcode scanner handling
    let lastChar = Date.now();
    let barcodeString = '';
    const delay = 50;

    $(document).on('keypress', function(e) {
        const currentTime = Date.now();
        const charStr = String.fromCharCode(e.which);

        // Only process if we're not typing in other input fields
        if ($('input:text, textarea').is(':focus') && !$('#bookSearch').is(':focus')) {
            return;
        }

        // Reset barcode if too much time has passed
        if (currentTime - lastChar > 1000) {
            barcodeString = '';
        }

        // Add character to barcode string
        if (currentTime - lastChar <= delay || barcodeString.length === 0) {
            barcodeString += charStr;
        } else {
            barcodeString = charStr;
        }

        lastChar = currentTime;

        // Most barcode scanners send an "Enter" key (13) at the end
        if (e.which === 13) {
            e.preventDefault();
            
            // Remove leading zeros from barcode
            const trimmedBarcode = barcodeString.replace(/^0+/, '');
            
            // Focus and populate the book search input
            $('#bookSearch').focus().val(trimmedBarcode);
            
            // Search book by accession number using AJAX
            $.ajax({
                url: 'search_book_by_accession.php',
                type: 'POST',
                data: { accession: trimmedBarcode },
                success: function(response) {
                    try {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            $('#book_id').val(res.book.id);
                            
                            Swal.fire({
                                title: 'Book Found',
                                html: `Found book:<br><b>${res.book.title}</b>`,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                title: 'Book Not Found',
                                text: 'No available book found with accession number: ' + trimmedBarcode,
                                icon: 'error'
                            });
                            $('#bookSearch').val('');
                            $('#book_id').val('');
                        }
                    } catch (e) {
                        console.error(e);
                        Swal.fire({
                            title: 'Error',
                            text: 'Invalid response from server',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Server error: ' + error,
                        icon: 'error'
                    });
                }
            });
            
            barcodeString = '';
        }
    });

    // Filter book dropdown based on search input
    $('#bookSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        // Store currently selected values
        const selectedValues = $('#book_id').val() || [];
        
        // Reset and hide all options first
        $('#book_id option:not(:first)').hide();
        
        // Show matching options
        $('#book_id option').each(function() {
            if ($(this).text().toLowerCase().includes(searchTerm)) {
                $(this).show();
            }
        });

        // Re-select previously selected values
        $('#book_id').val(selectedValues);

        // Auto-select if exact match found (only if no books are currently selected)
        if (selectedValues.length === 0) {
            const exactMatch = $('#book_id option').filter(function() {
                return $(this).text().toLowerCase().startsWith(searchTerm);
            }).first();

            if (exactMatch.length) {
                $('#book_id').val([exactMatch.val()]);
                updateSelectedBooksPreview();
            }
        }
    });

    // Function to update the preview section
    function updateSelectedBooksPreview() {
        const selectedOptions = $('#book_id option:selected');
        const previewList = $('#selectedBooksPreview');
        previewList.empty();
        
        selectedOptions.each(function() {
            const book = $(this).text();
            previewList.append(`
                <div class="badge bg-secondary p-2 m-1">
                    <span class="text-white">${book}</span>
                    <i class="fas fa-times ml-2 remove-book" style="cursor:pointer" data-value="${book}"></i>
                </div>
            `);
        });
    }

    // Update preview when dropdown selection changes
    $('#book_id').on('change', function() {
        const selectedCount = $(this).val() ? $(this).val().length : 0;
        const userType = $('#user_id option:selected').data('usertype');
        
        // Check book limit for students
        if (userType === 'Student' && selectedCount > 3) {
            Swal.fire({
                title: 'Limit Exceeded',
                text: 'Students can only borrow up to 3 books at a time',
                icon: 'warning'
            });
            // Reset selection
            $(this).val($(this).val().slice(0, 3));
        }
        
        // Update preview after validation
        updateSelectedBooksPreview();
    });

    // Handle removal of selected books
    $(document).on('click', '.remove-book', function() {
        const bookText = $(this).data('value');
        const bookOption = $('#book_id option').filter(function() {
            return $(this).text().trim() === bookText;
        });
        
        bookOption.prop('selected', false);
        updateSelectedBooksPreview();
    });

    // Filter user dropdown based on search input
    $('#userSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        // Reset and hide all options first
        $('#user_id option:not(:first)').hide();
        
        // Show matching options
        $('#user_id option').each(function() {
            const schoolId = $(this).data('school-id');
            const fullName = $(this).text().toLowerCase();
            if (schoolId && (schoolId.toString().includes(searchTerm) || fullName.includes(searchTerm))) {
                $(this).show();
            }
        });

        // Auto-select if exact match found
        const exactMatch = $('#user_id option').filter(function() {
            const schoolId = $(this).data('school-id');
            return schoolId && schoolId.toString() === searchTerm;
        }).first();

        if (exactMatch.length) {
            $('#user_id').val(exactMatch.val());
        } else {
            $('#user_id').val(''); // Reset selection if no exact match
        }
    });

    // Update text inputs when dropdowns change
    $('#book_id').on('change', function() {
        const selectedOptions = $(this).find('option:selected');
        const selectedBooks = selectedOptions.map(function() {
            return $(this).text();
        }).get();

        // Update the book search input with the first selected book's accession number
        if (selectedBooks.length > 0) {
            const firstSelectedBook = selectedBooks[0].split(' - ')[0];
            $('#bookSearch').val(firstSelectedBook);
        } else {
            $('#bookSearch').val('');
        }

        // Update the preview section
        const previewList = $('#selectedBooksPreview');
        previewList.empty();
        selectedBooks.forEach(function(book) {
            previewList.append('<span class="badge bg-secondary mr-1 text-white">' + book + ' <i class="fas fa-times remove-book" data-value="' + book + '"></i></span>');
        });
    });

    $('#user_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const schoolId = selectedOption.data('school-id');
        $('#userSearch').val(schoolId || '');
    });

    // SweetAlert confirmation and form submission
    $('#borrowingForm').on('submit', function(e) {
        e.preventDefault();
        
        const selectedBooks = $('#book_id option:selected').map(function() {
            return $(this).text();
        }).get().join(', ');
        const selectedBorrower = $('#user_id option:selected').text();

        if (!$('#book_id').val() || !$('#user_id').val()) {
            Swal.fire({
                title: 'Error!',
                text: 'Please select both a book and a borrower',
                icon: 'error'
            });
            return;
        }

        Swal.fire({
            title: 'Confirm Borrowing',
            html: `Are you sure you want to lend:<br>
                  <b>${selectedBooks}</b><br>
                  to<br>
                  <b>${selectedBorrower}</b>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'process_borrowing.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        try {
                            const res = JSON.parse(response);
                            if (res.status === 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    text: res.message,
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Cannot Proceed!',
                                    text: res.message,
                                    icon: 'error',
                                    confirmButtonColor: '#d33'
                                });
                            }
                        } catch (e) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Invalid server response',
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Server error: ' + error,
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            }
        });
    });
});
</script>