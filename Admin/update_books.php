<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../db.php';

// Get accession range from URL
$accession_range = isset($_GET['accession_range']) ? $_GET['accession_range'] : '';
if (empty($accession_range)) {
    die("No accession range provided");
}

// Function to extract all accession numbers from a range string
function parseAccessionRange($range) {
    $accessions = [];
    $parts = explode(',', $range);
    
    foreach ($parts as $part) {
        if (strpos($part, '-') !== false) {
            list($start, $end) = explode('-', $part);
            for ($i = (int)$start; $i <= (int)$end; $i++) {
                $accessions[] = $i;
            }
        } else {
            $accessions[] = (int)$part;
        }
    }
    
    return $accessions;
}

// Parse accession range and get all accession numbers
$accessions = parseAccessionRange($accession_range);
$accession_list = implode(',', $accessions);

// Modify the query to get all copies of the book instead of just the first one
$query = "SELECT b.*, 
          GROUP_CONCAT(DISTINCT CONCAT(w.lastname, ', ', w.firstname, ' ', w.middle_init, ':', c.role) ORDER BY c.role) as authors,
          GROUP_CONCAT(DISTINCT CONCAT(corp.name, ':', cc.role) ORDER BY cc.role) as corporate_contributors,
          p.publisher_id,
          YEAR(p.publish_date) as publish_date,
          pub.publisher,
          pub.place
          FROM books b 
          LEFT JOIN contributors c ON b.id = c.book_id
          LEFT JOIN writers w ON c.writer_id = w.id
          LEFT JOIN corporate_contributors cc ON b.id = cc.book_id
          LEFT JOIN corporates corp ON cc.corporate_id = corp.id
          LEFT JOIN publications p ON b.id = p.book_id
          LEFT JOIN publishers pub ON p.publisher_id = pub.id
          WHERE b.accession IN ($accession_list)
          GROUP BY b.id
          ORDER BY b.copy_number";

$result = $conn->query($query);
$books = $result->fetch_all(MYSQLI_ASSOC);

if (empty($books)) {
    die("No books found with the provided accession numbers");
}

// Get all writers for dropdown
$writers_query = "SELECT * FROM writers ORDER BY lastname, firstname";
$writers_result = $conn->query($writers_query);
$writers = $writers_result->fetch_all(MYSQLI_ASSOC);

// Get all publishers for dropdown
$publishers_query = "SELECT * FROM publishers ORDER BY publisher";
$publishers_result = $conn->query($publishers_query);
$publishers = $publishers_result->fetch_all(MYSQLI_ASSOC);

// Get all corporates for dropdown
$corporates_query = "SELECT * FROM corporates ORDER BY name";
$corporates_result = $conn->query($corporates_query);
$corporates = $corporates_result->fetch_all(MYSQLI_ASSOC);

// Get the book's individual contributors (writers)
$first_book_id = $books[0]['id'];
$contributors_query = "SELECT c.*, w.firstname, w.middle_init, w.lastname 
                      FROM contributors c 
                      JOIN writers w ON c.writer_id = w.id 
                      WHERE c.book_id = ?";
$stmt = $conn->prepare($contributors_query);
$stmt->bind_param("i", $first_book_id);
$stmt->execute();
$contributors_result = $stmt->get_result();
$contributors = $contributors_result->fetch_all(MYSQLI_ASSOC);

// Get the book's corporate contributors
$corporate_contributors_query = "SELECT cc.*, corp.name, corp.type 
                               FROM corporate_contributors cc 
                               JOIN corporates corp ON cc.corporate_id = corp.id 
                               WHERE cc.book_id = ?";
$stmt = $conn->prepare($corporate_contributors_query);
$stmt->bind_param("i", $first_book_id);
$stmt->execute();
$corporate_contributors_result = $stmt->get_result();
$corporate_contributors = $corporate_contributors_result->fetch_all(MYSQLI_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error_messages = [];
    $duplicateAccessions = [];

    // Validate accession numbers for duplicates
    if (isset($_POST['copies'])) {
        foreach ($_POST['copies'] as $book_id => $copy_data) {
            $accession = $copy_data['accession'];
            $book_id = intval($book_id);
            
            // Check if this accession already exists in another book
            $query = "SELECT id, title FROM books WHERE accession = ? AND id != ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $accession, $book_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $duplicate = $result->fetch_assoc();
                $duplicateAccessions[$book_id] = [
                    'accession' => $accession,
                    'duplicate_id' => $duplicate['id'],
                    'duplicate_title' => $duplicate['title']
                ];
                $error_messages[] = "Accession number '{$accession}' is already used by another book: {$duplicate['title']}";
            }
        }
    }

    // Only proceed if there are no duplicate accessions
    if (empty($duplicateAccessions)) {
        $conn->begin_transaction();
        try {
            // Handle image uploads first
            $uploadPath = '../Images/book-image/';
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Get current date for the update
            $current_date = date('Y-m-d');

            // Prepare common fields first
            $common_fields = [
                'title' => $_POST['title'],
                'preferred_title' => $_POST['preferred_title'],
                'parallel_title' => $_POST['parallel_title'],
                'subject_category' => $_POST['subject_category'],
                'program' => $_POST['program'],
                'subject_detail' => $_POST['subject_detail'],
                'summary' => $_POST['summary'],
                'contents' => $_POST['contents'],
                'dimension' => $_POST['dimension'],
                'total_pages' => $_POST['total_pages'],
                'supplementary_contents' => $_POST['supplementary_contents'],
                'ISBN' => $_POST['ISBN'],
                'content_type' => $_POST['content_type'],
                'media_type' => $_POST['media_type'],
                'carrier_type' => $_POST['carrier_type'],
                'language' => $_POST['language'],
                'URL' => $_POST['URL'],
                'updated_by' => $_SESSION['admin_employee_id'],  // Add admin employee ID
                'last_update' => $current_date  // Add current date
            ];

            // Process front image
            if (isset($_POST['remove_front_image']) && $_POST['remove_front_image'] == '1') {
                $common_fields['front_image'] = '';  // Clear the image path
            } elseif (!empty($_FILES['front_image']['name'])) {
                $front_ext = strtolower(pathinfo($_FILES['front_image']['name'], PATHINFO_EXTENSION));
                $front_filename = 'front_' . time() . '_' . uniqid() . '.' . $front_ext;
                $front_image_path = $uploadPath . $front_filename;
                
                if (move_uploaded_file($_FILES['front_image']['tmp_name'], $front_image_path)) {
                    $common_fields['front_image'] = $front_image_path;
                }
            }

            // Process back image
            if (isset($_POST['remove_back_image']) && $_POST['remove_back_image'] == '1') {
                $common_fields['back_image'] = '';  // Clear the image path
            } elseif (!empty($_FILES['back_image']['name'])) {
                $back_ext = strtolower(pathinfo($_FILES['back_image']['name'], PATHINFO_EXTENSION));
                $back_filename = 'back_' . time() . '_' . uniqid() . '.' . $back_ext;
                $back_image_path = $uploadPath . $back_filename;
                
                if (move_uploaded_file($_FILES['back_image']['tmp_name'], $back_image_path)) {
                    $common_fields['back_image'] = $back_image_path;
                }
            }
            
            // Build and execute the update query with all fields including images
            $update_query = "UPDATE books SET " . 
                           implode(", ", array_map(fn($k) => "$k = ?", array_keys($common_fields))) .
                           " WHERE accession IN ($accession_list)";
            
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param(str_repeat('s', count($common_fields)), ...array_values($common_fields));
            $stmt->execute();

            // 2. Update individual contributors (writers)
            // Get all book IDs that share this accession list
            $get_books_query = "SELECT id FROM books WHERE accession IN ($accession_list)";
            $books_result = $conn->query($get_books_query);
            $book_ids = $books_result->fetch_all(MYSQLI_ASSOC);

            // Delete existing contributors
            $delete_contributors = "DELETE FROM contributors WHERE book_id IN (SELECT id FROM books WHERE accession IN ($accession_list))";
            $conn->query($delete_contributors);

            // Add new contributors if any are selected
            if (isset($_POST['contributors']) && !empty($_POST['contributors'])) {
                foreach ($book_ids as $book) {
                    foreach ($_POST['contributors'] as $contributor) {
                        // Parse the JSON string if it's a string, otherwise use as is
                        $contributor_data = is_string($contributor) ? json_decode($contributor, true) : $contributor;
                        
                        if (!$contributor_data || !isset($contributor_data['writer_id']) || !isset($contributor_data['role'])) {
                            continue; // Skip invalid data
                        }

                        $insert_contributor = "INSERT INTO contributors (book_id, writer_id, role) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($insert_contributor);
                        $stmt->bind_param('iis', $book['id'], $contributor_data['writer_id'], $contributor_data['role']);
                        $stmt->execute();
                    }
                }
            }

            // 3. Update corporate contributors
            // Delete existing corporate contributors
            $delete_corp = "DELETE FROM corporate_contributors WHERE book_id IN (SELECT id FROM books WHERE accession IN ($accession_list))";
            $conn->query($delete_corp);

            // Add new corporate contributors if any are selected
            if (isset($_POST['corporate_contributors']) && !empty($_POST['corporate_contributors'])) {
                foreach ($book_ids as $book) {
                    foreach ($_POST['corporate_contributors'] as $corporate) {
                        // Parse the JSON string if it's a string, otherwise use as is
                        $corporate_data = is_string($corporate) ? json_decode($corporate, true) : $corporate;
                        
                        if (!$corporate_data || !isset($corporate_data['corporate_id']) || !isset($corporate_data['role'])) {
                            continue; // Skip invalid data
                        }

                        $insert_corp = "INSERT INTO corporate_contributors (book_id, corporate_id, role) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($insert_corp);
                        $stmt->bind_param('iis', $book['id'], $corporate_data['corporate_id'], $corporate_data['role']);
                        $stmt->execute();
                    }
                }
            }

            // 4. Update individual copy details
            foreach ($_POST['copies'] as $book_id => $copy_data) {
                $update_copy = "UPDATE books SET 
                               accession = ?,
                               copy_number = ?, 
                               edition = ?,
                               volume = ?,
                               part = ?,
                               series = ?,
                               shelf_location = ?,
                               call_number = ?,
                               status = ?
                               WHERE id = ?";
                
                $stmt = $conn->prepare($update_copy);
                $stmt->bind_param('sisssssssi', 
                    $copy_data['accession'],
                    $copy_data['copy_number'],
                    $copy_data['edition'],
                    $copy_data['volume'],
                    $copy_data['part'],
                    $copy_data['series'],
                    $copy_data['shelf_location'],
                    $copy_data['call_number'],
                    $copy_data['status'],
                    $book_id
                );
                $stmt->execute();
            }

            // Update publication details
            if (isset($_POST['publisher_id'])) {
                $update_publication = "UPDATE publications SET 
                                     publisher_id = ?,
                                     publish_date = ?
                                     WHERE book_id IN (SELECT id FROM books WHERE accession IN ($accession_list))";
                
                $stmt = $conn->prepare($update_publication);
                $stmt->bind_param('is', $_POST['publisher_id'], $_POST['publish_date']);
                $stmt->execute();
            }

            // Commit transaction if everything succeeded
            $conn->commit();
            $_SESSION['success_message'] = "Books updated successfully!";
            header("Location: book_list.php");
            exit();

        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_message = "Error updating books: " . $e->getMessage();
            // Log the error for debugging
            error_log("Update books error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Books</title>
    <?php include 'inc/header.php'; ?>
    <style>
        .copy-details {
            background-color: #f8f9fc;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.35rem;
            border-left: 4px solid #4e73df;
        }
        
        .contributors-section {
            background-color: #f8f9fc;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 0.35rem;
            border-left: 4px solid #4e73df;
        }
        
        .contributors-section h5 {
            color: #4e73df;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .contributors-container {
            max-height: none;
            overflow-y: visible;
        }
        
        .contributor-entry, .corporate-contributor-entry {
            background-color: white;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .remove-contributor {
            color: #e74a3b;
            cursor: pointer;
        }
        
        .nav-tabs .nav-link.active {
            background-color: #4e73df;
            color: white;
        }

        .contributor-card {
            background: #f8f9fc;
            border-left: 4px solid #4e73df;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 0.35rem;
            position: relative;
        }
        .contributor-card .remove-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            cursor: pointer;
            color: #e74a3b;
        }
        .contributor-role {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.25rem;
            background-color: #4e73df;
            color: white;
            margin-left: 0.5rem;
        }
        
        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #e74a3b;
        }
        
        .is-invalid {
            border-color: #e74a3b !important;
            padding-right: calc(1.5em + 0.75rem) !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23e74a3b'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23e74a3b' stroke='none'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right calc(0.375em + 0.1875rem) center !important;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
        }
        
        .is-invalid + .invalid-feedback,
        .is-invalid ~ .invalid-feedback {
            display: block;
        }
        
        /* File input styling */
        input[type="file"].form-control {
            padding: 0;
            padding-right: 0.75rem;
            padding-bottom: 0.75rem;
            padding-left: 0.75rem;
            background-color: #f8f9fc;
            border: 1px solid #d1d3e2;
            border-radius: 0.35rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        input[type="file"].form-control:hover {
            background-color: #eaecf4;
            cursor: pointer;
        }

        input[type="file"].form-control:focus {
            background-color: #fff;
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        /* Enhance the file input button */
        input[type="file"].form-control::file-selector-button {
            padding: 0.375rem 0.75rem;
            margin: 0;
            margin-right: 1rem;
            margin-left: -0.75rem;
            background-color: #e9ecef;  /* Changed to light grey */
            color: #333;  /* Darkened text color for better contrast */
            border: 0;
            border-radius: 0.25rem 0 0 0.25rem;
            transition: background-color 0.15s ease-in-out;
        }

        input[type="file"].form-control::file-selector-button:hover {
            background-color: #dde0e3;  /* Slightly darker on hover */
        }

        /* Image preview styling */
        .image-preview-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }
        
        .image-preview-container img {
            max-height: 200px;
            object-fit: contain;
        }

        /* Fix contributor cards alignment */
        #selectedWriterCards, #selectedCorporateCards {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            width: 100%;
        }

        .writer-entry, .corporate-entry {
            width: calc(50% - 0.5rem);
            margin-bottom: 0;
        }

        .contributor-card {
            height: 100%;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            padding-right: 2rem;
        }

        /* Add remove image button styling */
        .image-container {
            position: relative;
        }
        
        .remove-image {
            position: absolute;
            top: 0;
            right: 0;
            background: #e74a3b;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0 0 0 0.35rem;
            cursor: pointer;
            z-index: 1;
        }
        
        .remove-image:hover {
            background: #d52a1a;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Update Books</h1>
        
        <?php if (!empty($error_messages)): ?>
            <div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">Form submission failed!</h4>
                <p>The following errors were found:</p>
                <ul>
                    <?php foreach ($error_messages as $msg): ?>
                        <li><?php echo $msg; ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="mb-0">Please correct these issues and try again.</p>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST" id="updateBooksForm" enctype="multipart/form-data">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#title">Title Proper</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#access">Access Point</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#abstract">Abstract & Notes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#description">Description</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#local">Local Information</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#contributors">Publication & Contributors</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#copies">Individual Copies</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#images">Cover Images</a>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Title Proper Tab -->
                        <div class="tab-pane fade show active" id="title">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Title:</label>
                                        <input type="text" class="form-control" name="title" value="<?php echo $books[0]['title']; ?>" required>
                                        <small class="form-text text-muted">Main title of the resource as it appears on the source</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Preferred Title:</label>
                                        <input type="text" class="form-control" name="preferred_title" value="<?php echo $books[0]['preferred_title']; ?>">
                                        <small class="form-text text-muted">Standardized form of the title used for cataloging</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Parallel Title:</label>
                                        <input type="text" class="form-control" name="parallel_title" value="<?php echo $books[0]['parallel_title']; ?>">
                                        <small class="form-text text-muted">Title in another language or script</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Access Point Tab -->
                        <div class="tab-pane fade" id="access">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Subject Category:</label>
                                        <input type="text" class="form-control" name="subject_category" value="<?php echo $books[0]['subject_category']; ?>">
                                        <small class="form-text text-muted">Topical, Personal, Corporate, Geographical</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Program:</label>
                                        <input type="text" class="form-control" name="program" value="<?php echo $books[0]['program']; ?>">
                                        <small class="form-text text-muted">Computer Science, Accountancy, Accountancy Information System, Entrepreneurship, Tourism Management</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Subject Details:</label>
                                <textarea class="form-control" name="subject_detail" rows="3"><?php echo $books[0]['subject_detail']; ?></textarea>
                                <small class="form-text text-muted">Detailed topics, themes, or subjects covered in the resource</small>
                            </div>
                        </div>

                        <!-- Abstract & Notes Tab -->
                        <div class="tab-pane fade" id="abstract">
                            <div class="form-group mb-3">
                                <label>Summary:</label>
                                <textarea class="form-control" name="summary" rows="4"><?php echo $books[0]['summary']; ?></textarea>
                                <small class="form-text text-muted">Brief description of the main points or content of the resource</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label>Contents:</label>
                                <textarea class="form-control" name="contents" rows="4"><?php echo $books[0]['contents']; ?></textarea>
                                <small class="form-text text-muted">List of chapters, sections, or other parts of the resource</small>
                            </div>
                        </div>

                        <!-- Description Tab -->
                        <div class="tab-pane fade" id="description">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Dimension:</label>
                                        <input type="text" class="form-control" name="dimension" value="<?php echo $books[0]['dimension']; ?>">
                                        <small class="form-text text-muted">Physical dimensions of the resource (include unit: cm, mm, inches)</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Extent of Text and Illustrations:</label>
                                        <input type="text" class="form-control" name="total_pages" value="<?php echo $books[0]['total_pages']; ?>">
                                        <small class="form-text text-muted">Format as: preliminary pages + main text (e.g., "xiii, 256p." or "xii, 345p. : ill.")</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Supplementary Contents:</label>
                                        <input type="text" class="form-control" name="supplementary_contents" value="<?php echo $books[0]['supplementary_contents']; ?>">
                                        <small class="form-text text-muted">Includes: Appendix (app.), Bibliography (bibl.), Glossary (gloss.), Index (ind.), Maps, Tables (tbl.)</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Local Information Tab -->
                        <div class="tab-pane fade" id="local">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ISBN:</label>
                                        <input type="text" class="form-control" name="ISBN" value="<?php echo $books[0]['ISBN']; ?>">
                                        <small class="form-text text-muted">Example: 978-0-545-01022-1</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>URL:</label>
                                        <input type="text" class="form-control" name="URL" value="<?php echo $books[0]['URL']; ?>">
                                        <small class="form-text text-muted">Optional: Link to digital version if available</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Content Type:</label>
                                        <select class="form-control" name="content_type">
                                            <option value="Text" <?php echo ($books[0]['content_type'] == 'Text') ? 'selected' : ''; ?>>Text</option>
                                            <option value="Image" <?php echo ($books[0]['content_type'] == 'Image') ? 'selected' : ''; ?>>Image</option>
                                            <option value="Audio" <?php echo ($books[0]['content_type'] == 'Audio') ? 'selected' : ''; ?>>Audio</option>
                                            <option value="Video" <?php echo ($books[0]['content_type'] == 'Video') ? 'selected' : ''; ?>>Video</option>
                                            <option value="Multimedia" <?php echo ($books[0]['content_type'] == 'Multimedia') ? 'selected' : ''; ?>>Multimedia</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Media Type:</label>
                                        <select class="form-control" name="media_type">
                                            <option value="Print" <?php echo ($books[0]['media_type'] == 'Print') ? 'selected' : ''; ?>>Print</option>
                                            <option value="Digital" <?php echo ($books[0]['media_type'] == 'Digital') ? 'selected' : ''; ?>>Digital</option>
                                            <option value="Electronic" <?php echo ($books[0]['media_type'] == 'Electronic') ? 'selected' : ''; ?>>Electronic</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Carrier Type:</label>
                                        <select class="form-control" name="carrier_type">
                                            <option value="Book" <?php echo ($books[0]['carrier_type'] == 'Book') ? 'selected' : ''; ?>>Book</option>
                                            <option value="CD" <?php echo ($books[0]['carrier_type'] == 'CD') ? 'selected' : ''; ?>>CD</option>
                                            <option value="DVD" <?php echo ($books[0]['carrier_type'] == 'DVD') ? 'selected' : ''; ?>>DVD</option>
                                            <option value="Online Resource" <?php echo ($books[0]['carrier_type'] == 'Online Resource') ? 'selected' : ''; ?>>Online Resource</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Language:</label>
                                        <select class="form-control" name="language">
                                            <option value="English" <?php echo ($books[0]['language'] == 'English') ? 'selected' : ''; ?>>English</option>
                                            <option value="Filipino" <?php echo ($books[0]['language'] == 'Filipino') ? 'selected' : ''; ?>>Filipino</option>
                                            <option value="Multilingual" <?php echo ($books[0]['language'] == 'Multilingual') ? 'selected' : ''; ?>>Multilingual</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contributors Tab -->
                        <div class="tab-pane fade" id="contributors">
                            <!-- Publication Details Section -->
                            <div class="contributors-section">
                                <h5><i class="fas fa-book"></i> Publication Details</h5>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group mb-3">
                                            <label>Publisher:</label>
                                            <select name="publisher_id" class="form-control">
                                                <option value="">Select Publisher</option>
                                                <?php foreach ($publishers as $publisher): ?>
                                                    <option value="<?php echo $publisher['id']; ?>" 
                                                        <?php echo ($publisher['id'] == $books[0]['publisher_id']) ? 'selected' : ''; ?>>
                                                        <?php echo $publisher['publisher'] . ' (' . $publisher['place'] . ')'; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted">Select the publisher of this resource</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label>Publication Year:</label>
                                            <input type="number" class="form-control" name="publish_date" 
                                                   value="<?php echo $books[0]['publish_date']; ?>" 
                                                   min="1900" max="<?php echo date('Y'); ?>">
                                            <small class="form-text text-muted">Enter 4-digit year of publication</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Writer Contributors Section -->
                                <div class="col-md-6">
                                    <div class="contributors-section">
                                        <h5><i class="fas fa-user-edit"></i> Writer Contributors</h5>
                                        <div class="mb-3">
                                            <div class="form-group">
                                                <label>Select Writer:</label>
                                                <select id="writerSelect" class="form-control">
                                                    <option value="">Select Writer</option>
                                                    <?php foreach ($writers as $writer): ?>
                                                        <?php 
                                                        $fullName = trim($writer['lastname'] . ', ' . $writer['firstname'] . ' ' . $writer['middle_init']); 
                                                        ?>
                                                        <option value="<?php echo $writer['id']; ?>"><?php echo $fullName; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-group">
                                                <label>Role:</label>
                                                <div class="input-group">
                                                    <select id="writerRoleSelect" class="form-control">
                                                        <option value="">Select Role</option>
                                                        <option value="Author">Author</option>
                                                        <option value="Co-Author">Co-Author</option>
                                                        <option value="Editor">Editor</option>
                                                        <option value="Translator">Translator</option>
                                                        <option value="Illustrator">Illustrator</option>
                                                    </select>
                                                    <button type="button" id="addSelectedWriter" class="btn btn-primary">
                                                        <i class="fas fa-plus"></i> Add
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="selectedWriterCards">
                                            <?php foreach ($contributors as $contributor): ?>
                                                <?php 
                                                $fullName = trim($contributor['lastname'] . ', ' . $contributor['firstname'] . ' ' . $contributor['middle_init']); 
                                                ?>
                                                <div class="writer-entry" data-writer-id="<?php echo $contributor['writer_id']; ?>">
                                                    <div class="contributor-card">
                                                        <i class="fas fa-times remove-btn"></i>
                                                        <i class="fas fa-user-edit me-2"></i>
                                                        <span class="contributor-name"><?php echo $fullName; ?></span>
                                                        <span class="contributor-role"><?php echo $contributor['role']; ?></span>
                                                        <input type="hidden" name="contributors[]" value='{"writer_id":"<?php echo $contributor['writer_id']; ?>","role":"<?php echo $contributor['role']; ?>"}'>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Corporate Contributors Section -->
                                <div class="col-md-6">
                                    <div class="contributors-section">
                                        <h5><i class="fas fa-building"></i> Corporate Contributors</h5>
                                        <div class="mb-3">
                                            <div class="form-group">
                                                <label>Select Corporate Entity:</label>
                                                <select id="corporateSelect" class="form-control">
                                                    <option value="">Select Corporate Entity</option>
                                                    <?php foreach ($corporates as $corporate): ?>
                                                        <option value="<?php echo $corporate['id']; ?>"><?php echo $corporate['name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-group">
                                                <label>Role:</label>
                                                <div class="input-group">
                                                    <select id="corporateRoleSelect" class="form-control">
                                                        <option value="">Select Role</option>
                                                        <option value="Corporate Author">Corporate Author</option>
                                                        <option value="Corporate Contributor">Corporate Contributor</option>
                                                        <option value="Publisher">Publisher</option>
                                                        <option value="Distributor">Distributor</option>
                                                        <option value="Sponsor">Sponsor</option>
                                                        <option value="Funding Body">Funding Body</option>
                                                        <option value="Research Institution">Research Institution</option>
                                                    </select>
                                                    <button type="button" id="addSelectedCorporate" class="btn btn-primary">
                                                        <i class="fas fa-plus"></i> Add
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="selectedCorporateCards">
                                            <?php foreach ($corporate_contributors as $corporate): ?>
                                                <div class="corporate-entry" data-corporate-id="<?php echo $corporate['corporate_id']; ?>">
                                                    <div class="contributor-card">
                                                        <i class="fas fa-times remove-btn"></i>
                                                        <i class="fas fa-building me-2"></i>
                                                        <span class="contributor-name"><?php echo $corporate['name']; ?></span>
                                                        <span class="contributor-role"><?php echo $corporate['role']; ?></span>
                                                        <input type="hidden" name="corporate_contributors[]" value='{"corporate_id":"<?php echo $corporate['corporate_id']; ?>","role":"<?php echo $corporate['role']; ?>"}'>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Individual Copies Tab -->
                        <div class="tab-pane fade" id="copies">
                            <?php foreach ($books as $book): ?>
                                <div class="copy-details">
                                    <h5>Copy #<?php echo $book['copy_number']; ?> (Accession: <?php echo $book['accession']; ?>)</h5>
                                    <!-- Row 1: Accession, Copy Number, Edition -->
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Accession Number:</label>
                                                <input 
                                                    type="text" 
                                                    class="form-control accession-field <?php echo isset($duplicateAccessions[$book['id']]) ? 'is-invalid' : ''; ?>" 
                                                    name="copies[<?php echo $book['id']; ?>][accession]" 
                                                    data-book-id="<?php echo $book['id']; ?>"
                                                    data-original="<?php echo $book['accession']; ?>"
                                                    value="<?php echo isset($_POST['copies'][$book['id']]['accession']) ? $_POST['copies'][$book['id']]['accession'] : $book['accession']; ?>"
                                                >
                                                <div class="invalid-feedback">
                                                    <?php 
                                                        if (isset($duplicateAccessions[$book['id']])) {
                                                            echo "This accession number already exists in another book: " . $duplicateAccessions[$book['id']]['duplicate_title'];
                                                        } else {
                                                            echo "This accession number already exists in another book.";
                                                        }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Copy Number:</label>
                                                <input type="number" class="form-control" name="copies[<?php echo $book['id']; ?>][copy_number]" 
                                                       value="<?php echo $book['copy_number']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Edition:</label>
                                                <input type="text" class="form-control" name="copies[<?php echo $book['id']; ?>][edition]" 
                                                       value="<?php echo $book['edition']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Row 2: Series, Volume, Part -->
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Series:</label>
                                                <input type="text" class="form-control" name="copies[<?php echo $book['id']; ?>][series]" 
                                                       value="<?php echo $book['series']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Volume:</label>
                                                <input type="text" class="form-control" name="copies[<?php echo $book['id']; ?>][volume]" 
                                                       value="<?php echo $book['volume']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Part:</label>
                                                <input type="text" class="form-control" name="copies[<?php echo $book['id']; ?>][part]" 
                                                       value="<?php echo $book['part']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Row 3: Shelf Location, Call Number, Status -->
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Shelf Location:</label>
                                                <select class="form-control" name="copies[<?php echo $book['id']; ?>][shelf_location]">
                                                    <?php
                                                    $locations = ['TH' => 'Thesis', 
                                                                'FIL' => 'Filipiniana', 
                                                                'CIR' => 'Circulation', 'REF' => 'Reference', 
                                                                'SC' => 'Special Collection', 'BIO' => 'Biography',
                                                                'RES' => 'Reserve', 'FIC' => 'Fiction'];
                                                    foreach ($locations as $code => $name) {
                                                        $selected = ($code == $book['shelf_location']) ? 'selected' : '';
                                                        echo "<option value='$code' $selected>$name</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Call Number:</label>
                                                <input type="text" class="form-control" name="copies[<?php echo $book['id']; ?>][call_number]" 
                                                       value="<?php echo $book['call_number']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Status:</label>
                                                <select class="form-control" name="copies[<?php echo $book['id']; ?>][status]">
                                                    <?php
                                                    $statuses = ['Available', 'Borrowed', 'Reserved', 'Lost', 'Damaged', 'Under Repair'];
                                                    foreach ($statuses as $status) {
                                                        $selected = ($status == $book['status']) ? 'selected' : '';
                                                        echo "<option value='$status' $selected>$status</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Cover Images Tab -->
                        <div class="tab-pane fade" id="images">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Front Cover:</label>
                                        <?php if (!empty($books[0]['front_image'])): ?>
                                            <div class="mb-2 image-preview-container image-container">
                                                <div class="remove-image" data-type="front"><i class="fas fa-times"></i> Remove</div>
                                                <img src="<?php echo $books[0]['front_image']; ?>" alt="Front Cover" class="img-thumbnail">
                                                <input type="hidden" name="remove_front_image" value="0">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" name="front_image" accept="image/*">
                                        <small class="form-text text-muted">Upload new image to replace existing front cover</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Back Cover:</label>
                                        <?php if (!empty($books[0]['back_image'])): ?>
                                            <div class="mb-2 image-preview-container image-container">
                                                <div class="remove-image" data-type="back"><i class="fas fa-times"></i> Remove</div>
                                                <img src="<?php echo $books[0]['back_image']; ?>" alt="Back Cover" class="img-thumbnail">
                                                <input type="hidden" name="remove_back_image" value="0">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" name="back_image" accept="image/*">
                                        <small class="form-text text-muted">Upload new image to replace existing back cover</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update Books</button>
                    <a href="book_list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            // Add image removal handling
            $('.remove-image').click(function() {
                const type = $(this).data('type');
                const container = $(this).closest('.image-container');
                
                // Set the hidden input value to 1 to indicate removal
                $(`input[name="remove_${type}_image"]`).val(1);
                
                // Hide the image container
                container.slideUp(300);
                
                // Show confirmation
                Swal.fire({
                    icon: 'success',
                    title: 'Image Removal',
                    text: `The ${type} cover image will be removed when you save the changes.`,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            });

            // Modify the writer card template
            $('#addSelectedWriter').click(function() {
                const writerId = $('#writerSelect').val();
                const writerName = $('#writerSelect option:selected').text();
                const role = $('#writerRoleSelect').val();
                
                if (!writerId || !role) {
                    alert('Please select both a writer and a role');
                    return;
                }
                
                // Create writer card
                const writerCard = `
                    <div class="writer-entry" data-writer-id="${writerId}">
                        <div class="contributor-card">
                            <i class="fas fa-times remove-btn"></i>
                            <i class="fas fa-user-edit me-2"></i>
                            <span class="contributor-name">${writerName}</span>
                            <span class="contributor-role">${role}</span>
                            <input type="hidden" name="contributors[]" value='{"writer_id":"${writerId}","role":"${role}"}'>
                        </div>
                    </div>
                `;
                
                $('#selectedWriterCards').append(writerCard);
                
                // Reset selections
                $('#writerSelect').val('').trigger('change');
                $('#writerRoleSelect').val('');
            });

            // Modify the corporate card template
            $('#addSelectedCorporate').click(function() {
                const corporateId = $('#corporateSelect').val();
                const corporateName = $('#corporateSelect option:selected').text();
                const role = $('#corporateRoleSelect').val();
                
                if (!corporateId || !role) {
                    alert('Please select both a corporate entity and a role');
                    return;
                }
                
                // Create corporate card
                const corporateCard = `
                    <div class="corporate-entry" data-corporate-id="${corporateId}">
                        <div class="contributor-card">
                            <i class="fas fa-times remove-btn"></i>
                            <i class="fas fa-building me-2"></i>
                            <span class="contributor-name">${corporateName}</span>
                            <span class="contributor-role">${role}</span>
                            <input type="hidden" name="corporate_contributors[]" value='{"corporate_id":"${corporateId}","role":"${role}"}'>
                        </div>
                    </div>
                `;
                
                $('#selectedCorporateCards').append(corporateCard);
                
                // Reset selections
                $('#corporateSelect').val('').trigger('change');
                $('#corporateRoleSelect').val('');
            });

            // Update remove contributor handler
            $(document).on('click', '.remove-btn', function() {
                $(this).closest('.writer-entry, .corporate-entry').remove();
            });

            // Initialize select2 with search
            $('#writerSelect, #corporateSelect').select2({
                placeholder: "Search and select...",
                allowClear: true
            });
            
            // Object to track validation errors
            let accessionErrors = {};
            
            // Function to check for duplicate accession number
            function checkDuplicateAccession(input) {
                const accessionField = $(input);
                const bookId = accessionField.data('book-id');
                const accession = accessionField.val().trim();
                const originalAccession = accessionField.data('original');
                
                // Skip validation if the accession hasn't changed
                if (accession === originalAccession) {
                    accessionField.removeClass('is-invalid');
                    delete accessionErrors[bookId];
                    updateSubmitButton();
                    return;
                }
                
                // Skip validation if the field is empty
                if (!accession) {
                    accessionField.removeClass('is-invalid');
                    delete accessionErrors[bookId];
                    updateSubmitButton();
                    return;
                }
                
                // Make AJAX request to check for duplication
                $.ajax({
                    url: 'check_accession.php',
                    method: 'POST',
                    data: {
                        accession: accession,
                        book_id: bookId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.exists) {
                            accessionField.addClass('is-invalid');
                            accessionField.next('.invalid-feedback').text('This accession number already exists in another book.');
                            accessionErrors[bookId] = true;
                        } else {
                            accessionField.removeClass('is-invalid');
                            delete accessionErrors[bookId];
                        }
                        updateSubmitButton();
                    },
                    error: function() {
                        // If the request fails, don't block submission
                        accessionField.removeClass('is-invalid');
                        delete accessionErrors[bookId];
                        updateSubmitButton();
                    }
                });
            }
            
            // Update the submit button state based on validation errors
            function updateSubmitButton() {
                if (Object.keys(accessionErrors).length > 0) {
                    $('#updateBooksForm button[type="submit"]').prop('disabled', true);
                } else {
                    $('#updateBooksForm button[type="submit"]').prop('disabled', false);
                }
            }
            
            // Add validation on input change with debounce
            let debounceTimer;
            $('.accession-field').on('input', function() {
                const input = this;
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    checkDuplicateAccession(input);
                }, 300); // 300ms delay
            });
            
            // Also validate on blur
            $('.accession-field').on('blur', function() {
                clearTimeout(debounceTimer);
                checkDuplicateAccession(this);
            });
            
            // Validate all fields on form submission
            $('#updateBooksForm').on('submit', function(e) {
                // Check all accession fields for duplicates
                $('.accession-field').each(function() {
                    checkDuplicateAccession(this);
                });
                
                // Prevent form submission if there are errors
                if (Object.keys(accessionErrors).length > 0) {
                    e.preventDefault();
                    
                    // Switch to the copies tab
                    $('a[href="#copies"]').tab('show');
                    
                    // Scroll to the first invalid field
                    setTimeout(function() {
                        const firstError = $('.is-invalid').first();
                        if (firstError.length) {
                            $('html, body').animate({
                                scrollTop: firstError.offset().top - 100
                            }, 200);
                            firstError.focus();
                        }
                    }, 200);
                    
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'There are duplicate accession numbers. Please fix the highlighted fields.'
                    });
                }
            });
            
            // Check for existing errors on page load
            $('.accession-field').each(function() {
                if ($(this).hasClass('is-invalid')) {
                    const bookId = $(this).data('book-id');
                    accessionErrors[bookId] = true;
                }
            });
            updateSubmitButton();
        });
    </script>
</body>
</html>
