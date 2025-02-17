<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../db.php'; // Database connection
include 'lcc_generator.php'; // Add this line

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
            <ul class="nav nav-tabs mb-3" id="bookDetailsTabs" role="tablist">
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

            <div class="tab-content" id="bookDetailsContent">
                <!-- Standard View Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <div class="book-details">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php else: ?>
                            <h2><?php echo htmlspecialchars($book['title']); ?></h2>
                            
                            <div class="book-images">
                                <?php if (!empty($book['front_image'])): ?>
                                    <img src="../inc/book-image/<?php echo htmlspecialchars($book['front_image']); ?>" alt="Front Image">
                                <?php endif; ?>
                                <?php if (!empty($book['back_image'])): ?>
                                    <img src="../inc/book-image/<?php echo htmlspecialchars($book['back_image']); ?>" alt="Back Image">
                                <?php endif; ?>
                            </div>

                            <div class="book-info-grid">
                                <div class="info-item">
                                    <span class="label">Accession:</span> <?php echo htmlspecialchars($book['accession']); ?>
                                </div>
                                <div class="info-item">
                                    <span class="label">Preferred Title:</span> <?php echo htmlspecialchars($book['preferred_title']); ?>
                                </div>
                                <div class="info-item">
                                    <span class="label">Parallel Title:</span> <?php echo htmlspecialchars($book['parallel_title']); ?>
                                </div>
                                <div class="info-item">
                                    <span class="label">Call Number:</span> <?php echo htmlspecialchars($book['call_number']); ?>
                                </div>
                                <div class="info-item">
                                    <span class="label">Generated Call Number:</span> <?php echo htmlspecialchars($generatedCallNumber); ?>
                                </div>
                                <div class="info-item">
                                    <span class="label">Copy Number:</span> <?php echo htmlspecialchars($book['copy_number']); ?>
                                </div>
                                
                                <div class="info-item">
                                    <span class="label">Series:</span> <?php echo htmlspecialchars($book['series']); ?>
                                </div>
                                <div class="info-item">
                                    <span class="label">Volume:</span> <?php echo htmlspecialchars($book['volume']); ?>
                                </div>
                                
                                <div class="info-item">
                                    <span class="label">Language:</span> <?php echo htmlspecialchars($book['language']); ?>
                                </div>
                                <div class="info-item">
                                    <span class="label">Status:</span> <?php echo htmlspecialchars($book['status']); ?>
                                </div>

                                <div class="info-item book-info-full">
                                    <span class="label">Physical Details:</span>
                                    Height: <?php echo htmlspecialchars($book['height']); ?>cm, 
                                    Width: <?php echo htmlspecialchars($book['width']); ?>cm, 
                                    Pages: <?php echo htmlspecialchars($book['total_pages']); ?>
                                </div>
                            </div>

                            <!-- Add Subject Information -->
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
                        <?php endif; ?>
                    </div>
                </div>

                <!-- MARC 21 View Tab -->
                <div class="tab-pane fade" id="marc" role="tabpanel">
                    <div class="marc-details p-4">
                        <style>
                            .marc-record {
                                width: 100%;
                                background-color: #ffffff;
                                padding: 30px;
                                box-shadow: 0 0 15px rgba(0,0,0,0.1);
                            }
                            .marc-field { 
                                margin-bottom: 12px; 
                                line-height: 1.6;
                                background: #f8f9fa;
                                padding: 12px 15px;
                                border-radius: 5px;
                                border-left: 3px solid #2c3e50;
                            }
                            .marc-tag { 
                                display: inline-block;
                                width: 45px;
                                color: #004085;
                                font-weight: bold;
                                margin-right: 10px;
                            }
                            .marc-indicators {
                                display: inline-block;
                                width: 30px;
                                color: #666;
                                margin-right: 10px;
                                font-family: monospace;
                            }
                            .marc-content {
                                display: inline-block;
                                font-family: monospace;
                                color: #2c3e50;
                            }
                            .marc-delimiter {
                                color: #dc3545;
                                font-weight: bold;
                                margin: 0 2px;
                            }
                            .marc-subfield-code {
                                color: #28a745;
                                margin-right: 3px;
                            }
                            .marc-field-tooltip {
                                cursor: help;
                                border-bottom: 1px dotted #666;
                            }
                            .marc-section {
                                margin-bottom: 20px;
                                padding: 15px;
                                background: #fff;
                                border-radius: 5px;
                            }
                            .marc-section-title {
                                color: #34495e;
                                font-size: 1.2rem;
                                margin-bottom: 15px;
                                padding-bottom: 8px;
                                border-bottom: 2px solid #eee;
                            }
                        </style>

                        <?php if (!isset($error)): ?>
                        <div class="marc-record">
                            <?php
                            // Helper function to format MARC values
                            function formatMarcValue($value) {
                                return !empty($value) ? htmlspecialchars(trim($value)) : '';
                            }

                            // Get primary author from contributors
                            $primaryAuthor = '';
                            if (!empty($contributors)) {
                                foreach ($contributors as $contributor) {
                                    if ($contributor['role'] === 'Author') {
                                        $primaryAuthor = $contributor['lastname'] . ', ' . 
                                                       $contributor['firstname'] . ' ' . 
                                                       $contributor['middle_init'];
                                        break;
                                    }
                                }
                            }

                            // Get publication info
                            $publication = !empty($publications) ? reset($publications) : null;

                            // Get publication year from publications table
                            $publicationYear = '';
                            if (!empty($publications)) {
                                $publicationYear = $publications[0]['publish_date'];
                            }

                            // MARC Fields Array
                            $marcFields = [
                                // Leader
                                // ['LDR', '', '', str_pad('00000nam a22000000 4500', 24)],
                                
                                // Control Fields
                                ['001', '', '', formatMarcValue($book['accession'])],
                                ['005', '', '', date('YmdHis', strtotime($book['last_update']))],
                                ['008', '', '', date('ymd', strtotime($book['date_added'])) . 's' . 
                                           str_pad($publicationYear, 4, ' ') . 
                                           'pau' .          // Geographic area code (Pennsylvania, USA as example)
                                           'n' .           // Modified record
                                           'bn' .          // Type of material - No special format
                                           ' ' .           // Blank
                                           'a' .           // Form of item (regular print)
                                           ' ' .           // Nature of contents
                                           '000' .         // Government publication
                                           '0' .           // Conference publication
                                           strtolower(substr($book['language'], 0, 3)) . 
                                           'd'],          // Modified record

                                // Variable Fields
                                ['020', '##', 'a', $book['ISBN']],
                                // ['040', '##', 'a', 'LOCAL'], // Local cataloging source
                                ['082', '04', 'a', $book['call_number']],
                                ['100', '1#', 'a', $primaryAuthor],
                                ['245', '10', 'a', $book['title'],
                                           'b', $book['parallel_title'],
                                           'c', $primaryAuthor],
                                ['246', '1#', 'a', $book['preferred_title']],
                                ['250', '##', 'a', $book['edition']],
                                ['260', '##', 'a', $publication['place'] ?? '',
                                           'b', $publication['publisher'] ?? '',
                                           'c', $publication['publish_date'] ?? ''],
                                ['300', '##', 'a', $book['total_pages'] . ' pages',
                                           'b', 'illustrations',
                                           'c', $book['height'] . ' x ' . $book['width'] . ' cm'],
                                ['336', '##', 'a', $book['content_type'],
                                           '2', 'rdacontent'],
                                ['337', '##', 'a', $book['media_type'],
                                           '2', 'rdamedia'],
                                ['338', '##', 'a', $book['carrier_type'],
                                           '2', 'rdacarrier'],
                                ['490', '1#', 'a', $book['series'],
                                           'v', $book['volume']],
                                ['500', '##', 'a', 'Copy ' . $book['copy_number']],
                                ['505', '0#', 'a', $book['contents']],
                                ['520', '##', 'a', $book['summary']],
                                ['650', '##', 'a', $book['subject_category'],
                                           'x', $book['subject_specification'],
                                           'y', $book['subject_detail']],
                                ['852', '##', 'a', $book['shelf_location'],
                                           'p', $book['accession']],
                                ['856', '40', 'u', $book['URL'],
                                           'z', 'Online access'],
                                ['902', '##', 'a', 'Status: ' . $book['status'],
                                           'b', 'Entered by: ' . $book['entered_by'],
                                           'c', 'Date added: ' . $book['date_added']],
                                // Add additional author entries for other contributors
                                ['700', '1#', 'a', $primaryAuthor,
                                           'e', 'author'],
                            ];

                            // Define MARC field descriptions
                            $marcFieldDesc = [
                                'LDR' => 'Record Leader',
                                '001' => 'Control Number',
                                '005' => 'Date and Time of Latest Transaction',
                                '008' => 'Fixed-Length Data Elements',
                                '020' => 'International Standard Book Number',
                                '040' => 'Cataloging Source',
                                '082' => 'Dewey Decimal Classification Number',
                                '100' => 'Main Entry - Personal Name',
                                '245' => 'Title Statement',
                                '246' => 'Varying Form of Title',
                                '250' => 'Edition Statement',
                                '260' => 'Publication Information',
                                '300' => 'Physical Description',
                                '336' => 'Content Type',
                                '337' => 'Media Type',
                                '338' => 'Carrier Type',
                                '490' => 'Series Statement',
                                '500' => 'General Note',
                                '505' => 'Formatted Contents Note',
                                '520' => 'Summary, etc.',
                                '650' => 'Subject Added Entry - Topical Term',
                                '852' => 'Location',
                                '856' => 'Electronic Location and Access',
                                '902' => 'Local Information'
                            ];

                            // Display MARC Record
                            foreach ($marcFields as $field) {
                                if (!empty($field[3]) || $field[0] === 'LDR') {
                                    echo '<div class="marc-field">';
                                    
                                    // Display tag with tooltip
                                    echo '<span class="marc-tag marc-field-tooltip" title="' . 
                                         ($marcFieldDesc[$field[0]] ?? '') . '">' . 
                                         $field[0] . '</span>';

                                    if ($field[0] === 'LDR' || $field[0] < '010') {
                                        // Control field
                                        echo '<span class="marc-content">' . $field[3] . '</span>';
                                    } else {
                                        // Data field
                                        echo '<span class="marc-indicators">' . $field[1] . '</span>';
                                        echo '<span class="marc-content">';
                                        
                                        // Display subfields
                                        for ($i = 2; $i < count($field); $i += 2) {
                                            if (!empty($field[$i + 1])) {
                                                echo '<span class="marc-delimiter">‡</span>';
                                                echo '<span class="marc-subfield-code">' . $field[$i] . '</span>';
                                                echo formatMarcValue($field[$i + 1]) . ' ';
                                            }
                                        }
                                        
                                        echo '</span>';
                                    }
                                    
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ISBD View Tab -->
                <div class="tab-pane fade" id="isbd" role="tabpanel">
                    <div class="isbd-details p-4">
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
                        </style>

                        <?php if (!isset($error)): ?>
                        <div class="isbd-record">
                            <?php
                            // Title and Statement of Responsibility Area
                            echo '<div class="isbd-area">';
                            echo htmlspecialchars($book['title']);
                            
                            if (!empty($book['parallel_title'])) {
                                echo ' <span class="isbd-punctuation">=</span> ' . htmlspecialchars($book['parallel_title']);
                            }
                            
                            if (!empty($book['preferred_title'])) {
                                echo ' <span class="isbd-punctuation">[</span>' . htmlspecialchars($book['preferred_title']) . '<span class="isbd-punctuation">]</span>';
                            }
                            
                            echo '</div>';

                            // Publication, Distribution, etc. Area
                            if ($publication) {
                                echo '<div class="isbd-area">';
                                $pubInfo = [];
                                
                                if (!empty($publication['place'])) {
                                    $pubInfo[] = htmlspecialchars($publication['place']);
                                }
                                
                                if (!empty($publication['publisher'])) {
                                    $pubInfo[] = htmlspecialchars($publication['publisher']);
                                }
                                
                                if (!empty($publication['publish_date'])) {
                                    $pubInfo[] = htmlspecialchars($publication['publish_date']);
                                }
                                
                                echo implode(' <span class="isbd-punctuation">:</span> ', $pubInfo);
                                echo '<span class="isbd-punctuation">.</span>';
                                echo '</div>';
                            }

                            // Date Added
                            if (!empty($book['date_added'])) {
                                echo '<div class="isbd-date">';
                                echo '- ' . date('F j, Y', strtotime($book['date_added']));
                                echo '</div>';
                            }

                            // Summary/Abstract
                            if (!empty($book['summary'])) {
                                echo '<div class="isbd-area">';
                                echo '<br>';
                                echo htmlspecialchars($book['summary']);
                                echo '</div>';
                            }

                            // Subject Access
                            echo '<div class="isbd-subjects">';
                            echo 'Subjects<span class="isbd-punctuation">--</span>Index Terms:';
                            
                            $subjects = [];
                            if (!empty($book['subject_category'])) {
                                $subjects[] = htmlspecialchars($book['subject_category']);
                            }
                            if (!empty($book['subject_specification'])) {
                                $subjects[] = htmlspecialchars($book['subject_specification']);
                            }
                            if (!empty($book['subject_detail'])) {
                                $subjects[] = htmlspecialchars($book['subject_detail']);
                            }
                            
                            echo implode('. ', $subjects);
                            echo '</div>';

                            // Accession Number
                            echo '<div class="isbd-accession">';
                            echo 'Accession no.:<span class="isbd-punctuation">:</span>' . htmlspecialchars($book['accession']);
                            echo '</div>';
                            ?>
                        </div>
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
</body>
</html>