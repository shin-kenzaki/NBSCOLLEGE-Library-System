<?php
session_start();
require_once '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Increase limits for large imports
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');

// Initialize variables
$message = '';
$status = '';
$importCount = 0;
$errorCount = 0;
$errors = [];
$importedBooks = [];

// Process import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($fileExt === 'csv') {
        $results = processBookCSV($file['tmp_name'], $conn);
        
        $importCount = $results['success'];
        $errorCount = count($results['errors']);
        $errors = $results['errors'];
        $importedBooks = $results['imported_books'] ?? [];

        if ($importCount > 0) {
            $status = 'success';
            $message = "Successfully imported $importCount books.";
            if ($errorCount > 0) {
                $message .= " $errorCount records had errors.";
            }
        } else {
            $status = 'error';
            $message = "No books were imported. Please check your file format.";
            if ($errorCount > 0) {
                $message .= " $errorCount records had errors.";
            }
        }
    } else {
        $status = 'error';
        $message = "Invalid file type. Please upload a CSV file.";
    }
}

function parseAnyDate($dateString) {
    if (empty($dateString)) {
        return date('Y-m-d');
    }
    
    // Handle common date formats including MM/DD/YY and MM/DD/YYYY
    if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2}$/', $dateString)) {
        $date = DateTime::createFromFormat('m/d/y', $dateString);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }
    
    if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateString)) {
        $date = DateTime::createFromFormat('m/d/Y', $dateString);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }
    
    // Fallback to strtotime for other formats
    $timestamp = strtotime($dateString);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return date('Y-m-d');
}

function processBookCSV($filePath, $conn) {
    $success = 0;
    $errors = [];
    $importedBooks = [];

    if (($handle = fopen($filePath, "r")) !== FALSE) {
        // Read and validate header row
        $header = fgetcsv($handle, 0, ",", '"', '\\');
        $expectedHeader = [
            'Accession Number', 'Date Received', 'ISBN', 'Author', 
            'Title of Book', 'Edition', 'Volume', 'Pages', 
            'Publisher', 'Place of Publication', 'Year', 
            'Program', 'Location', 'Call number from Sophia'
        ];
        
        // Case-insensitive header comparison
        $headerLower = array_map('strtolower', array_map('trim', $header));
        $expectedLower = array_map('strtolower', $expectedHeader);
        
        if ($headerLower !== $expectedLower) {
            $errors[] = "CSV header does not match the expected format. Please use the exact column names.";
            return ['success' => 0, 'errors' => $errors];
        }
        
        $rowNum = 1;
        while (($data = fgetcsv($handle, 0, ",", '"', '\\')) !== FALSE) {
            $rowNum++;
            
            // Skip empty rows
            if (count(array_filter($data)) === 0) continue;
            
            // Ensure we have exactly 14 columns
            if (count($data) !== 14) {
                $errors[] = "Row $rowNum: Incorrect number of columns (expected 14, found ".count($data).")";
                continue;
            }

            // Clean and trim all data
            $data = array_map('trim', $data);
            
            try {
                // Parse date or use current date if empty
                $dateReceived = parseAnyDate($data[1]);

                // Prepare book data with your specific CSV mapping
                $bookData = [
                    'accession' => $data[0],
                    'date_received' => $dateReceived,
                    'ISBN' => $data[2],
                    'author' => $data[3],
                    'title' => $data[4],
                    'edition' => $data[5],
                    'volume' => $data[6],
                    'total_pages' => $data[7],
                    'publisher' => $data[8],
                    'place_of_publication' => $data[9],
                    'year' => $data[10],
                    'program' => $data[11],
                    'shelf_location' => $data[12],
                    'call_number' => $data[13],
                    'status' => 'Available',
                    'media_type' => 'print',
                    'carrier_type' => 'volume',
                    'language' => 'English',
                    'entered_by' => $_SESSION['admin_id'],
                    'date_added' => $dateReceived,
                    'last_update' => date('Y-m-d')
                ];

                // Validate required fields
                $required = ['accession', 'title', 'program', 'shelf_location', 'call_number'];
                $missingFields = [];
                foreach ($required as $field) {
                    if (empty($bookData[$field])) {
                        $missingFields[] = ucfirst(str_replace('_', ' ', $field));
                    }
                }
                
                if (!empty($missingFields)) {
                    $errors[] = "Row $rowNum: Missing required fields: " . implode(', ', $missingFields);
                    continue;
                }

                // Validate accession number format
                if (!preg_match('/^\d{7}$/', $bookData['accession'])) {
                    $errors[] = "Row $rowNum: Accession number must be 7 digits";
                    continue;
                }

                // Check for existing accession
                $checkStmt = $conn->prepare("SELECT id FROM books WHERE accession = ?");
                $checkStmt->bind_param("s", $bookData['accession']);
                $checkStmt->execute();
                
                if ($checkStmt->get_result()->num_rows > 0) {
                    $errors[] = "Row $rowNum: Accession {$bookData['accession']} already exists";
                    continue;
                }

                // Begin transaction
                $conn->begin_transaction();

                // Insert book
                $insertSql = "INSERT INTO books (
                    accession, title, edition, volume,
                    total_pages, ISBN, program,
                    shelf_location, call_number, status, 
                    media_type, carrier_type, language, 
                    entered_by, date_added, last_update
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($insertSql);
                $stmt->bind_param(
                    "sssssssssssssss",
                    $bookData['accession'], $bookData['title'], $bookData['edition'],
                    $bookData['volume'], $bookData['total_pages'], $bookData['ISBN'],
                    $bookData['program'], $bookData['shelf_location'], $bookData['call_number'],
                    $bookData['status'], $bookData['media_type'], $bookData['carrier_type'],
                    $bookData['language'], $bookData['entered_by'], $bookData['date_added']
                );

                if (!$stmt->execute()) {
                    throw new Exception("Book insert failed: " . $conn->error);
                }

                $bookId = $conn->insert_id;

                // Handle publisher if provided
                if (!empty($bookData['publisher']) && !empty($bookData['place_of_publication'])) {
                    // Check if publisher exists
                    $publisherStmt = $conn->prepare("SELECT id FROM publishers WHERE publisher = ? AND place = ?");
                    $publisherStmt->bind_param("ss", $bookData['publisher'], $bookData['place_of_publication']);
                    $publisherStmt->execute();
                    $publisherResult = $publisherStmt->get_result();

                    $publisherId = null;
                    if ($publisherResult->num_rows > 0) {
                        $publisherId = $publisherResult->fetch_assoc()['id'];
                    } else {
                        // Insert new publisher
                        $insertPublisher = $conn->prepare("INSERT INTO publishers (publisher, place) VALUES (?, ?)");
                        $insertPublisher->bind_param("ss", $bookData['publisher'], $bookData['place_of_publication']);
                        if (!$insertPublisher->execute()) {
                            throw new Exception("Publisher insert failed: " . $conn->error);
                        }
                        $publisherId = $conn->insert_id;
                    }

                    // Link publisher to book
                    $publicationStmt = $conn->prepare("INSERT INTO publications (book_id, publisher_id, publish_date) VALUES (?, ?, ?)");
                    $publicationStmt->bind_param("iis", $bookId, $publisherId, $bookData['year']);
                    if (!$publicationStmt->execute()) {
                        throw new Exception("Publication link failed: " . $conn->error);
                    }
                }

                // Handle contributors (authors)
                if (!empty($bookData['author'])) {
                    // Split authors by comma or ampersand (with optional spaces)
                    $authors = preg_split('/\s*[,&]\s*/', $bookData['author']);
                    
                    foreach ($authors as $author) {
                        $author = trim($author);
                        if (empty($author)) continue;

                        // Parse name (handles formats like "Lastname, Firstname" or "Firstname Lastname")
                        if (strpos($author, ',') !== false) {
                            // Format: "Lastname, Firstname M."
                            list($lastName, $firstName) = explode(',', $author, 2);
                            $firstName = trim($firstName);
                            $middleInit = '';
                            
                            // Check for middle initial in first name
                            if (preg_match('/([A-Z])\.?$/', $firstName, $matches)) {
                                $middleInit = $matches[1];
                                $firstName = trim(substr($firstName, 0, -strlen($matches[0])));
                            }
                        } else {
                            // Format: "Firstname M. Lastname" or "Firstname Lastname"
                            $nameParts = preg_split('/\s+/', $author);
                            $firstName = $nameParts[0];
                            $lastName = end($nameParts);
                            $middleInit = (count($nameParts) > 2 ? $nameParts[1] : '');
                            
                            // Handle middle initial
                            if (preg_match('/^[A-Z]\.?$/', $middleInit)) {
                                $middleInit = str_replace('.', '', $middleInit);
                            } else {
                                $middleInit = '';
                            }
                        }

                        // Clean up names
                        $firstName = trim($firstName);
                        $lastName = trim($lastName);
                        $middleInit = trim($middleInit);

                        // Check if writer exists
                        $writerStmt = $conn->prepare("SELECT id FROM writers WHERE firstname = ? AND lastname = ?");
                        $writerStmt->bind_param("ss", $firstName, $lastName);
                        $writerStmt->execute();
                        $writerResult = $writerStmt->get_result();

                        $writerId = null;
                        if ($writerResult->num_rows > 0) {
                            $writerId = $writerResult->fetch_assoc()['id'];
                        } else {
                            // Insert new writer
                            $insertWriter = $conn->prepare("INSERT INTO writers (firstname, middle_init, lastname) VALUES (?, ?, ?)");
                            $insertWriter->bind_param("sss", $firstName, $middleInit, $lastName);
                            if (!$insertWriter->execute()) {
                                throw new Exception("Writer insert failed: " . $conn->error);
                            }
                            $writerId = $conn->insert_id;
                        }

                        // Link writer to book
                        $contributorStmt = $conn->prepare("INSERT INTO contributors (book_id, writer_id, role) VALUES (?, ?, 'Author')");
                        $contributorStmt->bind_param("iis", $bookId, $writerId);
                        if (!$contributorStmt->execute()) {
                            throw new Exception("Contributor link failed: " . $conn->error);
                        }
                    }
                }

                // Commit transaction
                $conn->commit();

                $success++;
                $importedBooks[] = [
                    'accession' => $bookData['accession'],
                    'title' => $bookData['title'],
                    'call_number' => $bookData['call_number'],
                    'location' => $bookData['shelf_location'],
                    'status' => $bookData['status']
                ];
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Row $rowNum: " . $e->getMessage();
            }
        }
        fclose($handle);
    } else {
        $errors[] = "Failed to open CSV file";
    }

    return [
        'success' => $success,
        'errors' => $errors,
        'imported_books' => $importedBooks
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Books - NBSC Library</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="Admin/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('Images/BG/library-background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Nunito', sans-serif;
            padding: 2rem 0;
        }
        .page-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
        }
        .sample-header {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: monospace;
        }
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .back-link {
            color: #6c757d;
            text-decoration: none;
        }
        .back-link:hover {
            color: #495057;
            text-decoration: underline;
        }
        .error-row {
            background-color: #fff3f3;
        }
    </style>
</head>
<body>
    <div class="container page-container">
        <h1>Import Books</h1>

        <?php if ($status === 'success'): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>

            <?php if (!empty($importedBooks)): ?>
                <div class="alert alert-info">
                    <h5 class="mb-3">Import Summary:</h5>
                    <p><?php echo count($importedBooks); ?> books were successfully added to the system.</p>
                </div>

                <div class="password-table">
                    <table id="books-import-table" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Accession</th>
                                <th>Title</th>
                                <th>Call Number</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($importedBooks as $book): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($book['accession']); ?></td>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['call_number']); ?></td>
                                    <td><?php echo htmlspecialchars($book['location']); ?></td>
                                    <td><?php echo htmlspecialchars($book['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="export-buttons">
                    <a href="book-list.php" class="btn btn-primary">
                        <i class="fas fa-book"></i> View All Books
                    </a>
                    <a href="add-book.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Another Book
                    </a>
                </div>
            <?php endif; ?>

        <?php elseif ($status === 'error'): ?>
            <div class="alert alert-danger">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$status): ?>
            <div class="import-steps">
                <h5>Instructions:</h5>
                <ol>
                    <li>Prepare your CSV file with the following columns in order:
                        <div class="sample-header">
                            Accession Number, Date Received, ISBN, Author, Title of Book, 
                            Edition, Volume, Pages, Publisher, Place of Publication, 
                            Year, Program, Location, Call number from Sophia
                        </div>
                    </li>
                    <li>The first row must contain these exact column headers</li>
                    <li><strong>Required fields</strong>: Accession Number, Title of Book, Program, Location, Call number</li>
                    <li><strong>Accession Number</strong>: Must be exactly 7 digits (e.g., 0000001)</li>
                    <li><strong>Date Received</strong>: Format as MM/DD/YYYY or MM/DD/YY (e.g., 08/22/17 or 08/22/2017)</li>
                    <li><strong>Author field</strong>:
                        <ul>
                            <li>For individual authors: "Lastname, Firstname" or "Firstname Lastname"</li>
                            <li>For multiple authors: separate with commas or ampersands (e.g., "Lastname, Firstname & Lastname, Firstname")</li>
                        </ul>
                    </li>
                    <li>Save your file as CSV (comma-delimited) before uploading</li>
                </ol>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="import_file" class="form-label">Select CSV File</label>
                    <input class="form-control" type="file" id="import_file" name="import_file" accept=".csv" required>
                </div>

                <div class="d-grid gap-2 d-md-flex">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-file-import me-2"></i> Import Books
                    </button>
                    <a href="book-list.php" class="btn btn-secondary flex-grow-1">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($errorCount > 0): ?>
            <div class="mt-4">
                <h5>Import Errors (<?php echo $errorCount; ?>):</h5>
                <div class="table-errors">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Row</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($errors as $key => $error): 
                                // Extract row number from error message if available
                                $rowNum = 1;
                                if (preg_match('/Row (\d+):/', $error, $matches)) {
                                    $rowNum = $matches[1];
                                }
                            ?>
                                <tr class="<?php echo strpos($error, 'Row') === 0 ? 'error-row' : ''; ?>">
                                    <td><?php echo $key + 1; ?></td>
                                    <td><?php echo $rowNum; ?></td>
                                    <td><?php echo $error; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$status): ?>
            <div class="text-center mt-4">
                <a href="book-list.php" class="back-link">
                    <i class="fas fa-arrow-left me-1"></i> Back to Book List
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

    <script>
        function exportToExcel() {
            const table = document.getElementById('books-import-table');
            const wb = XLSX.utils.table_to_book(table, { sheet: "Imported Books" });
            XLSX.writeFile(wb, 'imported_books_' + new Date().toISOString().slice(0, 10) + '.xlsx');
            showToast('Exported to Excel successfully');
        }

        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            doc.autoTable({
                html: '#books-import-table',
                theme: 'grid',
                headStyles: { fillColor: [78, 115, 223] }
            });
            
            doc.save('imported_books_' + new Date().toISOString().slice(0, 10) + '.pdf');
            showToast('Exported to PDF successfully');
        }

        function showToast(message) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            
            Toast.fire({ icon: 'success', title: message });
        }
    </script>
</body>
</html>