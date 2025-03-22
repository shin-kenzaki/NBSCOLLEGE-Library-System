<?php
session_start();
include '../db.php';

// Get the book title from the query parameters
$bookTitle = isset($_GET['title']) ? $_GET['title'] : '';

if (!empty($bookTitle)) {
    // Fetch book details
    $query = "SELECT * FROM books WHERE title = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $bookTitle);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();

    // Fetch total copies and in-shelf count
    $copiesQuery = "SELECT COUNT(*) as total_copies, SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as in_shelf FROM books WHERE title = ?";
    $stmt = $conn->prepare($copiesQuery);
    $stmt->bind_param("s", $bookTitle);
    $stmt->execute();
    $copiesResult = $stmt->get_result();
    $copies = $copiesResult->fetch_assoc();
    $totalCopies = $copies['total_copies'];
    $inShelf = $copies['in_shelf'];

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

    // Fetch all copies of the book
    $allCopiesQuery = "SELECT * FROM books WHERE title = ?";
    $stmt = $conn->prepare($allCopiesQuery);
    $stmt->bind_param("s", $bookTitle);
    $stmt->execute();
    $allCopiesResult = $stmt->get_result();
    $allCopies = [];
    while ($row = $allCopiesResult->fetch_assoc()) {
        $allCopies[] = $row;
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
            <!-- Tab navigation -->
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
                <?php if (isset($book)): ?>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary add-to-cart" data-title="<?php echo htmlspecialchars($book['title']); ?>">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                    <button class="btn btn-success borrow-book" data-title="<?php echo htmlspecialchars($book['title']); ?>">
                        <i class="fas fa-book"></i> Borrow Book
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div class="tab-content" id="bookDetailsContent">
                <!-- Standard View Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <div class="book-details">
                        <?php if (isset($book)): ?>
                            <!-- Title and Contributors Line -->
                            <?php
                            $contributorLine = '';
                            $authorsList = [];
                            $coAuthorsList = [];
                            $editorsList = [];

                            foreach ($contributors as $contributor) {
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
                            <h4>
                                <?php
                                echo htmlspecialchars($book['title']) . ' / ';
                                $contributorNames = [];
                                foreach ($contributors as $contributor) {
                                    $contributorNames[] = htmlspecialchars($contributor['firstname'] . ' ' . $contributor['middle_init'] . ' ' . $contributor['lastname']);
                                }
                                if (count($contributorNames) > 1) {
                                    $lastContributor = array_pop($contributorNames);
                                    echo implode(', ', $contributorNames) . ' and ' . $lastContributor;
                                } else {
                                    echo implode('', $contributorNames);
                                }
                                ?>
                            </h4>

                            <!-- Authors Line -->
                            <?php if (!empty($authorsList) || !empty($coAuthorsList) || !empty($editorsList)): ?>
                                <p><strong>By:</strong>
                                    <?php
                                    echo implode(count($allContributors) > 1 ? ', ' : '', array_map('htmlspecialchars', $allContributors));
                                    ?>
                                </p>
                            <?php endif; ?>

                            <!-- Content Type -->
                            <p><strong>Content Type:</strong> <?php echo htmlspecialchars($book['content_type']); ?></p>

                            <!-- Publication Details -->
                            <?php if (!empty($publications)): ?>
                                <p><strong>Publication Details:</strong>
                                    <?php
                                    $pub = $publications[0];
                                    echo htmlspecialchars($pub['place'] . '. ' . $pub['publisher'] . ', ' . $pub['publish_date']);
                                    ?>
                                </p>
                            <?php endif; ?>

                            <!-- Physical Description -->
                            <p><strong>Description:</strong>
                                <?php echo htmlspecialchars($book['total_pages'] . ' pages : illustrations ; ' .
                                    $book['dimension'] . ' cm'); ?>
                            </p>

                            <!-- ISBN -->
                            <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['ISBN']); ?></p>

                            <!-- Subjects -->
                            <p><strong>Subject(s):</strong>
                                <?php
                                $subjects = [];
                                if (!empty($book['subject_category'])) $subjects[] = $book['subject_category'];
                                if (!empty($book['subject_detail'])) $subjects[] = $book['subject_detail'];
                                echo htmlspecialchars(implode(' -- ', $subjects));
                                ?>
                            </p>

                            <!-- Call Number -->
                            <p><strong>Loc classification:</strong> <?php echo htmlspecialchars($book['call_number']); ?></p>

                            <div class="book-images">
                                <?php if (!empty($book['cover_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover">
                                <?php endif; ?>
                                <?php if (!empty($book['back_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($book['back_image']); ?>" alt="Back Cover">
                                <?php endif; ?>
                            </div>

                            <!-- Holdings Section -->
                            <h3 class="mt-4">Holdings Information</h3>
                            <?php if (!empty($allCopies)): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Accession</th>
                                                <th>Call Number</th>
                                                <th>Copy Number</th>
                                                <th>Status</th>
                                                <th>Location</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($allCopies as $copy): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($copy['accession']); ?></td>
                                                    <td><?php echo htmlspecialchars($copy['call_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($copy['copy_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($copy['status']); ?></td>
                                                    <td><?php echo htmlspecialchars($copy['shelf_location']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No copies found.</div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-danger">Book not found.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- MARC 21 View Tab -->
                <div class="tab-pane fade" id="marc" role="tabpanel">
                    <?php
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
                    }
                    ?>

                    <!-- MARC Display Controls -->
                    <div class="btn-group mb-3" role="group">
                        <input type="radio" class="btn-check" name="marc-display" id="marc-labeled" checked>
                        <label class="btn btn-outline-primary" for="marc-labeled">Labeled Display</label>

                        <input type="radio" class="btn-check" name="marc-display" id="marc-plain">
                        <label class="btn btn-outline-primary" for="marc-plain">MARC Display</label>
                    </div>

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
                            '6' => 'Linkage'
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
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <style>
                        /* Add these styles to your existing MARC styles */
                        .field-header {
                            display: flex;
                            align-items: center;
                            margin-bottom: 5px;
                        }
                        .field-name {
                            color: #2c3e50;
                            font-weight: 500;
                            margin-right: 10px;
                        }
                        .subfield {
                            margin: 3px 0 3px 20px;
                            display: flex;
                            align-items: center;
                            gap: 5px;
                        }
                        .subfield-value {
                            color: #2c3e50;
                        }
                        .subfield-code {
                            cursor: help;
                        }
                        .marc-field {
                            background: #fff;
                            padding: 10px;
                            border-radius: 4px;
                            margin-bottom: 8px;
                            border: 1px solid #e3e6f0;
                        }
                        .marc-field:hover {
                            background: #f8f9fc;
                        }
                    </style>

                    <!-- MARC Display -->
                    <div id="marc-plain-view" style="display: none;" class="marc-record">
                        <pre class="marc-text">
<?php
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

                    <style>
                        .marc-record {
                            font-family: monospace;
                            background: #f8f9fc;
                            padding: 20px;
                            border-radius: 5px;
                            margin-top: 15px;
                        }
                        .marc-field {
                            margin: 5px 0;
                            display: flex;
                            align-items: start;
                        }
                        .field-tag {
                            color: #4e73df;
                            font-weight: bold;
                            margin-right: 10px;
                            min-width: 40px;
                        }
                        .indicators {
                            color: #1cc88a;
                            margin-right: 10px;
                        }
                        .subfield-delimiter {
                            color: #e74a3b;
                            font-weight: bold;
                            margin: 0 2px;
                        }
                        .subfield-code {
                            color: #36b9cc;
                            margin-right: 5px;
                        }
                        .marc-text {
                            white-space: pre-wrap;
                            font-size: 14px;
                            line-height: 1.5;
                        }
                    </style>

                    <script>
                        document.getElementById('marc-labeled').addEventListener('change', function() {
                            document.getElementById('marc-labeled-view').style.display = 'block';
                            document.getElementById('marc-plain-view').style.display = 'none';
                        });

                        document.getElementById('marc-plain').addEventListener('change', function() {
                            document.getElementById('marc-labeled-view').style.display = 'none';
                            document.getElementById('marc-plain-view').style.display = 'block';
                        });
                    </script>
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
                            echo htmlspecialchars($book['total_pages']) . ' pages';
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
       // Add click event listener for 'Add to Cart' button
       document.querySelector('.add-to-cart').addEventListener('click', function() {
            const title = this.getAttribute('data-title');
            addToCart(title);
        });

        // Add click event listener for 'Borrow Book' button
        document.querySelector('.borrow-book').addEventListener('click', function() {
            const title = this.getAttribute('data-title');
            borrowBook(title);
        });

        function addToCart(title) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to add "' + title + '" to the cart?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, add it!',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'add_to_cart.php',
                        type: 'POST',
                        data: { title: title },
                        success: function(response) {
                            var res = JSON.parse(response);
                            Swal.fire({
                                title: res.success ? 'Added!' : 'Failed!',
                                text: res.message,
                                icon: res.success ? 'success' : 'error'
                            }).then(() => {
                                if (res.success) {
                                    location.reload();
                                }
                            });
                        },
                        error: function() {
                            Swal.fire('Failed!', 'Failed to add "' + title + '" to cart.', 'error');
                        }
                    });
                }
            });
        }

        function borrowBook(title) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to borrow "' + title + '"?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, borrow it!',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'reserve_book.php',
                        type: 'POST',
                        data: { title: title },
                        success: function(response) {
                            var res = JSON.parse(response);
                            Swal.fire({
                                title: res.success ? 'Reserved!' : 'Failed!',
                                text: res.message,
                                icon: res.success ? 'success' : 'error'
                            }).then(() => {
                                if (res.success) {
                                    location.reload();
                                }
                            });
                        },
                        error: function() {
                            Swal.fire('Failed!', 'Failed to reserve "' + title + '".', 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>