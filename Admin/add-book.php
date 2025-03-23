<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection
include '../db.php';

// Include the processing file for form submissions
include 'process/process-add-book.php';

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
                    <button type="submit" name="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Book
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
                        <ul class="nav nav-tabs" id="formTabs" role="tablist">
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

                        <div class="tab-content card border-0 shadow-sm p-4 mt-3" id="formTabsContent">
                            <!-- Title Proper Tab -->
                            <div class="tab-pane fade show active" id="title-proper" role="tabpanel">
                                <h4 class="mb-3">Title Information</h4>
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
                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary disabled">Previous</button>
                                    <button type="button" class="btn btn-primary next-tab" data-next="subject-tab">Next</button>
                                </div>
                            </div>

                            <!-- Subject Entry Tab -->
                            <div class="tab-pane fade" id="subject-entry" role="tabpanel">
                                <h4 class="mb-3">Subject Entry</h4>
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
                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary prev-tab" data-prev="title-tab">Previous</button>
                                    <button type="button" class="btn btn-primary next-tab" data-next="abstracts-tab">Next</button>
                                </div>
                            </div>

                            <!-- Abstracts Tab -->
                            <div class="tab-pane fade" id="abstracts" role="tabpanel">
                                <h4 class="mb-3">Abstract & Notes</h4>
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
                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary prev-tab" data-prev="subject-tab">Previous</button>
                                    <button type="button" class="btn btn-primary next-tab" data-next="description-tab">Next</button>
                                </div>
                            </div>

                            <!-- Description Tab -->
                            <div class="tab-pane fade" id="description" role="tabpanel">
                                <h4 class="mb-3">Description</h4>
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
                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary prev-tab" data-prev="abstracts-tab">Previous</button>
                                    <button type="button" class="btn btn-primary next-tab" data-next="local-info-tab">Next</button>
                                </div>
                            </div>

                            <!-- Local Information Tab -->
                            <div class="tab-pane fade" id="local-info" role="tabpanel">
                                <h4 class="mb-3">Local Information</h4>
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
                                    </div>
                                    <div class="card-body">
                                        <div id="callNumberContainer">
                                            <!-- Call numbers will be generated here by JavaScript -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Other Information -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="content_type">Content Type</label>
                                            <select class="form-control" id="content_type" name="content_type">
                                                <option value="text">Text</option>
                                                <option value="still image">Still Image</option>
                                                <option value="performed music">Performed Music</option>
                                                <option value="spoken word">Spoken Word</option>
                                                <option value="computer program">Computer Program</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="media_type">Media Type</label>
                                            <select class="form-control" id="media_type" name="media_type">
                                                <option value="unmediated">Unmediated</option>
                                                <option value="audio">Audio</option>
                                                <option value="computer">Computer</option>
                                                <option value="microform">Microform</option>
                                                <option value="video">Video</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="carrier_type">Carrier Type</label>
                                            <select class="form-control" id="carrier_type" name="carrier_type">
                                                <option value="volume">Volume</option>
                                                <option value="audio disc">Audio Disc</option>
                                                <option value="online resource">Online Resource</option>
                                                <option value="computer disc">Computer Disc</option>
                                                <option value="videocassette">Videocassette</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="language">Language</label>
                                            <select class="form-control" id="language" name="language">
                                                <option value="eng">English</option>
                                                <option value="fil">Filipino</option>
                                                <option value="spa">Spanish</option>
                                                <option value="fra">French</option>
                                                <option value="jpn">Japanese</option>
                                                <option value="kor">Korean</option>
                                                <option value="chi">Chinese</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="url">URL (if applicable)</label>
                                    <input type="url" class="form-control" id="url" name="url" placeholder="https://example.com">
                                </div>
                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary prev-tab" data-prev="description-tab">Previous</button>
                                    <button type="button" class="btn btn-primary next-tab" data-next="publication-tab">Next</button>
                                </div>
                            </div>

                            <!-- Publication Tab -->
                            <div class="tab-pane fade" id="publication" role="tabpanel">
                                <h4 class="mb-3">Publication Details</h4>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="publisher">Publisher</label>
                                            <div class="input-group mb-2">
                                                <input type="text" id="publisherSearch" class="form-control" placeholder="Search publishers...">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" data-toggle="modal" data-target="#addPublisherModal">
                                                        <i class="fas fa-plus"></i> New
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
                                        <!-- Authors Section -->
                                        <div class="form-group">
                                            <label for="authorSelect">Author(s)</label>
                                            <div class="input-group mb-2">
                                                <input type="text" id="authorSearch" class="form-control" placeholder="Search authors...">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" data-toggle="modal" data-target="#addAuthorModal">
                                                        <i class="fas fa-plus"></i> New
                                                    </button>
                                                </div>
                                            </div>
                                            <select id="authorSelect" name="author[]" class="form-control" required>
                                                <option value="">Select Author</option>
                                                <?php foreach ($writers as $writer): ?>
                                                    <option value="<?php echo $writer['id']; ?>"><?php echo $writer['name']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div id="authorPreview" class="selected-preview mt-2"></div>
                                        </div>

                                        <!-- Co-Authors Section -->
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

                                        <!-- Editors Section -->
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

                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary prev-tab" data-prev="local-info-tab">Previous</button>
                                    <button type="submit" name="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Save Book
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form> <!-- Form closing tag -->
        </div>
    </div>
    <?php include '../admin/inc/footer.php'; ?>
</div>

<!-- Add Author Modal -->
<div class="modal fade" id="addAuthorModal" tabindex="-1" role="dialog" aria-labelledby="addAuthorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAuthorModalLabel">Add New Authors</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="newAuthorForm">
                    <div id="authorEntriesContainer">
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
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" id="addAuthorEntry">
                        <i class="fas fa-plus"></i> Add Another Author
                    </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveAuthors">Save All Authors</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Publisher Modal -->
<div class="modal fade" id="addPublisherModal" tabindex="-1" role="dialog" aria-labelledby="addPublisherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPublisherModalLabel">Add New Publishers</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="newPublisherForm">
                    <div id="publisherEntriesContainer">
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
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" id="addPublisherEntry">
                        <i class="fas fa-plus"></i> Add Another Publisher
                    </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="savePublishers">Save All Publishers</button>
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
</style>

<!-- Bootstrap and JS -->
<script src="inc/js/demo/chart-area-demo.js"></script>
<script src="inc/js/demo/chart-pie-demo.js"></script>
<script src="inc/js/demo/chart-bar-demo.js"></script>

<!-- Include External JavaScript Files -->
<script src="js/form-navigation.js"></script>
<script src="js/form-validation.js"></script>
<script src="js/accession-management.js"></script>
<script src="js/author-management.js"></script>
<script src="js/form-autosave.js"></script>
