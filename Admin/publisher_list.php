<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php';

// Handle form submission to save publishers
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $companies = $_POST['company'];
    $places = $_POST['place'];

    $success = true;

    for ($i = 0; $i < count($companies); $i++) {
        $company = $conn->real_escape_string($companies[$i]);
        $place = $conn->real_escape_string($places[$i]);

        // Check if the company and place combination already exists
        $checkSql = "SELECT * FROM publishers WHERE company = '$company' AND place = '$place'";
        $checkResult = $conn->query($checkSql);

        if ($checkResult->num_rows > 0) {
            $success = false;
            echo "<script>alert('The combination of company and place already exists: $company, $place');</script>";
            break;
        }

        $sql = "INSERT INTO publishers (company, place) VALUES ('$company', '$place')";
        if (!$conn->query($sql)) {
            $success = false;
            break;
        }
    }

    if ($success) {
        echo "<script>alert('Publishers saved successfully'); window.location.href='publisher_list.php';</script>";
    } else {
        echo "<script>alert('Failed to save publishers');</script>";
    }
}

// Get the search query if it exists
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch publishers data
$sql = "SELECT id, company, place FROM publishers";
if (!empty($searchQuery)) {
    $sql .= " WHERE company LIKE '%$searchQuery%' OR place LIKE '%$searchQuery%'";
}
$result = $conn->query($sql);
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Publishers List</h6>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addPublisherModal">Add Publisher</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <!-- Search Form -->
                    <form method="GET" action="publisher_list.php" id="searchForm">
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
                                <th>Company</th>
                                <th>Place</th>
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
                                            <td>" . $row['company'] . "</td>
                                            <td>" . $row['place'] . "</td>
                                          </tr>";
                                }
                            } else {
                                // If no data is found, display a message
                                echo "<tr><td colspan='3'>No publishers found</td></tr>";
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

<!-- Add Publisher Modal -->
<div class="modal fade" id="addPublisherModal" tabindex="-1" role="dialog" aria-labelledby="addPublisherModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPublisherModalLabel">Add Publishers</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addPublishersForm" method="POST" action="publisher_list.php">
                    <div id="publishersContainer">
                        <div class="publisher-entry mb-3">
                            <input type="text" name="company[]" class="form-control mb-2" placeholder="Company" required>
                            <input type="text" name="place[]" class="form-control mb-2" placeholder="Place" required>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" id="addMorePublishers">Add More Publishers</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="savePublishers">Save Publishers</button>
            </div>
        </div>
    </div>
</div>

<!-- Context Menu -->
<div id="contextMenu" class="dropdown-menu" style="display:none; position:absolute;">
    <a class="dropdown-item" href="#" id="updatePublisher">Update</a>
    <a class="dropdown-item" href="#" id="deletePublisher">Delete</a>
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
            { "data": "company" },
            { "data": "place" }
        ]
    });

    // Remove the search input field
    $('#dataTable_filter').remove();

    // Fix pagination buttons styling & spacing
    $('.dataTables_paginate .paginate_button')
        .addClass('btn btn-sm btn-outline-primary mx-1');

    // Add more publishers functionality
    $('#addMorePublishers').click(function() {
        var publisherEntry = `
            <div class="publisher-entry mb-3">
                <input type="text" name="company[]" class="form-control mb-2" placeholder="Company" required>
                <input type="text" name="place[]" class="form-control mb-2" placeholder="Place" required>
            </div>`;
        $('#publishersContainer').append(publisherEntry);
    });

    // Save publishers functionality
    $('#savePublishers').click(function() {
        $('#addPublishersForm').submit();
    });

    // Handle search form submission
    $('form').submit(function(event) {
        event.preventDefault();
        var searchQuery = $('input[name="search"]').val();

        $.ajax({
            url: 'fetch_publishers.php',
            type: 'GET',
            data: {
                search: searchQuery
            },
            success: function(response) {
                $('#dataTable tbody').html(response);
            }
        });
    });

    var selectedPublisherId;

    // Show context menu on right-click
    $('#dataTable tbody').on('contextmenu', 'tr', function(e) {
        e.preventDefault();
        $('#dataTable tbody tr').removeClass('context-menu-active');
        $(this).addClass('context-menu-active');
        selectedPublisherId = $(this).find('td:nth-child(1)').text();
        $('#contextMenu').css({
            display: 'block',
            left: e.pageX,
            top: e.pageY
        });
        return false;
    });

    // Hide context menu on click outside
    $(document).click(function() {
        $('#contextMenu').hide();
    });

    // Handle context menu actions
    $('#updatePublisher').click(function() {
        console.log('Update publisher clicked');
        window.location.href = `update_publisher.php?publisher_id=${selectedPublisherId}`;
    });

    $('#deletePublisher').click(function() {
        var row = $('#dataTable tbody tr.context-menu-active');
        var publisherId = row.find('td:nth-child(1)').text();
        var company = row.find('td:nth-child(2)').text();
        var place = row.find('td:nth-child(3)').text();

        if (confirm(`Are you sure you want to delete this publisher?\n\nID: ${publisherId}\nCompany: ${company}\nPlace: ${place}`)) {
            $.post('delete_publisher.php', { publisher_id: publisherId }, function(response) {
                alert(response.message);
                location.reload();
            }, 'json');
        }
    });

    // Save updated publisher functionality
    $('#saveUpdatedPublisher').click(function() {
        $('#updatePublisherForm').submit();
    });

    // Display success message if available
    var successMessage = "<?php echo isset($_SESSION['success_message']) ? $_SESSION['success_message'] : ''; ?>";
    if (successMessage) {
        alert(successMessage);
        <?php unset($_SESSION['success_message']); ?>
    }
});
</script>