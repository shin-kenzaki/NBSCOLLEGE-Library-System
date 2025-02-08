<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';

// Fetch writers data
$sql = "SELECT id, firstname, middle_init, lastname FROM writers";
$result = $conn->query($sql);

if (!$result) {
    die("Error retrieving writers: " . $conn->error);
}

$sql1 = "SELECT id, company, place FROM publishers";
$result1 = $conn->query($sql1);
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <form id="bookForm" action="../Admin/inc/add-book-process.php" method="POST" enctype="multipart/form-data">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-2 text-gray-800">Book Management</h1>
                <button type="submit" class="btn btn-success">Add Book</button>
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
                                <input type="number" class="form-control" name="height">
                            </div>
                            <div class="form-group">
                                <label>Width (cm)</label>
                                <input type="number" class="form-control" name="width">
                            </div>
                            <div class="form-group">
                                <label>Total Pages</label>
                                <input type="number" class="form-control" name="total_pages">
                            </div>
                        </div>

                        <!-- Local Information Tab -->
                        <div class="tab-pane fade" id="local-info" role="tabpanel">
                            <h4>Local Information</h4>
                                    <!--  -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>ID</label>
                                                <input type="text" class="form-control" name="id" required>
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
                                                <input type="number" class="form-control" name="copy_number">
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



        </form>
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
