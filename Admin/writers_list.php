<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';

// Handle form submission to save writers
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstnames = $_POST['firstname'];
    $middle_inits = $_POST['middle_init'];
    $lastnames = $_POST['lastname'];

    $success = true;

    for ($i = 0; $i < count($firstnames); $i++) {
        $firstname = $conn->real_escape_string($firstnames[$i]);
        $middle_init = $conn->real_escape_string($middle_inits[$i]);
        $lastname = $conn->real_escape_string($lastnames[$i]);

        // Check if the full name already exists
        $checkSql = "SELECT * FROM writers WHERE firstname = '$firstname' AND middle_init = '$middle_init' AND lastname = '$lastname'";
        $checkResult = $conn->query($checkSql);

        if ($checkResult->num_rows > 0) {
            $success = false;
            echo "<script>alert('The writer already exists: $firstname $middle_init $lastname');</script>";
            break;
        }

        $sql = "INSERT INTO writers (firstname, middle_init, lastname) VALUES ('$firstname', '$middle_init', '$lastname')";
        if (!$conn->query($sql)) {
            $success = false;
            break;
        }
    }

    if ($success) {
        echo "<script>alert('Writers saved successfully'); window.location.href='writers_list.php';</script>";
    } else {
        echo "<script>alert('Failed to save writers');</script>";
    }
}

// Get the search query if it exists
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch writers data
$sql = "SELECT id, firstname, middle_init, lastname FROM writers";
if (!empty($searchQuery)) {
    $sql .= " WHERE firstname LIKE '%$searchQuery%' OR middle_init LIKE '%$searchQuery%' OR lastname LIKE '%$searchQuery%'";
}
$result = $conn->query($sql);
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Writers List</h6>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addWriterModal">Add Writer</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <!-- Search Form -->
                    <form method="GET" action="writers_list.php" id="searchForm">
                        <div class="input-group mb-3">
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </div>
                    </form>
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
                            // Check if the query returned any rows
                            if ($result->num_rows > 0) {
                                // Loop through the rows and display them in the table
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>" . $row['id'] . "</td>
                                            <td>" . $row['firstname'] . "</td>
                                            <td>" . $row['middle_init'] . "</td>
                                            <td>" . $row['lastname'] . "</td>
                                          </tr>";
                                }
                            } else {
                                // If no data is found, display a message
                                echo "<tr><td colspan='4'>No writers found</td></tr>";
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

<!-- Add Writer Modal -->
<div class="modal fade" id="addWriterModal" tabindex="-1" role="dialog" aria-labelledby="addWriterModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addWriterModalLabel">Add Writers</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addWritersForm" method="POST" action="writers_list.php">
                    <div id="writersContainer">
                        <div class="writer-entry mb-3">
                            <input type="text" name="firstname[]" class="form-control mb-2" placeholder="First Name" required>
                            <input type="text" name="middle_init[]" class="form-control mb-2" placeholder="Middle Initial">
                            <input type="text" name="lastname[]" class="form-control mb-2" placeholder="Last Name" required>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" id="addMoreWriters">Add More Writers</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveWriters">Save Writers</button>
            </div>
        </div>
    </div>
</div>

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
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "pagingType": "simple_numbers",
        "language": {
            "search": "" // Removes the default 'Search:' label
        },
        "columns": [
            { "data": "id" },
            { "data": "firstname" },
            { "data": "middle_init" },
            { "data": "lastname" }
        ]
    });

    // Remove the search input field
    $('#dataTable_filter').remove();

    // Fix pagination buttons styling & spacing
    $('.dataTables_paginate .paginate_button')
        .addClass('btn btn-sm btn-outline-primary mx-1');

    // Add more writers functionality
    $('#addMoreWriters').click(function() {
        var writerEntry = `
            <div class="writer-entry mb-3">
                <input type="text" name="firstname[]" class="form-control mb-2" placeholder="First Name" required>
                <input type="text" name="middle_init[]" class="form-control mb-2" placeholder="Middle Initial">
                <input type="text" name="lastname[]" class="form-control mb-2" placeholder="Last Name" required>
            </div>`;
        $('#writersContainer').append(writerEntry);
    });

    // Save writers functionality
    $('#saveWriters').click(function() {
        $('#addWritersForm').submit();
    });

    // Handle search form submission
    $('form#searchForm').submit(function(event) {
        event.preventDefault();
        var searchQuery = $('input[name="search"]').val();

        $.ajax({
            url: 'fetch_writers.php',
            type: 'GET',
            data: {
                search: searchQuery
            },
            success: function(response) {
                $('#dataTable tbody').html(response);
            }
        });
    });
});
</script>