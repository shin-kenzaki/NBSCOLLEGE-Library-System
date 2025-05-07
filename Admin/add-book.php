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

// Define contributor roles for the merged dropdown
$contributor_roles = [
    'author' => 'Author',
    'co_author' => 'Co-Author', 
    'editor' => 'Editor',
    'illustrator' => 'Illustrator',
    'translator' => 'Translator'
];

// Fetch publishers for the dropdown
$publishers_query = "SELECT id, publisher, place FROM publishers";
$publishers_result = mysqli_query($conn, $publishers_query);
$publishers = [];
while ($row = mysqli_fetch_assoc($publishers_result)) {
    $publishers[] = $row;
}

$accession_error = '';

?>

<!-- Include the new contributor select CSS -->
<link rel="stylesheet" href="css/contributor-select.css">

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
</style>

<!-- Main Content -->
<div id="content-wrapper" class="d-flex flex-column min-vh-100">
    <div id="content" class="flex-grow-1">
        <div class="container-fluid">
            <!-- UPDATE: Add onsubmit to ensure corporate contributors are included -->
            <form id="bookForm" action="add-book.php" method="POST" enctype="multipart/form-data" class="h-100"
                  onsubmit="return prepareFormSubmission()" onkeydown="return event.key != 'Enter';">
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

                <div class="row">
                    <div class="col-xl-12 col-lg-12">
                        <!-- Tab Navigation - Make scrollable on mobile -->
                        <div class="nav-tab-wrapper overflow-auto">
                            <ul class="nav nav-tabs flex-nowrap" id="formTabs" role="tablist" style="white-space: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 5px;">
                                <li class="nav-item">
                                    <a class="nav-link active" id="title-tab" data-toggle="tab" href="#title-proper" role="tab">
                                        <i class="fas fa-book"></i> Title Proper
                                        <span class="tab-status-indicator" id="title-tab-indicator"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="subject-tab" data-toggle="tab" href="#subject-entry" role="tab">
                                        <i class="fas fa-tag"></i> Access Point
                                        <span class="tab-status-indicator" id="subject-tab-indicator"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="abstracts-tab" data-toggle="tab" href="#abstracts" role="tab">
                                        <i class="fas fa-file-alt"></i> Abstract & Notes
                                        <span class="tab-status-indicator" id="abstracts-tab-indicator"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="description-tab" data-toggle="tab" href="#description" role="tab">
                                        <i class="fas fa-info-circle"></i> Description
                                        <span class="tab-status-indicator" id="description-tab-indicator"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="local-info-tab" data-toggle="tab" href="#local-info" role="tab">
                                        <i class="fas fa-map-marker-alt"></i> Local Information
                                        <span class="tab-status-indicator" id="local-info-tab-indicator"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="publication-tab" data-toggle="tab" href="#publication" role="tab">
                                        <i class="fas fa-print"></i> Publication
                                        <span class="tab-status-indicator" id="publication-tab-indicator"></span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <style>
                        /* Tab status indicator styling */
                        .tab-status-indicator {
                            position: relative;
                            display: inline-block;
                            width: 8px;
                            height: 8px;
                            border-radius: 50%;
                            margin-left: 5px;
                        }
                        
                        .tab-status-indicator.required {
                            background-color: #e74a3b; /* Red for missing required fields */
                            animation: pulse-red 1.5s infinite;
                        }
                        
                        .tab-status-indicator.complete {
                            background-color: #1cc88a; /* Green for completed */
                        }
                        
                        .tab-status-indicator.partial {
                            background-color: #f6c23e; /* Yellow for partially completed */
                        }
                        
                        @keyframes pulse-red {
                            0% { box-shadow: 0 0 0 0 rgba(231, 74, 59, 0.4); }
                            70% { box-shadow: 0 0 0 6px rgba(231, 74, 59, 0); }
                            100% { box-shadow: 0 0 0 0 rgba(231, 74, 59, 0); }
                        }
                        </style>

                        <script>
                        // Function to update tab indicators based on field validation
                        function updateTabIndicators() {
                            // Map of tab IDs to their corresponding content panes
                            const tabMap = {
                                'title-tab': 'title-proper',
                                'subject-tab': 'subject-entry',
                                'abstracts-tab': 'abstracts',
                                'description-tab': 'description',
                                'local-info-tab': 'local-info',
                                'publication-tab': 'publication'
                            };
                            
                            // Check each tab
                            Object.entries(tabMap).forEach(([tabId, contentId]) => {
                                const tabPane = document.getElementById(contentId);
                                const indicator = document.getElementById(`${tabId}-indicator`);
                                
                                if (!tabPane || !indicator) return;
                                
                                // Find all required fields in this tab
                                const requiredFields = tabPane.querySelectorAll('input[required], select[required], textarea[required]');
                                
                                if (requiredFields.length === 0) {
                                    // No required fields in this tab
                                    indicator.className = 'tab-status-indicator';
                                    return;
                                }
                                
                                // Count how many required fields are filled
                                let filledCount = 0;
                                requiredFields.forEach(field => {
                                    if (field.value.trim() !== '') {
                                        filledCount++;
                                    }
                                });
                                
                                // Update indicator class based on completion status
                                if (filledCount === 0) {
                                    indicator.className = 'tab-status-indicator required';
                                    indicator.title = 'Required fields need attention';
                                } else if (filledCount === requiredFields.length) {
                                    indicator.className = 'tab-status-indicator complete';
                                    indicator.title = 'All required fields completed';
                                } else {
                                    indicator.className = 'tab-status-indicator partial';
                                    indicator.title = `${filledCount} of ${requiredFields.length} required fields completed`;
                                }
                            });
                        }
                        
                        // Run on page load
                        document.addEventListener('DOMContentLoaded', function() {
                            // Initial check
                            updateTabIndicators();
                            
                            // Update on any input change
                            document.querySelectorAll('input, select, textarea').forEach(input => {
                                input.addEventListener('input', updateTabIndicators);
                                input.addEventListener('change', updateTabIndicators);
                            });
                            
                            // Special handling for dynamically added inputs (like in accession groups)
                            const observer = new MutationObserver(function(mutations) {
                                updateTabIndicators();
                                
                                // Add listeners to any new inputs
                                mutations.forEach(function(mutation) {
                                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                                        mutation.addedNodes.forEach(function(node) {
                                            if (node.nodeType === Node.ELEMENT_NODE) {
                                                node.querySelectorAll('input, select, textarea').forEach(input => {
                                                    input.addEventListener('input', updateTabIndicators);
                                                    input.addEventListener('change', updateTabIndicators);
                                                });
                                            }
                                        });
                                    }
                                });
                            });
                            
                            // Observe the entire form for changes
                            const form = document.getElementById('bookForm');
                            if (form) {
                                observer.observe(form, { childList: true, subtree: true });
                            }
                        });
                        </script>

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
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Main title of the book.
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label for="preferred_title">Preferred Title</label>
                                    <input type="text" class="form-control" id="preferred_title" name="preferred_title">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Alternative title, if applicable.
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label for="parallel_title">Parallel Title</label>
                                    <input type="text" class="form-control" id="parallel_title" name="parallel_title">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Title in another language.
                                    </small>
                                </div>
                            </div>
                            <!-- Access Point Tab -->
                            <div class="tab-pane fade" id="subject-entry" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="mb-0">Access Point</h4>
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
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Select the primary subject classification for this book.
                                    </small>
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
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Choose the academic program this book is most relevant to.
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label>Details</label>
                                    <textarea class="form-control" name="subject_paragraphs[]" 
                                    rows="3" placeholder="Enter additional details about this subject"></textarea>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Provide specific subject terms, keywords, or descriptions that help identify the content.
                                    </small>
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
                                    <label for="abstract">Summary/Abstract</label>
                                    <textarea class="form-control" id="abstract" name="abstract" rows="4"></textarea>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Brief summary of the book's content.
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label for="notes">Notes/Contents</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="4"></textarea>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Additional notes about the book.
                                    </small>
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
                                <!-- New file inputs with custom file upload components -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Front Cover Image</label>
                                            <div class="file-upload-container" id="front-cover-upload">
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
                                            <div class="file-upload-container" id="back-cover-upload">
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

                                <script>
                                // Add function to clear all file uploads
                                window.clearFileUploads = function() {
                                  const fileUploads = document.querySelectorAll('.file-upload-container');
                                  
                                  fileUploads.forEach(container => {
                                    const input = container.querySelector('.file-upload-input');
                                    const previewContainer = container.querySelector('.file-preview-container');
                                    const preview = container.querySelector('.file-preview');
                                    const fileName = container.querySelector('.file-name');
                                    const fileSize = container.querySelector('.file-size');
                                    
                                    // Clear file input
                                    if (input) input.value = '';
                                    
                                    // Reset preview
                                    if (preview) preview.style.backgroundImage = '';
                                    
                                    // Hide preview container
                                    if (previewContainer) previewContainer.classList.remove('show');
                                    
                                    // Clear file info
                                    if (fileName) fileName.textContent = '';
                                    if (fileSize) fileSize.textContent = '';
                                    
                                    // Remove any validation classes
                                    container.classList.remove('is-invalid');
                                  });
                                };

                                // Connect the clearFileUploads function to the existing clear form functionality
                                document.addEventListener('DOMContentLoaded', function() {
                                  const clearFormBtn = document.querySelector('[data-clear-form]');
                                  const clearTabBtns = document.querySelectorAll('.clear-tab-btn');
                                  
                                  if (clearFormBtn) {
                                    const originalHandler = clearFormBtn.onclick;
                                    clearFormBtn.onclick = function(e) {
                                      // Call the original handler if it exists
                                      if (originalHandler) originalHandler.call(this, e);
                                      
                                      // Clear file uploads
                                      window.clearFileUploads();
                                    };
                                  }
                                  
                                  // Also handle "Clear Tab" for the description tab
                                  clearTabBtns.forEach(btn => {
                                    if (btn.getAttribute('data-tab-id') === 'description') {
                                      const originalHandler = btn.onclick;
                                      btn.onclick = function(e) {
                                        // Call the original handler if it exists
                                        if (originalHandler) originalHandler.call(this, e);
                                        
                                        // Clear file uploads
                                        window.clearFileUploads();
                                      };
                                    }
                                  });
                                });
                                </script>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Dimension (cm²)</label>
                                        <input type="text" class="form-control" name="dimension" placeholder="e.g. 23 x 24 or 23 cm²">
                                            <div class="mt-2">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> Format examples: 23 x 24, 23 * 24, or just 24 (cm² will be added automatically for single numbers)

                                                </small>
                                            </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="prefix_pages">Prefix Pages</label>
                                            <input type="text" class="form-control" id="prefix_pages" name="prefix_pages" placeholder="e.g., xii">
                                            <div class="mt-2">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> Enter the number of prefatory pages in Roman numerals.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="main_pages">Main Pages</label>
                                            <input type="text" class="form-control" id="main_pages" name="main_pages" placeholder="e.g., 350a">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> Provide the total number of main pages in the book. (Format examples: 345p)
                                                </small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Supplementary Content</label>
                                            <select class="form-control" name="supplementary_content[]" multiple id="supplementarySelect" onchange="updateSupplementaryPreview()">
                                                <option value="Appendix">Appendix</option>
                                                <option value="Bibliography">Bibliography</option>
                                                <option value="Glossary">Glossary</option>
                                                <option value="Index">Index</option>
                                                <option value="Illustrations">Illustrations</option>
                                                <option value="Maps">Maps</option>
                                                <option value="Tables">Tables</option>
                                            </select>
                                            <!-- Supplementary Content Preview -->
                                            <div class="preview-container supplementary-preview">
                                                <div id="supplementaryPreview" class="selected-preview mt-2"></div>
                                                <div id="supplementaryCount" class="selection-count-badge supplementary-badge">0</div>
                                            </div>
                                            <small class="text-primary d-block mb-1">
                                                <i class="fas fa-keyboard mr-1"></i> Hold <kbd>Ctrl</kbd> (Windows) or <kbd>⌘ Cmd</kbd> (Mac) to select multiple items
                                            </small>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i> Select any additional content included in the book.
                                            </small>
                                        </div>
                                        
                                        <script>
                                        function updateSupplementaryPreview() {
                                            const select = document.getElementById('supplementarySelect');
                                            const preview = document.getElementById('supplementaryPreview');
                                            const countBadge = document.getElementById('supplementaryCount');
                                            
                                            // Clear current preview
                                            preview.innerHTML = '';
                                            const selectedCount = select.selectedOptions.length;
                                            
                                            // Update count badge
                                            countBadge.textContent = selectedCount;
                                            countBadge.style.display = selectedCount > 0 ? 'flex' : 'none';
                                            
                                            if (selectedCount > 0) {
                                                countBadge.classList.remove('pulse');
                                                setTimeout(() => countBadge.classList.add('pulse'), 10);
                                            }
                                            
                                            // Generate preview badges
                                            Array.from(select.selectedOptions).forEach((option, index) => {
                                                const badge = document.createElement('span');
                                                badge.className = 'preview-badge supplementary-item';
                                                badge.style.animationDelay = `${index * 0.05}s`;
                                                
                                                let displayName = option.text;
                                                if (displayName.length > 30) {
                                                    displayName = displayName.substring(0, 27) + '...';
                                                    badge.title = option.text;
                                                }
                                                
                                                badge.innerHTML = `${displayName} <i class="fas fa-times remove-icon" data-value="${option.value}"></i>`;
                                                preview.appendChild(badge);
                                            });
                                            
                                            // Add click handlers to remove icons
                                            preview.querySelectorAll('.remove-icon').forEach(icon => {
                                                icon.addEventListener('click', function(e) {
                                                    e.stopPropagation();
                                                    const value = this.getAttribute('data-value');
                                                    const option = Array.from(select.options).find(opt => opt.value === value);
                                                    if (option) option.selected = false;
                                                    updateSupplementaryPreview();
                                                });
                                            });
                                            
                                            // Save selected values to form data if available
                                            if (typeof saveFormData === 'function') {
                                                saveFormData();
                                            }
                                        }
                                        
                                        // Initialize preview when DOM is loaded
                                        document.addEventListener('DOMContentLoaded', function() {
                                            // First check if we need to restore selections from saved form data
                                            const savedData = localStorage.getItem('bookFormData');
                                            if (savedData) {
                                                try {
                                                    const formData = JSON.parse(savedData);
                                                    const supplementarySelect = document.getElementById('supplementarySelect');
                                                    
                                                    if (formData['supplementary_content[]'] && Array.isArray(formData['supplementary_content[]'])) {
                                                        // Restore selected options
                                                        Array.from(supplementarySelect.options).forEach(option => {
                                                            option.selected = formData['supplementary_content[]'].includes(option.value);
                                                        });
                                                    }
                                                } catch (e) {
                                                    console.error('Error restoring supplementary content selections:', e);
                                                }
                                            }
                                            
                                            // Initialize supplementary content preview
                                            updateSupplementaryPreview();
                                            
                                            // Hook into the form clear functionality
                                            const clearFormBtn = document.querySelector('[data-clear-form]');
                                            const clearTabBtns = document.querySelectorAll('.clear-tab-btn');
                                            
                                            if (clearFormBtn) {
                                                const originalClickHandler = clearFormBtn.onclick;
                                                clearFormBtn.onclick = function(e) {
                                                    if (originalClickHandler) originalClickHandler.call(this, e);
                                                    // Reset supplementary select and update preview
                                                    const supplementarySelect = document.getElementById('supplementarySelect');
                                                    Array.from(supplementarySelect.options).forEach(option => {
                                                        option.selected = false;
                                                    });
                                                    updateSupplementaryPreview();
                                                };
                                            }
                                            
                                            // Handle clearing when "Clear Tab" is clicked for the description tab
                                            clearTabBtns.forEach(btn => {
                                                if (btn.getAttribute('data-tab-id') === 'description') {
                                                    btn.addEventListener('click', function() {
                                                        // Reset supplementary select and update preview
                                                        const supplementarySelect = document.getElementById('supplementarySelect');
                                                        Array.from(supplementarySelect.options).forEach(option => {
                                                            option.selected = false;
                                                        });
                                                        updateSupplementaryPreview();
                                                    });
                                                }
                                            });
                                        });
                                        </script>
                                        
                                        <style>
                                        .supplementary-preview .preview-badge {
                                            background: linear-gradient(135deg, #4169E1 0%, #0000CD 100%);
                                        }
                                        
                                        .supplementary-badge {
                                            background-color: #4169E1 !important;
                                        }
                                        </style>
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
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Accession Group 1</label>
                                                            <input type="text" class="form-control accession-input" name="accession[]"
                                                                placeholder="e.g., 00001, 00002, etc." required>
                                                            <div class="mt-2">
                                                                <small class="form-text text-muted">
                                                                    <i class="fas fa-info-circle mr-1"></i> If you enter 0001 and set 3 copies, it will create: 0001, 0002, 0003
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Number of Copies</label>
                                                            <input type="number" class="form-control copies-input" name="number_of_copies[]" min="1" value="1" required>
                                                            <div class="mt-2">
                                                                <small class="form-text text-muted">
                                                                    <i class="fas fa-info-circle mr-1"></i> System will auto-increment accession numbers
                                                                </small>
                                                            </div>
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
                                        <div class="mt-2">
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i> Add another accession group for books with different ISBN/edition/volume combinations
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <!-- Call Numbers -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Call Numbers</h5>
                                        
                                        <div class="alert alert-info py-2 mb-3 mt-3">
                                            <i class="fas fa-info-circle mr-1"></i> 
                                            <strong>How It Works:</strong> You only need to enter the classification number and author cutter. The system automatically adds shelf location, year, volume, part, and copy number to create the complete call number.
                                        </div>
                                        
                                        <div class="alert alert-warning py-2">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            <strong>Call Number Format Guidelines:</strong>
                                            <ul class="mb-0 small pl-4 mt-2">
                                                <li>Enter only the main classification and author cutter (e.g., "HD69.B7 W56")</li>
                                                <li>Use a single space between classification and author cutter</li>
                                                <li>Don't include the shelf location, year, volume, part or copy number - these are added automatically</li>
                                                <li>Preview shows the complete formatted call number that will be saved</li>
                                                <li><strong>Example:</strong> You enter "HD69.B7 W56" → System saves as "REF HD69.B7 W56 c2023 v.2 pt.3 c1"</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="card-body">
                                        <div id="callNumberContainer" class="mb-4">
                                            <!-- Call numbers will be generated here by JavaScript -->
                                        </div>
                                        
                                        <div class="mt-3">
                                            <button type="button" id="generateCallNumbersBtn" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-sync mr-1"></i> Reset Call Numbers
                                            </button>
                                            
                                            <div class="mt-2">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> Click this button to regenerate call numbers if they don't appear automatically
                                                </small>
                                            </div>
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
                                                <option value="Audio">Audio</option>
                                                <option value="Multimedia">Multimedia</option>
                                            </select>
                                            <div class="mt-2">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> Specifies the fundamental form of the content
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="media_type">Media Type</label>
                                            <select class="form-control" id="media_type" name="media_type">
                                                <option value="Print">Print</option>
                                                <option value="Digital">Digital</option>
                                                <option value="Audio">Audio</option>
                                                <option value="Video">Video</option>
                                                <option value="Microform">Microform</option>
                                            </select>
                                            <div class="mt-2">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> General type of intermediation device required
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="carrier_type">Carrier Type</label>
                                            <select class="form-control" id="carrier_type" name="carrier_type">
                                                <option value="Book">Book</option>
                                                <option value="CD">CD</option>
                                                <option value="DVD">DVD</option>
                                                <option value="USB">USB</option>
                                                <option value="Online Resource">Online Resource</option>
                                            </select>
                                            <div class="mt-2">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> The physical medium or storage format of the resource
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="language">Language</label>
                                            <select class="form-control" id="language" name="language">
                                                <option value="English">English</option>
                                                <option value="Filipino">Filipino</option>
                                                <option value="Spanish">Spanish</option>
                                                <option value="French">French</option>
                                                <option value="Chinese">Chinese</option>
                                                <option value="Japanese">Japanese</option>
                                                <option value="Multiple">Multiple Languages</option>
                                            </select>
                                            <div class="mt-2">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> Primary language of the resource's content
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="url">URL or Text (if applicable)</label>
                                    <input type="text" class="form-control" id="url" name="url" placeholder="Enter URL or any text">
                                    <div class="mt-2">
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle mr-1"></i> For digital resources, enter the web address or any descriptive text about resource access
                                        </small>
                                    </div>
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
                                            <small class="form-text text-muted">
                                                <i class="fas fa-search mr-1"></i> Search by publisher name or place of publication
                                            </small>
                                            <select class="form-control" id="publisher" name="publisher" required>
                                                <option value="">Select Publisher</option>
                                                <?php foreach ($publishers as $publisher): ?>
                                                    <option value="<?php echo $publisher['publisher']; ?>" data-place="<?php echo $publisher['place']; ?>"><?php echo $publisher['place']; ?> : <?php echo $publisher['publisher'] ?? 'Unknown'; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted mt-1">
                                                <i class="fas fa-info-circle mr-1"></i> Format: Place of publication ; Publisher name
                                            </small>
                                            <script>
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    const publisherSearch = document.getElementById('publisherSearch');
                                                    const publisherSelect = document.getElementById('publisher');
                                                    const publisherOptions = Array.from(publisherSelect.options);
                                                    
                                                    // Function to restore all publisher options
                                                    window.restorePublisherOptions = function() {
                                                        // Clear all options except the first placeholder
                                                        while (publisherSelect.options.length > 1) {
                                                            publisherSelect.remove(1);
                                                        }
                                                        
                                                        // Add all options back
                                                        publisherOptions.forEach(option => {
                                                            if (option.value !== '') {
                                                                publisherSelect.add(option.cloneNode(true));
                                                            }
                                                        });
                                                        
                                                        // Reset the search input
                                                        publisherSearch.value = '';
                                                        
                                                        // Reset selection to placeholder
                                                        publisherSelect.selectedIndex = 0;
                                                    };
                                                    
                                                    publisherSearch.addEventListener('input', function() {
                                                        const searchText = this.value.toLowerCase();
                                                        
                                                        // Clear current options except the first one
                                                        while (publisherSelect.options.length > 1) {
                                                            publisherSelect.remove(1);
                                                        }
                                                        
                                                        // Filter and add matching options
                                                        publisherOptions.forEach(option => {
                                                            if (option.value === '') return; // Skip the first empty option
                                                            
                                                            const optionText = option.textContent.toLowerCase();
                                                            const place = option.getAttribute('data-place')?.toLowerCase() || '';
                                                            const publisher = option.value.toLowerCase();
                                                            
                                                            if (optionText.includes(searchText) || 
                                                                place.includes(searchText) || 
                                                                publisher.includes(searchText)) {
                                                                publisherSelect.add(option.cloneNode(true));
                                                            }
                                                        });
                                                        
                                                        // If no search text, clear dropdown first then restore all options
                                                        if (searchText === '') {
                                                            // Clear all options except the first placeholder
                                                            while (publisherSelect.options.length > 1) {
                                                                publisherSelect.remove(1);
                                                            }
                                                            
                                                            // Then add all options back
                                                            publisherOptions.forEach(option => {
                                                                if (option.value !== '') {
                                                                    publisherSelect.add(option.cloneNode(true));
                                                                }
                                                            });
                                                        }
                                                        
                                                        // Auto-select first result if available
                                                        if (publisherSelect.options.length > 1) {
                                                            publisherSelect.selectedIndex = 1;
                                                            // Trigger change event to update any dependent UI
                                                            publisherSelect.dispatchEvent(new Event('change'));
                                                        }
                                                    });
                                                    
                                                    // Hook into form clear functionality
                                                    document.querySelector('[data-clear-form]')?.addEventListener('click', function() {
                                                        // Restore publisher options after a small delay to ensure form reset is complete
                                                        setTimeout(window.restorePublisherOptions, 100);
                                                    });
                                                    
                                                    // Also hook into "Clear Tab" for publication tab
                                                    document.querySelector('.clear-tab-btn[data-tab-id="publication"]')?.addEventListener('click', function() {
                                                        // Restore publisher options after a small delay
                                                        setTimeout(window.restorePublisherOptions, 100);
                                                    });
                                                });
                                            </script>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="publish_date">Publication Year</label>
                                            <input type="number" class="form-control" id="publish_date" name="publish_date"
                                                min="1800" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>" required>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i> Enter the year the book was published
                                            </small>
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
                                        <!-- Contributors Section -->
                                        <div class="row">
                                            <!-- Individual Contributors Card - Left Column -->
                                            <div class="col-lg-6">
                                                <div class="card mb-4">
                                                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                                        <h6 class="m-0 font-weight-bold text-primary">Individual Contributors</h6>
                                                        <button type="button" id="addNewAuthorBtn" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-plus-circle"></i> Add New Author
                                                        </button>
                                                    </div>
                                                    <div class="card-body">
                                                        <p class="small text-muted mb-3">
                                                            <i class="fas fa-info-circle mr-1"></i> Select authors, editors, and other individual contributors to this publication
                                                        </p>
                                                        <div id="contributorSelectContainer"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Corporate Contributors Card - Right Column -->
                                            <div class="col-lg-6">
                                                <div class="card mb-4">
                                                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                                        <h6 class="m-0 font-weight-bold text-primary">Corporate Contributors</h6>
                                                        <button type="button" id="addNewCorporateBtn" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-plus-circle"></i> Add New Corporate
                                                        </button>
                                                    </div>
                                                    <div class="card-body">
                                                        <p class="small text-muted mb-3">
                                                            <i class="fas fa-info-circle mr-1"></i> Select corporate entities (organizations, institutions, etc.) that contributed to this publication
                                                        </p>
                                                        
                                                        <!-- Corporate Contributor Select Component -->
                                                        <div id="corporateContributorSelectContainer"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        

                                        <!-- Enhanced preview styles -->
                                        <style>
                                        
                                        .selected-preview .preview-badge {
                                            display: inline-flex;
                                            align-items: center;
                                            margin: 3px;
                                            padding: 4px 8px;
                                            background: linear-gradient(135deg, #4e73df 0%, #2e59d9 100%);
                                            color: white;
                                            border-radius: 30px;
                                            font-size: 0.85rem;
                                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                            transition: all 0.2s ease-in-out;
                                            position: relative;
                                            overflow: hidden;
                                        }
                                        
                                        .selected-preview .preview-badge::before {
                                            content: '';
                                            position: absolute;
                                            top: 0;
                                            left: 0;
                                            width: 100%;
                                            height: 100%;
                                            background: rgba(255,255,255,0.1);
                                            transform: translateX(-100%);
                                            transition: transform 0.3s ease-out;
                                        }
                                        
                                        .selected-preview .preview-badge:hover {
                                            transform: translateY(-2px);
                                            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
                                        }
                                        
                                        .selected-preview .preview-badge:hover::before {
                                            transform: translateX(0);
                                        }
                                        
                                        .selected-preview .remove-icon {
                                            margin-left: 6px;
                                            cursor: pointer;
                                            opacity: 0.7;
                                            transition: all 0.2s ease;
                                            background: rgba(255,255,255,0.2);
                                            border-radius: 50%;
                                            width: 18px;
                                            height: 18px;
                                            display: inline-flex;
                                            align-items: center;
                                            justify-content: center;
                                            font-size: 10px;
                                        }
                                        
                                        .selected-preview .remove-icon:hover {
                                            opacity: 1;
                                            background: rgba(255,255,255,0.3);
                                            transform: scale(1.1);
                                        }
                                        
                                        @keyframes fadeIn {
                                            from { opacity: 0; transform: translateY(5px); }
                                            to { opacity: 1; transform: translateY(0); }
                                        }
                                        
                                        .preview-badge {
                                            animation: fadeIn 0.3s ease forwards;
                                        }
                                        
                                        /* Enhanced Number badge styling - positioned at corner of preview */
                                        .selection-count-badge {
                                            position: absolute;
                                            top: -8px;
                                            right: -8px;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            min-width: 24px;
                                            height: 24px;
                                            border-radius: 50%;
                                            color: white;
                                            font-size: 0.75rem;
                                            font-weight: bold;
                                            padding: 0 4px;
                                            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                                            z-index: 5;
                                            transition: all 0.3s ease;
                                            border: 2px solid #fff;
                                        }
                                        
                                        /* Different colors for different sections */
                                        #authorCount {
                                            background-color: #FF0000;
                                        }
                                        
                                        #coAuthorCount {
                                            background-color: #4169E1;
                                        }
                                        
                                        #editorCount {
                                            background-color: #808080;
                                            color: white;
                                        }
                                        
                                        /* Make badge pulse when count changes */
                                        @keyframes pulse {
                                            0% { transform: scale(1); }
                                            50% { transform: scale(1.3); }
                                            100% { transform: scale(1); }
                                        }
                                        
                                        .pulse {
                                            animation: pulse 0.4s ease-in-out;
                                        }
                                        </style>

                                        <!-- Add real-time preview script -->
                                        <script>
                                        // Function to update preview in real-time
                                        function updatePreviewRealtime(selectId, previewId) {
                                            const select = document.getElementById(selectId);
                                            const preview = document.getElementById(previewId);
                                            
                                            // Clear current preview
                                            preview.innerHTML = '';
                                            
                                            // Determine badge type and count badge ID based on selectId
                                            let badgeClass = 'author-badge';
                                            let countBadgeId = 'authorCount';
                                            
                                            if (selectId === 'coAuthorsSelect') {
                                                badgeClass = 'coauthor-badge';
                                                countBadgeId = 'coAuthorCount';
                                            }
                                            
                                            if (selectId === 'editorsSelect') {
                                                badgeClass = 'editor-badge';
                                                countBadgeId = 'editorCount';
                                            }
                                            
                                            // Get the count badge element
                                            const countBadge = document.getElementById(countBadgeId);
                                            const selectedCount = select.selectedOptions.length;
                                            
                                            // Update the count
                                            const oldCount = parseInt(countBadge.textContent);
                                            countBadge.textContent = selectedCount;
                                            
                                            // Add pulse animation if count changed
                                            if (oldCount !== selectedCount) {
                                                countBadge.classList.remove('pulse');
                                                setTimeout(() => {
                                                    countBadge.classList.add('pulse');
                                                }, 10);
                                            }
                                            
                                            // Show/hide badge based on count
                                            if (selectedCount > 0) {
                                                countBadge.style.display = 'flex';
                                            } else {
                                                countBadge.style.display = 'none';
                                            }
                                            
                                            // Generate badges for each selected option with staggered animation
                                            Array.from(select.selectedOptions).forEach((option, index) => {
                                                const badge = document.createElement('span');
                                                badge.className = `preview-badge ${badgeClass}`;
                                                badge.style.animationDelay = `${index * 0.05}s`;
                                                
                                                // Truncate very long names
                                                let displayName = option.text;
                                                if (displayName.length > 30) {
                                                    displayName = displayName.substring(0, 27) + '...';
                                                    badge.title = option.text; // Show full name on hover
                                                }
                                                
                                                badge.innerHTML = `${displayName} <i class="fas fa-times remove-icon" data-select="${selectId}" data-value="${option.value}"></i>`;
                                                preview.appendChild(badge);
                                            });
                                            
                                            // Add click handlers to remove icons
                                            preview.querySelectorAll('.remove-icon').forEach(icon => {
                                                icon.addEventListener('click', function(e) {
                                                    e.stopPropagation(); // Prevent event bubbling
                                                    
                                                    const selectElement = document.getElementById(this.dataset.select);
                                                    const value = this.dataset.value;
                                                    const badge = this.closest('.preview-badge');
                                                    
                                                    // Animation for removal
                                                    badge.style.transition = 'all 0.2s ease-out';
                                                    badge.style.transform = 'scale(0.8)';
                                                    badge.style.opacity = '0';
                                                    
                                                    setTimeout(() => {
                                                        // Find and unselect the option
                                                        for(let i = 0; i < selectElement.options.length; i++) {
                                                            if(selectElement.options[i].value === value) {
                                                                selectElement.options[i].selected = false;
                                                                break;
                                                            }
                                                        }
                                                        
                                                        // Update the preview
                                                        updatePreviewRealtime(this.dataset.select, previewId);
                                                    }, 200);
                                                });
                                            });
                                            
                                            // Make the badges clickable to show the full name if truncated
                                            preview.querySelectorAll('.preview-badge').forEach(badge => {
                                                if (badge.title) {
                                                    badge.style.cursor = 'help';
                                                }
                                            });
                                        }
                                        
                                        // Function to clear all author previews
                                        function clearAuthorPreviews() {
                                            // Clear all selections
                                            ['authorSelect', 'coAuthorsSelect', 'editorsSelect'].forEach(selectId => {
                                                const select = document.getElementById(selectId);
                                                if (select) {
                                                    // Deselect all options
                                                    for(let i = 0; i < select.options.length; i++) {
                                                        select.options[i].selected = false;
                                                    }
                                                }
                                            });
                                            
                                            // Update all previews
                                            updatePreviewRealtime('authorSelect', 'authorPreview');
                                            updatePreviewRealtime('coAuthorsSelect', 'coAuthorsPreview');
                                            updatePreviewRealtime('editorsSelect', 'editorsPreview');
                                            
                                            // Hide count badges
                                            document.getElementById('authorCount').style.display = 'none';
                                            document.getElementById('authorCount').textContent = '0';
                                            document.getElementById('coAuthorCount').style.display = 'none';
                                            document.getElementById('coAuthorCount').textContent = '0';
                                            document.getElementById('editorCount').style.display = 'none';
                                            document.getElementById('editorCount').textContent = '0';
                                        }
                                        
                                        // Initialize previews on page load
                                        document.addEventListener('DOMContentLoaded', function() {
                                            // Initialize count badges to be hidden if empty
                                            document.querySelectorAll('.selection-count-badge').forEach(badge => {
                                                if (badge.textContent === '0') {
                                                    badge.style.display = 'none';
                                                }
                                            });
                                            
                                            updatePreviewRealtime('authorSelect', 'authorPreview');
                                            updatePreviewRealtime('coAuthorsSelect', 'coAuthorsPreview');
                                            updatePreviewRealtime('editorsSelect', 'editorsPreview');
                                            
                                            // Make search fields also update the preview in real-time
                                            document.getElementById('authorSearch').addEventListener('input', function() {
                                                setTimeout(() => updatePreviewRealtime('authorSelect', 'authorPreview'), 100);
                                            });
                                            
                                            document.getElementById('coAuthorsSearch').addEventListener('input', function() {
                                                setTimeout(() => updatePreviewRealtime('coAuthorsSelect', 'coAuthorsPreview'), 100);
                                            });
                                            
                                            document.getElementById('editorsSearch').addEventListener('input', function() {
                                                setTimeout(() => updatePreviewRealtime('editorsSelect', 'editorsPreview'), 100);
                                            });
                                            
                                            // Add filtering functionality for search boxes
                                            setupFilterDropdown('authorSearch', 'authorSelect');
                                            setupFilterDropdown('coAuthorsSearch', 'coAuthorsSelect');
                                            setupFilterDropdown('editorsSearch', 'editorsSelect');
                                            
                                            // Hook into the "Clear Form" and "Clear Tab" functionality
                                            const clearFormBtn = document.querySelector('[data-clear-form]');
                                            const clearTabBtns = document.querySelectorAll('.clear-tab-btn');
                                            
                                            if (clearFormBtn) {
                                                const originalClickHandler = clearFormBtn.onclick;
                                                clearFormBtn.onclick = function(e) {
                                                    if (originalClickHandler) originalClickHandler.call(this, e);
                                                    // Clear author previews
                                                    clearAuthorPreviews();
                                                };
                                            }
                                            
                                            // Add clear functionality for publication tab specifically
                                            clearTabBtns.forEach(btn => {
                                                if (btn.getAttribute('data-tab-id') === 'publication') {
                                                    const originalClickHandler = btn.onclick;
                                                    btn.onclick = function(e) {
                                                        if (originalClickHandler) originalClickHandler.call(this, e);
                                                        // Clear author previews
                                                        clearAuthorPreviews();
                                                    };
                                                }
                                            });
                                        });
                                        
                                        // Setup dropdown filtering function
                                        function setupFilterDropdown(inputId, selectId) {
                                            const input = document.getElementById(inputId);
                                            const select = document.getElementById(selectId);
                                            
                                            if (input && select) {
                                                input.addEventListener('input', function() {
                                                    const filterText = this.value.toLowerCase();
                                                    
                                                    Array.from(select.options).forEach(option => {
                                                        const optionText = option.text.toLowerCase();
                                                        const match = optionText.includes(filterText);
                                                        
                                                        // Use modern approach for hiding options
                                                        option.style.display = match ? '' : 'none';
                                                    });
                                                });
                                            }
                                        }
                                        </script>
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
                                                    <label for="entered_by">Entered By (Currently Logged In)</label>
                                                    <input type="text" class="form-control" id="entered_by" name="entered_by"
                                                        value="<?php echo ($_SESSION['admin_firstname'] ?? '') . ' ' . ($_SESSION['admin_lastname'] ?? '') . ' (' . ($_SESSION['admin_employee_id'] ?? '') . ' - ' . ($_SESSION['role'] ?? '') . ')'; ?>" readonly>
                                                    <small class="form-text text-muted">
                                                        <i class="fas fa-user mr-1"></i> Staff member who created this record
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="date_added">Date Added</label>
                                                    <input type="text" class="form-control" id="date_added" name="date_added"
                                                        value="<?php echo date('Y-m-d H:i:s'); ?>" readonly>
                                                    <small class="form-text text-muted">
                                                        <i class="fas fa-calendar mr-1"></i> Date the book was added to the system
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="status">Status</label>
                                                <input type="text" class="form-control" id="status" name="status"
                                                    value="Available" readonly>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i> Current availability status of the book
                                                </small>
                                            </div>
                                        </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="last_update">Last Update</label>
                                                    <input type="text" class="form-control" id="last_update" name="last_update"
                                                        value="<?php echo date('Y-m-d H:i:s'); ?>" readonly>
                                                    <small class="form-text text-muted">
                                                        <i class="fas fa-clock mr-1"></i> Timestamp of the most recent modification
                                                    </small>
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
</div>
<?php include 'inc/footer.php'; ?>

<!-- Include the contributor select JS file before the closing body tag -->
<script src="js/contributor-select.js"></script>

<!-- Initialize the contributor select component -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get writer data from PHP
    const writersData = <?php echo json_encode($writers); ?>;
    
    // Define contributor roles
    const contributorRoles = <?php echo json_encode($contributor_roles); ?>;
    
    // Initialize the contributor select component
    window.contributorSelect = new ContributorSelect({
        containerId: 'contributorSelectContainer',
        writersData: writersData,
        roles: contributorRoles,
        onSelectionChange: function(contributors) {
            console.log('Contributors changed:', contributors);
            // Debug output of current contributors
            if (contributors && contributors.length > 0) {
                console.log(`Current contributors (${contributors.length}):`, 
                    contributors.map(c => `ID=${c.id}, Role=${c.role}`).join(', '));
            }
        },
        addNewCallback: function() {
            // Use the existing function to show add author dialog
            if (typeof showAddAuthorDialog === 'function') {
                showAddAuthorDialog();
            }
        }
    });
    
    // Create a function to update writer data after adding new writers
    window.updateContributorSelectData = function(newWriters) {
        if (Array.isArray(newWriters)) {
            contributorSelect.refreshWritersData(newWriters);
        }
    };
});
</script>

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
                        <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Overview</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="formtabs-tab" data-toggle="tab" href="#formtabs" role="tab" aria-controls="formtabs" aria-selected="false">Form Tabs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="features-tab" data-toggle="tab" href="#features" role="tab" aria-controls="features" aria-selected="false">Special Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="callnumber-tab" data-toggle="tab" href="#callnumber" role="tab" aria-controls="callnumber" aria-selected="false">Call Numbers</a>
                    </li>
                </ul>

                <div class="tab-content" id="instructionTabsContent">
                    <!-- Overview Tab -->
                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> The Add Book form is organized into six tabs to help you enter complete bibliographic data.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="m-0 font-weight-bold">Form Organization</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>The six main tabs:</strong></p>
                                        <ol>
                                            <li><strong>Title Proper</strong> - Basic title information</li>
                                            <li><strong>Access Point</strong> - Subject categorization</li>
                                            <li><strong>Abstract & Notes</strong> - Content summaries</li>
                                            <li><strong>Description</strong> - Physical attributes & images</li>
                                            <li><strong>Local Information</strong> - Accession & call numbers</li>
                                            <li><strong>Publication</strong> - Publishing details & contributors</li>
                                        </ol>
                                        <p class="text-muted small"><i class="fas fa-lightbulb"></i> Tip: You can clear individual tabs using the "Clear Tab" button in each section.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="m-0 font-weight-bold">Time-Saving Features</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul>
                                            <li><strong>Autosave</strong> - Your work is automatically saved</li>
                                            <li><strong>Smart Call Numbers</strong> - Auto-formatted call numbers</li>
                                            <li><strong>Drag & Drop</strong> - Easy image uploads for book covers</li>
                                            <li><strong>Quick Add</strong> - Add new authors and publishers on the fly</li>
                                            <li><strong>Preview</strong> - Visual preview of selected contributors</li>
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

                    <!-- Form Tabs Tab -->
                    <div class="tab-pane fade" id="formtabs" role="tabpanel" aria-labelledby="formtabs-tab">
                        <div class="accordion" id="tabsAccordion">
                            <div class="card">
                                <div class="card-header" id="titleProperHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#titleProperContent" aria-expanded="true" aria-controls="titleProperContent">
                                            <i class="fas fa-book"></i> Title Proper
                                        </button>
                                    </h2>
                                </div>
                                <div id="titleProperContent" class="collapse show" aria-labelledby="titleProperHeading" data-parent="#tabsAccordion">
                                    <div class="card-body">
                                        <p>Enter the book's primary title information:</p>
                                        <ul>
                                            <li><strong>Title Proper</strong> - The main title as it appears on the book</li>
                                            <li><strong>Preferred Title</strong> - Alternative title (if applicable)</li>
                                            <li><strong>Parallel Title</strong> - Title in another language</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header" id="accessPointHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#accessPointContent" aria-expanded="false" aria-controls="accessPointContent">
                                            <i class="fas fa-tag"></i> Access Point
                                        </button>
                                    </h2>
                                </div>
                                <div id="accessPointContent" class="collapse" aria-labelledby="accessPointHeading" data-parent="#tabsAccordion">
                                    <div class="card-body">
                                        <p>Categorize the book to make it easier to find:</p>
                                        <ul>
                                            <li><strong>Subject Category</strong> - Primary classification (Topical, Personal, etc.)</li>
                                            <li><strong>Program</strong> - Relevant academic program</li>
                                            <li><strong>Details</strong> - Additional subject terms and keywords</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header" id="abstractsHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#abstractsContent" aria-expanded="false" aria-controls="abstractsContent">
                                            <i class="fas fa-file-alt"></i> Abstract & Notes
                                        </button>
                                    </h2>
                                </div>
                                <div id="abstractsContent" class="collapse" aria-labelledby="abstractsHeading" data-parent="#tabsAccordion">
                                    <div class="card-body">
                                        <p>Document the book's content and special notes:</p>
                                        <ul>
                                            <li><strong>Summary/Abstract</strong> - Brief overview of the book's content</li>
                                            <li><strong>Notes/Contents</strong> - Additional information about the book</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header" id="descriptionHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#descriptionContent" aria-expanded="false" aria-controls="descriptionContent">
                                            <i class="fas fa-info-circle"></i> Description
                                        </button>
                                    </h2>
                                </div>
                                <div id="descriptionContent" class="collapse" aria-labelledby="descriptionHeading" data-parent="#tabsAccordion">
                                    <div class="card-body">
                                        <p>Document physical characteristics of the book:</p>
                                        <ul>
                                            <li><strong>Cover Images</strong> - Upload front and back cover images (drag & drop supported)</li>
                                            <li><strong>Dimension</strong> - Physical size of the book</li>
                                            <li><strong>Pages</strong> - Prefix pages (Roman numerals) and main pages</li>
                                            <li><strong>Supplementary Content</strong> - Additional materials included (index, illustrations, etc.)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header" id="localInfoHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#localInfoContent" aria-expanded="false" aria-controls="localInfoContent">
                                            <i class="fas fa-map-marker-alt"></i> Local Information
                                        </button>
                                    </h2>
                                </div>
                                <div id="localInfoContent" class="collapse" aria-labelledby="localInfoHeading" data-parent="#tabsAccordion">
                                    <div class="card-body">
                                        <p>Library-specific information:</p>
                                        <ul>
                                            <li><strong>Accession Numbers</strong> - Unique identifiers for each physical copy</li>
                                            <li><strong>Call Numbers</strong> - Classification for shelf location</li>
                                            <li><strong>Content/Media/Carrier Types</strong> - Format specifications</li>
                                            <li><strong>Language</strong> - Primary language of the resource</li>
                                            <li><strong>URL</strong> - For digital resources</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header" id="publicationHeading">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#publicationContent" aria-expanded="false" aria-controls="publicationContent">
                                            <i class="fas fa-print"></i> Publication
                                        </button>
                                    </h2>
                                </div>
                                <div id="publicationContent" class="collapse" aria-labelledby="publicationHeading" data-parent="#tabsAccordion">
                                    <div class="card-body">
                                        <p>Publishing details and contributors:</p>
                                        <ul>
                                            <li><strong>Publisher Information</strong> - Publisher name and location</li>
                                            <li><strong>Publication Year</strong> - When the book was published</li>
                                            <li><strong>ISBN/Series/Volume/Edition</strong> - Bibliographic identifiers</li>
                                            <li><strong>Contributors</strong> - Authors, co-authors, and editors</li>
                                            <li><strong>System Information</strong> - Entry tracking data</li>
                                        </ul>
                                        <p class="text-primary"><i class="fas fa-save"></i> The "Save Book" button is on this tab.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Special Features Tab -->
                    <div class="tab-pane fade" id="features" role="tabpanel" aria-labelledby="features-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="m-0 font-weight-bold">Enhanced Image Upload</h6>
                                    </div>
                                    <div class="card-body">
                                        <p>The Description tab includes a modern drag & drop interface for uploading book covers:</p>
                                        <ul>
                                            <li>Drag images directly onto the upload area</li>
                                            <li>Click to browse your files</li>
                                            <li>Preview images before submission</li>
                                            <li>Supports JPG, PNG, and GIF formats up to 5MB</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="m-0 font-weight-bold">Contributors Selection</h6>
                                    </div>
                                    <div class="card-body">
                                        <p>Easily manage authors and contributors in the Publication tab:</p>
                                        <ul>
                                            <li>Search for existing authors</li>
                                            <li>Hold Ctrl/Cmd to select multiple contributors</li>
                                            <li>Visual preview of selected contributors</li>
                                            <li>Add new authors on the fly with the "New Author" button</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="m-0 font-weight-bold">Autosave Functionality</h6>
                                    </div>
                                    <div class="card-body">
                                        <p>Your work is automatically saved as you type:</p>
                                        <ul>
                                            <li>No data loss if you navigate away accidentally</li>
                                            <li>Resume work where you left off</li>
                                            <li>Clear form button available if you want to start over</li>
                                            <li>Individual tabs can be cleared with "Clear Tab" button</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="m-0 font-weight-bold">Accession Groups</h6>
                                    </div>
                                    <div class="card-body">
                                        <p>Efficiently add multiple copies or editions:</p>
                                        <ul>
                                            <li>Create separate accession groups for different editions/ISBNs</li>
                                            <li>Specify multiple copies within each group</li>
                                            <li>System auto-increments accession numbers</li>
                                            <li>Each group can have unique details (ISBN, volume, edition)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Call Numbers Tab -->
                    <div class="tab-pane fade" id="callnumber" role="tabpanel" aria-labelledby="callnumber-tab">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="m-0 font-weight-bold">Understanding Call Numbers</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Call numbers follow a specific format for proper shelf organization.
                                </div>
                                
                                <h6><strong>Call Number Format:</strong></h6>
                                <p>The system will automatically format your call number following this pattern:</p>
                                <code>LOCATION CALLNUMBER cYEAR VOLUME PART c#</code>
                                
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-sm">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Component</th>
                                                <th>Description</th>
                                                <th>Example</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>LOCATION</td>
                                                <td>Shelf location code</td>
                                                <td>REF, CIR, TR, FIL</td>
                                            </tr>
                                            <tr>
                                                <td>CALLNUMBER</td>
                                                <td>Classification and author cutter</td>
                                                <td>HD69.B7 W56</td>
                                            </tr>
                                            <tr>
                                                <td>cYEAR</td>
                                                <td>Publication year with 'c' prefix</td>
                                                <td>c2023</td>
                                            </tr>
                                            <tr>
                                                <td>VOLUME</td>
                                                <td>Volume number (if applicable)</td>
                                                <td>v.2</td>
                                            </tr>
                                            <tr>
                                                <td>PART</td>
                                                <td>Part number with 'pt.' prefix (if applicable)</td>
                                                <td>pt.3</td>
                                            </tr>
                                            <tr>
                                                <td>c#</td>
                                                <td>Copy number with 'c' prefix</td>
                                                <td>c3</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <h6 class="mt-3"><strong>Example:</strong></h6>
                                <p><code>REF HD69.B7 W56 c2023 v.2 pt.3 c1</code></p>
                                
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Enter only the classification and author cutter in the call number field. The system will automatically add the location, year, volume, part, and copy number.
                                </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update the corporate contributor initialization to store the instance globally
    fetch('ajax/get_corporates.php')
        .then(response => response.json())
        .then(data => {
            // Initialize the corporate contributor select component and store globally
            window.corporateContributorSelect = new ContributorSelect({
                containerId: 'corporateContributorSelectContainer',
                writersData: data.map(corporate => ({
                    id: corporate.id,
                    name: `${corporate.name} (${corporate.type})`
                })),
                roles: {
                    'corporate_author': 'Corporate Author',
                    'corporate_contributor': 'Corporate Contributor',
                    'publisher': 'Publisher',
                    'distributor': 'Distributor',
                    'sponsor': 'Sponsor',
                    'funding_body': 'Funding Body',
                    'research_institution': 'Research Institution'
                },
                onSelectionChange: function(corporates) {
                    console.log('Corporate Contributors changed:', corporates);
                },
                addNewCallback: function() {
                    showAddCorporateDialog();
                }
            });
            
            // Complete the implementation of the updateCorporateContributorSelectData function
            window.updateCorporateContributorSelectData = function(newCorporates) {
                if (Array.isArray(newCorporates)) {
                    // First fetch the current list of corporates
                    fetch('ajax/get_corporates.php')
                        .then(response => response.json())
                        .then(data => {
                            // Get reference to the corporateContributorSelect instance
                            if (window.corporateContributorSelect) {
                                // Format the data appropriately
                                const formattedData = data.map(corporate => ({
                                    id: corporate.id,
                                    name: `${corporate.name} (${corporate.type})`
                                }));
                                
                                // Update the select component with all corporates
                                window.corporateContributorSelect.refreshWritersData(formattedData);
                                console.log('Corporate contributors list updated with all entries:', formattedData.length);
                            } else {
                                console.error('Corporate contributor select component not initialized');
                            }
                        })
                        .catch(error => {
                            console.error('Error refreshing corporate entities list:', error);
                        });
                }
            };
        })

        .catch(error => {
            console.error('Error fetching corporate entities:', error);
            document.getElementById('corporateContributorSelectContainer').innerHTML = 
                '<div class="alert alert-danger">Error loading corporate entities. Please refresh the page.</div>';
        });

        // Create a function to show the add corporate entity dialog using SweetAlert
        window.showAddCorporateDialog = function() {
            Swal.fire({
                title: '<i class="fas fa-building"></i> Add New Corporate Entity',
                html: `
                    <div id="sweetAlertCorporateContainer">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="corporate_name">Name</label>
                                    <input type="text" class="form-control" id="corporate_name" placeholder="Enter corporate name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="corporate_type">Type</label>
                                    <select class="form-control" id="corporate_type" required>
                                        <option value="">Select Type</option>
                                        <option value="Government">Government</option>
                                        <option value="Educational">Educational</option>
                                        <option value="Non-profit">Non-profit</option>
                                        <option value="Research">Research</option>
                                        <option value="Corporate">Corporate</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="corporate_location">Location</label>
                                    <input type="text" class="form-control" id="corporate_location" placeholder="Enter location">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="corporate_description">Description</label>
                                    <textarea class="form-control" id="corporate_description" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save"></i> Save Corporate Entity',
                confirmButtonColor: '#3085d6',
                cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                cancelButtonColor: '#d33',
                width: '800px',
                customClass: {
                    cancelButton: 'btn btn-danger'
                },
                preConfirm: () => {
                    // Validate required fields
                    const name = document.getElementById('corporate_name').value.trim();
                    const type = document.getElementById('corporate_type').value;
                    
                    if (!name || !type) {
                        Swal.showValidationMessage('Name and Type are required');
                        return false;
                    }
                    
                    return {
                        name: name,
                        type: type,
                        location: document.getElementById('corporate_location').value.trim(),
                        description: document.getElementById('corporate_description').value.trim()
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Save the new corporate entity via AJAX
                    fetch('ajax/add_corporate.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(result.value)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message,
                                timer: 1500
                            });
                            
                            // Update the corporate contributor select component
                            if (typeof updateCorporateContributorSelectData === 'function') {
                                updateCorporateContributorSelectData([data.corporate]);
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error adding corporate entity:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to add corporate entity. Please try again.'
                        });
                    });
                }
            });
        };

    // Connect the Add New Corporate Entity button
    const addNewCorporateBtn = document.getElementById('addNewCorporateBtn');
    if (addNewCorporateBtn) {
        addNewCorporateBtn.addEventListener('click', showAddCorporateDialog);
    }
});
/**
 * Prepare form for submission by adding all contributor data as hidden fields
 * This ensures contributors are always included regardless of submission method
 */
function prepareFormSubmission() {
    try {
        // First, remove any existing hidden contributor fields to avoid duplicates
        document.querySelectorAll('input[name="corporate_contributor_ids[]"], input[name="corporate_contributor_roles[]"]').forEach(el => el.remove());
        document.querySelectorAll('input[name="contributor_ids[]"], input[name="contributor_roles[]"]').forEach(el => el.remove());
        
        // Get the form element
        const form = document.getElementById('bookForm');
        
        // Get individual contributors if component exists
        if (window.contributorSelect && typeof window.contributorSelect.getSelectedContributors === 'function') {
            const individualContributors = window.contributorSelect.getSelectedContributors();
            console.log(`Adding ${individualContributors.length} individual contributors to form`);
            
            // Add hidden inputs for individual contributors
            individualContributors.forEach((contributor) => {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'contributor_ids[]';
                idInput.value = contributor.id;
                
                const roleInput = document.createElement('input');
                roleInput.type = 'hidden';
                roleInput.name = 'contributor_roles[]';
                roleInput.value = contributor.role;
                
                form.appendChild(idInput);
                form.appendChild(roleInput);
            });
        }
        
        // Get corporate contributors if component exists
        if (window.corporateContributorSelect && typeof window.corporateContributorSelect.getSelectedContributors === 'function') {
            const corporateContributors = window.corporateContributorSelect.getSelectedContributors();
            console.log(`Adding ${corporateContributors.length} corporate contributors to form`);
            
            // Add hidden inputs for corporate contributors
            corporateContributors.forEach((corporate) => {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'corporate_contributor_ids[]';
                idInput.value = corporate.id;
                
                const roleInput = document.createElement('input');
                roleInput.type = 'hidden';
                roleInput.name = 'corporate_contributor_roles[]';
                roleInput.value = corporate.role;
                
                form.appendChild(idInput);
                form.appendChild(roleInput);
            });
        }
        
        // Also add formatted call numbers as hidden fields if they exist
        document.querySelectorAll('.call-number-input').forEach((input, index) => {
            if (input.dataset.formattedCallNumber) {
                const formattedInput = document.createElement('input');
                formattedInput.type = 'hidden';
                formattedInput.name = 'formatted_call_numbers[]';
                formattedInput.value = input.dataset.formattedCallNumber;
                form.appendChild(formattedInput);
            }
        });
        
        console.log('Form preparation complete, ready for submission');
        return true; // Allow form submission to continue
    } catch (error) {
        console.error('Error preparing form for submission:', error);
        return false; // Prevent form submission if there was an error
    }
}

// Remove the old event listener approach that might not catch all submission methods
document.addEventListener('DOMContentLoaded', function() {
    // Still keep a backup method for the submit button clicks
    document.getElementById('bookForm').addEventListener('submit', function(e) {
        // The onsubmit attribute will handle this, but as a backup:
        if (!this.querySelector('input[name="corporate_contributor_ids[]"]') && 
            window.corporateContributorSelect && 
            typeof window.corporateContributorSelect.getSelectedContributors === 'function') {
            
            e.preventDefault(); // Stop the submission temporarily
            prepareFormSubmission(); // Prepare the form
            this.submit(); // Continue with submission
        }
    });
});
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

        // Remove 'completed' marker from this tab if exists
        const tabLink = document.querySelector(`a[href="#${tabId}"]`);
        if (tabLink) {
            tabLink.classList.remove('completed');
        }
    }

    function clearAllTabs() {
        const tabs = ['title-proper', 'subject-entry', 'abstracts', 'description', 'local-info', 'publication'];
        tabs.forEach(tabId => clearTab(tabId));


        // Reset to first tab
        const firstTab = document.querySelector('#formTabs .nav-link');
        if (firstTab && typeof $(firstTab).tab === 'function') {
            $(firstTab).tab('show');
        }

        // Reset current tab index if it's being tracked
        if (typeof window.currentTabIndex !== 'undefined') {
            window.currentTabIndex = 0;
        }

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

        // Clear call number container
        const callNumberContainer = document.getElementById('callNumberContainer');
        if (callNumberContainer) {
            callNumberContainer.innerHTML = '';
        }

        // Reset the form element
        document.getElementById('bookForm').reset();

        // Refresh the form state in localStorage if autosave is enabled
        if (typeof saveFormData === 'function') {
            saveFormData();
        }
    }

    // Helper function to recalculate progress
    function updateFormProgress() {
        const totalTabs = document.querySelectorAll('#formTabs .nav-link').length;
        const completedTabs = document.querySelectorAll('#formTabs .nav-link.completed').length;
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

                // Activate the tab with Bootstrap
                $(nextTab).tab('show');
            } else {
                // Move cursor to the first invalid field
                const currentTab = tabs[currentTabIndex];
                const currentTabId = currentTab.getAttribute('href').substring(1);
                const currentTabPane = document.getElementById(currentTabId);
                const firstInvalidField = currentTabPane.querySelector('.is-invalid');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
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

            // Trigger click on the previous tab
            $(prevTab).tab('show');
        });
    });

    // Modified: Allow direct tab clicking without restriction
    tabs.forEach((tab) => {
        tab.addEventListener('click', function(e) {
            const clickedTabIndex = Array.from(tabs).indexOf(this);
            currentTabIndex = clickedTabIndex;
            updateProgressBar();

            // Use Bootstrap's tab method to show the tab
            $(this).tab('show');
        });
    });
});
</script>

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
    
    // NEW: Reset supplementary content selection and update its preview
    const supplementarySelect = document.getElementById('supplementarySelect');
    if (supplementarySelect) {
        // Deselect all options
        Array.from(supplementarySelect.options).forEach(option => {
            option.selected = false;
        });
        
        // Update the preview to reflect empty selection
        if (typeof updateSupplementaryPreview === 'function') {
            updateSupplementaryPreview();
        } else {
            // Fallback if function isn't available - manually clear the preview
            const supplementaryPreview = document.getElementById('supplementaryPreview');
            const supplementaryCount = document.getElementById('supplementaryCount');
            if (supplementaryPreview) supplementaryPreview.innerHTML = '';
            if (supplementaryCount) {
                supplementaryCount.textContent = '0';
                supplementaryCount.style.display = 'none';
            }
        }
    }

    // Also reset file uploads by clearing the preview containers
    if (typeof clearFileUploads === 'function') {
        clearFileUploads();
    }

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
            const partInput = group.querySelector('input[name^="part"]');

            if (accessionInput && copiesInput) {
                accessionGroups.push({
                    accession: accessionInput.value,
                    copies: copiesInput.value,
                    isbn: isbnInput ? isbnInput.value : '',
                    series: seriesInput ? seriesInput.value : '',
                    volume: volumeInput ? volumeInput.value : '',
                    edition: editionInput ? editionInput.value : '',
                    part: partInput ? partInput.value : ''
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

        // Save completed tabs
        const completedTabs = Array.from(document.querySelectorAll('#formTabs .nav-link.completed')).map(tab => tab.id);
        formData['completedTabs'] = completedTabs;

        localStorage.setItem(storageKey, JSON.stringify(formData));
    }

    // Function to clear a specific tab's data without confirmation
    function clearTabData(tabId) {
        const tabPane = document.querySelector(`#${tabId}`);
        if (!tabPane) return;

        // IDs to preserve in the publication tab
        const preserveFields = ['entered_by', 'date_added', 'last_update', 'status'];

        tabPane.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(input => {
            // Skip preserved fields in publication tab
            if (tabId === 'publication' && preserveFields.includes(input.id)) return;

            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else if (input.type === 'select-multiple') {
                input.selectedIndex = -1;
                const previewId = input.id + 'Preview';
                const preview = document.getElementById(previewId);
                if (preview) preview.innerHTML = '';
            } else if (input.type === 'file') {
                input.value = '';
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

        // Special handling for Local Information tab
        if (tabId === 'local-info') {
            console.log('Clearing Local Information tab - resetting accession and call numbers');
            // Clear accession groups, keeping only the first one and resetting its values
            const accessionContainer = document.getElementById('accessionContainer');
            if (accessionContainer) {
                const firstGroup = accessionContainer.querySelector('.accession-group');
                if (firstGroup) {
                    // Clear inputs in the first group
                    const accessionInput = firstGroup.querySelector('.accession-input');
                    const copiesInput = firstGroup.querySelector('.copies-input');
                    if (accessionInput) accessionInput.value = '';
                    if (copiesInput) copiesInput.value = '1'; // Reset copies to 1

                    // Remove all other groups
                    Array.from(accessionContainer.children).forEach((child, index) => {
                        if (index > 0) child.remove();
                    });

                    // Clear details within the first group if they exist
                    const detailsSection = firstGroup.querySelector('.accession-details');
                    if (detailsSection) {
                        detailsSection.innerHTML = ''; // Clear details
                    }
                }
            }

            // Clear call numbers
            const callNumberContainer = document.getElementById('callNumberContainer');
            if (callNumberContainer) {
                callNumberContainer.innerHTML = '';
            }

            // Clear ISBN container (if it exists and is separate)
            const isbnContainer = document.getElementById('isbnContainer');
            if (isbnContainer) {
                isbnContainer.innerHTML = '';
            }

            // Optionally, re-run the function that generates the initial fields if needed
            if (typeof updateISBNFields === 'function') {
                 // Delay slightly to ensure DOM is updated before regenerating
                 setTimeout(updateISBNFields, 50);
            }
        }

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
                                const partInput = group.querySelector('input[name^="part"]');

                                if (isbnInput) isbnInput.value = groupData.isbn || '';
                                if (seriesInput) seriesInput.value = groupData.series || '';
                                if (volumeInput) volumeInput.value = groupData.volume || '';
                                if (editionInput) editionInput.value = groupData.edition || '';
                                if (partInput) partInput.value = groupData.part || '';
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
                    copyNumberLabel.textContent = 'Copy Number';

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

        // Restore completed tabs with improved selector handling
        if (formData['completedTabs'] && Array.isArray(formData['completedTabs'])) {
            formData['completedTabs'].forEach(tabId => {
                // Try different selector approaches to find the tab
                let tab = document.querySelector(`a#${tabId}`);
                if (!tab) tab = document.querySelector(`a[id="${tabId}"]`);
                if (!tab) tab = document.querySelector(`#formTabs .nav-link[href="#${tabId.replace('tab', 'proper')}"]`);
                if (!tab) tab = document.querySelector(`#formTabs .nav-link[href="#${tabId}"]`);
                if (!tab) tab = document.querySelector(`#formTabs .nav-link[id="${tabId}"]`);
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
    
    // Update all previews after form data is restored
    setTimeout(function() {
        // Update previews for author, co-author, and editor selections
        updatePreviewRealtime('authorSelect', 'authorPreview');
        updatePreviewRealtime('coAuthorsSelect', 'coAuthorsPreview');
        updatePreviewRealtime('editorsSelect', 'editorsPreview');
    }, 600);

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
                        <label>Accession Group ${copyNumber}</label>
                        <input type="text" class="form-control accession-input" name="accession[]"
                            placeholder="e.g., 0001" required>
                        <div class="mt-2">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i> If you enter 0001 and set 3 copies, it will create: 0001, 0002, 0003
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Number of Copies</label>
                        <input type="number" class="form-control copies-input" name="number_of_copies[]" min="1" value="1" required>
                        <div class="mt-2">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i> Auto-increments accession
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-center justify-content-center">
                    <!-- ${copyNumber > 1 ? '<button type="button" class="btn btn-danger btn-sm remove-accession"><i class="fas fa-trash"></i> Remove</button>' : ''} -->
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
/**
 * Ensure the formatted call numbers from preview are used during submission
 */
document.getElementById('bookForm').addEventListener('submit', function(e) {
    const callNumberInputs = document.querySelectorAll('.call-number-input');
    const previewElements = document.querySelectorAll('.call-number-preview');

    if (callNumberInputs.length > 0) {
        // Always use formatted call numbers for submission
        callNumberInputs.forEach((input, index) => {
            // Get the preview text with the arrow symbol removed
            const previewElement = previewElements[index];
            const previewText = previewElement ? previewElement.textContent.replace('→ ', '') : '';
            
            // Use preview text directly if available, otherwise use data attribute
            const formattedCallNumber = previewText || input.dataset.formattedCallNumber || input.value;
            
            if (formattedCallNumber) {
                // Replace input value with exactly what's in the preview
                input.value = formattedCallNumber;
                
                // Create a hidden flag to indicate this call number is already formatted
                const formattedFlag = document.createElement('input');
                formattedFlag.type = 'hidden';
                formattedFlag.name = 'call_number_already_formatted[]';
                formattedFlag.value = '1';
                this.appendChild(formattedFlag);
            }
        });
        
        console.log('Preview call numbers applied for submission');
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
            const currentGroup = removeButton.closest('.accession-group');
            const previousGroup = currentGroup.previousElementSibling;

            if (accessionContainer.children.length > 1) {
                // Remove the current accession group
                currentGroup.remove();

                // Scroll to the previous group if it exists
                if (previousGroup) {
                    previousGroup.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                // Update labels and call numbers
                updateAccessionLabels();
                updateCallNumbers();
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
            const baseCallNumber = e.target.value;

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

    // Add event listeners for volume and copy number changes
    document.addEventListener('change', function(e) {
        if (e.target && (e.target.name === 'volume[]' || e.target.classList.contains('copy-number-input'))) {
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

        // Create a row for ISBN, series, volume, edition, and part inputs
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
        seriesDiv.className = 'col-md-2';

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
        volumeDiv.className = 'col-md-2';

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

        // Create part input - NEW
        const partDiv = document.createElement('div');
        partDiv.className = 'col-md-2';

        const partLabel = document.createElement('small');
        partLabel.className = 'form-text text-muted';
        partLabel.textContent = 'Part';
        partDiv.appendChild(partLabel);

        const partInput = document.createElement('input');
        partInput.type = 'text';
        partInput.className = 'form-control';
        partInput.name = 'part[]';
        partInput.placeholder = `Part`;
        partInput.dataset.groupIndex = groupIndex; // Add data attribute for identification

        partDiv.appendChild(partInput);
        rowDiv.appendChild(partDiv);

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
            part: partInput.value || '',
            accession: accessionInput,
            copies: copiesCount
        });
    });

    // Track overall copy index across all groups
    let globalCopyIndex = 0;

    // Second pass: determine copy numbers and create call number inputs
    detailsGroups.forEach((groupDetails, index) => {
        // Create a key for this group's details
        const detailsKey = `${groupDetails.isbn}|${groupDetails.series}|${groupDetails.volume}|${groupDetails.edition}|${groupDetails.part}`;

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
            copyNumberLabel.textContent = 'Copy Number';

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

    // Handle formats like "0001" or "001"
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
                    <label>Accession Groyp ${newIndex + 1}</label>
                    <input type="text" class="form-control accession-input" name="accession[]"
                        placeholder="e.g., 00001, 00002, etc." required>
                    <div class="mt-2">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle mr-1"></i> If you enter 0001 and set 3 copies, it will create: 0001, 0002, 0003
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Number of Copies</label>
                    <input type="number" class="form-control copies-input" name="number_of_copies[]" min="1" value="1" 
                        placeholder="Number of copies" required>
                    <div class="mt-2">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle mr-1"></i> System will auto-increment accession numbers
                        </small>
                    </div>
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

    // Scroll to the newly added group
    newGroup.scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // Focus on the accession input field in the newly added group
    const accessionInput = newGroup.querySelector('.accession-input');
    if (accessionInput) {
        setTimeout(() => accessionInput.focus(), 100);
    }
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
            label.textContent = `Accession Group ${index + 1}`;
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
        const partInput = group.querySelector('input[name^="part"]');

        if (isbnInput && seriesInput && volumeInput && editionInput && partInput) {
            valuesMap[index] = {
                isbn: isbnInput.value,
                series: seriesInput.value,
                volume: volumeInput.value,
                edition: editionInput.value,
                part: partInput.value
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
            const partInput = group.querySelector('input[name^="part"]');

            if (isbnInput) isbnInput.value = valuesMap[index].isbn;
            if (seriesInput) seriesInput.value = valuesMap[index].series;
            if (volumeInput) volumeInput.value = valuesMap[index].volume;
            if (editionInput) editionInput.value = valuesMap[index].edition;
            if (partInput) partInput.value = valuesMap[index].part;
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
        const partInput = group.querySelector('input[name^="part"]');

        // Store this group's details for later comparison
        detailsGroups.push({
            groupIndex,
            isbn: isbnInput ? isbnInput.value || '' : '',
            series: seriesInput ? seriesInput.value || '' : '',
            volume: volumeInput ? volumeInput.value || '' : '',
            edition: editionInput ? editionInput.value || '' : '',
            part: partInput ? partInput.value || '' : '',
            accession: accessionInput,
            copies: copiesCount
        });
    });

    // Second pass: determine copy numbers and create call number inputs
    detailsGroups.forEach((groupDetails, index) => {
        // Create a key for this group's details
        const detailsKey = `${groupDetails.isbn}|${groupDetails.series}|${groupDetails.volume}|${groupDetails.edition}|${groupDetails.part}`;

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
            copyNumberLabel.textContent = 'Copy Number';

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
    copyNumberLabel.textContent = 'Copy Number';

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

    // Get the base call number entered by the user - Don't trim to preserve spaces
    const baseCallNumber = callNumberInput.value;
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

    // Get volume if available - find the volume input for this accession group
    let volume = '';
    let part = ''; // Add part variable

    // First try to find volume and part in the same accession group
    const accessionGroup = callNumberInput.closest('[data-accession-group]');
    if (accessionGroup) {
        const groupIndex = accessionGroup.dataset.accessionGroup;
        // Find volume inputs and check if there's a value
        const volumeInputs = document.querySelectorAll('input[name="volume[]"]');
        if (volumeInputs.length > groupIndex && volumeInputs[groupIndex].value) {
            volume = 'v.' + volumeInputs[groupIndex].value;
        }

        // Find part inputs and check if there's a value
        const partInputs = document.querySelectorAll('input[name="part[]"]');
        if (partInputs.length > groupIndex && partInputs[groupIndex].value) {
            part = 'pt.' + partInputs[groupIndex].value;
        }
    }

    // Create the full formatted call number with proper spacing
    let formattedCallNumber = [];
    formattedCallNumber.push(shelfLocation);

    // Add base call number as-is without splitting/trimming to preserve spaces
    formattedCallNumber.push(baseCallNumber);

    // REMOVED: Copyright year is no longer included in call number format
    // Requested By Ms.Vel Villanueva
    // if (publishYear) formattedCallNumber.push('c' + publishYear);
    if (volume) formattedCallNumber.push(volume);
    if (part) formattedCallNumber.push(part); // Add part to call number if present
    formattedCallNumber.push('c.' + copyNumber);

    // Join with single spaces but preserve internal spaces in the base call number
    const preview = formattedCallNumber.join(' ');

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
                copyNumberLabel.textContent = 'Copy Number';

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
                        // Default simple formatting without trimming
                        const baseCallNumber = this.value;
                        if (baseCallNumber) {
                            const shelf = shelfLocationSelect.value;
                            const volume = volumeValue ? ` vol${volumeValue}` : '';
                            // Add 'c' before year to indicate copyright
                            const year = publishYear ? ` c${publishYear}` : '';
                            const copy = ` c${copyNumberInput.value}`;
                            const formatted = `${shelf} ${baseCallNumber}${year}${volume}${copy}`;
                            callNumberPreview.textContent = `→ ${formatted}`;
                            // Store the formatted value to be used on submission
                            this.dataset.formattedCallNumber = formatted;
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
                                newOption.textContent = `${pub.place} ; ${pub.publisher}`;
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
            title: '<i class="fas fa-user-plus"></i> Add New Author',
            html: `
                <div id="sweetAlertAuthorContainer">
                    <p class="text-muted mb-3">Enter author details below. You can add multiple authors at once.</p>
                    <div id="authorEntriesContainer">
                        <div class="author-entry card mb-3 p-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control author-firstname" required>
                                        <div class="invalid-feedback">First name is required</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Middle Initial</label>
                                        <input type="text" class="form-control author-middleinit" maxlength="5">
                                        <small class="form-text text-muted">Optional</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control author-lastname" required>
                                        <div class="invalid-feedback">Last name is required</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-save"></i> Save Authors',
            confirmButtonColor: '#3085d6',
            cancelButtonText: '<i class="fas fa-times"></i> Cancel',
            cancelButtonColor: '#d33',
            width: '800px',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-danger'
            },
            didOpen: () => {
                // Add button below the author entries container
                const container = document.getElementById('sweetAlertAuthorContainer');
                const addButton = document.createElement('button');
                addButton.type = 'button';
                addButton.className = 'btn btn-secondary btn-sm mt-2 add-author-btn';
                addButton.innerHTML = '<i class="fas fa-plus"></i> Add Another Author';
                addButton.id = 'addAuthorEntry';
                addButton.style.display = 'block';
                addButton.style.width = '100%';
                addButton.style.marginBottom = '10px';
                container.appendChild(addButton);

                // Setup validation listeners for required fields
                setupValidationListeners();

                // Add event listener for the button
                addButton.addEventListener('click', function() {
                    const authorEntriesContainer = document.getElementById('authorEntriesContainer');
                    const newEntry = document.createElement('div');
                    newEntry.className = 'author-entry card mb-3 p-3';
                    newEntry.innerHTML = `
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="text-muted">Additional Author</h6>
                            <button type="button" class="btn btn-danger btn-sm remove-author-entry">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control author-firstname" required>
                                    <div class="invalid-feedback">First name is required</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Middle Initial</label>
                                    <input type="text" class="form-control author-middleinit" maxlength="5">
                                    <small class="form-text text-muted">Optional</small>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control author-lastname" required>
                                    <div class="invalid-feedback">Last name is required</div>
                                </div>
                            </div>
                        </div>
                    `;
                    authorEntriesContainer.appendChild(newEntry);

                    // Setup validation for new fields
                    setupValidationListeners(newEntry);

                    // Scroll to the bottom of the container to show the new entry
                    const swalContent = document.querySelector('.swal2-content');
                    if (swalContent) {
                        swalContent.scrollTop = swalContent.scrollHeight;
                    }

                    // Add remove functionality for the new entry
                    newEntry.querySelector('.remove-author-entry').addEventListener('click', function() {
                        newEntry.remove();
                    });
                });

                // Setup delegation for removing author entries
                document.addEventListener('click', function(e) {
                    if (e.target && (e.target.classList.contains('remove-author-entry') || e.target.closest('.remove-author-entry'))) {
                        const entry = e.target.closest('.author-entry');
                        if (entry) {
                            entry.remove();
                        }
                    }
                });

                function setupValidationListeners(parent = document) {
                    // Setup real-time validation
                    parent.querySelectorAll('.author-firstname, .author-lastname').forEach(input => {
                        input.addEventListener('input', function() {
                            if (this.value.trim() === '') {
                                this.classList.add('is-invalid');
                            } else {
                                this.classList.remove('is-invalid');
                                this.classList.add('is-valid');
                            }
                        });
                    });
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Collect data from all author entries
                const authorEntries = document.querySelectorAll('#authorEntriesContainer .author-entry');
                const authorsData = [];
                let hasErrors = false;

                authorEntries.forEach(entry => {
                    const firstname = entry.querySelector('.author-firstname').value.trim();
                    const middle_init = entry.querySelector('.author-middleinit').value.trim();
                    const lastname = entry.querySelector('.author-lastname').value.trim();

                    if (!firstname || !lastname) {
                        hasErrors = true;

                        // Highlight empty fields
                        if (!firstname) {
                            entry.querySelector('.author-firstname').classList.add('is-invalid');
                        }
                        if (!lastname) {
                            entry.querySelector('.author-lastname').classList.add('is-invalid');
                        }
                        return;
                    }

                    authorsData.push({
                        firstname: firstname,
                        middle_init: middle_init,
                        lastname: lastname
                    });
                });

                if (hasErrors) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'First name and last name are required for all authors.',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                if (authorsData.length === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No Authors Added',
                        text: 'Please add at least one author.',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                // Show loading state
                Swal.fire({
                    title: 'Saving Authors',
                    html: '<div class="d-flex justify-content-center align-items-center"><i class="fas fa-spinner fa-spin fa-2x mr-2"></i> Please wait...</div>',
                    allowOutsideClick: false,
                    showConfirmButton: false
                });

                // AJAX request to save all authors
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'ajax/add_writers.php', true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.onload = function() {
                    Swal.close(); // Close loading indicator
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

                                    if (authorSelect) authorSelect.appendChild(newOption.cloneNode(true));
                                    if (coAuthorsSelect) coAuthorsSelect.appendChild(newOption.cloneNode(true));
                                    if (editorsSelect) editorsSelect.appendChild(newOption.cloneNode(true));
                                });

                                // Auto-select the last added author
                                if (authorSelect && response.authors.length > 0) {
                                    const lastAuthor = response.authors[response.authors.length - 1];
                                    authorSelect.value = lastAuthor.id;
                                    // Trigger change event to update any dependent UI
                                    authorSelect.dispatchEvent(new Event('change'));
                                }

                                // Prepare list of added authors for the success message
                                let addedAuthorsHtml = '<ul class="list-group list-group-flush text-center small mt-2" style="max-height: 150px; overflow-y: auto; display: inline-block;">'; // Centered list
                                response.authors.forEach(author => {
                                    addedAuthorsHtml += `<li class="list-group-item py-1">${author.name}</li>`;
                                });
                                addedAuthorsHtml += '</ul>';

                                Swal.fire({
                                    icon: 'success',
                                    title: '<span style="font-size: 1.2em;">Authors Added Successfully!</span>',
                                    html: `
                                        <div class="text-center"> <!-- Center align content -->
                                            <p><strong>${response.authors.length}</strong> new author(s) have been added:</p>
                                            ${addedAuthorsHtml}
                                            <p class="small text-muted mb-0 mt-2">The author dropdowns have been updated.</p>
                                        </div>
                                    `,
                                    confirmButtonText: '<i class="fas fa-check"></i> OK',
                                    confirmButtonColor: '#3085d6'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error Adding Authors',
                                    html: `<p>An error occurred:</p><p class="text-danger">${response.message || 'Failed to add authors'}</p>`,
                                    confirmButtonColor: '#d33',
                                    confirmButtonText: '<i class="fas fa-times"></i> Close'
                                });
                            }
                        } catch (e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Processing Error',
                                html: `<p>Error processing server response:</p><p class="text-danger">${e.message}</p>`,
                                confirmButtonColor: '#d33',
                                confirmButtonText: '<i class="fas fa-times"></i> Close'
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            html: `<p>Failed to communicate with the server (Status: ${this.status}).</p>`,
                            confirmButtonColor: '#d33',
                            confirmButtonText: '<i class="fas fa-times"></i> Close'
                        });
                    }
                };
                xhr.onerror = function() { // Handle network errors
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Could not connect to the server. Please check your network connection.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: '<i class="fas fa-times"></i> Close'
                    });
                };
                xhr.send(JSON.stringify(authorsData));
            }
        });
    };

    // Create a function to show the add publisher dialog using SweetAlert
    window.showAddPublisherDialog = function() {
        Swal.fire({
            title: '<i class="fas fa-building"></i> Add New Publisher',
            html: `
                <div id="sweetAlertPublisherContainer">
                    <p class="text-muted mb-3">Enter publisher details below. You can add multiple publishers at once.</p>
                    <div id="publisherEntriesContainer">
                        <div class="publisher-entry card mb-3 p-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Publisher Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control publisher-name" placeholder="Enter publisher name" required>
                                        <div class="invalid-feedback">Publisher name is required</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Place of Publication <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control publisher-place" placeholder="Enter place of publication" required>
                                        <div class="invalid-feedback">Place is required</div>
                                        <small class="form-text text-muted">Example: New York, Manila, London</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-save"></i> Save Publisher',
            confirmButtonColor: '#3085d6',
            cancelButtonText: '<i class="fas fa-times"></i> Cancel',
            cancelButtonColor: '#d33',
            width: '800px',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-danger'
            },
            didOpen: () => {
                // Add button below the publisher entries container
                const container = document.getElementById('sweetAlertPublisherContainer');
                const addButton = document.createElement('button');
                addButton.type = 'button';
                addButton.className = 'btn btn-secondary btn-sm mt-2 add-publisher-btn';
                addButton.innerHTML = '<i class="fas fa-plus"></i> Add Another Publisher';
                addButton.id = 'addPublisherEntry';
                addButton.style.display = 'block';
                addButton.style.width = '100%';
                addButton.style.marginBottom = '10px';
                container.appendChild(addButton);

                // Setup validation listeners
                setupValidationListeners();

                // Add event listener for the button
                addButton.addEventListener('click', function() {
                    const publisherEntriesContainer = document.getElementById('publisherEntriesContainer');
                    const newEntry = document.createElement('div');
                    newEntry.className = 'publisher-entry card mb-3 p-3';
                    newEntry.innerHTML = `
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="text-muted">Additional Publisher</h6>
                            <button type="button" class="btn btn-danger btn-sm remove-publisher-entry">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Publisher Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control publisher-name" placeholder="Enter publisher name" required>
                                    <div class="invalid-feedback">Publisher name is required</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Place of Publication <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control publisher-place" placeholder="Enter place of publication" required>
                                    <div class="invalid-feedback">Place is required</div>
                                    <small class="form-text text-muted">Example: New York, Manila, London</small>
                                </div>
                            </div>
                        </div>
                    `;
                    publisherEntriesContainer.appendChild(newEntry);

                    // Setup validation for new fields
                    setupValidationListeners(newEntry);

                    // Scroll to the bottom of the container to show the new entry
                    const swalContent = document.querySelector('.swal2-content');
                    if (swalContent) {
                        swalContent.scrollTop = swalContent.scrollHeight;
                    }

                    // Add remove functionality for the new entry
                    newEntry.querySelector('.remove-publisher-entry').addEventListener('click', function() {
                        newEntry.remove();
                    });
                });

                // Setup delegation for removing publisher entries
                document.addEventListener('click', function(e) {
                    if (e.target && (e.target.classList.contains('remove-publisher-entry') || e.target.closest('.remove-publisher-entry'))) {
                        const entry = e.target.closest('.publisher-entry');
                        if (entry) {
                            entry.remove();
                        }
                    }
                });

                function setupValidationListeners(parent = document) {
                    // Setup real-time validation
                    parent.querySelectorAll('.publisher-name, .publisher-place').forEach(input => {
                        input.addEventListener('input', function() {
                            if (this.value.trim() === '') {
                                this.classList.add('is-invalid');
                            } else {
                                this.classList.remove('is-invalid');
                                this.classList.add('is-valid');
                            }
                        });
                    });
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const publisherEntries = document.querySelectorAll('#publisherEntriesContainer .publisher-entry');
                const publishersData = [];
                let hasErrors = false;

                // Collect data from all publisher entries
                publisherEntries.forEach(entry => {
                    const publisher = entry.querySelector('.publisher-name').value.trim();
                    const place = entry.querySelector('.publisher-place').value.trim();

                    if (!publisher || !place) {
                        hasErrors = true;

                        // Highlight empty fields
                        if (!publisher) {
                            entry.querySelector('.publisher-name').classList.add('is-invalid');
                        }
                        if (!place) {
                            entry.querySelector('.publisher-place').classList.add('is-invalid');
                        }
                        return;
                    }

                    publishersData.push({
                        publisher: publisher,
                        place: place
                    });
                });

                if (hasErrors) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Publisher name and place are required for all publishers.',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                if (publishersData.length === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No Publishers Added',
                        text: 'Please add at least one publisher.',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                // Show loading state
                Swal.fire({
                    title: 'Saving Publishers',
                    html: '<div class="d-flex justify-content-center align-items-center"><i class="fas fa-spinner fa-spin fa-2x mr-2"></i> Please wait...</div>',
                    allowOutsideClick: false,
                    showConfirmButton: false
                });

                // AJAX request to save all publishers
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'ajax/add_publishers.php', true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.onload = function() {
                    Swal.close(); // Close loading indicator
                    if (this.status === 200) {
                        try {
                            const response = JSON.parse(this.responseText);
                            if (response.success) {
                                // Refresh publisher dropdown
                                const publisherSelect = document.getElementById('publisher');
                                if (publisherSelect) {
                                    response.publishers.forEach(pub => {
                                        const option = document.createElement('option');
                                        option.value = pub.id;
                                        option.textContent = `${pub.place} ; ${pub.publisher}`;
                                        publisherSelect.appendChild(option);
                                    });

                                    // Auto-select the last added publisher
                                    if (response.publishers.length > 0) {
                                        const lastPub = response.publishers[response.publishers.length - 1];
                                        publisherSelect.value = lastPub.id;
                                        // Trigger change event to update any dependent UI
                                        publisherSelect.dispatchEvent(new Event('change'));
                                    }
                                }

                                // Prepare list of added publishers for the success message
                                let addedPublishersHtml = '<ul class="list-group list-group-flush text-center small mt-2" style="max-height: 150px; overflow-y: auto; display: inline-block;">'; // Centered list
                                response.publishers.forEach(pub => {
                                    addedPublishersHtml += `<li class="list-group-item py-1">${pub.publisher} (${pub.place})</li>`;
                                });
                                addedPublishersHtml += '</ul>';

                                Swal.fire({
                                    icon: 'success',
                                    title: '<span style="font-size: 1.2em;">Publishers Added Successfully!</span>',
                                    html: `
                                        <div class="text-center"> <!-- Center align content -->
                                            <p><strong>${response.publishers.length}</strong> new publisher(s) have been added:</p>
                                            ${addedPublishersHtml}
                                            <p class="small text-muted mb-0 mt-2">The publisher dropdown has been updated.</p>
                                        </div>
                                    `,
                                    confirmButtonText: '<i class="fas fa-check"></i> OK',
                                    confirmButtonColor: '#3085d6'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error Adding Publishers',
                                    html: `<p>An error occurred:</p><p class="text-danger">${response.message || 'Failed to add publishers'}</p>`,
                                    confirmButtonColor: '#d33',
                                    confirmButtonText: '<i class="fas fa-times"></i> Close'
                                });
                            }
                        } catch (e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Processing Error',
                                html: `<p>Error processing server response:</p><p class="text-danger">${e.message}</p>`,
                                confirmButtonColor: '#d33',
                                confirmButtonText: '<i class="fas fa-times"></i> Close'
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            html: `<p>Failed to communicate with the server (Status: ${this.status}).</p>`,
                            confirmButtonColor: '#d33',
                            confirmButtonText: '<i class="fas fa-times"></i> Close'
                        });
                    }
                };
                xhr.onerror = function() { // Handle network errors
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Could not connect to the server. Please check your network connection.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: '<i class="fas fa-times"></i> Close'
                    });
                };
                xhr.send(JSON.stringify(publishersData));
            }
        });
    };

    // Set up event listeners for the "Add New" buttons
    const addNewAuthorBtn = document.getElementById('addNewAuthorBtn');
    if (addNewAuthorBtn) {
        addNewAuthorBtn.addEventListener('click', showAddAuthorDialog);
    }

    const addNewPublisherBtn = document.getElementById('addNewPublisherBtn');
    if (addNewPublisherBtn) {
        addNewPublisherBtn.addEventListener('click', showAddPublisherDialog);
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Update file input labels when a file is selected
    document.querySelectorAll('.custom-file-input').forEach(input => {
        input.addEventListener('change', function () {
            const fileName = this.files[0]?.name || 'Choose file';
            const label = this.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                label.textContent = fileName;
            }
        });
    });
});

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
</script>

<!-- Add this JavaScript for validation indicators -->
<script>
/**
 * Live validation indicators for required fields
 */
document.addEventListener('DOMContentLoaded', function() {
  // Find all required inputs and add validation classes and indicators
  const requiredInputs = document.querySelectorAll('input[required], textarea[required], select[required]');
  
  requiredInputs.forEach(input => {
    // Add live validation class
    input.classList.add('live-validate');
    
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
    }
  });
  
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
    const observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
          mutation.addedNodes.forEach(function(node) {
            if (node.nodeType === Node.ELEMENT_NODE && node.classList.contains('accession-group')) {
              const newInput = node.querySelector('.accession-input');
              if (newInput) {
                // Set up validation for the new input
                validateAccessionInput(newInput);
                
                newInput.addEventListener('input', function() {
                  validateAccessionInput(this);
                });
                
                newInput.addEventListener('blur', function() {
                  validateAccessionInput(this);
                });
              }
            }
          });
        }
      });
    });
    
    // Start observing
    observer.observe(accessionContainer, { childList: true });
  }
  
  // Add validation to the main title field
  const titleInput = document.getElementById('title');
  if (titleInput) {
    // Special styling for the title field
    const titleForm = titleInput.closest('.form-group');
    if (titleForm) {
      titleForm.style.position = 'relative';
    }
    
    function validateTitleField() {
      const isValid = titleInput.value.trim() !== '';
      if (isValid) {
        titleInput.classList.remove('is-invalid');
        titleInput.classList.add('is-valid');
      } else {
        titleInput.classList.remove('is-valid');
        titleInput.classList.add('is-invalid');
      }
      
      // Find or create indicators
      let parentElement = titleInput.closest('.form-group');
      let indicator = parentElement.querySelector('.validation-indicator');
      let check = parentElement.querySelector('.validation-check');
      
      if (!indicator) {
        indicator = document.createElement('i');
        indicator.className = 'fas fa-exclamation-circle validation-indicator';
        parentElement.appendChild(indicator);
      }
      
      if (!check) {
        check = document.createElement('i');
        check.className = 'fas fa-check-circle validation-check';
        parentElement.appendChild(check);
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
    
    // Initial validation
    validateTitleField();
    
    // Set up listeners
    titleInput.addEventListener('input', validateTitleField);
    titleInput.addEventListener('blur', validateTitleField);
  }
});

// Extend the addAccessionGroup function to handle validation for new groups
const originalAddAccessionGroup = addAccessionGroup;
addAccessionGroup = function() {
  // Call the original function
  originalAddAccessionGroup();
  
  // Then find the newly added accession group
  const accessionContainer = document.getElementById('accessionContainer');
  if (accessionContainer) {
    const groups = accessionContainer.querySelectorAll('.accession-group');
    const newGroup = groups[groups.length - 1];
    
    if (newGroup) {
      const newInput = newGroup.querySelector('.accession-input');
      if (newInput) {
        // Set up validation for the new input
        const isValid = newInput.value.trim() !== '';
        
        if (isValid) {
          newInput.classList.remove('is-invalid');
          newInput.classList.add('is-valid');
        } else {
          newInput.classList.remove('is-valid');
          newInput.classList.add('is-invalid');
        }
        
        // Add validation indicators
        const parentElement = newInput.closest('.form-group');
        if (parentElement) {
          let indicator = document.createElement('i');
          indicator.className = 'fas fa-exclamation-circle validation-indicator';
          if (!isValid) indicator.classList.add('show');
          
          let check = document.createElement('i');
          check.className = 'fas fa-check-circle validation-check';
          if (isValid) check.classList.add('show');
          
          parentElement.style.position = 'relative';
          parentElement.appendChild(indicator);
          parentElement.appendChild(check);
        }
        
        // Add event listeners
        newInput.addEventListener('input', function() {
          const isValid = this.value.trim() !== '';
          
          if (isValid) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            
            const parentElement = this.closest('.form-group');
            if (parentElement) {
              const indicator = parentElement.querySelector('.validation-indicator');
              const check = parentElement.querySelector('.validation-check');
              
              if (indicator) indicator.classList.remove('show');
              if (check) check.classList.add('show');
            }
          } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
            
            const parentElement = this.closest('.form-group');
            if (parentElement) {
              const indicator = parentElement.querySelector('.validation-indicator');
              const check = parentElement.querySelector('.validation-check');
              
              if (indicator) indicator.classList.add('show');
              if (check) check.classList.remove('show');
            }
          }
        });
        
        // Trigger focus after validation setup
        setTimeout(() => newInput.focus(), 100);
      }
    }
  }
};
</script>