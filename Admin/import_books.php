<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian'])) {
    header("Location: index.php");
    exit();
}

include '../db.php'; // Database connection

// Initialize variables
$successCount = 0;
$errorCount = 0;
$errors = [];
$duplicates = [];
$importedBooks = [];
$totalRows = 0;
$processedRows = 0;

// Process CSV import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    // Check file upload errors
    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload error: " . $_FILES['csv_file']['error'];
    } else {
        // Validate file type
        $fileInfo = pathinfo($_FILES['csv_file']['name']);
        if (strtolower($fileInfo['extension']) !== 'csv') {
            $errors[] = "Invalid file type. Please upload a CSV file.";
        } else {
            // Process CSV file
            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
            
            // Read the header row
            $header = fgetcsv($handle);
            
            // Verify CSV structure
            $expectedHeaders = ['Accession Number', 'Date Received', 'ISBN', 'Author', 'Title of Book', 
                              'Edition', 'Volume', 'Pages', 'Publisher', 'Place of Publication', 
                              'Year', 'Program', 'Location', 'Call number from Sophia'];
            
            $headerValid = true;
            foreach ($expectedHeaders as $index => $expectedHeader) {
                if (!isset($header[$index]) || trim($header[$index]) !== $expectedHeader) {
                    $headerValid = false;
                    break;
                }
            }
            
            if (!$headerValid) {
                $errors[] = "CSV file format does not match the expected structure.";
            } else {
                // Count total rows for progress tracking
                $countHandle = fopen($_FILES['csv_file']['tmp_name'], 'r');
                fgetcsv($countHandle); // Skip header
                while (fgetcsv($countHandle) !== false) {
                    $totalRows++;
                }
                fclose($countHandle);

                // Begin transaction for database operations
                $conn->begin_transaction();
                try {
                    // Process each row in the CSV
                    while (($row = fgetcsv($handle)) !== false) {
                        // Skip empty rows
                        if (empty($row[0])) continue;
                        
                        $processedRows++;
                        
                        // Extract data from CSV row
                        $accession = trim($row[0]);
                        $dateReceived = !empty($row[1]) ? date('Y-m-d', strtotime(str_replace('/', '-', $row[1]))) : null;
                        $isbn = !empty($row[2]) ? trim($row[2]) : null;
                        $author = !empty($row[3]) ? trim($row[3]) : null;
                        $title = !empty($row[4]) ? trim($row[4]) : null;
                        $edition = !empty($row[5]) ? trim($row[5]) : null;
                        $volume = !empty($row[6]) ? trim($row[6]) : null;
                        $pages = !empty($row[7]) ? trim($row[7]) : null;
                        $publisher = !empty($row[8]) ? trim($row[8]) : null;
                        $placeOfPublication = !empty($row[9]) ? trim($row[9]) : null;
                        $year = !empty($row[10]) ? trim($row[10]) : null;
                        $program = !empty($row[11]) ? trim($row[11]) : null;
                        $location = !empty($row[12]) ? trim($row[12]) : null;
                        $callNumber = !empty($row[13]) ? trim($row[13]) : null;
                        
                        // Check if book with this accession number already exists
                        $checkStmt = $conn->prepare("SELECT id FROM books WHERE accession = ?");
                        $checkStmt->bind_param("s", $accession);
                        $checkStmt->execute();
                        $result = $checkStmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $duplicates[] = $accession;
                            continue;
                        }
                        
                        // Extract copy number from call number if available
                        $copyNumber = 1;
                        if (preg_match('/c\.?(\d+)$/i', $callNumber, $matches) || 
                            preg_match('/c(\d+)$/i', $callNumber, $matches)) {
                            $copyNumber = (int)$matches[1];
                        }
                        
                        // Insert book into database
                        $insertBookStmt = $conn->prepare("INSERT INTO books (
                            accession, title, edition, volume, total_pages, 
                            ISBN, call_number, program, shelf_location, 
                            entered_by, date_added, status, copy_number
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Available', ?)");
                        
                        $currentDate = date('Y-m-d');
                        $admin_employee_id = $_SESSION['admin_employee_id'];
                        
                        $insertBookStmt->bind_param("sssssssssssi", 
                            $accession, $title, $edition, $volume, 
                            $pages, $isbn, $callNumber, $program, $location,
                            $admin_employee_id, $currentDate, $copyNumber
                        );
                        
                        if ($insertBookStmt->execute()) {
                            $bookId = $conn->insert_id;
                            
                            // Process author(s)
                            if (!empty($author)) {
                                processAuthors($conn, $author, $bookId);
                            }
                            
                            // Process publisher
                            if (!empty($publisher) && !empty($placeOfPublication)) {
                                processPublisher($conn, $publisher, $placeOfPublication, $bookId, $year);
                            }
                            
                            // Add to imported books for display
                            $importedBooks[] = [
                                'accession' => $accession,
                                'title' => $title,
                                'author' => $author,
                                'callNumber' => $callNumber
                            ];
                            
                            $successCount++;
                            
                            // Log the book addition to updates table
                            $log_query = "INSERT INTO updates (user_id, role, title, message, `update`) VALUES (?, ?, ?, ?, NOW())";
                            $log_stmt = $conn->prepare($log_query);
                            if ($log_stmt) {
                                $log_title = "Admin Added New Book";
                                $log_message = "Admin " . $_SESSION['admin_firstname'] . " " . $_SESSION['admin_lastname'] . " imported \"" . $title . "\" (Accession: " . $accession . ")";
                                $log_stmt->bind_param("ssss", $admin_employee_id, $_SESSION['role'], $log_title, $log_message);
                                $log_stmt->execute();
                                $log_stmt->close();
                            }
                        } else {
                            $errorCount++;
                            $errors[] = "Failed to insert book: {$accession} - {$title}";
                        }
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    
                } catch (Exception $e) {
                    // Roll back transaction on error
                    $conn->rollback();
                    $errors[] = "Database error: " . $e->getMessage();
                }
            }
            
            fclose($handle);
        }
    }
}

// Function to process authors (handles both individual writers and corporate entities)
function processAuthors($conn, $author, $bookId) {
    // Split multiple authors separated by & or commas
    $authors = preg_split('/\s*&\s*/', $author);
    $allAuthors = [];
    
    foreach ($authors as $authorPart) {
        // Check if this part contains multiple authors separated by commas
        if (substr_count($authorPart, ',') > 1) {
            // This could be "LastName, FirstName, LastName2, FirstName2"
            $parts = explode(',', $authorPart);
            $tempAuthors = [];
            
            for ($i = 0; $i < count($parts); $i += 2) {
                if (isset($parts[$i + 1])) {
                    $tempAuthors[] = trim($parts[$i]) . ', ' . trim($parts[$i + 1]);
                } else {
                    $tempAuthors[] = trim($parts[$i]);
                }
            }
            
            $allAuthors = array_merge($allAuthors, $tempAuthors);
        } else {
            $allAuthors[] = trim($authorPart);
        }
    }
    
    foreach ($allAuthors as $singleAuthor) {
        if (empty($singleAuthor)) continue;
        
        // Determine if corporate or individual author
        $isCorporate = isLikelyCorporateAuthor($singleAuthor);
        
        if ($isCorporate) {
            processCorpAuthor($conn, $singleAuthor, $bookId);
        } else {
            processIndividualAuthor($conn, $singleAuthor, $bookId);
        }
    }
}

// Function to check if author is likely a corporate entity
function isLikelyCorporateAuthor($author) {
    $corporateKeywords = [
        'University', 'College', 'Institute', 'Association', 'Society', 
        'Press', 'Publishers', 'Publishing', 'Group', 'Department',
        'Committee', 'Commission', 'Council', 'Board', 'Bureau',
        'Ministry', 'Agency', 'Organization', 'Organisation', 'Center',
        'Centre', 'Foundation', 'Corporation', 'Co.', 'Inc.', 'Ltd.',
        'Limited', 'LLC', 'LLP', 'Library', 'Museum', 'Gallery'
    ];
    
    // Check for corporate indicators
    foreach ($corporateKeywords as $keyword) {
        if (stripos($author, $keyword) !== false) {
            return true;
        }
    }
    
    // Check for individual author pattern (LastName, FirstName OR FirstName LastName)
    if (preg_match('/^[A-Za-z]+,\s+[A-Za-z]+/', $author) ||
        preg_match('/^[A-Za-z]+\s+[A-Za-z]+$/', $author)) {
        return false;
    }
    
    // If no commas in the name, and multiple words, may be corporate
    if (strpos($author, ',') === false && strpos($author, ' ') !== false) {
        return true;
    }
    
    return false;
}

// Function to process corporate authors
function processCorpAuthor($conn, $authorName, $bookId) {
    // Check if corporate author exists
    $checkStmt = $conn->prepare("SELECT id FROM corporates WHERE name = ?");
    $checkStmt->bind_param("s", $authorName);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $corporateId = $result->fetch_assoc()['id'];
    } else {
        // Insert new corporate author
        $insertStmt = $conn->prepare("INSERT INTO corporates (name, type) VALUES (?, 'Organization')");
        $insertStmt->bind_param("s", $authorName);
        $insertStmt->execute();
        $corporateId = $conn->insert_id;
    }
    
    // Link corporate author to book
    $linkStmt = $conn->prepare("INSERT INTO corporate_contributors (book_id, corporate_id, role) VALUES (?, ?, 'Author')");
    $linkStmt->bind_param("ii", $bookId, $corporateId);
    $linkStmt->execute();
}

// Function to process individual authors
function processIndividualAuthor($conn, $authorName, $bookId) {
    // Parse author name
    $firstname = '';
    $middleInit = '';
    $lastname = '';
    
    // Check for "LastName, FirstName MiddleInit" format
    if (preg_match('/^([^,]+),\s*(.+?)(?:\s+([A-Z]\.?))?$/', $authorName, $matches)) {
        $lastname = trim($matches[1]);
        $firstname = trim($matches[2]);
        $middleInit = isset($matches[3]) ? trim($matches[3]) : '';
    } 
    // Check for "FirstName MiddleInit LastName" format
    else if (preg_match('/^(.+?)\s+([A-Z]\.?)\s+(.+)$/', $authorName, $matches)) {
        $firstname = trim($matches[1]);
        $middleInit = trim($matches[2]);
        $lastname = trim($matches[3]);
    }
    // Default to splitting by spaces
    else {
        $nameParts = explode(' ', $authorName);
        if (count($nameParts) > 1) {
            $lastname = array_pop($nameParts);
            if (count($nameParts) > 1 && preg_match('/^[A-Z]\.?$/', $nameParts[count($nameParts)-1])) {
                $middleInit = array_pop($nameParts);
            }
            $firstname = implode(' ', $nameParts);
        } else {
            $lastname = $authorName;
        }
    }
    
    // Check if writer exists
    $checkStmt = $conn->prepare("SELECT id FROM writers WHERE 
               (firstname = ? OR ? = '') AND 
               (middle_init = ? OR ? = '') AND 
               lastname = ?");
    $checkStmt->bind_param("sssss", 
        $firstname, $firstname, 
        $middleInit, $middleInit, 
        $lastname
    );
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $writerId = $result->fetch_assoc()['id'];
    } else {
        // Insert new writer
        $insertStmt = $conn->prepare("INSERT INTO writers (firstname, middle_init, lastname) VALUES (?, ?, ?)");
        $insertStmt->bind_param("sss", $firstname, $middleInit, $lastname);
        $insertStmt->execute();
        $writerId = $conn->insert_id;
    }
    
    // Link writer to book
    $linkStmt = $conn->prepare("INSERT INTO contributors (book_id, writer_id, role) VALUES (?, ?, 'Author')");
    $linkStmt->bind_param("ii", $bookId, $writerId);
    $linkStmt->execute();
}

// Function to process publisher information
function processPublisher($conn, $publisher, $place, $bookId, $year) {
    // Check if publisher exists
    $checkStmt = $conn->prepare("SELECT id FROM publishers WHERE publisher = ? AND place = ?");
    $checkStmt->bind_param("ss", $publisher, $place);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $publisherId = $result->fetch_assoc()['id'];
    } else {
        // Insert new publisher
        $insertStmt = $conn->prepare("INSERT INTO publishers (publisher, place) VALUES (?, ?)");
        $insertStmt->bind_param("ss", $publisher, $place);
        $insertStmt->execute();
        $publisherId = $conn->insert_id;
    }
    
    // Link publisher to book
    $linkStmt = $conn->prepare("INSERT INTO publications (book_id, publisher_id, publish_date) VALUES (?, ?, ?)");
    $linkStmt->bind_param("iis", $bookId, $publisherId, $year);
    $linkStmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Books from CSV</title>
    <style>
        .csv-example {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            overflow-x: auto;
            white-space: nowrap;
            margin-top: 10px;
        }
        .import-summary {
            margin-top: 20px;
        }
        .progress {
            height: 20px;
            margin-bottom: 15px;
        }
        #progressBar {
            transition: width 0.3s ease;
        }
        
        /* Loading Overlay Styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .loading-content {
            width: 90%;
            max-width: 500px;
        }
        
        .processing-log-container {
            margin-bottom: 1rem;
        }
        
        #processingInfo {
            font-family: monospace;
            font-size: 0.85rem;
            line-height: 1.5;
        }
        
        #processingInfo div {
            margin-bottom: 0.25rem;
            padding: 0.25rem 0;
            border-bottom: 1px dotted #e0e0e0;
        }
        
        #processingInfo div:last-child {
            border-bottom: none;
        }
        
        /* Success color for the progress bar at 100% */
        .progress-bar.complete {
            background-color: #1cc88a !important;
        }
        
        /* Progress bar animation */
        @keyframes progress-bar-stripes {
            from { background-position: 1rem 0; }
            to { background-position: 0 0; }
        }
        
        /* Import success card styles */
        .import-success-card {
            border-left: 4px solid #28a745;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            transition: all 0.3s ease;
        }

        .import-success-card:hover {
            transform: translateY(-5px);
        }

        /* Icon circle */
        .icon-circle {
            height: 3rem;
            width: 3rem;
            border-radius: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Timeline styling */
        .import-timeline {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
            padding: 1rem 0;
        }

        .import-timeline::before {
            content: '';
            position: absolute;
            width: 2px;
            background-color: #e0e0e0;
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -1px;
        }

        .timeline-item {
            margin-bottom: 2rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            z-index: 1;
        }

        .timeline-content {
            margin-left: 1rem;
            padding: 0.5rem 1rem;
            background-color: #f8f9fa;
            border-radius: 0.35rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            min-width: 200px;
            text-align: left;
        }

        /* Animation classes */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .animate__fadeIn {
            animation: fadeIn 1s ease;
        }

        /* Enhanced File Upload Styling */
        .file-upload-container {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
        }

        .file-upload-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background-color: #f8f9fc;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 180px;
        }

        .file-upload-area:hover, .file-upload-area.drag-over {
            border-color: #4e73df;
            background-color: rgba(78, 115, 223, 0.05);
        }

        .file-upload-area .upload-icon {
            font-size: 2rem;
            color: #4e73df;
            margin-bottom: 10px;
        }

        .file-upload-area .upload-text {
            color: #6e707e;
            margin-bottom: 10px;
        }

        .file-upload-area .upload-hint {
            font-size: 0.8rem;
            color: #858796;
        }

        .file-upload-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-preview-container {
            display: none;
            margin-top: 15px;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .file-preview-container.show {
            display: block;
        }

        .csv-preview {
            padding: 15px;
            background-color: #f8f9fc;
            max-height: 200px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }

        .csv-preview-header {
            background-color: #4e73df;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
        }

        .file-info {
            padding: 10px 15px;
            background: #f8f9fc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e3e6f0;
        }

        .file-info .file-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 70%;
            font-weight: bold;
        }

        .file-info .file-size {
            color: #858796;
        }

        .file-info .file-icon {
            margin-right: 10px;
            color: #4e73df;
        }

        .file-actions {
            display: flex;
            padding: 10px 15px;
            border-top: 1px solid #e3e6f0;
            background-color: #f8f9fc;
        }

        .file-remove {
            color: #e74a3b;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .file-remove:hover {
            color: #be3128;
        }

        .file-validate {
            color: #1cc88a;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            margin-left: auto;
            transition: all 0.2s ease;
        }

        .file-validate:hover {
            color: #169a6e;
        }
        
        .file-upload-container.is-invalid .file-upload-area {
            border-color: #e74a3b;
        }

        .file-upload-container .invalid-feedback {
            display: none;
        }

        .file-upload-container.is-invalid .invalid-feedback {
            display: block;
        }
    </style>
</head>
<body>
    <?php include '../admin/inc/header.php'; ?>

    <!-- Main Content -->
    <div id="content" class="d-flex flex-column min-vh-100">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Import Books from CSV</h1>
                <a href="book_list.php" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Books
                </a>
            </div>

            <!-- CSV Format Instructions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">CSV File Format</h6>
                </div>
                <div class="card-body">
                    <p>The CSV file should have the following columns:</p>
                    <div class="row">
                        <div class="col-md-6">
                            <ol>
                                <li>Accession Number</li>
                                <li>Date Received (MM/DD/YY format)</li>
                                <li>ISBN</li>
                                <li>Author</li>
                                <li>Title of Book</li>
                                <li>Edition</li>
                                <li>Volume</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <ol start="8">
                                <li>Pages</li>
                                <li>Publisher</li>
                                <li>Place of Publication</li>
                                <li>Year</li>
                                <li>Program</li>
                                <li>Location</li>
                                <li>Call number from Sophia</li>
                            </ol>
                        </div>
                    </div>
                    <p><strong>Example:</strong></p>
                    <div class="csv-example">
                        0000001,08/22/17,9780735699236,"Lambert, Joan & Frye, Curtis",Microsoft Office 2013 Step by Step,,,539,Microsoft Press,United States,2015,BSCS,Circulation,005.5 L17 2015 c1
                    </div>
                </div>
            </div>

            <!-- Enhanced Upload Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Upload CSV File</h6>
                </div>
                <div class="card-body">
                    <form id="csvUploadForm" action="" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="csv_file">Select CSV File:</label>
                            
                            <!-- Enhanced File Upload Container -->
                            <div class="file-upload-container">
                                <div class="file-upload-area">
                                    <i class="fas fa-file-csv upload-icon"></i>
                                    <div class="upload-text">Drag & drop your CSV file here</div>
                                    <div class="upload-hint">or click to browse files</div>
                                </div>
                                <input type="file" class="file-upload-input" id="csv_file" name="csv_file" accept=".csv" required>
                                <div class="invalid-feedback">Please select a valid CSV file.</div>
                                
                                <!-- File Preview Container -->
                                <div class="file-preview-container">
                                    <div class="csv-preview-header">
                                        CSV File Preview
                                    </div>
                                    <div class="csv-preview" id="csvPreview">
                                        <!-- CSV content preview will be shown here -->
                                    </div>
                                    <div class="file-info">
                                        <div>
                                            <i class="fas fa-file-csv file-icon"></i>
                                            <span class="file-name">No file selected</span>
                                        </div>
                                        <span class="file-size">0 KB</span>
                                    </div>
                                    <div class="file-actions">
                                        <div class="file-remove">
                                            <i class="fas fa-trash-alt mr-1"></i> Remove
                                        </div>
                                        <div class="file-validate">
                                            <i class="fas fa-check-circle mr-1"></i> Validate Structure
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <small class="form-text text-muted mt-2">Please upload a valid CSV file with the correct format.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-import"></i> Upload and Import
                        </button>
                    </form>
                </div>
            </div>

            <!-- Progress Bar (shows during import) -->
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file']) && $totalRows > 0): ?>
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="progress">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" aria-valuenow="<?php echo round(($processedRows / $totalRows) * 100); ?>" 
                                 aria-valuemin="0" aria-valuemax="100" 
                                 style="width: <?php echo round(($processedRows / $totalRows) * 100); ?>%">
                            </div>
                        </div>
                        <p id="progressText">
                            <?php echo $processedRows; ?> of <?php echo $totalRows; ?> 
                            (<?php echo round(($processedRows / $totalRows) * 100); ?>%)
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Import Results -->
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Import Results</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle"></i> Errors:</h5>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="import-summary">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Summary:</h5>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body">
                                                    <h5 class="card-title">Successfully Imported</h5>
                                                    <p class="card-text display-4"><?php echo $successCount; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <div class="card bg-warning text-dark">
                                                <div class="card-body">
                                                    <h5 class="card-title">Duplicates Skipped</h5>
                                                    <p class="card-text display-4"><?php echo count($duplicates); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <div class="card bg-danger text-white">
                                                <div class="card-body">
                                                    <h5 class="card-title">Errors</h5>
                                                    <p class="card-text display-4"><?php echo $errorCount; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($duplicates)): ?>
                                        <button class="btn btn-outline-warning mt-3" type="button" data-toggle="collapse" 
                                                data-target="#duplicatesList" aria-expanded="false" aria-controls="duplicatesList">
                                            <i class="fas fa-list"></i> Show/Hide Duplicate Accessions
                                        </button>
                                        <div class="collapse mt-3" id="duplicatesList">
                                            <div class="card card-body">
                                                <h6>Duplicate Accession Numbers:</h6>
                                                <?php if (count($duplicates) <= 10): ?>
                                                    <ul>
                                                        <?php foreach ($duplicates as $accession): ?>
                                                            <li><?php echo htmlspecialchars($accession); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <p>First 10 of <?php echo count($duplicates); ?> duplicates:</p>
                                                    <ul>
                                                        <?php for ($i = 0; $i < 10; $i++): ?>
                                                            <li><?php echo htmlspecialchars($duplicates[$i]); ?></li>
                                                        <?php endfor; ?>
                                                    </ul>
                                                    <p>...and <?php echo count($duplicates) - 10; ?> more.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($successCount > 0): ?>
                            <div class="mt-4">
                                <a href="book_list.php" class="btn btn-success">
                                    <i class="fas fa-list"></i> View Book List
                                </a>
                                <a href="import_books.php" class="btn btn-primary ml-2">
                                    <i class="fas fa-upload"></i> Import More Books
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Enhanced Success Notification -->
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file']) && $successCount > 0): ?>
                    <div class="card shadow mb-4 import-success-card animate__animated animate__fadeIn">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-success text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-check-circle fa-lg me-2"></i> Import Process Completed!
                            </h6>
                            <span class="badge bg-light text-success rounded-pill">
                                <?php echo date('M d, Y h:i A'); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="display-4 text-success mb-3"><i class="fas fa-book"></i></div>
                                <h4>CSV Import Completed Successfully!</h4>
                                <p class="text-muted">Your library catalog has been updated with new items</p>
                                
                                <!-- Import Timeline -->
                                <div class="import-timeline my-4">
                                    <div class="timeline-item">
                                        <div class="timeline-icon bg-primary">
                                            <i class="fas fa-file-csv"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <p class="mb-0"><strong>CSV Processed</strong><br><?php echo $totalRows; ?> rows</p>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-icon bg-success">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <p class="mb-0"><strong>Records Added</strong><br><?php echo $successCount; ?> books</p>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-icon <?php echo count($duplicates) > 0 ? 'bg-warning' : 'bg-light'; ?>">
                                            <i class="fas fa-copy"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <p class="mb-0"><strong>Duplicates Skipped</strong><br><?php echo count($duplicates); ?> items</p>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-icon <?php echo $errorCount > 0 ? 'bg-danger' : 'bg-light'; ?>">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <p class="mb-0"><strong>Errors</strong><br><?php echo $errorCount; ?> issues</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Import Statistics -->
                            <div class="row mb-4">
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-success text-white h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-uppercase">Successfully Imported</h6>
                                                    <h2 class="display-4 mb-0"><?php echo $successCount; ?></h2>
                                                </div>
                                                <div class="icon-circle bg-white text-success">
                                                    <i class="fas fa-check fa-2x"></i>
                                                </div>
                                            </div>
                                            <div class="mt-2 text-white-50">
                                                <small><?php echo round(($successCount / max(1, $totalRows)) * 100); ?>% success rate</small>
                                                <div class="progress bg-white bg-opacity-25 mt-1" style="height: 5px">
                                                    <div class="progress-bar bg-white" role="progressbar" style="width: <?php echo round(($successCount / max(1, $totalRows)) * 100); ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <div class="card <?php echo count($duplicates) > 0 ? 'bg-warning text-dark' : 'bg-light text-muted'; ?> h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-uppercase">Duplicates Skipped</h6>
                                                    <h2 class="display-4 mb-0"><?php echo count($duplicates); ?></h2>
                                                </div>
                                                <div class="icon-circle <?php echo count($duplicates) > 0 ? 'bg-white text-warning' : 'bg-secondary text-white'; ?>">
                                                    <i class="fas fa-copy fa-2x"></i>
                                                </div>
                                            </div>
                                            <div class="mt-2 <?php echo count($duplicates) > 0 ? 'text-dark-50' : 'text-muted'; ?>">
                                                <small><?php echo count($duplicates) > 0 ? 'Review duplicates below' : 'No duplicates found'; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <div class="card <?php echo $errorCount > 0 ? 'bg-danger text-white' : 'bg-light text-muted'; ?> h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-uppercase">Errors</h6>
                                                    <h2 class="display-4 mb-0"><?php echo $errorCount; ?></h2>
                                                </div>
                                                <div class="icon-circle <?php echo $errorCount > 0 ? 'bg-white text-danger' : 'bg-secondary text-white'; ?>">
                                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                                </div>
                                            </div>
                                            <div class="mt-2 <?php echo $errorCount > 0 ? 'text-white-50' : 'text-muted'; ?>">
                                                <small><?php echo $errorCount > 0 ? 'Check error details below' : 'No errors encountered'; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-info text-white h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-uppercase">Processing Time</h6>
                                                    <h2 class="display-4 mb-0"><?php echo isset($processingTime) ? round($processingTime, 2) : '—'; ?>s</h2>
                                                </div>
                                                <div class="icon-circle bg-white text-info">
                                                    <i class="fas fa-clock fa-2x"></i>
                                                </div>
                                            </div>
                                            <div class="mt-2 text-white-50">
                                                <small><?php echo $processedRows; ?> of <?php echo $totalRows; ?> rows processed</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="text-center">
                                <a href="book_list.php" class="btn btn-primary me-2">
                                    <i class="fas fa-list"></i> View Book List
                                </a>
                                <a href="import_books.php" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-file-import"></i> Import Another CSV
                                </a>
                                <a href="#" class="btn btn-outline-success" id="downloadReport">
                                    <i class="fas fa-file-excel"></i> Export Report
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Table of Imported Books -->
                <?php if (!empty($importedBooks)): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Imported Books</h6>
                            <span class="badge badge-pill badge-success"><?php echo count($importedBooks); ?> Books</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="importedBooksTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Accession</th>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Call Number</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($importedBooks as $book): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($book['accession']); ?></td>
                                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                <td><?php echo htmlspecialchars($book['callNumber']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                        $(document).ready(function() {
                            $('#importedBooksTable').DataTable({
                                "pageLength": 10,
                                "order": [[0, "asc"]],
                                "language": {
                                    "search": "_INPUT_",
                                    "searchPlaceholder": "Search imported books..."
                                }
                            });
                        });
                    </script>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Loading Overlay for CSV Processing -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-content">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0"><i class="fas fa-sync fa-spin me-2"></i> Processing CSV Import</h5>
                </div>
                <div class="card-body">
                    <div class="progress mb-4" style="height: 25px;">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" 
                             aria-valuemax="100">0%</div>
                    </div>
                    
                    <p id="progressText" class="text-center mb-3">Initializing import process...</p>
                    
                    <div class="processing-log-container">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold">Processing Log</h6>
                            </div>
                            <div class="card-body p-2" style="height: 150px; overflow-y: auto;">
                                <div id="processingInfo" class="small"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../Admin/inc/footer.php' ?>

    <!-- Enhanced File Upload JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeFileUpload();
        
        // ... existing JavaScript ...
    });
    
    function initializeFileUpload() {
        const container = document.querySelector('.file-upload-container');
        if (!container) return;
        
        const input = container.querySelector('.file-upload-input');
        const uploadArea = container.querySelector('.file-upload-area');
        const previewContainer = container.querySelector('.file-preview-container');
        const csvPreview = container.querySelector('#csvPreview');
        const fileName = container.querySelector('.file-name');
        const fileSize = container.querySelector('.file-size');
        const removeButton = container.querySelector('.file-remove');
        const validateButton = container.querySelector('.file-validate');
        
        // Handle file selection
        input.addEventListener('change', function() {
            handleFileSelection(this.files[0]);
        });
        
        // Handle remove button click
        if (removeButton) {
            removeButton.addEventListener('click', function(e) {
                e.stopPropagation();
                clearFileSelection();
            });
        }
        
        // Handle validation button click
        if (validateButton) {
            validateButton.addEventListener('click', function(e) {
                e.stopPropagation();
                validateCsvFile(input.files[0]);
            });
        }
        
        // Handle drag and drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });
        
        uploadArea.addEventListener('dragleave', function() {
            uploadArea.classList.remove('drag-over');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            handleFileSelection(e.dataTransfer.files[0]);
        });
        
        // Click on upload area to trigger file input
        uploadArea.addEventListener('click', function() {
            input.click();
        });
        
        // Function to handle selected file
        function handleFileSelection(file) {
            if (!file) return;
            
            // Check if file is a CSV
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('Please select a CSV file (.csv)');
                clearFileSelection();
                container.classList.add('is-invalid');
                return;
            }
            
            // Update file info
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            
            // Show preview container
            previewContainer.classList.add('show');
            
            // Read file for preview
            const reader = new FileReader();
            reader.onload = function(e) {
                const content = e.target.result;
                
                // Display first 5 lines (or less if file is smaller)
                const lines = content.split('\n');
                const previewLines = lines.slice(0, Math.min(10, lines.length));
                csvPreview.textContent = previewLines.join('\n');
                
                // Add preview note if truncated
                if (lines.length > 10) {
                    csvPreview.textContent += '\n\n[...] ' + (lines.length - 10) + ' more rows (not shown in preview)';
                }
            };
            reader.readAsText(file);
            
            // Remove invalid state if present
            container.classList.remove('is-invalid');
        }
        
        // Function to clear file selection
        function clearFileSelection() {
            input.value = '';
            previewContainer.classList.remove('show');
            csvPreview.textContent = '';
            fileName.textContent = 'No file selected';
            fileSize.textContent = '0 KB';
        }
        
        // Function to validate CSV structure
        function validateCsvFile(file) {
            if (!file) {
                alert('No file selected. Please select a CSV file first.');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const content = e.target.result;
                const lines = content.split('\n');
                
                if (lines.length < 2) {
                    alert('Invalid CSV file: The file appears to be empty or have no data rows.');
                    return;
                }
                
                const headerLine = lines[0];
                const expectedHeaders = ['Accession Number', 'Date Received', 'ISBN', 'Author', 'Title of Book', 
                                     'Edition', 'Volume', 'Pages', 'Publisher', 'Place of Publication', 
                                     'Year', 'Program', 'Location', 'Call number from Sophia'];
                
                const headers = headerLine.split(',');
                
                // Simple validation - check number of columns
                if (headers.length < expectedHeaders.length) {
                    alert('CSV validation failed: The file has fewer columns than expected. Please check the format.');
                } else {
                    // Check for matching headers (simple check - could be enhanced)
                    let headerMatches = 0;
                    for (let i = 0; i < expectedHeaders.length; i++) {
                        if (headers[i] && headers[i].trim().replace(/"/g, '') === expectedHeaders[i]) {
                            headerMatches++;
                        }
                    }
                    
                    if (headerMatches >= expectedHeaders.length * 0.7) {
                        alert('CSV validation passed! The file structure appears to be correct.');
                    } else {
                        alert('CSV validation warning: Header columns may not match the expected format. Please verify your file.');
                    }
                }
            };
            reader.readAsText(file);
        }
    }
    
    // Utility function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    </script>

    <!-- JavaScript for the loading overlay functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('csvUploadForm') || document.querySelector('form[enctype="multipart/form-data"]');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const processingInfo = document.getElementById('processingInfo');
        
        // Processing messages to display during import
        const processingMessages = [
            "Reading CSV file and validating format...",
            "Checking for duplicate accession numbers...",
            "Processing book entries...",
            "Identifying authors and contributors...",
            "Creating database associations...",
            "Processing publisher information...",
            "Generating call numbers...",
            "Finalizing import process..."
        ];
        
        let currentMsgIndex = 0;
        let processingInterval;
        
        if (form) {
            form.addEventListener('submit', function(e) {
                const fileInput = document.querySelector('input[type="file"]');
                
                if (fileInput.files.length > 0) {
                    e.preventDefault(); // Prevent the default form submission
                    
                    // Show loading overlay
                    loadingOverlay.style.display = 'flex';
                    
                    // Start with 0% progress
                    updateProgress(0, "Preparing to import...");
                    
                    // Add initial processing message
                    addProcessingMessage("Starting import process...");
                    
                    // Simulate progress updates with processing messages
                    processingInterval = setInterval(function() {
                        // Update progress bar (random increments between 5-15%)
                        const currentProgress = parseInt(progressBar.getAttribute('aria-valuenow'));
                        if (currentProgress < 90) {
                            const increment = Math.floor(Math.random() * 10) + 5;
                            const newProgress = Math.min(currentProgress + increment, 90);
                            updateProgress(newProgress, `Processing... ${newProgress}%`);
                            
                            // Add a processing message
                            if (currentMsgIndex < processingMessages.length) {
                                addProcessingMessage(processingMessages[currentMsgIndex]);
                                currentMsgIndex++;
                            }
                        }
                    }, 2000); // Update every 2 seconds
                    
                    // Submit the form with AJAX
                    const formData = new FormData(form);
                    
                    fetch(form.action || window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(html => {
                        // Clear the interval
                        clearInterval(processingInterval);
                        
                        // Complete the progress bar
                        updateProgress(100, "Import complete!");
                        progressBar.classList.add('complete');
                        
                        addProcessingMessage("Import completed successfully!");
                        
                        // Replace the page content with the response
                        setTimeout(function() {
                            // We'll let the page reload normally - no need to maintain the overlay
                            document.open();
                            document.write(html);
                            document.close();
                        }, 1000);
                    })
                    .catch(error => {
                        clearInterval(processingInterval);
                        console.error('Error:', error);
                        addProcessingMessage("Error occurred: " + error.message);
                        updateProgress(100, "Import failed!");
                        progressBar.classList.remove('complete');
                        progressBar.classList.add('bg-danger');
                        
                        // Allow the user to try again
                        setTimeout(function() {
                            loadingOverlay.style.display = 'none';
                        }, 3000);
                    });
                    
                    return false; // Prevent form submission
                }
            });
        }
        
        // Function to update progress bar
        function updateProgress(percent, message) {
            // Ensure jQuery is available
            if (typeof $ !== 'undefined') {
                // Animate both progress bars for smooth transition
                $(progressBar).animate({
                    width: percent + '%'
                }, 400, function() {
                    // Update text after animation completes
                    progressBar.setAttribute('aria-valuenow', percent);
                    progressBar.textContent = percent + '%';
                    progressText.textContent = message;
                });
            } else {
                // Fallback if jQuery is not available
                progressBar.style.width = percent + '%';
                progressBar.setAttribute('aria-valuenow', percent);
                progressBar.textContent = percent + '%';
                progressText.textContent = message;
            }
            
            // When we reach 100%, add the complete class
            if (percent >= 100) {
                progressBar.classList.add('complete');
            }
        }
        
        // Function to add processing message
        function addProcessingMessage(message) {
            const messageElement = document.createElement('div');
            messageElement.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            processingInfo.appendChild(messageElement);
            processingInfo.parentElement.scrollTop = processingInfo.parentElement.scrollHeight;
        }
        
        // Check if PHP has already processed a form submission
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])): ?>
        // Clear the loading overlay once page is loaded with results
        $(document).ready(function() {
            // Hide the progress display immediately since import is done
            $('#loadingOverlay').hide();
            
            // Remove progress section from DOM if it exists - we don't need it anymore
            $('.card.shadow.mb-4 .progress').parent().remove();
            
            <?php if ($successCount > 0): ?>
            // If successful imports, trigger celebration
            setTimeout(celebrateSuccess, 300);
            <?php endif; ?>
        });
        <?php endif; ?>
    });

    // Fix for DataTables reinitialization issue
    $(document).ready(function() {
        // Safety check for DataTables
        if ($.fn.DataTable && $('#importedBooksTable').length) {
            if ($.fn.DataTable.isDataTable('#importedBooksTable')) {
                $('#importedBooksTable').DataTable().destroy();
            }
            
            setTimeout(function() {
                $('#importedBooksTable').DataTable({
                    "pageLength": 10,
                    "order": [[0, "asc"]],
                    "language": {
                        "search": "_INPUT_",
                        "searchPlaceholder": "Search imported books..."
                    }
                });
            }, 100);
        }
    });
</script>

<!-- Add this CSS to enhance the progress bar transition -->
<style>
    /* Enhanced progress bar animation */
    .progress-bar {
        transition: width 0.4s ease;
    }
    
    /* Success state styling */
    .progress-bar.complete {
        background-color: #1cc88a !important;
        transition: background-color 0.5s ease;
    }
    
    /* Failure state styling */
    .progress-bar.bg-danger {
        background-color: #e74a3b !important;
        transition: background-color 0.5s ease;
    }
    
    /* Add some animation to the success message */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translate3d(0, 20px, 0);
        }
        to {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }
    }
    
    .processing-complete {
        animation: fadeInUp 0.5s ease-out;
    }
</style>

<!-- Add the confetti animation and styles at the end of the file, before the closing body tag -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script>
// Function to trigger confetti when import is successful
function celebrateSuccess() {
    const successCard = document.querySelector('.import-success-card');
    
    if (successCard) {
        // Calculate middle of the card for confetti origin
        const rect = successCard.getBoundingClientRect();
        const x = (rect.left + rect.right) / 2 / window.innerWidth;
        const y = (rect.top + rect.top + 100) / window.innerHeight; // Start a bit below the top
        
        // Trigger confetti
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { x, y },
            disableForReducedMotion: true,
            colors: ['#28a745', '#17a2b8', '#007bff', '#6610f2']
        });
        
        // Secondary confetti burst after a short delay
        setTimeout(() => {
            confetti({
                particleCount: 50,
                angle: 60,
                spread: 55,
                origin: { x: x - 0.1, y },
                colors: ['#28a745', '#17a2b8', '#007bff']
            });
            
            confetti({
                particleCount: 50,
                angle: 120,
                spread: 55,
                origin: { x: x + 0.1, y },
                colors: ['#28a745', '#17a2b8', '#007bff']
            });
        }, 500);
    }
}

// Trigger celebration on page load if there's a success card
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file']) && $successCount > 0): ?>
        // Add a slight delay for visual impact
        setTimeout(celebrateSuccess, 300);
    <?php endif; ?>
    
    // Add download report functionality
    const downloadBtn = document.getElementById('downloadReport');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Create CSV content
            const csvContent = [
                ['Import Report - <?php echo date("Y-m-d H:i:s"); ?>'],
                [''],
                ['Summary:'],
                ['Total Rows', '<?php echo $totalRows; ?>'],
                ['Successfully Imported', '<?php echo $successCount; ?>'],
                ['Duplicates Skipped', '<?php echo count($duplicates); ?>'],
                ['Errors', '<?php echo $errorCount; ?>'],
                ['']
            ];
            
            <?php if (!empty($importedBooks)): ?>
            // Add imported books
            csvContent.push(['Imported Books:']);
            csvContent.push(['Accession', 'Title', 'Author', 'Call Number']);
            <?php foreach ($importedBooks as $book): ?>
            csvContent.push([
                '<?php echo addslashes($book['accession']); ?>', 
                '<?php echo addslashes($book['title']); ?>', 
                '<?php echo addslashes($book['author']); ?>', 
                '<?php echo addslashes($book['callNumber']); ?>'
            ]);
            <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($duplicates)): ?>
            // Add duplicates
            csvContent.push(['']);
            csvContent.push(['Duplicates:']);
            <?php foreach(array_slice($duplicates, 0, 50) as $dup): ?>
            csvContent.push(['<?php echo addslashes($dup); ?>']);
            <?php endforeach; ?>
            <?php if (count($duplicates) > 50): ?>
            csvContent.push(['... and <?php echo count($duplicates) - 50; ?> more']);
            <?php endif; ?>
            <?php endif; ?>
            
            // Convert to CSV
            const csvString = csvContent.map(e => e.join(',')).join('\n');
            const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            
            // Create download link and trigger click
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'import-report-<?php echo date("Y-m-d"); ?>.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }
});
</script>

<!-- If PHP has processed an import, don't display progress bar in results page -->
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file']) && $successCount > 0): ?>
<style>
    /* Hide all progress bars and loading indicators after import completes */
    .card.progress-card {
        display: none !important;
    }
    
    #loadingOverlay {
        display: none !important;
    }
    
    .progress-container {
        display: none !important;
    }
</style>
<?php endif; ?>

</body>
</html>
