<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}
include '../admin/inc/header.php';
?>

            <!-- Main Content -->
            <div id="content">


                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">Add Book</h1>
                    <p class="mb-4">Maintenance</p>

                    <!-- Content Row -->
                    <div class="row">


                        <div class="col-xl-12 col-lg-7">
                                <form id="bookForm" action="process_add_book.php" method="POST" enctype="multipart/form-data">
                                <div class="form-step step-1">
    <h4>Book Details</h4>

    <!-- Row for fields in pairs -->
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
                        <input type="text" name="entered_by" class="form-control" value="<?php echo $_SESSION['userId']; ?>" readonly>
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

            <button type="button" class="btn btn-primary next-step">Next</button>
        </div>



                    
                    <div class="form-step step-2 d-none">

                    <h1 class="h3 mb-2 text-gray-800">Contributors</h1>
        <p class="mb-4">Manage book writers and contributors.</p>
        
        <!-- Search Bar -->
        <div class="mb-3">
            <input type="text" id="searchWriter" class="form-control" placeholder="Search writer...">
        </div>
        
        <!-- Add Writer Button -->
        <div class="mb-4">
            <button class="btn btn-primary" data-toggle="modal" data-target="#addWriterModal">Add Writer</button>
        </div>

        <!-- Writers Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Middle Initial</th>
                    <th>Last Name</th>
                </tr>
            </thead>
            <tbody id="writersList">
                <!-- Writer rows will be dynamically inserted here -->
            </tbody>
        </table>
        <button type="button" class="btn btn-secondary prev-step">Previous</button>
        <button type="button" class="btn btn-primary next-step">Next</button>

        
        
                        <!-- Add Writer Modal -->
<div class="modal fade" id="addWriterModal" tabindex="-1" role="dialog" aria-labelledby="addWriterModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
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
                        <label>First Name</label>
                        <input type="text" name="firstname" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" name="middle_init" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lastname" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success">Add</button>
                </form>
  
            </div>
        </div>
    </div>
</div>


                    <div class="form-step step-3 d-none">
                        <h4>Publisher Details</h4>
                        <div class="form-group">
                            <label>Company</label>
                            <input type="text" name="company" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Place</label>
                            <input type="text" name="place" class="form-control">
                        </div>
                        <button type="button" class="btn btn-secondary prev-step">Previous</button>
                        <button type="submit" class="btn btn-success">Add Book</button>
                    </div>
                </form>
                <!-- END OF FORM -->

                                

                        </div>

                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include '../admin/inc/footer.php'; ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>


    <script src="inc/js/form.js"></script>