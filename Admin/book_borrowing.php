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
                        <h6 class="font-weight-bold text-primary">Borrowing Rules:</h6>
                        <ul class="mb-3">
                            <li><strong>Students:</strong> Maximum of 3 books at a time</li>
                            <li><strong>Faculty/Staff:</strong> Maximum of 5 books at a time</li>
                            <li><strong>Regular Books (CIR):</strong> 7-day borrowing period</li>
                            <li><strong>Reference Books (REF):</strong> Same-day use only (must return by 4:00pm)</li>
                            <li><strong>Reserved Books (RES):</strong> Next-day return (24-hour period)</li>
                            <li><strong>Textbooks (TR):</strong> 3-day borrowing period</li>
                        </ul>
                        <div class="alert alert-warning small py-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Students with overdue books or unpaid fines cannot borrow additional items.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold text-primary">How to Process a Loan:</h6>
                        <ol class="mb-0">
                            <li>Enter borrower's ID number in the "Search Borrower" field or scan their ID card</li>
                            <li>Verify borrower's information is correct after selection</li>
                            <li>Scan book barcode or search by title/accession number</li>
                            <li>Select multiple books as needed (respecting borrower limits)</li>
                            <li>Verify all information is correct in the preview section</li>
                            <li>Click "Process Borrowing" to complete the transaction</li>
                            <li>Print receipt for the borrower if needed</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Book Borrowing Form</h6>
                <div>
                    <a href="borrowed_books.php" class="btn btn-info mr-2">
                        <i class="fas fa-list mr-1"></i> View Issued Books
                    </a>
                    <button type="submit" form="borrowingForm" class="btn btn-primary">
                        <i class="fas fa-check-circle mr-2"></i>Process Borrowing
                    </button>
                </div>
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
                                            <i class="fas fa-book mr-2"></i>Select Book
                                        </label>
                                        <div class="input-group">
                                            <select class="form-control" id="book_id">
                                                <option value="" disabled selected>Choose a book...</option>
                                                <?php
                                                // Enhanced query to fetch additional book details
                                                $books_query = "SELECT b.id, b.title, b.accession, b.shelf_location, 
                                                                b.series, b.volume, b.part, b.edition, b.copy_number, 
                                                                b.content_type, b.media_type, b.call_number, 
                                                                GROUP_CONCAT(DISTINCT CONCAT(w.firstname, ' ', w.middle_init, ' ', w.lastname) SEPARATOR ', ') AS authors,
                                                                GROUP_CONCAT(DISTINCT p.publisher SEPARATOR ', ') AS publishers,
                                                                MAX(pub.publish_date) AS publish_year
                                                                FROM books b
                                                                LEFT JOIN contributors c ON b.id = c.book_id
                                                                LEFT JOIN writers w ON c.writer_id = w.id
                                                                LEFT JOIN publications pub ON b.id = pub.book_id
                                                                LEFT JOIN publishers p ON pub.publisher_id = p.id
                                                                WHERE b.status = 'Available'
                                                                GROUP BY b.id, b.title, b.accession, b.shelf_location
                                                                ORDER BY b.accession";
                                                $books_result = $conn->query($books_query);
                                                while($book = $books_result->fetch_assoc()): 
                                                    // Prepare book details with enhanced information for display
                                                    $bookDisplay = $book['accession'] . ' (' . $book['shelf_location'] . ') - ' . $book['title'];
                                                    
                                                    // Add additional details like series, volume, part to the display text
                                                    $additionalDetails = [];
                                                    if (!empty($book['series'])) $additionalDetails[] = "Series: " . $book['series'];
                                                    if (!empty($book['volume'])) $additionalDetails[] = "Vol. " . $book['volume'];
                                                    if (!empty($book['part'])) $additionalDetails[] = "Pt. " . $book['part'];
                                                    if (!empty($book['edition'])) $additionalDetails[] = $book['edition'] . " Ed.";
                                                    if (!empty($book['copy_number'])) $additionalDetails[] = "Copy " . $book['copy_number'];
                                                    
                                                    // Add the additional details to display text if available
                                                    if (!empty($additionalDetails)) {
                                                        $bookDisplay .= ' [' . implode(' | ', $additionalDetails) . ']';
                                                    }
                                                ?>
                                                    <option value="<?php echo $book['id']; ?>"
                                                            data-accession="<?php echo $book['accession']; ?>"
                                                            data-title="<?php echo htmlspecialchars($book['title']); ?>"
                                                            data-location="<?php echo $book['shelf_location']; ?>"
                                                            data-series="<?php echo htmlspecialchars($book['series'] ?? ''); ?>"
                                                            data-volume="<?php echo htmlspecialchars($book['volume'] ?? ''); ?>"
                                                            data-part="<?php echo htmlspecialchars($book['part'] ?? ''); ?>"
                                                            data-edition="<?php echo htmlspecialchars($book['edition'] ?? ''); ?>"
                                                            data-copy="<?php echo htmlspecialchars($book['copy_number'] ?? ''); ?>"
                                                            data-call-number="<?php echo htmlspecialchars($book['call_number'] ?? ''); ?>"
                                                            data-content-type="<?php echo htmlspecialchars($book['content_type'] ?? ''); ?>"
                                                            data-media-type="<?php echo htmlspecialchars($book['media_type'] ?? ''); ?>"
                                                            data-authors="<?php echo htmlspecialchars($book['authors'] ?? ''); ?>"
                                                            data-publishers="<?php echo htmlspecialchars($book['publishers'] ?? ''); ?>"
                                                            data-year="<?php echo htmlspecialchars($book['publish_year'] ?? ''); ?>">
                                                        <?php echo htmlspecialchars($bookDisplay); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <div class="input-group-append">
                                                <button type="button" id="addBookBtn" class="btn btn-success">
                                                    <i class="fas fa-plus"></i> Add
                                                </button>
                                            </div>
                                        </div>
                                        <!-- Hidden input to store selected book IDs -->
                                        <div id="selectedBooksInputs"></div>
                                    </div>
                                    <!-- Selected Books Preview -->
                                    <div class="mt-3">
                                        <h6 class="text-muted mb-2">Selected Books <span class="badge badge-info" id="bookCount">0</span></h6>
                                        <div id="selectedBooksPreview" class="border rounded p-2" style="min-height: 150px; max-height: 300px; overflow-y: auto;">
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

    // To store selected books
    let selectedBooks = [];

    // Initialize barcode scanner handling
    let lastChar = Date.now();
    let barcodeString = '';
    const delay = 50;

    // Prevent form submission on Enter key press
    $(document).on('keydown', function(e) {
        if (e.which === 13 && !$(e.target).is('textarea')) {
            e.preventDefault();
        }
    });

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
                            // Add the book to our selection with all available details
                            addBookToSelection({
                                id: res.book.id,
                                title: res.book.title,
                                accession: res.book.accession,
                                location: res.book.shelf_location,
                                series: res.book.series,
                                volume: res.book.volume,
                                part: res.book.part,
                                edition: res.book.edition,
                                copy: res.book.copy_number,
                                callNumber: res.book.call_number,
                                contentType: res.book.content_type,
                                mediaType: res.book.media_type,
                                authors: res.book.authors,
                                publishers: res.book.publishers,
                                year: res.book.publish_year
                            });
                            
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

    // Add Book button click handler
    $('#addBookBtn').on('click', function() {
        const bookSelect = $('#book_id');
        const selectedOption = bookSelect.find('option:selected');
        
        if (bookSelect.val()) {
            const bookData = {
                id: bookSelect.val(),
                title: selectedOption.data('title'),
                accession: selectedOption.data('accession'),
                location: selectedOption.data('location'),
                // Add additional book details
                series: selectedOption.data('series'),
                volume: selectedOption.data('volume'),
                part: selectedOption.data('part'),
                edition: selectedOption.data('edition'),
                copy: selectedOption.data('copy'),
                callNumber: selectedOption.data('call-number'),
                contentType: selectedOption.data('content-type'),
                mediaType: selectedOption.data('media-type'),
                authors: selectedOption.data('authors'),
                publishers: selectedOption.data('publishers'),
                year: selectedOption.data('year')
            };
            
            addBookToSelection(bookData);
            
            // Reset the dropdown
            bookSelect.val('');
        } else {
            Swal.fire({
                title: 'No Book Selected',
                text: 'Please select a book to add',
                icon: 'warning'
            });
        }
    });

    // Function to add a book to the selection
    function addBookToSelection(bookData) {
        // Check if book is already in the selection
        if (selectedBooks.some(book => book.id === bookData.id)) {
            Swal.fire({
                title: 'Already Added',
                text: 'This book is already in your selection',
                icon: 'info',
                timer: 1500,
                showConfirmButton: false
            });
            return;
        }
        
        // Check borrowing limits for students
        const userType = $('#user_id option:selected').data('usertype');
        if (userType === 'Student' && selectedBooks.length >= 3) {
            Swal.fire({
                title: 'Limit Exceeded',
                text: 'Students can only borrow up to 3 books at a time',
                icon: 'warning'
            });
            return;
        }
        
        // Add to our collection
        selectedBooks.push(bookData);
        
        // Update the UI
        updateSelectedBooksUI();
    }

    // Function to remove a book from selection
    function removeBookFromSelection(bookId) {
        selectedBooks = selectedBooks.filter(book => book.id !== bookId);
        updateSelectedBooksUI();
    }

    // Update the UI to reflect the current selection
    function updateSelectedBooksUI() {
        const previewContainer = $('#selectedBooksPreview');
        const noBooks = $('#noBooksSelected');
        const inputsContainer = $('#selectedBooksInputs');
        
        // Update book count
        $('#bookCount').text(selectedBooks.length);
        
        // Clear existing content
        previewContainer.find('.book-item').remove();
        inputsContainer.empty();
        
        if (selectedBooks.length > 0) {
            noBooks.hide();
            
            // Add each book to the preview
            selectedBooks.forEach((book, index) => {
                // Construct detailed description
                let detailsHtml = '';
                if (book.authors) detailsHtml += `<div><strong>Author(s):</strong> ${book.authors}</div>`;
                
                let identifiers = [];
                if (book.series) identifiers.push(`<span class="text-primary">Series: ${book.series}</span>`);
                if (book.volume) identifiers.push(`<span class="text-primary">Vol. ${book.volume}</span>`);
                if (book.part) identifiers.push(`<span class="text-primary">Pt. ${book.part}</span>`);
                if (book.edition) identifiers.push(`<span class="text-primary">${book.edition} Ed.</span>`);
                if (book.copy) identifiers.push(`<span class="text-primary">Copy ${book.copy}</span>`);
                
                if (identifiers.length > 0) {
                    detailsHtml += `<div class="small mt-1">${identifiers.join(' | ')}</div>`;
                }
                
                let publishInfo = [];
                if (book.publishers) publishInfo.push(book.publishers);
                if (book.year) publishInfo.push(book.year);
                
                if (publishInfo.length > 0) {
                    detailsHtml += `<div class="small text-muted">${publishInfo.join(', ')}</div>`;
                }
                
                if (book.callNumber) {
                    detailsHtml += `<div class="small"><strong>Call Number:</strong> ${book.callNumber}</div>`;
                }
                
                let mediaInfo = [];
                if (book.contentType) mediaInfo.push(book.contentType);
                if (book.mediaType) mediaInfo.push(book.mediaType);
                
                if (mediaInfo.length > 0) {
                    detailsHtml += `<div class="small text-muted">${mediaInfo.join(' | ')}</div>`;
                }
                
                const bookElement = $(`
                    <div class="book-item card mb-2">
                        <div class="card-header py-2 px-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-primary">ACC# ${book.accession}</strong>
                                    <span class="badge badge-${getLocationBadgeClass(book.location)} ml-2">${book.location}</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-book" data-id="${book.id}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body py-2 px-3">
                            <h6 class="mb-1">${book.title}</h6>
                            ${detailsHtml}
                        </div>
                    </div>
                `);
                
                previewContainer.append(bookElement);
                
                // Add hidden input for form submission
                inputsContainer.append(`<input type="hidden" name="book_id[]" value="${book.id}">`);
            });
        } else {
            noBooks.show();
        }
    }
    
    // Helper function to get badge class based on location
    function getLocationBadgeClass(location) {
        switch(location) {
            case 'REF': return 'danger';
            case 'RES': return 'warning';
            case 'TR': return 'info';
            case 'CIR': return 'success';
            default: return 'secondary';
        }
    }

    // Handle removal of selected books
    $(document).on('click', '.remove-book', function() {
        const bookId = $(this).data('id');
        removeBookFromSelection(bookId);
    });

    // Filter book dropdown based on search input
    $('#bookSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        // Reset and hide all options first
        $('#book_id option:not(:first)').hide();
        
        // Show matching options
        $('#book_id option').each(function() {
            if ($(this).text().toLowerCase().includes(searchTerm)) {
                $(this).show();
            }
        });

        // Auto-select if exact match found
        const exactMatch = $('#book_id option').filter(function() {
            return $(this).text().toLowerCase().startsWith(searchTerm);
        }).first();

        if (exactMatch.length) {
            $('#book_id').val(exactMatch.val());
        }
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

    // Update user search input when dropdown changes
    $('#user_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const schoolId = selectedOption.data('school-id');
        $('#userSearch').val(schoolId || '');
    });

    // SweetAlert confirmation and form submission
    $('#borrowingForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!$('#user_id').val()) {
            Swal.fire({
                title: 'Error!',
                text: 'Please select a borrower',
                icon: 'error'
            });
            return;
        }
        
        if (selectedBooks.length === 0) {
            Swal.fire({
                title: 'Error!',
                text: 'Please add at least one book',
                icon: 'error'
            });
            return;
        }

        const selectedBorrower = $('#user_id option:selected').text();
        const booksText = selectedBooks.map(book => `${book.accession} - ${book.title}`).join('<br>');
        
        Swal.fire({
            title: 'Confirm Borrowing',
            html: `Are you sure you want to lend:<br><br>
                  <b>${booksText}</b><br><br>
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
                                // Format book titles for display
                                let bookListHtml = '';
                                if (res.books && res.books.length > 0) {
                                    bookListHtml = '<ul style="text-align: left; padding-left: 20px;">';
                                    res.books.forEach(book => {
                                        bookListHtml += `<li><strong>Accession ${book.accession}</strong>: ${book.title}</li>`;
                                    });
                                    bookListHtml += '</ul>';
                                }
                                
                                Swal.fire({
                                    title: 'Success!',
                                    html: `
                                        <div style="margin-bottom: 15px;">
                                            <strong>${res.borrower.name}</strong> (ID: ${res.borrower.school_id}) 
                                            has successfully borrowed the following book(s):
                                        </div>
                                        ${bookListHtml}
                                    `,
                                    icon: 'success',
                                    showDenyButton: true,
                                    confirmButtonText: 'OK',
                                    denyButtonText: 'Check Issued Books',
                                    denyButtonColor: '#28a745',
                                }).then((result) => {
                                    if (result.isDenied) {
                                        // Redirect to borrowed books page
                                        window.location.href = 'borrowed_books.php';
                                    } else {
                                        // Stay on the current page but reset the form
                                        $('#bookSearch').val('');
                                        $('#book_id').val('');
                                        selectedBooks = [];
                                        updateSelectedBooksUI();
                                    }
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

    // Add explicit button click handler for the submit button
    $('button[type="submit"]').on('click', function() {
        $('#borrowingForm').submit();
    });
});
</script>