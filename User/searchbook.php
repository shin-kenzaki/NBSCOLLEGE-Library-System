<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get search parameters
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$program = isset($_GET['program']) ? $_GET['program'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Prepare SQL query based on search parameters - modified to group books
$sql = "SELECT 
    MIN(books.id) as id, 
    books.title, 
    COUNT(books.id) as total_copies,
    SUM(CASE WHEN books.status = 'Available' THEN 1 ELSE 0 END) as available_copies,
    books.subject_category, 
    books.program,
    books.ISBN,
    books.series,
    books.volume,
    books.part,
    books.edition,
    books.front_image,
    books.summary,
    writers.firstname, 
    writers.middle_init, 
    writers.lastname 
FROM books 
LEFT JOIN contributors ON books.id = contributors.book_id 
LEFT JOIN writers ON contributors.writer_id = writers.id 
WHERE 1=1";

$params = array();
$types = "";

if (!empty($searchTerm)) {
    $sql .= " AND (books.title LIKE ? OR books.accession LIKE ? OR writers.firstname LIKE ? OR writers.lastname LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ssss";
}

if (!empty($program)) {
    $sql .= " AND books.program = ?";
    $params[] = $program;
    $types .= "s";
}

if (!empty($category)) {
    $sql .= " AND books.subject_category = ?";
    $params[] = $category;
    $types .= "s";
}

// Group by the attributes that identify unique books
$sql .= " GROUP BY 
    books.title, 
    IFNULL(books.ISBN, ''),
    IFNULL(books.series, ''),
    IFNULL(books.volume, ''),
    IFNULL(books.part, ''),
    IFNULL(books.edition, '')
    ORDER BY books.title";

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Catalog Search</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <?php include 'inc/header.php'; ?>

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
                                
                                <a href="view_book.php?book_id=<?php echo $book['id']; ?>&group=1" class="btn btn-outline-primary btn-view">
                                    <i class="fas fa-eye me-1"></i> View Details
                                </a>
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

    <?php include 'inc/footer.php'; ?>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
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
    </script>
</body>
</html>
