<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Set the flag indicating we're on the form page
$_SESSION['return_to_form'] = true;

// Place database connection and form processing before including header
include '../db.php';

// Add transaction support check
$transactionSupported = true;
try {
    mysqli_begin_transaction($conn);
    mysqli_rollback($conn);
} catch (Exception $e) {
    $transactionSupported = false;
}

// Helper function to calculate accession number with increment
function calculateAccession($baseAccession, $increment) {
    if (!$baseAccession) return '';

    // Handle formats like "0001"
    $match = preg_match('/^(.*?)(\d+)$/', $baseAccession, $matches);
    if (!$match) return $baseAccession;
    
    $prefix = $matches[1]; // Everything before the number
    $num = intval($matches[2]); // The number part
    $width = strlen($matches[2]); // Original width of the number
    
    // Calculate new number and pad with zeros to maintain original width
    $newNum = ($num + $increment);
    $newNumStr = str_pad($newNum, $width, '0', STR_PAD_LEFT);
    
    return $prefix . $newNumStr;
}

// Handle form submission first, before any HTML output
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($transactionSupported) {
        mysqli_begin_transaction($conn);
    }

    try {
        $accessions = $_POST['accession'];
        $number_of_copies_array = $_POST['number_of_copies'];
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $preferred_title = mysqli_real_escape_string($conn, $_POST['preferred_title']);
        $parallel_title = mysqli_real_escape_string($conn, $_POST['parallel_title']);
        $summary = mysqli_real_escape_string($conn, $_POST['abstract']);
        $contents = mysqli_real_escape_string($conn, $_POST['notes']);

        // Process author, co-authors, editors, illustrators, translators, and corporate contributors
        $author_id = mysqli_real_escape_string($conn, $_POST['author']);
        $authors_ids = isset($_POST['authors']) ? $_POST['authors'] : [];
        $co_authors_ids = isset($_POST['co_authors']) ? $_POST['co_authors'] : [];
        $editors_ids = isset($_POST['editors']) ? $_POST['editors'] : [];
        $illustrators_ids = isset($_POST['illustrators']) ? $_POST['illustrators'] : [];
        $translators_ids = isset($_POST['translators']) ? $_POST['translators'] : [];
        $corporate_contributors_ids = isset($_POST['corporate_contributors']) ? $_POST['corporate_contributors'] : [];
        $corporate_roles = isset($_POST['corporate_roles']) ? $_POST['corporate_roles'] : [];

        // Process publisher and publication date
        $publisher_id = $_SESSION['book_shortcut']['publisher_id'];
        $publish_year = $_SESSION['book_shortcut']['publish_year'];

        if (!$publisher_id) {
            throw new Exception("Publisher ID is not set. Please select a publisher.");
        }

        // Simply escape the dimension field without auto-formatting
        $dimension = mysqli_real_escape_string($conn, $_POST['dimension'] ?? '');

        // Add missing variable declarations - replace the existing combined field with a direct reference
        $total_pages = mysqli_real_escape_string($conn, $_POST['total_pages'] ?? '');
        // Update supplementary_contents to handle a single string rather than an array
        $supplementary_contents = mysqli_real_escape_string($conn, $_POST['supplementary_content'] ?? '');
        $content_type = mysqli_real_escape_string($conn, $_POST['content_type'] ?? 'Text');
        $media_type = mysqli_real_escape_string($conn, $_POST['media_type'] ?? 'Print');
        $carrier_type = mysqli_real_escape_string($conn, $_POST['carrier_type'] ?? 'Book');
        $url = mysqli_real_escape_string($conn, $_POST['url'] ?? '');
        $language = mysqli_real_escape_string($conn, $_POST['language'] ?? 'English');
        $entered_by = $_SESSION['admin_employee_id'];
        $date_added = date('Y-m-d');
        $status = 'Available';
        $updated_by = $_SESSION['admin_employee_id'];
        $last_update = date('Y-m-d');

        // Process each accession group
        $front_image = '';
        $back_image = '';
        $first_book_id = null;
        $imageFolder = '../Images/book-image/';
        $all_book_ids = [];

        // Ensure the folder exists
        if (!is_dir($imageFolder)) {
            mkdir($imageFolder, 0777, true);
        }

        // Process book insertions first to get all book IDs
        $success_count = 0;
        $error_messages = array();
        $call_number_index = 0;

        // Process each accession group
        for ($i = 0; $i < count($accessions); $i++) {
            $base_accession = $accessions[$i];
            $copies_for_this_accession = (int)$number_of_copies_array[$i];
            $current_series = mysqli_real_escape_string($conn, $_POST['series'][$i]);
            $current_volume = mysqli_real_escape_string($conn, $_POST['volume'][$i]);
            $current_part = mysqli_real_escape_string($conn, $_POST['part'][$i]); // Add part field
            $current_edition = mysqli_real_escape_string($conn, $_POST['edition'][$i]);
            $current_isbn = isset($_POST['isbn'][$i]) ? mysqli_real_escape_string($conn, $_POST['isbn'][$i]) : '';

            for ($j = 0; $j < $copies_for_this_accession; $j++) {
                // Fix: Use calculateAccession function to preserve leading zeroes
                $current_accession = calculateAccession($base_accession, $j);
                
                $current_call_number = isset($_POST['call_number'][$call_number_index]) ?
                    mysqli_real_escape_string($conn, $_POST['call_number'][$call_number_index]) : '';
                $current_shelf_location = isset($_POST['shelf_locations'][$call_number_index]) ?
                    mysqli_real_escape_string($conn, $_POST['shelf_locations'][$call_number_index]) : '';
                
                // Fix: Read from 'copy_number' array generated by JS. Fallback to loop index + 1.
                $current_copy_number = isset($_POST['copy_number'][$call_number_index]) ?
                    mysqli_real_escape_string($conn, $_POST['copy_number'][$call_number_index]) : ($j + 1); 
                
                $call_number_index++;

                // Format the call number - include volume information if present
                if (!empty($current_volume)) {
                    // With volume, format: [shelf_location] [call_number] vol[volume_number] c[copy_number]
                    // Build formatted call number with parts
                    $formatted_call_number = $current_shelf_location . ' ' . $current_call_number . ' vol.' . $current_volume;

                    // Add part if present
                    if (!empty($current_part)) {
                        $formatted_call_number .= ' pt.' . $current_part;
                    }

                    // Add copy number
                    $formatted_call_number .= ' c.' . $current_copy_number;
                } else {
                    // Without volume, use standard format: [shelf_location] [call_number] c[copy_number]
                    $formatted_call_number = $current_shelf_location . ' ' . $current_call_number . ' c.' . $current_copy_number;
                }

                // Check for duplicate accession
                $check_query = "SELECT * FROM books WHERE accession = '$current_accession'";
                $result = mysqli_query($conn, $check_query);

                if (mysqli_num_rows($result) > 0) {
                    $error_messages[] = "Accession number $current_accession already exists - skipping.";
                    continue;
                }

                // Process subject entries for this copy
                $subject_categories = isset($_POST['subject_categories']) ? $_POST['subject_categories'] : array();
                $subject_paragraphs = isset($_POST['subject_paragraphs']) ? $_POST['subject_paragraphs'] : array();
                $programs = isset($_POST['program']) ? $_POST['program'] : array();

                // Combine all subject entries into strings for storage
                $all_categories = array();
                $all_details = array();
                $all_programs = array();

                for ($k = 0; $k < count($subject_categories); $k++) {
                    if (!empty($subject_categories[$k])) {
                        $all_categories[] = mysqli_real_escape_string($conn, $subject_categories[$k]);
                        $all_details[] = mysqli_real_escape_string($conn, $subject_paragraphs[$k]);
                        $all_programs[] = mysqli_real_escape_string($conn, $programs[$k]);
                    }
                }

                $subject_category = implode('; ', $all_categories);
                $subject_detail = implode('; ', $all_details);
                $program = implode('; ', $all_programs);

                $query = "INSERT INTO books (
                    accession, title, preferred_title, parallel_title,
                    subject_category, subject_detail, program,
                    summary, contents, front_image, back_image,
                    dimension, series, volume, part, edition,
                    copy_number, total_pages, supplementary_contents, ISBN, content_type,
                    media_type, carrier_type, call_number, URL,
                    language, shelf_location, entered_by, date_added,
                    status, updated_by, last_update
                ) VALUES (
                    '$current_accession', '$title', '$preferred_title', '$parallel_title',
                    '$subject_category', '$subject_detail', '$program',
                    '$summary', '$contents', '$front_image', '$back_image',
                    '$dimension', '$current_series', '$current_volume', '$current_part', '$current_edition',
                    '$current_copy_number', '$total_pages', '$supplementary_contents', '$current_isbn', '$content_type',
                    '$media_type', '$carrier_type', '$formatted_call_number', '$url',
                    '$language', '$current_shelf_location', '$entered_by', '$date_added',
                    '$status', '$updated_by', '$last_update'
                )";

                // Store the first book ID when inserting books
                if (!mysqli_query($conn, $query)) {
                    throw new Exception("Error inserting book data: " . mysqli_error($conn));
                }
                
                $book_id = mysqli_insert_id($conn);
                if ($first_book_id === null) {
                    $first_book_id = $book_id;
                }
                $all_book_ids[] = $book_id;

                // Insert into publications table using publisher id and publish year from session
                $pubQuery = "INSERT INTO publications (book_id, publisher_id, publish_date) VALUES ('$book_id', '$publisher_id', '$publish_year')";
                if (!mysqli_query($conn, $pubQuery)) {
                    $error_messages[] = "Error adding publication for book with accession $current_accession: " . mysqli_error($conn);
                }

                // Insert contributors in batches
                $contributors = [];

                // Add authors
                foreach ($authors_ids as $author_id) {
                    $contributors[] = "('$book_id', '$author_id', 'Author')";
                }

                // Add co-authors
                foreach ($co_authors_ids as $co_author_id) {
                    $contributors[] = "('$book_id', '$co_author_id', 'Co-Author')";
                }

                // Add editors
                foreach ($editors_ids as $editor_id) {
                    $contributors[] = "('$book_id', '$editor_id', 'Editor')";
                }

                // Add illustrators
                foreach ($illustrators_ids as $illustrator_id) {
                    $contributors[] = "('$book_id', '$illustrator_id', 'Illustrator')";
                }

                // Add translators
                foreach ($translators_ids as $translator_id) {
                    $contributors[] = "('$book_id', '$translator_id', 'Translator')";
                }

                // Insert all contributors in one query
                if (!empty($contributors)) {
                    $contributor_query = "INSERT INTO contributors (book_id, writer_id, role) VALUES " . implode(', ', $contributors);
                    if (!mysqli_query($conn, $contributor_query)) {
                        $error_messages[] = "Error adding contributors for book with accession $current_accession: " . mysqli_error($conn);
                    }
                }

                // Add corporate contributors - Fix how corporate contributors are processed
                if (!empty($corporate_contributors_ids)) {
                    for ($k = 0; $k < count($corporate_contributors_ids); $k++) {
                        $corporate_id = mysqli_real_escape_string($conn, $corporate_contributors_ids[$k]);
                        
                        // Get the role directly from the session data using corporate id as a key
                        $role = 'Corporate Contributor'; // Default role
                        
                        // Find the role in session data
                        if (isset($_SESSION['book_shortcut']['selected_corporates'])) {
                            foreach ($_SESSION['book_shortcut']['selected_corporates'] as $selected_corporate) {
                                if ($selected_corporate['id'] == $corporate_id) {
                                    $role = $selected_corporate['role'];
                                    break;
                                }
                            }
                        }
                        
                        $corpQuery = "INSERT INTO corporate_contributors (book_id, corporate_id, role) VALUES ('$book_id', '$corporate_id', '$role')";
                        if (!mysqli_query($conn, $corpQuery)) {
                            throw new Exception("Error adding corporate contributor: " . mysqli_error($conn));
                        }
                    }
                }

                $success_count++;
            }
        }

        // After getting first_book_id, process images
        if ($first_book_id) {
            // Process front image
            if (isset($_FILES['front_image']) && $_FILES['front_image']['error'] === UPLOAD_ERR_OK) {
                $extension = strtolower(pathinfo($_FILES['front_image']['name'], PATHINFO_EXTENSION));
                $frontImageName = $first_book_id . '_front.' . $extension;
                $frontImagePath = $imageFolder . $frontImageName;

                if (move_uploaded_file($_FILES['front_image']['tmp_name'], $frontImagePath)) {
                    $front_image = '../Images/book-image/' . $frontImageName;
                    
                    // Update all books with the same front image path
                    $update_front_image = "UPDATE books SET front_image = '$front_image' WHERE id IN (" . implode(',', $all_book_ids) . ")";
                    if (!mysqli_query($conn, $update_front_image)) {
                        throw new Exception("Failed to update front image for all copies.");
                    }
                } else {
                    throw new Exception("Failed to upload front image.");
                }
            }

            // Process back image
            if (isset($_FILES['back_image']) && $_FILES['back_image']['error'] === UPLOAD_ERR_OK) {
                $extension = strtolower(pathinfo($_FILES['back_image']['name'], PATHINFO_EXTENSION));
                $backImageName = $first_book_id . '_back.' . $extension;
                $backImagePath = $imageFolder . $backImageName;

                if (move_uploaded_file($_FILES['back_image']['tmp_name'], $backImagePath)) {
                    $back_image = '../Images/book-image/' . $backImageName;
                    
                    // Update all books with the same back image path
                    $update_back_image = "UPDATE books SET back_image = '$back_image' WHERE id IN (" . implode(',', $all_book_ids) . ")";
                    if (!mysqli_query($conn, $update_back_image)) {
                        throw new Exception("Failed to update back image for all copies.");
                    }
                } else {
                    throw new Exception("Failed to upload back image.");
                }
            }
        }

        if ($transactionSupported) {
            mysqli_commit($conn);
        }

        // Set success flag for the step-by-step page
        $_SESSION['book_shortcut_success'] = true;
        $_SESSION['success_message'] = "Book \"$title\" ($success_count copies) added successfully!"; // Keep a general success message

        // Clear the book shortcut session data to reset the process
        unset($_SESSION['book_shortcut']);
        // Also clear the return_to_form flag
        unset($_SESSION['return_to_form']);

        // Redirect back to the main step-by-step page
        header("Location: step-by-step-add-book.php");
        exit();
    } catch (Exception $e) {
        if ($transactionSupported) {
            mysqli_rollback($conn);
        }
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: step-by-step-add-book-form.php");
        exit();
    }
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: step-by-step-add-book-form.php");
    exit();
}

// Only include header after all potential redirects
include '../admin/inc/header.php';

// Add after database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Add transaction support check
$transactionSupported = true;
try {
    mysqli_begin_transaction($conn);
    mysqli_rollback($conn);
} catch (Exception $e) {
    $transactionSupported = false;
}

// Only keep the main subject options array
$subject_options = array(
    "Topical",
    "Personal",
    "Corporate",
    "Geographical"
);

// Fetch writers for the dropdown
$writers_query = "SELECT id, CONCAT(firstname, ' ', middle_init, ' ', lastname) AS name FROM writers";
$writers_result = mysqli_query($conn, $writers_query);
$writers = [];
while ($row = mysqli_fetch_assoc($writers_result)) {
    $writers[] = $row;
}

// Fetch publishers for the dropdown
$publishers_query = "SELECT id, publisher FROM publishers";
$publishers_result = mysqli_query($conn, $publishers_query);
$publishers = [];
while ($row = mysqli_fetch_assoc($publishers_result)) {
    $publishers[] = $row;
}

$accession_error = '';

// Display the appropriate contributor sections based on contributor type
$showIndividualContributors = !isset($_SESSION['book_shortcut']['contributor_type']) || 
                              $_SESSION['book_shortcut']['contributor_type'] === 'individual_only' || 
                              $_SESSION['book_shortcut']['contributor_type'] === 'both';

$showCorporateContributors = !isset($_SESSION['book_shortcut']['contributor_type']) || 
                             $_SESSION['book_shortcut']['contributor_type'] === 'corporate_only' || 
                             $_SESSION['book_shortcut']['contributor_type'] === 'both';

?>

<!-- Add custom CSS for file uploads -->
<style>
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

.file-preview {
  position: relative;
  width: 100%;
  padding-top: 56.25%; /* 16:9 aspect ratio */
  background-position: center;
  background-size: cover;
  background-repeat: no-repeat;
}

.file-info {
  padding: 10px;
  background: #f8f9fc;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85rem;
}

.file-info .file-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 70%;
}

.file-info .file-size {
  color: #858796;
}

.file-remove {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 30px;
  height: 30px;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #e74a3b;
  cursor: pointer;
  transition: all 0.2s ease;
}

.file-remove:hover {
  background: rgba(255, 255, 255, 1);
  transform: scale(1.1);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .file-upload-area {
    min-height: 150px;
    padding: 15px;
  }
  
  .file-upload-area .upload-icon {
    font-size: 1.5rem;
  }
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

/* Live validation indicators styles */
.validation-indicator {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #e74a3b;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.validation-indicator.show {
  opacity: 1;
}

.form-control.live-validate:invalid,
.form-control.live-validate.is-invalid {
  border-color: #e74a3b;
  padding-right: 30px;
  background-image: none;
}

.form-control.live-validate:valid,
.form-control.live-validate.is-valid {
  border-color: #1cc88a;
  padding-right: 30px;
  background-image: none;
}

.form-group {
  position: relative;
}

.validation-message {
  display: none;
  font-size: 80%;
  color: #e74a3b;
  margin-top: 0.25rem;
}

.form-control.is-invalid + .validation-message {
  display: block;
}

.validation-check {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #1cc88a;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.validation-check.show {
  opacity: 1;
}

/* Tab required indicator (red dot) */
.tab-required-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    margin-left: 6px;
    border-radius: 50%;
    vertical-align: middle;
    position: relative;
    top: -2px;
    transition: background-color 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease, transform 0.3s ease;
    opacity: 1;
    transform: scale(1);
}
.tab-required-indicator.hidden {
    opacity: 0;
    transform: scale(0);
    width: 0;
    height: 0;
    margin-left: 0;
    overflow: hidden;
}
.tab-required-indicator.error {
    background: #e74a3b;
    box-shadow: 0 0 3px #e74a3b;
}
.tab-required-indicator.warning {
    background: #f6c23e;
    box-shadow: 0 0 3px #f6c23e;
}
</style>

<!-- Main Content -->
<div id="content-wrapper" class="d-flex flex-column min-vh-100">
    <div id="content" class="flex-grow-1">
        <div class="container-fluid">
            <!-- Fix: Remove enctype if not needed -->
            <form id="bookForm" action="step-by-step-add-book-form.php" method="POST" enctype="multipart/form-data" class="h-100"
                  onkeydown="return event.key != 'Enter';">
                <div class="container-fluid d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                    <h1 class="h3 mb-2 text-gray-800">Add Book</h1>
                    <div>
                        <a href="step-by-step-add-book.php" class="btn btn-secondary mr-2">
                            <i class="fas fa-arrow-left"></i> Back to Progress Form
                        </a>
                        <!-- Fix: Change button type to submit -->
                        <button type="submit" name="submit" class="btn btn-success">Add Book</button>
                    </div>
                </div>

                <!-- Display Shortcut Information -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['book_shortcut']['book_title']); ?></div>
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Book Title (From Shortcut)
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="row no-gutters align-items-center">
                                    <div class="col-md-4">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Author(s):</div>
                                        <ul class="list-unstyled">
                                            <?php
                                            $writer_info = [];
                                            if (!empty($_SESSION['book_shortcut']['selected_writers'])) {
                                                foreach ($_SESSION['book_shortcut']['selected_writers'] as $selected_writer) {
                                                    $writer_id = $selected_writer['id'];
                                                    $writer_query = "SELECT firstname, middle_init, lastname FROM writers WHERE id = $writer_id";
                                                    $writer_result = $conn->query($writer_query);
                                                    if ($writer_result && $writer_result->num_rows > 0) {
                                                        $writer = $writer_result->fetch_assoc();
                                                        $writer_info[] = [
                                                            'name' => trim($writer['firstname'] . ' ' . $writer['middle_init'] . ' ' . $writer['lastname']),
                                                            'role' => $selected_writer['role']
                                                        ];
                                                    }
                                                }
                                            } else {
                                                // Fallback to using just the writer_id from the shortcut
                                                if (isset($_SESSION['book_shortcut']['writer_id']) && $_SESSION['book_shortcut']['writer_id']) {
                                                    $writer_id = $_SESSION['book_shortcut']['writer_id'];
                                                    $writer_query = "SELECT firstname, middle_init, lastname FROM writers WHERE id = $writer_id";
                                                    $writer_result = $conn->query($writer_query);
                                                    if ($writer_result && $writer_result->num_rows > 0) {
                                                        $writer = $writer_result->fetch_assoc();
                                                        $writer_info[] = [
                                                            'name' => trim($writer['firstname'] . ' ' . $writer['middle_init'] . ' ' . $writer['lastname']),
                                                            'role' => 'Author'
                                                        ];
                                                    }
                                                }
                                            }
                                            ?>
                                            <?php foreach ($writer_info as $writer): ?>
                                                <li>
                                                    <span class="badge badge-<?php echo ($writer['role'] == 'Author' ? 'primary' : ($writer['role'] == 'Co-Author' ? 'info' : 'secondary')); ?>">
                                                        <?php echo htmlspecialchars($writer['role']); ?>
                                                    </span>
                                                    <?php echo htmlspecialchars($writer['name']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Publisher:</div>
                                        <div class="h6 mb-0 font-weight-bold text-gray-800">
                                            <?php
                                            if (isset($_SESSION['book_shortcut']['publisher_id']) && $_SESSION['book_shortcut']['publisher_id']) {
                                                $publisher_id = $_SESSION['book_shortcut']['publisher_id'];
                                                $publisher_query = "SELECT publisher, place FROM publishers WHERE id = $publisher_id";
                                                $publisher_result = $conn->query($publisher_query);
                                                if ($publisher_result && $publisher_result->num_rows > 0) {
                                                    $publisher_data = $publisher_result->fetch_assoc();
                                                    echo htmlspecialchars($publisher_data['publisher']);
                                                } else {
                                                    echo 'Publisher not found';
                                                }
                                            } else {
                                                echo 'Not selected';
                                            }
                                            ?>
                                        </div>
                                        <div class="small text-gray-600">
                                            <?php
                                            if (isset($publisher_data) && isset($publisher_data['place'])) {
                                                echo htmlspecialchars($publisher_data['place']);
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Publication Year:</div>
                                        <div class="h6 mb-0 font-weight-bold text-gray-800">
                                            <?php
                                            if (isset($_SESSION['book_shortcut']['publish_year'])) {
                                                echo htmlspecialchars($_SESSION['book_shortcut']['publish_year']);
                                            } else {
                                                echo date('Y');
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12 col-lg-12">
                        <!-- Tab Navigation - Single Row -->
                        <div class="nav-tab-wrapper overflow-auto">
                            <ul class="nav nav-tabs flex-nowrap" id="formTabs" role="tablist" style="white-space: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 5px;">
                                <li class="nav-item">
                                    <a class="nav-link active" id="title-tab" data-toggle="tab" href="#title-proper" role="tab">
                                        <i class="fas fa-book"></i> Title Proper
                                        <span class="tab-required-indicator"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="subject-tab" data-toggle="tab" href="#subject-entry" role="tab">
                                        <i class="fas fa-tag"></i> Access Point
                                        <span class="tab-required-indicator"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="abstracts-tab" data-toggle="tab" href="#abstracts" role="tab">
                                        <i class="fas fa-file-alt"></i> Abstract & Notes
                                        <span class="tab-required-indicator"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="description-tab" data-toggle="tab" href="#description" role="tab">
                                        <i class="fas fa-info-circle"></i> Description
                                        <span class="tab-required-indicator"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="local-info-tab" data-toggle="tab" href="#local-info" role="tab">
                                        <i class="fas fa-map-marker-alt"></i> Local Information
                                        <span class="tab-required-indicator"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="publication-tab" data-toggle="tab" href="#publication" role="tab">
                                        <i class="fas fa-print"></i> Publication
                                        <span class="tab-required-indicator"></span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content mt-3" id="formTabsContent">
                            <!-- Title Proper Tab -->
                            <div class="tab-pane fade show active" id="title-proper" role="tabpanel">
                                <h4>Title Proper</h4>

                                <!-- Book Title from Shortcut in alert box, similar to Writers and Publisher -->
                                <div class="alert alert-info mb-4">
                                    <h5 class="mb-2">Book Title from Shortcut</h5>
                                    <p>The following title will be used for this book:</p>
                                    <div class="card mb-2">
                                        <div class="card-body py-2">
                                            <h6 class="mb-0 font-weight-bold">
                                                <?php echo isset($_SESSION['book_shortcut']['book_title']) ?
                                                    htmlspecialchars($_SESSION['book_shortcut']['book_title']) :
                                                    '<span class="text-danger">No title set</span>'; ?>
                                            </h6>
                                        </div>
                                    </div>
                                    <p class="mt-2 mb-0"><small>To change the title, please <a href="step-by-step-books.php">go back to the book title selection page</a>.</small></p>
                                </div>
                                <input type="hidden" name="title" value="<?php echo isset($_SESSION['book_shortcut']['book_title']) ? htmlspecialchars($_SESSION['book_shortcut']['book_title']) : ''; ?>">

                                <div class="form-group">
                                    <label>Preferred Title</label>
                                    <input type="text" class="form-control" name="preferred_title">
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle mr-1"></i> Alternative title, if applicable.
                                        </small>
                                </div>
                                <div class="form-group">
                                    <label>Parallel Title</label>
                                    <input type="text" class="form-control" name="parallel_title">
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle mr-1"></i> Title in another language.
                                        </small>
                                </div>

                                <!-- Hidden inputs for writer information - only include if showing individual contributors -->
                                <?php if ($showIndividualContributors && !empty($_SESSION['book_shortcut']['selected_writers'])): ?>
                                    <?php
                                    if (!empty($_SESSION['book_shortcut']['selected_writers'])) {
                                        $mainAuthorFound = false;
                                        $illustrators_ids = array(); // Initialize arrays for illustrators
                                        $translators_ids = array(); // Initialize arrays for translators
                                        
                                        foreach ($_SESSION['book_shortcut']['selected_writers'] as $index => $selected_writer) {
                                            $writer_id = $selected_writer['id'];
                                            $writer_role = $selected_writer['role'];

                                            if ($writer_role === 'Author') {
                                                // For the first author, store as main author AND in the authors array
                                                if (!$mainAuthorFound) {
                                                    echo '<input type="hidden" name="author" value="' . $writer_id . '">';
                                                    $mainAuthorFound = true;
                                                }
                                                // Always include all authors in the authors array
                                                echo '<input type="hidden" name="authors[]" value="' . $writer_id . '">';
                                            } elseif ($writer_role === 'Co-Author') {
                                                // Add co-authors
                                                echo '<input type="hidden" name="co_authors[]" value="' . $writer_id . '">';
                                            } elseif ($writer_role === 'Editor') {
                                                // Add editors
                                                echo '<input type="hidden" name="editors[]" value="' . $writer_id . '">';
                                            } elseif ($writer_role === 'Illustrator') {
                                                // Add illustrators
                                                echo '<input type="hidden" name="illustrators[]" value="' . $writer_id . '">';
                                            } elseif ($writer_role === 'Translator') {
                                                // Add translators
                                                echo '<input type="hidden" name="translators[]" value="' . $writer_id . '">';
                                            }
                                        }
                                    } elseif (isset($_SESSION['book_shortcut']['writer_id']) && $_SESSION['book_shortcut']['writer_id']) {
                                        // Fallback to using just the writer_id from the shortcut as the main author
                                        echo '<input type="hidden" name="author" value="' . $_SESSION['book_shortcut']['writer_id'] . '">';
                                    }
                                    ?>
                                <?php endif; ?>

                                <!-- Display selected contributors based on contributor type -->
                                <?php if ($showIndividualContributors && !empty($_SESSION['book_shortcut']['selected_writers'])): ?>
                                <div class="alert alert-info mt-4">
                                    <h5 class="mb-2">Writers from Shortcut</h5>
                                    <p>The following writers will be associated with this book:</p>
                                    <ul class="list-group">
                                        <?php
                                        if (!empty($_SESSION['book_shortcut']['selected_writers'])) {
                                            foreach ($_SESSION['book_shortcut']['selected_writers'] as $selected_writer) {
                                                $writer_id = $selected_writer['id'];
                                                $writer_role = $selected_writer['role'];
                                                $writer_query = "SELECT firstname, middle_init, lastname FROM writers WHERE id = $writer_id";
                                                $writer_result = $conn->query($writer_query);

                                                if ($writer_result && $writer_result->num_rows > 0) {
                                                    $writer = $writer_result->fetch_assoc();
                                                    echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                                    echo htmlspecialchars(trim($writer['firstname'] . ' ' . $writer['middle_init'] . ' ' . $writer['lastname']));
                                                    echo '<span class="badge badge-' .
                                                        ($writer_role == 'Author' ? 'primary' : ($writer_role == 'Co-Author' ? 'info' : 'secondary')) .
                                                        ' rounded-pill">' . htmlspecialchars($writer_role) . '</span>';
                                                    echo '</li>';
                                                }
                                            }
                                        } elseif (isset($_SESSION['book_shortcut']['writer_id']) && $_SESSION['book_shortcut']['writer_id']) {
                                            $writer_id = $_SESSION['book_shortcut']['writer_id'];
                                            $writer_query = "SELECT firstname, middle_init, lastname FROM writers WHERE id = $writer_id";
                                            $writer_result = $conn->query($writer_query);
                                            if ($writer_result && $writer_result->num_rows > 0) {
                                                $writer = $writer_result->fetch_assoc();
                                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                                echo htmlspecialchars(trim($writer['firstname'] . ' ' . $writer['middle_init'] . ' ' . $writer['lastname']));
                                                echo '<span class="badge badge-primary rounded-pill">Author</span>';
                                                echo '</li>';
                                            }
                                        } else {
                                            echo '<li class="list-group-item">No writers selected</li>';
                                        }
                                        ?>
                                    </ul>
                                    <p class="mt-2 mb-0"><small>To change writers, please <a href="step-by-step-writers.php">go back to the writers selection page</a>.</small></p>
                                </div>
                                <?php endif; ?>

                                <!-- Add Corporate Contributors section - only show if corporate contributors are enabled -->
                                <?php if ($showCorporateContributors && !empty($_SESSION['book_shortcut']['selected_corporates'])): ?>
                                <div class="alert alert-info mt-4">
                                    <h5 class="mb-2">Corporate Contributors from Shortcut</h5>
                                    <p>The following corporate contributors will be associated with this book:</p>
                                    <ul class="list-group">
                                        <?php
                                        if (!empty($_SESSION['book_shortcut']['selected_corporates'])) {
                                            foreach ($_SESSION['book_shortcut']['selected_corporates'] as $selected_corporate) {
                                                $corporate_id = $selected_corporate['id'];
                                                $corporate_role = $selected_corporate['role'];
                                                $stmt = $conn->prepare("SELECT name, type FROM corporates WHERE id = ?");
                                                $stmt->bind_param("i", $corporate_id);
                                                $stmt->execute();
                                                $corporate_result = $stmt->get_result();

                                                if ($corporate_result && $corporate_result->num_rows > 0) {
                                                    $corporate = $corporate_result->fetch_assoc();
                                                    echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                                    echo htmlspecialchars($corporate['name']) . ' <small class="text-muted">(' . htmlspecialchars($corporate['type']) . ')</small>';
                                                    echo '<span class="badge badge-'.($corporate_role == 'Sponsor' ? 'primary' : 'info').'">' . htmlspecialchars($corporate_role) . '</span>';
                                                    echo '</li>';
                                                    echo '<input type="hidden" name="corporate_contributors[]" value="' . $corporate_id . '">';
                                                    echo '<input type="hidden" name="corporate_roles[' . $corporate_id . ']" value="' . htmlspecialchars($corporate_role) . '">';
                                                }
                                                $stmt->close();
                                            }
                                        } else {
                                            echo '<li class="list-group-item">No corporate contributors selected</li>';
                                        }
                                        ?>
                                    </ul>
                                    <p class="mt-2 mb-0"><small>To change corporate contributors, please <a href="step-by-step-corporates.php">go back to the corporate selection page</a>.</small></p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Subject Entry Tab -->
                            <div class="tab-pane fade" id="subject-entry" role="tabpanel">
                                <h4>Access Point</h4>
                                <div id="subjectEntriesContainer">
                                    <div class="subject-entry-group mb-3">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Subject Category</label>
                                                <input type="text" class="form-control subject-category" name="subject_categories[]">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> Example options: Topical, Personal, Corporate, Geographical
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Program</label>
                                                <input type="text" class="form-control" name="program[]">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> 
                                                    Example options: General Education, Computer Science, Accountancy, Entrepreneurship, Accountancy Information System, Tourism Management
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Details</label>
                                                <textarea class="form-control" name="subject_paragraphs[]"
                                                    rows="3"></textarea>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> Provide specific subject terms, keywords, or descriptions that help identify the content.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Abstracts Tab -->
                            <div class="tab-pane fade" id="abstracts" role="tabpanel">
                                <h4>Abstracts</h4>
                                <div class="form-group">
                                    <label>Summary/Abstract</label>
                                    <textarea class="form-control" name="abstract" rows="4"></textarea>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Brief summary of the book's content.
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label>Contents</label>
                                    <textarea class="form-control" name="notes" rows="4"></textarea>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Additional notes about the book.
                                    </small>
                                </div>
                            </div>

                            <!-- Description Tab -->
                            <div class="tab-pane fade" id="description" role="tabpanel">
                                <h4>Description</h4>
                                <!-- Replace file inputs with custom file upload components -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Front Cover Image</label>
                                            <div class="file-upload-container">
                                                <div class="file-upload-area">
                                                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                                    <div class="upload-text">Drag & drop front cover image or click to browse</div>
                                                    <div class="upload-hint">Supported formats: JPG, PNG, GIF (Max: 5MB)</div>
                                                </div>
                                                <input type="file" name="front_image" class="file-upload-input" accept="image/*">
                                                <div class="invalid-feedback">Please select a front cover image</div>
                                                
                                                <div class="file-preview-container">
                                                    <div class="file-preview"></div>
                                                    <div class="file-info">
                                                        <span class="file-name"></span>
                                                        <span class="file-size"></span>
                                                    </div>
                                                    <div class="file-remove"><i class="fas fa-times"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Back Cover Image</label>
                                            <div class="file-upload-container">
                                                <div class="file-upload-area">
                                                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                                    <div class="upload-text">Drag & drop back cover image or click to browse</div>
                                                    <div class="upload-hint">Supported formats: JPG, PNG, GIF (Max: 5MB)</div>
                                                </div>
                                                <input type="file" name="back_image" class="file-upload-input" accept="image/*">
                                                <div class="invalid-feedback">Please select a back cover image</div>
                                                
                                                <div class="file-preview-container">
                                                    <div class="file-preview"></div>
                                                    <div class="file-info">
                                                        <span class="file-name"></span>
                                                        <span class="file-size"></span>
                                                    </div>
                                                    <div class="file-remove"><i class="fas fa-times"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                

                                <!-- Then the rest of the fields -->
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Dimension</label>
                                            <input type="text" class="form-control" name="dimension">
                                            <div class="mt-2">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> Physical dimensions of the resource (include unit: cm, mm, inches)
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Extent of Text and Illustrations</label>
                                            <input type="text" class="form-control" name="total_pages">
                                            <div class="mt-2">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> Format as: preliminary pages + main text (e.g., "xiii, 256p." or "xii, 345p. : ill.")
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Supplementary Contents</label>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <input type="text" class="form-control" name="supplementary_content">
                                            <div class="mt-2">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> Includes: Appendix (app.), Bibliography (bibl.), Glossary (gloss.), Index (ind.), Maps, Tables (tbl.)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Local Information Tab -->
                            <div class="tab-pane fade" id="local-info" role="tabpanel">
                                <h4>Local Information</h4>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="accessionContainer">
                                            <div class="accession-group mb-2">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label><i class="fas fa-barcode mr-1"></i> Accession Group 1 <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control accession-input live-validate" name="accession[]"
                                                                placeholder="e.g., 0001 (will auto-increment based on copies)" required>
                                                                <small class="form-text text-muted">
                                                                    <i class="fas fa-info-circle mr-1"></i> If you enter 0001 and set 3 copies, it will create: 0001, 0002, 0003
                                                                </small>
                                                            <?php if ($accession_error): ?>
                                                                <small class="text-danger"><?php echo $accession_error; ?></small>
                                                            <?php endif; ?>
                                                            <div class="validation-message">This field is required</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label><i class="fas fa-copy mr-1"></i> Number of Copies <span class="text-danger">*</span></label>
                                                            <input type="number" class="form-control copies-input live-validate" name="number_of_copies[]" min="1" value="1" required>
                                                            <small class="form-text text-muted">
                                                                <i class="fas fa-info-circle mr-1"></i> Auto-increments accession
                                                            </small>
                                                            <div class="validation-message">This field is required</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label>&nbsp;</label>
                                                        <button type="button" class="btn btn-primary btn-block w-100 add-accession"><i class="fas fa-plus-circle mr-1"></i> Add Another</button>
                                                        <small class="form-text text-muted">
                                                            <i class="fas fa-info-circle mr-1"></i> Add another accession group for books with different ISBN/edition/volume combinations
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Reduce top margin and add bottom margin -->
                                        <div class="form-group mb-3">
                                            <div id="callNumberContainer">
                                                <!-- Will be populated by JavaScript -->
                                            </div>
                                            <small class="text-muted">Enter unique call numbers for each copy</small>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <!-- end local information -->

                            <!-- Publication Tab -->
                            <div class="tab-pane fade" id="publication" role="tabpanel">
                                <h4>Publication</h4>

                                <!-- Hidden input for publisher and publication year from shortcut -->
                                <?php if (isset($_SESSION['book_shortcut']['publisher_id']) && $_SESSION['book_shortcut']['publisher_id']): ?>
                                    <input type="hidden" name="publisher_id" value="<?php echo $_SESSION['book_shortcut']['publisher_id']; ?>">
                                <?php endif; ?>

                                <?php if (isset($_SESSION['book_shortcut']['publish_year'])): ?>
                                    <input type="hidden" name="publish_date" value="<?php echo $_SESSION['book_shortcut']['publish_year']; ?>">
                                <?php else: ?>
                                    <input type="hidden" name="publish_date" value="<?php echo date('Y'); ?>">
                                <?php endif; ?>

                                <div class="alert alert-info mb-4">
                                    <h5 class="mb-2">Publisher from Shortcut</h5>
                                    <p>The following publisher information will be used for this book:</p>

                                    <?php
                                    if (isset($_SESSION['book_shortcut']['publisher_id']) && $_SESSION['book_shortcut']['publisher_id']):
                                        $publisher_id = $_SESSION['book_shortcut']['publisher_id'];
                                        $publisher_query = "SELECT publisher, place FROM publishers WHERE id = $publisher_id";
                                        $publisher_result = $conn->query($publisher_query);
                                        if ($publisher_result && $publisher_result->num_rows > 0):
                                            $publisher = $publisher_result->fetch_assoc();
                                    ?>
                                        <div class="card mb-2">
                                            <div class="card-body py-2">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Publisher:</strong> <?php echo htmlspecialchars($publisher['publisher']); ?></p>
                                                        <p class="mb-1"><strong>Place:</strong> <?php echo htmlspecialchars($publisher['place']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Year of Publication:</strong>
                                                            <?php
                                                            if (isset($_SESSION['book_shortcut']['publish_year'])) {
                                                                echo htmlspecialchars($_SESSION['book_shortcut']['publish_year']);
                                                            } else {
                                                                echo date('Y');
                                                            }
                                                            ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                        else:
                                    ?>
                                        <p class="text-warning">Publisher information not found.</p>
                                    <?php
                                        endif;
                                    else:
                                    ?>
                                        <p class="text-warning">No publisher selected.</p>
                                    <?php endif; ?>

                                    <p class="mt-2 mb-0"><small>To change publisher information, please <a href="step-by-step-publishers.php">go back to the publisher selection page</a>.</small></p>
                                </div>

                                <!-- Move Details for Accession Group fields just below the "Publisher from Shortcut" -->
                                <div id="detailsForAccessionGroupContainer">
                                    <!-- Will be populated by JavaScript -->
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div id="isbnContainer">
                                                <!-- Will be populated by JavaScript -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Move URL to its own row spanning full width -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="url">URL (if applicable)</label>
                                            <input type="text" class="form-control" id="url" name="url" placeholder="https://example.com">
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i> Optional URL for digital resources
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content Type, Media Type, Carrier Type, and Language in One Row -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Content Type</label>
                                            <select class="form-control" name="content_type">
                                                <option value="Text">Text</option>
                                                <option value="Image">Image</option>
                                                <option value="Video">Video</option>
                                            </select>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i> The form of communication through which the work is expressed
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Media Type</label>
                                            <select class="form-control" name="media_type">
                                                <option value="Print">Print</option>
                                                <option value="Digital">Digital</option>
                                                <option value="Audio">Audio</option>
                                            </select>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i> The general type of intermediation device required to view the content
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Carrier Type</label>
                                            <select class="form-control" name="carrier_type">
                                                <option value="Book">Book</option>
                                                <option value="CD">CD</option>
                                                <option value="USB">USB</option>
                                            </select>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i> The physical medium in which the content is stored or displayed
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Language</label>
                                            <select class="form-control" name="language">
                                                <option value="English">English</option>
                                                <option value="Spanish">Spanish</option>
                                            </select>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i> Primary language of the resource's content
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Move Details for Accession Group fields outside the current column -->
                                <div id="detailsForAccessionGroupContainer">
                                    <!-- Will be populated by JavaScript -->
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </form> <!-- Fix: Moved form closing tag here -->
        </div>
    </div>
    <?php include '../admin/inc/footer.php'; ?>
</div>

<!-- Add this style for responsive improvements -->
<style>
/* Responsive fixes */
.nav-tab-wrapper {
    width: 100%;
    white-space: nowrap;
    margin-bottom: 15px;
}

@media (max-width: 768px) {
    .input-group {
        flex-wrap: wrap;
    }

    .input-group > * {
        flex: 0 0 100%;
        margin-bottom: 5px;
    }

    .input-group .input-group-text {
        width: 100%;
        border-radius: 0.25rem 0.25rem 0 0;
    }

    .input-group .form-control {
        width: 100%;
        border-radius: 0 0 0.25rem 0.25rem;
    }

    .input-group .shelf-location-select {
        width: 100%;
        margin-top: 5px;
        border-radius: 0.25rem;
    }

    .accession-group .row {
        margin-bottom: 10px;
    }

    .copy-number-input {
        width: 100% !important;
    }

    .call-number-preview {
        position: static !important;
        transform: none !important;
        display: block;
        width: 100%;
        padding: 5px 0;
        text-align: center;
    }
}

/* Fix overflow in tables */
.table-responsive {
    overflow-x: auto;
}

/* Improve tab display */
@media (max-width: 576px) {
    .nav-tabs {
        border-bottom: none;
    }

    .nav-tabs .nav-item {
        display: inline-block;
    }

    .nav-tabs .nav-link {
        margin-bottom: 5px;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }
}
</style>

<!-- Bootstrap and JS -->
<script src="inc/js/demo/chart-area-demo.js"></script>
<script src="inc/js/demo/chart-pie-demo.js"></script>
<script src="inc/js/demo/chart-bar-demo.js"></script>

<!-- SCRIPT FOR TAB -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    var triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'));
    triggerTabList.forEach(function(triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl);
        triggerEl.addEventListener("click", function(event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });
});

function updateISBNFields() {
    const isbnContainer = document.getElementById('isbnContainer');
    const callNumberContainer = document.getElementById('callNumberContainer');
    const detailsForAccessionGroupContainer = document.getElementById('detailsForAccessionGroupContainer');
    isbnContainer.innerHTML = '';
    callNumberContainer.innerHTML = '';
    detailsForAccessionGroupContainer.innerHTML = '';

    // Save existing copy numbers before regenerating
    const copyNumberValues = {};
    document.querySelectorAll('.copy-number-input').forEach((input, index) => {
        copyNumberValues[index] = input.value;
    });

    // Get all accession groups
    const accessionGroups = document.querySelectorAll('.accession-group');

    accessionGroups.forEach((group, groupIndex) => {
        const accessionInput = group.querySelector('.accession-input').value;
        const copiesCount = parseInt(group.querySelector('.copies-input').value) || 1;

        // Create a container for Series, Volume, Edition, and ISBN inputs (in Publication tab)
        const groupDiv = document.createElement('div');
        groupDiv.className = 'form-group mb-3';

        const groupLabel = document.createElement('label');
        groupLabel.textContent = `Details for Accession Group ${groupIndex + 1}`;
        groupDiv.appendChild(groupLabel);

        const rowDiv = document.createElement('div');
        rowDiv.className = 'row';

        // Create ISBN input cell
        const isbnDiv = document.createElement('div');
        isbnDiv.className = 'col-md-3'; 
        const isbnLabel = document.createElement('small');
        isbnLabel.className = 'd-block';
        isbnLabel.textContent = 'ISBN';
        const isbnInput = document.createElement('input');
        isbnInput.type = 'text';
        isbnInput.className = 'form-control';
        isbnInput.name = 'isbn[]';
        isbnInput.placeholder = 'ISBN';
        isbnDiv.appendChild(isbnLabel);
        isbnDiv.appendChild(isbnInput);
        rowDiv.appendChild(isbnDiv);

        // Create Series input cell
        const seriesDiv = document.createElement('div');
        seriesDiv.className = 'col-md-2';
        const seriesLabel = document.createElement('small');
        seriesLabel.className = 'd-block';
        seriesLabel.textContent = 'Series';
        const seriesInput = document.createElement('input');
        seriesInput.type = 'text';
        seriesInput.className = 'form-control';
        seriesInput.name = 'series[]';
        seriesInput.placeholder = 'Series';
        seriesDiv.appendChild(seriesLabel);
        seriesDiv.appendChild(seriesInput);
        rowDiv.appendChild(seriesDiv);

        // Create Volume input cell
        const volumeDiv = document.createElement('div');
        volumeDiv.className = 'col-md-2';
        const volumeLabel = document.createElement('small');
        volumeLabel.className = 'd-block';
        volumeLabel.textContent = 'Volume';
        const volumeInput = document.createElement('input');
        volumeInput.type = 'text';
        volumeInput.className = 'form-control';
        volumeInput.name = 'volume[]';
        volumeInput.placeholder = 'Volume';
        volumeDiv.appendChild(volumeLabel);
        volumeDiv.appendChild(volumeInput);
        rowDiv.appendChild(volumeDiv);

        // Create Part input cell - NEW
        const partDiv = document.createElement('div');
        partDiv.className = 'col-md-2';
        const partLabel = document.createElement('small');
        partLabel.className = 'd-block';
        partLabel.textContent = 'Part';
        const partInput = document.createElement('input');
        partInput.type = 'text';
        partInput.className = 'form-control';
        partInput.name = 'part[]';
        partInput.placeholder = 'Part';
        partDiv.appendChild(partLabel);
        partDiv.appendChild(partInput);
        rowDiv.appendChild(partDiv);

        // Create Edition input cell
        const editionDiv = document.createElement('div');
        editionDiv.className = 'col-md-3'; 
        const editionLabel = document.createElement('small');
        editionLabel.className = 'd-block';
        editionLabel.textContent = 'Edition';
        const editionInput = document.createElement('input');
        editionInput.type = 'text';
        editionInput.className = 'form-control';
        editionInput.name = 'edition[]';
        editionInput.placeholder = 'Edition';
        editionDiv.appendChild(editionLabel);
        editionDiv.appendChild(editionInput);
        rowDiv.appendChild(editionDiv);

        groupDiv.appendChild(rowDiv);
        detailsForAccessionGroupContainer.appendChild(groupDiv);

        // Create call number inputs (in Local Information tab)
        const callNumberGroupLabel = document.createElement('h6');
        callNumberGroupLabel.textContent = `Call Numbers for Accession Group ${groupIndex + 1}`;
        callNumberContainer.appendChild(callNumberGroupLabel);

        // Track index across all copies for accessing saved copy numbers
        let globalCopyIndex = 0;
        for (let i = 0; i < copiesCount; i++) {
            const currentAccession = calculateAccession(accessionInput, i);

            const callNumberDiv = document.createElement('div');
            callNumberDiv.className = 'input-group mb-2';

            const accessionLabel = document.createElement('span');
            accessionLabel.className = 'input-group-text';
            accessionLabel.textContent = `Accession ${currentAccession}`;

            const callNumberInput = document.createElement('input');
            callNumberInput.type = 'text';
            callNumberInput.className = 'form-control call-number-input';
            callNumberInput.name = 'call_number[]';
            callNumberInput.placeholder = 'Enter call number';

            // Add Copy Number label and input
            const copyNumberLabel = document.createElement('span');
            copyNumberLabel.className = 'input-group-text';
            copyNumberLabel.textContent = 'Copy Number';

            const copyNumberInput = document.createElement('input');
            copyNumberInput.type = 'number';
            copyNumberInput.className = 'form-control copy-number-input';
            copyNumberInput.name = 'copy_number[]';
            copyNumberInput.min = '1';

            // Use saved copy number if available, otherwise use i+1
            const copyIndex = globalCopyIndex;
            globalCopyIndex++;
            const savedCopyNumber = copyNumberValues[copyIndex];
            copyNumberInput.value = savedCopyNumber || (i + 1);

            copyNumberInput.style.width = '70px';
            copyNumberInput.dataset.originalValue = i + 1; // Store original value for reference

            const shelfLocationSelect = document.createElement('select');
            shelfLocationSelect.className = 'form-control shelf-location-select';
            shelfLocationSelect.name = 'shelf_locations[]';

            // Add shelf location options
            const shelfOptions = [
                ['TH', 'Thesis'],
                ['FIL', 'Filipiniana'],
                ['CIR', 'Circulation'],
                ['REF', 'Reference'],
                ['SC', 'Special Collection'],
                ['BIO', 'Biography'],
                ['RES', 'Reserve'],
                ['FIC', 'Fiction']
            ];

            shelfOptions.forEach(([value, text]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = text;
                shelfLocationSelect.appendChild(option);
            });

            // Restructure the order of elements
            callNumberDiv.appendChild(accessionLabel);
            callNumberDiv.appendChild(callNumberInput);
            callNumberDiv.appendChild(copyNumberLabel);
            callNumberDiv.appendChild(copyNumberInput);
            callNumberDiv.appendChild(shelfLocationSelect);
            callNumberContainer.appendChild(callNumberDiv);

            // Add event listener to update the call number preview when copy number changes
            copyNumberInput.addEventListener('input', function() {
                const callNumberInput = this.closest('.input-group').querySelector('.call-number-input');
                formatCallNumberDisplay(callNumberInput);
            });
        }
    });
}

function calculateAccession(baseAccession, increment) {
    if (!baseAccession) return '(undefined)';

    // Handle formats like "0001"
    const match = baseAccession.match(/^(.*?)(\d+)$/);
    if (!match) return baseAccession;

    const prefix = match[1]; // Everything before the number
    const num = parseInt(match[2]); // The number part
    const width = match[2].length; // Original width of the number

    // Calculate new number and pad with zeros to maintain original width
    const newNum = (num + increment).toString().padStart(width, '0');

    return prefix + newNum;
}

// Event listeners for accession changes
document.addEventListener('input', function(e) {
    if (e.target && (e.target.classList.contains('copies-input') || e.target.classList.contains('accession-input'))) {
        updateISBNFields();
    }
});

// Modified add-accession handler with icons
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('add-accession')) {
        const accessionContainer = document.getElementById('accessionContainer');
        const groupCount = document.querySelectorAll('.accession-group').length + 1;
        const newGroup = document.createElement('div');
        newGroup.className = 'accession-group mb-3';
        newGroup.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fas fa-barcode mr-1"></i> Accession Group ${groupCount} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control accession-input live-validate" name="accession[]"
                            placeholder="e.g., 0001" required>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i> If you enter 0001 and set 3 copies, it will create: 0001, 0002, 0003
                            </small>
                        <div class="validation-message">This field is required</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fas fa-copy mr-1"></i> Number of Copies <span class="text-danger">*</span></label>
                        <input type="number" class="form-control copies-input live-validate" name="number_of_copies[]" 
                            min="1" value="1" required>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle mr-1"></i> Auto-increments accession
                        </small>
                        <div class="validation-message">This field is required</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-block w-100 remove-accession">
                        <i class="fas fa-trash-alt mr-1"></i> Remove
                    </button>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle mr-1"></i> Remove this accession group
                    </small>
                </div>
            </div>
        `;
        accessionContainer.appendChild(newGroup);
        
        // Set up validation for the new accession input
        const newAccessionInput = newGroup.querySelector('.accession-input');
        setupFieldValidation(newAccessionInput);
        
        // Set up validation for the new copies input
        const newCopiesInput = newGroup.querySelector('.copies-input');
        setupFieldValidation(newCopiesInput);
        
        updateISBNFields();

        // Find the new input field and focus it
        if (newAccessionInput) {
            newAccessionInput.focus();
        }
    }
    
    if (e.target && e.target.classList.contains('remove-accession')) {
        e.target.closest('.accession-group').remove();
        updateISBNFields();
        
        // Update labels for remaining groups
        document.querySelectorAll('.accession-group').forEach((group, index) => {
            const label = group.querySelector('label');
            if (label) {
                label.innerHTML = `<i class="fas fa-barcode mr-1"></i> Accession Group ${index + 1} <span class="text-danger">*</span>`;
            }
        });
    }
});

// Add this to your existing form validation
document.getElementById('bookForm').addEventListener('submit', function(e) {
    let hasErrors = false;
    
    // Validate all accession inputs
    document.querySelectorAll('.accession-input').forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            const parentGroup = input.closest('.accession-group');
            if (parentGroup) {
                parentGroup.classList.add('is-invalid');
                const indicator = parentGroup.querySelector('.validation-indicator');
                if (indicator) {
                    indicator.classList.add('show');
                }
            }
            hasErrors = true;
        }
    });
    
    // Validate all copies inputs
    document.querySelectorAll('.copies-input').forEach(input => {
        const value = parseInt(input.value);
        if (isNaN(value) || value < 1) {
            input.classList.add('is-invalid');
            const parentGroup = input.closest('.accession-group');
            if (parentGroup) {
                parentGroup.classList.add('is-invalid');
                const indicator = parentGroup.querySelector('.validation-indicator');
                if (indicator) {
                    indicator.classList.add('show');
                }
            }
            hasErrors = true;
        }
    });
    
    if (hasErrors) {
        e.preventDefault();
        alert('Please fill in all required accession fields and ensure number of copies is at least 1.');
        return false;
    }
});

// Add input validation for numbers only
document.addEventListener('input', function(e) {
    if (e.target && e.target.classList.contains('accession-input')) {
        e.target.value = e.target.value.replace(/\D/g, ''); // Remove non-digits
    }
});

// Add event listener for cascading updates for call numbers
document.addEventListener('input', function(e) {
    if (e.target && e.target.classList.contains('call-number-input')) {
        const callNumberInputs = document.querySelectorAll('.call-number-input');
        const index = Array.from(callNumberInputs).indexOf(e.target);

        for (let i = index + 1; i < callNumberInputs.length; i++) {
            callNumberInputs[i].value = callNumberInputs[index].value;
        }
    }
});

// Add event listener for cascading updates for shelf locations
document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('shelf-location-select')) {
        const shelfLocationSelects = document.querySelectorAll('.shelf-location-select');
        const index = Array.from(shelfLocationSelects).indexOf(e.target);

        for (let i = index + 1; i < shelfLocationSelects.length; i++) {
            shelfLocationSelects[i].value = shelfLocationSelects[index].value;
        }
    }
});

// Function to format call number for each copy
function formatCallNumber() {
    const rawCallNumber = document.querySelector('input[name="raw_call_number"]').value.trim();
    const copies = document.querySelectorAll('.book-copy');

    copies.forEach((copy, index) => {
        // Use the user-entered copy number rather than the index+1
        const copyNumberInput = copy.querySelector('input[name="copy_number[]"]');
        const copyNumber = copyNumberInput ? copyNumberInput.value : (index + 1);
        const shelfLocation = copy.querySelector('select[name="shelf_location[]"]').value;

        // Ensure proper spacing between components (removed publish year)
        const formattedCallNumber = [
            shelfLocation,
            rawCallNumber,
            `c.${copyNumber}`
        ].filter(Boolean).join(' ');

        // Update hidden input
        let hiddenInput = copy.querySelector('input[name="formatted_call_numbers[]"]');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'formatted_call_numbers[]';
            copy.appendChild(hiddenInput);
        }
        hiddenInput.value = formattedCallNumber;

        // Update display if exists
        const displayElement = copy.querySelector('.call-number-display');
        if (displayElement) {
            displayElement.textContent = formattedCallNumber;
        }
    });
}

// Add more specific formatting for call numbers with copy numbers
function formatCallNumberDisplay(callNumberInput) {
    if (!callNumberInput) return;

    const container = callNumberInput.closest('.input-group');
    if (!container) return;

    const baseCallNumber = callNumberInput.value.trim();
    const copyNumberInput = container.querySelector('.copy-number-input');
    const shelfLocationSelect = container.querySelector('.shelf-location-select');

    if (!baseCallNumber || !copyNumberInput || !shelfLocationSelect) return;

    // Use user's copy number value rather than auto-generated
    const copyNumber = copyNumberInput.value || '1';
    const shelfLocation = shelfLocationSelect.value;

    // Build formatted call number with proper spacing (removed publish year)
    const formattedCallNumber = [
        shelfLocation,
        baseCallNumber
    ].filter(Boolean).join(' ');

    // Get the volume and part if available
    const accessionGroup = container.closest('[data-accession-group]')?.dataset?.accessionGroup;
    let volumeText = '';
    let partText = '';

    if (accessionGroup !== undefined) {
        const volumeInputs = document.querySelectorAll('input[name="volume[]"]');
        if (volumeInputs[accessionGroup] && volumeInputs[accessionGroup].value) {
            volumeText = 'vol.' + volumeInputs[accessionGroup].value;
        }

        const partInputs = document.querySelectorAll('input[name="part[]"]');
        if (partInputs[accessionGroup] && partInputs[accessionGroup].value) {
            partText = 'pt.' + partInputs[accessionGroup].value;
        }
    }

    // Build the final call number with all components (removed publish year)
    const finalCallNumber = [
        formattedCallNumber,
        volumeText,
        partText,
        'c.' + copyNumber
    ].filter(Boolean).join(' ');

    // Create or update preview element
    let previewElem = container.querySelector('.call-number-preview');
    if (!previewElem) {
        previewElem = document.createElement('small');
                previewElem.className = 'call-number-preview text-muted';
        previewElem.style.position = 'absolute';
        previewElem.style.right = '120px';
        previewElem.style.top = '50%';
        previewElem.style.transform = 'translateY(-50%)';
        container.style.position = 'relative';
        container.appendChild(previewElem);
    }

    previewElem.textContent = `→ ${finalCallNumber}`;
    callNumberInput.setAttribute('data-formatted', finalCallNumber);
}

// Add event listener specifically for copy number changes
document.addEventListener('input', function(e) {
    if (e.target && e.target.classList.contains('copy-number-input')) {
        // When copy number changes, update the formatted call number display
        const callNumberInput = e.target.closest('.input-group')?.querySelector('.call-number-input');
        if (callNumberInput) {
            formatCallNumberDisplay(callNumberInput);
        }
    }
});

// Update call numbers when adding/removing copies
function updateCopyNumbers() {
    document.querySelectorAll('.call-number-input').forEach((input) => {
        formatCallNumberDisplay(input);
    });
}

/**
 * Enhanced File Upload Component
 */
function initializeFileUploads() {
  const fileUploads = document.querySelectorAll('.file-upload-container');
    
  fileUploads.forEach(container => {
    const input = container.querySelector('.file-upload-input');
    const uploadArea = container.querySelector('.file-upload-area');
    const previewContainer = container.querySelector('.file-preview-container');
    const preview = container.querySelector('.file-preview');
    const fileName = container.querySelector('.file-name');
    const fileSize = container.querySelector('.file-size');
    const removeButton = container.querySelector('.file-remove');
    
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
      
      // Check if file is an image
      if (!file.type.match('image.*')) {
        alert('Please select an image file (jpg, png, etc.)');
        return;
      }
      
      // Check file size (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        alert('File size exceeds 5MB. Please choose a smaller file.');
        return;
      }
      
      // Update preview
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.style.backgroundImage = `url(${e.target.result})`;
      };
      reader.readAsDataURL(file);
      
      // Show preview container
      previewContainer.classList.add('show');
      
      // Update file info
      fileName.textContent = file.name;
      fileSize.textContent = formatFileSize(file.size);
      
      // Remove invalid state if present
      container.classList.remove('is-invalid');
    }
    
    // Function to clear file selection
    function clearFileSelection() {
      input.value = '';
      preview.style.backgroundImage = '';
      previewContainer.classList.remove('show');
      fileName.textContent = '';
      fileSize.textContent = '';
    }
  });
}

// Utility function to format file size
function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  initializeFileUploads();
});

/**
 * Live validation indicators for required fields
 */
document.addEventListener('DOMContentLoaded', function() {
  // Find all required inputs and add validation classes and indicators
  const requiredInputs = document.querySelectorAll('input[required], textarea[required], select[required]');
  
  // Add explicit validation for specific fields: title, publisher, publish_year
  const specificFields = ['title', 'publisher', 'publish_year'];
  specificFields.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (field && !field.hasAttribute('required')) {
      field.classList.add('live-validate');
      setupFieldValidation(field);
    }
  });
  
  requiredInputs.forEach(input => {
    // Add live validation class
    input.classList.add('live-validate');
    setupFieldValidation(input);
  });
  
  // Function to set up validation for a field
  function setupFieldValidation(input) {
    // Create validation indicator (exclamation mark)
    const validationIndicator = document.createElement('i');
    validationIndicator.className = 'fas fa-exclamation-circle validation-indicator';
    validationIndicator.setAttribute('aria-hidden', 'true');
    
    // Create validation check mark (for valid inputs)
    const validationCheck = document.createElement('i');
    validationCheck.className = 'fas fa-check-circle validation-check';
    validationCheck.setAttribute('aria-hidden', 'true');
    
    // Create validation message
    const validationMessage = document.createElement('div');
    validationMessage.className = 'validation-message';
    validationMessage.textContent = 'This field is required';
    
    // Add indicators and message to parent container
    const parentElement = input.closest('.form-group');
    if (parentElement) {
      parentElement.style.position = 'relative';
      parentElement.appendChild(validationIndicator);
      parentElement.appendChild(validationCheck);
      parentElement.appendChild(validationMessage);
    }
    
    // Function to validate this input
    function validateInput() {
      const isValid = input.value.trim() !== '';
      if (isValid) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        validationIndicator.classList.remove('show');
        validationCheck.classList.add('show');
      } else {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        validationIndicator.classList.add('show');
        validationCheck.classList.remove('show');
      }
    }
    
    // Validate on input
    input.addEventListener('input', validateInput);
    
    // Validate on blur (when user leaves the field)
    input.addEventListener('blur', validateInput);
    
    // Initial validation for pre-filled fields
    if (input.value.trim() !== '') {
      input.classList.add('is-valid');
      validationCheck.classList.add('show');
    } else if (isKeyField(input)) {
      // For important fields like title, publisher, etc., show validation state even initially
      input.classList.add('is-invalid');
      validationIndicator.classList.add('show');
    }
  }
  
  // Helper function to check if a field is one of our key monitored fields
  function isKeyField(input) {
    const keyFields = ['title', 'publisher', 'publish_year'];
    return keyFields.includes(input.id);
  }
  
  // Special handling for accession inputs
  const accessionInputs = document.querySelectorAll('.accession-input');
  accessionInputs.forEach(input => {
    // Use MutationObserver to watch for dynamically added accession fields
    if (input.closest('.accession-group')) {
      // Initial validation
      validateAccessionInput(input);
      
      // Set up event listeners
      input.addEventListener('input', function() {
        validateAccessionInput(this);
      });
      
      input.addEventListener('blur', function() {
        validateAccessionInput(this);
      });
    }
  });
  
  // Function to validate accession input (specialized for accession groups)
  function validateAccessionInput(input) {
    const isValid = input.value.trim() !== '';
    const parentGroup = input.closest('.accession-group');
    
    if (parentGroup) {
      if (isValid) {
        parentGroup.classList.remove('is-invalid');
        parentGroup.classList.add('is-valid');
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
      } else {
        parentGroup.classList.remove('is-valid');
        parentGroup.classList.add('is-invalid');
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
      }
      
      // Find or create indicators
      let indicator = parentGroup.querySelector('.validation-indicator');
      let check = parentGroup.querySelector('.validation-check');
      
      if (!indicator) {
        indicator = document.createElement('i');
        indicator.className = 'fas fa-exclamation-circle validation-indicator';
        input.parentNode.appendChild(indicator);
      }
      
      if (!check) {
        check = document.createElement('i');
        check.className = 'fas fa-check-circle validation-check';
        input.parentNode.appendChild(check);
      }
      
      // Show/hide indicators
      if (isValid) {
        indicator.classList.remove('show');
        check.classList.add('show');
      } else {
        indicator.classList.add('show');
        check.classList.remove('show');
      }
    }
  }
  
  // Set up observer for dynamically added elements
  const accessionContainer = document.getElementById('accessionContainer');
  if (accessionContainer) {
    const observer = new MutationObserver((mutationsList) => {
      for(const mutation of mutationsList) {
        if (mutation.type === 'childList') {
          mutation.addedNodes.forEach(node => {
            if (node.nodeType === 1 && (node.querySelector('.accession-input, .copies-input') || node.matches('.accession-input, .copies-input'))) {
              updateTabRequiredIndicators();
              return;
            }
          });
          mutation.removedNodes.forEach(node => {
               if (node.nodeType === 1 && (node.querySelector('.accession-input, .copies-input') || node.matches('.accession-input, .copies-input'))) {
              updateTabRequiredIndicators();
              return;
            }
          });
        }
      }
    });
    observer.observe(accessionContainer, { childList: true, subtree: true });
  }

     setTimeout(updateTabRequiredIndicators, 200);
});

</script>
