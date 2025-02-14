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
    $companies = $_POST['publisher'];
    $places = $_POST['place'];

    $success = true;
    $valid_entries = 0;
    $existing_combinations = [];

    for ($i = 0; $i < count($companies); $i++) {
        $publisher = trim($conn->real_escape_string($companies[$i]));
        $place = trim($conn->real_escape_string($places[$i]));

        // Skip entries without publisher name or place
        if (empty($publisher) || empty($place)) {
            continue;
        }

        // Check for duplicate entries within the current submission
        $combination = $publisher . '|' . $place;
        if (in_array($combination, $existing_combinations)) {
            $success = false;
            echo "<script>alert('Duplicate entry found: $publisher in $place');</script>";
            break;
        }
        $existing_combinations[] = $combination;

        // Check if the exact publisher and place combination already exists in database
        $checkSql = "SELECT * FROM publishers WHERE publisher = '$publisher' AND place = '$place'";
        $checkResult = $conn->query($checkSql);

        if ($checkResult->num_rows > 0) {
            $success = false;
            echo "<script>alert('This publisher already exists in this location: $publisher in $place');</script>";
            break;
        }

        $sql = "INSERT INTO publishers (publisher, place) VALUES ('$publisher', '$place')";
        if ($conn->query($sql)) {
            $valid_entries++;
        } else {
            $success = false;
            break;
        }
    }

    if ($success && $valid_entries > 0) {
        echo "<script>alert('$valid_entries publisher(s) saved successfully'); window.location.href='publisher_list.php';</script>";
    } elseif ($valid_entries === 0) {
        echo "<script>alert('No valid publishers to save. Please provide both publisher name and place.');</script>";
    } else {
        echo "<script>alert('Failed to save publishers');</script>";
    }
}

// Get the search query if it exists
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch publishers data
$sql = "SELECT id, publisher, place FROM publishers";
if (!empty($searchQuery)) {
    $sql .= " WHERE publisher LIKE '%$searchQuery%' OR place LIKE '%$searchQuery%'";
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
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Publisher</th>
                                <th>Place of Publication</th>
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
                                            <td>" . $row['publisher'] . "</td>
                                            <td>" . $row['place'] . "</td>
                                          </tr>";
                                }
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
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <input type="text" name="publisher[]" class="form-control mb-2" placeholder="Publisher" required>
                                    <input type="text" name="place[]" class="form-control mb-2" placeholder="Place" required>
                                </div>
                                <button type="button" class="btn btn-danger ml-2 remove-publisher" style="height: 38px;">×</button>
                            </div>
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
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "pageLength": 10,
        "responsive": true,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        },
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
            $('#dataTable_filter').addClass('d-flex align-items-center');
            $('#dataTable_filter label').append('<i class="fas fa-search ml-2"></i>');
            $('.dataTables_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
        }
    });

    // Add more publishers functionality
    $('#addMorePublishers').click(function() {
        var publisherEntry = `
            <div class="publisher-entry mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <input type="text" name="publisher[]" class="form-control mb-2" placeholder="Publisher" required>
                        <input type="text" name="place[]" class="form-control mb-2" placeholder="Place" required>
                    </div>
                    <button type="button" class="btn btn-danger ml-2 remove-publisher" style="height: 38px;">×</button>
                </div>
            </div>`;
        $('#publishersContainer').append(publisherEntry);
    });

    // Remove publisher functionality
    $(document).on('click', '.remove-publisher', function() {
        if ($('.publisher-entry').length > 1) {
            $(this).closest('.publisher-entry').remove();
        } else {
            alert('At least one publisher entry must remain.');
        }
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
        var publisher = row.find('td:nth-child(2)').text();
        var place = row.find('td:nth-child(3)').text();

        if (confirm(`Are you sure you want to delete this publisher?\n\nID: ${publisherId}\npublisher: ${publisher}\nPlace: ${place}`)) {
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

    // Update the save publishers functionality
    $('#savePublishers').click(function(e) {
        e.preventDefault();
        
        // Validate that at least one publisher has both name and place
        var hasValidPublisher = false;
        $('.publisher-entry').each(function() {
            var publisher = $(this).find('input[name="publisher[]"]').val().trim();
            var place = $(this).find('input[name="place[]"]').val().trim();
            if (publisher && place) {
                hasValidPublisher = true;
                return false; // break the loop
            }
        });

        if (!hasValidPublisher) {
            alert('Please provide at least one publisher with both name and place.');
            return;
        }

        // Submit the form
        $('#addPublishersForm').submit();
    });
});
</script>