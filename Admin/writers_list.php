<?php
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

// Handle bulk action requests
if (isset($_POST['bulk_action']) && isset($_POST['selected_ids'])) {
    $selectedIds = $_POST['selected_ids'];
    $action = $_POST['bulk_action'];
    
    if (empty($selectedIds)) {
        $_SESSION['error_message'] = "No writers selected for action.";
    } else {
        // Process bulk actions
        switch ($action) {
            case 'delete':
                // Start transaction to ensure data integrity
                $conn->begin_transaction();
                try {
                    $deleteCount = 0;
                    foreach ($selectedIds as $id) {
                        $id = (int)$id; // Ensure it's an integer
                        
                        // First delete all contributor records that reference this writer
                        $deleteContributorsSql = "DELETE FROM contributors WHERE writer_id = $id";
                        $conn->query($deleteContributorsSql);
                        
                        // Then delete the writer
                        $deleteWriterSql = "DELETE FROM writers WHERE id = $id";
                        if ($conn->query($deleteWriterSql)) {
                            $deleteCount++;
                        }
                    }
                    
                    // Commit the transaction
                    $conn->commit();
                    
                    if ($deleteCount > 0) {
                        $_SESSION['success_message'] = "$deleteCount writer(s) deleted successfully. Related contributor records were also removed.";
                    } else {
                        $_SESSION['error_message'] = "Failed to delete writers.";
                    }
                } catch (Exception $e) {
                    // An error occurred, rollback the transaction
                    $conn->rollback();
                    $_SESSION['error_message'] = "Error deleting writers: " . $e->getMessage();
                }
                break;
                
            // Add more bulk actions here if needed
        }
    }
    
    // Clear selected IDs after processing
    $_SESSION['selectedWriterIds'] = [];
    
    // Redirect to refresh the page
    header("Location: writers_list.php");
    exit;
}

// Count total writers
$totalWritersQuery = "SELECT COUNT(*) as total FROM writers";
$totalWritersResult = $conn->query($totalWritersQuery);
$totalWriters = $totalWritersResult->fetch_assoc()['total'];

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
                <div class="d-flex align-items-center">
                    <span class="mr-3 total-writers-display">
                        Total Writers: <?php echo number_format($totalWriters); ?>
                    </span>
                    <button id="returnSelectedBtn" class="btn btn-danger btn-sm mr-2 bulk-delete-btn" disabled>
                        <i class="fas fa-trash"></i>
                        <span>Delete Selected</span>
                        <span class="badge badge-light ml-1">0</span>
                    </button>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addWriterModal">Add Writer</button>
                </div>
            </div>
            <div class="card-body px-0"> <!-- Remove padding for full-width scroll -->
                <div class="table-responsive px-3"> <!-- Add padding inside scroll container -->
                    <!-- Hidden form for bulk actions -->
                    <form id="bulkActionForm" method="POST" action="writers_list.php">
                        <input type="hidden" name="bulk_action" id="bulk_action">
                        <div id="selected_ids_container"></div>
                    </form>
                    
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="cursor: pointer; text-align: center;" id="checkboxHeader"><input type="checkbox" id="selectAll"></th>
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
    /* Add checkbox cell styles */
    .checkbox-cell {
        cursor: pointer;
        text-align: center;
        vertical-align: middle;
        width: 50px !important; /* Fixed width for uniformity */
    }
    .checkbox-cell:hover {
        background-color: rgba(0, 123, 255, 0.1);
    }
    .checkbox-cell input[type="checkbox"] {
        margin: 0 auto;
        display: block;
    }
    
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
    
    /* Add styles for writer stats */
    .writer-stats {
        display: flex;
        align-items: center;
    }
    
    .total-writers-display {
        font-size: 0.9rem;
        color: #4e73df;
        font-weight: 600;
        margin-left: 10px;
    }

    /* Add button badge styles */
    .bulk-delete-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .bulk-delete-btn .badge {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
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
        "columnDefs": [
            { 
                "orderable": false, 
                "searchable": false,
                "targets": 0,
                "className": "checkbox-cell" // Add checkbox-cell class
            }
        ],
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
        selectedWriterId = $(this).find('td:nth-child(2)').text(); // Changed from td:nth-child(1) to td:nth-child(2)
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
        var writerId = row.find('td:nth-child(2)').text();
        var firstName = row.find('td:nth-child(3)').text();
        var lastName = row.find('td:nth-child(5)').text();

        if (confirm(`Are you sure you want to delete this writer?\n\nID: ${writerId}\nName: ${firstName} ${lastName}\n\nThis will also delete all contributor records for this writer.`)) {
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

    // Handle select all checkbox and header click
    $('#selectAll, #checkboxHeader').on('click', function(e) {
        if ($(this).is('th')) {
            // If clicking the header cell, toggle the checkbox
            const checkbox = $('#selectAll');
            checkbox.prop('checked', !checkbox.prop('checked'));
        }
        // Apply the checkbox state to all row checkboxes
        $('.row-checkbox').prop('checked', $('#selectAll').prop('checked'));
        // Prevent event bubbling when clicking the checkbox itself
        if ($(this).is('input')) {
            e.stopPropagation();
        }
        
        // Update the selectedIds array and save to session
        if ($('#selectAll').prop('checked')) {
            $('.row-checkbox').each(function() {
                var id = $(this).val();
                if (!selectedIds.includes(id)) {
                    selectedIds.push(id);
                }
            });
        } else {
            selectedIds = [];
        }
        
        saveSelectedIds();
    });

    // Handle individual checkbox changes
    $('#dataTable tbody').on('change', '.row-checkbox', function() {
        var id = $(this).val();
        
        if ($(this).prop('checked')) {
            if (!selectedIds.includes(id)) {
                selectedIds.push(id);
            }
        } else {
            selectedIds = selectedIds.filter(item => item !== id);
        }
        
        // Update select all checkbox
        updateSelectAllCheckbox();
        saveSelectedIds();
    });

    // Add cell click handler for the checkbox column
    $('#dataTable tbody').on('click', 'td:first-child', function(e) {
        // If the click was directly on the checkbox, don't execute this handler
        if (e.target.type === 'checkbox') return;
        
        // Find the checkbox within this cell and toggle it
        var checkbox = $(this).find('.row-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // Add row click handler to check the row checkbox
    $('#dataTable tbody').on('click', 'tr', function(e) {
        // Ignore clicks on checkbox itself and on action buttons
        if (e.target.type === 'checkbox' || $(e.target).hasClass('btn') || $(e.target).parent().hasClass('btn')) {
            return;
        }
        
        // Find the checkbox within this row and toggle it
        var checkbox = $(this).find('.row-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // Remove the old handlers that might interfere
    $('#dataTable tbody').off('click', 'tr');
    
    // Track selected rows
    var selectedIds = <?php echo json_encode($_SESSION['selectedWriterIds'] ?? []); ?>;
    
    // Initialize checkboxes based on session data
    function initializeCheckboxes() {
        $('.row-checkbox').each(function() {
            var id = $(this).val();
            if (selectedIds.includes(id)) {
                $(this).prop('checked', true);
            }
        });
        
        // Update select all checkbox
        updateSelectAllCheckbox();
    }
    
    // Update the select all checkbox state
    function updateSelectAllCheckbox() {
        var allChecked = $('.row-checkbox:checked').length === $('.row-checkbox').length && $('.row-checkbox').length > 0;
        $('#selectAll').prop('checked', allChecked);
    }
    
    // Save selected IDs to session via AJAX
    function saveSelectedIds() {
        updateDeleteButton(); // Add this line
        $.ajax({
            url: 'writers_list.php',
            type: 'POST',
            data: {
                action: 'updateSelectedWriters',
                selectedIds: selectedIds
            },
            dataType: 'json',
            success: function(response) {
                console.log('Saved ' + response.count + ' selected writers');
            }
        });
    }
    
    // Initialize checkboxes on page load
    initializeCheckboxes();
    
    // Handle row clicks to select checkbox
    $('#dataTable tbody').on('click', 'tr', function(e) {
        // Ignore clicks on checkbox itself and on action buttons
        if (e.target.type === 'checkbox' || $(e.target).hasClass('btn') || $(e.target).parent().hasClass('btn')) {
            return;
        }
        
        var checkbox = $(this).find('.row-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });
    
    // Handle checkbox change events
    $('#dataTable tbody').on('change', '.row-checkbox', function() {
        var id = $(this).val();
        
        if ($(this).prop('checked')) {
            if (!selectedIds.includes(id)) {
                selectedIds.push(id);
            }
        } else {
            selectedIds = selectedIds.filter(item => item !== id);
        }
        
        updateSelectAllCheckbox();
        saveSelectedIds();
    });
    
    // Handle select all checkbox
    $('#selectAll').on('change', function() {
        var isChecked = $(this).prop('checked');
        
        $('.row-checkbox').each(function() {
            $(this).prop('checked', isChecked);
            
            var id = $(this).val();
            if (isChecked && !selectedIds.includes(id)) {
                selectedIds.push(id);
            }
        });
        
        if (!isChecked) {
            selectedIds = [];
        }
        
        saveSelectedIds();
    });
    
    // Handle header cell click for select all
    $('#checkboxHeader').on('click', function(e) {
        // If clicking directly on the checkbox, don't execute this
        if (e.target.type === 'checkbox') return;
        
        $('#selectAll').trigger('click');
    });
    
    // Handle bulk actions
    $('.bulk-delete-btn').on('click', function(e) {
        e.preventDefault();
        
        if (selectedIds.length === 0) {
            alert('Please select at least one writer to delete.');
            return;
        }
        
        var confirmMessage = 'Are you sure you want to delete ' + selectedIds.length + ' selected writer(s)?\n\nThis will also delete all contributor records for these writers.';
        
        if (confirm(confirmMessage)) {
            $('#selected_ids_container').empty();
            selectedIds.forEach(function(id) {
                $('#selected_ids_container').append('<input type="hidden" name="selected_ids[]" value="' + id + '">');
            });
            $('#bulk_action').val('delete');
            $('#bulkActionForm').submit();
        }
    });

    // Modified checkbox handling - Header cell click handler
    $(document).on('click', 'thead th:first-child', function(e) {
        // If the click was directly on the checkbox, don't execute this handler
        if (e.target.type === 'checkbox') return;
        
        // Find and click the checkbox
        var checkbox = $('#selectAll');
        checkbox.prop('checked', !checkbox.prop('checked'));
        $('.row-checkbox').prop('checked', checkbox.prop('checked'));
        
        // Update selectedIds array
        if (checkbox.prop('checked')) {
            $('.row-checkbox').each(function() {
                var id = $(this).val();
                if (!selectedIds.includes(id)) {
                    selectedIds.push(id);
                }
            });
        } else {
            selectedIds = [];
        }
        saveSelectedIds();
    });

    // Remove old header click handlers
    $('#checkboxHeader').off('click');
    $('#selectAll, #checkboxHeader').off('click');

    // Keep existing checkbox change handlers

    function updateDeleteButton() {
        const count = selectedIds.length;
        const deleteBtn = $('.bulk-delete-btn');
        deleteBtn.find('.badge').text(count);
        deleteBtn.prop('disabled', count === 0);
    }

    // Make the entire checkbox cell clickable
    $(document).on('click', '.checkbox-cell', function(e) {
        // Prevent triggering if clicking directly on the checkbox
        if (e.target.type !== 'checkbox') {
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            checkbox.trigger('change'); // Trigger change event
        }
    });

    // Remove the old handlers that might interfere
    $('#dataTable tbody').off('click', 'td:first-child');
    $('#dataTable tbody').off('click', 'tr');
});
</script>