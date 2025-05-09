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
        // Ensure all IDs are integers
        $safeSelectedIds = array_map('intval', $selectedIds);
        $idsString = implode(',', $safeSelectedIds);

        // Process bulk actions
        switch ($action) {
            case 'delete':
                // Fetch details before deleting
                $deleted_publishers_details = [];
                if (!empty($idsString)) {
                    $fetchDetailsSql = "SELECT id, publisher, place FROM publishers WHERE id IN ($idsString)";
                    $detailsResult = $conn->query($fetchDetailsSql);
                    if ($detailsResult && $detailsResult->num_rows > 0) {
                        while ($row = $detailsResult->fetch_assoc()) {
                            $deleted_publishers_details[$row['id']] = $row['publisher'] . ' (' . $row['place'] . ')';
                        }
                    }
                }

                // Start transaction to ensure data integrity
                $conn->begin_transaction();
                try {
                    $deleteCount = 0;
                    $successfully_deleted_details = []; // Store details of successfully deleted publishers

                    foreach ($safeSelectedIds as $id) {
                        // First delete all publication records that reference this publisher
                        $deletePublicationsSql = "DELETE FROM publications WHERE publisher_id = $id";
                        $conn->query($deletePublicationsSql); // Assuming this won't fail critically or has FK constraints
                        
                        // Then delete the publisher
                        $deletePublisherSql = "DELETE FROM publishers WHERE id = $id";
                        if ($conn->query($deletePublisherSql) && $conn->affected_rows > 0) {
                            $deleteCount++;
                            // Add details to the list if deletion was successful
                            if (isset($deleted_publishers_details[$id])) {
                                $successfully_deleted_details[] = $deleted_publishers_details[$id];
                            }
                        } else {
                            // Optional: Log or handle cases where deletion might fail
                        }
                    }
                    
                    // Commit the transaction
                    $conn->commit();
                    
                    if ($deleteCount > 0) {
                        $_SESSION['success_message'] = "$deleteCount publisher(s) deleted successfully.";
                        $_SESSION['deleted_publishers_details'] = $successfully_deleted_details; // Store successfully deleted details
                    } else {
                        $_SESSION['error_message'] = "Failed to delete selected publishers or they were already deleted.";
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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['publisher'])) { // Check if it's the add publisher form
    $companies = $_POST['publisher'];
    $places = $_POST['place'];

    $success = true;
    $valid_entries = 0;
    $existing_combinations = [];
    $error_message = '';
    $duplicate_message = '';
    $added_publishers_details = []; // Array to store details of added publishers

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
            $duplicate_message = "Duplicate entry found in submission: '$publisher' in '$place'.";
            break; // Stop processing if duplicate in submission
        }
        $existing_combinations[] = $combination;

        // Check if the exact publisher and place combination already exists in database
        $checkSql = "SELECT * FROM publishers WHERE publisher = '$publisher' AND place = '$place'";
        $checkResult = $conn->query($checkSql);

        if ($checkResult->num_rows > 0) {
            $success = false;
            $duplicate_message = "This publisher already exists: '$publisher' in '$place'.";
            break; // Stop processing if duplicate in DB
        }

        $sql = "INSERT INTO publishers (publisher, place) VALUES ('$publisher', '$place')";
        if ($conn->query($sql)) {
            $valid_entries++;
            $added_publishers_details[] = "$publisher ($place)"; // Add details to list
        } else {
            $success = false;
            $error_message = "Database error while saving publisher: " . $conn->error;
            break; // Stop processing on database error
        }
    }

    if ($success && $valid_entries > 0) {
        $_SESSION['success_message'] = "$valid_entries publisher(s) saved successfully.";
        $_SESSION['added_publishers_details'] = $added_publishers_details; // Store details in session
    } elseif (!$success && !empty($duplicate_message)) {
        $_SESSION['error_message'] = $duplicate_message;
    } elseif ($valid_entries === 0 && empty($duplicate_message) && empty($error_message)) {
        $_SESSION['warning_message'] = 'No valid publishers to save. Please provide both publisher name and place.';
    } elseif (!$success && !empty($error_message)) {
        $_SESSION['error_message'] = "Failed to save publishers. " . $error_message;
    } else {
         $_SESSION['error_message'] = 'An unexpected error occurred while saving publishers.';
    }

    // Redirect to the same page to prevent form resubmission
    header("Location: publisher_list.php");
    exit();
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

<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Publishers List</h6>
                <div class="d-flex align-items-center">
                    <button id="deleteSelectedBtn" class="btn btn-danger btn-sm mr-2 bulk-delete-btn" disabled>
                        <i class="fas fa-trash"></i>
                        <span>Delete Selected</span>
                        <span class="badge badge-light ml-1">0</span>
                    </button>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addPublisherModal">Add Publisher</button>
                </div>
            </div>
            <div class="card-body px-0">
                <div class="table-responsive px-3">
                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="text-align: center;" id="checkboxHeader">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th style="text-align: center;">ID</th>
                                <th style="text-align: center;">Publisher</th>
                                <th style="text-align: center;">Place</th>
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
</div>
<!-- End of Main Content -->

<!-- Footer -->
<?php include '../Admin/inc/footer.php'; ?>
<!-- End of Footer -->

<!-- Add Publisher Modal -->
<div class="modal fade" id="addPublisherModal" tabindex="-1" role="dialog" aria-labelledby="addPublisherModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addPublisherModalLabel">Add New Publisher</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addPublisherForm" method="POST" action="publisher_list.php">
                    <div class="form-group">
                        <label for="publisherName">Publisher</label>
                        <input type="text" class="form-control" name="publisher[]" id="publisherName" required>
                    </div>
                    <div class="form-group">
                        <label for="publisherPlace">Place</label>
                        <input type="text" class="form-control" name="place[]" id="publisherPlace" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="savePublisher">Save Publisher</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    var selectedIds = [];

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

    // Update delete button state
    function updateDeleteButton() {
        const count = selectedIds.length;
        $('#deleteSelectedBtn .badge').text(count);
        $('#deleteSelectedBtn').prop('disabled', count === 0);
    }

    // Handle bulk delete button click
    $('#deleteSelectedBtn').on('click', function () {
        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Confirm Bulk Deletion',
            html: `Are you sure you want to delete <strong>${selectedIds.length}</strong> selected publisher(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the form with selected IDs
                const form = $('<form>', {
                    method: 'POST',
                    action: 'publisher_list.php'
                });
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'bulk_action',
                    value: 'delete'
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

    // Display session messages using SweetAlert2
    <?php if (isset($_SESSION['success_message'])): ?>
        <?php
        $message = addslashes($_SESSION['success_message']);
        $detailsList = '';
        // Check for added publishers
        if (isset($_SESSION['added_publishers_details']) && !empty($_SESSION['added_publishers_details'])) {
            $details = array_map('htmlspecialchars', $_SESSION['added_publishers_details']); // Sanitize details
            $detailsList = '<br><br><strong>Added Publishers:</strong><br>' . implode('<br>', $details);
            unset($_SESSION['added_publishers_details']); // Unset the added details list
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

    $('#dataTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "responsive": true,
        "scrollX": true,
        "order": [[1, "asc"]],
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        }
    });

    $('#savePublisher').click(function () {
        $('#addPublisherForm').submit();
    });
});
</script>