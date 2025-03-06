<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
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
    $valid_entries = 0;

    for ($i = 0; $i < count($firstnames); $i++) {
        $firstname = trim($conn->real_escape_string($firstnames[$i]));
        $middle_init = trim($conn->real_escape_string($middle_inits[$i]));
        $lastname = trim($conn->real_escape_string($lastnames[$i]));

        // Skip entries without firstname or lastname
        if (empty($firstname) || empty($lastname)) {
            continue;
        }

        // Check if the full name already exists
        $checkSql = "SELECT * FROM writers WHERE firstname = '$firstname' AND middle_init = '$middle_init' AND lastname = '$lastname'";
        $checkResult = $conn->query($checkSql);

        if ($checkResult->num_rows > 0) {
            $success = false;
            echo "<script>alert('The writer already exists: $firstname $middle_init $lastname');</script>";
            break;
        }

        $sql = "INSERT INTO writers (firstname, middle_init, lastname) VALUES ('$firstname', '$middle_init', '$lastname')";
        if ($conn->query($sql)) {
            $valid_entries++;
        } else {
            $success = false;
            break;
        }
    }

    if ($success && $valid_entries > 0) {
        echo "<script>alert('$valid_entries writer(s) saved successfully'); window.location.href='writers_list.php';</script>";
    } elseif ($valid_entries === 0) {
        echo "<script>alert('No valid writers to save. Please provide both firstname and lastname.');</script>";
    } else {
        echo "<script>alert('Failed to save writers');</script>";
    }
}

// Get the search query if it exists
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Get selected writer IDs from session if they exist
$selectedWriterIds = isset($_SESSION['selectedWriterIds']) ? $_SESSION['selectedWriterIds'] : [];

// Modify the SQL query to handle selected writers first
$sql = "SELECT id, firstname, middle_init, lastname FROM writers";
if (!empty($searchQuery)) {
    $sql .= " WHERE firstname LIKE '%$searchQuery%' OR middle_init LIKE '%$searchQuery%' OR lastname LIKE '%$searchQuery%'";
}
$sql .= " ORDER BY CASE WHEN id IN (" . 
        (!empty($selectedWriterIds) ? implode(',', array_map('intval', $selectedWriterIds)) : "0") . 
        ") THEN 0 ELSE 1 END, id DESC";

$result = $conn->query($sql);
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Writers List</h6>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addWriterModal">Add Writer</button>
            </div>
            <div class="card-body px-0"> <!-- Remove padding for full-width scroll -->
                <div class="table-responsive px-3"> <!-- Add padding inside scroll container -->
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center">ID</th>
                                <th class="text-center">First Name</th>
                                <th class="text-center">Middle Initial</th>
                                <th class="text-center">Last Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Check if the query returned any rows
                            if ($result->num_rows > 0) {
                                // Loop through the rows and display them in the table
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td style='text-align: center;'>{$row['id']}</td>
                                            <td style='text-align: center;'>{$row['firstname']}</td>
                                            <td style='text-align: center;'>{$row['middle_init']}</td>
                                            <td style='text-align: center;'>{$row['lastname']}</td>
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
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <input type="text" name="firstname[]" class="form-control mb-2" placeholder="First Name" required>
                                    <input type="text" name="middle_init[]" class="form-control mb-2" placeholder="Middle Initial">
                                    <input type="text" name="lastname[]" class="form-control mb-2" placeholder="Last Name" required>
                                </div>
                                <button type="button" class="btn btn-danger ml-2 remove-writer" style="height: 38px;">×</button>
                            </div>
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

<!-- Update Writer Modal -->
<div class="modal fade" id="updateWriterModal" tabindex="-1" role="dialog" aria-labelledby="updateWriterModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateWriterModalLabel">Update Writer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="updateWriterForm" method="POST" action="update_writer.php">
                    <input type="hidden" name="writer_id" id="updateWriterId">
                    <div class="form-group">
                        <label for="updateFirstName">First Name</label>
                        <input type="text" class="form-control" name="firstname" id="updateFirstName" required>
                    </div>
                    <div class="form-group">
                        <label for="updateMiddleInit">Middle Initial</label>
                        <input type="text" class="form-control" name="middle_init" id="updateMiddleInit">
                    </div>
                    <div class="form-group">
                        <label for="updateLastName">Last Name</label>
                        <input type="text" class="form-control" name="lastname" id="updateLastName" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveUpdatedWriter">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Context Menu -->
<div id="contextMenu" class="dropdown-menu" style="display:none; position:absolute;">
    <a class="dropdown-item" href="#" id="updateWriter">Update</a>
    <a class="dropdown-item" href="#" id="deleteWriter">Delete</a>
</div>

<!-- Footer -->
<?php include '../Admin/inc/footer.php' ?>
<!-- End of Footer -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<style>
    /* Add these styles in the head section */
    .table-responsive {
        width: 100%;
        margin-bottom: 1rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Ensure minimum width for table columns */
    #dataTable th,
    #dataTable td {
        min-width: 100px;
        white-space: nowrap;
    }
    
    /* Make the table stretch full width */
    #dataTable {
        width: 100% !important;
    }
    
    /* Prevent text wrapping in cells */
    .table td, .table th {
        white-space: nowrap;
    }
</style>

<script>
$(document).ready(function () {
    var table = $('#dataTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "pageLength": 10,
        "responsive": false, // Disable DataTables responsive handling
        "scrollX": true, // Enable horizontal scrolling
        "order": [[1, "asc"]], // Sort by First Name by default
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

    // Add more writers functionality
    $('#addMoreWriters').click(function() {
        var writerEntry = `
            <div class="writer-entry mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <input type="text" name="firstname[]" class="form-control mb-2" placeholder="First Name" required>
                        <input type="text" name="middle_init[]" class="form-control mb-2" placeholder="Middle Initial">
                        <input type="text" name="lastname[]" class="form-control mb-2" placeholder="Last Name" required>
                    </div>
                    <button type="button" class="btn btn-danger ml-2 remove-writer" style="height: 38px;">×</button>
                </div>
            </div>`;
        $('#writersContainer').append(writerEntry);
    });

    // Remove writer functionality
    $(document).on('click', '.remove-writer', function() {
        if ($('.writer-entry').length > 1) {
            $(this).closest('.writer-entry').remove();
        } else {
            alert('At least one writer entry must remain.');
        }
    });

    var selectedWriterId;

    // Show context menu on right-click
    $('#dataTable tbody').on('contextmenu', 'tr', function(e) {
        e.preventDefault();
        $('#dataTable tbody tr').removeClass('context-menu-active');
        $(this).addClass('context-menu-active');
        selectedWriterId = $(this).find('td:nth-child(1)').text();
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
    $('#updateWriter').click(function() {
        console.log('Update writer clicked');
        window.location.href = `update_writer.php?writer_id=${selectedWriterId}`;
    });

    $('#deleteWriter').click(function() {
        var row = $('#dataTable tbody tr.context-menu-active');
        var writerId = row.find('td:nth-child(1)').text();
        var firstName = row.find('td:nth-child(2)').text();
        var lastName = row.find('td:nth-child(4)').text();

        if (confirm(`Are you sure you want to delete this writer?\n\nID: ${writerId}\nName: ${firstName} ${lastName}`)) {
            $.ajax({
                url: 'delete_writer.php',
                type: 'POST',
                data: { writer_id: writerId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        row.remove();
                        $('#contextMenu').hide();
                        alert(response.message);
                    } else {
                        alert(response.message || 'Error deleting writer');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete request failed:', error);
                    console.error('Server response:', xhr.responseText);
                    alert('An error occurred while trying to delete the writer. Please check the console for details.');
                }
            });
        }
    });

    // Save updated writer functionality
    $('#saveUpdatedWriter').click(function() {
        $('#updateWriterForm').submit();
    });

    // Display success message if available
    var successMessage = "<?php echo isset($_SESSION['success_message']) ? $_SESSION['success_message'] : ''; ?>";
    if (successMessage) {
        alert(successMessage);
        <?php unset($_SESSION['success_message']); ?>
    }

    // Update the save writers functionality
    $('#saveWriters').click(function(e) {
        e.preventDefault();
        
        // Validate that at least one writer has both firstname and lastname
        var hasValidWriter = false;
        $('.writer-entry').each(function() {
            var firstname = $(this).find('input[name="firstname[]"]').val().trim();
            var lastname = $(this).find('input[name="lastname[]"]').val().trim();
            if (firstname && lastname) {
                hasValidWriter = true;
                return false; // break the loop
            }
        });

        if (!hasValidWriter) {
            alert('Please provide at least one writer with both firstname and lastname.');
            return;
        }

        // Submit the form
        $('#addWritersForm').submit();
    });

    // Adjust table columns on window resize
    $(window).on('resize', function () {
        table.columns.adjust();
    });
});
</script>