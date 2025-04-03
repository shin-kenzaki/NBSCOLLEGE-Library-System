<?php
ob_start(); // Start output buffering to prevent "headers already sent" errors
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection first
include '../db.php';
include '../admin/inc/header.php';

// Initialize selected publishers array in session if not exists
if (!isset($_SESSION['selectedPublisherIds'])) {
    $_SESSION['selectedPublisherIds'] = [];
}

// Handle AJAX request to update selected publishers
if (isset($_POST['action']) && $_POST['action'] == 'updateSelectedPublishers') {
    $_SESSION['selectedPublisherIds'] = isset($_POST['selectedIds']) ? $_POST['selectedIds'] : [];
    echo json_encode(['success' => true, 'count' => count($_SESSION['selectedPublisherIds'])]);
    exit;
}

// Handle bulk action requests
if (isset($_POST['bulk_action']) && isset($_POST['selected_ids'])) {
    $selectedIds = $_POST['selected_ids'];
    $action = $_POST['bulk_action'];
    
    if (empty($selectedIds)) {
        $_SESSION['error_message'] = "No publishers selected for action.";
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
                        
                        // First delete all publication records that reference this publisher
                        $deletePublicationsSql = "DELETE FROM publications WHERE publisher_id = $id";
                        $conn->query($deletePublicationsSql);
                        
                        // Then delete the publisher
                        $deletePublisherSql = "DELETE FROM publishers WHERE id = $id";
                        if ($conn->query($deletePublisherSql)) {
                            $deleteCount++;
                        }
                    }
                    
                    // Commit the transaction
                    $conn->commit();
                    
                    if ($deleteCount > 0) {
                        $_SESSION['success_message'] = "$deleteCount publisher(s) deleted successfully. Related publication records were also removed.";
                    } else {
                        $_SESSION['error_message'] = "Failed to delete publishers.";
                    }
                } catch (Exception $e) {
                    // An error occurred, rollback the transaction
                    $conn->rollback();
                    $_SESSION['error_message'] = "Error deleting publishers: " . $e->getMessage();
                }
                break;
                
            // Add more bulk actions here if needed
        }
    }
    
    // Clear selected IDs after processing
    $_SESSION['selectedPublisherIds'] = [];
    
    // Redirect to refresh the page
    header("Location: publisher_list.php");
    exit;
}

// Count total publishers
$totalPublishersQuery = "SELECT COUNT(*) as total FROM publishers";
$totalPublishersResult = $conn->query($totalPublishersQuery);
$totalPublishers = $totalPublishersResult->fetch_assoc()['total'];

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

// Fetch publishers data with proper sorting
$sql = "SELECT id, publisher, place FROM publishers";
if (!empty($searchQuery)) {
    $sql .= " WHERE publisher LIKE '%$searchQuery%' OR place LIKE '%$searchQuery%'";
}
$sql .= " ORDER BY id DESC";
$result = $conn->query($sql);
?>

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
    
    /* Add responsive table styles */
    .table-responsive {
        width: 100%;
        margin-bottom: 1rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    #dataTable th,
    #dataTable td {
        min-width: 100px;
        white-space: nowrap;
    }
    
    #dataTable {
        width: 100% !important;
    }
    
    .table td, .table th {
        white-space: nowrap;
    }
    
    /* Add styles for publisher stats */
    .publisher-stats {
        display: flex;
        align-items: center;
    }
    
    .total-publishers-display {
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
    
    /* Context menu styling */
    .context-menu {
        position: absolute;
        display: none;
        z-index: 1000;
        min-width: 180px;
        padding: 0;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        border-radius: 0.35rem;
        overflow: hidden;
    }
    
    .context-menu .list-group {
        margin-bottom: 0;
    }
    
    .context-menu-item {
        cursor: pointer;
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        transition: background-color 0.2s;
    }
    
    .context-menu-item:hover {
        background-color: #f8f9fc;
        color: #4e73df;
    }
    
    .context-menu-item i {
        width: 20px;
        text-align: center;
    }
    
    /* Improved checkbox centering */
    #dataTable th:first-child,
    #dataTable td:first-child {
        text-align: center;
        vertical-align: middle;
        width: 40px !important;
        min-width: 40px !important;
        max-width: 40px !important;
        box-sizing: border-box;
        padding: 0.75rem 0.5rem;
    }
    
    #checkboxHeader {
        width: 40px !important;
        min-width: 40px !important;
        max-width: 40px !important;
        padding: 0.75rem 0.5rem !important;
        box-sizing: border-box;
    }
    
    #dataTable input[type="checkbox"] {
        margin: 0 auto;
        display: block;
        width: 16px;
        height: 16px;
    }
    
    .checkbox-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
        width: 100%;
        padding: 0 !important;
    }

    /* Add selected class styling */
    #dataTable tbody tr.selected {
        background-color: rgba(0, 123, 255, 0.1) !important;
    }
    
    /* Override striped table styling for selected rows */
    #dataTable.table-striped tbody tr.selected:nth-of-type(odd),
    #dataTable.table-striped tbody tr.selected:nth-of-type(even) {
        background-color: rgba(0, 123, 255, 0.1) !important;
    }
</style>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Publishers List</h6>
                <div class="d-flex align-items-center">
                    <span class="mr-3 total-publishers-display">
                        Total Publishers: <?php echo number_format($totalPublishers); ?>
                    </span>
                    <button id="returnSelectedBtn" class="btn btn-danger btn-sm mr-2 bulk-delete-btn" disabled>
                        <i class="fas fa-trash"></i>
                        <span>Delete Selected</span>
                        <span class="badge badge-light ml-1">0</span>
                    </button>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addPublisherModal">Add Publisher</button>
                    <button type="button" class="btn btn-info btn-sm ml-2" data-toggle="modal" data-target="#instructionsModal">
                        <i class="fas fa-question-circle"></i> Instructions
                    </button>
                </div>
            </div>
            <div class="card-body px-0">
                <div class="table-responsive px-3">
                    <!-- Hidden form for bulk actions -->
                    <form id="bulkActionForm" method="POST" action="publisher_list.php">
                        <input type="hidden" name="bulk_action" id="bulk_action">
                        <div id="selected_ids_container"></div>
                    </form>
                    
                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="text-align: center;">Select</th>
                                <th class="text-center">ID</th>
                                <th class="text-center">Publisher</th>
                                <th class="text-center">Place of Publication</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Check if the query returned any rows
                            if ($result->num_rows > 0) {
                                // Loop through the rows and display them in the table
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td style='text-align: center;'><input type='checkbox' class='row-checkbox' value='" . $row['id'] . "'></td>
                                            <td style='text-align: center;'>" . $row['id'] . "</td>
                                            <td style='text-align: center;'>" . $row['publisher'] . "</td>
                                            <td style='text-align: center;'>" . $row['place'] . "</td>
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

<!-- Instructions Modal -->
<div class="modal fade" id="instructionsModal" tabindex="-1" role="dialog" aria-labelledby="instructionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="instructionsModalLabel">
                    <i class="fas fa-info-circle mr-2"></i>Publisher Management Instructions
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">Managing Publishers</h6>
                    </div>
                    <div class="card-body">
                        <p>This page allows you to manage publisher information in the library system:</p>
                        <ul>
                            <li><strong>View Publishers</strong>: The table displays all publishers with their details</li>
                            <li><strong>Add New Publisher</strong>: Click the "Add Publisher" button to create a new publisher entry</li>
                            <li><strong>Edit Publisher</strong>: Use the edit button in the action column to modify existing publisher information</li>
                            <li><strong>Delete Publisher</strong>: Remove a publisher if it's no longer needed (caution: this may affect book records)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">Publisher Information</h6>
                    </div>
                    <div class="card-body">
                        <p>When adding or editing a publisher, consider the following fields:</p>
                        <ul>
                            <li><strong>Publisher Name</strong>: The official name of the publishing company</li>
                            <li><strong>Place of Publication</strong>: The city or location where the publisher is based</li>
                            <li><strong>Website</strong> (optional): URL for the publisher's official website</li>
                            <li><strong>Contact Information</strong> (optional): Phone, email, or other contact details</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">Best Practices</h6>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>Always check if a publisher already exists before creating a new entry</li>
                            <li>Maintain consistent naming conventions (e.g., "Oxford University Press" vs. "OUP")</li>
                            <li>Include specific location information in the Place field (e.g., "New York, NY" rather than just "USA")</li>
                            <li>Use the search and filter features to quickly find publishers in the list</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Context Menu -->
<div class="context-menu" id="contextMenu">
    <ul class="list-group">
        <li class="list-group-item context-menu-item" data-action="edit"><i class="fas fa-edit mr-2"></i>Edit Publisher</li>
        <li class="list-group-item context-menu-item" data-action="delete"><i class="fas fa-trash-alt mr-2"></i>Delete Publisher</li>
    </ul>
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
        "responsive": false,
        "scrollX": true,
        "order": [[1, "desc"]], // Sort by ID in descending order
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

    // Add window resize handler
    $(window).on('resize', function () {
        table.columns.adjust();
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
        selectedPublisherId = $(this).find('td:nth-child(2)').text();
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
        var publisherId = row.find('td:nth-child(2)').text();
        var publisher = row.find('td:nth-child(3)').text();
        var place = row.find('td:nth-child(4)').text();

        if (confirm(`Are you sure you want to delete this publisher?\n\nID: ${publisherId}\nPublisher: ${publisher}\nPlace: ${place}\n\nThis will also delete all publication records for this publisher.`)) {
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
    var selectedIds = <?php echo json_encode($_SESSION['selectedPublisherIds'] ?? []); ?>;
    
    // Initialize checkboxes based on session data
    function initializeCheckboxes() {
        $('.row-checkbox').each(function() {
            var id = $(this).val();
            if (selectedIds.includes(id)) {
                $(this).prop('checked', true);
            }
        });
    }
    
    // Save selected IDs to session via AJAX
    function saveSelectedIds() {
        updateDeleteButton(); // Add this line
        $.ajax({
            url: 'publisher_list.php',
            type: 'POST',
            data: {
                action: 'updateSelectedPublishers',
                selectedIds: selectedIds
            },
            dataType: 'json',
            success: function(response) {
                console.log('Saved ' + response.count + ' selected publishers');
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
        
        saveSelectedIds();
    });
    
    // Handle bulk actions
    $('.bulk-action').on('click', function(e) {
        e.preventDefault();
        
        var action = $(this).data('action');
        
        if (selectedIds.length === 0) {
            alert('Please select at least one publisher to perform this action.');
            return;
        }
        
        var confirmMessage = 'Are you sure you want to ' + action + ' ' + selectedIds.length + ' selected publisher(s)?';
        if (action === 'delete') {
            confirmMessage += '\n\nThis will also delete all publication records for these publishers.';
        }
        
        if (confirm(confirmMessage)) {
            // Clear previous inputs
            $('#selected_ids_container').empty();
            
            // Add hidden inputs for each selected ID
            selectedIds.forEach(function(id) {
                $('#selected_ids_container').append('<input type="hidden" name="selected_ids[]" value="' + id + '">');
            });
            
            // Set the action and submit
            $('#bulk_action').val(action);
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

    // Replace the bulk-action click handler with this:
    $('.bulk-delete-btn').on('click', function(e) {
        e.preventDefault();
        
        if (selectedIds.length === 0) {
            alert('Please select at least one publisher to delete.');
            return;
        }
        
        var confirmMessage = 'Are you sure you want to delete ' + selectedIds.length + ' selected publisher(s)?\n\nThis will also delete all publication records for these publishers.';
        
        if (confirm(confirmMessage)) {
            $('#selected_ids_container').empty();
            selectedIds.forEach(function(id) {
                $('#selected_ids_container').append('<input type="hidden" name="selected_ids[]" value="' + id + '">');
            });
            $('#bulk_action').val('delete');
            $('#bulkActionForm').submit();
        }
    });

    // Make the entire checkbox cell clickable
    $(document).on('click', '.checkbox-cell', function(e) {
        // Prevent triggering if clicking directly on the checkbox
        if (e.target.type !== 'checkbox') {
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            checkbox.trigger('change'); // Trigger change event
        }
    });

    // Remove the old click handlers that might interfere
    $('#dataTable tbody').off('click', 'td:first-child');
    $('#dataTable tbody').off('click', 'tr');

    // Context menu handling
    let contextTarget = null;
    
    // Show context menu on right-click
    $('#dataTable tbody').on('contextmenu', 'tr', function(e) {
        e.preventDefault();
        
        // Get the clicked row data
        const rowData = table.row(this).data();
        if (!rowData) return;
        
        // Highlight the selected row
        $('#dataTable tbody tr').removeClass('table-primary');
        $(this).addClass('table-primary');
        
        // Set context target
        contextTarget = {
            id: $(this).find('td:eq(1)').text(),
            publisher: $(this).find('td:eq(2)').text(),
            place: $(this).find('td:eq(3)').text(),
            element: this
        };
        
        // Show the context menu at mouse position
        $('#contextMenu').css({
            top: e.pageY + 'px',
            left: e.pageX + 'px',
            display: 'block'
        });
    });
    
    // Hide context menu on click outside
    $(document).click(function() {
        $('#contextMenu').hide();
    });
    
    // Handle context menu item clicks
    $('.context-menu-item').on('click', function() {
        const action = $(this).data('action');
        
        if (!contextTarget) return;
        
        if (action === 'edit') {
            window.location.href = `update_publisher.php?publisher_id=${contextTarget.id}`;
        } else if (action === 'delete') {
            Swal.fire({
                title: 'Delete Publisher?',
                html: `Are you sure you want to delete <strong>${contextTarget.publisher}</strong> in <strong>${contextTarget.place}</strong>?<br><br>
                      <span class="text-danger">This will also remove all publication records for this publisher.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Perform delete operation via AJAX
                    $.ajax({
                        url: 'delete_publisher.php',
                        type: 'POST',
                        data: { publisher_id: contextTarget.id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message || 'Publisher deleted successfully',
                                    icon: 'success'
                                }).then(() => {
                                    // Remove row from table
                                    table.row($(contextTarget.element)).remove().draw();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message || 'Failed to delete publisher',
                                    icon: 'error'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'A server error occurred',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        }
        
        // Hide the context menu
        $('#contextMenu').hide();
    });

    // Wrap checkboxes in centering div for better alignment
    $('#dataTable tbody tr td:first-child').each(function() {
        // Only add wrapper if not already wrapped
        if (!$(this).find('.checkbox-wrapper').length) {
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.wrap('<div class="checkbox-wrapper"></div>');
        }
    });
    
    // Also ensure header checkbox is centered
    if (!$('#checkboxHeader .checkbox-wrapper').length) {
        $('#selectAll').wrap('<div class="checkbox-wrapper"></div>');
    }
    
    // Make sure newly added rows also get wrapper
    table.on('draw', function() {
        $('#dataTable tbody tr td:first-child').each(function() {
            if (!$(this).find('.checkbox-wrapper').length) {
                const checkbox = $(this).find('input[type="checkbox"]');
                checkbox.wrap('<div class="checkbox-wrapper"></div>');
            }
        });
    });

    // Add row click handler to toggle the row checkbox
    $('#dataTable tbody').on('click', 'tr', function(e) {
        // Ignore clicks on checkbox itself or action buttons
        if (e.target.type === 'checkbox' || $(e.target).hasClass('btn') || $(e.target).parent().hasClass('btn')) {
            return;
        }

        // Find the checkbox within this row and toggle it
        var checkbox = $(this).find('.row-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // Update row selection visuals
    function updateRowSelectionState() {
        $('#dataTable tbody tr').each(function() {
            const isChecked = $(this).find('.row-checkbox').prop('checked');
            $(this).toggleClass('selected', isChecked);
        });
        
        // Also update the delete button badge with count of selected items
        const count = $('.row-checkbox:checked').length;
        $('.bulk-delete-btn .badge').text(count);
        $('.bulk-delete-btn').prop('disabled', count === 0);
    }

    // Listen for checkbox state changes to update row selection visuals
    $('#dataTable tbody').on('change', '.row-checkbox', function() {
        updateRowSelectionState();
    });

    // Initialize row selection visuals on page load
    updateRowSelectionState();
    
    // Add selected class styling
    $('<style>').text(`
        #dataTable tbody tr.selected {
            background-color: rgba(0, 123, 255, 0.1) !important;
        }
    `).appendTo('head');
});
</script>

<script>
$(document).ready(function() {
    // ...existing code...

    // Update row selection visuals
    function updateRowSelectionState() {
        $('#dataTable tbody tr').each(function() {
            const isChecked = $(this).find('.row-checkbox').prop('checked');
            $(this).toggleClass('selected', isChecked);
        });
    }

    // Update visuals when individual checkboxes are toggled
    $('#dataTable tbody').on('change', '.row-checkbox', function() {
        updateRowSelectionState();
    });

    // Initialize row selection visuals on page load
    updateRowSelectionState();

    // ...existing code...
});
</script>