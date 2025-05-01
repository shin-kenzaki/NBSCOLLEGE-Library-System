<?php
session_start();

// Handle book deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['delete_book_id'])) {
        $bookId = intval($input['delete_book_id']);

        // Start transaction
        $conn->begin_transaction();
        try {
            // Delete related contributors first
            $contribQuery = "DELETE FROM contributors WHERE book_id = ?";
            $contribStmt = $conn->prepare($contribQuery);
            $contribStmt->bind_param('i', $bookId);
            $contribStmt->execute();

            // Delete related publications
            $pubQuery = "DELETE FROM publications WHERE book_id = ?";
            $pubStmt = $conn->prepare($pubQuery);
            $pubStmt->bind_param('i', $bookId);
            $pubStmt->execute();

            // Delete the book
            $bookQuery = "DELETE FROM books WHERE id = ?";
            $bookStmt = $conn->prepare($bookQuery);
            $bookStmt->bind_param('i', $bookId);
            $bookStmt->execute();

            $conn->commit();
            http_response_code(200);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
        }
        exit;
    }
}

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../db.php'; // Database connection

// Add this function near the top of the file after database connection
function parseDimension($dimension) {
    $dimensions = ['height' => '', 'width' => ''];
    if (!empty($dimension)) {
        // Check if dimension contains 'x' separator
        if (strpos($dimension, 'x') !== false) {
            list($height, $width) = array_map('trim', explode('x', $dimension));
            $dimensions['height'] = $height;
            $dimensions['width'] = $width;
        } else {
            // If single number, use it for both height and width
            $dimensions['height'] = $dimensions['width'] = trim($dimension);
        }
    }
    return $dimensions;
}

// Get the book ID from the query parameters
$bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

if ($bookId > 0) {
    // Fetch book details from the database
    $query = "SELECT * FROM books WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $book = $result->fetch_assoc();

        // Fetch total copies and Available count
        $copiesQuery = "SELECT COUNT(*) as total_copies, SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as in_shelf FROM books WHERE title = ?";
        $stmt = $conn->prepare($copiesQuery);
        $stmt->bind_param("s", $book['title']);
        $stmt->execute();
        $copiesResult = $stmt->get_result();
        $copies = $copiesResult->fetch_assoc();
        $totalCopies = $copies['total_copies'];
        $inShelf = $copies['in_shelf'];

    } else {
        $error = "Book not found.";
    }

    // Modify the contributors query to properly join with writers table and get author
    $contributorsQuery = "SELECT c.*, w.firstname, w.middle_init, w.lastname, c.role
                         FROM contributors c
                         JOIN writers w ON c.writer_id = w.id
                         WHERE c.book_id = ? AND c.role = 'Author'
                         LIMIT 1";
    $stmt = $conn->prepare($contributorsQuery);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $contributorResult = $stmt->get_result();
    $primaryAuthor = '';

    if ($contributorResult && $contributorResult->num_rows > 0) {
        $author = $contributorResult->fetch_assoc();
        $primaryAuthor = $author['lastname'] . ', ' . $author['firstname'] . ' ' . $author['middle_init'];
    }

    // Then get all contributors for the full list
    $allContributorsQuery = "SELECT c.*, w.firstname, w.middle_init, w.lastname
                            FROM contributors c
                            JOIN writers w ON c.writer_id = w.id
                            WHERE c.book_id = ?";
    $stmt = $conn->prepare($allContributorsQuery);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $contributorsResult = $stmt->get_result();
    $contributors = [];
    while ($row = $contributorsResult->fetch_assoc()) {
        $contributors[] = $row;
    }

    // Fetch publications from the database
    $publicationsQuery = "SELECT p.*, pub.publisher, pub.place FROM publications p JOIN publishers pub ON p.publisher_id = pub.id WHERE p.book_id = $bookId";
    $publicationsResult = $conn->query($publicationsQuery);
    $publications = [];
    while ($row = $publicationsResult->fetch_assoc()) {
        $publications[] = $row;
    }

    // Fetch all copies of the book
    $allCopiesQuery = "SELECT * FROM books WHERE title = ?";
    $stmt = $conn->prepare($allCopiesQuery);
    $stmt->bind_param("s", $book['title']);
    $stmt->execute();
    $allCopiesResult = $stmt->get_result();
    $allCopies = [];
    while ($row = $allCopiesResult->fetch_assoc()) {
        $allCopies[] = $row;
    }
} else {
    $error = "Invalid book ID.";
}

// Export functionality
if (isset($_GET['export']) && in_array($_GET['export'], ['standard', 'marc21', 'isbd'])) {
    $exportType = $_GET['export'];

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="book_export_' . $exportType . '.txt"');

    // Ensure $marcFields is initialized before export functionality
    $marcFields = [];
    if (isset($book)) {
        // Leader/Control Fields
        $marcFields[] = ['LDR', '', '', '00000nam a22000007a 4500'];
        $marcFields[] = ['001', '', '', $book['accession']];
        $marcFields[] = ['005', '', '', date('YmdHis', strtotime($book['date_added']))];

        // Variable Fields
        $marcFields[] = ['020', '##', 'a', $book['ISBN']];
        $marcFields[] = ['040', '##', 'a', 'PH-MnNBS', 'c', 'PH-MnNBS'];
        $marcFields[] = ['082', '04', 'a', $book['call_number']];

        // Title and Author Fields
        if (!empty($primaryAuthor)) {
            $marcFields[] = ['100', '1#', 'a', $primaryAuthor, 'e', 'author'];
        }

        $marcFields[] = ['245', '10', 'a', $book['title'], 'c', $primaryAuthor ?? ''];

        // Publication Information
        if (!empty($publications)) {
            $pub = $publications[0];
            $marcFields[] = ['260', '##', 'a', $pub['place'], 'b', $pub['publisher'], 'c', $pub['publish_date']];
        }

        // Physical Description - removed " cm" from dimension and " pages" from total_pages
        $marcFields[] = ['300', '##', 'a', $book['total_pages'], 'c', $book['dimension']];

        // Subject Headings
        if (!empty($book['subject_category'])) {
            foreach (explode(';', $book['subject_category']) as $subject) {
                $marcFields[] = ['650', '#0', 'a', trim($subject)];
            }
        }

        // Contributors
        foreach ($contributors as $contributor) {
            if ($contributor['role'] !== 'Author') {
                $marcFields[] = ['700', '1#', 'a', $contributor['lastname'] . ', ' . $contributor['firstname'] . ' ' . $contributor['middle_init'], 'e', strtolower($contributor['role'])];
            }
        }

        // Holdings Information
        $marcFields[] = ['852', '##', 'a', $book['shelf_location'], 'p', $book['accession']];
    }

    if ($exportType === 'standard') {
        echo "Standard View Export\n";
        echo "Title: " . htmlspecialchars($book['title']) . "\n";
        echo "Author: " . htmlspecialchars($primaryAuthor) . "\n";
        echo "Accession: " . htmlspecialchars($book['accession']) . "\n";
        echo "Call Number: " . htmlspecialchars($book['call_number']) . "\n";
        echo "ISBN: " . htmlspecialchars($book['ISBN']) . "\n";
        echo "Language: " . htmlspecialchars($book['language']) . "\n";
        // Physical Description - removed " pages" and " cm"
        echo "Physical Description: " . htmlspecialchars($book['total_pages']) . ", " . htmlspecialchars($book['dimension']) . "\n";
        echo "Publication: " . htmlspecialchars($publications[0]['place'] ?? 'N/A') . "; " . htmlspecialchars($publications[0]['publisher'] ?? 'N/A') . ", " . htmlspecialchars($publications[0]['publish_date'] ?? 'N/A') . "\n";
    } elseif ($exportType === 'marc21') {
        echo "MARC21 Export\n";
        foreach ($marcFields as $field) {
            echo $field[0] . " " . $field[1] . " ";
            for ($i = 2; $i < count($field); $i += 2) {
                if (!empty($field[$i + 1])) { // Ensure subfield value is not empty
                    echo "$" . $field[$i] . " " . htmlspecialchars($field[$i + 1]) . " ";
                }
            }
            echo "\n";
        }
        exit;
    } elseif ($exportType === 'isbd') {
        echo "ISBD Export\n";
        echo htmlspecialchars($book['title']) . " / " . htmlspecialchars($primaryAuthor) . "\n";
        echo htmlspecialchars($publications[0]['place'] ?? 'N/A') . " : " . htmlspecialchars($publications[0]['publisher'] ?? 'N/A') . ", " . htmlspecialchars($publications[0]['publish_date'] ?? 'N/A') . "\n";
        // Physical Description - removed " pages" and " cm"
        echo htmlspecialchars($book['total_pages']) . " ; " . htmlspecialchars($book['dimension']) . "\n";
        echo "ISBN: " . htmlspecialchars($book['ISBN']) . "\n";
    }

    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OPAC - Book Details</title>
    <style>
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
        /* Add style rule to center all table columns */
        table.table th,
        table.table td {
            text-align: center;
        }

        /* Add these new styles for smoother card corners */
        .card {
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
        }

        .card-body, .card-footer {
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .list-group-item {
            border-radius: 8px !important;
            margin-bottom: 4px;
        }

        img, .img-fluid {
            border-radius: 10px;
        }

        .badge {
            border-radius: 20px;
            padding: 8px 12px;
        }

        .btn {
            border-radius: 8px;
        }

        .alert {
            border-radius: 10px;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        /* MARC view styling */
        .marc-field {
            border-radius: 10px;
        }

        /* ISBD view styling */
        .isbd-record {
            border-radius: 12px;
        }

        /* Tab styling */
        .nav-tabs .nav-link {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        /* Improve spacing */
        .card-body {
            padding: 1.5rem;
        }

        /* Shadow styling for more depth */
        .shadow-sm {
            box-shadow: 0 4px 10px rgba(0,0,0,0.07) !important;
        }

        .shadow {
            box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
        }

        /* Table responsive scrolling styles */
        .table-responsive-horizontal {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            position: relative;
        }

        .table-holdings {
            width: 100%;
            min-width: 970px; /* Ensure table expands beyond screen width on small devices */
            border-collapse: separate;
            border-spacing: 0;
        }

        .scroll-indicator {
            color: #6c757d;
            padding: 8px;
            font-size: 14px;
            background-color: #f8f9fa;
            border-radius: 5px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        /* Fix horizontal scrollbar visibility on small screens */
        @media (max-width: 768px) {
            .table-responsive-horizontal::-webkit-scrollbar {
                height: 6px;
            }

            .table-responsive-horizontal::-webkit-scrollbar-track {
                background: #f1f1f1;
            }

            .table-responsive-horizontal::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 3px;
            }

            .table-responsive-horizontal::-webkit-scrollbar-thumb:hover {
                background: #555;
            }

            .table-holdings th,
            .table-holdings td {
                white-space: nowrap;
            }
        }
    </style>
    <!-- Add Bootstrap CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include '../admin/inc/header.php'; ?>

    <!-- Instructions Button - Add to top of page -->
    <div class="container-fluid">
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#instructionsModal">
                <i class="fas fa-question-circle"></i> How to Use OPAC
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div id="content" class="d-flex flex-column min-vh-100">
        <div class="container-fluid px-4">
            <!-- Update tab navigation -->
            <div class="d-flex justify-content-between align-items-center mb-3">
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
                <!-- Export buttons with icons -->
                <?php if (isset($book)): ?>
                    <div class="d-flex">
                        <a href="?book_id=<?php echo $bookId; ?>&export=standard" class="btn btn-primary btn-sm me-2">
                            <i class="fas fa-file-alt"></i> Export Standard View
                        </a>
                        <a href="?book_id=<?php echo $bookId; ?>&export=marc21" class="btn btn-success btn-sm me-2">
                            <i class="fas fa-code"></i> Export MARC21
                        </a>
                        <a href="?book_id=<?php echo $bookId; ?>&export=isbd" class="btn btn-info btn-sm">
                            <i class="fas fa-book"></i> Export ISBD
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-content" id="bookDetailsContent">
                <!-- Standard View Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <?php if (isset($book)): ?>
                        <!-- Main Book Information Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold text-primary">Bibliographic Information</h5>
                                <div class="badge bg-<?php echo ($inShelf > 0) ? 'success' : 'danger'; ?> p-2">
                                    <?php echo ($inShelf > 0) ? 'Available' : 'Not Available'; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Book Images -->
                                    <div class="col-md-3">
                                        <!-- Display Front Image -->
                                        <?php if (!empty($book['front_image'])): ?>
                                            <img src="<?php echo htmlspecialchars('../' . $book['front_image']); ?>" alt="Front Cover" class="img-fluid mb-3 rounded shadow-sm">
                                        <?php else: ?>
                                            <div class="text-center p-4 bg-light rounded mb-3">
                                                <i class="fas fa-book fa-4x text-secondary"></i>
                                                <p class="mt-2 text-muted">No front cover image</p>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Display Back Image -->
                                        <?php if (!empty($book['back_image'])): ?>
                                            <img src="<?php echo htmlspecialchars('../' . $book['back_image']); ?>" alt="Back Cover" class="img-fluid rounded shadow-sm">
                                        <?php else: ?>
                                            <div class="text-center p-4 bg-light rounded mb-3">
                                                <i class="fas fa-book fa-4x text-secondary"></i>
                                                <p class="mt-2 text-muted">No back cover image</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Main Book Details -->
                                    <div class="col-md-9">
                                        <!-- Title and Author -->
                                        <h4 class="text-dark mb-2"><?php echo htmlspecialchars($book['title']); ?></h4>

                                        <?php
                                        $authorDisplayed = false;
                                        if (!empty($contributors)) {
                                            foreach ($contributors as $contributor) {
                                                if ($contributor['role'] === 'Author') {
                                                    echo '<h6 class="text-muted mb-4">By: ' . htmlspecialchars($contributor['lastname'] . ', ' . $contributor['firstname'] . ' ' . $contributor['middle_init']) . '</h6>';
                                                    $authorDisplayed = true;
                                                    break;
                                                }
                                            }
                                        }
                                        if (!$authorDisplayed) {
                                            echo '<h6 class="text-muted mb-4">Author: Not specified</h6>';
                                        }
                                        ?>

                                        <!-- Book Details in Columns -->
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="mb-2"><strong>Accession:</strong> <?php echo htmlspecialchars($book['accession']); ?></div>
                                                <div class="mb-2"><strong>Call Number:</strong> <?php echo htmlspecialchars($book['call_number']); ?></div>
                                                <div class="mb-2"><strong>Copy Number:</strong> <?php echo htmlspecialchars($book['copy_number']); ?></div>
                                                <div class="mb-2"><strong>ISBN:</strong> <?php echo !empty($book['ISBN']) ? htmlspecialchars($book['ISBN']) : 'N/A'; ?></div>
                                                <div class="mb-2"><strong>Language:</strong> <?php echo !empty($book['language']) ? htmlspecialchars($book['language']) : 'N/A'; ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-2"><strong>Series:</strong> <?php echo !empty($book['series']) ? htmlspecialchars($book['series']) : 'N/A'; ?></div>
                                                <div class="mb-2"><strong>Volume:</strong> <?php echo !empty($book['volume']) ? htmlspecialchars($book['volume']) : 'N/A'; ?></div>
                                                <div class="mb-2"><strong>Part:</strong> <?php echo !empty($book['part']) ? htmlspecialchars($book['part']) : 'N/A'; ?></div>
                                                <div class="mb-2"><strong>Edition:</strong> <?php echo !empty($book['edition']) ? htmlspecialchars($book['edition']) : 'N/A'; ?></div>
                                                <div class="mb-2"><strong>Location:</strong> <?php echo !empty($book['shelf_location']) ? htmlspecialchars($book['shelf_location']) : 'N/A'; ?></div>
                                                <div class="mb-2"><strong>Availability:</strong> <span class="text-<?php echo ($inShelf > 0) ? 'success' : 'danger'; ?> fw-bold"><?php echo htmlspecialchars($inShelf); ?> of <?php echo htmlspecialchars($totalCopies); ?> copies</span></div>
                                            </div>
                                        </div>

                                        <!-- Physical Description -->
                                        <div class="mb-3">
                                            <strong>Physical Description:</strong>
                                            <?php
                                                $physDesc = [];
                                                if (!empty($book['total_pages'])) {
                                                    // Removed " pages" from total_pages
                                                    $physDesc[] = htmlspecialchars($book['total_pages']);
                                                }
                                                if (!empty($book['supplementary_contents'])) {
                                                    $physDesc[] = htmlspecialchars($book['supplementary_contents']);
                                                }
                                                if (!empty($book['dimension'])) {
                                                    // Dimension already modified to not include " cm"
                                                    $physDesc[] = htmlspecialchars($book['dimension']);
                                                }
                                                echo !empty($physDesc) ? implode(', ', $physDesc) : 'Information not available';
                                            ?>
                                        </div>

                                        <!-- Publication Information -->
                                        <div class="mb-3">
                                            <strong>Publication:</strong>
                                            <?php
                                            if (!empty($publications)) {
                                                $pub = $publications[0];
                                                echo htmlspecialchars($pub['place'] . '; ' . $pub['publisher'] . ', ' . $pub['publish_date']);
                                            } else {
                                                echo 'Information not available';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Content Details Card -->
                        <div class="row">
                            <!-- Summary Column -->
                            <div class="col-md-6">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Summary</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($book['summary'])): ?>
                                            <p class="card-text"><?php echo nl2br(htmlspecialchars($book['summary'])); ?></p>
                                        <?php else: ?>
                                            <p class="text-muted font-italic">No summary available.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Contents Column -->
                            <div class="col-md-6">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Table of Contents</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($book['contents'])): ?>
                                            <p class="card-text"><?php echo nl2br(htmlspecialchars($book['contents'])); ?></p>
                                        <?php else: ?>
                                            <p class="text-muted font-italic">No contents information available.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Subject Information Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Subject Information</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($book['subject_category']) || !empty($book['subject_specification']) || !empty($book['subject_detail']) || !empty($book['program'])): ?>
                                    <div class="mb-4">
                                        <!-- Subject Categories and Dedicated Program in the same row -->
                                        <div class="row mb-3">
                                            <!-- Subject Categories Column -->
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <?php if (!empty($book['subject_category'])): ?>
                                                    <h6 class="text-dark mb-2">Subject Categories</h6>
                                                    <ul class="list-group">
                                                        <?php foreach (explode(';', $book['subject_category']) as $category): ?>
                                                            <li class="list-group-item bg-light"><?php echo htmlspecialchars(trim($category)); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <h6 class="text-dark mb-2">Subject Categories</h6>
                                                    <ul class="list-group">
                                                        <li class="list-group-item bg-light text-muted">No subject categories available</li>
                                                    </ul>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Dedicated Program Column -->
                                            <div class="col-md-6">
                                                <?php if (!empty($book['program'])): ?>
                                                    <h6 class="text-dark mb-2">Dedicated Program</h6>
                                                    <ul class="list-group">
                                                        <li class="list-group-item bg-light"><?php echo htmlspecialchars($book['program']); ?></li>
                                                    </ul>
                                                <?php else: ?>
                                                    <h6 class="text-dark mb-2">Dedicated Program</h6>
                                                    <ul class="list-group">
                                                        <li class="list-group-item bg-light text-muted">No program specified</li>
                                                    </ul>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Subject Details Section -->
                                        <h6 class="text-dark mb-2">Subject Details</h6>
                                        <div>
                                            <ul class="list-group">
                                                <?php if (!empty($book['subject_specification'])): ?>
                                                    <?php foreach (explode(';', $book['subject_specification']) as $spec): ?>
                                                        <li class="list-group-item bg-light"><strong>Specific:</strong> <?php echo htmlspecialchars(trim($spec)); ?></li>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>

                                                <?php if (!empty($book['subject_detail'])): ?>
                                                    <?php foreach (explode(';', $book['subject_detail']) as $detail): ?>
                                                        <li class="list-group-item bg-light"><?php echo htmlspecialchars(trim($detail)); ?></li>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                
                                                <?php if (empty($book['subject_specification']) && empty($book['subject_detail'])): ?>
                                                    <li class="list-group-item bg-light text-muted">No detailed subject information available</li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted font-italic">No subject information available.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Contributors Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Contributors</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($contributors)): ?>
                                    <div class="row">
                                        <?php
                                        $contributorsByRole = [];
                                        foreach ($contributors as $contributor) {
                                            $role = $contributor['role'];
                                            if (!isset($contributorsByRole[$role])) {
                                                $contributorsByRole[$role] = [];
                                            }
                                            $contributorsByRole[$role][] = $contributor;
                                        }

                                        foreach ($contributorsByRole as $role => $people): ?>
                                            <div class="col-md-4 mb-3">
                                                <h6 class="text-dark"><?php echo htmlspecialchars($role); ?></h6>
                                                <ul class="list-group list-group-flush">
                                                    <?php foreach ($people as $person): ?>
                                                        <li class="list-group-item bg-light">
                                                            <?php echo htmlspecialchars($person['firstname'] . ' ' . $person['middle_init'] . ' ' . $person['lastname']); ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted font-italic">No contributors found.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Holdings Information Card -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Available Copies</h6>
                                <div class="d-flex align-items-center">
                                    <div class="badge bg-primary p-2 me-3">
                                        Total Copies: <?php echo htmlspecialchars($totalCopies); ?> |
                                        Available: <?php echo htmlspecialchars($inShelf); ?>
                                    </div>
                                    <div class="btn-group">
                                        <a href="export_barcodes.php?title=<?php echo urlencode($book['title']); ?>"
                                           class="btn btn-sm btn-primary"
                                           target="_blank">
                                            <i class="fas fa-download"></i> Export Barcodes
                                        </a>
                                        <?php if (in_array($_SESSION['role'], ['Admin', 'Librarian'])): ?>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDeleteAllCopies('<?php echo htmlspecialchars($book['title']); ?>')">
                                            <i class="fas fa-trash"></i> Delete All Copies
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($allCopies)): ?>
                                    <div class="table-responsive-horizontal">
                                        <div class="scroll-indicator d-block d-lg-none text-center mb-2">
                                            <i class="fas fa-arrows-alt-h"></i> Swipe horizontally to see more details
                                        </div>
                                        <table class="table table-bordered table-striped table-holdings" width="100%" cellspacing="0">
                                            <thead class="bg-primary text-white">
                                                <tr>
                                                    <th style="min-width: 100px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Accession</th>
                                                    <th style="min-width: 120px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Call Number</th>
                                                    <th style="min-width: 60px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Copy</th>
                                                    <th style="min-width: 90px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Status</th>
                                                    <th style="min-width: 120px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Location</th>
                                                    <th style="min-width: 100px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Last Update</th>
                                                    <th style="min-width: 100px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Series</th>
                                                    <th style="min-width: 80px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Volume</th>
                                                    <th style="min-width: 80px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Part</th>
                                                    <th style="min-width: 80px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Edition</th>
                                                    <th style="min-width: 120px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">ISBN</th>
                                                    <th style="min-width: 80px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="table-group-divider">
                                                <?php foreach ($allCopies as $index => $copy): ?>
                                                    <tr data-book-id="<?php echo htmlspecialchars($copy['id']); ?>" 
                                                        class="copy-row <?php echo ($copy['id'] == $bookId) ? 'table-primary' : ($index % 2 == 0 ? 'table-light' : 'table-white'); ?>"
                                                        style="transition: all 0.2s ease;">
                                                        <td style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($copy['accession']); ?></td>
                                                        <td style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($copy['call_number']); ?></td>
                                                        <td style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($copy['copy_number']); ?></td>
                                                        <td style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                            <span class="badge bg-<?php echo ($copy['status'] == 'Available') ? 'success' : 'warning'; ?> text-white fw-bold">
                                                                <?php echo htmlspecialchars($copy['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($copy['shelf_location']); ?></td>
                                                        <td style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars(date('Y-m-d', strtotime($copy['last_update']))); ?></td>
                                                        <td style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo !empty($copy['series']) ? htmlspecialchars($copy['series']) : '-'; ?></td>
                                                        <td style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo !empty($copy['volume']) ? htmlspecialchars($copy['volume']) : '-'; ?></td>
                                                        <td style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo !empty($copy['part']) ? htmlspecialchars($copy['part']) : '-'; ?></td>
                                                        <td style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo !empty($copy['edition']) ? htmlspecialchars($copy['edition']) : '-'; ?></td>
                                                        <td style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo !empty($copy['ISBN']) ? htmlspecialchars($copy['ISBN']) : '-'; ?></td>
                                                        <td style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteCopy(<?php echo htmlspecialchars($copy['id']); ?>, '<?php echo htmlspecialchars($copy['accession']); ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
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

                    <?php else: ?>
                        <div class="alert alert-danger">Book not found.</div>
                    <?php endif; ?>
                </div>

                <!-- MARC View Tab -->
                <div class="tab-pane fade" id="marc" role="tabpanel">
                    <h5 class="text-primary font-weight-bold mb-3">MARC21 Record View</h5>

                    <div class="marc-record-container">
                        <?php
                        // MARC field descriptions
                        $fieldDescriptions = [
                            'LDR' => 'LEADER',
                            '001' => 'Control Number',
                            '005' => 'Date and Time of Latest Transaction',
                            '020' => 'International Standard Book Number',
                            '040' => 'Cataloging Source',
                            '082' => 'Dewey Decimal Classification Number',
                            '100' => 'Main Entry - Personal Name',
                            '245' => 'Title Statement',
                            '260' => 'Publication, Distribution, etc.',
                            '300' => 'Physical Description',
                            '336' => 'Content Type',
                            '337' => 'Media Type',
                            '338' => 'Carrier Type',
                            '650' => 'Subject Added Entry - Topical Term',
                            '700' => 'Added Entry - Personal Name',
                            '852' => 'Location'
                        ];

                        // Helper function to format MARC values and date function
                        function formatMarcValue($value) {
                            return trim(preg_replace('/\s+/', ' ', $value));
                        }

                        function formatMarc21Date($date) {
                            return date('YmdHis', strtotime($date));
                        }

                        // Subfield descriptions
                        $subfieldDescriptions = [
                            'a' => 'Main entry/Primary information',
                            'b' => 'Name of publisher, distributor, etc.',
                            'c' => 'Statement of responsibility/Publication date',
                            'd' => 'Place of publication',
                            'e' => 'Relator term',
                            'n' => 'Number of part/section',
                            'p' => 'Name of part/section',
                            '2' => 'Source of term',
                            '6' => 'Linkage',
                            'x' => 'Subject subdivision'
                        ];

                        if (isset($book)) {
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
                            $marcFields[] = ['020', '##', 'a', $book['ISBN']];
                            $marcFields[] = ['040', '##', 'a', 'PH-MnNBS', 'c', 'PH-MnNBS'];
                            $marcFields[] = ['082', '04', 'a', $book['call_number']];

                            // Title and Author Fields
                            if (!empty($primaryAuthor)) {
                                $marcFields[] = ['100', '1#', 'a', $primaryAuthor, 'e', 'author'];
                            }

                            // Statement of responsibility
                            $responsibilityStatement = [];
                            if (!empty($primaryAuthor)) $responsibilityStatement[] = $primaryAuthor;

                            $marcFields[] = ['245', '10',
                                'a', $book['title'],
                                'c', !empty($responsibilityStatement) ? implode(', ', $responsibilityStatement) : ''
                            ];

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
                                'a', $book['total_pages'] . (isset($book['supplementary_contents']) ? ' ' . $book['supplementary_contents'] : ''),
                                'c', $book['dimension']
                            ];

                            // Content/Media Type
                            if (!empty($book['content_type'])) {
                                $marcFields[] = ['336', '##', 'a', $book['content_type'], '2', 'rdacontent'];
                            } else {
                                $marcFields[] = ['336', '##', 'a', 'text', '2', 'rdacontent'];
                            }

                            if (!empty($book['media_type'])) {
                                $marcFields[] = ['337', '##', 'a', $book['media_type'], '2', 'rdamedia'];
                            } else {
                                $marcFields[] = ['337', '##', 'a', 'unmediated', '2', 'rdamedia'];
                            }

                            if (!empty($book['carrier_type'])) {
                                $marcFields[] = ['338', '##', 'a', $book['carrier_type'], '2', 'rdacarrier'];
                            } else {
                                $marcFields[] = ['338', '##', 'a', 'volume', '2', 'rdacarrier'];
                            }

                            // Subject Headings
                            if (!empty($book['subject_category'])) {
                                $subjects = explode(';', $book['subject_category']);
                                foreach ($subjects as $subject) {
                                    $marcFields[] = ['650', '#0', 'a', trim($subject)];
                                }
                            }
                            if (!empty($book['subject_detail'])) {
                                $details = explode(';', $book['subject_detail']);
                                foreach ($details as $detail) {
                                    $marcFields[] = ['650', '#0', 'x', trim($detail)];
                                }
                            }

                            // Added Entries for Contributors
                            foreach ($contributors as $contributor) {
                                if ($contributor['role'] !== 'Author') { // Skip primary author already in 100 field
                                    $marcFields[] = ['700', '1#',
                                        'a', $contributor['lastname'] . ', ' . $contributor['firstname'] . ' ' . $contributor['middle_init'],
                                        'e', strtolower($contributor['role'])
                                    ];
                                }
                            }

                            // Holdings Information
                            $marcFields[] = ['852', '##',
                                'a', $book['shelf_location'],
                                'p', $book['accession']
                            ];
                        }

                        foreach ($marcFields as $field):
                            $isControlField = ($field[0] === 'LDR' || $field[0] < '010');
                            $fieldType = $isControlField ? 'control' : 'data';
                        ?>
                            <div class="marc-field marc-<?= $fieldType ?>-field">
                                <div class="marc-field-tag"><?= $field[0] ?></div>
                                <div class="marc-field-content">
                                    <div class="marc-field-header">
                                        <span class="marc-field-name"><?= $fieldDescriptions[$field[0]] ?? 'Field ' . $field[0] ?></span>
                                        <?php if (!$isControlField): ?>
                                            <span class="marc-indicators"><?= $field[1] ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($isControlField): ?>
                                        <div class="marc-field-value">
                                            <?= htmlspecialchars($field[3]) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="marc-subfields">
                                            <?php for ($i = 2; $i < count($field); $i += 2): ?>
                                                <?php if (!empty($field[$i + 1])): ?>
                                                    <div class="marc-subfield">
                                                        <span class="marc-subfield-code"
                                                              title="<?= $subfieldDescriptions[$field[$i]] ?? 'Subfield ' . $field[$i] ?>">
                                                            $<?= $field[$i] ?>
                                                        </span>
                                                        <span class="marc-subfield-value">
                                                            <?= htmlspecialchars(formatMarcValue($field[$i + 1])) ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <style>
                        .marc-record-container {
                            font-family: 'Roboto Mono', monospace;
                            background-color: #f8f9fa;
                            border-radius: 8px;
                            padding: 20px;
                            box-shadow: 0 4px 10px rgba(0,0,0,0.07);
                            margin-bottom: 20px;
                        }

                        .marc-field {
                            display: flex;
                            margin-bottom: 10px;
                            border-radius: 6px;
                            overflow: hidden;
                            transition: all 0.2s ease;
                            background-color: #fff;
                            border-left: 4px solid transparent;
                        }

                        .marc-control-field {
                            border-left-color: #4e73df;
                        }

                        .marc-data-field {
                            border-left-color: #1cc88a;
                        }

                        .marc-field:hover {
                            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                        }

                        .marc-field-tag {
                            background-color: #4e73df;
                            color: white;
                            font-weight: bold;
                            padding: 8px 12px;
                            min-width: 50px;
                            text-align: center;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }

                        .marc-control-field .marc-field-tag {
                            background-color: #4e73df;
                        }

                        .marc-data-field .marc-field-tag {
                            background-color: #1cc88a;
                        }

                        .marc-field-content {
                            flex-grow: 1;
                            padding: 8px 15px;
                        }

                        .marc-field-header {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 5px;
                            border-bottom: 1px solid #eee;
                            padding-bottom: 5px;
                        }

                        .marc-field-name {
                            font-weight: 500;
                            color: #2c3e50;
                        }

                        .marc-indicators {
                            background-color: #f1f5f8;
                            color: #4e73df;
                            padding: 0 8px;
                            border-radius: 4px;
                            font-size: 0.9em;
                            font-weight: bold;
                        }

                        .marc-field-value {
                            font-family: 'Courier New', monospace;
                            padding: 5px 0;
                            color: #2c3e50;
                            word-break: break-all;
                        }

                        .marc-subfields {
                            display: flex;
                            flex-wrap: wrap;
                            gap: 8px;
                        }

                        .marc-subfield {
                            display: flex;
                            align-items: center;
                            background-color: #f8f9fa;
                            border-radius: 4px;
                            padding: 4px 8px;
                            margin-bottom: 5px;
                        }

                        .marc-subfield-code {
                            color: #e74a3b;
                            font-weight: bold;
                            margin-right: 8px;
                            cursor: help;
                        }

                        .marc-subfield-value {
                            color: #2c3e50;
                        }

                        /* Add Roboto Mono from Google Fonts */
                        @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500&display=swap');
                    </style>
                </div>

                <!-- ISBD View Tab -->
                <div class="tab-pane fade" id="isbd" role="tabpanel">
                    <div class="isbd-details p-4">

                    <?php if (isset($book)): ?>
                        <div class="isbd-record">
                            <?php
                            // Title Line
                            echo '<div class="isbd-area">';
                            echo htmlspecialchars($book['title']);
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

                            // Physical description - removed " pages" and " cm"
                            echo ' - ';
                            if (!empty($book['preliminaries'])) {
                                echo htmlspecialchars($book['preliminaries']) . ', ';
                            }
                            echo htmlspecialchars($book['total_pages']) . ' ' . htmlspecialchars($book['supplementary_contents']);
                            if (!empty($book['illustrations'])) {
                                echo ' : illustrations';
                            }
                            echo ' ; ' . htmlspecialchars($book['dimension']);
                            echo '</div>';

                            // ISBN Line
                            echo '<div class="isbd-area">';
                            echo 'ISBN: ' . htmlspecialchars($book['ISBN']);
                            echo '</div>';

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
                        <?php else: ?>
                            <div class="alert alert-danger">Book not found.</div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- End of Main Content -->

    <!-- Footer -->
    <?php include '../Admin/inc/footer.php' ?>
    <!-- End of Footer -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Add this script before closing body tag -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var triggerTabList = [].slice.call(document.querySelectorAll('#bookDetailsTabs button'));
            triggerTabList.forEach(function(triggerEl) {
                var tabTrigger = new bootstrap.Tab(triggerEl);
                triggerEl.addEventListener('click', function(event) {
                    event.preventDefault();
                    tabTrigger.show();
                });
            });
        });
    </script>

    <script>
function confirmDeleteCopy(bookId, accession) {
    Swal.fire({
        title: 'Delete Book Copy?',
        html: `
            Are you sure you want to delete the copy with accession #<strong>${accession}</strong>?<br><br>
            <span class="text-danger">This action cannot be undone!</span>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                html: 'Please wait while the copy is being deleted.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const response = await fetch('delete_copy.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ bookId: bookId })
                });

                const data = await response.json();

                if (data.success) {
                    if (data.redirect) {
                        Swal.fire({
                            title: 'Success!',
                            text: data.message,
                            icon: 'success'
                        }).then(() => {
                            window.location.href = data.redirect;
                        });
                    } else if (data.selectedBookId) {
                        Swal.fire({
                            title: 'Success!',
                            text: data.message,
                            icon: 'success'
                        }).then(() => {
                            window.location.href = `opac.php?book_id=${data.selectedBookId}`;
                        });
                    }
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.error || 'An unexpected error occurred.',
                        icon: 'error'
                    });
                }
            } catch (error) {
                Swal.fire({
                    title: 'Error!',
                    text: 'An unexpected error occurred while deleting the copy.',
                    icon: 'error'
                });
            }
        }
    });
}

// Add new function to handle deleting all copies
function confirmDeleteAllCopies(title) {
    // Get all book IDs from the table
    const copyRows = document.querySelectorAll('.copy-row');
    const bookIds = Array.from(copyRows).map(row => parseInt(row.getAttribute('data-book-id')));

    if (bookIds.length === 0) {
        Swal.fire({
            title: 'No copies found',
            text: 'There are no copies available to delete.',
            icon: 'info'
        });
        return;
    }

    Swal.fire({
        title: 'Delete All Copies?',
        html: `
            Are you sure you want to delete all ${bookIds.length} copies of <strong>"${title}"</strong>?<br><br>
            <span class="text-danger">This action cannot be undone!</span>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete all!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Start the deletion process
            deleteAllCopies(bookIds, title);
        }
    });
}

async function deleteAllCopies(bookIds, title) {
    // Create progress modal
    Swal.fire({
        title: 'Deleting Copies',
        html: `
            <div class="text-start mb-3">
                Deleting all copies of "${title}".<br>
                Progress: <span id="delete-progress">0</span>/${bookIds.length}
            </div>
            <div class="progress">
                <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger"
                     role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div id="deletion-status" class="mt-3"></div>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false
    });

    // Track success and failure counts
    let successCount = 0;
    let failureCount = 0;
    let failureMessages = [];

    // Process each book ID sequentially
    for (let i = 0; i < bookIds.length; i++) {
        const bookId = bookIds[i];

        try {
            const response = await fetch('delete_copy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ bookId: bookId })
            });

            const data = await response.json();

            // Update progress
            document.getElementById('delete-progress').textContent = i + 1;
            document.getElementById('progress-bar').style.width = `${((i + 1) / bookIds.length) * 100}%`;

            if (data.success) {
                successCount++;
                document.getElementById('deletion-status').innerHTML += `
                    <div class="alert alert-success py-1 mb-1">Book ID ${bookId}: Deleted successfully</div>
                `;
            } else {
                failureCount++;
                document.getElementById('deletion-status').innerHTML += `
                    <div class="alert alert-danger py-1 mb-1">Book ID ${bookId}: ${data.error}</div>
                `;
                failureMessages.push(`Book ID ${bookId}: ${data.error}`);
            }
        } catch (error) {
            failureCount++;
            document.getElementById('deletion-status').innerHTML += `
                <div class="alert alert-danger py-1 mb-1">Book ID ${bookId}: Unexpected error</div>
            `;
            failureMessages.push(`Book ID ${bookId}: Unexpected error`);
        }
    }

    // Show final results
    let resultIcon, resultTitle, resultHtml;

    if (failureCount === 0) {
        resultIcon = 'success';
        resultTitle = 'All Copies Deleted';
        resultHtml = `Successfully deleted all ${successCount} copies of "${title}".`;
    } else if (successCount === 0) {
        resultIcon = 'error';
        resultTitle = 'Failed to Delete Copies';
        resultHtml = `Failed to delete any copies of "${title}".<br><br>`;
        resultHtml += failureMessages.join('<br>');
    } else {
        resultIcon = 'warning';
        resultTitle = 'Partial Deletion';
        resultHtml = `Deleted ${successCount} copies, but failed to delete ${failureCount} copies of "${title}".<br><br>`;
        resultHtml += failureMessages.join('<br>');
    }

    Swal.fire({
        title: resultTitle,
        html: resultHtml,
        icon: resultIcon,
        confirmButtonText: 'OK'
    }).then(() => {
        if (successCount > 0) {
            // Redirect to book list if at least one copy was deleted
            window.location.href = 'book_list.php';
        }
    });
}
</script>

<!-- Add script for interactive holdings table -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listeners to all copy rows
    const copyRows = document.querySelectorAll('.copy-row');
    copyRows.forEach(row => {
        row.addEventListener('click', function() {
            const bookId = this.getAttribute('data-book-id');
            if (bookId) {
                loadBookDetails(bookId);
            }
        });
    });

    // Style for clickable rows
    const style = document.createElement('style');
    style.innerHTML = `
        .copy-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .copy-row:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
    `;
    document.head.appendChild(style);

    // Function to load book details via AJAX
    function loadBookDetails(bookId) {
        // Update URL without refreshing the page
        const url = new URL(window.location.href);
        url.searchParams.set('book_id', bookId);
        window.history.pushState({}, '', url);

        // Highlight the selected row
        document.querySelectorAll('.copy-row').forEach(row => {
            row.classList.remove('table-primary');
        });
        document.querySelector(`.copy-row[data-book-id="${bookId}"]`).classList.add('table-primary');

        // Load the new page content
        fetch(`opac.php?book_id=${bookId}&partial=true`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Create a temporary div to hold the response
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;

            // Get currently active tab
            const activeTab = document.querySelector('#bookDetailsTabs .nav-link.active');
            const activeTabId = activeTab ? activeTab.id : 'details-tab';

            // STANDARD VIEW TAB UPDATES - Only update card contents
            if (tempDiv.querySelector('#details')) {
                // Find all cards in the details tab
                const detailsCards = tempDiv.querySelectorAll('#details .card');

                // Update each card individually
                detailsCards.forEach(newCard => {
                    const cardHeading = newCard.querySelector('.card-header h5, .card-header h6');
                    if (cardHeading) {
                        const cardTitle = cardHeading.textContent.trim();
                        // Find matching card in current page
                        const currentCards = document.querySelectorAll('#details .card');

                        currentCards.forEach(currentCard => {
                            const currentHeading = currentCard.querySelector('.card-header h5, .card-header h6');
                            if (currentHeading && currentHeading.textContent.trim() === cardTitle) {
                                // Update only the card body content
                                const newCardBody = newCard.querySelector('.card-body');
                                const currentCardBody = currentCard.querySelector('.card-body');
                                if (newCardBody && currentCardBody) {
                                    currentCardBody.innerHTML = newCardBody.innerHTML;
                                }
                            }
                        });
                    }
                });
            }

            // MARC VIEW TAB UPDATES
            if (tempDiv.querySelector('#marc')) {
                const newMarcContent = tempDiv.querySelector('#marc .marc-record-container');
                const currentMarcContent = document.querySelector('#marc .marc-record-container');
                if (newMarcContent && currentMarcContent) {
                    currentMarcContent.innerHTML = newMarcContent.innerHTML;
                }
            }

            // ISBD VIEW TAB UPDATES
            if (tempDiv.querySelector('#isbd')) {
                const newIsbdContent = tempDiv.querySelector('#isbd .isbd-record');
                const currentIsbdContent = document.querySelector('#isbd .isbd-record');
                if (newIsbdContent && currentIsbdContent) {
                    currentIsbdContent.innerHTML = newIsbdContent.innerHTML;
                }
            }

            // Ensure the active tab remains active
            document.querySelector(`#${activeTabId}`).click();

            // Rebind event listeners on the new content
            rebindEventListeners();

            // Scroll to top of the page after loading content
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        })
        .catch(error => {
            console.error('Error loading book details:', error);
            // Show error message
            Swal.fire({
                title: 'Error',
                text: 'Failed to load book details. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        });
    }

    // Function to rebind event listeners after content update
    function rebindEventListeners() {
        // Rebind click events to copy rows
        document.querySelectorAll('.copy-row').forEach(row => {
            row.addEventListener('click', function() {
                const bookId = this.getAttribute('data-book-id');
                if (bookId) {
                    loadBookDetails(bookId);
                }
            });
        });

        // Rebind confirm delete function
        const deleteBtn = document.querySelector('[onclick^="confirmDeleteCopy"]');
        if (deleteBtn) {
            const originalOnclick = deleteBtn.getAttribute('onclick');
            deleteBtn.setAttribute('onclick', originalOnclick);
        }
    }
});
</script>

<!-- Instructions Modal -->
<div class="modal fade" id="instructionsModal" tabindex="-1" role="dialog" aria-labelledby="instructionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="instructionsModalLabel">
                    <i class="fas fa-info-circle mr-2"></i>How to Use the OPAC System
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">What is OPAC?</h6>
                    </div>
                    <div class="card-body">
                        <p>OPAC (Online Public Access Catalog) allows you to view detailed information about books in the library collection, including:</p>
                        <ul>
                            <li>Bibliographic details (title, author, publication information)</li>
                            <li>Physical characteristics and content information</li>
                            <li>Availability status of each copy</li>
                            <li>Location information for finding the book in the library</li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">Viewing Book Details</h6>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>Book information is presented in a structured format following library standards.</li>
                            <li>You can see all copies of a book and their current status.</li>
                            <li>Click on any copy in the holdings table to view its specific details.</li>
                            <li>The availability count shows how many copies are currently on the shelf.</li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">Export Options</h6>
                    </div>
                    <div class="card-body">
                        <p>You can export book records in different formats:</p>
                        <ul>
                            <li><strong>Standard Format</strong>: Simple text export with basic bibliographic information</li>
                            <li><strong>MARC21</strong>: Machine-Readable Cataloging format for library system interoperability</li>
                            <li><strong>ISBD</strong>: International Standard Bibliographic Description format</li>
                        </ul>
                        <p>Use the export buttons to download the record in your preferred format.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>