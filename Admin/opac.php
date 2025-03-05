<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../db.php'; // Database connection
include 'lcc_generator.php'; // Add this line

// Handle Add Copies via modal submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['num_copies']) && isset($_POST['modal_action']) && $_POST['modal_action'] === 'add_copies') {
    $title = $_POST['title'];
    // Fetch first copy to duplicate details
    $stmt = $conn->prepare("SELECT * FROM books WHERE title = ? ORDER BY copy_number ASC LIMIT 1");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        die("Book not found.");
    }
    $firstCopy = $result->fetch_assoc();
    // Get current max copy number and accession for this title
    $stmt = $conn->prepare("SELECT MAX(copy_number) as max_copy, MAX(accession) as max_accession FROM books WHERE title = ?");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $result = $stmt->get_result();
    $maxInfo = $result->fetch_assoc();
    $currentCopy = $maxInfo['max_copy'];
    $currentAccession = $maxInfo['max_accession'];
    
    $numCopiesToAdd = intval($_POST['num_copies']);
    $successCount = 0;
    for ($i = 1; $i <= $numCopiesToAdd; $i++) {
        $newCopyNumber = $currentCopy + $i;
        $newAccession = $currentAccession + $i;
        $query = "INSERT INTO books (
            accession, title, preferred_title, parallel_title, subject_category,
            subject_detail, summary, contents, front_image, back_image,
            dimension, series, volume, edition, copy_number, total_pages,
            supplementary_contents, ISBN, content_type, media_type, carrier_type,
            call_number, URL, language, shelf_location, entered_by, date_added,
            status, last_update
        ) SELECT 
            ?, title, preferred_title, parallel_title, subject_category,
            subject_detail, summary, contents, front_image, back_image,
            dimension, series, volume, edition, ?,
            total_pages, supplementary_contents, ISBN, content_type, media_type, carrier_type,
            call_number, URL, language, shelf_location, entered_by, date_added,
            status, last_update
         FROM books WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $newAccession, $newCopyNumber, $firstCopy['id']);
        if ($stmt->execute()) {
            $newBookId = $conn->insert_id;
            $successCount++;
            // Duplicate publication
            $pubQuery = "INSERT INTO publications (book_id, publisher_id, publish_date)
                         SELECT ?, publisher_id, publish_date FROM publications WHERE book_id = ?";
            $pubStmt = $conn->prepare($pubQuery);
            $pubStmt->bind_param("ii", $newBookId, $firstCopy['id']);
            $pubStmt->execute();
            // Duplicate contributors
            $contribQuery = "INSERT INTO contributors (book_id, writer_id, role)
                             SELECT ?, writer_id, role FROM contributors WHERE book_id = ?";
            $contribStmt = $conn->prepare($contribQuery);
            $contribStmt->bind_param("ii", $newBookId, $firstCopy['id']);
            $contribStmt->execute();
        }
    }
    $_SESSION['success_message'] = "Successfully added {$successCount} new copies!";
    // Optionally refresh page or redirect back to opac.php (keeping the same book details)
    header("Location: opac.php?book_id=" . $firstCopy['id']);
    exit();
}

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

        // Only generate call number for display
        $generatedCallNumber = generateCallNumber($book);
        
        // Remove the auto-update logic, just keep the display
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

    // Add this debug code after fetching contributors
    if (!empty($contributors)) {
        echo "<!-- Debug: Primary Author: ";
        foreach ($contributors as $contributor) {
            if ($contributor['role'] === 'Author') {
                echo htmlspecialchars($contributor['lastname']);
                break;
            }
        }
        echo " -->";
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
    </style>
    <!-- Add Bootstrap CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php include '../admin/inc/header.php'; ?>

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
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="holdings-tab" data-bs-toggle="tab" data-bs-target="#holdings" type="button" role="tab" aria-controls="holdings" aria-selected="false">
                            Holdings
                        </button>
                    </li>
                </ul>
                <?php if (isset($book)): ?>
                <div class="d-flex gap-2">
                    <a href="update_books.php?title=<?php echo urlencode($book['title']); ?>&id_range=<?php echo $book['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Update Books
                    </a>
                    <a href="javascript:void(0);" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCopiesModal">
                        <i class="fas fa-plus"></i> Add Copies
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <div class="tab-content" id="bookDetailsContent">
                <!-- Standard View Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <div class="book-details">
                        <?php if (isset($book)): ?>
                            <!-- Title and Contributors -->
                            <h4><?php echo htmlspecialchars($book['title']); ?></h4>

                            <!-- Author Information -->
                            <p>
                                <strong>By:</strong>
                                <?php 
                                if (!empty($contributors)) {
                                    foreach ($contributors as $contributor) {
                                        if ($contributor['role'] === 'Author') {
                                            echo htmlspecialchars($contributor['lastname'] . ', ' . $contributor['firstname'] . ' ' . $contributor['middle_init']);
                                            break;
                                        }
                                    }
                                }
                                ?>
                            </p>

                            <!-- Book Images -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <?php if (!empty($book['front_image'])): ?>
                                        <img src="../inc/book-image/<?php echo htmlspecialchars($book['front_image']); ?>" alt="Front Image" class="img-fluid">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <?php if (!empty($book['back_image'])): ?>
                                        <img src="../inc/book-image/<?php echo htmlspecialchars($book['back_image']); ?>" alt="Back Image" class="img-fluid">
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Book Details -->
                            <div class="mb-4">
                                <p><strong>Accession:</strong> <?php echo htmlspecialchars($book['accession']); ?></p>
                                <p><strong>Call Number:</strong> <?php echo htmlspecialchars($book['call_number']); ?></p>
                                <p><strong>Generated Call Number:</strong> <?php echo htmlspecialchars($generatedCallNumber); ?></p>
                                <p><strong>Copy Number:</strong> <?php echo htmlspecialchars($book['copy_number']); ?></p>
                                <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['ISBN']); ?></p>
                                <p><strong>Series:</strong> <?php echo htmlspecialchars($book['series']); ?></p>
                                <p><strong>Volume:</strong> <?php echo htmlspecialchars($book['volume']); ?></p>
                                <p><strong>Edition:</strong> <?php echo htmlspecialchars($book['edition']); ?></p>
                                <p><strong>Language:</strong> <?php echo htmlspecialchars($book['language']); ?></p>
                                <p><strong>Physical Description:</strong> <?php echo htmlspecialchars($book['total_pages']); ?> pages <?php echo htmlspecialchars($book['supplementary_contents']); ?>, <?php echo htmlspecialchars($book['dimension']); ?> cm</p>
                                <p><strong>Availability:</strong> <?php echo htmlspecialchars($inShelf); ?> of <?php echo htmlspecialchars($totalCopies); ?> copies available</p>
                            </div>
                            
                            <!-- Subject Information -->
                            <h3>Subject Information</h3>
                            <?php if (!empty($book['subject_category']) || !empty($book['subject_specification']) || !empty($book['subject_detail'])): ?>
                                <div class="subject-info">
                                    <?php if (!empty($book['subject_category'])): ?>
                                        <div class="row">
                                            <p><span class="label">Subject Categories:</span></p>
                                            <ul>
                                                <?php foreach (explode(';', $book['subject_category']) as $category): ?>
                                                    <li><?php echo htmlspecialchars(trim($category)); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($book['subject_specification'])): ?>
                                        <div class="row">
                                            <p><span class="label">Specific Subjects:</span></p>
                                            <ul>
                                                <?php foreach (explode(';', $book['subject_specification']) as $spec): ?>
                                                    <li><?php echo htmlspecialchars(trim($spec)); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($book['subject_detail'])): ?>
                                        <div class="row">
                                            <p><span class="label">Subject Details:</span></p>
                                            <div class="subject-details">
                                                <?php foreach (explode(';', $book['subject_detail']) as $detail): ?>
                                                    <p><?php echo htmlspecialchars(trim($detail)); ?></p>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p>No subject information available.</p>
                            <?php endif; ?>

                            <!-- Add Summary Section -->
                            <h3>Summary</h3>
                            <?php if (!empty($book['summary'])): ?>
                                <div class="summary">
                                    <p><?php echo nl2br(htmlspecialchars($book['summary'])); ?></p>
                                </div>
                            <?php else: ?>
                                <p>No summary available.</p>
                            <?php endif; ?>

                            <!-- Add Contents Section -->
                            <h3>Contents</h3>
                            <?php if (!empty($book['contents'])): ?>
                                <div class="contents">
                                    <p><?php echo nl2br(htmlspecialchars($book['contents'])); ?></p>
                                </div>
                            <?php else: ?>
                                <p>No contents information available.</p>
                            <?php endif; ?>

                            <!-- Display Contributors -->
                            <h3>Contributors</h3>
                            <?php if (!empty($contributors)): ?>
                                <ul>
                                    <?php foreach ($contributors as $contributor): ?>
                                        <li><?php echo htmlspecialchars($contributor['firstname'] . ' ' . $contributor['middle_init'] . ' ' . $contributor['lastname'] . ' (' . $contributor['role'] . ')'); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No contributors found.</p>
                            <?php endif; ?>

                            <!-- Display Publications -->
                            <h3>Publications</h3>
                            <?php if (!empty($publications)): ?>
                                <ul>
                                    <?php foreach ($publications as $publication): ?>
                                        <li><?php echo htmlspecialchars($publication['publisher'] . ' (' . $publication['place'] . ') - ' . $publication['publish_date'] . ''); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No publications found.</p>
                            <?php endif; ?>

                            <!-- Holdings Information Section -->
                            <h3 class="mt-4">Holdings Information</h3>
                            <?php if (!empty($allCopies)): ?>
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                        <h6 class="m-0 font-weight-bold text-primary">Available Copies</h6>
                                        <div class="d-flex align-items-center">
                                            <div class="small text-muted me-3">
                                                Total Copies: <?php echo htmlspecialchars($totalCopies); ?> | 
                                                Available: <?php echo htmlspecialchars($inShelf); ?>
                                            </div>
                                            <a href="export_barcodes.php?title=<?php echo urlencode($book['title']); ?>" 
                                               class="btn btn-sm btn-primary ms-2" 
                                               target="_blank">
                                                <i class="fas fa-download"></i> Export Barcodes
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>Accession</th>
                                                        <th>Call Number</th>
                                                        <th>Copy Number</th>
                                                        <th>Status</th>
                                                        <th>Location</th>
                                                        <th>Last Update</th>
                                                        <th>Series</th> <!-- New column -->
                                                        <th>Volume</th> <!-- New column -->
                                                        <th>Edition</th> <!-- New column -->
                                                        <th>ISBN</th> <!-- New column -->
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($allCopies as $copy): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($copy['accession']); ?></td>
                                                            <td><?php echo htmlspecialchars($copy['call_number']); ?></td>
                                                            <td><?php echo htmlspecialchars($copy['copy_number']); ?></td>
                                                            <td>
                                                                <?php echo htmlspecialchars($copy['status']); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($copy['shelf_location']); ?></td>
                                                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($copy['last_update']))); ?></td>
                                                            <td><?php echo htmlspecialchars($copy['series']); ?></td> <!-- New column -->
                                                            <td><?php echo htmlspecialchars($copy['volume']); ?></td> <!-- New column -->
                                                            <td><?php echo htmlspecialchars($copy['edition']); ?></td> <!-- New column -->
                                                            <td><?php echo htmlspecialchars($copy['ISBN']); ?></td> <!-- New column -->
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No copies found.</div>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="alert alert-danger">Book not found.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- MARC View Tab -->
                <div class="tab-pane fade" id="marc" role="tabpanel">
                    
                    <!-- Only show Labeled Display -->
                    <div id="marc-labeled-view" class="marc-record">
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

                        // Helper function to format MARC values
                        function formatMarcValue($value) {
                            return trim(preg_replace('/\s+/', ' ', $value));
                        }

                        // Format date for MARC21
                        function formatMarc21Date($date) {
                            return date('YmdHis', strtotime($date));
                        }

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
                                'a', $book['total_pages'] . ' pages' . (isset($book['supplementary_contents']) ? ' ' . $book['supplementary_contents'] : ''),
                                'b', 'illustrations',
                                'c', $book['dimension'] . ' cm'
                            ];

                            // Content/Media Type
                            $marcFields[] = ['336', '##', 'a', $book['content_type'], '2', 'rdacontent'];
                            $marcFields[] = ['337', '##', 'a', $book['media_type'], '2', 'rdamedia'];
                            $marcFields[] = ['338', '##', 'a', $book['carrier_type'], '2', 'rdacarrier'];

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

                            // Added Entries for Co-Authors and Editors
                            foreach ($coauthors as $coauthor) {
                                $marcFields[] = ['700', '1#', 'a', $coauthor, 'e', 'co-author'];
                            }
                            foreach ($editors as $editor) {
                                $marcFields[] = ['700', '1#', 'a', $editor, 'e', 'editor'];
                            }

                            // Holdings Information
                            $marcFields[] = ['852', '##', 
                                'a', $book['shelf_location'],
                                'p', $book['accession']
                            ];
                        }
                        ?>

                        <!-- Labeled Display -->
                        <div id="marc-labeled-view" class="marc-record">
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

                            foreach ($marcFields as $field): ?>
                                <div class="marc-field">
                                    <div class="field-header">
                                        <span class="field-tag"><?= $field[0] ?></span>
                                        <span class="field-name"><?= $fieldDescriptions[$field[0]] ?? 'Field ' . $field[0] ?></span>
                                        <?php if ($field[0] >= '010'): ?>
                                            <span class="indicators" title="Field Indicators"><?= $field[1] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="field-content">
                                        <?php
                                        if ($field[0] === 'LDR' || $field[0] < '010') {
                                            echo '<span class="field-value">' . htmlspecialchars($field[3]) . '</span>';
                                        } else {
                                            for ($i = 2; $i < count($field); $i += 2) {
                                                if (!empty($field[$i + 1])) {
                                                    echo '<div class="subfield">';
                                                    echo '<span class="subfield-delimiter">‡</span>';
                                                    echo '<span class="subfield-code" title="' . 
                                                        ($subfieldDescriptions[$field[$i]] ?? 'Subfield ' . $field[$i]) . 
                                                        '">' . $field[$i] . '</span>';
                                                    echo '<span class="subfield-value">' . 
                                                        htmlspecialchars(formatMarcValue($field[$i + 1])) . '</span>';
                                                    echo '</div>';
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <style>
                            .marc-record {
                                font-family: monospace;
                                background: #f8f9fc;
                                padding: 20px;
                                border-radius: 5px;
                                margin-top: 15px;
                            }
                            .marc-field {
                                background: #fff;
                                padding: 10px;
                                border-radius: 4px;
                                margin-bottom: 8px;
                                border: 1px solid #e3e6f0;
                            }
                            .field-header {
                                display: flex;
                                align-items: center;
                                margin-bottom: 5px;
                            }
                            .field-tag {
                                color: #4e73df;
                                font-weight: bold;
                                margin-right: 10px;
                                min-width: 40px;
                            }
                            .field-name {
                                color: #2c3e50;
                                font-weight: 500;
                                margin-right: 10px;
                            }
                            .indicators {
                                color: #1cc88a;
                                margin-right: 10px;
                            }
                            .field-content {
                                margin-left: 20px;
                            }
                            .subfield {
                                margin: 3px 0;
                                display: flex;
                                align-items: center;
                                gap: 5px;
                            }
                            .subfield-delimiter {
                                color: #e74a3b;
                                font-weight: bold;
                            }
                            .subfield-code {
                                color: #36b9cc;
                                cursor: help;
                            }
                            .subfield-value {
                                color: #2c3e50;
                            }
                            .marc-text {
                                white-space: pre-wrap;
                                font-size: 14px;
                                line-height: 1.5;
                            }
                        </style>
                    </div>
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

            // Physical description
            echo ' - ';
            if (!empty($book['preliminaries'])) {
                echo htmlspecialchars($book['preliminaries']) . ', ';
            }
            echo htmlspecialchars($book['total_pages']) . ' pages' . htmlspecialchars($book['supplementary_contents']);
            if (!empty($book['illustrations'])) {
                echo ' : illustrations';
            }
            echo ' ; ' . htmlspecialchars($book['dimension']) . ' cm';
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

                <!-- Holdings Tab -->
                <div class="tab-pane fade" id="holdings" role="tabpanel">
                    <div class="holdings-details p-4">
                        <?php if (!empty($allCopies)): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Available Copies</h6>
                                    <div class="d-flex align-items-center">
                                        <div class="small text-muted me-3">
                                            Total Copies: <?php echo htmlspecialchars($totalCopies); ?> | 
                                            Available: <?php echo htmlspecialchars($inShelf); ?>
                                        </div>
                                        <a href="export_barcodes.php?title=<?php echo urlencode($book['title']); ?>" 
                                           class="btn btn-sm btn-primary ms-2" 
                                           target="_blank">
                                            <i class="fas fa-download"></i> Export Barcodes
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Accession</th>
                                                    <th>Call Number</th>
                                                    <th>Copy Number</th>
                                                    <th>Status</th>
                                                    <th>Location</th>
                                                    <th>Last Update</th>
                                                    <th>Series</th> <!-- New column -->
                                                    <th>Volume</th> <!-- New column -->
                                                    <th>Edition</th> <!-- New column -->
                                                    <th>ISBN</th> <!-- New column -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($allCopies as $copy): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($copy['accession']); ?></td>
                                                        <td><?php echo htmlspecialchars($copy['call_number']); ?></td>
                                                        <td><?php echo htmlspecialchars($copy['copy_number']); ?></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($copy['status']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($copy['shelf_location']); ?></td>
                                                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($copy['last_update']))); ?></td>
                                                        <td><?php echo htmlspecialchars($copy['series']); ?></td> <!-- New column -->
                                                        <td><?php echo htmlspecialchars($copy['volume']); ?></td> <!-- New column -->
                                                        <td><?php echo htmlspecialchars($copy['edition']); ?></td> <!-- New column -->
                                                        <td><?php echo htmlspecialchars($copy['ISBN']); ?></td> <!-- New column -->
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No copies found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Main Content -->

    <!-- Add the modal for 'Add Copies' -->
    <div class="modal fade" id="addCopiesModal" tabindex="-1" aria-labelledby="addCopiesModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form method="POST" action="opac.php?book_id=<?php echo urlencode($bookId); ?>">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="addCopiesModalLabel">Enter Number of Copies</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="modal_action" value="add_copies">
                <input type="hidden" name="title" value="<?php echo htmlspecialchars($book['title']); ?>">
                <div class="mb-3">
                    <label for="num_copies" class="form-label">How many copies to add?</label>
                    <input type="number" class="form-control" id="num_copies" name="num_copies" min="1" required>
                </div>
                <p class="small text-muted">New copies will duplicate the first copy’s information, including contributors and publication details.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Add Copies</button>
            </div>
          </div>
        </form>
      </div>
    </div>

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
</body>
</html>