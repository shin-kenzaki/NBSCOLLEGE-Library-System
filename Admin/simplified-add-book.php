<?php
session_start();

// Add at the top after session_start()
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Check if shortcut data exists
if (!isset($_SESSION['book_shortcut']) || 
    !isset($_SESSION['book_shortcut']['selected_writers']) || 
    !isset($_SESSION['book_shortcut']['publisher_id']) || 
    !isset($_SESSION['book_shortcut']['book_title'])) {
    header("Location: add_book_shortcut.php");
    exit();
}

// Get shortcut data
$writer_id = isset($_SESSION['book_shortcut']['writer_id']) ? $_SESSION['book_shortcut']['writer_id'] : null;
$publisher_id = $_SESSION['book_shortcut']['publisher_id'];
$book_title = $_SESSION['book_shortcut']['book_title'];
$selected_writers = isset($_SESSION['book_shortcut']['selected_writers']) ? $_SESSION['book_shortcut']['selected_writers'] : [];
$publish_year = isset($_SESSION['book_shortcut']['publish_year']) ? $_SESSION['book_shortcut']['publish_year'] : date('Y');

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_book'])) {
    if ($transactionSupported) {
        mysqli_begin_transaction($conn);
    }
    
    try {
        $accessions = $_POST['accession'];
        $number_of_copies_array = $_POST['number_of_copies'];
        $title = mysqli_real_escape_string($conn, $book_title); // Use from session
        $preferred_title = mysqli_real_escape_string($conn, $_POST['preferred_title'] ?? '');
        $parallel_title = mysqli_real_escape_string($conn, $_POST['parallel_title'] ?? '');
        $summary = mysqli_real_escape_string($conn, $_POST['abstract'] ?? '');
        $contents = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        
        // Handle file uploads
        $front_image = null;
        $back_image = null;
        if(isset($_FILES['front_image']) && $_FILES['front_image']['error'] == 0) {
            $front_image = 'uploads/' . basename($_FILES['front_image']['name']);
            move_uploaded_file($_FILES['front_image']['tmp_name'], $front_image);
        }
        if(isset($_FILES['back_image']) && $_FILES['back_image']['error'] == 0) {
            $back_image = 'uploads/' . basename($_FILES['back_image']['name']);
            move_uploaded_file($_FILES['back_image']['tmp_name'], $back_image);
        }

        $dimension = mysqli_real_escape_string($conn, $_POST['dimension'] ?? '');
        $series = mysqli_real_escape_string($conn, $_POST['series'] ?? '');
        $volume = mysqli_real_escape_string($conn, $_POST['volume'] ?? '');
        $edition = mysqli_real_escape_string($conn, $_POST['edition'] ?? '');
        $isbn = mysqli_real_escape_string($conn, $_POST['isbn'] ?? '');
        $content_type = mysqli_real_escape_string($conn, $_POST['content_type'] ?? 'Text');
        $media_type = mysqli_real_escape_string($conn, $_POST['media_type'] ?? 'Print');
        $carrier_type = mysqli_real_escape_string($conn, $_POST['carrier_type'] ?? 'Book');
        $url = mysqli_real_escape_string($conn, $_POST['url'] ?? '');
        $language = mysqli_real_escape_string($conn, $_POST['language'] ?? 'English');
        $entered_by = $_SESSION['admin_id'];
        
        // Process subject entries
        $subject_categories = isset($_POST['subject_categories']) ? $_POST['subject_categories'] : array();
        $subject_paragraphs = isset($_POST['subject_paragraphs']) ? $_POST['subject_paragraphs'] : array();

        // Combine all subject entries into strings for storage
        $all_categories = array();
        $all_details = array();

        for ($k = 0; $k < count($subject_categories); $k++) {
            if (!empty($subject_categories[$k])) {
                $all_categories[] = mysqli_real_escape_string($conn, $subject_categories[$k]);
                $all_details[] = mysqli_real_escape_string($conn, $subject_paragraphs[$k]);
            }
        }

        $subject_category = implode('; ', $all_categories);
        $subject_detail = implode('; ', $all_details);
        
        // Process pages and supplementary contents separately
        $prefix_pages = mysqli_real_escape_string($conn, $_POST['prefix_pages'] ?? '');
        $main_pages = mysqli_real_escape_string($conn, $_POST['main_pages'] ?? '');
        $total_pages = trim("$prefix_pages $main_pages");
        $supplementary_content = isset($_POST['supplementary_content']) ? $_POST['supplementary_content'] : [];
        $supplementary_contents = !empty($supplementary_content) ? implode('; ', $supplementary_content) : '';

        $success_count = 0;
        $error_messages = array();

        // Process each accession group
        for ($i = 0; $i < count($accessions); $i++) {
            $base_accession = $accessions[$i];
            $copies_for_this_accession = (int)$number_of_copies_array[$i];
            $current_series = isset($_POST['series'][$i]) ? mysqli_real_escape_string($conn, $_POST['series'][$i]) : $series;
            $current_volume = isset($_POST['volume'][$i]) ? mysqli_real_escape_string($conn, $_POST['volume'][$i]) : $volume;
            $current_edition = isset($_POST['edition'][$i]) ? mysqli_real_escape_string($conn, $_POST['edition'][$i]) : $edition;
            
            for ($j = 0; $j < $copies_for_this_accession; $j++) {
                $current_accession = (int)($base_accession) + $j;
                $call_number_index = $i * $copies_for_this_accession + $j;
                
                // Check for duplicate accession
                $check_query = "SELECT * FROM books WHERE accession = '$current_accession'";
                $result = mysqli_query($conn, $check_query);
                
                if (mysqli_num_rows($result) > 0) {
                    $error_messages[] = "Accession number $current_accession already exists - skipping.";
                    continue;
                }

                // Get current copy number
                $copy_query = "SELECT MAX(copy_number) as max_copy FROM books WHERE title = '$title'";
                $copy_result = mysqli_query($conn, $copy_query);
                $copy_row = mysqli_fetch_assoc($copy_result);
                $copy_number = ($copy_row['max_copy'] !== null) ? $copy_row['max_copy'] + 1 : 1;

                // Format call number
                $current_shelf_location = isset($_POST['shelf_locations'][$call_number_index]) ? 
                    mysqli_real_escape_string($conn, $_POST['shelf_locations'][$call_number_index]) : '';
                $current_call_number = isset($_POST['call_number'][$call_number_index]) ? 
                    mysqli_real_escape_string($conn, $_POST['call_number'][$call_number_index]) : '';
                
                $formatted_call_number = trim("$current_shelf_location $current_call_number c$copy_number");

                // In your book insertion query, replace the static status with the array value
                $status = mysqli_real_escape_string($conn, $_POST['status'][$j] ?? 'Available');

                $query = "INSERT INTO books (
                    accession, title, preferred_title, parallel_title, 
                    subject_category, subject_detail,
                    summary, contents, front_image, back_image, 
                    dimension, series, volume, edition, 
                    copy_number, total_pages, supplementary_contents, ISBN, content_type, 
                    media_type, carrier_type, call_number, URL, 
                    language, shelf_location, entered_by, date_added, 
                    status, last_update
                ) VALUES (
                    '$current_accession', '$title', '$preferred_title', '$parallel_title',
                    '$subject_category', '$subject_detail',
                    '$summary', '$contents', '$front_image', '$back_image',
                    '$dimension', '$current_series', '$current_volume', '$current_edition',
                    $copy_number, '$total_pages', '$supplementary_contents', '$isbn', '$content_type',
                    '$media_type', '$carrier_type', '$formatted_call_number', '$url',
                    '$language', '$current_shelf_location', '$entered_by', CURDATE(),
                    '$status', CURDATE()
                )";

                // Replace the existing publication and contributors insertion code with this:
                if (mysqli_query($conn, $query)) {
                    $success_count++;
                    
                    // Insert into publications table using accession
                    $publication_query = "INSERT INTO publications (book_id, publisher_id, publish_date) 
                                        VALUES ('$current_accession', '$publisher_id', '$publish_year')";
                    if (!mysqli_query($conn, $publication_query)) {
                        $error_messages[] = "Error adding publication for book with accession $current_accession: " . mysqli_error($conn);
                    }

                    // Insert contributors using accession
                    if (!empty($selected_writers)) {
                        foreach ($selected_writers as $selected_writer) {
                            $writer_id = $selected_writer['id'];
                            $role = mysqli_real_escape_string($conn, $selected_writer['role']);
                            
                            $contributor_query = "INSERT INTO contributors (book_id, writer_id, role) 
                                                VALUES ('$current_accession', '$writer_id', '$role')";
                            if (!mysqli_query($conn, $contributor_query)) {
                                $error_messages[] = "Error adding contributor for book with accession $current_accession: " . mysqli_error($conn);
                            }
                        }
                    } 
                    // Fallback for older format
                    elseif ($writer_id) {
                        $contributor_query = "INSERT INTO contributors (book_id, writer_id, role) 
                                            VALUES ('$current_accession', '$writer_id', 'Author')";
                        if (!mysqli_query($conn, $contributor_query)) {
                            $error_messages[] = "Error adding contributor for book with accession $current_accession: " . mysqli_error($conn);
                        }
                    }
                } else {
                    $error_messages[] = "Error adding book with accession $current_accession: " . mysqli_error($conn);
                }
            }
        }

        if ($success_count > 0) {
            if ($transactionSupported) {
                mysqli_commit($conn);
            }
            
            // Reset the shortcut data
            $_SESSION['book_shortcut'] = [
                'writer_id' => '',
                'publisher_id' => '',
                'book_title' => '',
                'selected_writers' => [],
                'publish_year' => date('Y'),
                'steps_completed' => [
                    'writer' => false,
                    'publisher' => false,
                    'title' => false
                ]
            ];

            $_SESSION['success_message'] = "Successfully added " . $success_count . " book(s)! Progress has been reset.";
            header("Location: add_book_shortcut.php");
            exit();
        } else {
            throw new Exception("No books were added successfully.");
        }

    } catch (Exception $e) {
        if ($transactionSupported) {
            mysqli_rollback($conn);
        }
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        if (!empty($error_messages)) {
            $_SESSION['error_details'] = implode("<br>", $error_messages);
        }
    }
}

// Only include header after all potential redirects
include '../admin/inc/header.php';

// Get publisher information for display purposes only
$publisher_query = "SELECT publisher, place FROM publishers WHERE id = $publisher_id";
$publisher_result = $conn->query($publisher_query);
$publisher_data = $publisher_result->fetch_assoc();

// Get author information for display purposes only
$writer_info = [];
if (!empty($selected_writers)) {
    foreach ($selected_writers as $selected_writer) {
        $writer_query = "SELECT firstname, middle_init, lastname FROM writers WHERE id = {$selected_writer['id']}";
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
    if ($writer_id) {
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

$subject_options = array(
    "Topical",
    "Personal",
    "Corporate",
    "Geographical"
);
?>

<!-- Main Content -->
<div id="content-wrapper" class="d-flex flex-column min-vh-100">
    <div id="content" class="flex-grow-1">
        <div class="container-fluid">
            <form id="bookForm" action="simplified-add-book.php" method="POST" enctype="multipart/form-data" class="h-100" 
                  onkeydown="return event.key != 'Enter';">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-2 text-gray-800">Add Book - Simplified Form</h1>
                    <button type="submit" name="submit_book" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Book
                    </button>
                </div>
                
                <!-- Display Shortcut Information -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo htmlspecialchars($book_title); ?></div>
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
                                            <?php foreach ($writer_info as $writer): ?>
                                                <li>
                                                    <span class="badge badge-<?php echo ($writer['role'] == 'Author' ? 'primary' : ($writer['role'] == 'Co-Author' ? 'info' : 'secondary')); ?>">
                                                        <?php echo $writer['role']; ?>
                                                    </span>
                                                    <?php echo htmlspecialchars($writer['name']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Publisher:</div>
                                        <div class="h6 mb-0 font-weight-bold text-gray-800">
                                            <?php echo htmlspecialchars($publisher_data['publisher']); ?>
                                        </div>
                                        <div class="small text-gray-600">
                                            <?php echo htmlspecialchars($publisher_data['place']); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Publication Year:</div>
                                        <div class="h6 mb-0 font-weight-bold text-gray-800">
                                            <?php echo htmlspecialchars($publish_year); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <!-- Error Messages Display -->
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                            <?php if (isset($_SESSION['error_details'])): ?>
                                <hr>
                                <p><?php echo $_SESSION['error_details']; ?></p>
                                <?php unset($_SESSION['error_details']); ?>
                            <?php endif; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-xl-12 col-lg-7">
                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs" id="formTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#title-proper" role="tab">Title Proper</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#subject-entry" role="tab">Access Point</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#abstracts" role="tab">Abstracts</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#description" role="tab">Description</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#local-info" role="tab">Local Information</a>
                            </li>
                        </ul>

                        <div class="tab-content mt-3" id="formTabsContent">
                            <!-- Title Proper Tab -->
                            <div class="tab-pane fade show active" id="title-proper" role="tabpanel">
                                <h4>Title Proper</h4>
                                <div class="form-group">
                                    <label>Title (Pre-filled from shortcut)</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($book_title); ?>" readonly>
                                    <input type="hidden" name="title" value="<?php echo htmlspecialchars($book_title); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Preferred Title</label>
                                    <input type="text" class="form-control" name="preferred_title">
                                </div>
                                <div class="form-group">
                                    <label>Parallel Title</label>
                                    <input type="text" class="form-control" name="parallel_title">
                                </div>
                            </div>

                            <!-- Access Point Tab -->
                            <div class="tab-pane fade" id="subject-entry" role="tabpanel">
                                <h4>Access Point</h4>
                                <div id="subjectEntriesContainer">
                                    <div class="subject-entry-group mb-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Subject Category</label>
                                                    <select class="form-control subject-category" name="subject_categories[]">
                                                        <option value="">Select Subject Category</option>
                                                        <?php foreach ($subject_options as $subject): ?>
                                                            <option value="<?php echo htmlspecialchars($subject); ?>">
                                                                <?php echo htmlspecialchars($subject); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Details</label>
                                                    <textarea class="form-control" name="subject_paragraphs[]" 
                                                        rows="3" placeholder="Enter additional details about this subject"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Removed "Add More Subjects" button -->
                            </div>

                            <!-- Abstracts Tab -->
                            <div class="tab-pane fade" id="abstracts" role="tabpanel">
                                <h4>Abstracts</h4>
                                <div class="form-group">
                                    <label>Summary/Abstract</label>
                                    <textarea class="form-control" name="abstract" rows="6" 
                                        placeholder="Enter a summary or abstract of the book"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Contents</label>
                                    <textarea class="form-control" name="notes" rows="4" 
                                        placeholder="Enter the table of contents or chapter list"></textarea>
                                </div>
                            </div>

                            <!-- Description Tab -->
                            <div class="tab-pane fade" id="description" role="tabpanel">
                                <h4>Description</h4>
                                <!-- Add the single ISBN field here -->
                                <div class="form-group">
                                    <label>ISBN</label>
                                    <input type="text" class="form-control" name="isbn" placeholder="Enter ISBN">
                                    <small class="text-muted">This ISBN will be used for all copies</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Front Image</label>
                                    <input type="file" class="form-control" name="front_image">
                                </div>
                                <div class="form-group">
                                    <label>Back Image</label>
                                    <input type="file" class="form-control" name="back_image">
                                </div>
                                <div class="form-group">
                                    <label>Dimension (cm)</label>
                                    <input type="text" class="form-control" name="dimension">
                                </div>
                                <div class="form-group">
                                    <label>Pages</label>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="small">Prefix (Roman)</label>
                                            <input type="text" class="form-control" name="prefix_pages" placeholder="e.g. xvi">
                                            <small class="text-muted">Use roman numerals</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small">Main Pages</label>
                                            <input type="text" class="form-control" name="main_pages" placeholder="e.g. 234a">
                                            <small class="text-muted">Can include letters (e.g. 123a)</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small">Supplementary Contents</label>
                                            <select class="form-control" name="supplementary_content[]" multiple>
                                                <option value="includes bibliography">Includes bibliography</option>
                                                <option value="includes index">Includes index</option>
                                                <option value="includes glossary">Includes glossary</option>
                                                <option value="includes appendix">Includes appendix</option>
                                                <option value="includes notes">Includes notes</option>
                                                <option value="includes references">Includes references</option>
                                                <option value="includes bibliography index">Includes bibliography and index</option>
                                                <option value="includes bibliography notes">Includes bibliography and notes</option>
                                                <option value="includes bibliography references">Includes bibliography and references</option>
                                                <option value="includes index glossary">Includes index and glossary</option>
                                                <option value="includes appendices index">Includes appendices and index</option>
                                                <option value="includes bibliographical references">Includes bibliographical references</option>
                                                <option value="includes bibliography index glossary">Includes bibliography, index, and glossary</option>
                                                <option value="includes bibliography index notes">Includes bibliography, index, and notes</option>
                                                <option value="includes bibliography references index">Includes bibliography, references, and index</option>
                                            </select>
                                            <small class="text-muted">Hold Ctrl/Cmd to select multiple items</small>
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
                                                    <div class="col-md-8">
                                                        <div class="form-group">
                                                            <label>Accession</label>
                                                            <input type="number" class="form-control accession-input" name="accession[]" 
                                                                placeholder="e.g., 2024" required>
                                                            <small class="text-muted">If you enter 2024 and set 3 copies, it will create: 2024, 2025, 2026</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label>Number of Copies</label>
                                                            <input type="number" class="form-control copies-input" name="number_of_copies[]" min="1" value="1" required>
                                                            <small class="text-muted">Auto-increments accession</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>&nbsp;</label>
                                                        <button type="button" class="btn btn-primary btn-block w-100 add-accession">Add Another</button>
                                                    </div>
                                                </div>
                                                <!-- Series, Volume, Edition for each accession group -->
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Series</label>
                                                            <input type="text" class="form-control" name="series">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Volume</label>
                                                            <input type="text" class="form-control" name="volume">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Edition</label>
                                                            <input type="text" class="form-control" name="edition">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <div id="callNumberContainer">
                                                <!-- Will be populated by JavaScript -->
                                            </div>
                                            <small class="text-muted">Enter unique call numbers for each copy</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content Type, Media Type, Carrier Type in one row -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Content Type</label>
                                            <select class="form-control" name="content_type">
                                                <option value="Text">Text</option>
                                                <option value="Image">Image</option>
                                                <option value="Video">Video</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Media Type</label>
                                            <select class="form-control" name="media_type">
                                                <option value="Print">Print</option>
                                                <option value="Digital">Digital</option>
                                                <option value="Audio">Audio</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Carrier Type</label>
                                            <select class="form-control" name="carrier_type">
                                                <option value="Book">Book</option>
                                                <option value="CD">CD</option>
                                                <option value="USB">USB</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Language</label>
                                            <select class="form-control" name="language">
                                                <option value="English">English</option>
                                                <option value="Spanish">Spanish</option>
                                                <option value="Filipino">Filipino</option>
                                                <option value="Tagalog">Tagalog</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>URL</label>
                                            <input type="text" class="form-control" name="url" placeholder="http://example.com">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Entered By</label>
                                            <?php
                                            // Get admin details
                                            $admin_id = $_SESSION['admin_id'];
                                            $admin_query = "SELECT CONCAT(firstname, ' ', lastname) as full_name, role 
                                                          FROM admins WHERE id = ?";
                                            $stmt = $conn->prepare($admin_query);
                                            $stmt->bind_param("i", $admin_id);
                                            $stmt->execute();
                                            $admin_result = $stmt->get_result();
                                            $admin_data = $admin_result->fetch_assoc();
                                            ?>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($admin_data['full_name'] . ' (' . $admin_data['role'] . ') - ID: ' . $admin_id); ?>" 
                                                   readonly>
                                            <input type="hidden" name="entered_by" value="<?php echo $admin_id; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Date Added</label>
                                            <input type="text" class="form-control" name="date_added" value="<?php echo date('Y-m-d'); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!--  -->

                                <!--  -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select class="form-control" name="status">
                                                <option value="Available">Available</option>
                                                <option value="Borrowed">Borrowed</option>
                                                <option value="Lost">Lost</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Last Update</label>
                                            <input type="text" class="form-control" name="last_update" value="<?php echo date('Y-m-d'); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!--  -->
                            </div>
                            <!-- end local information -->

                            <!-- Publication Tab -->
                            <div class="tab-pane fade" id="publication" role="tabpanel">
                                <h4>Publication</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Publisher</label>
                                            <select class="form-control" name="publisher" required>
                                                <option value="">Select Publisher</option>
                                                <?php foreach ($publishers as $publisher): ?>
                                                    <option value="<?php echo htmlspecialchars($publisher['publisher']); ?>">
                                                        <?php echo htmlspecialchars($publisher['publisher']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Year of Publication</label>
                                            <input type="number" class="form-control" name="publish_date" placeholder="e.g., 2023" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Series</label>
                                            <input type="text" class="form-control" name="series">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Volume</label>
                                            <input type="text" class="form-control" name="volume">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Edition</label>
                                            <input type="text" class="form-control" name="edition">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>ISBN Numbers</label>
                                            <div id="isbnContainer">
                                                <!-- Will be populated by JavaScript -->
                                            </div>
                                            <small class="text-muted">One ISBN per accession group</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>URL</label>
                                            <input type="text" class="form-control" name="url">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Content Type</label>
                                            <select class="form-control" name="content_type">
                                                <option value="Text">Text</option>
                                                <option value="Image">Image</option>
                                                <option value="Video">Video</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content Type, Media Type, Carrier Type in One Row -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Media Type</label>
                                            <select class="form-control" name="media_type">
                                                <option value="Print">Print</option>
                                                <option value="Digital">Digital</option>
                                                <option value="Audio">Audio</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Carrier Type</label>
                                            <select class="form-control" name="carrier_type">
                                                <option value="Book">Book</option>
                                                <option value="CD">CD</option>
                                                <option value="USB">USB</option>
                                            </select>
                                        </div>
                                    </div>
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
    
    // Initialize call number container
    updateISBNFields();
});

// Update the updateISBNFields function to include status field
function updateISBNFields() {
    const callNumberContainer = document.getElementById('callNumberContainer');
    callNumberContainer.innerHTML = '';
    
    // Get all accession groups
    const accessionGroups = document.querySelectorAll('.accession-group');
    
    accessionGroups.forEach((group, groupIndex) => {
        const accessionInput = group.querySelector('.accession-input').value;
        const copiesCount = parseInt(group.querySelector('.copies-input').value) || 1;
        
        const groupLabel = document.createElement('h6');
        groupLabel.className = 'mt-3 mb-2';
        groupLabel.textContent = `Call Numbers for Accession Group ${groupIndex + 1}`;
        callNumberContainer.appendChild(groupLabel);
        
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
            
            const shelfLocationSelect = document.createElement('select');
            shelfLocationSelect.className = 'form-control shelf-location-select';
            shelfLocationSelect.name = 'shelf_locations[]';
            
            // Add shelf location options
            const shelfOptions = [
                ['TR', 'Teachers Reference'],
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

            // Add status select
            const statusSelect = document.createElement('select');
            statusSelect.className = 'form-control status-select';
            statusSelect.name = 'status[]';
            
            // Add status options
            const statusOptions = [
                ['Available', 'Available'],
                ['Borrowed', 'Borrowed'],
                ['Lost', 'Lost'],
                ['Damaged', 'Damaged'],
                ['Under Repair', 'Under Repair'],
                ['Reserved', 'Reserved']
            ];
            
            statusOptions.forEach(([value, text]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = text;
                statusSelect.appendChild(option);
            });
            
            callNumberDiv.appendChild(accessionLabel);
            callNumberDiv.appendChild(callNumberInput);
            callNumberDiv.appendChild(shelfLocationSelect);
            callNumberDiv.appendChild(statusSelect);
            callNumberContainer.appendChild(callNumberDiv);
        }
    });
}

function calculateAccession(baseAccession, increment) {
    if (!baseAccession) return '(undefined)';
    
    // Handle formats like "2023-0001" or "2023-001" or just "0001"
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

// Modified add-accession handler
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('add-accession')) {
        const groupCount = document.querySelectorAll('.accession-group').length + 1;
        const newGroup = document.createElement('div');
        newGroup.className = 'accession-group mb-3';
        newGroup.innerHTML = `
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Accession (Copy ${groupCount})</label>
                        <input type="text" class="form-control accession-input" name="accession[]" 
                            placeholder="e.g., 2023-0001" required>
                        <small class="text-muted">Format: YYYY-NNNN</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Number of Copies</label>
                        <input type="number" class="form-control copies-input" name="number_of_copies[]" min="1" value="1" required>
                        <small class="text-muted">Auto-increments accession</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-block w-100 remove-accession">Remove</button>
                </div>
            </div>
            <!-- Series, Volume, Edition for each accession group -->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Series</label>
                        <input type="text" class="form-control" name="series[]">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Volume</label>
                        <input type="text" class="form-control" name="volume[]">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Edition</label>
                        <input type="text" class="form-control" name="edition[]">
                    </div>
                </div>
            </div>
        `;
        document.getElementById('accessionContainer').appendChild(newGroup);
        updateISBNFields();
    }

    if (e.target && e.target.classList.contains('remove-accession')) {
        e.target.closest('.accession-group').remove();
        updateISBNFields();
    }
});

// Replace SweetAlert validation with standard alert
function validateForm(e) {
    const accessionInputs = document.querySelectorAll('.accession-input');
    let hasError = false;
    let errorMessage = '';
    
    accessionInputs.forEach(input => {
        if (!input.value.trim()) {
            hasError = true;
            errorMessage = 'Please fill in all accession fields before submitting.';
        } else if (!/^\d+$/.test(input.value.trim())) {
            hasError = true;
            errorMessage = 'Accession numbers must contain only digits (0-9).';
        }
    });
    
    if (hasError) {
        e.preventDefault();
        alert(errorMessage);
        return false;
    }
    return true;
}

// PHP Success/Error message handler
<?php if (isset($_SESSION['success_message'])): ?>
    alert(<?php echo json_encode($_SESSION['success_message']); ?>);
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    alert(<?php echo json_encode($_SESSION['error_message']); ?>);
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

// Single form submission handler
document.getElementById('bookForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent default temporarily
    
    // Basic validation
    const accessionInputs = document.querySelectorAll('.accession-input');
    let hasError = false;
    let errorMessage = '';
    
    // Check accession numbers
    accessionInputs.forEach(input => {
        if (!input.value.trim()) {
            hasError = true;
            errorMessage = 'Please fill in all accession numbers.';
        }
    });

    if (hasError) {
        alert(errorMessage);
        return false;
    }

    // If validation passes, ask for confirmation
    if (confirm('Are you sure you want to add this book?')) {
        // Submit the form
        this.submit();
    }
});

// Add input validation for numbers only
document.addEventListener('input', function(e) {
    if (e.target && e.target.classList.contains('accession-input')) {
        e.target.value = e.target.value.replace(/\D/g, ''); // Remove non-digits
    }
});

// Add event listener to the form
document.getElementById('bookForm').addEventListener('submit', validateForm);

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

// Add event listener for cascading updates for status
document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('status-select')) {
        const statusSelects = document.querySelectorAll('.status-select');
        const index = Array.from(statusSelects).indexOf(e.target);
        
        // Update all subsequent status selects to match the changed one
        for (let i = index + 1; i < statusSelects.length; i++) {
            statusSelects[i].value = statusSelects[index].value;
        }
    }
});

</script>