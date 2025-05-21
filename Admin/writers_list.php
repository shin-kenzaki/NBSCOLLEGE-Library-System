<?php
ob_start(); // Start output buffering to prevent "headers already sent" errors
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection first
include '../db.php';
include '../admin/inc/header.php';

// Initialize selected writers array in session if not exists
if (!isset($_SESSION['selectedWriterIds'])) {
    $_SESSION['selectedWriterIds'] = [];
}

// Handle AJAX request to update selected writers
if (isset($_POST['action']) && $_POST['action'] == 'updateSelectedWriters') {
    $_SESSION['selectedWriterIds'] = isset($_POST['selectedIds']) ? $_POST['selectedIds'] : [];
    echo json_encode(['success' => true, 'count' => count($_SESSION['selectedWriterIds'])]);
    exit;
}

// Handle AJAX request for deleting a writer
if (isset($_POST['action']) && $_POST['action'] === 'deleteWriter') {
    $writerId = intval($_POST['writer_id']);
    $conn->begin_transaction();
    try {
        // Delete associated individual contributors
        $deleteContributorsSql = "DELETE FROM contributors WHERE writer_id = $writerId";
        $conn->query($deleteContributorsSql);

        // Delete the writer
        $deleteWriterSql = "DELETE FROM writers WHERE id = $writerId";
        $conn->query($deleteWriterSql);

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle bulk delete button click
if (isset($_POST['action']) && $_POST['action'] === 'bulkDelete') {
    $selectedIds = isset($_POST['selected_ids']) ? array_map('intval', $_POST['selected_ids']) : [];

    if (empty($selectedIds)) {
        $_SESSION['error_message'] = "No writers selected for deletion.";
    } else {
        $conn->begin_transaction();
        try {
            foreach ($selectedIds as $id) {
                // Delete associated individual contributors
                $deleteContributorsSql = "DELETE FROM contributors WHERE writer_id = $id";
                $conn->query($deleteContributorsSql);

                // Delete the writer
                $deleteWriterSql = "DELETE FROM writers WHERE id = $id";
                $conn->query($deleteWriterSql);
            }
            $conn->commit();
            $_SESSION['success_message'] = count($selectedIds) . " writer(s) deleted successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error deleting writers: " . $e->getMessage();
        }
    }

    // Redirect to refresh the page
    header("Location: writers_list.php");
    exit;
}

// Count total writers
$totalWritersQuery = "SELECT COUNT(*) as total FROM writers";
$totalWritersResult = $conn->query($totalWritersQuery);
$totalWriters = $totalWritersResult->fetch_assoc()['total'];

// Handle form submission to save writers
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['firstname'])) { // Check if it's the add writer form
    $firstnames = $_POST['firstname'];
    $middle_inits = $_POST['middle_init'];
    $lastnames = $_POST['lastname'];

    $success = true;
    $valid_entries = 0;
    $error_message = '';
    $duplicate_message = '';
    $added_writers_names = []; // Array to store names of added writers

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
            $duplicate_message = "The writer '$firstname $middle_init $lastname' already exists.";
            break; // Stop processing if a duplicate is found
        }

        $sql = "INSERT INTO writers (firstname, middle_init, lastname) VALUES ('$firstname', '$middle_init', '$lastname')";
        if ($conn->query($sql)) {
            $valid_entries++;
            $added_writers_names[] = trim("$firstname $middle_init $lastname"); // Add name to list
        } else {
            $success = false;
            $error_message = "Database error while saving writer: " . $conn->error;
            break; // Stop processing on database error
        }
    }

    if ($success && $valid_entries > 0) {
        $_SESSION['success_message'] = "$valid_entries writer(s) saved successfully.";
        $_SESSION['added_writers_names'] = $added_writers_names; // Store names in session
    } elseif (!$success && !empty($duplicate_message)) {
        $_SESSION['error_message'] = $duplicate_message;
    } elseif ($valid_entries === 0 && empty($duplicate_message) && empty($error_message)) {
        $_SESSION['warning_message'] = 'No valid writers to save. Please provide both firstname and lastname.';
    } elseif (!$success && !empty($error_message)) {
        $_SESSION['error_message'] = "Failed to save writers. " . $error_message;
    } else {
         $_SESSION['error_message'] = 'An unexpected error occurred while saving writers.';
    }

    // Redirect to the same page to prevent form resubmission
    header("Location: writers_list.php");
    exit();
}

// Handle update writer submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'updateWriter') {
    $writer_id = $conn->real_escape_string($_POST['writer_id']);
    $firstname = $conn->real_escape_string($_POST['update_firstname']);
    $middle_init = $conn->real_escape_string($_POST['update_middle_init']);
    $lastname = $conn->real_escape_string($_POST['update_lastname']);

    // Check for duplicate names excluding current writer
    $checkSql = "SELECT * FROM writers WHERE firstname = '$firstname' AND middle_init = '$middle_init' 
                 AND lastname = '$lastname' AND id != '$writer_id'";
    $checkResult = $conn->query($checkSql);

    if ($checkResult->num_rows > 0) {
        $_SESSION['error_message'] = "A writer with this name already exists.";
    } else {
        $sql = "UPDATE writers SET firstname = '$firstname', middle_init = '$middle_init', 
                lastname = '$lastname' WHERE id = '$writer_id'";
        
        if ($conn->query($sql)) {
            $_SESSION['success_message'] = "Writer updated successfully.";
        } else {
            $_SESSION['error_message'] = "Error updating writer: " . $conn->error;
        }
    }

    header("Location: writers_list.php");
    exit();
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
        <h1 class="h3 mb-2 text-gray-800">Writers Management</h1>
        <p class="mb-4">Manage all writers in the system.</p>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <button id="deleteSelectedBtn" class="btn btn-outline-danger btn-sm" disabled>
                    Delete Selected (<span id="selectedDeleteCount">0</span>)
                </button>
            </div>
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addWriterModal">
                <i class="fas fa-plus"></i> Add Writer
            </button>
        </div>

        <!-- Writers Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th style="text-align: center;" id="checkboxHeader">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th style="text-align: center;">ID</th>
                        <th style="text-align: center;">First Name</th>
                        <th style="text-align: center;">Middle Initial</th>
                        <th style="text-align: center;">Last Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td style='text-align: center;'><input type='checkbox' class='row-checkbox' value='{$row['id']}'></td>
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
<!-- End of Main Content -->

<!-- Add Writer Modal -->
<div class="modal fade" id="addWriterModal" tabindex="-1" role="dialog" aria-labelledby="addWriterModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addWriterModalLabel">Add New Writer</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addWriterForm" method="POST" action="writers_list.php">
                    <div class="form-group">
                        <label for="writerFirstName">First Name</label>
                        <input type="text" class="form-control" name="firstname[]" id="writerFirstName" required>
                    </div>
                    <div class="form-group">
                        <label for="writerMiddleInit">Middle Initial</label>
                        <input type="text" class="form-control" name="middle_init[]" id="writerMiddleInit">
                    </div>
                    <div class="form-group">
                        <label for="writerLastName">Last Name</label>
                        <input type="text" class="form-control" name="lastname[]" id="writerLastName" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveWriter">Save Writer</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Writer Modal -->
<div class="modal fade" id="updateWriterModal" tabindex="-1" role="dialog" aria-labelledby="updateWriterModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="updateWriterModalLabel">Update Writer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="updateWriterForm" method="POST" action="writers_list.php">
                    <input type="hidden" name="action" value="updateWriter">
                    <input type="hidden" name="writer_id" id="update_writer_id">
                    <div class="form-group">
                        <label for="update_firstname">First Name</label>
                        <input type="text" class="form-control" name="update_firstname" id="update_firstname" required>
                    </div>
                    <div class="form-group">
                        <label for="update_middle_init">Middle Initial</label>
                        <input type="text" class="form-control" name="update_middle_init" id="update_middle_init">
                    </div>
                    <div class="form-group">
                        <label for="update_lastname">Last Name</label>
                        <input type="text" class="form-control" name="update_lastname" id="update_lastname" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" id="updateWriterBtn">Update Writer</button>
            </div>
        </div>
    </div>
</div>

<!-- Context Menu -->
<div id="contextMenu" class="dropdown-menu context-menu" style="display: none; position: absolute;">
    <a class="dropdown-item" href="#" id="contextMenuUpdate">
        <i class="fas fa-edit text-warning"></i> Edit Writer
    </a>
    <a class="dropdown-item" href="#" id="contextMenuDelete">
        <i class="fas fa-trash text-danger"></i> Delete Writer
    </a>
</div>

<!-- Footer -->
<?php include '../Admin/inc/footer.php'; ?>
<!-- End of Footer -->

<script>
$(document).ready(function () {
    var selectedIds = [];
    var contextMenuTargetId = null;
    var contextMenuTargetData = null;

    // Handle select all checkbox
    $('#selectAll').on('change', function () {
        var isChecked = $(this).prop('checked');
        $('.row-checkbox').prop('checked', isChecked);
        selectedIds = isChecked ? $('.row-checkbox').map(function () { return $(this).val(); }).get() : [];
        updateDeleteButton();
    });

    // Handle individual checkbox changes
    $('#dataTable tbody').on('change', '.row-checkbox', function () {
        var id = $(this).val();
        if ($(this).prop('checked')) {
            if (!selectedIds.includes(id)) selectedIds.push(id);
        } else {
            selectedIds = selectedIds.filter(item => item !== id);
        }
        $('#selectAll').prop('checked', $('.row-checkbox:checked').length === $('.row-checkbox').length);
        updateDeleteButton();
    });

    // Update delete button state and count
    function updateDeleteButton() {
        const count = selectedIds.length;
        $('#selectedDeleteCount').text(count);
        $('#deleteSelectedBtn').prop('disabled', count === 0);
    }

    // Context menu setup
    $('#dataTable tbody').on('contextmenu', 'tr', function (e) {
        e.preventDefault();
        
        // Get data from row
        contextMenuTargetId = $(this).find('td:eq(1)').text();
        contextMenuTargetData = {
            firstname: $(this).find('td:eq(2)').text(),
            middleInit: $(this).find('td:eq(3)').text(),
            lastname: $(this).find('td:eq(4)').text()
        };
        
        // Position the context menu at the cursor position
        $('#contextMenu').css({
            top: e.pageY + 'px',
            left: e.pageX + 'px'
        }).show();
        
        // Highlight the selected row
        $('#dataTable tbody tr').removeClass('table-active');
        $(this).addClass('table-active');
    });

    // Hide context menu when clicking elsewhere
    $(document).on('click', function () {
        $('#contextMenu').hide();
        $('#dataTable tbody tr').removeClass('table-active');
    });

    // Handle context menu update action
    $('#contextMenuUpdate').on('click', function (e) {
        e.preventDefault();
        
        // Populate update modal with selected writer data
        $('#update_writer_id').val(contextMenuTargetId);
        $('#update_firstname').val(contextMenuTargetData.firstname);
        $('#update_middle_init').val(contextMenuTargetData.middleInit);
        $('#update_lastname').val(contextMenuTargetData.lastname);
        
        // Show the modal
        $('#updateWriterModal').modal('show');
    });

    // Handle context menu delete action
    $('#contextMenuDelete').on('click', function (e) {
        e.preventDefault();
        
        // Set up confirmation dialog for deletion
        Swal.fire({
            title: 'Confirm Deletion',
            html: `Are you sure you want to delete writer <strong>${contextMenuTargetData.firstname} ${contextMenuTargetData.lastname}</strong>?<br><br>
                   <span class="text-danger">This action cannot be undone!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // If confirmed, perform deletion via AJAX
                $.ajax({
                    url: 'writers_list.php',
                    method: 'POST',
                    data: {
                        action: 'deleteWriter',
                        writer_id: contextMenuTargetId
                    },
                    success: function (response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'Writer has been deleted successfully.',
                                icon: 'success',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.error || 'An error occurred while deleting the writer.',
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while deleting the writer.',
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            }
        });
    });

    // Handle bulk delete button click
    $('#deleteSelectedBtn').on('click', function () {
        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Confirm Deletion',
            html: `Are you sure you want to delete <strong>${selectedIds.length}</strong> selected writer(s)?<br><br>
                   <span class="text-danger">This action cannot be undone!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the form with selected IDs
                const form = $('<form>', {
                    method: 'POST',
                    action: 'writers_list.php'
                });
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'action',
                    value: 'bulkDelete'
                }));
                selectedIds.forEach(id => {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'selected_ids[]',
                        value: id
                    }));
                });
                $('body').append(form);
                form.submit();
            }
        });
    });

    // Handle form submissions
    $('#saveWriter').click(function () {
        $('#addWriterForm').submit();
    });

    $('#updateWriterBtn').click(function () {
        $('#updateWriterForm').submit();
    });

    $('#dataTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "responsive": true,
        "scrollX": true,
        "order": [[1, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": 0 } // Disable sorting for the checkbox column
        ],
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        }
    });

    // Add CSS for the context menu and table row highlighting
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .context-menu {
                z-index: 1000;
                min-width: 200px;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }
            .table-active {
                background-color: rgba(0, 123, 255, 0.1) !important;
            }
            .dropdown-item:hover {
                background-color: rgba(0, 123, 255, 0.1);
            }
        `)
        .appendTo('head');

    // Double-click row to open update modal
    $('#dataTable tbody').on('dblclick', 'tr', function () {
        const id = $(this).find('td:eq(1)').text();
        const firstname = $(this).find('td:eq(2)').text();
        const middleInit = $(this).find('td:eq(3)').text();
        const lastname = $(this).find('td:eq(4)').text();
        
        $('#update_writer_id').val(id);
        $('#update_firstname').val(firstname);
        $('#update_middle_init').val(middleInit);
        $('#update_lastname').val(lastname);
        
        $('#updateWriterModal').modal('show');
    });

    // Display session messages using SweetAlert2
    <?php if (isset($_SESSION['success_message'])): ?>
        <?php
        $message = addslashes($_SESSION['success_message']);
        $detailsList = '';
        // Check for added writers
        if (isset($_SESSION['added_writers_names']) && !empty($_SESSION['added_writers_names'])) {
            $names = array_map('htmlspecialchars', $_SESSION['added_writers_names']); // Sanitize names
            $detailsList = '<br><br><strong>Added Writers:</strong><br>' . implode('<br>', $names);
            unset($_SESSION['added_writers_names']); // Unset the added names list
        }
        ?>
        Swal.fire({
            title: 'Success!',
            html: '<?php echo $message . $detailsList; ?>', // Use html property for formatted content
            icon: 'success',
            confirmButtonColor: '#3085d6'
        });
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?php echo addslashes($_SESSION['error_message']); ?>',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['warning_message'])): ?>
        Swal.fire({
            title: 'Warning!',
            text: '<?php echo addslashes($_SESSION['warning_message']); ?>',
            icon: 'warning',
            confirmButtonColor: '#ffc107'
        });
        <?php unset($_SESSION['warning_message']); ?>
    <?php endif; ?>
});
</script>