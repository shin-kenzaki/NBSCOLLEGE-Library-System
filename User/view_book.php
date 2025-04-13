<?php
session_start();
include '../db.php';

// Get the book identifying parameters
$bookTitle = isset($_GET['title']) ? $_GET['title'] : '';
$isbn = isset($_GET['isbn']) ? $_GET['isbn'] : '';
$series = isset($_GET['series']) ? $_GET['series'] : '';
$volume = isset($_GET['volume']) ? $_GET['volume'] : '';
$part = isset($_GET['part']) ? $_GET['part'] : '';
$edition = isset($_GET['edition']) ? $_GET['edition'] : '';
$selected_copy_id = isset($_GET['copy_id']) ? intval($_GET['copy_id']) : null;

if (!empty($bookTitle)) {
    // Build the WHERE clause for the query based on available parameters
    $whereClause = "title = ?";
    $queryParams = [$bookTitle];
    $types = "s";
    
    if (!empty($isbn)) {
        $whereClause .= " AND ISBN = ?";
        $queryParams[] = $isbn;
        $types .= "s";
    } else {
        $whereClause .= " AND (ISBN IS NULL OR ISBN = '')";
    }
    
    if (!empty($series)) {
        $whereClause .= " AND series = ?";
        $queryParams[] = $series;
        $types .= "s";
    } else {
        $whereClause .= " AND (series IS NULL OR series = '')";
    }
    
    if (!empty($volume)) {
        $whereClause .= " AND volume = ?";
        $queryParams[] = $volume;
        $types .= "s";
    } else {
        $whereClause .= " AND (volume IS NULL OR volume = '')";
    }
    
    if (!empty($part)) {
        $whereClause .= " AND part = ?";
        $queryParams[] = $part;
        $types .= "s";
    } else {
        $whereClause .= " AND (part IS NULL OR part = '')";
    }
    
    if (!empty($edition)) {
        $whereClause .= " AND edition = ?";
        $queryParams[] = $edition;
        $types .= "s";
    } else {
        $whereClause .= " AND (edition IS NULL OR edition = '')";
    }

    // Fetch book details
    $query = "SELECT * FROM books WHERE $whereClause LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$queryParams);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();

    // Fetch total copies and in-shelf count
    $copiesQuery = "SELECT COUNT(*) as total_copies, SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as in_shelf FROM books WHERE $whereClause";
    $stmt = $conn->prepare($copiesQuery);
    $stmt->bind_param($types, ...$queryParams);
    $stmt->execute();
    $copiesResult = $stmt->get_result();
    $copies = $copiesResult->fetch_assoc();
    $totalCopies = $copies['total_copies'];
    $inShelf = $copies['in_shelf'];

    if ($book) {
        // Fetch contributors
        $contributorsQuery = "SELECT w.*, c.role FROM contributors c
                             JOIN writers w ON c.writer_id = w.id
                             WHERE c.book_id = ?";
        $stmt = $conn->prepare($contributorsQuery);
        $stmt->bind_param("i", $book['id']);
        $stmt->execute();
        $contributorsResult = $stmt->get_result();
        $contributors = [];
        while ($row = $contributorsResult->fetch_assoc()) {
            $contributors[] = $row;
        }

        // Fetch publication details
        $publicationsQuery = "SELECT p.*, pub.publisher, pub.place
                             FROM publications p
                             JOIN publishers pub ON p.publisher_id = pub.id
                             WHERE p.book_id = ?";
        $stmt = $conn->prepare($publicationsQuery);
        $stmt->bind_param("i", $book['id']);
        $stmt->execute();
        $publicationsResult = $stmt->get_result();
        $publications = [];
        while ($row = $publicationsResult->fetch_assoc()) {
            $publications[] = $row;
        }

        // Fetch all copies of the book with the same identifying characteristics
        $allCopiesQuery = "SELECT * FROM books WHERE $whereClause";
        $stmt = $conn->prepare($allCopiesQuery);
        $stmt->bind_param($types, ...$queryParams);
        $stmt->execute();
        $allCopiesResult = $stmt->get_result();
        $allCopies = [];
        $allCopiesDetails = []; // Store detailed information for each copy
        
        while ($row = $allCopiesResult->fetch_assoc()) {
            $allCopies[] = $row;
            
            // Store copy details for later use
            $copyDetails = [
                'copy' => $row,
                'contributors' => [],
                'publications' => []
            ];
            
            // Fetch contributors for this specific copy
            $contributorsQuery = "SELECT w.*, c.role FROM contributors c
                                JOIN writers w ON c.writer_id = w.id
                                WHERE c.book_id = ?";
            $stmt2 = $conn->prepare($contributorsQuery);
            $stmt2->bind_param("i", $row['id']);
            $stmt2->execute();
            $contributorsResult = $stmt2->get_result();
            while ($contributorRow = $contributorsResult->fetch_assoc()) {
                $copyDetails['contributors'][] = $contributorRow;
            }
            
            // Fetch publication details for this specific copy
            $publicationsQuery = "SELECT p.*, pub.publisher, pub.place
                                FROM publications p
                                JOIN publishers pub ON p.publisher_id = pub.id
                                WHERE p.book_id = ?";
            $stmt3 = $conn->prepare($publicationsQuery);
            $stmt3->bind_param("i", $row['id']);
            $stmt3->execute();
            $publicationsResult = $stmt3->get_result();
            while ($pubRow = $publicationsResult->fetch_assoc()) {
                $copyDetails['publications'][] = $pubRow;
            }
            
            $allCopiesDetails[$row['id']] = $copyDetails;
        }
        
        // Set the initially displayed copy - either the requested copy_id or the first copy
        $displayedCopy = $book;
        $displayedContributors = $contributors;
        $displayedPublications = $publications;
        
        if ($selected_copy_id && isset($allCopiesDetails[$selected_copy_id])) {
            $displayedCopy = $allCopiesDetails[$selected_copy_id]['copy'];
            $displayedContributors = $allCopiesDetails[$selected_copy_id]['contributors'];
            $displayedPublications = $allCopiesDetails[$selected_copy_id]['publications'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Details</title>
    <style>
        .isbd-record {
            width: 100%;
            background-color: #ffffff;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            font-family: "Times New Roman", Times, serif;
            line-height: 1.8;
        }
        .isbd-area {
            margin-bottom: 1.5em;
            text-indent: -1em;
            padding-left: 1em;
        }
        .isbd-punctuation {
            color: #666;
            font-weight: bold;
        }
        .isbd-date {
            margin: 1em 0;
            font-style: italic;
        }
        .isbd-subjects {
            margin-top: 2em;
        }
        .isbd-accession {
            margin-top: 1em;
            font-weight: bold;
        }
        .book-details {
            width: 100%;
            padding: 30px;
            background-color: #ffffff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .book-details h2 {
            margin-bottom: 25px;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .book-details h3 {
            margin: 25px 0 15px;
            color: #34495e;
            font-size: 1.3rem;
        }
        .book-details img {
            max-width: 150px;
            margin: 0 15px 15px 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .book-details .label {
            font-weight: 600;
            color: #455a64;
            min-width: 120px;
            display: inline-block;
        }
        .book-images {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .book-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .book-info-full {
            grid-column: 1 / -1;
        }
        .info-item {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 5px;
            border-left: 3px solid #2c3e50;
        }
        .subject-info ul {
            list-style-type: none;
            padding-left: 15px;
        }
        .subject-info li {
            margin-bottom: 8px;
            position: relative;
            padding-left: 20px;
        }
        .subject-info li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #2c3e50;
        }
        .summary, .contents {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 15px 0;
        }
        @media (max-width: 1200px) {
            .book-info-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .book-info-grid {
                grid-template-columns: 1fr;
            }
            .book-details {
                padding: 15px;
            }
        }
        /* Floating action button styles */
        .btn-group-vertical .btn {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        .btn-group-vertical .btn:hover {
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .btn-group-vertical {
                margin-bottom: 60px;
            }
        }
        /* Add style rule to center all table columns */
        table.table {
            width: 100%;
            border-collapse: collapse;
        }
        table.table thead {
            background-color: #f8f9fa;
        }
        table.table th, table.table td {
            border: 1px solid #e9ecef;
            padding: 8px;
        }
        table.table th,
        table.table td {
            text-align: center;
        }
        /* Additional card styling */
        .book-card {
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .book-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .book-cover-img {
            max-height: 300px;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .detail-item {
            margin-bottom: 10px;
            display: flex;
        }
        .detail-label {
            font-weight: 600;
            width: 150px;
            color: #4e73df;
        }
        .detail-value {
            flex: 1;
        }
        .book-title {
            color: #2c3e50;
            border-left: 4px solid #4e73df;
            padding-left: 15px;
            margin-bottom: 20px;
        }
        .availability-badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 500;
            display: inline-block;
            margin-top: 10px;
        }
        .badge-available {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .badge-unavailable {
            background-color: #ffebee;
            color: #c62828;
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 15px 20px;
            font-weight: 600;
        }
        .card-header i {
            margin-right: 8px;
        }
        .action-button {
            transition: all 0.2s ease;
        }
        .action-button:hover {
            transform: translateY(-2px);
        }
        /* Styles for clickable holdings items */
        .holdings-table tbody tr {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .holdings-table tbody tr:hover {
            background-color: #f0f4ff !important;
        }
        .holdings-table tbody tr.selected {
            background-color: #e3e9ff !important;
            border-left: 3px solid #4e73df;
        }
        .holdings-table tbody tr.selected td:first-child {
            padding-left: calc(0.75rem - 3px);
        }
    </style>
    <!-- Add Bootstrap CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php include '../user/inc/header.php'; ?>

    <!-- Main Content -->
    <div id="content" class="d-flex flex-column min-vh-100">
        <div class="container-fluid px-4">
            <?php if (isset($book)): ?>
            <!-- Book Information Header -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-book"></i> Book Details</h1>
            </div>
            <?php endif; ?>
            
            <!-- Tab navigation with action buttons -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <ul class="nav nav-tabs" id="bookDetailsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="true">
                            Standard View
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="marc-tab" data-bs-toggle="tab" data-bs-target="#marc" type="button" role="tab" aria-controls="marc" aria-selected="false">
                            MARC View
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="isbd-tab" data-bs-toggle="tab" data-bs-target="#isbd" type="button" role="tab" aria-controls="isbd" aria-selected="false">
                            ISBD View
                        </button>
                    </li>
                </ul>
                
                <?php if (isset($book)): ?>
                <div class="d-flex mt-2 mt-md-0">
                    <button class="btn btn-primary btn-sm me-2 action-button add-to-cart" 
                        data-title="<?php echo htmlspecialchars($displayedCopy['title']); ?>"
                        data-isbn="<?php echo htmlspecialchars($displayedCopy['ISBN'] ?? ''); ?>"
                        data-series="<?php echo htmlspecialchars($displayedCopy['series'] ?? ''); ?>"
                        data-volume="<?php echo htmlspecialchars($displayedCopy['volume'] ?? ''); ?>"
                        data-part="<?php echo htmlspecialchars($displayedCopy['part'] ?? ''); ?>"
                        data-edition="<?php echo htmlspecialchars($displayedCopy['edition'] ?? ''); ?>">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                    <button class="btn btn-success btn-sm action-button borrow-book" 
                        data-title="<?php echo htmlspecialchars($displayedCopy['title']); ?>"
                        data-isbn="<?php echo htmlspecialchars($displayedCopy['ISBN'] ?? ''); ?>"
                        data-series="<?php echo htmlspecialchars($displayedCopy['series'] ?? ''); ?>"
                        data-volume="<?php echo htmlspecialchars($displayedCopy['volume'] ?? ''); ?>"
                        data-part="<?php echo htmlspecialchars($displayedCopy['part'] ?? ''); ?>"
                        data-edition="<?php echo htmlspecialchars($displayedCopy['edition'] ?? ''); ?>">
                        <i class="fas fa-book"></i> Borrow Book
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div class="tab-content" id="bookDetailsContent">
                <!-- Standard View Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <?php if (isset($book)): 
                        // Process contributors data - use the displayed copy's contributors
                        $contributorLine = '';
                        $authorsList = [];
                        $coAuthorsList = [];
                        $editorsList = [];

                        foreach ($displayedContributors as $contributor) {
                            $fullName = $contributor['lastname'] . ', ' . $contributor['firstname'] . ' ' . $contributor['middle_init'];
                            if ($contributor['role'] == 'Author') {
                                $authorsList[] = $fullName . ' (Author)';
                            } elseif ($contributor['role'] == 'Co-Author') {
                                $coAuthorsList[] = $fullName . ' (Co-Author)';
                            } elseif ($contributor['role'] == 'Editor') {
                                $editorsList[] = $fullName . ' (Editor)';
                            }
                        }

                        $allContributors = array_merge($authorsList, $coAuthorsList, $editorsList);
                        $contributorLine = implode(', ', $allContributors);
                    ?>
                    <div class="row">
                        <!-- Left Column - Book Cover and Basic Info -->
                        <div class="col-md-4 mb-4">
                            <div class="card shadow book-card h-100">
                                <div class="card-header">
                                    <i class="fas fa-image"></i> Book Cover
                                </div>
                                <div class="card-body text-center">
                                    <?php if (!empty($displayedCopy['front_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($displayedCopy['front_image']); ?>" alt="Book Cover" class="book-cover-img mb-3" id="book-cover">
                                    <?php elseif (!empty($displayedCopy['cover_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($displayedCopy['cover_image']); ?>" alt="Book Cover" class="book-cover-img mb-3" id="book-cover">
                                    <?php else: ?>
                                        <img src="../Admin/inc/upload/default-book.jpg" alt="Default Cover" class="book-cover-img mb-3" id="book-cover">
                                    <?php endif; ?>
                                    
                                    <h5 class="mt-3 mb-3" id="cover-title"><?php echo htmlspecialchars($displayedCopy['title']); ?></h5>
                                    
                                    <div class="d-flex justify-content-around mt-4">
                                        <div class="text-center">
                                            <h6 class="text-muted mb-1">Total Copies</h6>
                                            <h4 id="total-copies"><?php echo $totalCopies; ?></h4>
                                        </div>
                                        <div class="text-center">
                                            <h6 class="text-muted mb-1">Available</h6>
                                            <h4 class="<?php echo $inShelf > 0 ? 'text-success' : 'text-danger'; ?>" id="available-copies">
                                                <?php echo $inShelf; ?>
                                            </h4>
                                        </div>
                                    </div>
                                    
                                    <div id="availability-badge" class="availability-badge <?php echo $inShelf > 0 ? 'badge-available' : 'badge-unavailable'; ?>">
                                        <?php echo $inShelf > 0 ? 'Available for Borrowing' : 'Currently Unavailable'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column - Detailed Information with Tabs -->
                        <div class="col-md-8 mb-4">
                            <div class="card shadow book-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div><i class="fas fa-info-circle"></i> Book Details</div>
                                    <div>
                                        <span class="badge bg-primary">Accession: <span id="accession-badge"><?php echo htmlspecialchars($displayedCopy['accession']); ?></span></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h4 class="book-title mb-4" id="book-title"><?php echo htmlspecialchars($displayedCopy['title']); ?></h4>
                                    
                                    
                                    <!-- Nav tabs for book information sections -->
                                    <ul class="nav nav-tabs mb-3" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="biblio-info-tab" data-bs-toggle="tab" 
                                                    data-bs-target="#biblio-info" type="button" role="tab" 
                                                    aria-controls="biblio-info" aria-selected="true">
                                                Bibliographic Info
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="summary-tab" data-bs-toggle="tab" 
                                                    data-bs-target="#summary" type="button" role="tab" 
                                                    aria-controls="summary" aria-selected="false">
                                                Summary
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="contents-tab" data-bs-toggle="tab" 
                                                    data-bs-target="#contents" type="button" role="tab" 
                                                    aria-controls="contents" aria-selected="false">
                                                Table of Contents
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="subject-tab" data-bs-toggle="tab" 
                                                    data-bs-target="#subject" type="button" role="tab" 
                                                    aria-controls="subject" aria-selected="false">
                                                Subject Info
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="contributors-tab" data-bs-toggle="tab" 
                                                    data-bs-target="#contributors" type="button" role="tab" 
                                                    aria-controls="contributors" aria-selected="false">
                                                Contributors
                                            </button>
                                        </li>
                                    </ul>
                                    
                                    <!-- Tab content -->
                                    <div class="tab-content">
                                        <!-- Bibliographic Information -->
                                        <div class="tab-pane fade show active" id="biblio-info" role="tabpanel">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="detail-item" id="isbn-container" <?php echo empty($displayedCopy['ISBN']) ? 'style="display: none;"' : ''; ?>>
                                                        <div class="detail-label">ISBN:</div>
                                                        <div class="detail-value" id="isbn-value"><?php echo htmlspecialchars($displayedCopy['ISBN'] ?? ''); ?></div>
                                                    </div>
                                                    
                                                    <div class="detail-item" id="edition-container" <?php echo empty($displayedCopy['edition']) ? 'style="display: none;"' : ''; ?>>
                                                        <div class="detail-label">Edition:</div>
                                                        <div class="detail-value" id="edition-value"><?php echo htmlspecialchars($displayedCopy['edition'] ?? ''); ?></div>
                                                    </div>
                                                    
                                                    <div class="detail-item" id="series-container" <?php echo empty($displayedCopy['series']) ? 'style="display: none;"' : ''; ?>>
                                                        <div class="detail-label">Series:</div>
                                                        <div class="detail-value" id="series-value"><?php echo htmlspecialchars($displayedCopy['series'] ?? ''); ?></div>
                                                    </div>
                                                    
                                                    <div class="detail-item" id="volume-container" <?php echo empty($displayedCopy['volume']) ? 'style="display: none;"' : ''; ?>>
                                                        <div class="detail-label">Volume:</div>
                                                        <div class="detail-value" id="volume-value"><?php echo htmlspecialchars($displayedCopy['volume'] ?? ''); ?></div>
                                                    </div>
                                                    
                                                    <div class="detail-item" id="part-container" <?php echo empty($displayedCopy['part']) ? 'style="display: none;"' : ''; ?>>
                                                        <div class="detail-label">Part:</div>
                                                        <div class="detail-value" id="part-value"><?php echo htmlspecialchars($displayedCopy['part'] ?? ''); ?></div>
                                                    </div>

                                                    <div class="detail-item">
                                                        <div class="detail-label">Content Type:</div>
                                                        <div class="detail-value" id="content-type-value"><?php echo htmlspecialchars($displayedCopy['content_type']); ?></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <?php if (!empty($displayedPublications)): 
                                                        $pub = $displayedPublications[0];
                                                    ?>
                                                    <div class="detail-item" id="publisher-container">
                                                        <div class="detail-label">Publisher:</div>
                                                        <div class="detail-value" id="publisher-value"><?php echo htmlspecialchars($pub['publisher']); ?></div>
                                                    </div>
                                                    
                                                    <div class="detail-item" id="publish-location-container">
                                                        <div class="detail-label">Publish Location:</div>
                                                        <div class="detail-value" id="publish-location-value"><?php echo htmlspecialchars($pub['place']); ?></div>
                                                    </div>
                                                    
                                                    <div class="detail-item" id="publish-date-container">
                                                        <div class="detail-label">Publish Date:</div>
                                                        <div class="detail-value" id="publish-date-value"><?php echo htmlspecialchars($pub['publish_date']); ?></div>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="detail-item">
                                                        <div class="detail-label">Physical Description:</div>
                                                        <div class="detail-value" id="physical-desc-value">
                                                            <?php echo htmlspecialchars($displayedCopy['total_pages'] . ' pages ; ' . $displayedCopy['dimension'] . ' cm'); ?>
                                                        </div>
                                                    </div>

                                                    <div class="detail-item">
                                                        <div class="detail-label">Call Number:</div>
                                                        <div class="detail-value" id="call-number-value"><?php echo htmlspecialchars($displayedCopy['call_number']); ?></div>
                                                    </div>

                                                    <div class="detail-item" id="shelf-location-container">
                                                        <div class="detail-label">Location:</div>
                                                        <div class="detail-value" id="shelf-location-value"><?php echo htmlspecialchars($displayedCopy['shelf_location']); ?></div>
                                                    </div>

                                                    <div class="detail-item">
                                                        <div class="detail-label">Status:</div>
                                                        <div class="detail-value">
                                                            <span id="status-badge" class="badge rounded-pill bg-<?php echo $displayedCopy['status'] == 'Available' ? 'success' : 'secondary'; ?>">
                                                                <?php echo htmlspecialchars($displayedCopy['status']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Summary Section -->
                                        <div class="tab-pane fade" id="summary" role="tabpanel">
                                            <?php if (!empty($displayedCopy['summary'])): ?>
                                                <div class="book-detail-section summary-content">
                                                    <?php echo nl2br(htmlspecialchars($displayedCopy['summary'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted" style="text-align:center">No summary available for this book.</p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Table of Contents Section -->
                                        <div class="tab-pane fade" id="contents" role="tabpanel">
                                            <?php if (!empty($displayedCopy['contents'])): ?>
                                                <div class="book-detail-section contents-content">
                                                    <?php echo nl2br(htmlspecialchars($displayedCopy['contents'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted" style="text-align:center">No table of contents available for this book.</p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Subject Information Section -->
                                        <div class="tab-pane fade" id="subject" role="tabpanel">
                                            <?php if (!empty($displayedCopy['subject_category']) || !empty($displayedCopy['subject_detail'])): ?>
                                                <div class="subject-tags mb-3">
                                                    <?php if (!empty($displayedCopy['subject_category'])): ?>
                                                        <h6>Subject Category:</h6>
                                                        <div class="mb-3">
                                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($displayedCopy['subject_category']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($displayedCopy['subject_detail'])): ?>
                                                        <h6>Subject Details:</h6>
                                                        <div>
                                                            <?php 
                                                            $subjects = explode('|', $displayedCopy['subject_detail']);
                                                            foreach($subjects as $subject): ?>
                                                                <span class="badge bg-info me-1 mb-2"><?php echo htmlspecialchars($subject); ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted" style="text-align:center">No subject information available for this book.</p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Contributors Section -->
                                        <div class="tab-pane fade" id="contributors" role="tabpanel">
                                            <?php if (!empty($displayedContributors)): ?>
                                                <ul class="list-group list-group-flush">
                                                    <?php foreach ($displayedContributors as $contributor): ?>
                                                        <li class="list-group-item">
                                                            <strong><?php echo htmlspecialchars($contributor['firstname'] . ' ' . $contributor['middle_init'] . ' ' . $contributor['lastname']); ?></strong>
                                                            <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($contributor['role']); ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p class="text-muted" style="text-align:center">No contributor information available for this book.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Holdings Information -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card shadow book-card">
                                <div class="card-header">
                                    <i class="fas fa-clipboard-list"></i> Holdings Information
                                    <small class="text-muted ms-2">(Click on a row to view specific copy details)</small>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($allCopies)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped holdings-table">
                                                <thead>
                                                    <tr>
                                                        <th>Accession</th>
                                                        <th>Call Number</th>
                                                        <th>Copy Number</th>
                                                        <th>ISBN</th>
                                                        <th>Edition</th>
                                                        <th>Series</th>
                                                        <th>Volume</th>
                                                        <th>Part</th>
                                                        <th>Status</th>
                                                        <th>Location</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($allCopies as $copy): 
                                                        $isSelected = ($selected_copy_id && $copy['id'] == $selected_copy_id) || 
                                                                    (!$selected_copy_id && $copy['id'] == $book['id']);
                                                    ?>
                                                        <tr class="holding-row <?php echo $isSelected ? 'selected' : ''; ?>" 
                                                            data-copy-id="<?php echo $copy['id']; ?>">
                                                            <td><?php echo htmlspecialchars($copy['accession']); ?></td>
                                                            <td><?php echo htmlspecialchars($copy['call_number']); ?></td>
                                                            <td><?php echo htmlspecialchars($copy['copy_number']); ?></td>
                                                            <td><?php echo !empty($copy['ISBN']) ? htmlspecialchars($copy['ISBN']) : '-'; ?></td>
                                                            <td><?php echo !empty($copy['edition']) ? htmlspecialchars($copy['edition']) : '-'; ?></td>
                                                            <td><?php echo !empty($copy['series']) ? htmlspecialchars($copy['series']) : '-'; ?></td>
                                                            <td><?php echo !empty($copy['volume']) ? htmlspecialchars($copy['volume']) : '-'; ?></td>
                                                            <td><?php echo !empty($copy['part']) ? htmlspecialchars($copy['part']) : '-'; ?></td>
                                                            <td>
                                                                <span class="badge rounded-pill bg-<?php echo $copy['status'] == 'Available' ? 'success' : 'secondary'; ?>">
                                                                    <?php echo htmlspecialchars($copy['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($copy['shelf_location']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">No copies found.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-danger">Book not found.</div>
                    <?php endif; ?>
                </div>

                <!-- MARC 21 View Tab -->
                <div class="tab-pane fade" id="marc" role="tabpanel">
                    <?php if (isset($book)): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-code"></i> MARC21 Record</h6>
                        </div>
                        <div class="card-body">
                            <!-- MARC Display - Removing labeled display, keeping only plain MARC display -->
                            <div class="marc-record">
                                <pre class="marc-text">
<?php
// Helper function to format MARC values and dates
function formatMarcValue($value) {
    return trim(preg_replace('/\s+/', ' ', $value));
}

function formatMarc21Date($date) {
    return date('YmdHis', strtotime($date));
}

// Initialize arrays for different types of contributors
$author = isset($authorsList[0]) ? $authorsList[0] : '';
$coauthors = $coAuthorsList ?? [];
$editors = $editorsList ?? [];

// Define MARC21 fields
$marcFields = [];

// Leader/Control Fields
$marcFields[] = ['LDR', '', '', '00000nam a22000007a 4500'];
$marcFields[] = ['001', '', '', $book['accession']];
$marcFields[] = ['005', '', '', formatMarc21Date($book['date_added'])];

// Variable Fields
if (!empty($book['ISBN'])) {
    $marcFields[] = ['020', '##', 'a', $book['ISBN']];
}
$marcFields[] = ['040', '##', 'a', 'PH-MnNBS', 'c', 'PH-MnNBS'];
$marcFields[] = ['082', '04', 'a', $book['call_number']];

// Title and Author Fields
if (!empty($author)) {
    $marcFields[] = ['100', '1#', 'a', $author, 'e', 'author'];
}

// Statement of responsibility
$responsibilityStatement = [];
if (!empty($author)) $responsibilityStatement[] = $author;
if (!empty($coauthors)) $responsibilityStatement = array_merge($responsibilityStatement, $coauthors);

$marcFields[] = ['245', '10',
    'a', $book['title'],
    'c', implode(', ', $responsibilityStatement)
];

// Series information
if (!empty($book['series'])) {
    $seriesStatement = $book['series'];
    if (!empty($book['volume'])) {
        $seriesStatement .= ' ; v. ' . $book['volume'];
    }
    if (!empty($book['part'])) {
        $seriesStatement .= ', pt. ' . $book['part'];
    }
    $marcFields[] = ['490', '1#', 'a', $seriesStatement];
}

// Edition statement
if (!empty($book['edition'])) {
    $marcFields[] = ['250', '##', 'a', $book['edition']];
}

// Publication Information
if (!empty($publications)) {
    $pub = $publications[0];
    $marcFields[] = ['260', '##',
        'a', $pub['place'],
        'b', $pub['publisher'],
        'c', $pub['publish_date']
    ];
}

// Physical Description
$marcFields[] = ['300', '##',
    'a', $book['total_pages'] . ' pages',
    'b', 'illustrations',
    'c', $book['dimension'] . ' cm'
];

// Content/Media Type
$marcFields[] = ['336', '##', 'a', $book['content_type'], '2', 'rdacontent'];
$marcFields[] = ['337', '##', 'a', $book['media_type'], '2', 'rdamedia'];
$marcFields[] = ['338', '##', 'a', $book['carrier_type'], '2', 'rdacarrier'];

// Subject Headings
if (!empty($book['subject_detail'])) {
    $subjects = explode('|', $book['subject_detail']);
    foreach ($subjects as $subject) {
        $marcFields[] = ['650', '#0', 'a', trim($subject)];
    }
}

// Added Entries for Co-Authors and Editors
if (!empty($coauthors)) {
    foreach ($coauthors as $coauthor) {
        $marcFields[] = ['700', '1#', 'a', $coauthor, 'e', 'co-author'];
    }
}

if (!empty($editors)) {
    foreach ($editors as $editor) {
        $marcFields[] = ['700', '1#', 'a', $editor, 'e', 'editor'];
    }
}

// Holdings Information
$marcFields[] = ['852', '##',
    'a', $book['shelf_location'],
    'p', $book['accession']
];

// Display MARC fields in standard MARC format
foreach ($marcFields as $field) {
    echo str_pad($field[0], 3, ' ', STR_PAD_LEFT) . ' ';
    if ($field[0] >= '010') {
        echo $field[1] . ' ';
        for ($i = 2; $i < count($field); $i += 2) {
            if (!empty($field[$i + 1])) {
                echo '‡' . $field[$i] . formatMarcValue($field[$i + 1]) . ' ';
            }
        }
    } else {
        echo formatMarcValue($field[3]);
    }
    echo "\n";
}
?>
                                </pre>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-danger">Book not found.</div>
                    <?php endif; ?>
                </div>

                <!-- ISBD View Tab -->
                <div class="tab-pane fade" id="isbd" role="tabpanel">
                    <?php if (isset($book)): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> ISBD Record</h6>
                        </div>
                        <div class="card-body">
                            <div class="isbd-record">
                                <?php
                                // Title Line
                                echo '<div class="isbd-area">';
                                echo htmlspecialchars($book['title']);
                                
                                // Add series/volume/part when available
                                $additionalInfo = [];
                                if (!empty($book['series'])) {
                                    $additionalInfo[] = $book['series'];
                                }
                                if (!empty($book['volume'])) {
                                    $additionalInfo[] = 'volume ' . $book['volume'];
                                }
                                if (!empty($book['part'])) {
                                    $additionalInfo[] = 'part ' . $book['part'];
                                }
                                if (!empty($additionalInfo)) {
                                    echo ' (' . htmlspecialchars(implode(', ', $additionalInfo)) . ')';
                                }
                                
                                // Add edition when available
                                if (!empty($book['edition'])) {
                                    echo ' -- ' . htmlspecialchars($book['edition'] . ' edition');
                                }
                                echo '</div>';

                                // Author Line (surname first)
                                if (!empty($authorsList)) {
                                    echo '<div class="isbd-area">';
                                    echo htmlspecialchars($authorsList[0]);
                                    echo '</div>';
                                }

                                // Title/Author/Co-Authors/Editors-Place/Publisher/Year Line
                                echo '<div class="isbd-area">';
                                // Title part
                                echo htmlspecialchars($book['title']) . ' / ';

                                // Contributors part
                                $allContributors = array();
                                if (!empty($authorsList)) $allContributors[] = implode(', ', $authorsList);
                                if (!empty($coAuthorsList)) $allContributors[] = implode(', ', $coAuthorsList);
                                if (!empty($editorsList)) $allContributors[] = implode(', ', $editorsList);
                                echo htmlspecialchars(implode(', and ', $allContributors));

                                // Publication information
                                if (!empty($publications)) {
                                    $pub = $publications[0];
                                    echo ' - ' . htmlspecialchars($pub['place']) . ' ';
                                    echo htmlspecialchars($pub['publisher']) . ', ';
                                    echo htmlspecialchars($pub['publish_date']);
                                }

                                // Physical description
                                echo ' - ';
                                if (!empty($book['preliminaries'])) {
                                    echo htmlspecialchars($book['preliminaries']) . ', ';
                                }
                                echo htmlspecialchars($book['total_pages']) . ' pages';
                                if (!empty($book['illustrations'])) {
                                    echo ' : illustrations';
                                }
                                echo ' ; ' . htmlspecialchars($book['dimension']) . ' cm';
                                echo '</div>';

                                // ISBN Line
                                if (!empty($book['ISBN'])) {
                                    echo '<div class="isbd-area">';
                                    echo 'ISBN: ' . htmlspecialchars($book['ISBN']);
                                    echo '</div>';
                                }

                                // Subject Category and Details
                                if (!empty($book['subject_category']) || !empty($book['subject_detail'])) {
                                    echo '<div class="isbd-area">';
                                    if (!empty($book['subject_category'])) {
                                        echo 'Subject Category: ' . htmlspecialchars($book['subject_category']) . '<br>';
                                    }
                                    if (!empty($book['subject_detail'])) {
                                        $subjects = explode('|', $book['subject_detail']);
                                        foreach ($subjects as $subject) {
                                            echo htmlspecialchars($subject) . '<br>';
                                        }
                                    }
                                    echo '</div>';
                                }

                                // LC Classification Number
                                echo '<div class="isbd-area">';
                                echo 'LC Class No.: ' . htmlspecialchars($book['call_number']);
                                echo '</div>';

                                ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-danger">Book not found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include '../Admin/inc/footer.php'; ?>

    <!-- Scroll to Top Button -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Scripts section -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
    // Check login status
    var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    
    // Store all copy details in JavaScript for easy access
    const allCopiesDetails = <?php echo json_encode($allCopiesDetails ?? []); ?>;
    
    // Add click event listener for 'Add to Cart' button
    document.querySelector('.add-to-cart').addEventListener('click', function() {
        const bookData = {
            title: this.getAttribute('data-title'),
            isbn: this.getAttribute('data-isbn'),
            series: this.getAttribute('data-series'),
            volume: this.getAttribute('data-volume'),
            part: this.getAttribute('data-part'),
            edition: this.getAttribute('data-edition')
        };
        addToCart(bookData);
    });

    // Add click event listener for 'Borrow Book' button
    document.querySelector('.borrow-book').addEventListener('click', function() {
        const bookData = {
            title: this.getAttribute('data-title'),
            isbn: this.getAttribute('data-isbn'),
            series: this.getAttribute('data-series'),
            volume: this.getAttribute('data-volume'),
            part: this.getAttribute('data-part'),
            edition: this.getAttribute('data-edition')
        };
        borrowBook(bookData);
    });

    // Helper function to format book details for display
    function formatBookDetails(bookData) {
        let details = [bookData.title];
        let metaDetails = [];
        
        if (bookData.edition) metaDetails.push("Edition: " + bookData.edition);
        if (bookData.series) metaDetails.push("Series: " + bookData.series);
        if (bookData.volume) metaDetails.push("Volume: " + bookData.volume);
        if (bookData.part) metaDetails.push("Part: " + bookData.part);
        if (bookData.isbn) metaDetails.push("ISBN: " + bookData.isbn);
        
        if (metaDetails.length > 0) {
            details.push(metaDetails.join(" | "));
        }
        
        return details.join("<br>");
    }

    function addToCart(bookData) {
        if (!isLoggedIn) {
            Swal.fire({
                title: 'Please Login',
                text: 'You need to be logged in to add books to the cart.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            html: 'Do you want to add this book to the cart?<br><br><strong>' + formatBookDetails(bookData) + '</strong>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, add it!',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'add_to_cart.php',
                    type: 'POST',
                    data: bookData,
                    success: function(response) {
                        var res = JSON.parse(response);
                        Swal.fire({
                            title: res.success ? 'Added!' : 'Failed!',
                            html: res.message,
                            icon: res.success ? 'success' : 'error'
                        }).then(() => {
                            if (res.success) {
                                location.reload();
                            }
                        });
                    },
                    error: function() {
                        Swal.fire('Failed!', 'Failed to add book to cart.', 'error');
                    }
                });
            }
        });
    }

    function borrowBook(bookData) {
        if (!isLoggedIn) {
            Swal.fire({
                title: 'Please Login',
                text: 'You need to be logged in to borrow books.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            html: 'Do you want to borrow this book?<br><br><strong>' + formatBookDetails(bookData) + '</strong>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, borrow it!',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'reserve_book.php',
                    type: 'POST',
                    data: bookData,
                    success: function(response) {
                        var res = JSON.parse(response);
                        Swal.fire({
                            title: res.success ? 'Reserved!' : 'Failed!',
                            html: res.message,
                            icon: res.success ? 'success' : 'error'
                        }).then(() => {
                            if (res.success) {
                                location.reload();
                            }
                        });
                    },
                    error: function() {
                        Swal.fire('Failed!', 'Failed to reserve book.', 'error');
                    }
                });
            }
        });
    }

    // Function to update book details based on selected copy
    function updateBookDetails(copyId) {
        if (!allCopiesDetails[copyId]) return;
        
        const copyData = allCopiesDetails[copyId];
        const copy = copyData.copy;
        const contributors = copyData.contributors;
        const publications = copyData.publications;
        
        // Update accession badge
        document.getElementById('accession-badge').textContent = copy.accession;
        
        // Update book title
        document.getElementById('book-title').textContent = copy.title;
        document.getElementById('cover-title').textContent = copy.title;
        
        // Update book cover
        const bookCover = document.getElementById('book-cover');
        if (copy.front_image) {
            bookCover.src = copy.front_image;
        } else if (copy.cover_image) {
            bookCover.src = copy.cover_image;
        } else {
            bookCover.src = '../Admin/inc/upload/default-book.jpg';
        }
        
        // Update Quick Reference Information
        updateQuickReferenceField('quick-isbn', copy.ISBN);
        updateQuickReferenceField('quick-edition', copy.edition);
        updateQuickReferenceField('quick-series', copy.series);
        updateQuickReferenceField('quick-volume', copy.volume);
        updateQuickReferenceField('quick-part', copy.part);
        updateQuickReferenceField('quick-location', copy.shelf_location);
        
        // Update contributors (authors, co-authors, editors)
        const authorsValue = document.getElementById('authors-value');
        const coauthorsContainer = document.getElementById('coauthors-container');
        const coauthorsValue = document.getElementById('coauthors-value');
        const editorsContainer = document.getElementById('editors-container');
        const editorsValue = document.getElementById('editors-value');
        
        // Reset contributors
        authorsValue.textContent = '';
        coauthorsValue.textContent = '';
        editorsValue.textContent = '';
        
        // Group contributors by role
        const contributorsByRole = {
            'Author': [],
            'Co-Author': [],
            'Editor': []
        };
        
        contributors.forEach(contributor => {
            const name = contributor.firstname + ' ' + contributor.middle_init + ' ' + contributor.lastname;
            if (contributorsByRole[contributor.role]) {
                contributorsByRole[contributor.role].push(name);
            }
        });
        
        // Update authors
        authorsValue.textContent = contributorsByRole['Author'].join(', ');
        
        // Update co-authors
        if (contributorsByRole['Co-Author'].length > 0) {
            coauthorsValue.textContent = contributorsByRole['Co-Author'].join(', ');
            coauthorsContainer.style.display = 'flex';
        } else {
            coauthorsContainer.style.display = 'none';
        }
        
        // Update editors
        if (contributorsByRole['Editor'].length > 0) {
            editorsValue.textContent = contributorsByRole['Editor'].join(', ');
            editorsContainer.style.display = 'flex';
        } else {
            editorsContainer.style.display = 'none';
        }
        
        // Update other bibliographic fields
        updateFieldVisibility('isbn', copy.ISBN);
        updateFieldVisibility('edition', copy.edition);
        updateFieldVisibility('series', copy.series);
        updateFieldVisibility('volume', copy.volume);
        updateFieldVisibility('part', copy.part);
        document.getElementById('shelf-location-value').textContent = copy.shelf_location;
        
        // Update content type
        document.getElementById('content-type-value').textContent = copy.content_type;
        
        // Update publication information
        if (publications.length > 0) {
            const pub = publications[0];
            document.getElementById('publisher-value').textContent = pub.publisher;
            document.getElementById('publish-location-value').textContent = pub.place;
            document.getElementById('publish-date-value').textContent = pub.publish_date;
            document.getElementById('publisher-container').style.display = 'flex';
            document.getElementById('publish-location-container').style.display = 'flex';
            document.getElementById('publish-date-container').style.display = 'flex';
        } else {
            document.getElementById('publisher-container').style.display = 'none';
            document.getElementById('publish-location-container').style.display = 'none';
            document.getElementById('publish-date-container').style.display = 'none';
        }
        
        // Update physical description
        document.getElementById('physical-desc-value').textContent = 
            copy.total_pages + ' pages ; ' + copy.dimension + ' cm';
        
        // Update subjects
        const subjectsContainer = document.getElementById('subjects-container');
        const subjectsValue = document.getElementById('subjects-value');
        
        if (copy.subject_category || copy.subject_detail) {
            let subjects = [];
            if (copy.subject_category) subjects.push(copy.subject_category);
            if (copy.subject_detail) {
                const detailedSubjects = copy.subject_detail.split('|');
                subjects = subjects.concat(detailedSubjects);
            }
            subjectsValue.textContent = subjects.join(' -- ');
            subjectsContainer.style.display = 'flex';
        } else {
            subjectsContainer.style.display = 'none';
        }
        
        // Update call number
        document.getElementById('call-number-value').textContent = copy.call_number;
        
        // Update status badge
        const statusBadge = document.getElementById('status-badge');
        statusBadge.textContent = copy.status;
        statusBadge.className = 'badge rounded-pill bg-' + (copy.status === 'Available' ? 'success' : 'secondary');
        
        // Update action buttons with new copy data
        updateActionButtons(copy);
    }
    
    // Helper function to update field visibility and value
    function updateFieldVisibility(fieldId, value) {
        const container = document.getElementById(fieldId + '-container');
        const valueElement = document.getElementById(fieldId + '-value');
        
        if (value && value.trim() !== '') {
            valueElement.textContent = value;
            container.style.display = 'flex';
        } else {
            container.style.display = 'none';
        }
    }
    
    // Helper function to update quick reference fields
    function updateQuickReferenceField(fieldId, value) {
        const element = document.getElementById(fieldId);
        if (!element) return;
        
        const parentDiv = element.closest('.quick-detail');
        
        if (value && value.trim() !== '') {
            element.textContent = value;
            if (parentDiv) parentDiv.style.display = 'block';
        } else {
            if (parentDiv) parentDiv.style.display = 'none';
        }
    }
    
    // Update action buttons with new copy data
    function updateActionButtons(copy) {
        const addToCartBtn = document.querySelector('.add-to-cart');
        const borrowBookBtn = document.querySelector('.borrow-book');
        
        addToCartBtn.setAttribute('data-title', copy.title);
        addToCartBtn.setAttribute('data-isbn', copy.ISBN || '');
        addToCartBtn.setAttribute('data-series', copy.series || '');
        addToCartBtn.setAttribute('data-volume', copy.volume || '');
        addToCartBtn.setAttribute('data-part', copy.part || '');
        addToCartBtn.setAttribute('data-edition', copy.edition || '');
        
        borrowBookBtn.setAttribute('data-title', copy.title);
        borrowBookBtn.setAttribute('data-isbn', copy.ISBN || '');
        borrowBookBtn.setAttribute('data-series', copy.series || '');
        borrowBookBtn.setAttribute('data-volume', copy.volume || '');
        borrowBookBtn.setAttribute('data-part', copy.part || '');
        borrowBookBtn.setAttribute('data-edition', copy.edition || '');
    }

    // Handle tab switching and holdings row clicks
    document.addEventListener('DOMContentLoaded', function() {
        // Add click handlers for holdings rows
        document.querySelectorAll('.holding-row').forEach(row => {
            row.addEventListener('click', function() {
                const copyId = this.getAttribute('data-copy-id');
                
                // Remove selected class from all rows
                document.querySelectorAll('.holding-row').forEach(r => {
                    r.classList.remove('selected');
                });
                
                // Add selected class to clicked row
                this.classList.add('selected');
                
                // Update the displayed book details
                updateBookDetails(copyId);
                
                // Update URL without refreshing the page
                const url = new URL(window.location.href);
                url.searchParams.set('copy_id', copyId);
                history.replaceState(null, '', url);
            });
        });
    });
    </script>
</body>
</html>