<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Add form reset check - must come before including process_add_book.php
$resetForm = false;
if (isset($_SESSION['reset_book_form']) && $_SESSION['reset_book_form'] === true) {
    $resetForm = true;
    unset($_SESSION['reset_book_form']); // Clear the flag
}

// Include the database connection
include '../db.php';

// Include the processing file for form submissions
include 'process/process_add_book.php';

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
$writers_query = "SELECT id, CONCAT(lastname, ', ', firstname, ' ', middle_init) AS name FROM writers";
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

<!-- Add these responsive styles before the closing </head> tag -->
<style>
/* Responsive improvements for add-book.php */
@media (max-width: 767px) {
    /* Input groups responsive adjustments */
    .input-group {
        flex-wrap: wrap;
    }

    .input-group > * {
        flex: 0 0 100%;
        margin-bottom: 5px;
        width: 100% !important;
    }

    .input-group .input-group-text {
        border-radius: 0.25rem 0.25rem 0 0;
        justify-content: center;
    }

    .input-group input.form-control,
    .input-group select.form-control {
        border-radius: 0 0 0.25rem 0.25rem;
    }

    /* Call number and copy number inputs */
    .input-group .call-number-input,
    .input-group .copy-number-input {
        min-width: 100% !important;
        flex: 0 0 100% !important;
    }

    /* Preview elements positioning */
    .call-number-preview {
        position: static !important;
        transform: none !important;
        display: block;
        margin-top: 5px;
        text-align: center;
        width: 100%;
    }

    /* Improve modal display on small screens */
    .swal2-popup {
        width: 90% !important;
        padding: 1em !important;
    }

    /* Tab navigation scrollable */
    #formTabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        display: flex;
        padding-bottom: 5px;
    }

    #formTabs .nav-item {
        float: none;
        display: inline-block;
    }

    /* Button spacing on mobile */
    .btn {
        margin-bottom: 5px;
    }

    .d-flex {
        flex-wrap: wrap;
    }

    /* Tab navigation buttons visibility */
    .tab-navigation-buttons {
        width: 100%;
        justify-content: space-between;
        margin-top: 10px;
    }

    /* Accession group layout improvements */
    .accession-group .row {
        margin-bottom: 10px;
    }

    .accession-group .col-md-2 {
        margin-top: 10px;
    }

    /* Fix modal overflow */
    .modal-dialog {
        max-width: 95%;
        margin: 10px auto;
    }
}

/* Make tab content more readable on mobile */
.tab-pane {
    padding: 15px 10px;
}

/* Ensure proper form layout */
@media (max-width: 576px) {
    .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }

    .row [class^="col-"] {
        padding-left: 5px;
        padding-right: 5px;
    }

    .card {
        margin-bottom: 15px;
    }

    /* Fix spacing in accession-group sections */
    .accession-details .row {
        margin-left: -5px;
        margin-right: -5px;
    }

    /* Adjust small preview text */
    small {
        display: inline-block;
        margin-top: 3px;
    }
}

/* Fix multi-select and preview elements */
.selected-preview {
    flex-wrap: wrap;
}

.selected-preview .badge {
    white-space: normal;
    text-align: left;
    margin-bottom: 5px;
}

/* Enhanced File Upload Styling */
.custom-file-upload {
    display: flex;
    flex-direction: column;
    align-items: center;
    border: 2px dashed #ccc;
    border-radius: 5px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s;
    background-color: #f8f9fc;
    margin-bottom: 15px;
    position: relative;
}

.custom-file-upload:hover, .custom-file-upload.dragover {
    border-color: #4e73df;
    background-color: #eaecf4;
}

.custom-file-upload input[type="file"] {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    opacity: 0;
    cursor: pointer;
}

.upload-icon {
    font-size: 2rem;
    color: #4e73df;
    margin-bottom: 10px;
}

.upload-text {
    color: #5a5c69;
    font-size: 0.9rem;
    text-align: center;
}

.file-preview {
    margin-top: 15px;
    position: relative;
    max-width: 100%;
    display: none;
    text-align: center;
}

.file-preview img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    border: 1px solid #e3e6f0;
    display: block;
    margin: 0 auto;
}

.file-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 5px;
    font-size: 0.8rem;
    background: #eaecf4;
    padding: 5px 10px;
    border-radius: 3px;
    color: #5a5c69;
}

.remove-file {
    color: #e74a3b;
    cursor: pointer;
}

/* Responsive fixes */
@media (max-width: 768px) {
    .image-upload-container {
        flex-direction: column;
    }

    .file-upload-column {
        width: 100%;
        margin-bottom: 15px;
    }

    .file-preview img {
        max-height: 150px;
    }
}
</style>

<!-- Main Content -->
<div id="content-wrapper" class="d-flex flex-column min-vh-100">
    <div id="content" class="flex-grow-1">
        <div class="container-fluid">
            <!-- Fix: Remove enctype if not needed -->
            <form id="bookForm" action="add-book.php" method="POST" enctype="multipart/form-data" class="h-100"
                  onkeydown="return event.key != 'Enter';">
                <div class="container-fluid d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                    <h1 class="h3 mb-2 text-gray-800">Add Book</h1>
                    <div class="button-group mt-2 mt-md-0">
                        <button type="button" class="btn btn-info mr-2 mb-2 mb-md-0" data-toggle="modal" data-target="#instructionsModal">
                            <i class="fas fa-question-circle"></i> Instructions
                        </button>
                        <button type="button" class="btn btn-secondary mr-2 mb-2 mb-md-0" onclick="window.history.back();">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-warning mr-2 mb-2 mb-md-0" data-clear-form>
                            <i class="fas fa-trash"></i> Clear Form
                        </button>
                    </div>
                </div>

                <!-- Add Error Message Display -->
                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $_SESSION['error_message']; ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Add Success Message Display -->
                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <?php echo $_SESSION['success_message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <!-- Progress Bar -->
                <div class="progress mb-4">
                    <div class="progress-bar" role="progressbar" style="width: 0%" id="formProgressBar"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12 col-lg-12">
                        <!-- Tab Navigation - Make scrollable on mobile -->
                        <div class="nav-tab-wrapper overflow-auto">
                            <ul class="nav nav-tabs flex-nowrap" id="formTabs" role="tablist">
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

                        <!-- Tab content with responsive styling -->
                        <div class="tab-content card border-0 shadow-sm p-3 p-md-4 mt-3" id="formTabsContent">
                            <!-- Tab content remains the same -->
                            <!-- Title Proper Tab -->
                            <div class="tab-pane fade show active" id="title-proper" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0">Title Information</h4>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm clear-tab-btn" data-tab-id="title-proper">
                                            <i class="fas fa-eraser"></i> Clear Tab
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
                            </div>
                            <!-- Subject Entry Tab -->
                            <div class="tab-pane fade" id="subject-entry" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0">Subject Entry</h4>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm clear-tab-btn" data-tab-id="subject-entry">
                                            <i class="fas fa-eraser"></i> Clear Tab
                                        </button>
                                    </div>
                                </div>
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
                                    <label>Program</label>
                                    <select class="form-control" name="program[]">
                                        <option value="">Select Program</option>
                                        <option value="General Education">General Education</option>
                                        <option value="Computer Science">Computer Science</option>
                                        <option value="Accountancy">Accountancy</option>
                                        <option value="Entrepreneurship">Entrepreneurship</option>
                                        <option value="Accountancy Information System">Accountancy Information System</option>
                                        <option value="Tourism Management">Tourism Management</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Details</label>
                                    <textarea class="form-control" name="subject_paragraphs[]" rows="3"></textarea>
                                </div>
                            </div>
                            <!-- Abstracts Tab -->
                            <div class="tab-pane fade" id="abstracts" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0">Abstract & Notes</h4>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm clear-tab-btn" data-tab-id="abstracts">
                                            <i class="fas fa-eraser"></i> Clear Tab
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
                                        <button type="button" class="btn btn-outline-secondary btn-sm clear-tab-btn" data-tab-id="description">
                                            <i class="fas fa-eraser"></i> Clear Tab
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Book Images (Optional)</label>
                                    <div class="row image-upload-container">
                                        <div class="col-md-6 file-upload-column">
                                            <label class="custom-file-upload front-image-upload">
                                                <input type="file" name="front_image" id="frontImageUpload" accept="image/*">
                                                <div class="upload-icon">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <div class="upload-text">
                                                    <strong>Front Cover</strong><br>
                                                    Click or drag an image here
                                                </div>
                                            </label>
                                            <div class="file-preview front-image-preview">
                                                <img src="#" alt="Front cover preview">
                                                <div class="file-info">
                                                    <span class="file-name"></span>
                                                    <i class="fas fa-times remove-file" data-target="frontImageUpload"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 file-upload-column">
                                            <label class="custom-file-upload back-image-upload">
                                                <input type="file" name="back_image" id="backImageUpload" accept="image/*">
                                                <div class="upload-icon">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <div class="upload-text">
                                                    <strong>Back Cover</strong><br>
                                                    Click or drag an image here
                                                </div>
                                            </label>
                                            <div class="file-preview back-image-preview">
                                                <img src="#" alt="Back cover preview">
                                                <div class="file-info">
                                                    <span class="file-name"></span>
                                                    <i class="fas fa-times remove-file" data-target="backImageUpload"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dimension">Dimensions (cm<sup>2</sup>)</label>
                                            <input type="text" class="form-control" id="dimension" name="dimension" placeholder="e.g., 23 cm²">
                                            <small class="form-text text-muted">Specify the physical dimensions of the book.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="prefix_pages">Prefix Pages</label>
                                            <input type="text" class="form-control" id="prefix_pages" name="prefix_pages" placeholder="e.g., xii">
                                            <small class="form-text text-muted">Enter the number of prefatory pages in Roman numerals.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="main_pages">Main Pages</label>
                                            <input type="text" class="form-control" id="main_pages" name="main_pages" placeholder="e.g., 350 p.">
                                            <small class="form-text text-muted">Provide the total number of main pages in the book.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Supplementary Content</label>
                                            <select class="form-control" name="supplementary_content[]" multiple>
                                                <option value="Appendix">Appendix</option>
                                                <option value="Bibliography">Bibliography</option>
                                                <option value="Glossary">Glossary</option>
                                                <option value="Index">Index</option>
                                                <option value="Illustrations">Illustrations</option>
                                                <option value="Maps">Maps</option>
                                                <option value="Tables">Tables</option>
                                            </select>
                                            <small class="form-text text-muted">Select any additional content included in the book.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Local Information Tab -->
                            <div class="tab-pane fade" id="local-info" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0">Local Information</h4>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm clear-tab-btn" data-tab-id="local-info">
                                            <i class="fas fa-eraser"></i> Clear Tab
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
                                            <li>Example: "REF HD69.B7 W56 c2024 vol1 c1" (shelf location + classification + author cutter + year + vol + copy)</li>
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
                                    <input type="text" class="form-control" id="url" name="url" placeholder="https://example.com">
                                </div>
                            </div>
                            <!-- Publication Tab -->
                            <div class="tab-pane fade" id="publication" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0">Publication Details</h4>
                                    <div class="btn-group">
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
                                                    <option value="<?php echo $publisher['publisher']; ?>"><?php echo $publisher['place']; ?> ; <?php echo $publisher['publisher'] ?? 'Unknown'; ?></option>
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
                                                    <select id="authorSelect" name="author[]" class="form-control" multiple>
                                                        <?php foreach ($writers as $writer): ?>
                                                            <option value="<?php echo $writer['id']; ?>"><?php echo $writer['name']; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple options. <span class="text-info">Either authors or editors must be provided.</span></small>
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
                                                    <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple options.</small>
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
                                                    <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple options. <span class="text-info">Either authors or editors must be provided.</span></small>
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

<!-- Instructions Modal (detailed version) -->
<div class="modal fade" id="instructionsModal" tabindex="-1" role="dialog" aria-labelledby="instructionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="instructionsModalLabel">
                    <i class="fas fa-info-circle mr-2"></i>How to Add a New Book
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Add new tab for book entry methods -->
                <ul class="nav nav-tabs mb-3" id="instructionMethodTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="methods-tab" data-toggle="tab" href="#methods" role="tab" aria-controls="methods" aria-selected="true">Book Entry Methods</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="navigation-tab" data-toggle="tab" href="#navigation" role="tab" aria-controls="navigation" aria-selected="false">Form Navigation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="accession-tab" data-toggle="tab" href="#accession" role="tab" aria-controls="accession" aria-selected="false">Accession & Call Numbers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="author-tab" data-toggle="tab" href="#author" role="tab" aria-controls="author" aria-selected="false">Author & Publisher Information</a>
                    </li>
                </ul>

                <div class="tab-content" id="instructionTabsContent">
                    <!-- New Tab: Book Entry Methods -->
                    <div class="tab-pane fade show active" id="methods" role="tabpanel" aria-labelledby="methods-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="m-0 font-weight-bold">Advanced Form (Current)</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Purpose:</strong> Complete, detailed book cataloging with all possible metadata.</p>
                                        <h6 class="font-weight-bold">Features:</h6>
                                        <ul>
                                            <li>Multiple tabs for organizing different types of information</li>
                                            <li>Support for multiple accession numbers and copies</li>
                                            <li>Full MARC21-compatible fields</li>
                                            <li>Comprehensive subject categorization</li>
                                            <li>Supports complex publication information</li>
                                        </ul>
                                        <p><strong>Who should use this:</strong></p>
                                        <ul>
                                            <li>Librarians with cataloging experience</li>
                                            <li>When adding rare or special collection items</li>
                                            <li>When full bibliographic details are required</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="m-0 font-weight-bold">Step-by-Step Form</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Purpose:</strong> Simplified, guided book entry with contextual help.</p>
                                        <h6 class="font-weight-bold">Features:</h6>
                                        <ul>
                                            <li>One screen at a time with clear instructions</li>
                                            <li>Required fields clearly marked</li>
                                            <li>Help text for each field</li>
                                            <li>Automatic data validation</li>
                                            <li>Preview of entered information before submission</li>
                                        </ul>
                                        <p><strong>Who should use this:</strong></p>
                                        <ul>
                                            <li>New library staff members</li>
                                            <li>When adding standard books with basic information</li>
                                            <li>When training new catalogers</li>
                                        </ul>
                                        <div class="mt-3">
                                            <a href="step-by-step-add-book.php" class="btn btn-success btn-sm">
                                                <i class="fas fa-tasks"></i> Switch to Step-by-Step Form
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Tabs -->
                    <div class="tab-pane fade" id="navigation" role="tabpanel" aria-labelledby="navigation-tab">
                        <!-- Existing navigation content -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold">Form Navigation</h6>
                            </div>
                            <div class="card-body">
                                <ul>
                                    <li>This form is divided into multiple tabs for easier data entry.</li>
                                    <li>Complete each tab before proceeding to the next.</li>
                                    <li>Required fields are marked with an asterisk (*).</li>
                                    <li>Use the tab navigation to move between sections.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="accession" role="tabpanel" aria-labelledby="accession-tab">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold">Accession and Call Numbers</h6>
                            </div>
                            <div class="card-body">
                                <ul>
                                    <li><strong>Accession Number</strong>: Enter a unique identifier for each physical copy.</li>
                                    <li><strong>Call Number</strong>: Format should follow library standards (e.g., "TR Z936.98 L39 c2023 c1").</li>
                                    <li><strong>Multiple Copies</strong>: You can specify multiple copies, and the system will auto-increment accession numbers.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="author" role="tabpanel" aria-labelledby="author-tab">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold">Author, Publisher, and Subject Information</h6>
                            </div>
                            <div class="card-body">
                                <ul>
                                    <li>Select authors from the dropdown or add new authors if needed.</li>
                                    <li>You can specify co-authors and editors separately.</li>
                                    <li>Subject categories help with classification and searching.</li>
                                    <li>Multiple subject entries can be added for more detailed cataloging.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
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
document.addEventListener('DOMContentLoaded', function() {
    // File upload previews
    function handleFileSelect(fileInputId, previewClass) {
        const fileInput = document.getElementById(fileInputId);
        const previewDiv = document.querySelector(`.${previewClass}`);
        const previewImg = previewDiv.querySelector('img');
        const fileName = previewDiv.querySelector('.file-name');
        const uploadLabel = document.querySelector(`.${previewClass.replace('preview', 'upload')}`);
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    fileName.textContent = file.name;
                    previewDiv.style.display = 'block';
                    uploadLabel.style.display = 'none';
                    
                    // Calculate and display file size
                    let size = file.size;
                    let sizeText = "";
                    if (size < 1024) {
                        sizeText = size + " bytes";
                    } else if (size < 1048576) {
                        sizeText = (size / 1024).toFixed(1) + " KB";
                    } else {
                        sizeText = (size / 1048576).toFixed(1) + " MB";
                    }
                    fileName.textContent = `${file.name} (${sizeText})`;
                };
                
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Handle file removal
    document.querySelectorAll('.remove-file').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const fileInput = document.getElementById(targetId);
            fileInput.value = '';
            
            // Determine which preview to hide
            const previewClass = targetId === 'frontImageUpload' ? 'front-image-preview' : 'back-image-preview';
            const uploadClass = targetId === 'frontImageUpload' ? 'front-image-upload' : 'back-image-upload';
            
            document.querySelector(`.${previewClass}`).style.display = 'none';
            document.querySelector(`.${uploadClass}`).style.display = 'flex';
        });
    });
    
    // Drag and drop functionality
    function setupDragDrop(uploadAreaSelector) {
        const uploadArea = document.querySelector(uploadAreaSelector);
        if (!uploadArea) return;
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            uploadArea.classList.add('dragover');
        }
        
        function unhighlight() {
            uploadArea.classList.remove('dragover');
        }
        
        uploadArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            const fileInput = uploadArea.querySelector('input[type="file"]');
            
            if (files.length > 0 && fileInput) {
                fileInput.files = files;
                // Trigger change event to update preview
                fileInput.dispatchEvent(new Event('change'));
            }
        }
    }
    
    // Initialize file handling
    handleFileSelect('frontImageUpload', 'front-image-preview');
    handleFileSelect('backImageUpload', 'back-image-preview');
    
    // Initialize drag & drop
    setupDragDrop('.front-image-upload');
    setupDragDrop('.back-image-upload');
    
    // Validate image files
    function validateImageFile(fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, PNG, GIF, and WEBP files are allowed.');
                    this.value = '';
                }
            }
        });
    }
    
    // Initialize file validation
    validateImageFile(document.getElementById('frontImageUpload'));
    validateImageFile(document.getElementById('backImageUpload'));
});
</script>