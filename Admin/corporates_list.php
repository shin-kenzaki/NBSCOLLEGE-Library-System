<?php
ob_start();
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection and header
include '../db.php';
include '../admin/inc/header.php';

// Handle update corporate submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['corporate_id'])) {
    $corporate_id = intval($_POST['corporate_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $type = $conn->real_escape_string($_POST['type']);
    $location = $conn->real_escape_string($_POST['location']);
    $description = $conn->real_escape_string($_POST['description']);
    
    // Check for duplicate names excluding current corporate
    $checkSql = "SELECT * FROM corporates WHERE name = '$name' AND id != $corporate_id";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult->num_rows > 0) {
        $_SESSION['error_message'] = "A corporate with this name already exists.";
    } else {
        $sql = "UPDATE corporates SET name = '$name', type = '$type', location = '$location', 
                description = '$description' WHERE id = $corporate_id";
        
        if ($conn->query($sql)) {
            $_SESSION['success_message'] = "Corporate updated successfully.";
            $_SESSION['updated_corporate_details'] = $name;
        } else {
            $_SESSION['error_message'] = "Error updating corporate: " . $conn->error;
        }
    }
    
    // Redirect to refresh the page
    header("Location: corporates_list.php");
    exit();
}

// Query to fetch corporates
$query = "SELECT id, name, type, location, description FROM corporates";
$result = $conn->query($query);

// Handle bulk delete button click
if (isset($_POST['action']) && $_POST['action'] === 'bulkDelete') {
    $selectedIds = isset($_POST['selected_ids']) ? array_map('intval', $_POST['selected_ids']) : [];

    if (empty($selectedIds)) {
        $_SESSION['error_message'] = "No corporates selected for deletion.";
    } else {
        $conn->begin_transaction();
        try {
            // Get the names of corporates before deleting them
            $idsString = implode(',', $selectedIds);
            $detailsQuery = "SELECT name FROM corporates WHERE id IN ($idsString)";
            $detailsResult = $conn->query($detailsQuery);
            
            $deleted_corporates_details = [];
            while ($row = $detailsResult->fetch_assoc()) {
                $deleted_corporates_details[] = $row['name'];
            }
            
            foreach ($selectedIds as $id) {
                // Delete the corporate
                $deleteCorporateSql = "DELETE FROM corporates WHERE id = $id";
                $conn->query($deleteCorporateSql);
            }
            $conn->commit();
            $_SESSION['success_message'] = count($selectedIds) . " corporate(s) deleted successfully.";
            $_SESSION['deleted_corporates_details'] = $deleted_corporates_details;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error deleting corporates: " . $e->getMessage();
        }
    }

    // Redirect to refresh the page
    header("Location: corporates_list.php");
    exit;
}
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <h1 class="h3 mb-2 text-gray-800">Corporates Management</h1>
        <p class="mb-4">Manage all corporates in the system.</p>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <button id="deleteSelectedBtn" class="btn btn-outline-danger btn-sm" disabled>
                    Delete Selected (<span id="selectedDeleteCount">0</span>)
                </button>
            </div>
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addCorporateModal">
                <i class="fas fa-plus"></i> Add Corporate
            </button>
        </div>

        <!-- Corporates Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th style="text-align: center;" id="checkboxHeader">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th style="text-align: center;">ID</th>
                        <th style="text-align: center;">Name</th>
                        <th style="text-align: center;">Type</th>
                        <th style="text-align: center;">Location</th>
                        <th style="text-align: center;">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr data-id='{$row['id']}' data-name='" . htmlspecialchars($row['name'], ENT_QUOTES) . "' data-type='" . htmlspecialchars($row['type'], ENT_QUOTES) . "' data-location='" . htmlspecialchars($row['location'], ENT_QUOTES) . "' data-description='" . htmlspecialchars($row['description'], ENT_QUOTES) . "'>
                                    <td style='text-align: center;'><input type='checkbox' class='row-checkbox' value='{$row['id']}'></td>
                                    <td style='text-align: center;'>{$row['id']}</td>
                                    <td style='text-align: center;'>{$row['name']}</td>
                                    <td style='text-align: center;'>{$row['type']}</td>
                                    <td style='text-align: center;'>{$row['location']}</td>
                                    <td style='text-align: center;'>{$row['description']}</td>
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

<!-- Add Corporate Modal -->
<div class="modal fade" id="addCorporateModal" tabindex="-1" role="dialog" aria-labelledby="addCorporateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addCorporateModalLabel">Add New Corporate</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addCorporateForm" method="POST" action="add_corporate.php">
                    <div class="form-group">
                        <label for="corporateName">Name</label>
                        <input type="text" class="form-control" name="name" id="corporateName" required>
                    </div>
                    <div class="form-group">
                        <label for="corporateType">Type</label>
                        <select class="form-control" name="type" id="corporateType" required>
                            <option value="">Select Type</option>
                            <option value="Government Institution">Government Institution</option>
                            <option value="University">University</option>
                            <option value="Commercial Organization">Commercial Organization</option>
                            <option value="Non-profit Organization">Non-profit Organization</option>
                            <option value="Research Institute">Research Institute</option>
                            <option value="Publisher">Publisher</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="corporateLocation">Location</label>
                        <input type="text" class="form-control" name="location" id="corporateLocation" required>
                    </div>
                    <div class="form-group">
                        <label for="corporateDescription">Description</label>
                        <textarea class="form-control" name="description" id="corporateDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveCorporate">Save Corporate</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Corporate Modal -->
<div class="modal fade" id="updateCorporateModal" tabindex="-1" role="dialog" aria-labelledby="updateCorporateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="updateCorporateModalLabel">Update Corporate</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="updateCorporateForm" method="POST" action="corporates_list.php">
                    <input type="hidden" name="corporate_id" id="updateCorporateId">
                    <div class="form-group">
                        <label for="updateCorporateName">Name</label>
                        <input type="text" class="form-control" name="name" id="updateCorporateName" required>
                    </div>
                    <div class="form-group">
                        <label for="updateCorporateType">Type</label>
                        <select class="form-control" name="type" id="updateCorporateType" required>
                            <option value="">Select Type</option>
                            <option value="Government Institution">Government Institution</option>
                            <option value="University">University</option>
                            <option value="Commercial Organization">Commercial Organization</option>
                            <option value="Non-profit Organization">Non-profit Organization</option>
                            <option value="Research Institute">Research Institute</option>
                            <option value="Publisher">Publisher</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="updateCorporateLocation">Location</label>
                        <input type="text" class="form-control" name="location" id="updateCorporateLocation" required>
                    </div>
                    <div class="form-group">
                        <label for="updateCorporateDescription">Description</label>
                        <textarea class="form-control" name="description" id="updateCorporateDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" id="updateCorporate">Update Corporate</button>
            </div>
        </div>
    </div>
</div>

<!-- Context Menu -->
<div id="contextMenu" class="dropdown-menu context-menu" style="display: none; position: absolute;">
    <a class="dropdown-item" href="#" id="contextMenuUpdate">
        <i class="fas fa-edit text-warning"></i> Edit Corporate
    </a>
    <a class="dropdown-item" href="#" id="contextMenuDelete">
        <i class="fas fa-trash text-danger"></i> Delete Corporate
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
        
        contextMenuTargetId = $(this).data('id');
        contextMenuTargetData = {
            name: $(this).data('name'),
            type: $(this).data('type'),
            location: $(this).data('location'),
            description: $(this).data('description')
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
        
        // Populate update modal with selected corporate data
        $('#updateCorporateId').val(contextMenuTargetId);
        $('#updateCorporateName').val(contextMenuTargetData.name);
        $('#updateCorporateType').val(contextMenuTargetData.type);
        $('#updateCorporateLocation').val(contextMenuTargetData.location);
        $('#updateCorporateDescription').val(contextMenuTargetData.description);
        
        // Show the modal
        $('#updateCorporateModal').modal('show');
    });

    // Handle context menu delete action
    $('#contextMenuDelete').on('click', function (e) {
        e.preventDefault();
        
        // Set up confirmation dialog for deletion
        Swal.fire({
            title: 'Confirm Deletion',
            html: `Are you sure you want to delete corporate <strong>${contextMenuTargetData.name}</strong>?<br><br>
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
                // Submit form to delete
                const form = $('<form>', {
                    method: 'POST',
                    action: 'corporates_list.php'
                });
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'action',
                    value: 'bulkDelete'
                }));
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'selected_ids[]',
                    value: contextMenuTargetId
                }));
                $('body').append(form);
                form.submit();
            }
        });
    });

    // Handle bulk delete button click
    $('#deleteSelectedBtn').on('click', function () {
        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Confirm Deletion',
            html: `Are you sure you want to delete <strong>${selectedIds.length}</strong> selected corporate(s)?<br><br>
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
                    action: 'corporates_list.php'
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
    $('#saveCorporate').click(function () {
        $('#addCorporateForm').submit();
    });

    $('#updateCorporate').click(function () {
        $('#updateCorporateForm').submit();
    });

    // Double-click row to open update modal
    $('#dataTable tbody').on('dblclick', 'tr', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const type = $(this).data('type');
        const location = $(this).data('location');
        const description = $(this).data('description');
        
        $('#updateCorporateId').val(id);
        $('#updateCorporateName').val(name);
        $('#updateCorporateType').val(type);
        $('#updateCorporateLocation').val(location);
        $('#updateCorporateDescription').val(description);
        
        $('#updateCorporateModal').modal('show');
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

    // Display session messages using SweetAlert2
    <?php if (isset($_SESSION['success_message'])): ?>
        <?php
        $message = addslashes($_SESSION['success_message']);
        $detailsList = '';
        
        // Check for updated corporate details
        if (isset($_SESSION['updated_corporate_details'])) {
            $detail = htmlspecialchars($_SESSION['updated_corporate_details'], ENT_QUOTES);
            $detailsList = '<br><br><strong>Updated Corporate:</strong><br>' . $detail;
            unset($_SESSION['updated_corporate_details']);
        }
        
        // Check for deleted corporate details
        if (isset($_SESSION['deleted_corporates_details']) && !empty($_SESSION['deleted_corporates_details'])) {
            $details = array_map(function($detail) {
                return htmlspecialchars($detail, ENT_QUOTES);
            }, $_SESSION['deleted_corporates_details']);
            $detailsList = '<br><br><strong>Deleted Corporates:</strong><br>' . implode('<br>', $details);
            unset($_SESSION['deleted_corporates_details']);
        }
        ?>
        Swal.fire({
            title: 'Success!',
            html: '<?php echo $message . $detailsList; ?>',
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
});
</script>