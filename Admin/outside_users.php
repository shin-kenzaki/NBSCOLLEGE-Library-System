<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';

?>


            <!-- Main Content -->
            <div id="content" class="d-flex flex-column min-vh-100">
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Outside Users</h1>

                    <div class="card shadow mb-4">
        <div class="card-header py-3">

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

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include '../Admin/inc/footer.php'?>
            <!-- End of Footer -->



    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>


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