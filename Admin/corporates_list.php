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
            foreach ($selectedIds as $id) {
                // Delete the corporate
                $deleteCorporateSql = "DELETE FROM corporates WHERE id = $id";
                $conn->query($deleteCorporateSql);
            }
            $conn->commit();
            $_SESSION['success_message'] = count($selectedIds) . " corporate(s) deleted successfully.";
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
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Corporates List</h6>
                <div class="d-flex align-items-center">
                    <button id="deleteSelectedBtn" class="btn btn-danger btn-sm mr-2 bulk-delete-btn" disabled>
                        <i class="fas fa-trash"></i>
                        <span>Delete Selected</span>
                        <span class="badge badge-light ml-1">0</span>
                    </button>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addCorporateModal">Add Corporate</button>
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
                                    echo "<tr>
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

<!-- Footer -->
<?php include '../Admin/inc/footer.php'; ?>
<!-- End of Footer -->

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
            html: `Are you sure you want to delete <strong>${selectedIds.length}</strong> selected corporate(s)?`,
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

    // Display session messages using SweetAlert2
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            title: 'Success!',
            text: '<?php echo addslashes($_SESSION['success_message']); ?>',
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

    $('#saveCorporate').click(function () {
        $('#addCorporateForm').submit();
    });
});
</script>