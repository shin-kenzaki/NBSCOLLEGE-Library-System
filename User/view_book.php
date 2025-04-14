<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get the book ID from the query parameters
$bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;
$isGroup = isset($_GET['group']) && $_GET['group'] == 1;
$error = null;

if ($bookId > 0) {
    // First get the details of the selected book
    $query = "SELECT * FROM books WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $selectedBook = $result->fetch_assoc();
        
        // Now fetch all copies with the same attributes (ISBN, series, volume, part, edition)
        $copiesQuery = "SELECT * FROM books WHERE title = ?";
        $params = array($selectedBook['title']);
        $types = "s";
        
        // Add matching criteria for ISBN
        if (!empty($selectedBook['ISBN'])) {
            $copiesQuery .= " AND ISBN = ?";
            $params[] = $selectedBook['ISBN'];
            $types .= "s";
        } else {
            $copiesQuery .= " AND (ISBN IS NULL OR ISBN = '')";
        }
        
        // Add matching criteria for series
        if (!empty($selectedBook['series'])) {
            $copiesQuery .= " AND series = ?";
            $params[] = $selectedBook['series'];
            $types .= "s";
        } else {
            $copiesQuery .= " AND (series IS NULL OR series = '')";
        }
        
        // Add matching criteria for volume
        if (!empty($selectedBook['volume'])) {
            $copiesQuery .= " AND volume = ?";
            $params[] = $selectedBook['volume'];
            $types .= "s";
        } else {
            $copiesQuery .= " AND (volume IS NULL OR volume = '')";
        }
        
        // Add matching criteria for part
        if (!empty($selectedBook['part'])) {
            $copiesQuery .= " AND part = ?";
            $params[] = $selectedBook['part'];
            $types .= "s";
        } else {
            $copiesQuery .= " AND (part IS NULL OR part = '')";
        }
        
        // Add matching criteria for edition
        if (!empty($selectedBook['edition'])) {
            $copiesQuery .= " AND edition = ?";
            $params[] = $selectedBook['edition'];
            $types .= "s";
        } else {
            $copiesQuery .= " AND (edition IS NULL OR edition = '')";
        }
        
        $copiesQuery .= " ORDER BY copy_number";
        
        $stmt = $conn->prepare($copiesQuery);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $copiesResult = $stmt->get_result();
        $copies = [];
        $availableCopies = 0;
        
        while ($copy = $copiesResult->fetch_assoc()) {
            $copies[] = $copy;
            if ($copy['status'] == 'Available') {
                $availableCopies++;
            }
        }
        
        // Use the selected book as the representative book
        $book = $selectedBook;
        $totalCopies = count($copies);
        
        // Get primary author
        $contributorsQuery = "SELECT c.*, w.firstname, w.middle_init, w.lastname, c.role 
                             FROM contributors c 
                             JOIN writers w ON c.writer_id = w.id 
                             WHERE c.book_id = ? AND c.role = 'Author'
                             LIMIT 1";
        $stmt = $conn->prepare($contributorsQuery);
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $contributorResult = $stmt->get_result();
        $primaryAuthor = 'N/A';

        if ($contributorResult && $contributorResult->num_rows > 0) {
            $author = $contributorResult->fetch_assoc();
            $primaryAuthor = $author['firstname'] . ' ' .
                            ($author['middle_init'] ? $author['middle_init'] . '. ' : '') .
                            $author['lastname'];
        }

        // Fetch all contributors
        $allContributorsQuery = "SELECT c.*, w.firstname, w.middle_init, w.lastname, c.role 
                                FROM contributors c 
                                JOIN writers w ON c.writer_id = w.id 
                                WHERE c.book_id = ?";
        $stmt = $conn->prepare($allContributorsQuery);
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $contributorsResult = $stmt->get_result();
        $contributors = [];
        
        while ($row = $contributorsResult->fetch_assoc()) {
            $contributors[] = [
                'name' => $row['firstname'] . ' ' . 
                         ($row['middle_init'] ? $row['middle_init'] . '. ' : '') . 
                         $row['lastname'],
                'role' => $row['role']
            ];
        }

        // Fetch publications
        $publicationsQuery = "SELECT p.*, pub.publisher, pub.place 
                             FROM publications p 
                             JOIN publishers pub ON p.publisher_id = pub.id 
                             WHERE p.book_id = ?";
        $stmt = $conn->prepare($publicationsQuery);
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $publicationsResult = $stmt->get_result();
        $publications = [];
        
        while ($row = $publicationsResult->fetch_assoc()) {
            $publications[] = $row;
        }
        
        // Check if any of the copies are in user's cart, reserved, or borrowed
        $inCart = false;
        $isReserved = false;
        $isBorrowed = false;
        $reservationStatus = null;
        
        foreach ($copies as $copy) {
            // Check if book is in user's cart
            $checkCartQuery = "SELECT id FROM cart WHERE user_id = ? AND book_id = ? AND status = 1";
            $stmt = $conn->prepare($checkCartQuery);
            $stmt->bind_param("ii", $_SESSION['user_id'], $copy['id']);
            $stmt->execute();
            $cartResult = $stmt->get_result();
            if ($cartResult->num_rows > 0) {
                $inCart = true;
            }
            
            // Check if book is already reserved by the user - Modified here to only include 'Pending' and 'Ready'
            $checkReservationQuery = "SELECT id, status FROM reservations WHERE user_id = ? AND book_id = ? AND status IN ('Pending', 'Ready')";
            $stmt = $conn->prepare($checkReservationQuery);
            $stmt->bind_param("ii", $_SESSION['user_id'], $copy['id']);
            $stmt->execute();
            $reservationResult = $stmt->get_result();
            if ($reservationResult->num_rows > 0) {
                $isReserved = true;
                $reservationStatus = $reservationResult->fetch_assoc()['status'];
            }
            
            // Check if book is currently borrowed by the user
            $checkBorrowingQuery = "SELECT id FROM borrowings WHERE user_id = ? AND book_id = ? AND status = 'Borrowed'";
            $stmt = $conn->prepare($checkBorrowingQuery);
            $stmt->bind_param("ii", $_SESSION['user_id'], $copy['id']);
            $stmt->execute();
            $borrowingResult = $stmt->get_result();
            if ($borrowingResult->num_rows > 0) {
                $isBorrowed = true;
            }
        }
    } else {
        $error = "Book not found.";
    }
} else {
    $error = "Invalid book ID.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($book) ? htmlspecialchars($book['title']) : 'Book Details'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom styles -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #2e59d9;
            --light-bg: #f8f9fc;
            --card-border: rgba(0, 0, 0, 0.125);
        }
        
        body {
            background-color: var(--light-bg);
            color: #333;
        }
        
        .book-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 1rem 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .book-header .container {
            position: relative;
            z-index: 1;
        }
        
        .breadcrumb {
            background: none;
            padding: 0;
            margin-bottom: 1rem;
        }
        
        .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: white;
        }
        
        .book-cover {
            height: 350px;
            width: 100%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .no-image-placeholder {
            height: 350px;
            width: 100%;
            border-radius: 8px;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .book-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .book-author {
            font-size: 1.25rem;
            font-weight: 400;
            margin-bottom: 1.5rem;
        }
        
        .availability-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 1.5rem;
        }
        
        .badge-available {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        .badge-unavailable {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .action-buttons {
            margin-top: 2rem;
        }
        
        .action-buttons .btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 50px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
            color: var(--primary-color);
        }
        
        .detail-card {
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: none;
        }
        
        .details-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            margin-bottom: 1.25rem;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
            display: block;
        }
        
        .detail-value {
            color: #6c757d;
        }
        
        .summary-section {
            padding-top: 1rem;
        }
        
        .contributor-item {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 0.1rem 0.5rem rgba(0, 0, 0, 0.05);
        }
        
        .contributor-name {
            font-weight: 500;
            color: #495057;
        }
        
        .contributor-role {
            background-color: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .back-to-search {
            margin-top: 2rem;
            display: inline-block;
            margin-bottom: 2rem;
        }
        
        .book-metadata {
            margin-top: -7rem;
            position: relative;
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 2rem;
        }
        
        .nav-pills .nav-link {
            color: #6c757d;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-right: 0.5rem;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .tab-content {
            padding: 1.5rem 0;
        }
        
        .copy-list {
            margin-top: 1.5rem;
        }
        
        .copy-item {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 0.1rem 0.5rem rgba(0, 0, 0, 0.05);
        }
        
        .copy-info {
            display: flex;
            flex-direction: column;
        }
        
        .copy-accession {
            font-weight: 600;
            color: #495057;
        }
        
        .copy-callnumber {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .copy-status {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .copy-available {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        .copy-unavailable {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .copy-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .copy-summary {
            background-color: rgba(0, 123, 255, 0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #0056b3;
            font-weight: 500;
            text-align: center;
        }
        
        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .book-metadata {
                margin-top: 1rem;
            }
            
            .details-row {
                grid-template-columns: 1fr;
            }
            
            .book-title {
                font-size: 1.75rem;
            }
            
            .book-author {
                font-size: 1.1rem;
            }
            
            .book-cover, .no-image-placeholder {
                height: 300px;
            }
        }
        
        @media (max-width: 767.98px) {
            .book-header {
                padding: 2rem 0;
            }
            
            .action-buttons .btn {
                margin-bottom: 1rem;
                width: 100%;
            }
            
            .book-cover, .no-image-placeholder {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <?php include 'inc/header.php'; ?>

    <?php if ($error): ?>
        <div class="container mt-5">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                <div class="mt-3">
                    <a href="searchbook.php" class="btn btn-outline-danger">
                        <i class="fas fa-arrow-left me-2"></i> Back to Search
                    </a>
                </div>
            </div>
        </div>
    <?php elseif (isset($book)): ?>
        <!-- Book Header Section -->
        <header class="book-header">
            <div class="container">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="searchbook.php">Books</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars(substr($book['title'], 0, 30)) . (strlen($book['title']) > 30 ? '...' : ''); ?></li>
                    </ol>
                </nav>
            </div>
        </header>

        <div class="container">
            <div class="row">
                <!-- Book Cover Column -->
                <div class="col-lg-3 mb-4">
                    <?php if (!empty($book['front_image'])): ?>
                        <img src="<?php echo htmlspecialchars($book['front_image']); ?>" alt="Book Cover" class="book-cover">
                    <?php else: ?>
                        <div class="no-image-placeholder">
                            <i class="fas fa-book fa-5x text-muted"></i>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Status Badge -->
                    <div class="text-center mt-4">
                        <span class="availability-badge <?php echo ($availableCopies > 0) ? 'badge-available' : 'badge-unavailable'; ?>">
                            <i class="fas <?php echo ($availableCopies > 0) ? 'fa-check-circle' : 'fa-times-circle'; ?> me-2"></i>
                            <?php echo ($availableCopies > 0) ? 'Available' : 'Unavailable'; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Book Details Column -->
                <div class="col-lg-9">
                    <div class="book-metadata">
                        <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                        <p class="book-author">
                            <i class="fas fa-user-edit me-2"></i>
                            <?php echo htmlspecialchars($primaryAuthor); ?>
                        </p>
                        
                        <!-- Copy Summary -->
                        <div class="copy-summary">
                            <i class="fas fa-book-reader me-2"></i>
                            <?php echo $availableCopies; ?> out of <?php echo $totalCopies; ?> copies available
                        </div>
                        
                        <!-- Action Buttons -->
                        <?php if ($availableCopies > 0 && !$isBorrowed && !$isReserved): ?>
                            <div class="action-buttons">
                                <?php if (!$inCart): ?>
                                    <button class="btn btn-primary" onclick="addToCart(<?php echo $book['id']; ?>)">
                                        <i class="fas fa-cart-plus me-2"></i> Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success" disabled>
                                        <i class="fas fa-check me-2"></i> In Your Cart
                                    </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline-primary" onclick="reserveBook(<?php echo $book['id']; ?>)">
                                    <i class="fas fa-bookmark me-2"></i> Reserve Book
                                </button>
                            </div>
                        <?php elseif ($isReserved): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> This book is currently reserved by you.
                                <br>Status: <strong><?php echo htmlspecialchars($reservationStatus); ?></strong>
                            </div>
                        <?php elseif ($isBorrowed): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-book-reader me-2"></i> You currently have this book borrowed.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Book Details Tabs -->
                        <ul class="nav nav-pills mt-4" id="bookDetailsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="copies-tab" data-bs-toggle="tab" data-bs-target="#copies" type="button" role="tab" aria-controls="copies" aria-selected="true">
                                    <i class="fas fa-copy me-1"></i> Copies
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="false">
                                    <i class="fas fa-info-circle me-1"></i> Details
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="false">
                                    <i class="fas fa-align-left me-1"></i> Description
                                </button>
                            </li>
                            <?php if (count($contributors) > 1): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="contributors-tab" data-bs-toggle="tab" data-bs-target="#contributors" type="button" role="tab" aria-controls="contributors" aria-selected="false">
                                    <i class="fas fa-users me-1"></i> Contributors
                                </button>
                            </li>
                            <?php endif; ?>
                        </ul>
                        
                        <div class="tab-content" id="bookDetailsTabsContent">
                            <!-- Copies Tab -->
                            <div class="tab-pane fade show active" id="copies" role="tabpanel" aria-labelledby="copies-tab">
                                <div class="copy-list">
                                    <?php foreach ($copies as $copy): ?>
                                    <div class="copy-item">
                                        <div class="copy-info">
                                            <span class="copy-accession">Accession Number: <?php echo htmlspecialchars($copy['accession']); ?></span>
                                            <span class="copy-callnumber"><?php echo htmlspecialchars($copy['call_number'] ?: 'No call number'); ?></span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="copy-status <?php echo ($copy['status'] == 'Available') ? 'copy-available' : 'copy-unavailable'; ?>">
                                                <?php echo htmlspecialchars($copy['status']); ?>
                                            </span>
                                            <?php if ($copy['status'] == 'Available' && !$inCart && !$isReserved && !$isBorrowed): ?>
                                            <div class="copy-actions ms-3">
                                                <button class="btn btn-sm btn-outline-primary" onclick="addToCart(<?php echo $copy['id']; ?>)">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" onclick="reserveBook(<?php echo $copy['id']; ?>)">
                                                    <i class="fas fa-bookmark"></i>
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Details Tab -->
                            <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                                <div class="details-row">
                                    <div class="detail-item">
                                        <span class="detail-label">Title</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($book['title']); ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <span class="detail-label">Author</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($primaryAuthor); ?></span>
                                    </div>
                                </div>
                                
                                <div class="details-row">
                                    <div class="detail-item">
                                        <span class="detail-label">Category</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($book['subject_category'] ?: 'N/A'); ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <span class="detail-label">Program</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($book['program'] ?: 'N/A'); ?></span>
                                    </div>
                                </div>
                                
                                <div class="details-row">
                                    <?php if (!empty($publications)): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Publisher</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($publications[0]['publisher']); ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <span class="detail-label">Place</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($publications[0]['place']); ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <span class="detail-label">Publication Year</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($publications[0]['publish_date']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($book['ISBN'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">ISBN</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($book['ISBN']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="details-row">
                                    <?php if (!empty($book['edition'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Edition</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($book['edition']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($book['series'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Series</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($book['series']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($book['volume'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Volume</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($book['volume']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($book['part'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Part</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($book['part']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="details-row">
                                    <div class="detail-item">
                                        <span class="detail-label">Total Copies</span>
                                        <span class="detail-value"><?php echo $totalCopies; ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <span class="detail-label">Available Copies</span>
                                        <span class="detail-value"><?php echo $availableCopies; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description Tab -->
                            <div class="tab-pane fade" id="description" role="tabpanel" aria-labelledby="description-tab">
                                <?php if (!empty($book['summary']) || !empty($book['subject_detail']) || !empty($book['contents'])): ?>
                                    <div class="summary-section">
                                        <?php if (!empty($book['summary'])): ?>
                                            <h4>Summary</h4>
                                            <p><?php echo nl2br(htmlspecialchars($book['summary'])); ?></p>
                                            <hr>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($book['subject_detail'])): ?>
                                            <h4>Subject Details</h4>
                                            <p><?php echo nl2br(htmlspecialchars($book['subject_detail'])); ?></p>
                                            <hr>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($book['contents'])): ?>
                                            <h4>Contents</h4>
                                            <p><?php echo nl2br(htmlspecialchars($book['contents'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> No description available for this book.
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Contributors Tab -->
                            <?php if (count($contributors) > 1): ?>
                            <div class="tab-pane fade" id="contributors" role="tabpanel" aria-labelledby="contributors-tab">
                                <?php foreach ($contributors as $contributor): ?>
                                <div class="contributor-item">
                                    <span class="contributor-name"><?php echo htmlspecialchars($contributor['name']); ?></span>
                                    <span class="contributor-role"><?php echo htmlspecialchars($contributor['role']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <a href="searchbook.php" class="btn btn-outline-secondary back-to-search">
                <i class="fas fa-arrow-left me-2"></i> Back to Search Results
            </a>
        </div>
    <?php endif; ?>

    <?php include 'inc/footer.php'; ?>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        function addToCart(bookId) {
            $.ajax({
                type: 'POST',
                url: 'add_to_cart.php',
                data: {
                    title: '<?php echo addslashes($book['title']); ?>',
                    isbn: '<?php echo addslashes($book['ISBN']); ?>',
                    series: '<?php echo addslashes($book['series']); ?>',
                    volume: '<?php echo addslashes($book['volume']); ?>',
                    part: '<?php echo addslashes($book['part']); ?>',
                    edition: '<?php echo addslashes($book['edition']); ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Added to Cart',
                            html: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred.'
                    });
                }
            });
        }
        
        function reserveBook(bookId) {
            Swal.fire({
                title: 'Reserve this book?',
                text: "You will be notified when the book is ready for pickup.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4e73df',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, reserve it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: 'reserve_book.php',
                        data: {
                            title: '<?php echo addslashes($book['title']); ?>',
                            isbn: '<?php echo addslashes($book['ISBN']); ?>',
                            series: '<?php echo addslashes($book['series']); ?>',
                            volume: '<?php echo addslashes($book['volume']); ?>',
                            part: '<?php echo addslashes($book['part']); ?>',
                            edition: '<?php echo addslashes($book['edition']); ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Book Reserved',
                                    html: response.message,
                                    showConfirmButton: true
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    html: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An unexpected error occurred.'
                            });
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>