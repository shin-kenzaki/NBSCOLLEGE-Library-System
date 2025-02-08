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

// Debug: Check for SQL errors
if (!$result) {
    die("Error retrieving writers: " . $conn->error);
}

$sql1 = "SELECT id, company, place FROM publishers";
$result1 = $conn->query($sql1);
?>


<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">Writers</h1>

        <!-- Content Row -->
        <div class="row">

            <div class="col-xl-12 col-lg-7">

                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs" id="formTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="book-details-tab" data-bs-toggle="tab" href="#book-details" role="tab" aria-controls="book-details" aria-selected="true">Book Details</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="contributors-tab" data-bs-toggle="tab" href="#contributors" role="tab" aria-controls="contributors" aria-selected="false">Writers</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="publisher-details-tab" data-bs-toggle="tab" href="#publisher-details" role="tab" aria-controls="publisher-details" aria-selected="false">Publisher Details</a>
                        </li>
                    </ul>
                    <BR>

                    <div class="tab-content" id="formTabsContent">
                        <!-- Tab 1: Book Details -->
                        <div class="tab-pane fade show active" id="book-details" role="tabpanel" aria-labelledby="book-details-tab">
                            <h4>Book Details</h4>
                            <form id="bookForm" action="../Admin/inc/add-book-process.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ID</label>
                                        <input type="text" name="id" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Preferred Title</label>
                                        <input type="text" name="preferred_title" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Parallel Title</label>
                                        <input type="text" name="parallel_title" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Front Image</label>
                                        <input type="file" name="front_image" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Back Image</label>
                                        <input type="file" name="back_image" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Content Type</label>
                                        <select name="content_type" class="form-control">
                                            <option value="text">Text</option>
                                            <option value="audio">Audio</option>
                                            <option value="video">Video</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Media Type</label>
                                        <select name="media_type" class="form-control">
                                            <option value="print">Print</option>
                                            <option value="digital">Digital</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Carrier Type</label>
                                        <select name="carrier_type" class="form-control">
                                            <option value="volume">Volume</option>
                                            <option value="disk">Disk</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Call Number</label>
                                        <input type="text" name="call_number" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>URL</label>
                                        <input type="text" name="URL" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Language</label>
                                        <select name="language" class="form-control">
                                            <option value="English">English</option>
                                            <option value="Spanish">Spanish</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Shelf Location</label>
                                        <select name="shelf_location" class="form-control">
                                            <option value="A1">A1</option>
                                            <option value="B2">B2</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Entered By</label>
                                        <input type="text" name="entered_by" class="form-control" value="<?php echo $_SESSION['admin_id']; ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Date Added</label>
                                        <input type="text" name="date_added" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="inshelf">In Shelf</option>
                                            <option value="borrowed">Borrowed</option>
                                            <option value="lost">Lost</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Last Update</label>
                                        <input type="text" name="last_update" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                                          <!-- Submit Button -->
                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-success" id="addBookBtn">Add Book</button>
                    </div>
                        </div>


                </form>

                      <!-- Tab 2: Contributors -->
                        <div class="tab-pane fade" id="contributors" role="tabpanel" aria-labelledby="contributors-tab">
                            <h4>Writers</h4>

                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Writers List</h6>
                                    <!-- Add Writer Button Inside Header -->
                                    <button class="btn btn-primary" data-toggle="modal" data-target="#addWriterModal">Add Writer</button>
                                </div>

                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>First Name</th>
                                                    <th>Middle Initial</th>
                                                    <th>Last Name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php

                                                    // Loop through the rows and display them in the table
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<tr>
                                                                <td>" . $row['id'] . "</td>
                                                                <td>" . $row['firstname'] . "</td>
                                                                <td>" . $row['middle_init'] . "</td>
                                                                <td>" . $row['lastname'] . "</td>
                                                            </tr>";
                                                    }

                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                             <!-- END Tab 2: Contributors -->


                        <!-- Tab 3: Publisher Details -->
                        <div class="tab-pane fade" id="publisher-details" role="tabpanel" aria-labelledby="publisher-details-tab">
                            <h4>Publisher Details</h4>


                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Publisher Details</h6>
                                    <!-- Add Writer Button Inside Header -->
                                    <button class="btn btn-primary" data-toggle="modal" data-target="#addPublisherModal">Add Publisher</button>
                                </div>
                            <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable1" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Company</th>
                                                    <th>Place</th>

                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php

                                                    // Loop through the rows and display them in the table
                                                    while ($row1 = $result1->fetch_assoc()) {
                                                        echo "<tr>
                                                                <td>" . $row1['id'] . "</td>
                                                                <td>" . $row1['company'] . "</td>
                                                                <td>" . $row1['place'] . "</td>

                                                            </tr>";
                                                    }

                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>



                    </div>
            </div>
        </div>

    </div>
</div>

<!-- Footer -->
<?php include '../admin/inc/footer.php'; ?>

</div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->
</div>


    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

<!-- Add Publisher Modal -->
<div class="modal fade" id="addPublisherModal" tabindex="-1" role="dialog" aria-labelledby="addPublisherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPublisherModalLabel">Add Publisher</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addPublisherForm">
                    <div class="form-group">

                        <div id="publisherFields">
                            <!-- Initial Publisher Row with Company and Place Fields -->
                            <div class="row publisher-row align-items-center mb-2">
                                <div class="col-md-6">
                                    <label>Company</label>
                                    <input type="text" name="publisher_company[]" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Place</label>
                                    <input type="text" name="publisher_place[]" class="form-control" required>
                                    <button type="button" id="addPublisherRowBtn" class="btn btn-success btn-sm position-absolute delete-publisher-field"
                                                style="right: 15px; top: 72%; transform: translateY(-50%);">
                                    <i class="fas fa-plus"></i>
                                </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Add Publishers</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Writer Modal -->
<div class="modal fade" id="addWriterModal" tabindex="-1" role="dialog" aria-labelledby="addWriterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addWriterModalLabel">Add Writer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addWriterForm">
                    <div class="form-group">
                        <div class="writer-fields-container" id="writerFields">
                            <!-- Initial Row with Fields -->
                            <div class="row writer-row">
                                <div class="col-md-4">
                                    <label>First Name</label>
                                    <input type="text" name="firstname[]" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label>Middle Initial</label>
                                    <input type="text" name="middle_init[]" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label>Last Name</label>
                                    <div class="position-relative">
                                        <input type="text" name="lastname[]" class="form-control pr-5" required>
                                        <!-- Add Button -->
                                        <button type="button" class="btn btn-success btn-sm position-absolute add-row"
                                            style="right: 5px; top: 50%; transform: translateY(-50%);">
                                            <i class="fas fa-plus"></i>
                                        </button>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Add Writer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
    const publisherFields = document.getElementById('publisherFields'); // Container for publisher fields

    // Function to add new publisher row dynamically
    function addPublisherRow() {
        const newRow = document.createElement('div');
        newRow.classList.add('row', 'publisher-row', 'align-items-center', 'mb-2');

        newRow.innerHTML = `
            <div class="col-md-6">
                <label>Company</label>
                <input type="text" name="publisher_company[]" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label>Place</label>
                <input type="text" name="publisher_place[]" class="form-control" required>
                <button type="button" class="btn btn-danger btn-sm position-absolute delete-publisher-field"
                      style="right: 15px; top: 72%; transform: translateY(-50%);">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

        publisherFields.appendChild(newRow);

        // Add event listener for delete button to remove row
        newRow.querySelector('.delete-publisher-field').addEventListener('click', function() {
            newRow.remove();
        });
    }

    // Event listener for the "Add Publisher" button (to add new fields)
    document.querySelector('#addPublisherRowBtn').addEventListener('click', function() {
        addPublisherRow();
    });

    // Writer Modal Functionality
    const writerFields = document.getElementById('writerFields');

    function addWriterRow() {
        const newRow = document.createElement('div');
        newRow.classList.add('row', 'writer-row', 'align-items-center', 'mb-2');

        newRow.innerHTML = `
            <div class="col-md-4">
                <label>First Name</label>
                <input type="text" name="firstname[]" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label>Middle Initial</label>
                <input type="text" name="middle_init[]" class="form-control">
            </div>
            <div class="col-md-4">
                <label>Last Name</label>
                <div class="position-relative">
                    <input type="text" name="lastname[]" class="form-control pr-5" required>
                    <!-- Trash Icon -->
                    <button type="button" class="btn btn-danger btn-sm position-absolute delete-field"
                        style="right: 5px; top: 50%; transform: translateY(-50%);">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;

        writerFields.appendChild(newRow);

        // Add event listener for delete button
        newRow.querySelector('.delete-field').addEventListener('click', function() {
            newRow.remove();
        });

        // Add event listener for "+" button
        newRow.querySelector('.add-row').addEventListener('click', function() {
            addWriterRow();
        });
    }

    // Add event listener to the initial "+" button
    document.querySelector('.add-row').addEventListener('click', function() {
        addWriterRow();
    });
});




</script>

<!-- TABLE SCRIPT -->

<script>
    $(document).ready(function () {
        var table = $('#dataTable').DataTable({
            "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
            "pagingType": "simple_numbers",
            "language": {
                "search": "Search:" // Keeps the default 'Search:' label
            }
        });

        // Style the search input with Bootstrap
        $('#dataTable_filter input')
            .addClass('form-control')
            .attr("placeholder", "Search...");

        // Add a trailing search icon inside the search field
        $('#dataTable_filter input').wrap('<div class="input-group"></div>');  // Wrap input field with input group
        $('#dataTable_filter').append('<div class="input-group-append"><span class="input-group-text"><i class="fa fa-search"></i></span></div>');

        // Add a label next to the search field (without removing the default "Search:" label)
        $('#dataTable_filter').append('<label class="ml-2 font-weight-bold">Search</label>');

        // Fix pagination buttons styling & spacing
        $('.dataTables_paginate .paginate_button')
            .addClass('btn btn-sm btn-outline-primary mx-1');
    });
</script>

<script>
    $(document).ready(function () {
        var table = $('#dataTable1').DataTable({
            "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
            "pagingType": "simple_numbers",
            "language": {
                "search": "Search:" // Keeps the default 'Search:' label
            }
        });

        // Style the search input with Bootstrap
        $('#dataTable_filter input')
            .addClass('form-control')
            .attr("placeholder", "Search...");

        // Add a trailing search icon inside the search field
        $('#dataTable_filter input').wrap('<div class="input-group"></div>');  // Wrap input field with input group
        $('#dataTable_filter').append('<div class="input-group-append"><span class="input-group-text"><i class="fa fa-search"></i></span></div>');

        // Add a label next to the search field (without removing the default "Search:" label)
        $('#dataTable_filter').append('<label class="ml-2 font-weight-bold">Search</label>');

        // Fix pagination buttons styling & spacing
        $('.dataTables_paginate .paginate_button')
            .addClass('btn btn-sm btn-outline-primary mx-1');
    });
</script>





    <script src="inc/js/form.js"></script>

    <script src="inc/js/demo/chart-area-demo.js"></script>
    <script src="inc/js/demo/chart-pie-demo.js"></script>
    <script src="inc/js/demo/chart-bar-demo.js"></script>