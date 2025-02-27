<?php
session_start();
include('inc/header.php');
include('../db.php');

if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Librarian')) {
    header('Location: login.php');
    exit();
}
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Book Borrowing</h1>
        </div>
        
        <!-- Card starts here -->
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Book Borrowing Form</h6>
                <button type="submit" form="borrowingForm" class="btn btn-primary">Process Borrowing</button>
            </div>
            <div class="card-body">
                <form id="borrowingForm" method="POST" action="process_borrowing.php">
                    <!-- Borrower Section -->
                    <div class="mb-4">
                        <h5 class="mb-3">Borrower Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="userSearch" class="form-label">Search Borrower</label>
                                    <input type="text" class="form-control" id="userSearch" placeholder="Enter ID number or name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">Select Borrower</label>
                                    <select class="form-control" id="user_id" name="user_id" required>
                                        <option value="">Choose a borrower...</option>
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
                            </div>
                        </div>
                    </div>

                    <!-- Book Section -->
                    <div class="mb-4">
                        <h5 class="mb-3">Book Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bookSearch" class="form-label">Search Book</label>
                                    <input type="text" class="form-control" id="bookSearch" placeholder="Enter book ID, accession, or title">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="book_id" class="form-label">Select Book</label>
                                    <select class="form-control" id="book_id" name="book_id" required>
                                        <option value="">Choose a book...</option>
                                        <?php
                                        $books_query = "SELECT id, title, accession FROM books WHERE status = 'Available'";
                                        $books_result = $conn->query($books_query);
                                        while($book = $books_result->fetch_assoc()): ?>
                                            <option value="<?php echo $book['id']; ?>">
                                                <?php echo htmlspecialchars($book['accession'] . ' - ' . $book['title']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
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
        } else {
            $('#book_id').val(''); // Reset selection if no exact match
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

    // Update text inputs when dropdowns change
    $('#book_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const accessionNumber = selectedOption.text().split(' - ')[1];
        $('#bookSearch').val(accessionNumber);
    });

    $('#user_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const schoolId = selectedOption.data('school-id');
        $('#userSearch').val(schoolId || '');
    });

    // SweetAlert confirmation and form submission
    $('#borrowingForm').on('submit', function(e) {
        e.preventDefault();
        
        const selectedBook = $('#book_id option:selected').text();
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
                  <b>${selectedBook}</b><br>
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