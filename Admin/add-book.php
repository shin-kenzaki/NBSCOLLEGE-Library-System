<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';

$accession_error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accessions = $_POST['accession'];
    $number_of_copies_array = $_POST['number_of_copies'];
    $call_numbers = $_POST['call_number'];
    $isbns = isset($_POST['isbn']) ? $_POST['isbn'] : array();
    $title = $_POST['title'];
    
    // Other form fields
    $preferred_title = $_POST['preferred_title'];
    $parallel_title = $_POST['parallel_title'];
    $front_image = $_FILES['front_image']['name'];
    $back_image = $_FILES['back_image']['name'];
    $height = $_POST['height'];
    $width = $_POST['width'];
    $prefix_pages = $_POST['prefix_pages'];
    $main_pages = $_POST['total_pages'];
    $bibliography_pages = $_POST['bibliography_pages'];
    $index_pages = $_POST['index_pages'];
    $glossary_pages = $_POST['glossary_pages'];
    
    // Combine the page information
    $total_pages = '';
    if (!empty($prefix_pages)) {
        $total_pages .= $prefix_pages . ', ';
    }
    $total_pages .= $main_pages;
    if (!empty($bibliography_pages)) {
        $total_pages .= ' + ' . $bibliography_pages . ' p. bibl.';
    }
    if (!empty($index_pages)) {
        $total_pages .= ' + ' . $index_pages . ' p. index';
    }
    if (!empty($glossary_pages)) {
        $total_pages .= ' + ' . $glossary_pages . ' p. gloss.';
    }
    
    $call_number = $_POST['call_number'];
    $language = $_POST['language'];
    $shelf_location = $_POST['shelf_location'];
    $entered_by = $_POST['entered_by'];
    $date_added = $_POST['date_added'];
    $status = $_POST['status'];
    $last_update = $_POST['last_update'];
    $series = $_POST['series'];
    $volume = $_POST['volume'];
    $edition = $_POST['edition'];
    $url = $_POST['url'];
    $content_type = $_POST['content_type'];
    $media_type = $_POST['media_type'];
    $carrier_type = $_POST['carrier_type'];

    $success_count = 0;
    $error_messages = array();
    $isbn_index = 0;

    // Process each accession number and its copies
    for ($i = 0; $i < count($accessions); $i++) {
        $base_accession = $accessions[$i];
        $copies_for_this_accession = (int)$number_of_copies_array[$i];

        // Check if the accession range is available
        $conflict_found = false;
        for ($j = 0; $j < $copies_for_this_accession; $j++) {
            $current_accession = $base_accession + $j;
            $check_query = "SELECT * FROM books WHERE accession = '$current_accession'";
            $result = mysqli_query($conn, $check_query);
            if (mysqli_num_rows($result) > 0) {
                $error_messages[] = "Accession number $current_accession already exists.";
                $conflict_found = true;
                break;
            }
        }

        if ($conflict_found) {
            continue;
        }

        // Get existing copy numbers for this title
        $existing_copies_query = "SELECT copy_number FROM books WHERE title = '$title' ORDER BY copy_number";
        $existing_copies_result = mysqli_query($conn, $existing_copies_query);
        $taken_copy_numbers = array();
        while($row = mysqli_fetch_assoc($existing_copies_result)) {
            $taken_copy_numbers[] = (int)$row['copy_number'];
        }

        // Determine the starting copy number
        $current_copy = empty($taken_copy_numbers) ? 1 : max($taken_copy_numbers) + 1;

        // Insert copies for this accession
        for ($j = 0; $j < $copies_for_this_accession; $j++) {
            $current_accession = $base_accession + $j;

            // Find next available copy number
            while (in_array($current_copy, $taken_copy_numbers)) {
                $current_copy++;
            }

            // Get corresponding ISBN and call number
            $current_isbn = isset($isbns[$isbn_index]) ? $isbns[$isbn_index] : '';
            $current_call_number = isset($call_numbers[$isbn_index]) ? $call_numbers[$isbn_index] : '';
            $isbn_index++;

            $query = "INSERT INTO books (accession, title, preferred_title, parallel_title, front_image, back_image, 
                     height, width, total_pages, call_number, copy_number, language, shelf_location, entered_by, 
                     date_added, status, last_update, series, volume, edition, isbn, url, content_type, media_type, carrier_type) 
                     VALUES ('$current_accession', '$title', '$preferred_title', '$parallel_title', '$front_image', 
                     '$back_image', '$height', '$width', '$total_pages', '$current_call_number', '$current_copy', 
                     '$language', '$shelf_location', '$entered_by', '$date_added', '$status', '$last_update', '$series', 
                     '$volume', '$edition', '$current_isbn', '$url', '$content_type', '$media_type', '$carrier_type')";

            if (mysqli_query($conn, $query)) {
                $success_count++;
                $taken_copy_numbers[] = $current_copy;
            }
            
            $current_copy++;
        }
    }

    // Display results
    if (!empty($error_messages)) {
        echo "<script>alert('Errors occurred:\\n" . implode("\\n", $error_messages) . "');</script>";
    }
    if ($success_count > 0) {
        echo "<script>alert('Successfully added " . $success_count . " books in total!');</script>";
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
                                                        <option value="A1">A1</option>
                                                        <option value="B2">B2</option>
                                                        <option value="C3">C3</option>
                                                        <option value="D4">D4</option>
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
                                                        <option value="inshelf">In Shelf</option>
                                                        <option value="borrowed">Borrowed</option>
                                                        <option value="lost">Lost</option>
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
</script>
