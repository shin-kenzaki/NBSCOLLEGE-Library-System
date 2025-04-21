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
        // Delete associated contributors
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

// Handle bulk action requests
if (isset($_POST['bulk_action']) && isset($_POST['selected_ids'])) {
    $selectedIds = $_POST['selected_ids'];
    $action = $_POST['bulk_action'];
    
    if (empty($selectedIds)) {
        $_SESSION['error_message'] = "No writers selected for action.";
    } else {
        // Ensure all IDs are integers
        $safeSelectedIds = array_map('intval', $selectedIds);
        $idsString = implode(',', $safeSelectedIds);

        // Process bulk actions
        switch ($action) {
            case 'delete':
                // Fetch names before deleting
                $deleted_writers_names = [];
                if (!empty($idsString)) {
                    $fetchNamesSql = "SELECT id, firstname, middle_init, lastname FROM writers WHERE id IN ($idsString)";
                    $namesResult = $conn->query($fetchNamesSql);
                    if ($namesResult && $namesResult->num_rows > 0) {
                        while ($row = $namesResult->fetch_assoc()) {
                            $deleted_writers_names[$row['id']] = trim($row['firstname'] . ' ' . $row['middle_init'] . ' ' . $row['lastname']);
                        }
                    }
                }

                // Start transaction to ensure data integrity
                $conn->begin_transaction();
                try {
                    $deleteCount = 0;
                    $successfully_deleted_names = []; // Store names of successfully deleted writers

                    foreach ($safeSelectedIds as $id) {
                        // First delete all contributor records that reference this writer
                        $deleteContributorsSql = "DELETE FROM contributors WHERE writer_id = $id";
                        $conn->query($deleteContributorsSql); // Assuming this won't fail critically or has FK constraints

                        // Then delete the writer
                        $deleteWriterSql = "DELETE FROM writers WHERE id = $id";
                        if ($conn->query($deleteWriterSql) && $conn->affected_rows > 0) {
                            $deleteCount++;
                            // Add name to the list if deletion was successful
                            if (isset($deleted_writers_names[$id])) {
                                $successfully_deleted_names[] = $deleted_writers_names[$id];
                            }
                        } else {
                            // Optional: Log or handle cases where deletion might fail for a specific ID
                        }
                    }
                    
                    // Commit the transaction
                    $conn->commit();
                    
                    if ($deleteCount > 0) {
                        $_SESSION['success_message'] = "$deleteCount writer(s) deleted successfully.";
                        $_SESSION['deleted_writers_names'] = $successfully_deleted_names; // Store successfully deleted names
                    } else {
                        $_SESSION['error_message'] = "Failed to delete selected writers or they were already deleted.";
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
                    <button type="button" class="btn btn-info btn-sm ml-2" data-toggle="modal" data-target="#instructionsModal">
                        <i class="fas fa-question-circle"></i> Instructions
                    </button>
                </div>
            </div>
            <div class="card-body px-0"> <!-- Remove padding for full-width scroll -->
                <div class="table-responsive px-3"> <!-- Add padding inside scroll container -->
                    <!-- Hidden form for bulk actions -->
                    <form id="bulkActionForm" method="POST" action="writers_list.php">
                        <input type="hidden" name="bulk_action" id="bulk_action">
                        <div id="selected_ids_container"></div>
                    </form>
                    
                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="text-align: center;">Select</th>
                                <th style="text-align: center;">ID</th>
                                <th style="text-align: center;">First Name</th>
                                <th style="text-align: center;">Middle Initial</th>
                                <th style="text-align: center;">Last Name</th>
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
    <div class="modal-dialog modal-lg" role="document"> <!-- Increased modal size -->
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addWriterModalLabel"><i class="fas fa-user-plus mr-2"></i>Add New Writers</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addWritersForm" method="POST" action="writers_list.php">
                    <div id="writersContainer">
                        <!-- Initial Writer Entry -->
                        <div class="writer-entry border rounded p-3 mb-3 bg-light">
                            <div class="row align-items-center">
                                <div class="col-md-4 form-group mb-md-0">
                                    <label for="firstname_0" class="form-label small text-muted">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="firstname[]" id="firstname_0" class="form-control form-control-sm" placeholder="Enter first name" required>
                                </div>
                                <div class="col-md-3 form-group mb-md-0">
                                    <label for="middle_init_0" class="form-label small text-muted">Middle Initial</label>
                                    <input type="text" name="middle_init[]" id="middle_init_0" class="form-control form-control-sm" placeholder="M.I.">
                                </div>
                                <div class="col-md-4 form-group mb-md-0">
                                    <label for="lastname_0" class="form-label small text-muted">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="lastname[]" id="lastname_0" class="form-control form-control-sm" placeholder="Enter last name" required>
                                </div>
                                <div class="col-md-1 d-flex align-items-end justify-content-center">
                                    <button type="button" class="btn btn-danger btn-sm remove-writer" title="Remove this writer">×</button>
                                </div>
                            </div>
                        </div>
                        <!-- End Initial Writer Entry -->
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="addMoreWriters"><i class="fas fa-plus mr-1"></i>Add Another Writer</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveWriters"><i class="fas fa-save mr-1"></i>Save Writers</button>
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

<!-- Instructions Modal -->
<div class="modal fade" id="instructionsModal" tabindex="-1" role="dialog" aria-labelledby="instructionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="instructionsModalLabel">
                    <i class="fas fa-info-circle mr-2"></i>Writer Management Instructions
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">Managing Writers</h6>
                    </div>
                    <div class="card-body">
                        <p>This page allows you to manage information about authors, editors, and other contributors:</p>
                        <ul>
                            <li><strong>View Writers</strong>: The table displays all writers with their personal details</li>
                            <li><strong>Add New Writer</strong>: Click the "Add Writer" button to create a new writer entry</li>
                            <li><strong>Edit Writer</strong>: Use the edit button in the action column to modify existing writer information</li>
                            <li><strong>Delete Writer</strong>: Remove a writer if needed (caution: this may affect book records)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">Writer Information</h6>
                    </div>
                    <div class="card-body">
                        <p>When adding or editing a writer, include the following information:</p>
                        <ul>
                            <li><strong>First Name</strong>: The writer's given name</li>
                            <li><strong>Middle Initial</strong>: Middle name or initial (if applicable)</li>
                            <li><strong>Last Name</strong>: The writer's family name or surname</li>
                            <li><strong>Biography</strong> (optional): Brief biographical information about the writer</li>
                            <li><strong>Specialization</strong> (optional): Primary subject areas or genres</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">Best Practices</h6>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>Always check if a writer already exists in the system before creating a new entry</li>
                            <li>Be consistent with name formats (e.g., use full first names rather than nicknames)</li>
                            <li>When adding writers with the same name, include distinguishing information</li>
                            <li>For writers who use pseudonyms, create separate entries and note the relationship</li>
                            <li>Use the search function to quickly find writers by name</li>
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
        <li class="list-group-item context-menu-item" data-action="edit"><i class="fas fa-edit mr-2"></i>Edit Writer</li>
    </ul>
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
    
    /* Enhanced hover effect for checkbox column */
    #dataTable tbody tr td:first-child:hover,
    #dataTable thead tr th:first-child:hover {
        background-color: rgba(0, 123, 255, 0.15);
        transition: background-color 0.2s ease;
    }
    
    /* Add a highlight to the entire row on hover */
    #dataTable tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    /* Make the checkbox header stand out more */
    #checkboxHeader {
        cursor: pointer;
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    #checkboxHeader:hover {
        background-color: rgba(0, 123, 255, 0.15);
    }
    
    /* Enhance alternating row colors with hover effect preservation */
    #dataTable.table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.03);
    }
    
    #dataTable.table-striped tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    /* Make header row stand out more */
    #dataTable thead th {
        background-color: #f8f9fc;
        border-bottom: 2px solid #e3e6f0;
    }

    /* Improved checkbox centering for writers table */
    #dataTable th:first-child,
    #dataTable td:first-child {
        text-align: center;
        vertical-align: middle;
        width: 40px !important;
        min-width: 40px !important;
        position: relative;
    }
    
    /* Center checkboxes better in cells */
    #dataTable input[type="checkbox"] {
        margin: 0 auto;
        display: block;
        position: relative;
        top: 0;
        left: 0;
    }
    
    /* Create wrapper for checkbox to ensure centering */
    .checkbox-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
        width: 100%;
    }
    
    /* Remove any existing table cell padding and add it to wrapper */
    #dataTable td:first-child {
        padding: 0;
    }
    
    .checkbox-wrapper {
        padding: 0.75rem;
    }

    /* Fix inconsistent column width for checkbox cells */
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
    
    /* Ensure the header checkbox cell is the same size as the data cells */
    #checkboxHeader {
        width: 40px !important;
        min-width: 40px !important;
        max-width: 40px !important;
        padding: 0.75rem 0.5rem !important;
        box-sizing: border-box;
    }
    
    /* Ensure all checkboxes are centered and sized consistently */
    #dataTable input[type="checkbox"] {
        margin: 0 auto;
        display: block;
        width: 16px;
        height: 16px;
    }
    
    /* Remove any extra padding from inside the checkbox wrapper */
    .checkbox-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
        width: 100%;
        padding: 0 !important;
    }

    /* Create highlighted row styling that overrides striped table */
    #dataTable tbody tr.selected,
    #dataTable.table-striped tbody tr.selected:nth-of-type(odd),
    #dataTable.table-striped tbody tr.selected:nth-of-type(even) {
        background-color: rgba(0, 123, 255, 0.1) !important;
    }

    /* Add Writer Modal Enhancements */
    #addWriterModal .modal-lg {
        max-width: 800px; /* Adjust width as needed */
    }
    #addWriterModal .writer-entry {
        background-color: #f8f9fc; /* Light background for each entry */
    }
    #addWriterModal .form-label {
        margin-bottom: 0.25rem; /* Reduce space below labels */
        font-weight: 500;
    }
    #addWriterModal .form-control-sm {
        height: calc(1.5em + 0.5rem + 2px); /* Adjust input height */
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    #addWriterModal .remove-writer {
        line-height: 1; /* Center the 'x' better */
        padding: 0.3rem 0.6rem;
    }
    #addWriterModal .form-group {
        margin-bottom: 0.5rem; /* Reduce space between fields */
    }
    @media (min-width: 768px) {
        #addWriterModal .form-group.mb-md-0 {
            margin-bottom: 0 !important;
        }
    }
</style>

<script>
$(document).ready(function () {
    var table = $('#dataTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, 500, -1], [10, 25, 50, 100, 500, "All"]],
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
                "className": "checkbox-cell", // Add checkbox-cell class
                "width": "40px" // Set explicit width for checkbox column
            }
        ],
        "initComplete": function() {
            $('#dataTable_filter input').addClass('form-control form-control-sm');
            $('#dataTable_filter').addClass('d-flex align-items-center');
            $('#dataTable_filter label').append('<i class="fas fa-search ml-2"></i>');
            $('.dataTables_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary mx-1');
        }
    });

    // Add a confirmation dialog when "All" option is selected
    $('#dataTable').on('length.dt', function ( e, settings, len ) {
        if (len === -1) {
            Swal.fire({
                title: 'Display All Entries?',
                text: "Are you sure you want to display all entries? This may cause performance issues.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, display all!'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    // If the user cancels, reset the page length to the previous value
                    table.page.len(settings._iDisplayLength).draw();
                }
            });
        }
    });

    // Add more writers functionality - Updated for new structure
    let writerIndex = 1; // Start index for dynamically added writers
    $('#addMoreWriters').click(function() {
        var writerEntry = `
            <div class="writer-entry border rounded p-3 mb-3 bg-light">
                <div class="row align-items-center">
                    <div class="col-md-4 form-group mb-md-0">
                        <label for="firstname_${writerIndex}" class="form-label small text-muted">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="firstname[]" id="firstname_${writerIndex}" class="form-control form-control-sm" placeholder="Enter first name" required>
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <label for="middle_init_${writerIndex}" class="form-label small text-muted">Middle Initial</label>
                        <input type="text" name="middle_init[]" id="middle_init_${writerIndex}" class="form-control form-control-sm" placeholder="M.I.">
                    </div>
                    <div class="col-md-4 form-group mb-md-0">
                        <label for="lastname_${writerIndex}" class="form-label small text-muted">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="lastname[]" id="lastname_${writerIndex}" class="form-control form-control-sm" placeholder="Enter last name" required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end justify-content-center">
                        <button type="button" class="btn btn-danger btn-sm remove-writer" title="Remove this writer">×</button>
                    </div>
                </div>
            </div>`;
        $('#writersContainer').append(writerEntry);
        writerIndex++; // Increment index for the next entry
    });

    // Remove writer functionality - Updated confirmation
    $(document).on('click', '.remove-writer', function() {
        const writerEntries = $('.writer-entry');
        if (writerEntries.length > 1) {
            $(this).closest('.writer-entry').remove();
        } else {
            // Optionally, clear the fields instead of showing an alert
            const entry = $(this).closest('.writer-entry');
            entry.find('input[type="text"]').val('');
            // alert('At least one writer entry must remain. You can clear the fields if needed.');
        }
    });

    var selectedWriterId;

    // Show context menu on right-click
    $('#dataTable tbody').on('contextmenu', 'tr', function(e) {
        e.preventDefault();
        $('#dataTable tbody tr').removeClass('context-menu-active');
        $(this).addClass('context-menu-active');
        selectedWriterId = $(this).find('td:nth-child(2)').text(); // Get writer ID
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
    $('.context-menu-item').on('click', function() {
        const action = $(this).data('action');
        if (!selectedWriterId) return;

        if (action === 'edit') {
            window.location.href = `update_writer.php?writer_id=${selectedWriterId}`;
        }

        $('#contextMenu').hide();
    });

    // Save updated writer functionality
    $('#saveUpdatedWriter').click(function() {
        $('#updateWriterForm').submit();
    });

    // Display session messages using SweetAlert2
    <?php if (isset($_SESSION['success_message'])): ?>
        <?php
        $message = addslashes($_SESSION['success_message']);
        $detailsList = '';
        // Check for added writers first
        if (isset($_SESSION['added_writers_names']) && !empty($_SESSION['added_writers_names'])) {
            $names = array_map('htmlspecialchars', $_SESSION['added_writers_names']); // Sanitize names
            $detailsList = '<br><br><strong>Added:</strong><br>' . implode('<br>', $names);
            unset($_SESSION['added_writers_names']); // Unset the added names list
        } 
        // Check for deleted writers if no added writers
        elseif (isset($_SESSION['deleted_writers_names']) && !empty($_SESSION['deleted_writers_names'])) {
            $names = array_map('htmlspecialchars', $_SESSION['deleted_writers_names']); // Sanitize names
            $detailsList = '<br><br><strong>Deleted:</strong><br>' . implode('<br>', $names);
            unset($_SESSION['deleted_writers_names']); // Unset the deleted names list
        }
        ?>
        Swal.fire({
            title: 'Success!',
            html: '<?php echo $message . $detailsList; ?>', // Use html property
            icon: 'success',
            confirmButtonColor: '#3085d6'
        });
        <?php
        unset($_SESSION['success_message']); 
        ?>
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
        // Update the visual selection state of rows
        updateRowSelectionState();
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
        // Update row selection visuals after initializing checkboxes
        updateRowSelectionState();
    }
    
    // Update the select all checkbox state
    function updateSelectAllCheckbox() {
        var allChecked = $('.row-checkbox:checked').length === $('.row-checkbox').length && $('.row-checkbox').length > 0;
        $('#selectAll').prop('checked', allChecked);
    }
    
    // Save selected IDs to session via AJAX
    function saveSelectedIds() {
        updateDeleteButton(); // Add this line
        updateRowSelectionState(); // Ensure visuals are updated whenever IDs change
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
    
    // Handle bulk actions - Updated with SweetAlert2
    $('.bulk-delete-btn').on('click', function(e) {
        e.preventDefault();
        
        if (selectedIds.length === 0) {
            Swal.fire({
                title: 'No Selection',
                text: 'Please select at least one writer to delete.',
                icon: 'warning',
                confirmButtonColor: '#ffc107'
            });
            return;
        }
        
        Swal.fire({
            title: 'Confirm Bulk Deletion',
            html: `Are you sure you want to delete <strong>${selectedIds.length}</strong> selected writer(s)?<br><br>
                   <span class="text-danger">This will also delete all contributor records for these writers. This action cannot be undone.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#selected_ids_container').empty();
                selectedIds.forEach(function(id) {
                    $('#selected_ids_container').append('<input type="hidden" name="selected_ids[]" value="' + id + '">');
                });
                $('#bulk_action').val('delete');
                $('#bulkActionForm').submit();
            }
        });
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
        // Update the visual selection state of rows
        updateRowSelectionState();
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
            firstName: $(this).find('td:eq(2)').text(),
            middleInit: $(this).find('td:eq(3)').text(),
            lastName: $(this).find('td:eq(4)').text(),
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
            window.location.href = `update_writer.php?writer_id=${contextTarget.id}`;
        }
        
        // Hide the context menu
        $('#contextMenu').hide();
        // Remove highlight from the row
        if (contextTarget && contextTarget.element) {
            $(contextTarget.element).removeClass('table-primary');
        }
        contextTarget = null; // Clear context target
    });

    // Add row selection functionality - click anywhere on row to toggle checkbox
    $('#dataTable tbody').on('click', 'tr', function(e) {
        // Ignore clicks on checkbox itself and action buttons
        if (e.target.type === 'checkbox' || $(e.target).hasClass('btn') || $(e.target).parent().hasClass('btn')) {
            return;
        }
        
        // Find the checkbox within this row and toggle it
        var checkbox = $(this).find('.row-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // Update to make entire row clickable for selection
    $('#dataTable tbody').on('click', 'td', function(e) {
        // Skip if clicking directly on the checkbox or if the cell has buttons
        if (e.target.type === 'checkbox' || $(e.target).hasClass('btn') || $(e.target).parent().hasClass('btn')) {
            return;
        }
        
        // Toggle the row's checkbox
        const checkbox = $(this).closest('tr').find('.row-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // Remove conflicting handlers to avoid multiple toggling
    $('#dataTable tbody').off('click', 'td:first-child');

    // Clear any previously set row click handlers
    $('#dataTable tbody').off('click', 'tr');
    
    // Add visual feedback for clickable rows
    $('<style>')
        .text(`
            #dataTable tbody tr {
                cursor: pointer;
            }
            #dataTable tbody tr:hover td:not(:first-child) {
                background-color: rgba(0, 123, 255, 0.05);
            }
            #dataTable tbody tr.selected {
                background-color: rgba(0, 123, 255, 0.1) !important;
            }
        `)
        .appendTo('head');

    // Update row selection visual state - completely rewritten for better handling
    function updateRowSelectionState() {
        // First, remove the selected class from all rows
        $('#dataTable tbody tr').removeClass('selected');
        
        // Then apply it only to rows where the checkbox is checked
        $('#dataTable tbody tr').each(function() {
            const checkbox = $(this).find('.row-checkbox');
            if (checkbox.length && checkbox.prop('checked')) {
                $(this).addClass('selected');
            }
        });
        
        // Update delete button state
        const count = $('.row-checkbox:checked').length;
        $('.bulk-delete-btn .badge').text(count);
        $('.bulk-delete-btn').prop('disabled', count === 0);
    }
    
    // Listen for checkbox state changes to update row selection visuals
    $('#dataTable tbody').on('change', '.row-checkbox', function() {
        // Update just this row's class based on its checkbox
        const row = $(this).closest('tr');
        if ($(this).prop('checked')) {
            row.addClass('selected');
        } else {
            row.removeClass('selected');
        }
        
        // Also update the delete button badge
        const count = $('.row-checkbox:checked').length;
        $('.bulk-delete-btn .badge').text(count);
        $('.bulk-delete-btn').prop('disabled', count === 0);
    });
    
    // Initialize row selection state
    initializeCheckboxes();
    updateRowSelectionState();

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
        
        // Re-apply row selection highlights after table redraw
        updateRowSelectionState();
    });

    // Force the table to recalculate column widths after initialization
    setTimeout(function() {
        table.columns.adjust();
    }, 100);
    
    // Ensure equal width of header and data cells
    $('#dataTable').on('draw.dt', function() {
        // Get the width of the first cell in the first row
        const firstCellWidth = $('#dataTable tbody tr:first-child td:first-child').outerWidth();
        if (firstCellWidth) {
            // Apply the same width to the header
            $('#checkboxHeader').width(firstCellWidth);
        }
    });
});
</script>