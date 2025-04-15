<?php
session_start();
include '../db.php';

// Determine if the user is logged in or a guest
$isLoggedIn = isset($_SESSION['user_id']);

// Get search parameters
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$program = isset($_GET['program']) ? $_GET['program'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Prepare SQL query based on search parameters - modified to group books
$sql = "SELECT 
    book_counts.id, 
    book_counts.title, 
    book_counts.total_copies,
    book_counts.available_copies,
    book_counts.subject_category, 
    book_counts.program,
    book_counts.ISBN,
    book_counts.series,
    book_counts.volume,
    book_counts.part,
    book_counts.edition,
    book_counts.front_image,
    book_counts.summary,
    writers.firstname, 
    writers.middle_init, 
    writers.lastname 
FROM (
    SELECT
        MIN(id) as id,
        title,
        COUNT(*) as total_copies,
        SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available_copies,
        subject_category,
        program,
        ISBN,
        series,
        volume,
        part,
        edition,
        front_image,
        summary
    FROM books
    WHERE 1=1";

$params = array();
$types = "";

if (!empty($searchTerm)) {
    $sql .= " AND (title LIKE ? OR accession LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

if (!empty($program)) {
    $sql .= " AND program = ?";
    $params[] = $program;
    $types .= "s";
}

if (!empty($category)) {
    $sql .= " AND subject_category = ?";
    $params[] = $category;
    $types .= "s";
}

// Group by the attributes that identify unique books
$sql .= " GROUP BY 
        title, 
        IFNULL(ISBN, ''),
        IFNULL(series, ''),
        IFNULL(volume, ''),
        IFNULL(part, ''),
        IFNULL(edition, '')
) book_counts
LEFT JOIN contributors ON book_counts.id = contributors.book_id 
LEFT JOIN writers ON contributors.writer_id = writers.id";

// Add author search if not already included
if (!empty($searchTerm)) {
    $sql .= " WHERE writers.firstname LIKE ? OR writers.lastname LIKE ?";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

$sql .= " GROUP BY 
    book_counts.id
    ORDER BY book_counts.title";

// Get all unique programs and categories for filter dropdowns
$programsQuery = "SELECT DISTINCT program FROM books WHERE program IS NOT NULL AND program != '' ORDER BY program";
$programsResult = $conn->query($programsQuery);

$categoriesQuery = "SELECT DISTINCT subject_category FROM books WHERE subject_category IS NOT NULL AND subject_category != '' ORDER BY subject_category";
$categoriesResult = $conn->query($categoriesQuery);

// Execute search query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$searchResults = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php if (!$isLoggedIn): ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Catalog Search</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <?php endif; ?>
    <!-- Custom styles -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #2e59d9;
            --light-bg: #f8f9fc;
        }
        
        body {
            background-color: var(--light-bg);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 3rem 0;
            margin-bottom: 2rem;
            color: white;
            border-radius: 0 0 1rem 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .search-container {
            background-color: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 2rem;
            position: relative;
            margin-top: -4rem;
        }
        
        .result-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 0.5rem;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .card-img-top {
            height: 200px;
            object-fit: cover;
            background-color: #e9ecef;
        }
        
        .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        
        .card-title {
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
            line-height: 1.4;
            height: 3rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .card-author {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            height: 1.5rem;
            overflow: hidden;
        }
        
        .card-text {
            color: #6c757d;
            font-size: 0.9rem;
            height: 4.5rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        
        .card-details-row {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 0.85rem;
        }
        
        .card-details {
            display: flex;
            flex-direction: column;
            color: #6c757d;
        }
        
        .card-details span:first-child {
            font-weight: 600;
            color: #495057;
        }
        
        .availability {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .available {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .unavailable {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .btn-view {
            width: 100%;
            margin-top: 1rem;
        }
        
        .search-count {
            font-size: 1.2rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 1.5rem;
        }
        
        .filter-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #495057;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        /* Guest notification banner */
        .guest-banner {
            background-color: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
            text-align: center;
        }
        
        .guest-banner .btn {
            margin-left: 0.5rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .search-container {
                margin-top: -2rem;
            }
            
            .hero-section {
                padding: 2rem 0;
            }
            
            .card-img-top {
                height: 160px;
            }
        }
        
        /* Book action buttons */
        .book-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }
        
        .book-actions .btn {
            flex: 1;
        }
    </style>
</head>
<body>
    <?php 
    // Include the appropriate header based on login status
    if ($isLoggedIn) {
        include 'inc/header.php';
    } else {
        // Show guest notification banner
        echo '<nav class="navbar navbar-expand-lg navbar-light bg-light shadow mb-4">
                <div class="container">
                    <a class="navbar-brand" href="#">NBS College Library</a>
                    <div class="ml-auto">
                        <a href="index.php" class="btn btn-primary btn-sm">Login</a>
                        <a href="register.php" class="btn btn-outline-primary btn-sm ml-2">Register</a>
                    </div>
                </div>
              </nav>';
        
        echo '<div class="container mt-3">
              <div class="guest-banner">
                You are browsing as a guest. 
                <a href="index.php" class="btn btn-sm btn-outline-danger">Log In</a> or 
                <a href="register.php" class="btn btn-sm btn-outline-danger">Register</a> 
                to borrow or reserve books.
              </div>
            </div>';
    }
    ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4">Library Catalog</h1>
            <p class="lead">Search for books in our collection</p>
        </div>
    </div>

    <div class="container">
        <!-- Search Form Container -->
        <div class="search-container">
            <form method="GET" action="searchbook.php" class="row g-3">
                <div class="col-md-12 mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-primary"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search by title, accession, or author...">
                    </div>
                </div>
                
                <div class="col-md-5">
                    <label for="program" class="filter-label">Program</label>
                    <select class="form-select" name="program" id="program">
                        <option value="">All Programs</option>
                        <?php while ($row = $programsResult->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['program']); ?>" <?php if ($program === $row['program']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($row['program']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-5">
                    <label for="category" class="filter-label">Category</label>
                    <select class="form-select" name="category" id="category">
                        <option value="">All Categories</option>
                        <?php while ($row = $categoriesResult->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['subject_category']); ?>" <?php if ($category === $row['subject_category']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($row['subject_category']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Search Results -->
        <div class="search-count">
            <?php echo $searchResults->num_rows; ?> books found
            <?php if (!empty($searchTerm) || !empty($program) || !empty($category)): ?>
                <?php 
                    $filterText = [];
                    if (!empty($searchTerm)) $filterText[] = "\"" . htmlspecialchars($searchTerm) . "\"";
                    if (!empty($program)) $filterText[] = "Program: " . htmlspecialchars($program);
                    if (!empty($category)) $filterText[] = "Category: " . htmlspecialchars($category);
                    if (!empty($filterText)) echo " for " . implode(", ", $filterText);
                ?>
            <?php endif; ?>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
            <?php if ($searchResults->num_rows > 0): ?>
                <?php while ($book = $searchResults->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card result-card h-100">
                            <?php if (!empty($book['front_image'])): ?>
                                <img src="<?php echo htmlspecialchars($book['front_image']); ?>" class="card-img-top" alt="Book Cover">
                            <?php else: ?>
                                <div class="card-img-top d-flex align-items-center justify-content-center">
                                    <i class="fas fa-book fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="availability <?php echo ($book['available_copies'] > 0) ? 'available' : 'unavailable'; ?>">
                                        <?php echo ($book['available_copies'] > 0) ? 'Available' : 'Unavailable'; ?>
                                    </span>
                                    <span class="badge bg-info text-white d-flex align-items-center justify-content-center">
                                        <?php echo $book['available_copies']; ?>/<?php echo $book['total_copies']; ?> copies
                                    </span>
                                </div>
                                
                                <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                
                                <p class="card-author">
                                    <i class="fas fa-user-edit me-1"></i>
                                    <?php 
                                    if (!empty($book['firstname']) || !empty($book['lastname'])) {
                                        echo htmlspecialchars($book['firstname'] . ' ' . 
                                             ($book['middle_init'] ? $book['middle_init'] . '. ' : '') . 
                                             $book['lastname']);
                                    } else {
                                        echo 'Unknown Author';
                                    }
                                    ?>
                                </p>
                                
                                <!-- Book Edition Information -->
                                <div class="book-edition-info mb-2">
                                    <small class="text-muted">
                                        <?php
                                        $editionDetails = [];
                                        
                                        if (!empty($book['series'])) {
                                            $editionDetails[] = '<span><i class="fas fa-bookmark me-1"></i> Series: ' . htmlspecialchars($book['series']) . '</span>';
                                        }
                                        
                                        if (!empty($book['volume'])) {
                                            $editionDetails[] = '<span><i class="fas fa-layer-group me-1"></i> Volume: ' . htmlspecialchars($book['volume']) . '</span>';
                                        }
                                        
                                        if (!empty($book['part'])) {
                                            $editionDetails[] = '<span><i class="fas fa-puzzle-piece me-1"></i> Part: ' . htmlspecialchars($book['part']) . '</span>';
                                        }
                                        
                                        if (!empty($book['edition'])) {
                                            $editionDetails[] = '<span><i class="fas fa-file-alt me-1"></i> Edition: ' . htmlspecialchars($book['edition']) . '</span>';
                                        }
                                        
                                        if (!empty($editionDetails)) {
                                            echo implode('<br>', $editionDetails);
                                        }
                                        ?>
                                    </small>
                                </div>
                                
                                <?php if (!empty($book['summary'])): ?>
                                    <p class="card-text">
                                        <?php echo htmlspecialchars(substr($book['summary'], 0, 120)) . (strlen($book['summary']) > 120 ? '...' : ''); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="card-details-row">
                                    <div class="card-details">
                                        <span>Program</span>
                                        <span><?php echo htmlspecialchars($book['program'] ?: 'N/A'); ?></span>
                                    </div>
                                    <div class="card-details">
                                        <span>Category</span>
                                        <span><?php echo htmlspecialchars($book['subject_category'] ?: 'N/A'); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($isLoggedIn): ?>
                                    <!-- For logged in users: Show view details and add to cart buttons -->
                                    <div class="book-actions">
                                        <a href="view_book.php?book_id=<?php echo $book['id']; ?>&group=1" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        <?php if ($book['available_copies'] > 0): ?>
                                            <button class="btn btn-outline-success" 
                                                    onclick="addToCart(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>')">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary" disabled>
                                                <i class="fas fa-clock"></i> Unavailable
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- For guests: Direct link to view details (no more warning) -->
                                    <div class="book-actions">
                                        <a href="view_book.php?book_id=<?php echo $book['id']; ?>&group=1" 
                                           class="btn btn-outline-primary btn-view">
                                            <i class="fas fa-eye me-1"></i> View Details
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i> No books found matching your search criteria. Try adjusting your filters or search terms.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php 
    // Include the appropriate footer based on login status
    if ($isLoggedIn) {
        include 'inc/footer.php';
    } else {
        echo '<footer class="bg-white py-4 mt-auto shadow-sm">
                <div class="container">
                    <div class="text-center small">
                        <span>Copyright &copy; NBS College Library 2024</span>
                    </div>
                </div>
              </footer>';
    }
    ?>
    
    <?php if (!$isLoggedIn): ?>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php endif; ?>
    
    <script>
        $(document).ready(function() {
            // Add trigger for mobile filters toggle
            $('#filter-toggle').on('click', function() {
                $('#filter-controls').toggleClass('show');
            });
            
            // Auto-submit form when changing select fields
            $('#program, #category').on('change', function() {
                $(this).closest('form').submit();
            });
        });
        
        // Function to add book to cart (for logged-in users)
        function addToCart(bookId, bookTitle) {
            // Show loading state
            Swal.fire({
                title: 'Adding to cart...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                type: "POST",
                url: "add_to_cart.php",
                data: { book_id: bookId }, // Send book_id parameter
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: bookTitle + ' has been added to your cart',
                            icon: 'success',
                            confirmButtonText: 'View Cart',
                            showCancelButton: true,
                            cancelButtonText: 'Continue Browsing'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'cart.php';
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'There was a problem adding this book to your cart',
                            icon: 'error'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'There was a problem connecting to the server',
                        icon: 'error'
                    });
                }
            });
        }
    </script>
</body>
</html>
