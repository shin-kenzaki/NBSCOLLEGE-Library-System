<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';

// Change the subject options array at the top of the file
$subject_options = array(
    "Topical",
    "Personal",
    "Corporate",
    "Geographical",
    "Scientific",
    "Historical",
    "Literary",
    "Artistic"
);

// Add this after the existing $subject_options array
$specific_subjects = array(
    "Topical" => array(
        "Philosophy", "Psychology", "Religion", "Social Sciences",
        "Library Sciences", "Language", "Natural Sciences", "Technology", "Arts",
        "Literature", "History", "Geography", "Mathematics", "Physics", "Chemistry", "Biology"
    ),
    "Personal" => array(
        "Author", "Artist", "Musician", "Scientist",
        "Historical Figure", "Political Figure", "Athlete", "Actor"
    ),
    "Corporate" => array(
        "Government Agency", "Educational Institution",
        "Business Organization", "Non-profit Organization",
        "Religious Institution", "Media Company", "Tech Company"
    ),
    "Geographical" => array(
        "Continent", "Country", "City", "Region",
        "Landmark", "Geographic Feature", "Ocean", "Mountain"
    ),
    "Scientific" => array(
        "Astronomy", "Biology", "Chemistry", "Physics",
        "Earth Science", "Environmental Science", "Computer Science"
    ),
    "Historical" => array(
        "Ancient History", "Medieval History", "Modern History",
        "World Wars", "Revolutions", "Historical Events"
    ),
    "Literary" => array(
        "Poetry", "Novels", "Short Stories", "Drama",
        "Essays", "Biographies", "Autobiographies"
    ),
    "Artistic" => array(
        "Painting", "Sculpture", "Photography", "Architecture",
        "Music", "Dance", "Theater", "Film"
    )
);

// Convert the array to JSON for JavaScript use
$specific_subjects_json = json_encode($specific_subjects);

$accession_error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accessions = $_POST['accession'];
    $number_of_copies_array = $_POST['number_of_copies'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $preferred_title = mysqli_real_escape_string($conn, $_POST['preferred_title']);
    $parallel_title = mysqli_real_escape_string($conn, $_POST['parallel_title']);
    $summary = mysqli_real_escape_string($conn, $_POST['abstract']);
    $contents = mysqli_real_escape_string($conn, $_POST['notes']);
    
    // Handle file uploads
    $front_image = '';
    $back_image = '';
    if(isset($_FILES['front_image']) && $_FILES['front_image']['error'] == 0) {
        $front_image = 'uploads/' . basename($_FILES['front_image']['name']);
        move_uploaded_file($_FILES['front_image']['tmp_name'], $front_image);
    }
    if(isset($_FILES['back_image']) && $_FILES['back_image']['error'] == 0) {
        $back_image = 'uploads/' . basename($_FILES['back_image']['name']);
        move_uploaded_file($_FILES['back_image']['tmp_name'], $back_image);
    }

    $height = mysqli_real_escape_string($conn, $_POST['height']);
    $width = mysqli_real_escape_string($conn, $_POST['width']);
    $series = mysqli_real_escape_string($conn, $_POST['series']);
    $volume = mysqli_real_escape_string($conn, $_POST['volume']);
    $edition = mysqli_real_escape_string($conn, $_POST['edition']);
    $total_pages = mysqli_real_escape_string($conn, $_POST['total_pages']);
    $content_type = mysqli_real_escape_string($conn, $_POST['content_type']);
    $media_type = mysqli_real_escape_string($conn, $_POST['media_type']);
    $carrier_type = mysqli_real_escape_string($conn, $_POST['carrier_type']);
    $url = mysqli_real_escape_string($conn, $_POST['url']);
    $language = mysqli_real_escape_string($conn, $_POST['language']);
    $shelf_location = mysqli_real_escape_string($conn, $_POST['shelf_location']);
    $entered_by = $_POST['entered_by'];
    $date_added = $_POST['date_added'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $last_update = $_POST['last_update'];

    $success_count = 0;
    $error_messages = array();
    $isbn_index = 0;

    // Process each accession number and its copies
    for ($i = 0; $i < count($accessions); $i++) {
        $base_accession = $accessions[$i];
        $copies_for_this_accession = (int)$number_of_copies_array[$i];
        
        for ($j = 0; $j < $copies_for_this_accession; $j++) {
            $current_accession = $base_accession + $j;
            $current_isbn = isset($_POST['isbn'][$isbn_index]) ? mysqli_real_escape_string($conn, $_POST['isbn'][$isbn_index]) : '';
            $current_call_number = isset($_POST['call_number'][$isbn_index]) ? mysqli_real_escape_string($conn, $_POST['call_number'][$isbn_index]) : '';
            $isbn_index++;

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

            // Process subject entries for this copy
            $subject_categories = isset($_POST['subject_categories']) ? $_POST['subject_categories'] : array();
            $subject_entries = isset($_POST['subject_entries']) ? $_POST['subject_entries'] : array();
            $subject_paragraphs = isset($_POST['subject_paragraphs']) ? $_POST['subject_paragraphs'] : array();

            // Combine all subject entries into strings for storage
            $all_categories = array();
            $all_specifications = array();
            $all_details = array();

            for ($k = 0; $k < count($subject_categories); $k++) {
                if (!empty($subject_categories[$k])) {
                    $all_categories[] = mysqli_real_escape_string($conn, $subject_categories[$k]);
                    $all_specifications[] = mysqli_real_escape_string($conn, $subject_entries[$k]);
                    $all_details[] = mysqli_real_escape_string($conn, $subject_paragraphs[$k]);
                }
            }

            $subject_category = implode('; ', $all_categories);
            $subject_specification = implode('; ', $all_specifications);
            $subject_detail = implode('; ', $all_details);

            $query = "INSERT INTO books (
                accession, title, preferred_title, parallel_title, 
                subject_category, subject_specification, subject_detail,
                summary, contents, front_image, back_image, 
                height, width, series, volume, edition, 
                copy_number, total_pages, ISBN, content_type, 
                media_type, carrier_type, call_number, URL, 
                language, shelf_location, entered_by, date_added, 
                status, last_update
            ) VALUES (
                '$current_accession', '$title', '$preferred_title', '$parallel_title',
                '$subject_category', '$subject_specification', '$subject_detail',
                '$summary', '$contents', '$front_image', '$back_image',
                '$height', '$width', '$series', '$volume', '$edition',
                $copy_number, '$total_pages', '$current_isbn', '$content_type',
                '$media_type', '$carrier_type', '$current_call_number', '$url',
                '$language', '$shelf_location', '$entered_by', '$date_added',
                '$status', '$last_update'
            )";

            if (mysqli_query($conn, $query)) {
                $success_count++;
            } else {
                $error_messages[] = "Error adding book with accession $current_accession: " . mysqli_error($conn);
            }
        }
    }

    // Display results
    if (!empty($error_messages)) {
        echo "<script>alert('Note: Some accessions were skipped:\\n" . implode("\\n", $error_messages) . "\\n\\nSuccessfully added " . $success_count . " books.');</script>";
    } else if ($success_count > 0) {
        echo "<script>alert('Successfully added all " . $success_count . " books!');</script>";
    }
}

?>

<!-- Main Content -->
<div id="content-wrapper" class="d-flex flex-column min-vh-100">
    <div id="content" class="flex-grow-1">
        <div class="container-fluid">
            <!-- Fix: Remove enctype if not needed -->
            <form id="bookForm" action="add-book.php" method="POST" enctype="multipart/form-data" class="h-100">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-2 text-gray-800">Add Book</h1>
                    <!-- Fix: Change button type to submit -->
                    <button type="submit" name="submit" class="btn btn-success">Add Book</button>
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
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#publication" role="tab">Publication</a>
                            </li>
                        </ul>

                        <div class="tab-content mt-3" id="formTabsContent">
                            <!-- Title Proper Tab -->
                            <div class="tab-pane fade show active" id="title-proper" role="tabpanel">
                                <h4>Title Proper</h4>
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" class="form-control" name="title" required>
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
                                            <div class="col-md-4">
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
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Specific Subject</label>
                                                    <select class="form-control specific-subject" name="subject_entries[]" disabled>
                                                        <option value="">Select Category First</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Details</label>
                                                    <textarea class="form-control" name="subject_paragraphs[]" 
                                                        rows="3" placeholder="Enter additional details about this subject"></textarea>
                                                </div>
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
                                <div class="form-group">
                                    <label>Front Image</label>
                                    <input type="file" class="form-control" name="front_image">
                                </div>
                                <div class="form-group">
                                    <label>Back Image</label>
                                    <input type="file" class="form-control" name="back_image">
                                </div>
                                <div class="form-group">
                                    <label>Height (cm)</label>
                                    <input type="number" step="0.01" class="form-control" name="height">
                                </div>
                                <div class="form-group">
                                    <label>Width (cm)</label>
                                    <input type="number" step="0.01" class="form-control" name="width">
                                </div>
                                <div class="form-group">
                                    <label>Pages</label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="small">Prefix (Roman)</label>
                                            <input type="text" class="form-control" name="prefix_pages" placeholder="e.g. xvi">
                                            <small class="text-muted">Use roman numerals</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small">Main Pages</label>
                                            <input type="text" class="form-control" name="total_pages" placeholder="e.g. 234a">
                                            <small class="text-muted">Can include letters (e.g. 123a, 456b)</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small">Bibliography Pages</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="bibliography_pages">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">p. bibl.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <label class="small">Index Pages</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="index_pages">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">p. index</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small">Glossary Pages</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="glossary_pages">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">p. gloss.</span>
                                                </div>
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
                                                    <div class="accession-group mb-3">
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <div class="form-group">
                                                                    <label>Accession (Copy 1)</label>
                                                                    <input type="text" class="form-control accession-input" name="accession[]" 
                                                                        placeholder="e.g., 2023-0001 (will auto-increment based on copies)" required>
                                                                    <small class="text-muted">If you enter 2023-0001 and set 3 copies, it will create: 2023-0001, 2023-0002, 2023-0003</small>
                                                                    <?php if ($accession_error): ?>
                                                                        <small class="text-danger"><?php echo $accession_error; ?></small>
                                                                    <?php endif; ?>
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
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!--  -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Language</label>
                                                    <select class="form-control" name="language">
                                                        <option value="English">English</option>
                                                        <option value="Spanish">Spanish</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Shelf Location</label>
                                                    <select class="form-control" name="shelf_location">
                                                        <option value="GC">General Circulation</option>
                                                        <option value="TR">Teachers Reference</option>
                                                        <option value="FIL">Filipiniana</option>
                                                        <option value="CIR">Circulation</option>
                                                        <option value="REF">Reference</option>
                                                        <option value="SC">Special Collection</option>
                                                        <option value="BIO">Biography</option>
                                                        <option value="RES">Reserve</option>
                                                        <option value="SCH">Scholastic</option>
                                                        <option value="EAS">Easy</option>
                                                        <option value="FIC">Fiction</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <!--  -->

                                        <!--  -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Entered By</label>
                                                    <input type="text" class="form-control" name="entered_by" value="<?php echo $_SESSION['admin_id']; ?>" readonly>
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
                                            <!--  -->
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
                                            <!--  -->

                                            <!--  -->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Edition</label>
                                                        <input type="text" class="form-control" name="edition">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>ISBN & Call Number</label>
                                                        <div id="isbnContainer">
                                                            <div class="input-group mb-2">
                                                                <input type="text" class="form-control" name="isbn[]" 
                                                                    placeholder="Enter ISBN for Copy 1">
                                                                <input type="text" class="form-control" name="call_number[]" 
                                                                    placeholder="Enter call number for Copy 1">
                                                            </div>
                                                        </div>
                                                        <small class="text-muted">Each copy needs unique ISBN and call number</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <!--  -->

                                            <!--  -->
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
                                                <!--  -->

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
                                                    <!--  -->
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
});

function updateISBNFields() {
    const isbnContainer = document.getElementById('isbnContainer');
    isbnContainer.innerHTML = '';
    
    // Get all accession groups
    const accessionGroups = document.querySelectorAll('.accession-group');
    
    accessionGroups.forEach((group) => {
        const accessionInput = group.querySelector('.accession-input').value;
        const copiesCount = parseInt(group.querySelector('.copies-input').value) || 1;
        
        for (let i = 0; i < copiesCount; i++) {
            const currentAccession = calculateAccession(accessionInput, i);
            
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            
            const isbnInput = document.createElement('input');
            isbnInput.type = 'text';
            isbnInput.className = 'form-control';
            isbnInput.name = 'isbn[]';
            isbnInput.placeholder = `Enter ISBN for Accession ${currentAccession}`;
            
            const callNumberInput = document.createElement('input');
            callNumberInput.type = 'text';
            callNumberInput.className = 'form-control';
            callNumberInput.name = 'call_number[]';
            callNumberInput.placeholder = `Enter call number for Accession ${currentAccession}`;
            
            div.appendChild(isbnInput);
            div.appendChild(callNumberInput);
            isbnContainer.appendChild(div);
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
        `;
        accessionContainer.appendChild(newGroup);
        updateISBNFields();
    }

    if (e.target && e.target.classList.contains('remove-accession')) {
        e.target.closest('.accession-group').remove();
        updateISBNFields();
    }
});

// Add this new function for form validation
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
        alert(errorMessage);
        e.preventDefault();
        return false;
    }
    return true;
}

// Add input validation for numbers only
document.addEventListener('input', function(e) {
    if (e.target && e.target.classList.contains('accession-input')) {
        e.target.value = e.target.value.replace(/\D/g, ''); // Remove non-digits
    }
});

// Add event listener to the form
document.getElementById('bookForm').addEventListener('submit', validateForm);

// Initialize specific subjects data
const specificSubjects = <?php echo $specific_subjects_json; ?>;

// Function to populate specific subjects dropdown
function populateSpecificSubjects(categorySelect) {
    const specificSelect = categorySelect.closest('.row').querySelector('.specific-subject');
    const selectedCategory = categorySelect.value;
    
    specificSelect.innerHTML = '<option value="">Select Specific Subject</option>';
    specificSelect.disabled = !selectedCategory;
    
    if (selectedCategory && specificSubjects[selectedCategory]) {
        specificSubjects[selectedCategory].forEach(subject => {
            const option = document.createElement('option');
            option.value = subject;
            option.textContent = subject;
            specificSelect.appendChild(option);
        });
    }
}

// Event listener for category changes
document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('subject-category')) {
        populateSpecificSubjects(e.target);
    }
});

</script>
