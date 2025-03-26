<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Add form reset check - must come before including process-add-book.php
$resetForm = false;
if (isset($_SESSION['reset_book_form']) && $_SESSION['reset_book_form'] === true) {
    $resetForm = true;
    unset($_SESSION['reset_book_form']); // Clear the flag
}

// Include the database connection
include '../db.php';

// Include the processing file for form submissions
include 'process/process-add-book.php';

// Only include header after all potential redirects
include 'inc/header.php';

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
$publishers_query = "SELECT id, publisher, place FROM publishers";
$publishers_result = mysqli_query($conn, $publishers_query);
$publishers = [];
while ($row = mysqli_fetch_assoc($publishers_result)) {
    $publishers[] = $row;
}

$accession_error = '';

?>

<!-- Main Content -->
<div id="content-wrapper" class="d-flex flex-column min-vh-100">
    <div id="content" class="flex-grow-1">
        <div class="container-fluid">
            <!-- Fix: Remove enctype if not needed -->
            <form id="bookForm" action="add-book.php" method="POST" enctype="multipart/form-data" class="h-100" 
                  onkeydown="return event.key != 'Enter';">
                <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-2 text-gray-800">Add Book</h1>
                    <button type="button" class="btn btn-warning mr-2" data-clear-form>
                        <i class="fas fa-trash"></i> Clear Form
                    </button>
                </div>

                <!-- Progress Bar -->
                <div class="progress mb-4">
                    <div class="progress-bar" role="progressbar" style="width: 0%" id="formProgressBar"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12 col-lg-7">
                        <!-- Tab Navigation -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <ul class="nav nav-tabs flex-grow-1" id="formTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="title-tab" data-toggle="tab" href="#title-proper" role="tab">
                                        <i class="fas fa-book"></i> Title Information
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="subject-tab" data-toggle="tab" href="#subject-entry" role="tab">
                                        <i class="fas fa-tag"></i> Subject Entry
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="abstracts-tab" data-toggle="tab" href="#abstracts" role="tab">
                                        <i class="fas fa-file-alt"></i> Abstract & Notes
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="description-tab" data-toggle="tab" href="#description" role="tab">
                                        <i class="fas fa-info-circle"></i> Description
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="local-info-tab" data-toggle="tab" href="#local-info" role="tab">
                                        <i class="fas fa-map-marker-alt"></i> Local Information
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="publication-tab" data-toggle="tab" href="#publication" role="tab">
                                        <i class="fas fa-print"></i> Publication
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="tab-content card border-0 shadow-sm p-4 mt-3" id="formTabsContent">
                            <!-- Title Proper Tab -->
                            <div class="tab-pane fade show active" id="title-proper" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0">Title Information</h4>
                                    <div class="btn-group">
                                        <!-- Previous button if not first tab -->
                                        <button type="button" class="btn btn-outline-secondary btn-sm prev-tab me-2" data-prev="title-tab">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm clear-tab-btn" data-tab-id="title-proper">
                                            <i class="fas fa-eraser"></i> Clear Tab
                                        </button>
                                        <!-- Next button if not last tab -->
                                        <button type="button" class="btn btn-outline-primary btn-sm next-tab ms-2" data-next="subject-tab">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="title">Title Proper</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                    <small class="form-text text-muted">Main title of the book.</small>
                                </div>
                                <div class="form-group">
                                    <label for="preferred_title">Preferred Title</label>
                                    <input type="text" class="form-control" id="preferred_title" name="preferred_title">
                                    <small class="form-text text-muted">Alternative title, if applicable.</small>
                                </div>
                                <div class="form-group">
                                    <label for="parallel_title">Parallel Title</label>
                                    <input type="text" class="form-control" id="parallel_title" name="parallel_title">
                                    <small class="form-text text-muted">Title in another language.</small>
                                </div>
                                <div class="mt-4 d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary next-tab" data-next="subject-tab">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Subject Entry Tab -->
                            <div class="tab-pane fade" id="subject-entry" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0">Subject Entry</h4>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm prev-tab" data-prev="title-tab">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm clear-tab-btn" data-tab-id="subject-entry">
                                            <i class="fas fa-eraser"></i> Clear Tab
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm next-tab" data-next="abstracts-tab">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="subject-entries">
                                    <div class="subject-entry card p-3 mb-3">
                                        <button type="button" class="btn btn-danger btn-sm remove-subject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <div class="form-group">
                                            <label>Subject Category</label>
                                            <select class="form-control" name="subject_categories[]">
                                                <option value="">Select Subject Category</option>
                                                <?php foreach ($subject_options as $option): ?>
                                                    <option value="<?php echo $option; ?>"><?php echo $option; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Subject Detail</label>
                                            <textarea class="form-control" name="subject_paragraphs[]" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary mb-3" id="add-subject">
                                    <i class="fas fa-plus"></i> Add Another Subject
                                </button>
                            </div>
                            <!-- Abstracts Tab -->
                            <div class="tab-pane fade" id="abstracts" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0">Abstract & Notes</h4>
                                    <div class="btn-group">
                                        <!-- Previous button if not first tab -->
                                        <button type="button" class="btn btn-outline-secondary btn-sm prev-tab me-2" data-prev="subject-tab">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm clear-tab-btn" data-tab-id="abstracts">
                                            <i class="fas fa-eraser"></i> Clear Tab
                                        </button>
                                        <!-- Next button if not last tab -->
                                        <button type="button" class="btn btn-outline-primary btn-sm next-tab ms-2" data-next="description-tab">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="abstract">Abstract/Summary</label>
                                    <textarea class="form-control" id="abstract" name="abstract" rows="4"></textarea>
                                    <small class="form-text text-muted">Brief summary of the book's content.</small>
                                </div>
                                <div class="form-group">
                                    <label for="notes">Notes/Contents</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="4"></textarea>
                                    <small class="form-text text-muted">Additional notes about the book.</small>
                                </div>
                            </div>
                            <!-- Description Tab -->
                            <div class="tab-pane fade" id="description" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0">Description</h4>
                                    <div class="btn-group">
                                        <!-- Previous button if not first tab -->
                                        <button type="button" class="btn btn-outline-secondary btn-sm prev-tab me-2" data-prev="abstracts-tab">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm clear-tab-btn" data-tab-id="description">
                                            <i class="fas fa-eraser"></i> Clear Tab
                                        </button>
                                        <!-- Next button if not last tab -->
                                        <button type="button" class="btn btn-outline-primary btn-sm next-tab ms-2" data-next="local-info-tab">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dimension">Dimensions</label>
                                            <input type="text" class="form-control" id="dimension" name="dimension" placeholder="e.g., 23 cm">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="prefix_pages">Prefix Pages</label>
                                            <input type="text" class="form-control" id="prefix_pages" name="prefix_pages" placeholder="e.g., xii">
                                            <small class="form-text text-muted">Roman numerals for prefatory pages.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="main_pages">Main Pages</label>
                                            <input type="text" class="form-control" id="main_pages" name="main_pages" placeholder="e.g., 350 p.">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Supplementary Content</label><br>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="supplementary_content[]" value="ill." id="ill">
                                                <label class="form-check-label" for="ill">Illustrations</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="supplementary_content[]" value="maps" id="maps">
                                                <label class="form-check-label" for="maps">Maps</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="supplementary_content[]" value="index" id="index">
                                                <label class="form-check-label" for="index">Index</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="supplementary_content[]" value="bibliog." id="bibliog">
                                                <label class="form-check-label" for="bibliog">Bibliography</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Book Images (Optional)</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="custom-file mb-3">
                                                <input type="file" class="custom-file-input" id="front_image" name="front_image">
                                                <label class="custom-file-label" for="front_image">Front Cover</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="custom-file mb-3">
                                                <input type="file" class="custom-file-input" id="back_image" name="back_image">
                                                <label class="custom-file-label" for="back_image">Back Cover</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Local Information Tab -->
                            <div class="tab-pane fade" id="local-info" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0">Local Information</h4>
                                    <div class="btn-group">
                                        <!-- Previous button if not first tab -->
                                        <button type="button" class="btn btn-outline-secondary btn-sm prev-tab me-2" data-prev="description-tab">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm clear-tab-btn" data-tab-id="local-info">
                                            <i class="fas fa-eraser"></i> Clear Tab
                                        </button>
                                        <!-- Next button if not last tab -->
                                        <button type="button" class="btn btn-outline-primary btn-sm next-tab ms-2" data-next="publication-tab">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                                <!-- Accession Number Section -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Accession Numbers & Call Numbers</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="accessionContainer">
                                            <div class="accession-group mb-3">
                                                <div class="row">
                                                    <div class="col-md-7">
                                                        <div class="form-group">
                                                            <label>Accession (Copy 1)</label>
                                                            <input type="text" class="form-control accession-input" name="accession[]" 
                                                                placeholder="e.g., 2023-0001" required>
                                                            <small class="text-muted">Format: YYYY-NNNN</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Number of Copies</label>
                                                            <input type="number" class="form-control copies-input" name="number_of_copies[]" min="1" value="1" required>
                                                            <small class="text-muted">Auto-increments accession</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2 remove-btn-container">
                                                        <!-- No remove button for the first entry -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-secondary btn-sm add-accession">
                                            <i class="fas fa-plus"></i> Add Another Accession Group
                                        </button>
                                    </div>
                                </div>
                                <!-- Call Numbers -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Call Numbers</h5>
                                        <small class="text-info">Note: The full formatted call number (including shelf location, call number, year, and copy number) will be saved to the database.</small>
                                        <small class="text-danger d-block mt-1"><strong>Important:</strong> Follow proper call number formatting:</small>
                                        <ul class="text-danger mb-0 small pl-4 mt-1">
                                            <li>Enter classification number and author cutter with a single space between them (e.g., "HD69.B7 W56")</li>
                                            <li>Avoid extra spaces at beginning or end of your call number</li>
                                            <li>Trailing spaces will be automatically removed</li>
                                            <li>The system adds proper spacing between components (shelf location, call number, year, volume, copy)</li>
                                            <li>Example: "REF HD69.B7 W56 2024 c1" (shelf location + classification + author cutter + year + copy)</li>
                                        </ul>
                                    </div>
                                    <div class="card-body">
                                        <div id="callNumberContainer">
                                            <!-- Call numbers will be generated here by JavaScript -->
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" id="generateCallNumbersBtn" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-sync"></i> Reset Call Numbers
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Other Information -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="content_type">Content Type</label>
                                            <select class="form-control" id="content_type" name="content_type">
                                                <option value="Text">Text</option>
                                                <option value="Image">Image</option>
                                                <option value="Video">Video</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="media_type">Media Type</label>
                                            <select class="form-control" id="media_type" name="media_type">
                                                <option value="Print">Print</option>
                                                <option value="Digital">Digital</option>
                                                <option value="Audio">Audio</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="carrier_type">Carrier Type</label>
                                            <select class="form-control" id="carrier_type" name="carrier_type">
                                                <option value="Book">Book</option>
                                                <option value="CD">CD</option>
                                                <option value="USB">USB</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="language">Language</label>
                                            <select class="form-control" id="language" name="language">
                                                <option value="English">English</option>
                                                <option value="Spanish">Spanish</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="url">URL (if applicable)</label>
                                    <input type="url" class="form-control" id="url" name="url" placeholder="https://example.com">
                                </div>
                            </div>
                            <!-- Publication Tab -->
                            <div class="tab-pane fade" id="publication" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0">Publication Details</h4>
                                    <div class="btn-group">
                                        <!-- Previous button if not first tab -->
                                        <button type="button" class="btn btn-outline-secondary btn-sm prev-tab me-2" data-prev="local-info-tab">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm clear-tab-btn" data-tab-id="publication">
                                            <i class="fas fa-eraser"></i> Clear Tab
                                        </button>
                                        <button type="submit" name="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-save"></i> Save Book
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="publisher">Publisher</label>
                                            <div class="input-group mb-2">
                                                <input type="text" id="publisherSearch" class="form-control" placeholder="Search publishers...">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" id="addNewPublisherBtn">
                                                        <i class="fas fa-plus"></i> New Publisher
                                                    </button>
                                                </div>
                                            </div>
                                            <select class="form-control" id="publisher" name="publisher" required>
                                                <option value="">Select Publisher</option>
                                                <?php foreach ($publishers as $publisher): ?>
                                                    <option value="<?php echo $publisher['publisher']; ?>"><?php echo $publisher['publisher']; ?> (<?php echo $publisher['place'] ?? 'Unknown'; ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="publish_date">Publication Year</label>
                                            <input type="number" class="form-control" id="publish_date" name="publish_date" 
                                                min="1800" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div id="isbnContainer">
                                    <!-- ISBN/Series/Volume/Edition fields will be generated here by JavaScript -->
                                </div>
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Contributors</h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Contributors Row Layout -->
                                        <div class="row">
                                            <!-- Authors Section -->
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="authorSelect">Author(s)</label>
                                                    <div class="input-group mb-2">
                                                        <input type="text" id="authorSearch" class="form-control" placeholder="Search authors...">
                                                        <div class="input-group-append">
                                                            <button class="btn btn-outline-secondary" type="button" id="addNewAuthorBtn">
                                                                <i class="fas fa-plus"></i> New Author
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <select id="authorSelect" name="author[]" class="form-control">
                                                        <option value="">Select Author</option>
                                                        <?php foreach ($writers as $writer): ?>
                                                            <option value="<?php echo $writer['id']; ?>"><?php echo $writer['name']; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div id="authorPreview" class="selected-preview mt-2"></div>
                                                </div>
                                            </div>
                                            <!-- Co-Authors Section -->
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="coAuthorsSelect">Co-Author(s)</label>
                                                    <div class="input-group mb-2">
                                                        <input type="text" id="coAuthorsSearch" class="form-control" placeholder="Search co-authors...">
                                                    </div>
                                                    <select id="coAuthorsSelect" name="co_authors[]" class="form-control" multiple>
                                                        <?php foreach ($writers as $writer): ?>
                                                            <option value="<?php echo $writer['id']; ?>"><?php echo $writer['name']; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div id="coAuthorsPreview" class="selected-preview mt-2"></div>
                                                </div>
                                            </div>
                                            <!-- Editors Section -->
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="editorsSelect">Editor(s)</label>
                                                    <div class="input-group mb-2">
                                                        <input type="text" id="editorsSearch" class="form-control" placeholder="Search editors...">
                                                    </div>
                                                    <select id="editorsSelect" name="editors[]" class="form-control" multiple>
                                                        <?php foreach ($writers as $writer): ?>
                                                            <option value="<?php echo $writer['id']; ?>"><?php echo $writer['name']; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div id="editorsPreview" class="selected-preview mt-2"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- System Info -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">System Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="entered_by">Entered By</label>
                                                    <input type="text" class="form-control" id="entered_by" name="entered_by" 
                                                        value="<?php echo $_SESSION['admin_id']; ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="date_added">Date Added</label>
                                                    <input type="text" class="form-control" id="date_added" name="date_added" 
                                                        value="<?php echo date('Y-m-d'); ?>" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="status">Status</label>
                                                    <select class="form-control" id="status" name="status">
                                                        <option value="Available">Available</option>
                                                        <option value="Reserved">Reserved</option>
                                                        <option value="Borrowed">Borrowed</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="last_update">Last Update</label>
                                                    <input type="text" class="form-control" id="last_update" name="last_update" 
                                                        value="<?php echo date('Y-m-d H:i:s'); ?>" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form> <!-- Form closing tag -->
        </div>
    </div>
    <?php include 'inc/footer.php'; ?>
</div>


<style>
.selected-preview .badge {
    margin-right: 5px;
    margin-bottom: 5px;
}
.selected-preview .remove-icon {
    cursor: pointer;
    margin-left: 5px;
}
.nav-tabs .nav-link {
    position: relative;
    padding: 10px 15px;
}
.nav-tabs .nav-link.completed:after {
    content: '✓';
    position: absolute;
    top: 5px;
    right: 5px;
    color: #1cc88a;
    font-weight: bold;
}
/* Add these styles for button alignment */
.remove-btn-container {
    display: flex;
    align-items: center;
    justify-content: flex-end;
}
.subject-entry {
    position: relative;
}
.subject-entry .remove-subject {
    position: absolute;
    top: 10px;
    right: 10px;
}
/* Tab navigation styling */
.tab-navigation-buttons {
    display: flex;
    align-items: center;
}
/* Completed tab styling */
#formTabs .nav-link.completed {
    position: relative;
    display: flex;
    align-items: center;
}
#formTabs .nav-link.completed::after {
    content: '✓';
    position: absolute;
    top: 2px;
    right: 5px;
    font-size: 12px;
    color: #28a745;
}
/* Add to existing styles */
.btn-group .btn {
    margin: 0 2px;
}
.btn-group .prev-tab,
.btn-group .next-tab {
    min-width: 85px;
}
/* Hide previous button on first tab */
#title-proper .prev-tab {
    display: none;
}
/* Hide next button on last tab */
#publication .next-tab {
    display: none;
}
/* Special styling for submit button on last tab */
#publication .btn-success {
    margin-left: 2px;
}
.accession-details {
    padding: 15px;
    background-color: #f8f9fc;
    border-radius: 0.35rem;
    margin-top: 15px;
}
.accession-group {
    padding: 20px;
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    margin-bottom: 20px;
}

/* Add styling for call number grouping */
#callNumberContainer .text-muted.small.font-weight-bold {
    border-bottom: 1px solid #e3e6f0;
    padding-bottom: 5px;
    margin-top: 15px;
}

/* Style for copy number input */
.copy-number-input {
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    font-weight: bold;
    text-align: center;
}

/* Improve input group spacing */
.input-group > .input-group-text {
    background-color: #f8f9fc;
}

/* Improved styling for call number inputs */
.input-group .copy-number-input {
    width: 70px !important;
    flex: 0 0 70px;
    text-align: center;
    font-weight: bold;
    border-radius: 0;
}

/* Make call number input take more space */
.input-group .call-number-input {
    min-width: 150px;
    flex: 1;
}

/* Better spacing for input group elements */
.input-group > .input-group-text {
    background-color: #f8f9fc;
    padding: 0.375rem 0.5rem;
    white-space: nowrap;
}

/* Add styling for call number grouping */
#callNumberContainer .text-muted.small.font-weight-bold {
    border-bottom: 1px solid #e3e6f0;
    padding-bottom: 5px;
    margin-top: 15px;
}

/* SweetAlert Custom Styles */
.swal2-popup {
    padding: 1.5em;
}

.swal2-popup .swal2-title {
    font-size: 1.5em;
    margin-bottom: 1em;
}

/* Form styling inside SweetAlert */
#sweetAlertAuthorContainer, 
#sweetAlertPublisherContainer {
    max-height: 400px;
    overflow-y: auto;
    margin-bottom: 1em;
}

.swal2-popup .row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;
}

.swal2-popup .col-md-1,
.swal2-popup .col-md-3,
.swal2-popup .col-md-4,
.swal2-popup .col-md-5,
.swal2-popup .col-md-6 {
    position: relative;
    width: 100%;
    padding-right: 15px;
    padding-left: 15px;
}

.swal2-popup .col-md-1 { flex: 0 0 8.333333%; max-width: 8.333333%; }
.swal2-popup .col-md-3 { flex: 0 0 25%; max-width: 25%; }
.swal2-popup .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
.swal2-popup .col-md-5 { flex: 0 0 41.666667%; max-width: 41.666667%; }
.swal2-popup .col-md-6 { flex: 0 0 50%; max-width: 50%; }

.swal2-popup .form-group {
    margin-bottom: 1rem;
}

.swal2-popup .form-control {
    display: block;
    width: 100%;
    height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.swal2-popup .btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    user-select: none;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: 0.25rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.swal2-popup .btn-secondary {
    color: #fff;
    background-color: #6c757d;
    border-color: #6c757d;
}

.swal2-popup .btn-danger {
    color: #fff;
    background-color: #dc3545;
    border-color: #dc3545;
}

.swal2-popup .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}

.swal2-actions {
    margin-top: 1.5em;
}
</style>

<!-- Bootstrap and JS -->
<script src="inc/js/demo/chart-area-demo.js"></script>
<script src="inc/js/demo/chart-pie-demo.js"></script>
<script src="inc/js/demo/chart-bar-demo.js"></script>

<!-- Include JavaScript Files -->
<script src="js/form-validation.js"></script>
<script>
/**
 * Fallback for call number generation in case the main implementation fails
 */
document.addEventListener('DOMContentLoaded', function() {
    // Wait for DOM to be fully loaded
    setTimeout(function() {
        const callNumberContainer = document.getElementById('callNumberContainer');
        const accessionContainer = document.getElementById('accessionContainer');
        
        // Only run if call number container exists and is empty
        if (callNumberContainer && callNumberContainer.children.length === 0 && accessionContainer) {
            console.log('Call number fallback mechanism activated');
            
            // Save any existing copy numbers from another container if present
            const existingCopyNumbers = {};
            document.querySelectorAll('.copy-number-input').forEach((input, index) => {
                existingCopyNumbers[index] = input.value;
            });
            
            // Try to create call numbers manually
            const accessionGroups = accessionContainer.querySelectorAll('.accession-group');
            
            // Track total copies for continuous numbering
            let totalCopiesCount = 0;
            
            if (accessionGroups.length > 0) {
                accessionGroups.forEach((group, groupIndex) => {
                    const accessionInput = group.querySelector('.accession-input');
                    const copiesInput = group.querySelector('.copies-input');
                    
                    if (accessionInput && copiesInput) {
                        const accessionValue = accessionInput.value || `ACC-${groupIndex + 1}`;
                        const copiesCount = parseInt(copiesInput.value) || 1;
                        
                        // Create heading for this group
                        const groupHeader = document.createElement('div');
                        groupHeader.className = 'mb-2 text-muted small font-weight-bold';
                        groupHeader.innerHTML = `Accession Group ${groupIndex + 1}: ${accessionValue}`;
                        callNumberContainer.appendChild(groupHeader);
                        
                        // Create call number entries for each copy
                        for (let i = 0; i < copiesCount; i++) {
                            // Calculate accession with increment if possible
                            let currentAccession = accessionValue;
                            if (i > 0) {
                                // Simple increment logic
                                if (/\d+$/.test(accessionValue)) {
                                    const base = accessionValue.replace(/\d+$/, '');
                                    const num = parseInt(accessionValue.match(/\d+$/)[0]);
                                    currentAccession = base + (num + i).toString().padStart(accessionValue.match(/\d+$/)[0].length, '0');
                                }
                            }
                            
                            // Create call number input group
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
                            
                            // Add a formatter function for this input
                            callNumberInput.addEventListener('input', function() {
                                updateFormattedCallNumber(this);
                            });
                            
                            const copyNumberLabel = document.createElement('span');
                            copyNumberLabel.className = 'input-group-text';
                            copyNumberLabel.textContent = 'Copy #';
                            
                            // Use saved copy number if available, otherwise use continuous numbering
                            const copyIndex = totalCopiesCount + i;
                            const copyNumberValue = existingCopyNumbers[copyIndex] || (totalCopiesCount + i + 1);
                            
                            const copyNumberInput = document.createElement('input');
                            copyNumberInput.type = 'number';
                            copyNumberInput.className = 'form-control copy-number-input';
                            copyNumberInput.name = 'copy_number[]';
                            copyNumberInput.min = '1';
                            copyNumberInput.value = copyNumberValue;
                            copyNumberInput.style.width = '70px';
                            
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
                                if (value === 'CIR') option.selected = true;
                                shelfLocationSelect.appendChild(option);
                            });
                            
                            // Assemble the input group
                            callNumberDiv.appendChild(accessionLabel);
                            callNumberDiv.appendChild(callNumberInput);
                            callNumberDiv.appendChild(copyNumberLabel);
                            callNumberDiv.appendChild(copyNumberInput);
                            callNumberDiv.appendChild(shelfLocationSelect);
                            callNumberContainer.appendChild(callNumberDiv);
                        }
                        
                        // Update the total count for next group
                        totalCopiesCount += copiesCount;
                    }
                });
                
                console.log('Fallback successfully created call number fields');
            }
        }
    }, 1000); // Wait 1 second to ensure other scripts have run
    
    // Listen for changes on accession inputs and update call numbers if needed
    document.addEventListener('input', function(e) {
        if (e.target && (e.target.classList.contains('accession-input') || e.target.classList.contains('copies-input'))) {
            setTimeout(function() {
                const callNumberContainer = document.getElementById('callNumberContainer');
                if (callNumberContainer && callNumberContainer.children.length === 0) {
                    console.log('Accession input changed, call number container empty - activating fallback');
                    
                    // Try direct generator first
                    if (typeof generateCallNumbersDirectly === 'function') {
                        generateCallNumbersDirectly();
                    } else {
                        // Otherwise use fallback generation logic
                        const accessionContainer = document.getElementById('accessionContainer');
                        const accessionGroups = accessionContainer.querySelectorAll('.accession-group');
                        
                        if (accessionGroups.length > 0) {
                            // Use the existing fallback logic...
                            console.log('Using fallback logic to generate call numbers');
                        }
                    }
                }
            }, 500);
        }
    });
});

// Add this helper function at the end of the file
function updateFormattedCallNumber(input) {
    const container = input.closest('.input-group');
    if (!container) return;
    
    const baseCallNumber = input.value.trim();
    if (!baseCallNumber) return;
    
    const shelfSelect = container.querySelector('.shelf-location-select');
    const copyInput = container.querySelector('.copy-number-input');
    if (!shelfSelect || !copyInput) return;
    
    const publishYear = document.getElementById('publish_date')?.value || '';
    const shelf = shelfSelect.value;
    const copy = 'c' + copyInput.value;
    
    // Process base call number to ensure proper spacing
    const callParts = baseCallNumber.split(/\s+/).filter(part => part.length > 0);
    const processedCallNumber = callParts.join(' ');
    
    // Build the formatted call number with proper spacing
    const elements = [shelf, processedCallNumber];
    if (publishYear) elements.push(publishYear);
    elements.push(copy);
    const formatted = elements.join(' ').trim();
    
    // Store in data attribute for form submission
    input.dataset.formattedCallNumber = formatted;
    
    // Create or update preview element
    let preview = container.querySelector('.call-number-preview');
    if (!preview) {
        preview = document.createElement('small');
        preview.className = 'call-number-preview text-muted';
        preview.style.position = 'absolute';
        preview.style.right = '120px';
        preview.style.top = '50%';
        preview.style.transform = 'translateY(-50%)';
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(preview);
    }
    
    preview.textContent = `→ ${formatted}`;
    
    // Add warning about trailing spaces if needed
    let warningElem = container.querySelector('.call-number-warning');
    if (!warningElem) {
        warningElem = document.createElement('small');
        warningElem.className = 'call-number-warning text-danger d-block mt-1';
        warningElem.style.fontSize = '11px';
        input.parentNode.appendChild(warningElem);
    }
    
    // Check if the user has added trailing spaces to the base call number
    if (input.value !== input.value.trimEnd()) {
        warningElem.textContent = 'Warning: Trailing spaces will be removed from call number';
        warningElem.style.display = 'block';
    } else if (callParts.length === 0) {
        warningElem.textContent = 'Please enter a complete call number';
        warningElem.style.display = 'block';
    } else if (callParts.length === 1 && baseCallNumber.length > 2) {
        warningElem.textContent = 'Call numbers should have a space between classification and author cutter (e.g., "HD69.B7 W56")';
        warningElem.style.display = 'block';
    } else {
        warningElem.style.display = 'none';
    }
}
</script>

<script>
// Form clear functionality integrated directly
document.addEventListener('DOMContentLoaded', function() {
    // Clear individual tab sections
    document.querySelectorAll('.clear-tab-btn').forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab-id');
            if (confirm('Are you sure you want to clear all fields in this tab?')) {
                clearTab(tabId);
            }
        });
    });

    // Clear entire form
    document.querySelector('[data-clear-form]').addEventListener('click', function() {
        if (confirm('Are you sure you want to clear the entire form?')) {
            clearAllTabs();
        }
    });

    function clearTab(tabId) {
        const tab = document.getElementById(tabId);
        if (!tab) return;

        // Clear all inputs within the tab
        tab.querySelectorAll('input:not([readonly]), textarea').forEach(input => {
            input.value = '';
        });

        // Reset dropdowns with special handling
        const specialDropdowns = ['content_type', 'media_type', 'carrier_type', 'language', 'status'];
        tab.querySelectorAll('select').forEach(select => {
            if (specialDropdowns.includes(select.id)) {
                // Reset to first option for special dropdowns
                select.selectedIndex = 0;
            } else {
                // Clear other dropdowns
                select.value = '';
            }
        });

        // Clear checkboxes
        tab.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false;
        });

        // Reset file inputs
        tab.querySelectorAll('input[type="file"]').forEach(fileInput => {
            fileInput.value = '';
            // Reset the file input label
            const label = fileInput.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                label.textContent = label.getAttribute('data-default-text') || 'Choose file';
            }
        });

        // Preserve system information fields
        const preserveFields = ['entered_by', 'date_added', 'last_update'];
        preserveFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = field.getAttribute('value');
            }
        });
    }

    function clearAllTabs() {
        const tabs = ['title-proper', 'subject-entry', 'abstracts', 'description', 'local-info', 'publication'];
        tabs.forEach(tabId => clearTab(tabId));

        // Reset progress bar
        const progressBar = document.getElementById('formProgressBar');
        if (progressBar) {
            progressBar.style.width = '0%';
            progressBar.setAttribute('aria-valuenow', 0);
        }

        // Reset to first tab
        const firstTab = document.querySelector('#formTabs .nav-link');
        if (firstTab) {
            $(firstTab).tab('show');
        }

        // Remove 'completed' class from all tabs
        document.querySelectorAll('#formTabs .nav-link').forEach(tab => {
            tab.classList.remove('completed');
        });

        // Reset current tab index
        window.currentTabIndex = 0;
    }
});
</script>

<script>
/**
 * Form navigation and tab handling
 */
document.addEventListener("DOMContentLoaded", function() {
    // Variables to track form completion
    const totalTabs = document.querySelectorAll('#formTabs .nav-link').length;
    let completedTabs = 0;
    let currentTabIndex = 0;
    
    const tabs = document.querySelectorAll('#formTabs .nav-link');
    const tabContents = document.querySelectorAll('.tab-pane');
    const progressBar = document.getElementById('formProgressBar');
    
    // Function to update progress bar
    function updateProgressBar() {
        const progressPercentage = (currentTabIndex / (totalTabs - 1)) * 100;
        if (progressBar) {
            progressBar.style.width = progressPercentage + '%';
            progressBar.setAttribute('aria-valuenow', progressPercentage);
        }
    }
    
    // Function to validate current tab
    function validateCurrentTab() {
        const currentTab = tabs[currentTabIndex];
        const currentTabId = currentTab.getAttribute('href').substring(1);
        const currentTabPane = document.getElementById(currentTabId);
        
        let isValid = true;
        
        // Check required fields in the current tab
        const requiredFields = currentTabPane.querySelectorAll('input[required], select[required], textarea[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // If valid, mark tab as completed
        if (isValid) {
            currentTab.classList.add('completed');
            
            // Count completed tabs
            completedTabs = document.querySelectorAll('#formTabs .nav-link.completed').length;
        }
        
        return isValid;
    }
    
    // Function to navigate to the next tab
    function goToNextTab() {
        if (validateCurrentTab()) {
            if (currentTabIndex < totalTabs - 1) {
                // Go to next tab
                currentTabIndex++;
                $(tabs[currentTabIndex]).tab('show');
                updateProgressBar();
            } else {
                // We're on the last tab, submit the form
                if (confirm('Submit the book information?')) {
                    document.getElementById('bookForm').submit();
                }
            }
        } else {
            alert('Please fill in all required fields before proceeding.');
        }
    }
    
    // Function to navigate to the previous tab
    function goToPrevTab() {
        if (currentTabIndex > 0) {
            currentTabIndex--;
            $(tabs[currentTabIndex]).tab('show');
            updateProgressBar();
        }
    }
    
    // Next button click handler
    document.querySelectorAll('.next-tab').forEach(button => {
        button.addEventListener('click', function() {
            if (validateCurrentTab()) {
                const nextTabId = this.getAttribute('data-next');
                const nextTab = document.getElementById(nextTabId);
                
                // Find the index of the next tab
                tabs.forEach((tab, index) => {
                    if (tab.id === nextTabId) {
                        currentTabIndex = index;
                    }
                });
                
                // Update progress bar
                updateProgressBar();
                
                // Activate the tab with Bootstrap
                $(nextTab).tab('show');
            } else {
                alert('Please fill in all required fields before proceeding.');
            }
        });
    });
    
    // Previous button click handler
    document.querySelectorAll('.prev-tab').forEach(button => {
        button.addEventListener('click', function() {
            const prevTabId = this.getAttribute('data-prev');
            const prevTab = document.getElementById(prevTabId);
            
            // Find the index of the previous tab
            tabs.forEach((tab, index) => {
                if (tab.id === prevTabId) {
                    currentTabIndex = index;
                }
            });
            
            // Update progress bar
            updateProgressBar();
            
            // Trigger click on the previous tab
            $(prevTab).tab('show');
        });
    });
    
    // Disable direct tab clicking (completely prevent it)
    tabs.forEach((tab) => {
        tab.addEventListener('click', function(e) {
            // Always prevent the default tab switching behavior first
            e.preventDefault();
            e.stopPropagation();
            
            const clickedTabIndex = Array.from(tabs).indexOf(this);
            
            // Only allow clicking on completed tabs or the current tab
            if (tab.classList.contains('completed')) {
                // If it's a completed tab, allow navigation to it
                currentTabIndex = clickedTabIndex;
                updateProgressBar();
                
                // Use Bootstrap's tab method to show the tab
                $(this).tab('show');
            } else if (this === tabs[currentTabIndex]) {
                // Clicking on current tab - do nothing but allow it
                return false;
            } else {
                // Prevent navigation to any uncompleted tab
                alert('Please complete the current section before skipping ahead.');
            }
            return false;
        });
    });
    
    // Add subject entry
    document.getElementById('add-subject').addEventListener('click', function() {
        const subjectEntries = document.getElementById('subject-entries');
        const newEntry = document.createElement('div');
        newEntry.className = 'subject-entry card p-3 mb-3';
        newEntry.innerHTML = `
            <button type="button" class="btn btn-danger btn-sm remove-subject">
                <i class="fas fa-times"></i>
            </button>
            <div class="form-group">
                <label>Subject Category</label>
                <select class="form-control" name="subject_categories[]">
                    <option value="">Select Subject Category</option>
                    ${Array.from(document.querySelector('select[name="subject_categories[]"]').options)
                        .map(opt => `<option value="${opt.value}">${opt.textContent}</option>`)
                        .join('')}
                </select>
            </div>
            <div class="form-group">
                <label>Subject Detail</label>
                <textarea class="form-control" name="subject_paragraphs[]" rows="3"></textarea>
            </div>
        `;
        subjectEntries.appendChild(newEntry);
    });
    
    // Remove subject entry
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-subject')) {
            const subjectEntries = document.getElementById('subject-entries');
            if (subjectEntries.children.length > 1) {
                e.target.closest('.subject-entry').remove();
            } else {
                alert('At least one subject entry is required.');
            }
        }
    });
});
</script>

<!-- Form Reset Handler -->
<script>
// Check if we need to reset the form (after successful submission)
<?php if ($resetForm): ?>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Resetting form data after successful submission');
    
    // Clear all localStorage data related to the form
    localStorage.removeItem('bookFormData');
    localStorage.removeItem('formProgress');
    localStorage.removeItem('completedTabs');
    
    // Reset the form element
    document.getElementById('bookForm').reset();
    
    // Reset accession container to initial state
    const accessionContainer = document.getElementById('accessionContainer');
    if (accessionContainer) {
        const firstGroup = accessionContainer.querySelector('.accession-group');
        if (firstGroup) {
            // Clear the input values
            const accessionInput = firstGroup.querySelector('.accession-input');
            const copiesInput = firstGroup.querySelector('.copies-input');
            if (accessionInput) accessionInput.value = '';
            if (copiesInput) copiesInput.value = '1';
            
            // Remove any additional accession groups
            Array.from(accessionContainer.children).forEach((child, index) => {
                if (index > 0) child.remove();
            });
        }
    }
    
    // Clear call number container
    const callNumberContainer = document.getElementById('callNumberContainer');
    if (callNumberContainer) {
        callNumberContainer.innerHTML = '';
    }
    
    // Clear ISBN details container
    const isbnContainer = document.getElementById('isbnContainer');
    if (isbnContainer) {
        isbnContainer.innerHTML = '';
    }
    
    // Reset the progress bar
    const progressBar = document.getElementById('formProgressBar');
    if (progressBar) {
        progressBar.style.width = '0%';
        progressBar.setAttribute('aria-valuenow', 0);
    }
    
    // Remove 'completed' class from all tabs
    document.querySelectorAll('#formTabs .nav-link.completed').forEach(tab => {
        tab.classList.remove('completed');
    });
    
    // Activate the first tab
    const firstTab = document.querySelector('#formTabs .nav-link');
    if (firstTab) {
        $(firstTab).tab('show');
    }
});
<?php endif; ?>
</script>

<!-- Call Number Validation Script -->
<script>
// Add immediate call number validation and debugging
document.addEventListener('DOMContentLoaded', function() {
    // Debug call number container
    const callNumberContainer = document.getElementById('callNumberContainer');
    console.log('Call number container:', callNumberContainer);
    
    // Force call number generation after a delay if not already present
    setTimeout(function() {
        if (callNumberContainer && callNumberContainer.children.length === 0) {
            console.log('No call numbers found, manually triggering generation');
            
            // Check if accession inputs exist and have values
            const accessionInputs = document.querySelectorAll('.accession-input');
            if (accessionInputs.length > 0) {
                console.log(`Found ${accessionInputs.length} accession inputs`);
                
                // If updateISBNFields function exists, call it
                if (typeof updateISBNFields === 'function') {
                    updateISBNFields();
                    console.log('Called updateISBNFields function');
                } else {
                    console.error('updateISBNFields function not found');
                }
            } else {
                console.log('No accession inputs found yet');
            }
        } else if (callNumberContainer) {
            console.log(`Call number container has ${callNumberContainer.children.length} children`);
        }
    }, 1000);
});
</script>

<!-- Add this new script block at the end, after your other scripts but before the closing body tag -->
<script>
/**
 * Form autosave functionality with tab-specific clearing
 */
document.addEventListener("DOMContentLoaded", function() {
    const formId = 'bookForm';
    const storageKey = 'bookFormData';
    const form = document.getElementById(formId);
    
    // Function to save form data to localStorage
    function saveFormData() {
        const formData = {};
        
        // Save text inputs, textareas, and selects
        form.querySelectorAll('input:not([type="file"]), textarea, select').forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                formData[input.name + '-' + input.value] = input.checked;
            } else if (input.type === 'select-multiple') {
                formData[input.name] = Array.from(input.selectedOptions).map(option => option.value);
            } else {
                formData[input.name] = input.value;
            }
        });
        
        // Save accession groups data with more details
        const accessionGroups = [];
        document.querySelectorAll('.accession-group').forEach(group => {
            const accessionInput = group.querySelector('.accession-input');
            const copiesInput = group.querySelector('.copies-input');
            const isbnInput = group.querySelector('input[name^="isbn"]');
            const seriesInput = group.querySelector('input[name^="series"]');
            const volumeInput = group.querySelector('input[name^="volume"]');
            const editionInput = group.querySelector('input[name^="edition"]');
            
            if (accessionInput && copiesInput) {
                accessionGroups.push({
                    accession: accessionInput.value,
                    copies: copiesInput.value,
                    isbn: isbnInput ? isbnInput.value : '',
                    series: seriesInput ? seriesInput.value : '',
                    volume: volumeInput ? volumeInput.value : '',
                    edition: editionInput ? editionInput.value : ''
                });
            }
        });
        formData['accessionGroups'] = accessionGroups;

        // Save call numbers and shelf locations
        const callNumberData = [];
        const callNumberContainers = document.querySelectorAll('#callNumberContainer .input-group');
        callNumberContainers.forEach(container => {
            const callNumberInput = container.querySelector('.call-number-input');
            const shelfLocationSelect = container.querySelector('.shelf-location-select');
            const copyNumberInput = container.querySelector('.copy-number-input');
            const accessionLabel = container.querySelector('.input-group-text');
            
            if (callNumberInput && shelfLocationSelect) {
                callNumberData.push({
                    callNumber: callNumberInput.value,
                    shelfLocation: shelfLocationSelect.value,
                    copyNumber: copyNumberInput ? copyNumberInput.value : '',
                    accessionLabel: accessionLabel ? accessionLabel.textContent : ''
                });
            }
        });
        formData['callNumberData'] = callNumberData;
        
        // Save current active tab
        const activeTab = document.querySelector('#formTabs .nav-link.active');
        if (activeTab) {
            formData['activeTab'] = activeTab.id;
        }
        
        // Save progress bar state
        const progressBar = document.getElementById('formProgressBar');
        formData['progressValue'] = progressBar.style.width;
        
        // Save completed tabs
        const completedTabs = Array.from(document.querySelectorAll('#formTabs .nav-link.completed')).map(tab => tab.id);
        formData['completedTabs'] = completedTabs;
        
        localStorage.setItem(storageKey, JSON.stringify(formData));
    }

    // Function to clear a specific tab's data without confirmation
    function clearTabData(tabId) {
        const tabPane = document.querySelector(`#${tabId}`);
        if (!tabPane) return;

        // Clear inputs within the tab
        tabPane.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else if (input.type === 'select-multiple') {
                input.selectedIndex = -1;
                // Clear associated preview if exists
                const previewId = input.id + 'Preview';
                const preview = document.getElementById(previewId);
                if (preview) preview.innerHTML = '';
            } else if (input.type === 'file') {
                input.value = '';
                // Reset associated label
                const label = input.nextElementSibling;
                if (label && label.classList.contains('custom-file-label')) {
                    label.textContent = 'Choose file';
                }
            } else {
                input.value = '';
            }
        });

        // Remove completed status from tab
        const tabButton = document.querySelector(`[href="#${tabId}"]`);
        if (tabButton) tabButton.classList.remove('completed');

        // If this is the form-wide clear
        if (tabId === 'all') {
            // Clear progress data
            localStorage.removeItem('formProgress');
            localStorage.removeItem('completedTabs');
            
            // Reset UI progress
            const progressBar = document.getElementById('formProgressBar');
            if (progressBar) {
                progressBar.style.width = '0%';
                progressBar.setAttribute('aria-valuenow', 0);
            }

            // Remove completed status from all tabs
            document.querySelectorAll('#formTabs .nav-link').forEach(tab => {
                tab.classList.remove('completed');
            });

            // Clear accession groups
            const accessionContainer = document.getElementById('accessionContainer');
            if (accessionContainer) {
                const firstGroup = accessionContainer.querySelector('.accession-group');
                if (firstGroup) {
                    accessionContainer.innerHTML = '';
                    accessionContainer.appendChild(firstGroup);
                }
            }

            // Clear call numbers
            const callNumberContainer = document.getElementById('callNumberContainer');
            if (callNumberContainer) {
                callNumberContainer.innerHTML = '';
            }
        }

        // Save the updated form state
        saveFormData();
    }

    // Bind clear tab buttons
    document.querySelectorAll('.clear-tab-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const tabId = e.currentTarget.dataset.tabId;
            clearTabData(tabId);
        });
    });
    
    // Function to restore form data from localStorage
    function restoreFormData() {
        const savedData = localStorage.getItem(storageKey);
        if (!savedData) return;
        
        const formData = JSON.parse(savedData);
        
        // Restore text inputs, textareas, and selects
        form.querySelectorAll('input:not([type="file"]), textarea, select').forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                if (formData[input.name + '-' + input.value]) {
                    input.checked = true;
                }
            } else if (input.type === 'select-multiple' && formData[input.name]) {
                const values = formData[input.name];
                Array.from(input.options).forEach(option => {
                    option.selected = values.includes(option.value);
                });
                
                // Update the preview for multi-selects
                if (input.id === 'authorSelect') updatePreview('authorSelect', 'authorPreview');
                if (input.id === 'coAuthorsSelect') updatePreview('coAuthorsSelect', 'coAuthorsPreview');
                if (input.id === 'editorsSelect') updatePreview('editorsSelect', 'editorsPreview');
            } else if (formData[input.name] !== undefined) {
                input.value = formData[input.name];
            }
        });

        // Restore accession groups with details
        if (formData['accessionGroups']) {
            const accessionContainer = document.getElementById('accessionContainer');
            if (accessionContainer) {
                accessionContainer.innerHTML = ''; // Clear existing groups
                formData['accessionGroups'].forEach((group, index) => {
                    const groupElement = createAccessionGroup(index + 1);
                    groupElement.querySelector('.accession-input').value = group.accession;
                    groupElement.querySelector('.copies-input').value = group.copies;
                    accessionContainer.appendChild(groupElement);
                });
                
                // After creating all groups, update ISBN fields
                if (typeof updateISBNFields === 'function') {
                    updateISBNFields();
                    
                    // Then restore the saved values for the detail fields
                    setTimeout(() => {
                        const groups = document.querySelectorAll('.accession-group');
                        formData['accessionGroups'].forEach((groupData, index) => {
                            if (index < groups.length) {
                                const group = groups[index];
                                const isbnInput = group.querySelector('input[name^="isbn"]');
                                const seriesInput = group.querySelector('input[name^="series"]');
                                const volumeInput = group.querySelector('input[name^="volume"]');
                                const editionInput = group.querySelector('input[name^="edition"]');
                                
                                if (isbnInput) isbnInput.value = groupData.isbn || '';
                                if (seriesInput) seriesInput.value = groupData.series || '';
                                if (volumeInput) volumeInput.value = groupData.volume || '';
                                if (editionInput) editionInput.value = groupData.edition || '';
                            }
                        });
                    }, 100);
                }
            }
        }

        // Restore call numbers and shelf locations
        if (formData['callNumberData'] && formData['callNumberData'].length > 0) {
            const callNumberContainer = document.getElementById('callNumberContainer');
            if (callNumberContainer && callNumberContainer.children.length === 0) {
                // Only restore if call number fields haven't been generated yet
                formData['callNumberData'].forEach(data => {
                    const callNumberDiv = document.createElement('div');
                    callNumberDiv.className = 'input-group mb-2';
                    
                    const accessionLabel = document.createElement('span');
                    accessionLabel.className = 'input-group-text';
                    accessionLabel.textContent = data.accessionLabel || 'Accession';
                    
                    const callNumberInput = document.createElement('input');
                    callNumberInput.type = 'text';
                    callNumberInput.className = 'form-control call-number-input';
                    callNumberInput.name = 'call_number[]';
                    callNumberInput.value = data.callNumber || '';
                    callNumberInput.placeholder = 'Enter call number';
                    
                    // Create copy number label and input
                    const copyNumberLabel = document.createElement('span');
                    copyNumberLabel.className = 'input-group-text';
                    copyNumberLabel.textContent = 'Copy #';
                    
                    const copyNumberInput = document.createElement('input');
                    copyNumberInput.type = 'number';
                    copyNumberInput.className = 'form-control copy-number-input';
                    copyNumberInput.name = 'copy_number[]';
                    copyNumberInput.min = '1';
                    copyNumberInput.value = data.copyNumber || '';
                    copyNumberInput.style.width = '70px';
                    
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
                        if (value === data.shelfLocation) {
                            option.selected = true;
                        }
                        shelfLocationSelect.appendChild(option);
                    });
                    
                    // Apply the new order of elements
                    callNumberDiv.appendChild(accessionLabel);
                    callNumberDiv.appendChild(callNumberInput);
                    callNumberDiv.appendChild(copyNumberLabel);
                    callNumberDiv.appendChild(copyNumberInput);
                    callNumberDiv.appendChild(shelfLocationSelect);
                    callNumberContainer.appendChild(callNumberDiv);
                });
            } else if (callNumberContainer) {
                // If call number fields exist but empty (like after updateISBNFields), fill them in
                setTimeout(() => {
                    const callNumberContainers = callNumberContainer.querySelectorAll('.input-group');
                    formData['callNumberData'].forEach((data, index) => {
                        if (index < callNumberContainers.length) {
                            const container = callNumberContainers[index];
                            const callNumberInput = container.querySelector('.call-number-input');
                            const shelfLocationSelect = container.querySelector('.shelf-location-select');
                            const copyNumberInput = container.querySelector('.copy-number-input');
                            
                            if (callNumberInput) callNumberInput.value = data.callNumber || '';
                            if (shelfLocationSelect) shelfLocationSelect.value = data.shelfLocation || '';
                            if (copyNumberInput) copyNumberInput.value = data.copyNumber || '';
                        }
                    });
                }, 200);
            }
        }
        
        // Restore custom file input labels
        document.querySelectorAll('.custom-file-input').forEach(input => {
            const label = input.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                if (formData[input.name + '-label']) {
                    label.textContent = formData[input.name + '-label'];
                }
            }
        });
        
        // Restore active tab
        if (formData['activeTab']) {
            const tabToActivate = document.getElementById(formData['activeTab']);
            if (tabToActivate) {
                $(tabToActivate).tab('show');
            }
        }
        
        // Restore progress bar
        if (formData['progressValue']) {
            const progressBar = document.getElementById('formProgressBar');
            if (progressBar) {
                progressBar.style.width = formData['progressValue'];
                progressBar.setAttribute('aria-valuenow', parseInt(formData['progressValue']));
            }
        }
        
        // Restore completed tabs with improved selector handling
        if (formData['completedTabs'] && Array.isArray(formData['completedTabs'])) {
            formData['completedTabs'].forEach(tabId => {
                // Try different selector approaches to find the tab
                let tab = document.querySelector(`a#${tabId}`);
                if (!tab) tab = document.querySelector(`a[id="${tabId}"]`);
                if (!tab) tab = document.querySelector(`#formTabs .nav-link[href="#${tabId.replace('tab', 'proper')}"]`);
                if (!tab) tab = document.querySelector(`#formTabs .nav-link[href="#${tabId}"]`);
                if (!tab) tab = document.querySelector(`#formTabs .nav-link[id="${tabId}"]`);
                
                if (tab) {
                    tab.classList.add('completed');
                }
            });
        }
        
        // Validate all tabs on initial load to mark them as completed if needed
        validateAllTabs();
    }

    // Function to completely clear all form data from localStorage 
    window.clearAllFormData = function() {
        localStorage.removeItem(storageKey);
        localStorage.removeItem('formProgress');
        localStorage.removeItem('completedTabs');
        
        // Reset the form element
        if (form) form.reset();
        
        // Reset progress bar
        const progressBar = document.getElementById('formProgressBar');
        if (progressBar) {
            progressBar.style.width = '0%';
            progressBar.setAttribute('aria-valuenow', 0);
        }
        
        // Remove completed status from all tabs
        document.querySelectorAll('#formTabs .nav-link').forEach(tab => {
            tab.classList.remove('completed');
        });
        
        // Reset accession groups
        const accessionContainer = document.getElementById('accessionContainer');
        if (accessionContainer) {
            const firstGroup = accessionContainer.querySelector('.accession-group');
            if (firstGroup) {
                // Clear inputs
                const accessionInput = firstGroup.querySelector('.accession-input');
                const copiesInput = firstGroup.querySelector('.copies-input');
                if (accessionInput) accessionInput.value = '';
                if (copiesInput) copiesInput.value = '1';
                
                // Keep only the first group
                accessionContainer.innerHTML = '';
                accessionContainer.appendChild(firstGroup);
            }
        }
        
        // Clear call numbers and ISBN fields
        const callNumberContainer = document.getElementById('callNumberContainer');
        if (callNumberContainer) {
            callNumberContainer.innerHTML = '';
        }
        
        const isbnContainer = document.getElementById('isbnContainer');
        if (isbnContainer) {
            isbnContainer.innerHTML = '';
        }
        
        // Activate first tab
        const firstTab = document.querySelector('#formTabs .nav-link');
        if (firstTab && typeof $(firstTab).tab === 'function') {
            $(firstTab).tab('show');
        }
        
        console.log('All form data has been cleared');
    };

    // Function to validate all tabs and mark them as completed if all required fields are filled
    function validateAllTabs() {
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabPanes.forEach(pane => {
            const tabId = pane.id;
            const tab = document.querySelector(`a[href="#${tabId}"]`);
            if (!tab) return;
            
            // Check if all required fields in this tab are filled
            const requiredFields = pane.querySelectorAll('input[required], select[required], textarea[required]');
            let allFilled = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    allFilled = false;
                }
            });
            
            // If all required fields are filled, mark tab as completed
            if (allFilled && requiredFields.length > 0) {
                tab.classList.add('completed');
            }
        });
    }

    // Save form data periodically
    const autoSaveInterval = setInterval(saveFormData, 1000);
    
    // Save on input changes
    form.addEventListener('input', saveFormData);
    
    // Save on tab changes
    document.querySelectorAll('#formTabs .nav-link').forEach(tab => {
        tab.addEventListener('shown.bs.tab', saveFormData);
    });
    
    // Restore form data on page load
    restoreFormData();
    
    // Validate tabs after a short delay to ensure all fields are properly loaded
    setTimeout(validateAllTabs, 500);

    // Helper function for updating multi-select previews
    function updatePreview(selectId, previewId) {
        const select = document.getElementById(selectId);
        const preview = document.getElementById(previewId);
        if (!select || !preview) return;
        
        const selectedOptions = Array.from(select.selectedOptions).map(option => {
            return `<span class="badge bg-secondary mr-1 text-white">${option.text} <i class="fas fa-times remove-icon" data-value="${option.value}"></i></span>`;
        });
        preview.innerHTML = selectedOptions.join(' ');
    }

    // Helper function to create an accession group
    function createAccessionGroup(copyNumber) {
        const div = document.createElement('div');
        div.className = 'accession-group mb-3';
        div.innerHTML = `
            <div class="row">
                <div class="col-md-7">
                    <div class="form-group">
                        <label>Accession (Copy ${copyNumber})</label>
                        <input type="text" class="form-control accession-input" name="accession[]" 
                            placeholder="e.g., 2023-0001" required>
                        <small class="text-muted">Format: YYYY-NNNN</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Number of Copies</label>
                        <input type="number" class="form-control copies-input" name="number_of_copies[]" min="1" value="1" required>
                        <small class="text-muted">Auto-increments accession</small>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-center justify-content-center">
                    ${copyNumber > 1 ? '<button type="button" class="btn btn-danger btn-sm remove-accession"><i class="fas fa-trash"></i> Remove</button>' : ''}
                </div>
            </div>
            
            <!-- Details section will be populated by updateISBNFields -->
            <div class="accession-details"></div>
        `;
        return div;
    }
});
</script>

<!-- Add this at the end, just before the closing </body> tag -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add direct button handler for generating call numbers
    document.getElementById('generateCallNumbersBtn').addEventListener('click', function() {
        console.log('Manual call number generation requested');
        if (typeof forceGenerateCallNumbers === 'function') {
            forceGenerateCallNumbers();
        } else if (typeof updateISBNFields === 'function') {
            updateISBNFields();
        } else {
            alert('Call number generation functions not found. Please refresh the page.');
        }
    });
    
    // Also trigger when clicking on the Local Information tab
    document.getElementById('local-info-tab').addEventListener('shown.bs.tab', function() {
        console.log('Local Info tab activated, checking call numbers');
        setTimeout(function() {
            const callNumberContainer = document.getElementById('callNumberContainer');
            if (callNumberContainer && (!callNumberContainer.children.length || 
                (callNumberContainer.children.length === 1 && callNumberContainer.querySelector('.alert')))) {
                console.log('Call numbers not found or only alert message present');
                // Try both methods
                if (typeof forceGenerateCallNumbers === 'function') {
                    forceGenerateCallNumbers();
                } else if (typeof updateISBNFields === 'function') {
                    updateISBNFields();
                }
            }
        }, 200);
    });
    
    // Initial check - if accession inputs have values but no call numbers, generate them
    setTimeout(function() {
        const accessionInputs = document.querySelectorAll('.accession-input');
        const callNumberContainer = document.getElementById('callNumberContainer');
        
        if (accessionInputs.length > 0 && accessionInputs[0].value && 
            callNumberContainer && (!callNumberContainer.children.length || 
            (callNumberContainer.children.length === 1 && callNumberContainer.querySelector('.alert')))) {
            console.log('Detected accession input with value but no call numbers');
            // Try direct function call
            if (typeof forceGenerateCallNumbers === 'function') {
                forceGenerateCallNumbers();
            }
        }
    }, 1000);
});
</script>

<script>
// Confirm formatted call numbers are being used before submission
document.getElementById('bookForm').addEventListener('submit', function(e) {
    const callNumberInputs = document.querySelectorAll('.call-number-input');
    const previewElements = document.querySelectorAll('.call-number-preview');
    
    if (callNumberInputs.length > 0 && previewElements.length > 0) {
        // Check if any preview is different from input value
        let differentFormatDetected = false;
        callNumberInputs.forEach((input, index) => {
            const preview = previewElements[index]?.textContent?.replace('→ ', '') || '';
            if (preview && input.value !== preview && !input.dataset.formattedCallNumber) {
                differentFormatDetected = true;
                // Set the formatted value for submission
                input.value = preview;
            }
        });
        
        if (differentFormatDetected) {
            console.log('Formatted call numbers applied before submission');
        }
    }
});
</script>

<!-- Embedded accession management script (formerly accession-management.js) -->
<script>
/**
 * Accession management functionality
 */
document.addEventListener("DOMContentLoaded", function() {
    // Initialize call number container on page load
    updateISBNFields();
    
    // Consolidated event delegation for buttons
    document.addEventListener('click', function(e) {
        // Handle add accession button click
        if (e.target.closest('.add-accession')) {
            addAccessionGroup();
            return;
        }
        
        // Handle remove accession button click
        const removeButton = e.target.closest('.remove-accession');
        if (removeButton) {
            const accessionContainer = document.getElementById('accessionContainer');
            if (accessionContainer.children.length > 1) {
                // Store values from existing detail sections before removal
                const valuesMap = saveDetailValues();
                
                // Remove the accession group
                removeButton.closest('.accession-group').remove();
                
                // Update labels and call numbers
                updateAccessionLabels();
                updateCallNumbers();
                
                // Restore the saved values
                restoreDetailValues(valuesMap);
            } else {
                alert('At least one accession group is required.');
            }
        }
    });
    
    // Add event listener for copy number input changes
    document.addEventListener('input', function(e) {
        if (e.target && e.target.classList.contains('copy-number-input')) {
            // Save the updated copy number to ensure it persists
            if (typeof saveFormData === 'function') {
                setTimeout(saveFormData, 100);
            }
        }
        
        // Existing event listeners for accession changes
        if (e.target && (e.target.classList.contains('copies-input') || 
                        e.target.classList.contains('accession-input'))) {
            updateISBNFields();
        }
        
        // Add input validation for numbers only
        if (e.target && e.target.classList.contains('accession-input')) {
            e.target.value = e.target.value.replace(/\D/g, ''); // Remove non-digits
        }
        
        // Validate ISBN format if needed
        if (e.target && e.target.name === 'isbn[]') {
            // Optional: Add ISBN validation logic here
            // e.target.value = e.target.value.replace(/[^\d-]/g, '');
        }
    });
    
    // Add event listener for cascading updates for call numbers
    document.addEventListener('input', function(e) {
        if (e.target && e.target.classList.contains('call-number-input')) {
            const callNumberInputs = document.querySelectorAll('.call-number-input');
            const index = Array.from(callNumberInputs).indexOf(e.target);
            
            // Get the base call number
            const baseCallNumber = e.target.value.trim();
            
            // Format and update all subsequent call numbers
            for (let i = index; i < callNumberInputs.length; i++) {
                // Format will be applied by the formatCallNumberDisplay function
                callNumberInputs[i].value = baseCallNumber;
                
                // Format the complete call number display for this input
                formatCallNumberDisplay(callNumberInputs[i]);
            }
        }
    });
    
    // Ensure call numbers are formatted when shelf location changes
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('shelf-location-select')) {
            const shelfLocationSelects = document.querySelectorAll('.shelf-location-select');
            const index = Array.from(shelfLocationSelects).indexOf(e.target);
            
            for (let i = index; i < shelfLocationSelects.length; i++) {
                shelfLocationSelects[i].value = shelfLocationSelects[index].value;
                
                // Update call number format when shelf location changes
                const callNumberInput = shelfLocationSelects[i].closest('.input-group').querySelector('.call-number-input');
                if (callNumberInput) {
                    formatCallNumberDisplay(callNumberInput);
                }
            }
        }
    });
    
    // Add event listeners for publication year, volume, and copy number changes
    document.addEventListener('change', function(e) {
        if (e.target && (e.target.id === 'publish_date' || e.target.name === 'volume[]' || e.target.classList.contains('copy-number-input'))) {
            // Update all call number displays when these fields change
            document.querySelectorAll('.call-number-input').forEach(input => {
                formatCallNumberDisplay(input);
            });
        }
    });
    
    // Check if we need to manually trigger the call number creation on initial page load
    setTimeout(function() {
        // If call number container is empty but we have accession groups, update the call numbers
        const callNumberContainer = document.getElementById('callNumberContainer');
        const accessionGroups = document.querySelectorAll('.accession-group');
        
        if (callNumberContainer && callNumberContainer.children.length === 0 && accessionGroups.length > 0) {
            console.log('Manually triggering call number creation for initial load');
            updateISBNFields();
            
            // If that fails, try the direct generator
            setTimeout(function() {
                if (callNumberContainer.children.length === 0 && typeof generateCallNumbersDirectly === 'function') {
                    generateCallNumbersDirectly();
                }
            }, 300);
        }
    }, 300);
});

// Create data attributes for easier form processing
function updateISBNFields() {
    console.log('Running updateISBNFields function with direct DOM manipulation');
    
    // Save existing values first
    const valuesMap = saveDetailValues();
    
    // IMPORTANT: Save copy number values before regenerating
    const copyNumberValues = {};
    document.querySelectorAll('.copy-number-input').forEach((input, index) => {
        copyNumberValues[index] = input.value;
    });
    
    const isbnContainer = document.getElementById('isbnContainer');
    const callNumberContainer = document.getElementById('callNumberContainer');
    
    if (!callNumberContainer) {
        console.error('Call number container not found!');
        alert('Error: Call number container not found. Please refresh the page.');
        return;
    }
    
    // Always clear the containers to ensure fresh content
    console.log('Clearing containers...');
    isbnContainer.innerHTML = '';
    callNumberContainer.innerHTML = '';
    
    // Get all accession groups
    const accessionGroups = document.querySelectorAll('.accession-group');
    console.log(`Found ${accessionGroups.length} accession groups`);
    
    if (accessionGroups.length === 0) {
        callNumberContainer.innerHTML = '<div class="alert alert-warning">No accession groups found. Please add an accession number first.</div>';
        return;
    }
    
    // Track details across groups for comparison
    let detailsGroups = [];
    let totalCopiesByDetails = {};
    let startingCopyNumber = {};
    
    // First pass: collect all details
    accessionGroups.forEach((group, groupIndex) => {
        const accessionInput = group.querySelector('.accession-input').value;
        const copiesCount = parseInt(group.querySelector('.copies-input').value) || 1;
        
        // First remove any existing details section
        const existingDetails = group.querySelector('.accession-details');
        if (existingDetails) {
            existingDetails.remove();
        }
        
        // Create details section under each accession group
        const detailsDiv = document.createElement('div');
        detailsDiv.className = 'accession-details mt-3';
        
        // Add heading for the details
        const detailsLabel = document.createElement('h6');
        detailsLabel.className = 'text-muted mb-3';
        detailsLabel.textContent = `Details for Accession Group ${groupIndex + 1}`;
        detailsDiv.appendChild(detailsLabel);

        // Create a row for ISBN, series, volume, and edition inputs
        const rowDiv = document.createElement('div');
        rowDiv.className = 'row mb-3';

        // Create ISBN input
        const isbnDiv = document.createElement('div');
        isbnDiv.className = 'col-md-3';
        
        const isbnLabel = document.createElement('small');
        isbnLabel.className = 'form-text text-muted';
        isbnLabel.textContent = 'ISBN';
        isbnDiv.appendChild(isbnLabel);
        
        const isbnInput = document.createElement('input');
        isbnInput.type = 'text';
        isbnInput.className = 'form-control';
        isbnInput.name = 'isbn[]';
        isbnInput.placeholder = `ISBN`;
        isbnInput.dataset.groupIndex = groupIndex; // Add data attribute for identification
        
        isbnDiv.appendChild(isbnInput);
        rowDiv.appendChild(isbnDiv);

        // Create series input
        const seriesDiv = document.createElement('div');
        seriesDiv.className = 'col-md-3';

        const seriesLabel = document.createElement('small');
        seriesLabel.className = 'form-text text-muted';
        seriesLabel.textContent = 'Series';
        seriesDiv.appendChild(seriesLabel);
        
        const seriesInput = document.createElement('input');
        seriesInput.type = 'text';
        seriesInput.className = 'form-control';
        seriesInput.name = 'series[]';
        seriesInput.placeholder = `Series`;
        seriesInput.dataset.groupIndex = groupIndex; // Add data attribute for identification
        
        seriesDiv.appendChild(seriesInput);
        rowDiv.appendChild(seriesDiv);

        // Create volume input
        const volumeDiv = document.createElement('div');
        volumeDiv.className = 'col-md-3';

        const volumeLabel = document.createElement('small');
        volumeLabel.className = 'form-text text-muted';
        volumeLabel.textContent = 'Volume';
        volumeDiv.appendChild(volumeLabel);
        
        const volumeInput = document.createElement('input');
        volumeInput.type = 'text';
        volumeInput.className = 'form-control';
        volumeInput.name = 'volume[]';
        volumeInput.placeholder = `Volume`;
        volumeInput.dataset.groupIndex = groupIndex; // Add data attribute for identification
        
        volumeDiv.appendChild(volumeInput);
        rowDiv.appendChild(volumeDiv);

        // Create edition input
        const editionDiv = document.createElement('div');
        editionDiv.className = 'col-md-3';

        const editionLabel = document.createElement('small');
        editionLabel.className = 'form-text text-muted';
        editionLabel.textContent = 'Edition';
        editionDiv.appendChild(editionLabel);
        
        const editionInput = document.createElement('input');
        editionInput.type = 'text';
        editionInput.className = 'form-control';
        editionInput.name = 'edition[]';
        editionInput.placeholder = `Edition`;
        editionInput.dataset.groupIndex = groupIndex; // Add data attribute for identification
        
        editionDiv.appendChild(editionInput);
        rowDiv.appendChild(editionDiv);

        detailsDiv.appendChild(rowDiv);
        
        // Add the details section after the accession group's row
        const accessionRow = group.querySelector('.row');
        accessionRow.after(detailsDiv);
        
        // Store this group's details for later comparison
        detailsGroups.push({
            groupIndex,
            isbn: isbnInput.value || '',
            series: seriesInput.value || '',
            volume: volumeInput.value || '',
            edition: editionInput.value || '',
            accession: accessionInput,
            copies: copiesCount
        });
    });
    
    // Track overall copy index across all groups
    let globalCopyIndex = 0;
    
    // Second pass: determine copy numbers and create call number inputs
    detailsGroups.forEach((groupDetails, index) => {
        // Create a key for this group's details
        const detailsKey = `${groupDetails.isbn}|${groupDetails.series}|${groupDetails.volume}|${groupDetails.edition}`;
        
        // Check if we've seen this set of details before
        if (totalCopiesByDetails[detailsKey] === undefined) {
            // First time seeing these details, start copy number at 1
            totalCopiesByDetails[detailsKey] = 0;
            startingCopyNumber[detailsKey] = 1;
        }
        
        // Get the starting copy number for this group
        const startCopy = startingCopyNumber[detailsKey] + totalCopiesByDetails[detailsKey];
        
        // Update the total copies for this set of details
        totalCopiesByDetails[detailsKey] += groupDetails.copies;
        
        // Create heading for this accession group's call numbers
        const groupHeader = document.createElement('div');
        groupHeader.className = 'mb-2 text-muted small font-weight-bold';
        groupHeader.innerHTML = `Accession Group ${index + 1}: ${groupDetails.accession}`;
        callNumberContainer.appendChild(groupHeader);
        
        // Create call number inputs for this group
        for (let i = 0; i < groupDetails.copies; i++) {
            const currentAccession = calculateAccession(groupDetails.accession, i);
            // Use saved copy number if available, otherwise use incremental global index
            const copyNumber = copyNumberValues[globalCopyIndex] || (globalCopyIndex + 1);
            
            const callNumberDiv = document.createElement('div');
            callNumberDiv.className = 'input-group mb-2';
            callNumberDiv.dataset.accessionGroup = index;
            
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
            
            // Create copy number label and input (positioned between call number and shelf location)
            const copyNumberLabel = document.createElement('span');
            copyNumberLabel.className = 'input-group-text';
            copyNumberLabel.textContent = 'Copy #';
            
            const copyNumberInput = document.createElement('input');
            copyNumberInput.type = 'number';
            copyNumberInput.className = 'form-control copy-number-input';
            copyNumberInput.name = 'copy_number[]';
            copyNumberInput.min = '1';
            copyNumberInput.value = copyNumber;
            copyNumberInput.style.width = '70px';
            
            // New order of elements in the input group
            callNumberDiv.appendChild(accessionLabel);
            callNumberDiv.appendChild(callNumberInput); // Call number input
            callNumberDiv.appendChild(copyNumberLabel);
            callNumberDiv.appendChild(copyNumberInput); // Copy number input
            callNumberDiv.appendChild(shelfLocationSelect); // Shelf location select
            callNumberContainer.appendChild(callNumberDiv);
            
            // Increment the global copy index
            globalCopyIndex++;
        }
    });
    
    // After creating call number fields, log the number created:
    console.log(`Created ${callNumberContainer.children.length} call number entries`);
    
    // After all processing, restore saved values
    restoreDetailValues(valuesMap);
    
    // Trigger form autosave to persist the generated call numbers
    if (typeof saveFormData === 'function') {
        setTimeout(saveFormData, 100);
    }

    // After creating all call number fields, ensure visibility:
    if (callNumberContainer.children.length === 0) {
        console.error('Failed to create call number fields during normal process');
        callNumberContainer.innerHTML = '<div class="alert alert-danger">Error: Call number generation failed. Please try again or refresh the page.</div>';
    } else {
        console.log(`Successfully created ${callNumberContainer.children.length} call number elements`);
    }
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

// Function to add a new accession group
function addAccessionGroup() {
    const accessionContainer = document.getElementById('accessionContainer');
    const groups = accessionContainer.querySelectorAll('.accession-group');
    const newIndex = groups.length;
    
    // Create new accession group
    const newGroup = document.createElement('div');
    newGroup.className = 'accession-group mb-3';
    newGroup.innerHTML = `
        <div class="row">
            <div class="col-md-7">
                <div class="form-group">
                    <label>Accession (Copy ${newIndex + 1})</label>
                    <input type="text" class="form-control accession-input" name="accession[]" 
                        placeholder="e.g., 2023-0001" required>
                    <small class="text-muted">Format: YYYY-NNNN</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Number of Copies</label>
                    <input type="number" class="form-control copies-input" name="number_of_copies[]" min="1" value="1" required>
                    <small class="text-muted">Auto-increments accession</small>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-center justify-content-center">
                <button type="button" class="btn btn-danger btn-sm remove-accession">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
        </div>
        
        <!-- Details section - initially empty, will be populated by updateISBNFields -->
        <div class="accession-details"></div>
    `;
    
    accessionContainer.appendChild(newGroup);
    
    // Save current values
    const valuesMap = saveDetailValues();
    
    // Update labels and regenerate details
    updateAccessionLabels();
    updateISBNFields();
    
    // Restore saved values 
    restoreDetailValues(valuesMap);
}

// Initialize the first accession group with its own details section
function initializeAccessionGroups() {
    const firstGroup = document.querySelector('.accession-group');
    if (firstGroup) {
        // Remove any existing details to avoid duplicates
        const existingDetails = firstGroup.querySelector('.accession-details');
        if (existingDetails) {
            existingDetails.innerHTML = '';
        } else {
            // Create the container if it doesn't exist
            const detailsDiv = document.createElement('div');
            detailsDiv.className = 'accession-details';
            firstGroup.appendChild(detailsDiv);
        }
        
        // Let updateISBNFields populate the details section
        updateISBNFields();
    }
}

// Update accession labels after removal
function updateAccessionLabels() {
    const groups = document.querySelectorAll('.accession-group');
    groups.forEach((group, index) => {
        const label = group.querySelector('label');
        if (label) {
            label.textContent = `Accession (Copy ${index + 1})`;
        }
    });
}

// Create a function to save the current values of all detail fields
function saveDetailValues() {
    const valuesMap = {};
    
    // Get all accession groups
    const accessionGroups = document.querySelectorAll('.accession-group');
    
    accessionGroups.forEach((group, index) => {
        // Find inputs in this group's details section
        const isbnInput = group.querySelector('input[name^="isbn"]');
        const seriesInput = group.querySelector('input[name^="series"]');
        const volumeInput = group.querySelector('input[name^="volume"]');
        const editionInput = group.querySelector('input[name^="edition"]');
        
        if (isbnInput && seriesInput && volumeInput && editionInput) {
            valuesMap[index] = {
                isbn: isbnInput.value,
                series: seriesInput.value,
                volume: volumeInput.value,
                edition: editionInput.value
            };
        }
    });
    
    return valuesMap;
}

// Function to restore values after operations that might clear them
function restoreDetailValues(valuesMap) {
    const accessionGroups = document.querySelectorAll('.accession-group');
    
    accessionGroups.forEach((group, index) => {
        // Only restore if we have saved values for this index
        if (valuesMap[index]) {
            const isbnInput = group.querySelector('input[name^="isbn"]');
            const seriesInput = group.querySelector('input[name^="series"]');
            const volumeInput = group.querySelector('input[name^="volume"]');
            const editionInput = group.querySelector('input[name^="edition"]');
            
            if (isbnInput) isbnInput.value = valuesMap[index].isbn;
            if (seriesInput) seriesInput.value = valuesMap[index].series;
            if (volumeInput) volumeInput.value = valuesMap[index].volume;
            if (editionInput) editionInput.value = valuesMap[index].edition;
        }
    });
}

// Update only the call number container without affecting details
function updateCallNumbers() {
    const callNumberContainer = document.getElementById('callNumberContainer');
    callNumberContainer.innerHTML = '';
    
    // Get all accession groups
    const accessionGroups = document.querySelectorAll('.accession-group');
    
    // Track details across groups for comparison
    let detailsGroups = [];
    let totalCopiesByDetails = {};
    let startingCopyNumber = {};
    
    // First pass: collect all details
    accessionGroups.forEach((group, groupIndex) => {
        const accessionInput = group.querySelector('.accession-input').value;
        const copiesCount = parseInt(group.querySelector('.copies-input').value) || 1;
        
        const isbnInput = group.querySelector('input[name^="isbn"]');
        const seriesInput = group.querySelector('input[name^="series"]');
        const volumeInput = group.querySelector('input[name^="volume"]');
        const editionInput = group.querySelector('input[name^="edition"]');
        
        // Store this group's details for later comparison
        detailsGroups.push({
            groupIndex,
            isbn: isbnInput ? isbnInput.value || '' : '',
            series: seriesInput ? seriesInput.value || '' : '',
            volume: volumeInput ? volumeInput.value || '' : '',
            edition: editionInput ? editionInput.value || '' : '',
            accession: accessionInput,
            copies: copiesCount
        });
    });
    
    // Second pass: determine copy numbers and create call number inputs
    detailsGroups.forEach((groupDetails, index) => {
        // Create a key for this group's details
        const detailsKey = `${groupDetails.isbn}|${groupDetails.series}|${groupDetails.volume}|${groupDetails.edition}`;
        
        // Check if we've seen this set of details before
        if (totalCopiesByDetails[detailsKey] === undefined) {
            // First time seeing these details, start copy number at 1
            totalCopiesByDetails[detailsKey] = 0;
            startingCopyNumber[detailsKey] = 1;
        }
        
        // Get the starting copy number for this group
        const startCopy = startingCopyNumber[detailsKey] + totalCopiesByDetails[detailsKey];
        
        // Update the total copies for this set of details
        totalCopiesByDetails[detailsKey] += groupDetails.copies;
        
        // Create heading for this accession group's call numbers
        const groupHeader = document.createElement('div');
        groupHeader.className = 'mb-2 text-muted small font-weight-bold';
        groupHeader.innerHTML = `Accession Group ${index + 1}: ${groupDetails.accession}`;
        callNumberContainer.appendChild(groupHeader);
        
        // Create call number inputs for this group
        for (let i = 0; i < groupDetails.copies; i++) {
            const currentAccession = calculateAccession(groupDetails.accession, i);
            // Start copy numbers at 1 for each accession group
            const copyNumber = i + 1;
            
            const callNumberDiv = document.createElement('div');
            callNumberDiv.className = 'input-group mb-2';
            callNumberDiv.dataset.accessionGroup = index;
            
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
            
            // Create copy number label and input (positioned between call number and shelf location)
            const copyNumberLabel = document.createElement('span');
            copyNumberLabel.className = 'input-group-text';
            copyNumberLabel.textContent = 'Copy #';
            
            const copyNumberInput = document.createElement('input');
            copyNumberInput.type = 'number';
            copyNumberInput.className = 'form-control copy-number-input';
            copyNumberInput.name = 'copy_number[]';
            copyNumberInput.min = '1';
            copyNumberInput.value = copyNumber;
            copyNumberInput.style.width = '70px';
            
            // New order of elements in the input group
            callNumberDiv.appendChild(accessionLabel);
            callNumberDiv.appendChild(callNumberInput); // Call number input
            callNumberDiv.appendChild(copyNumberLabel);
            callNumberDiv.appendChild(copyNumberInput); // Copy number input
            callNumberDiv.appendChild(shelfLocationSelect); // Shelf location select
            callNumberContainer.appendChild(callNumberDiv);
        }
    });
    
    // Trigger form autosave to persist the updated call numbers
    if (typeof saveFormData === 'function') {
        setTimeout(saveFormData, 100);
    }
}

// Create a direct function to generate call numbers immediately
function forceGenerateCallNumbers() {
    console.log('Force generating call numbers');
    const callNumberContainer = document.getElementById('callNumberContainer');
    const accessionContainer = document.getElementById('accessionContainer');
    
    if (!callNumberContainer || !accessionContainer) {
        console.error('Required containers not found');
        return;
    }
    
    // Clear any existing content
    callNumberContainer.innerHTML = '';
    
    // Get all accession groups
    const accessionGroups = accessionContainer.querySelectorAll('.accession-group');
    console.log(`Found ${accessionGroups.length} accession groups for direct generation`);
    
    // Process each accession group
    accessionGroups.forEach((group, groupIndex) => {
        const accessionInput = group.querySelector('.accession-input');
        const copiesInput = group.querySelector('.copies-input');
        
        if (!accessionInput || !copiesInput) {
            console.error('Required input fields not found in accession group');
            return;
        }
        
        const accession = accessionInput.value || `ACC-${groupIndex+1}`;
        const copies = parseInt(copiesInput.value) || 1;
        
        // Create header for this group
        const groupHeader = document.createElement('div');
        groupHeader.className = 'mb-2 text-muted small font-weight-bold';
        groupHeader.innerHTML = `Accession Group ${groupIndex + 1}: ${accession}`;
        callNumberContainer.appendChild(groupHeader);
        
        // Create input fields for each copy
        for (let i = 0; i < copies; i++) {
            createCallNumberRow(callNumberContainer, accession, i, groupIndex);
        }
    });
}

// Helper function to create a single call number row
function createCallNumberRow(container, baseAccession, increment, groupIndex) {
    const currentAccession = calculateAccession(baseAccession, increment);
    const copyNumber = increment + 1;
    
    const callNumberDiv = document.createElement('div');
    callNumberDiv.className = 'input-group mb-2';
    callNumberDiv.dataset.accessionGroup = groupIndex;
    
    const accessionLabel = document.createElement('span');
    accessionLabel.className = 'input-group-text';
    accessionLabel.textContent = `Accession ${currentAccession}`;
    
    const callNumberInput = document.createElement('input');
    callNumberInput.type = 'text';
    callNumberInput.className = 'form-control call-number-input';
    callNumberInput.name = 'call_number[]';
    callNumberInput.placeholder = 'Enter call number';
    
    const copyNumberLabel = document.createElement('span');
    copyNumberLabel.className = 'input-group-text';
    copyNumberLabel.textContent = 'Copy #';
    
    const copyNumberInput = document.createElement('input');
    copyNumberInput.type = 'number';
    copyNumberInput.className = 'form-control copy-number-input';
    copyNumberInput.name = 'copy_number[]';
    copyNumberInput.min = '1';
    copyNumberInput.value = copyNumber;
    copyNumberInput.style.width = '70px';
    
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
        if (value === 'CIR') option.selected = true;
        shelfLocationSelect.appendChild(option);
    });
    
    // Assemble the input group
    callNumberDiv.appendChild(accessionLabel);
    callNumberDiv.appendChild(callNumberInput);
    callNumberDiv.appendChild(copyNumberLabel);
    callNumberDiv.appendChild(copyNumberInput);
    callNumberDiv.appendChild(shelfLocationSelect);
    container.appendChild(callNumberDiv);
    
    // Apply initial call number formatting
    setTimeout(() => formatCallNumberDisplay(callNumberInput), 100);
}

// Function to format the call number display according to the pattern
function formatCallNumberDisplay(callNumberInput) {
    if (!callNumberInput) return;
    
    // Get the base call number entered by the user
    const baseCallNumber = callNumberInput.value.trim();
    if (!baseCallNumber) return; // Skip if no base call number
    
    // Get the container and find related elements
    const container = callNumberInput.closest('.input-group');
    if (!container) return;
    
    const shelfLocationSelect = container.querySelector('.shelf-location-select');
    const copyNumberInput = container.querySelector('.copy-number-input');
    
    if (!shelfLocationSelect || !copyNumberInput) return;
    
    // Get shelf location abbreviation
    const shelfLocation = shelfLocationSelect.value;
    
    // Get copy number
    const copyNumber = copyNumberInput.value;
    
    // Get publication year from the form
    const publishYear = document.getElementById('publish_date')?.value || '';
    
    // Get volume if available - find the volume input for this accession group
    let volume = '';
    
    // First try to find volume in the same accession group
    const accessionGroup = callNumberInput.closest('[data-accession-group]');
    if (accessionGroup) {
        const groupIndex = accessionGroup.dataset.accessionGroup;
        // Find volume inputs and check if there's a value
        const volumeInputs = document.querySelectorAll('input[name="volume[]"]');
        if (volumeInputs.length > groupIndex && volumeInputs[groupIndex].value) {
            volume = 'vol' + volumeInputs[groupIndex].value;
        }
    }
    
    // Create the full formatted call number with proper spacing
    let formattedCallNumber = [];
    formattedCallNumber.push(shelfLocation);
    
    // Process base call number to ensure proper spacing between classification and cutter
    const callParts = baseCallNumber.split(/\s+/).filter(part => part.length > 0);
    formattedCallNumber.push(callParts.join(' '));
    
    if (publishYear) formattedCallNumber.push(publishYear);
    if (volume) formattedCallNumber.push(volume);
    formattedCallNumber.push('c' + copyNumber);
    
    // Join with single spaces and ensure no trailing spaces
    const preview = formattedCallNumber.join(' ').trim();
    
    // Add a data attribute with the full formatted call number
    callNumberInput.dataset.formattedCallNumber = preview;
    
    // For better UX, we keep the base call number in the input but show the full format in a tooltip
    callNumberInput.title = "Will be saved as: " + preview;
    
    // Add a small preview element next to the input if it doesn't exist
    let previewElem = container.querySelector('.call-number-preview');
    if (!previewElem) {
        previewElem = document.createElement('small');
        previewElem.className = 'call-number-preview text-muted ml-2';
        previewElem.style.position = 'absolute';
        previewElem.style.right = '120px'; // Position it near the end of the input
        previewElem.style.top = '50%';
        previewElem.style.transform = 'translateY(-50%)';
        callNumberInput.parentNode.style.position = 'relative';
        callNumberInput.parentNode.appendChild(previewElem);
    }
    
    // Update the preview text
    previewElem.textContent = '→ ' + preview;
    
    // Add warning about trailing spaces if needed
    let warningElem = container.querySelector('.call-number-warning');
    if (!warningElem) {
        warningElem = document.createElement('small');
        warningElem.className = 'call-number-warning text-danger d-block mt-1';
        warningElem.style.fontSize = '11px';
        callNumberInput.parentNode.appendChild(warningElem);
    }
    
    // Check if the user has added trailing spaces to the base call number
    if (callNumberInput.value !== callNumberInput.value.trimEnd()) {
        warningElem.textContent = 'Warning: Trailing spaces will be removed from call number';
        warningElem.style.display = 'block';
    } else if (callParts.length === 0) {
        warningElem.textContent = 'Please enter a complete call number';
        warningElem.style.display = 'block';
    } else if (callParts.length === 1 && baseCallNumber.length > 2) {
        warningElem.textContent = 'Call numbers should have a space between classification and author cutter (e.g., "HD69.B7 W56")';
        warningElem.style.display = 'block';
    } else {
        warningElem.style.display = 'none';
    }
}

// Initialize everything at page load
document.addEventListener("DOMContentLoaded", function() {
    // Call the standard initialization first
    updateISBNFields();
    
    // If for some reason call numbers aren't generated, force them after a delay
    setTimeout(function() {
        const callNumberContainer = document.getElementById('callNumberContainer');
        if (callNumberContainer && callNumberContainer.children.length === 0) {
            console.log('No call numbers found after initial load, forcing generation');
            forceGenerateCallNumbers();
        }
    }, 500);
    
    // Add a button click handler for local-info-tab to ensure call numbers are shown
    document.getElementById('local-info-tab').addEventListener('click', function() {
        setTimeout(function() {
            const callNumberContainer = document.getElementById('callNumberContainer');
            if (callNumberContainer && callNumberContainer.children.length === 0) {
                console.log('No call numbers found when tab activated, forcing generation');
                forceGenerateCallNumbers();
            }
        }, 100);
    });
});
</script>

<script>
/**
 * Direct Call Number Generator - works independently when all else fails
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Direct call number generator loaded');
    
    // Function to directly generate call numbers without dependency on other scripts
    window.generateCallNumbersDirectly = function() {
        console.log('Direct call number generation executing...');
        
        const callNumberContainer = document.getElementById('callNumberContainer');
        const accessionContainer = document.getElementById('accessionContainer');
        
        if (!callNumberContainer || !accessionContainer) {
            console.error('Essential containers missing');
            return;
        }
        
        // Get all accession groups
        const accessionGroups = accessionContainer.querySelectorAll('.accession-group');
        if (accessionGroups.length === 0) {
            callNumberContainer.innerHTML = '<div class="alert alert-warning">No accession groups found. Please add accession information first.</div>';
            return;
        }
        
        // Save existing copy numbers before regenerating
        const existingCopyNumbers = {};
        const existingCopyInputs = document.querySelectorAll('.copy-number-input');
        existingCopyInputs.forEach((input, index) => {
            existingCopyNumbers[index] = input.value;
        });
        
        // Clear container for fresh content
        callNumberContainer.innerHTML = '';
        
        // Track total copies count across all groups
        let totalCopiesCount = 0;
        
        // Get publication year for call numbers
        const publishYear = document.getElementById('publish_date')?.value || '';
        
        // Process each accession group
        accessionGroups.forEach((group, groupIndex) => {
            const accessionInput = group.querySelector('.accession-input');
            const copiesInput = group.querySelector('.copies-input');
            
            if (!accessionInput || !copiesInput) {
                console.error('Missing inputs in accession group');
                return;
            }
            
            // Get values (or use defaults if empty)
            const accessionValue = accessionInput.value || `ACC-${groupIndex+1}`;
            const copies = parseInt(copiesInput.value) || 1;
            
            // Find volume for this group if any
            let volumeValue = '';
            const volumeInput = group.querySelector('input[name="volume[]"]');
            if (volumeInput && volumeInput.value) {
                volumeValue = volumeInput.value;
            }
            
            // Create group header
            const groupHeader = document.createElement('div');
            groupHeader.className = 'mb-2 text-muted small font-weight-bold';
            groupHeader.innerHTML = `Accession Group ${groupIndex + 1}: ${accessionValue}`;
            callNumberContainer.appendChild(groupHeader);
            
            // Generate call number fields for each copy
            for (let i = 0; i < copies; i++) {
                // Calculate incremented accession number
                let currentAccession = accessionValue;
                if (i > 0 && /\d+$/.test(accessionValue)) {
                    const match = accessionValue.match(/^(.*?)(\d+)$/);
                    if (match) {
                        const prefix = match[1];
                        const num = parseInt(match[2]);
                        const width = match[2].length;
                        currentAccession = prefix + (num + i).toString().padStart(width, '0');
                    }
                }
                
                // Create a call number row
                const row = document.createElement('div');
                row.className = 'input-group mb-2';
                row.dataset.accessionGroup = groupIndex;
                
                // Create elements
                const accessionLabel = document.createElement('span');
                accessionLabel.className = 'input-group-text';
                accessionLabel.textContent = `Accession ${currentAccession}`;
                
                const callNumberInput = document.createElement('input');
                callNumberInput.type = 'text';
                callNumberInput.className = 'form-control call-number-input';
                callNumberInput.name = 'call_number[]';
                callNumberInput.placeholder = 'Enter call number';
                
                const copyNumberLabel = document.createElement('span');
                copyNumberLabel.className = 'input-group-text';
                copyNumberLabel.textContent = 'Copy #';
                
                // Use existing copy number if available, otherwise use global sequence
                const copyIndex = totalCopiesCount + i;
                // If we have a saved value use it, otherwise use the global count + 1
                const copyValue = existingCopyNumbers[copyIndex] || (totalCopiesCount + i + 1);
                
                const copyNumberInput = document.createElement('input');
                copyNumberInput.type = 'number';
                copyNumberInput.className = 'form-control copy-number-input';
                copyNumberInput.name = 'copy_number[]';
                copyNumberInput.min = '1';
                copyNumberInput.value = copyValue;
                copyNumberInput.style.width = '70px';
                
                const shelfLocationSelect = document.createElement('select');
                shelfLocationSelect.className = 'form-control shelf-location-select';
                shelfLocationSelect.name = 'shelf_locations[]';
                
                // Add shelf locations
                [
                    ['TR', 'Teachers Reference'],
                    ['FIL', 'Filipiniana'],
                    ['CIR', 'Circulation'],
                    ['REF', 'Reference'],
                    ['SC', 'Special Collection'],
                    ['BIO', 'Biography'],
                    ['RES', 'Reserve'],
                    ['FIC', 'Fiction']
                ].forEach(([value, text]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = text;
                    if (value === 'CIR') option.selected = true;
                    shelfLocationSelect.appendChild(option);
                });
                
                // Create a preview element for the formatted call number
                const callNumberPreview = document.createElement('small');
                callNumberPreview.className = 'call-number-preview text-muted ml-2';
                callNumberPreview.style.position = 'absolute';
                callNumberPreview.style.right = '120px';
                callNumberPreview.style.top = '50%';
                callNumberPreview.style.transform = 'translateY(-50%)';
                
                // Assemble the row
                row.appendChild(accessionLabel);
                row.appendChild(callNumberInput);
                callNumberInput.parentNode.style.position = 'relative';
                callNumberInput.parentNode.appendChild(callNumberPreview);
                row.appendChild(copyNumberLabel);
                row.appendChild(copyNumberInput);
                row.appendChild(shelfLocationSelect);
                callNumberContainer.appendChild(row);
                
                // Add event listener to update the formatted call number
                callNumberInput.addEventListener('input', function() {
                    // If formatCallNumberDisplay exists use it, otherwise create a simple preview
                    if (typeof formatCallNumberDisplay === 'function') {
                        formatCallNumberDisplay(callNumberInput);
                    } else {
                        // Default simple formatting
                        const baseCallNumber = this.value.trim();
                        if (baseCallNumber) {
                            const shelf = shelfLocationSelect.value;
                            const volume = volumeValue ? ` vol${volumeValue}` : '';
                            const year = publishYear ? ` ${publishYear}` : '';
                            const copy = ` c${copyNumberInput.value}`;
                            const formatted = `${shelf} ${baseCallNumber}${year}${volume}${copy}`.trim();
                            callNumberPreview.textContent = `→ ${formatted}`;
                            // Store the formatted value to be used on submission
                            this.dataset.formattedCallNumber = formatted;
                            
                            // Add warning about trailing spaces if needed
                            let warningElem = row.querySelector('.call-number-warning');
                            if (!warningElem) {
                                warningElem = document.createElement('small');
                                warningElem.className = 'call-number-warning text-danger d-block mt-1';
                                warningElem.style.fontSize = '11px';
                                this.parentNode.appendChild(warningElem);
                            }
                            
                            // Check if the user has added trailing spaces
                            if (this.value !== this.value.trimEnd()) {
                                warningElem.textContent = 'Warning: Trailing spaces will be removed from call number';
                                warningElem.style.display = 'block';
                            } else if (!baseCallNumber) {
                                warningElem.textContent = 'Please enter a complete call number';
                                warningElem.style.display = 'block';
                            } else if (baseCallNumber.indexOf(' ') === -1 && baseCallNumber.length > 2) {
                                warningElem.textContent = 'Call numbers should have a space between classification and author cutter (e.g., "HD69.B7 W56")';
                                warningElem.style.display = 'block';
                            } else {
                                warningElem.style.display = 'none';
                            }
                        } else {
                            callNumberPreview.textContent = '';
                            this.dataset.formattedCallNumber = '';
                        }
                    }
                });
                
                // Add listeners for other fields that affect the call number format
                shelfLocationSelect.addEventListener('change', function() {
                    if (typeof formatCallNumberDisplay === 'function') {
                        formatCallNumberDisplay(callNumberInput);
                    } else {
                        // Trigger input event to refresh preview with new shelf location
                        callNumberInput.dispatchEvent(new Event('input'));
                    }
                });
                
                copyNumberInput.addEventListener('change', function() {
                    if (typeof formatCallNumberDisplay === 'function') {
                        formatCallNumberDisplay(callNumberInput);
                    } else {
                        // Trigger input event to refresh preview with new copy number
                        callNumberInput.dispatchEvent(new Event('input'));
                    }
                });
            }
            
            // Increment the total copies count for the next group
            totalCopiesCount += copies;
        });
        
        console.log('Direct call number generation complete');
    };
    
    // Automatically check and generate call numbers when accession inputs change
    document.addEventListener('input', function(e) {
        if (e.target && (e.target.classList.contains('accession-input') || e.target.classList.contains('copies-input'))) {
            setTimeout(function() {
                const callNumberContainer = document.getElementById('callNumberContainer');
                if (callNumberContainer && callNumberContainer.children.length === 0) {
                    generateCallNumbersDirectly();
                }
            }, 300);
        }
    });
    
    // Add click handler for the tab
    const localInfoTab = document.getElementById('local-info-tab');
    if (localInfoTab) {
        localInfoTab.addEventListener('click', function() {
            setTimeout(function() {
                const callNumberContainer = document.getElementById('callNumberContainer');
                if (callNumberContainer && callNumberContainer.children.length === 0) {
                    generateCallNumbersDirectly();
                }
            }, 300);
        });
    }
    
    // Check if we need to generate call numbers on initial load
    setTimeout(function() {
        const callNumberContainer = document.getElementById('callNumberContainer');
        const accessionGroups = document.querySelectorAll('.accession-group');
        if (callNumberContainer && callNumberContainer.children.length === 0 && accessionGroups.length > 0) {
            generateCallNumbersDirectly();
        }
    }, 800);
    
    // Export the function globally
    window.setupManualCallNumberGeneration = function() {
        const callNumberContainer = document.getElementById('callNumberContainer');
        if (callNumberContainer && callNumberContainer.children.length === 0) {
            generateCallNumbersDirectly();
        }
    };
});
</script>

<!-- Embedded call-number-fallback code -->

<!-- Embedded author management script -->
<script>
/**
 * Author management functionality
 */
document.addEventListener("DOMContentLoaded", function() {
    // Add author entry functionality
    document.getElementById('addAuthorEntry').addEventListener('click', function() {
        const authorEntriesContainer = document.getElementById('authorEntriesContainer');
        const newEntry = document.createElement('div');
        newEntry.className = 'author-entry row mb-3';
        newEntry.innerHTML = `
            <div class="col-md-4">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" class="form-control author-firstname" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Middle Initial</label>
                    <input type="text" class="form-control author-middleinit">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" class="form-control author-lastname" required>
                </div>
            </div>
            <div class="col-md-1 remove-btn-container">
                <button type="button" class="btn btn-danger btn-sm remove-author-entry">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        authorEntriesContainer.appendChild(newEntry);
    });

    // Remove author entry
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('remove-author-entry') || e.target.closest('.remove-author-entry'))) {
            const authorEntriesContainer = document.getElementById('authorEntriesContainer');
            if (authorEntriesContainer.children.length > 1) {
                e.target.closest('.author-entry').remove();
            } else {
                alert('At least one author entry is required.');
            }
        }
    });

    // Replace the single author save with multiple authors save
    document.getElementById('saveAuthors').addEventListener('click', function() {
        const authorEntries = document.querySelectorAll('.author-entry');
        const authorsData = [];
        let hasErrors = false;

        // Collect data from all author entries
        authorEntries.forEach(entry => {
            const firstname = entry.querySelector('.author-firstname').value.trim();
            const middle_init = entry.querySelector('.author-middleinit').value.trim();
            const lastname = entry.querySelector('.author-lastname').value.trim();
            
            if (!firstname || !lastname) {
                hasErrors = true;
                return;
            }
            
            authorsData.push({
                firstname: firstname,
                middle_init: middle_init,
                lastname: lastname
            });
        });
        
        if (hasErrors) {
            alert('First name and last name are required for all authors.');
            return;
        }
        
        if (authorsData.length === 0) {
            alert('Please add at least one author.');
            return;
        }
        
        // AJAX request to save all authors
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax/add_writers.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Add all new authors to the select options
                        const authorSelect = document.getElementById('authorSelect');
                        const coAuthorsSelect = document.getElementById('coAuthorsSelect');
                        const editorsSelect = document.getElementById('editorsSelect');
                        
                        response.authors.forEach(author => {
                            const newOption = document.createElement('option');
                            newOption.value = author.id;
                            newOption.textContent = author.name;
                            
                            authorSelect.appendChild(newOption.cloneNode(true));
                            coAuthorsSelect.appendChild(newOption.cloneNode(true));
                            editorsSelect.appendChild(newOption.cloneNode(true));
                        });
                        
                        // Select the first new author in the author dropdown if no author is selected
                        if (!authorSelect.value && response.authors.length > 0) {
                            authorSelect.value = response.authors[0].id;
                        }
                        
                        // Close the modal
                        $('#addAuthorModal').modal('hide');
                        
                        // Clear the form
                        document.getElementById('newAuthorForm').reset();
                        // Reset to just one author entry
                        document.getElementById('authorEntriesContainer').innerHTML = `
                            <div class="author-entry row mb-3">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>First Name</label>
                                        <input type="text" class="form-control author-firstname" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Middle Initial</label>
                                        <input type="text" class="form-control author-middleinit">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Last Name</label>
                                        <input type="text" class="form-control author-lastname" required>
                                    </div>
                                </div>
                                <div class="col-md-1 remove-btn-container">
                                    <button type="button" class="btn btn-danger btn-sm remove-author-entry">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        
                        alert(`Successfully added ${response.authors.length} author(s)!`);
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (e) {
                    alert('Error processing response: ' + e.message);
                }
            } else {
                alert('Error adding authors');
            }
        };
        xhr.send(JSON.stringify(authorsData));
    });

    // Initialize dropdown filters
    function filterDropdown(inputId, selectId) {
        const input = document.getElementById(inputId);
        const select = document.querySelector(selectId);
        input.addEventListener("keyup", function() {
            const filter = input.value.toLowerCase();
            const options = select.options;
            for (let i = 0; i < options.length; i++) {
                const optionText = options[i].text.toLowerCase();
                options[i].style.display = optionText.includes(filter) ? "" : "none";
            }
        });
    }

    function updatePreview(selectId, previewId) {
        const select = document.getElementById(selectId);
        const preview = document.getElementById(previewId);
        const selectedOptions = Array.from(select.selectedOptions).map(option => {
            return `<span class="badge bg-secondary mr-1 text-white">${option.text} <i class="fas fa-times remove-icon" data-value="${option.value}"></i></span>`;
        });
        preview.innerHTML = selectedOptions.join(' ');
    }

    function removeSelectedOption(selectId, previewId) {
        const preview = document.getElementById(previewId);
        preview.addEventListener("click", function(event) {
            if (event.target.classList.contains("remove-icon")) {
                const value = event.target.getAttribute("data-value");
                const select = document.getElementById(selectId);
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value === value) {
                        select.options[i].selected = false;
                        break;
                    }
                }
                updatePreview(selectId, previewId);
            }
        });
    }

    // Update publisher search functionality to use existing search field
    function addPublisherSearch() {
        const publisherSelect = document.querySelector('select[name="publisher"]');
        const searchInput = document.getElementById('publisherSearch');
        
        if (!searchInput || !publisherSelect) return;
        
        // Store original options
        const originalOptions = Array.from(publisherSelect.options);
        
        // Add search functionality
        searchInput.addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            
            // Clear current options
            publisherSelect.innerHTML = '';
            
            // Add default "Select Publisher" option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select Publisher';
            publisherSelect.appendChild(defaultOption);
            
            // Filter and add matching options
            originalOptions.forEach(option => {
                if (option.value !== '' && option.text.toLowerCase().includes(searchText)) {
                    publisherSelect.appendChild(option.cloneNode(true));
                }
            });
        });
    }

    // Initialize dropdowns and selects
    filterDropdown("authorSearch", "select[name='author[]']");
    filterDropdown("coAuthorsSearch", "select[name='co_authors[]']");
    filterDropdown("editorsSearch", "select[name='editors[]']");

    document.getElementById("authorSelect").addEventListener("change", function() {
        updatePreview("authorSelect", "authorPreview");
    });
    document.getElementById("coAuthorsSelect").addEventListener("change", function() {
        updatePreview("coAuthorsSelect", "coAuthorsPreview");
    });
    document.getElementById("editorsSelect").addEventListener("change", function() {
        updatePreview("editorsSelect", "editorsPreview");
    });

    removeSelectedOption("authorSelect", "authorPreview");
    removeSelectedOption("coAuthorsSelect", "coAuthorsPreview");
    removeSelectedOption("editorsSelect", "editorsPreview");
    
    // Initialize publisher search
    addPublisherSearch();

    // Add publisher entry functionality
    document.getElementById('addPublisherEntry').addEventListener('click', function() {
        const publisherEntriesContainer = document.getElementById('publisherEntriesContainer');
        const newEntry = document.createElement('div');
        newEntry.className = 'publisher-entry row mb-3';
        newEntry.innerHTML = `
            <div class="col-md-6">
                <div class="form-group">
                    <label>Publisher Name</label>
                    <input type="text" class="form-control publisher-name" required>
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label>Place of Publication</label>
                    <input type="text" class="form-control publisher-place" required>
                </div>
            </div>
            <div class="col-md-1 remove-btn-container">
                <button type="button" class="btn btn-danger btn-sm remove-publisher-entry">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        publisherEntriesContainer.appendChild(newEntry);
    });

    // Remove publisher entry
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('remove-publisher-entry') || e.target.closest('.remove-publisher-entry'))) {
            const publisherEntriesContainer = document.getElementById('publisherEntriesContainer');
            if (publisherEntriesContainer.children.length > 1) {
                e.target.closest('.publisher-entry').remove();
            } else {
                alert('At least one publisher entry is required.');
            }
        }
    });

    // Save publishers functionality
    document.getElementById('savePublishers').addEventListener('click', function() {
        const publisherEntries = document.querySelectorAll('.publisher-entry');
        const publishersData = [];
        let hasErrors = false;

        // Collect data from all publisher entries
        publisherEntries.forEach(entry => {
            const publisher = entry.querySelector('.publisher-name').value.trim();
            const place = entry.querySelector('.publisher-place').value.trim();
            
            if (!publisher || !place) {
                hasErrors = true;
                return;
            }
            
            publishersData.push({
                publisher: publisher,
                place: place
            });
        });
        
        if (hasErrors) {
            alert('Publisher name and place are required for all publishers.');
            return;
        }
        
        if (publishersData.length === 0) {
            alert('Please add at least one publisher.');
            return;
        }
        
        // AJAX request to save all publishers
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax/add_publishers.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Add all new publishers to the select options
                        const publisherSelect = document.getElementById('publisher');
                        
                        response.publishers.forEach(pub => {
                            // Check if this publisher is already in the dropdown
                            let exists = false;
                            for (let i = 0; i < publisherSelect.options.length; i++) {
                                if (publisherSelect.options[i].value === pub.publisher) {
                                    exists = true;
                                    break;
                                }
                            }
                            
                            if (!exists) {
                                const newOption = document.createElement('option');
                                newOption.value = pub.publisher;
                                newOption.textContent = `${pub.publisher} (${pub.place})`;
                                publisherSelect.appendChild(newOption);
                            }
                        });
                        
                        // Select the first new publisher in the dropdown if none is selected
                        if (!publisherSelect.value && response.publishers.length > 0) {
                            publisherSelect.value = response.publishers[0].publisher;
                        }
                        
                        // Close the modal
                        $('#addPublisherModal').modal('hide');
                        
                        // Clear the form
                        document.getElementById('newPublisherForm').reset();
                        // Reset to just one publisher entry
                        document.getElementById('publisherEntriesContainer').innerHTML = `
                            <div class="publisher-entry row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Publisher Name</label>
                                        <input type="text" class="form-control publisher-name" required>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Place of Publication</label>
                                        <input type="text" class="form-control publisher-place" required>
                                    </div>
                                </div>
                                <div class="col-md-1 remove-btn-container">
                                    <button type="button" class="btn btn-danger btn-sm remove-publisher-entry">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        
                        alert(`Successfully added ${response.publishers.length} publisher(s)!`);
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (e) {
                    alert('Error processing response: ' + e.message);
                }
            } else {
                alert('Error adding publishers');
            }
        };
        xhr.send(JSON.stringify(publishersData));
    });
});
</script>

<script>
/**
 * Author and Publisher management using SweetAlert
 */
document.addEventListener("DOMContentLoaded", function() {
    // Create a function to show the add author dialog using SweetAlert
    window.showAddAuthorDialog = function() {
        Swal.fire({
            title: 'Add New Author',
            html: `
                <div id="sweetAlertAuthorContainer">
                    <div class="author-entry row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" class="form-control author-firstname" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Middle Initial</label>
                                <input type="text" class="form-control author-middleinit">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" class="form-control author-lastname" required>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="swalAddAuthorEntry" class="btn btn-info btn-sm">
                        <i class="fas fa-plus"></i> Add Another Author
                    </button>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Save Authors',
            cancelButtonText: 'Cancel',
            width: '800px',
            didOpen: () => {
                // Add another author entry when clicking the add button
                document.getElementById('swalAddAuthorEntry').addEventListener('click', function() {
                    const container = document.getElementById('sweetAlertAuthorContainer');
                    const newEntry = document.createElement('div');
                    newEntry.className = 'author-entry row mb-3';
                    newEntry.innerHTML = `
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" class="form-control author-firstname" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Middle Initial</label>
                                <input type="text" class="form-control author-middleinit">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" class="form-control author-lastname" required>
                            </div>
                        </div>
                        <div class="col-md-1 remove-btn-container">
                            <button type="button" class="btn btn-danger btn-sm remove-author-entry">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    container.insertBefore(newEntry, document.getElementById('swalAddAuthorEntry'));
                    
                    // Add remove functionality
                    newEntry.querySelector('.remove-author-entry').addEventListener('click', function() {
                        newEntry.remove();
                    });
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Collect data from all author entries
                const authorEntries = document.querySelectorAll('#sweetAlertAuthorContainer .author-entry');
                const authorsData = [];
                let hasErrors = false;
                
                authorEntries.forEach(entry => {
                    const firstname = entry.querySelector('.author-firstname').value.trim();
                    const middle_init = entry.querySelector('.author-middleinit').value.trim();
                    const lastname = entry.querySelector('.author-lastname').value.trim();
                    
                    if (!firstname || !lastname) {
                        hasErrors = true;
                        return;
                    }
                    
                    authorsData.push({
                        firstname: firstname,
                        middle_init: middle_init,
                        lastname: lastname
                    });
                });
                
                if (hasErrors) {
                    Swal.fire('Error', 'First name and last name are required for all authors.', 'error');
                    return;
                }
                
                if (authorsData.length === 0) {
                    Swal.fire('Error', 'Please add at least one author.', 'error');
                    return;
                }
                
                // AJAX request to save all authors
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'ajax/add_writers.php', true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.onload = function() {
                    if (this.status === 200) {
                        try {
                            const response = JSON.parse(this.responseText);
                            if (response.success) {
                                // Add all new authors to the select options
                                const authorSelect = document.getElementById('authorSelect');
                                const coAuthorsSelect = document.getElementById('coAuthorsSelect');
                                const editorsSelect = document.getElementById('editorsSelect');
                                
                                if (authorSelect && coAuthorsSelect && editorsSelect) {
                                    response.authors.forEach(author => {
                                        const newOption = document.createElement('option');
                                        newOption.value = author.id;
                                        newOption.textContent = author.name;
                                        
                                        authorSelect.appendChild(newOption.cloneNode(true));
                                        coAuthorsSelect.appendChild(newOption.cloneNode(true));
                                        editorsSelect.appendChild(newOption.cloneNode(true));
                                    });
                                    
                                    // Select the first new author in the author dropdown if no author is selected
                                    if (!authorSelect.value && response.authors.length > 0) {
                                        authorSelect.value = response.authors[0].id;
                                        // Manually trigger change event to update any previews
                                        const event = new Event('change');
                                        authorSelect.dispatchEvent(event);
                                    }
                                }
                                
                                Swal.fire(
                                    'Success',
                                    `Successfully added ${response.authors.length} author(s)!`,
                                    'success'
                                );
                            } else {
                                Swal.fire('Error', response.message || 'Failed to add authors', 'error');
                            }
                        } catch (e) {
                            Swal.fire('Error', 'Error processing response: ' + e.message, 'error');
                        }
                    } else {
                        Swal.fire('Error', 'Server error while adding authors', 'error');
                    }
                };
                xhr.send(JSON.stringify(authorsData));
            }
        });
    };
    
    // Create a function to show the add publisher dialog using SweetAlert
    window.showAddPublisherDialog = function() {
        Swal.fire({
            title: 'Add New Publisher',
            html: `
                <div id="sweetAlertPublisherContainer">
                    <div class="publisher-entry row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Publisher Name</label>
                                <input type="text" class="form-control publisher-name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Place of Publication</label>
                                <input type="text" class="form-control publisher-place" required>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="swalAddPublisherEntry" class="btn btn-info btn-sm">
                        <i class="fas fa-plus"></i> Add Another Publisher
                    </button>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Save Publishers',
            cancelButtonText: 'Cancel',
            width: '800px',
            didOpen: () => {
                // Add another publisher entry when clicking the add button
                document.getElementById('swalAddPublisherEntry').addEventListener('click', function() {
                    const container = document.getElementById('sweetAlertPublisherContainer');
                    const newEntry = document.createElement('div');
                    newEntry.className = 'publisher-entry row mb-3';
                    newEntry.innerHTML = `
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Publisher Name</label>
                                <input type="text" class="form-control publisher-name" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Place of Publication</label>
                                <input type="text" class="form-control publisher-place" required>
                            </div>
                        </div>
                        <div class="col-md-1 remove-btn-container">
                            <button type="button" class="btn btn-danger btn-sm remove-publisher-entry">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    container.insertBefore(newEntry, document.getElementById('swalAddPublisherEntry'));
                    
                    // Add remove functionality
                    newEntry.querySelector('.remove-publisher-entry').addEventListener('click', function() {
                        newEntry.remove();
                    });
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Collect data from all publisher entries
                const publisherEntries = document.querySelectorAll('#sweetAlertPublisherContainer .publisher-entry');
                const publishersData = [];
                let hasErrors = false;
                
                publisherEntries.forEach(entry => {
                    const publisher = entry.querySelector('.publisher-name').value.trim();
                    const place = entry.querySelector('.publisher-place').value.trim();
                    
                    if (!publisher || !place) {
                        hasErrors = true;
                        return;
                    }
                    
                    publishersData.push({
                        publisher: publisher,
                        place: place
                    });
                });
                
                if (hasErrors) {
                    Swal.fire('Error', 'Publisher name and place are required for all publishers.', 'error');
                    return;
                }
                
                if (publishersData.length === 0) {
                    Swal.fire('Error', 'Please add at least one publisher.', 'error');
                    return;
                }
                
                // AJAX request to save all publishers
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'ajax/add_publishers.php', true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.onload = function() {
                    if (this.status === 200) {
                        try {
                            const response = JSON.parse(this.responseText);
                            if (response.success) {
                                // Add all new publishers to the select options
                                const publisherSelect = document.getElementById('publisher');
                                
                                if (publisherSelect) {
                                    response.publishers.forEach(pub => {
                                        // Check if this publisher is already in the dropdown
                                        let exists = false;
                                        for (let i = 0; i < publisherSelect.options.length; i++) {
                                            if (publisherSelect.options[i].value === pub.id.toString()) {

                                                exists = true;
                                                break;
                                            }
                                        }
                                        
                                        if (!exists) {
                                            const newOption = document.createElement('option');
                                            newOption.value = pub.id;
                                            newOption.textContent = `${pub.publisher} (${pub.place})`;
                                            publisherSelect.appendChild(newOption);
                                        }
                                    });
                                    
                                    // Select the first new publisher in the dropdown if none is selected
                                    if (!publisherSelect.value && response.publishers.length > 0) {
                                        publisherSelect.value = response.publishers[0].id;
                                        // Manually trigger change event
                                        const event = new Event('change');
                                        publisherSelect.dispatchEvent(event);
                                    }
                                }
                                
                                Swal.fire(
                                    'Success',
                                    `Successfully added ${response.publishers.length} publisher(s)!`,
                                    'success'
                                );
                            } else {
                                Swal.fire('Error', response.message || 'Failed to add publishers', 'error');
                            }
                        } catch (e) {
                            Swal.fire('Error', 'Error processing response: ' + e.message, 'error');
                        }
                    } else {
                        Swal.fire('Error', 'Server error while adding publishers', 'error');
                    }
                };
                xhr.send(JSON.stringify(publishersData));
            }
        });
    };
    
    // Add event listeners to buttons that should trigger the dialogs
    document.getElementById('addNewAuthorBtn').addEventListener('click', showAddAuthorDialog);
    document.getElementById('addNewPublisherBtn').addEventListener('click', showAddPublisherDialog);
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('bookForm');
    
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
    
    // Add event listener to the form
    form.addEventListener('submit', function(e) {
        // First perform validation
        if (!validateForm(e)) {
            e.preventDefault();
            return false;
        }

        // Format call numbers if needed
        const callNumberInputs = document.querySelectorAll('.call-number-input');
        const previewElements = document.querySelectorAll('.call-number-preview');
        
        if (callNumberInputs.length > 0 && previewElements.length > 0) {
            // Check if any preview is different from input value
            callNumberInputs.forEach((input, index) => {
                const preview = previewElements[index]?.textContent?.replace('→ ', '') || '';
                if (preview && input.value !== preview && !input.dataset.formattedCallNumber) {
                    // Set the formatted value for submission
                    input.value = preview;
                }
            });
        }
    });

    // Initialize file input display
    document.querySelectorAll('.custom-file-input').forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'Choose file';
            this.nextElementSibling.textContent = fileName;
        });
    });
});
</script>

<script>
// Author Management Functionality
document.addEventListener("DOMContentLoaded", function() {
    // Add author entry functionality
    document.getElementById('addAuthorEntry').addEventListener('click', function() {
        const authorEntriesContainer = document.getElementById('authorEntriesContainer');
        const newEntry = document.createElement('div');
        newEntry.className = 'author-entry row mb-3';
        newEntry.innerHTML = `
            <div class="col-md-4">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" class="form-control author-firstname" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Middle Initial</label>
                    <input type="text" class="form-control author-middleinit">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" class="form-control author-lastname" required>
                </div>
            </div>
            <div class="col-md-1 remove-btn-container">
                <button type="button" class="btn btn-danger btn-sm remove-author-entry">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        authorEntriesContainer.appendChild(newEntry);
    });

    // Remove author entry
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('remove-author-entry') || e.target.closest('.remove-author-entry'))) {
            const authorEntriesContainer = document.getElementById('authorEntriesContainer');
            if (authorEntriesContainer.children.length > 1) {
                e.target.closest('.author-entry').remove();
            } else {
                alert('At least one author entry is required.');
            }
        }
    });

    // Save authors functionality
    document.getElementById('saveAuthors').addEventListener('click', function() {
        const authorEntries = document.querySelectorAll('.author-entry');
        const authorsData = [];
        let hasErrors = false;

        // Collect data from all author entries
        authorEntries.forEach(entry => {
            const firstname = entry.querySelector('.author-firstname').value.trim();
            const middle_init = entry.querySelector('.author-middleinit').value.trim();
            const lastname = entry.querySelector('.author-lastname').value.trim();
            
            if (!firstname || !lastname) {
                hasErrors = true;
                return;
            }
            
            authorsData.push({
                firstname: firstname,
                middle_init: middle_init,
                lastname: lastname
            });
        });
        
        if (hasErrors) {
            alert('First name and last name are required for all authors.');
            return;
        }
        
        if (authorsData.length === 0) {
            alert('Please add at least one author.');
            return;
        }
        
        // AJAX request to save all authors
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax/add_writers.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Add all new authors to the select options
                        const authorSelect = document.getElementById('authorSelect');
                        const coAuthorsSelect = document.getElementById('coAuthorsSelect');
                        const editorsSelect = document.getElementById('editorsSelect');
                        
                        response.authors.forEach(author => {
                            const newOption = document.createElement('option');
                            newOption.value = author.id;
                            newOption.textContent = author.name;
                            
                            authorSelect.appendChild(newOption.cloneNode(true));
                            coAuthorsSelect.appendChild(newOption.cloneNode(true));
                            editorsSelect.appendChild(newOption.cloneNode(true));
                        });
                        
                        // Select the first new author in the author dropdown if no author is selected
                        if (!authorSelect.value && response.authors.length > 0) {
                            authorSelect.value = response.authors[0].id;
                        }
                        
                        // Close the modal
                        $('#addAuthorModal').modal('hide');
                        
                        // Clear the form
                        document.getElementById('newAuthorForm').reset();
                        // Reset to just one author entry
                        document.getElementById('authorEntriesContainer').innerHTML = `
                            <div class="author-entry row mb-3">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>First Name</label>
                                        <input type="text" class="form-control author-firstname" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Middle Initial</label>
                                        <input type="text" class="form-control author-middleinit">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Last Name</label>
                                        <input type="text" class="form-control author-lastname" required>
                                    </div>
                                </div>
                                <div class="col-md-1 remove-btn-container">
                                    <button type="button" class="btn btn-danger btn-sm remove-author-entry">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        
                        alert(`Successfully added ${response.authors.length} author(s)!`);
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (e) {
                    alert('Error processing response: ' + e.message);
                }
            } else {
                alert('Error adding authors');
            }
        };
        xhr.send(JSON.stringify(authorsData));
    });
});

// Publisher Management Functionality
function showAddPublisherDialog() {
    Swal.fire({
        title: 'Add New Publisher',
        html: `
            <div id="sweetAlertPublisherContainer">
                <div class="publisher-entry row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Publisher Name</label>
                            <input type="text" class="form-control publisher-name" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Place of Publication</label>
                            <input type="text" class="form-control publisher-place" required>
                        </div>
                    </div>
                    <div class="col-md-1 remove-btn-container">
                        <button type="button" class="btn btn-danger btn-sm remove-publisher-entry">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <button type="button" id="swalAddPublisherEntry" class="btn btn-info btn-sm">
                    <i class="fas fa-plus"></i> Add Another Publisher
                </button>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save Publishers',
        cancelButtonText: 'Cancel',
        width: '800px',
        didOpen: () => {
            document.getElementById('swalAddPublisherEntry').addEventListener('click', function() {
                const container = document.getElementById('sweetAlertPublisherContainer');
                const newEntry = document.createElement('div');
                newEntry.className = 'publisher-entry row mb-3';
                newEntry.innerHTML = `
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Publisher Name</label>
                            <input type="text" class="form-control publisher-name" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Place of Publication</label>
                            <input type="text" class="form-control publisher-place" required>
                        </div>
                    </div>
                    <div class="col-md-1 remove-btn-container">
                        <button type="button" class="btn btn-danger btn-sm remove-publisher-entry">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                container.insertBefore(newEntry, document.getElementById('swalAddPublisherEntry'));
            });
        }
    });
}
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('bookForm');

    // Remove SweetAlert confirmation and directly submit the form
    form.addEventListener('submit', function(e) {
        // Perform validation before submission
        const accessionInputs = document.querySelectorAll('.accession-input');
        let hasError = false;
        let errorMessage = '';

        accessionInputs.forEach(input => {
            if (!input.value.trim()) {
                hasError = true;
                errorMessage = 'Please fill in all accession fields before submitting.';
            } else if (!/^\d+$/.test(input.value.trim())) {
                hasError = true;
                errorMessage = 'Accession fields must contain only numeric values.';
            }
        });

        if (hasError) {
            e.preventDefault();
            alert(errorMessage);
            return false;
        }

        // Format call numbers if needed
        const callNumberInputs = document.querySelectorAll('.call-number-input');
        const previewElements = document.querySelectorAll('.call-number-preview');

        if (callNumberInputs.length > 0 && previewElements.length > 0) {
            callNumberInputs.forEach((input, index) => {
                const preview = previewElements[index]?.textContent?.replace('→ ', '') || '';
                if (preview && input.value !== preview && !input.dataset.formattedCallNumber) {
                    input.value = preview; // Apply formatted value before submission
                }
            });
        }
    });

    // Initialize file input display
    document.querySelectorAll('.custom-file-input').forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'Choose file';
            this.nextElementSibling.textContent = fileName;
        });
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const accessionContainer = document.getElementById("accessionContainer");
    const publicationDetailsContainer = document.getElementById("publicationDetails");
    const contributorsContainer = document.getElementById("contributors");

    // Function to update publication details and contributors
    function updateDetails() {
        // Clear existing content
        publicationDetailsContainer.innerHTML = "";
        contributorsContainer.innerHTML = "";

        // Get all accession groups
        const accessionGroups = accessionContainer.querySelectorAll(".accession-group");

        accessionGroups.forEach((group, index) => {
            // Publication Details
            const publicationDiv = document.createElement("div");
            publicationDiv.className = "publication-detail mb-3";
            publicationDiv.innerHTML = `
                <h5>Accession Group ${index + 1}</h5>
                <div class="form-group">
                    <label for="publisher_${index}">Publisher</label>
                    <select class="form-control" id="publisher_${index}" name="publisher[]">
                        <option value="">Select Publisher</option>
                        <?php foreach ($publishers as $publisher): ?>
                            <option value="<?php echo $publisher['id']; ?>">
                                <?php echo $publisher['publisher']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="publish_date_${index}">Publication Year</label>
                    <input type="number" class="form-control" id="publish_date_${index}" name="publish_date[]" placeholder="Enter Year">
                </div>
            `;
            publicationDetailsContainer.appendChild(publicationDiv);

            // Contributors
            const contributorsDiv = document.createElement("div");
            contributorsDiv.className = "contributors-detail mb-3";
            contributorsDiv.innerHTML = `
                <h5>Accession Group ${index + 1}</h5>
                <div class="form-group">
                    <label for="author_${index}">Author</label>
                    <select class="form-control" id="author_${index}" name="author[]">
                        <option value="">Select Author</option>
                        <?php foreach ($writers as $writer): ?>
                            <option value="<?php echo $writer['id']; ?>">
                                <?php echo $writer['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="co_authors_${index}">Co-Authors</label>
                    <select class="form-control" id="co_authors_${index}" name="co_authors[${index}][]" multiple>
                        <?php foreach ($writers as $writer): ?>
                            <option value="<?php echo $writer['id']; ?>">
                                <?php echo $writer['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editors_${index}">Editors</label>
                    <select class="form-control" id="editors_${index}" name="editors[${index}][]" multiple>
                        <?php foreach ($writers as $writer): ?>
                            <option value="<?php echo $writer['id']; ?>">
                                <?php echo $writer['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            `;
            contributorsContainer.appendChild(contributorsDiv);
        });
    }

    // Listen for changes in accession groups
    accessionContainer.addEventListener("input", updateDetails);

    // Initial population
    updateDetails();
});
</script>