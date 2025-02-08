<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php'; // Database connection
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-4 text-gray-800">Book List</h1>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Book List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Preferred Title</th>
                                <th>Parallel Title</th>
                                <th>Front Image</th>
                                <th>Back Image</th>
                                <th>Height</th>
                                <th>Width</th>
                                <th>Series</th>
                                <th>Volume</th>
                                <th>Edition</th>
                                <th>Copy Number</th>
                                <th>Total Pages</th>
                                <th>ISBN</th>
                                <th>Content Type</th>
                                <th>Media Type</th>
                                <th>Carrier Type</th>
                                <th>URL</th>
                                <th>Language</th>
                                <th>Shelf Location</th>
                                <th>Entered By</th>
                                <th>Date Added</th>
                                <th>Status</th>
                                <th>Last Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch books from database
                            $query = "SELECT * FROM books ORDER BY id DESC";
                            $result = $conn->query($query);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$row['id']}</td>
                                        <td>{$row['title']}</td>
                                        <td>{$row['preferred_title']}</td>
                                        <td>{$row['parallel_title']}</td>
                                        <td>";
                                    if (!empty($row['front_image'])) {
                                        echo "<img src='../uploads/books/{$row['front_image']}' alt='Front Image' width='50'>";
                                    } else {
                                        echo "No Image";
                                    }
                                    echo "</td>
                                        <td>";
                                    if (!empty($row['back_image'])) {
                                        echo "<img src='../uploads/books/{$row['back_image']}' alt='Back Image' width='50'>";
                                    } else {
                                        echo "No Image";
                                    }
                                    echo "</td>
                                        <td>{$row['height']}</td>
                                        <td>{$row['width']}</td>
                                        <td>{$row['series']}</td>
                                        <td>{$row['volume']}</td>
                                        <td>{$row['edition']}</td>
                                        <td>{$row['copy_number']}</td>
                                        <td>{$row['total_pages']}</td>
                                        <td>{$row['ISBN']}</td>
                                        <td>{$row['content_type']}</td>
                                        <td>{$row['media_type']}</td>
                                        <td>{$row['carrier_type']}</td>
                                        <td>";
                                    echo !empty($row['URL']) ? "<a href='{$row['URL']}' target='_blank'>View</a>" : "N/A";
                                    echo "</td>
                                        <td>{$row['language']}</td>
                                        <td>{$row['shelf_location']}</td>
                                        <td>{$row['entered_by']}</td>
                                        <td>{$row['date_added']}</td>
                                        <td>{$row['status']}</td>
                                        <td>{$row['last_update']}</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='24' class='text-center'>No books found</td></tr>";
                            }
                            ?>
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
<?php include '../Admin/inc/footer.php' ?>
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
            "search": "Search:"
        }
    });

    $('#dataTable_filter input')
        .addClass('form-control')
        .attr("placeholder", "Search...");

    $('#dataTable_filter input').wrap('<div class="input-group"></div>');
    $('#dataTable_filter').append('<div class="input-group-append"><span class="input-group-text"><i class="fa fa-search"></i></span></div>');
    $('#dataTable_filter').append('<label class="ml-2 font-weight-bold">Search</label>');

    $('.dataTables_paginate .paginate_button')
        .addClass('btn btn-sm btn-outline-primary mx-1');
});
</script>
