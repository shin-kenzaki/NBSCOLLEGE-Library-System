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
    $accession = $_POST['accession'];
    $number_of_copies = (int)$_POST['number_of_copies'];
    $title = $_POST['title'];
    // Set copy_number to 1 if empty or invalid
    $copy_number = !empty($_POST['copy_number']) ? (int)$_POST['copy_number'] : 1;
    
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
    $isbn = $_POST['isbn'];
    $url = $_POST['url'];
    $content_type = $_POST['content_type'];
    $media_type = $_POST['media_type'];
    $carrier_type = $_POST['carrier_type'];

    // Check if the accession number already exists
    $check_query = "SELECT * FROM books WHERE accession = '$accession'";
    $result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($result) > 0) {
        $accession_error = "A book with the same accession number already exists.";
    } else {
        // Get all existing copy numbers for this title
        $existing_copies_query = "SELECT copy_number FROM books WHERE title = '$title' ORDER BY copy_number";
        $existing_copies_result = mysqli_query($conn, $existing_copies_query);
        $taken_copy_numbers = array();
        while($row = mysqli_fetch_assoc($existing_copies_result)) {
            $taken_copy_numbers[] = (int)$row['copy_number'];
        }

        // Determine the starting copy number
        if (!empty($_POST['copy_number'])) {
            $copy_number = (int)$_POST['copy_number'];
        } else {
            $copy_number = empty($taken_copy_numbers) ? 1 : max($taken_copy_numbers) + 1;
        }

        // Move uploaded files to the desired directory
        $front_image_filename = "";
        $back_image_filename = "";
        if($_FILES['front_image']['name']) {
            $front_image_filename = time() . '_' . $_FILES['front_image']['name'];
            move_uploaded_file($_FILES['front_image']['tmp_name'], "../uploads/" . $front_image_filename);
        }
        if($_FILES['back_image']['name']) {
            $back_image_filename = time() . '_' . $_FILES['back_image']['name'];
            move_uploaded_file($_FILES['back_image']['tmp_name'], "../uploads/" . $back_image_filename);
        }

        $success_count = 0;
        $assigned_copies = array();
        $current_copy = $copy_number;
        
        // Insert multiple copies while skipping taken numbers
        for ($i = 0; $i < $number_of_copies; $i++) {
            // Find next available copy number
            while (in_array($current_copy, $taken_copy_numbers)) {
                $current_copy++;
            }
            
            $current_accession = $accession + $i;
            $assigned_copies[] = $current_copy;
            
            $query = "INSERT INTO books (accession, title, preferred_title, parallel_title, front_image, back_image, 
                     height, width, total_pages, call_number, copy_number, language, shelf_location, entered_by, 
                     date_added, status, last_update, series, volume, edition, isbn, url, content_type, media_type, carrier_type) 
                     VALUES ('$current_accession', '$title', '$preferred_title', '$parallel_title', '$front_image_filename', 
                     '$back_image_filename', '$height', '$width', '$total_pages', '$call_number', '$current_copy', 
                     '$language', '$shelf_location', '$entered_by', '$date_added', '$status', '$last_update', '$series', 
                     '$volume', '$edition', '$isbn', '$url', '$content_type', '$media_type', '$carrier_type')";

            if (mysqli_query($conn, $query)) {
                $success_count++;
            }
            
            $current_copy++;
        }

        if ($success_count == $number_of_copies) {
            $copy_numbers_str = implode(", ", $assigned_copies);
            echo "<script>
                alert('Successfully added " . $success_count . " copies of the book!\\n" .
                "Accession numbers: " . $accession . " to " . ($accession + $number_of_copies - 1) . "\\n" .
                "Copy numbers: " . $copy_numbers_str . "');
            </script>";
        } else {
            echo "<script>alert('Error: Only " . $success_count . " out of " . $number_of_copies . " copies were added.');</script>";
        }
    }
}

?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
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
                                    <!--  -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Accession</label>
                                                <input type="text" class="form-control" name="accession" required>
                                                <?php if ($accession_error): ?>
                                                    <small class="text-danger"><?php echo $accession_error; ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Call Number</label>
                                                <input type="text" class="form-control" name="call_number">
                                            </div>
                                        </div>
                                    </div>
                                    <!--  -->

                                    <!--  -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Copy Number</label>
                                                <input type="number" class="form-control" name="copy_number" min="1" placeholder="Leave empty to start from 1">
                                                <small class="text-muted">If left empty, copy number will start from 1</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Language</label>
                                                <select class="form-control" name="language">
                                                    <option value="English">English</option>
                                                    <option value="Spanish">Spanish</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <!--  -->

                                    <!--  -->
                                    <div class="row">
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
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Entered By</label>
                                                <input type="text" class="form-control" name="entered_by" value="<?php echo $_SESSION['admin_id']; ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <!--  -->

                                    <!--  -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Date Added</label>
                                                <input type="text" class="form-control" name="date_added" value="<?php echo date('Y-m-d'); ?>" readonly>
                                            </div>
                                        </div>
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
                                    </div>
                                    <!--  -->

                                    <!--  -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Last Update</label>
                                                <input type="text" class="form-control" name="last_update" value="<?php echo date('Y-m-d'); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Number of Copies</label>
                                                <input type="number" class="form-control" name="number_of_copies" min="1" value="1" required>
                                                <small class="text-muted">This will create multiple entries with incremented accession and copy numbers</small>
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
                                                    <label>ISBN</label>
                                                    <input type="text" class="form-control" name="isbn">
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
</script>
