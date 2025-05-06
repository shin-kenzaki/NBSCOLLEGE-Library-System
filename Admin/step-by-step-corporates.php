<?php
session_start();
include '../db.php';

// Check if user is logged in with appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Initialize book_shortcut session if not exists
if (!isset($_SESSION['book_shortcut'])) {
    $_SESSION['book_shortcut'] = [
        'current_step' => 2,
        'selected_writers' => [],
        'selected_corporates' => [],
        'publisher_id' => null,
        'publish_year' => null,
        'book_title' => '',
        'contributor_type' => '',
        'steps_completed' => [
            'writer' => false,
            'corporate' => false,
            'publisher' => false,
            'title' => false
        ]
    ];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['corporate_ids']) && is_array($_POST['corporate_ids'])) {
        $selectedCorporates = [];
        $corporateRoles = $_POST['corporate_roles'] ?? [];

        foreach ($_POST['corporate_ids'] as $key => $corporateId) {
            if (!empty($corporateId)) {
                $role = isset($corporateRoles[$key]) ? $corporateRoles[$key] : 'Corporate Contributor';
                $selectedCorporates[] = [
                    'id' => $corporateId,
                    'role' => $role
                ];
            }
        }

        // Update the session with selected corporates
        $_SESSION['book_shortcut']['selected_corporates'] = $selectedCorporates;
        
        // Mark the corporate step as completed
        $_SESSION['book_shortcut']['steps_completed']['corporate'] = true;

        // Go to next step (publisher)
        $_SESSION['book_shortcut']['current_step'] = 3;
        header("Location: step-by-step-add-book.php");
        exit();
    }

    // Handle skipping corporates
    if (isset($_POST['skip_corporate'])) {
        $_SESSION['book_shortcut']['selected_corporates'] = [];
        $_SESSION['book_shortcut']['steps_completed']['corporate'] = true;
        $_SESSION['book_shortcut']['current_step'] = 3;
        header("Location: step-by-step-add-book.php");
        exit();
    }
}

// Clear corporate contributors if we're returning to this step
if (isset($_SESSION['return_to_form']) && $_SESSION['return_to_form']) {
    $_SESSION['book_shortcut']['selected_corporates'] = [];
}

// Get all corporate entries
$query = "SELECT id, name, type, location, description FROM corporates ORDER BY name ASC";
$result = mysqli_query($conn, $query);
$corporates = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $corporates[] = $row;
    }
}

// Add corporate roles
$corporateRoles = [
    'Corporate Author', 
    'Corporate Contributor', 
    'Publisher', 
    'Distributor', 
    'Sponsor', 
    'Funding Body', 
    'Research Institution'
];

include 'inc/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Select Corporate Contributors</h1>
    </div>

    <!-- Notification Alert -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Select Corporate Contributors for New Book</h6>
            <div>
                <a href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addCorporateModal">
                    <i class="fas fa-plus"></i> Add New Corporate Entity
                </a>
                <?php if (isset($_SESSION['book_shortcut']['contributor_type']) && $_SESSION['book_shortcut']['contributor_type'] !== 'corporate_only'): ?>
                <form method="POST" class="d-inline">
                    <button type="submit" name="skip_corporate" class="btn btn-sm btn-secondary">
                        <i class="fas fa-forward"></i> Skip This Step
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <form action="step-by-step-corporates.php" method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%">Select</th>
                                <th width="40%">Corporate Name</th>
                                <th width="20%">Type</th>
                                <th width="25%">Role</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($corporates) > 0): ?>
                                <?php foreach ($corporates as $corporate): ?>
                                    <?php
                                    $isSelected = false;
                                    $selectedRole = 'Corporate Contributor';
                                    
                                    // Check if this corporate is already selected
                                    if (!empty($_SESSION['book_shortcut']['selected_corporates'])) {
                                        foreach ($_SESSION['book_shortcut']['selected_corporates'] as $selected) {
                                            if ($selected['id'] == $corporate['id']) {
                                                $isSelected = true;
                                                $selectedRole = $selected['role'];
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input corporate-checkbox" 
                                                       id="corporate_<?= $corporate['id'] ?>" 
                                                       name="corporate_ids[]" 
                                                       value="<?= $corporate['id'] ?>" 
                                                       <?= $isSelected ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="corporate_<?= $corporate['id'] ?>"></label>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($corporate['name']) ?></td>
                                        <td><?= htmlspecialchars($corporate['type']) ?></td>
                                        <td>
                                            <select class="form-control form-control-sm" 
                                                    name="corporate_roles[]" 
                                                    <?= !$isSelected ? 'disabled' : '' ?>>
                                                <?php foreach ($corporateRoles as $role): ?>
                                                    <option value="<?= $role ?>" <?= $selectedRole === $role ? 'selected' : '' ?>>
                                                        <?= $role ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <a href="#" class="btn btn-sm btn-info edit-corporate" 
                                               data-id="<?= $corporate['id'] ?>"
                                               data-name="<?= htmlspecialchars($corporate['name']) ?>"
                                               data-type="<?= htmlspecialchars($corporate['type']) ?>"
                                               data-location="<?= htmlspecialchars($corporate['location'] ?? '') ?>"
                                               data-description="<?= htmlspecialchars($corporate['description'] ?? '') ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-danger delete-corporate" 
                                               data-id="<?= $corporate['id'] ?>"
                                               data-name="<?= htmlspecialchars($corporate['name']) ?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No corporate entities found. Please add some using the button above.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 d-flex justify-content-between">
                    <a href="step-by-step-add-book.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Step-by-Step
                    </a>
                    <button type="submit" class="btn btn-primary" id="continue-btn">
                        Continue <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<!-- Add Corporate Modal -->
<div class="modal fade" id="addCorporateModal" tabindex="-1" role="dialog" aria-labelledby="addCorporateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCorporateModalLabel">Add New Corporate Entity</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="process/add_corporate.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="corporate_name">Corporate Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="corporate_name" name="corporate_name" required>
                    </div>
                    <div class="form-group">
                        <label for="corporate_type">Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="corporate_type" name="corporate_type" required>
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
                        <label for="corporate_location">Location</label>
                        <input type="text" class="form-control" id="corporate_location" name="corporate_location">
                    </div>
                    <div class="form-group">
                        <label for="corporate_description">Description</label>
                        <textarea class="form-control" id="corporate_description" name="corporate_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Corporate Entity</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Corporate Modal -->
<div class="modal fade" id="editCorporateModal" tabindex="-1" role="dialog" aria-labelledby="editCorporateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCorporateModalLabel">Edit Corporate Entity</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="process/edit_corporate.php" method="POST">
                <input type="hidden" name="corporate_id" id="edit_corporate_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_corporate_name">Corporate Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_corporate_name" name="corporate_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_corporate_type">Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit_corporate_type" name="corporate_type" required>
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
                        <label for="edit_corporate_location">Location</label>
                        <input type="text" class="form-control" id="edit_corporate_location" name="corporate_location">
                    </div>
                    <div class="form-group">
                        <label for="edit_corporate_description">Description</label>
                        <textarea class="form-control" id="edit_corporate_description" name="corporate_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Corporate Entity</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for Dynamic Behavior -->
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#dataTable').DataTable({
            "order": [[1, "asc"]], // Sort by name column
            "pageLength": 10
        });
        
        // Handle checkbox change to enable/disable role selection
        $('.corporate-checkbox').change(function() {
            const roleSelect = $(this).closest('tr').find('select');
            if ($(this).is(':checked')) {
                roleSelect.prop('disabled', false);
            } else {
                roleSelect.prop('disabled', true);
            }
        });
        
        // Handle edit corporate button clicks
        $('.edit-corporate').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const name = $(this).data('name');
            const type = $(this).data('type');
            const location = $(this).data('location');
            const description = $(this).data('description');
            
            // Populate the edit modal
            $('#edit_corporate_id').val(id);
            $('#edit_corporate_name').val(name);
            $('#edit_corporate_type').val(type);
            $('#edit_corporate_location').val(location);
            $('#edit_corporate_description').val(description);
            
            // Show the edit modal
            $('#editCorporateModal').modal('show');
        });
        
        // Handle delete corporate button clicks
        $('.delete-corporate').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            // Confirm deletion
            if (confirm(`Are you sure you want to delete the corporate entity "${name}"?`)) {
                window.location.href = `process/delete_corporate.php?id=${id}`;
            }
        });
        
        // Form validation for continue button
        $('#continue-btn').click(function(e) {
            // If using corporate_only contributor type, require at least one corporate
            <?php if (isset($_SESSION['book_shortcut']['contributor_type']) && $_SESSION['book_shortcut']['contributor_type'] === 'corporate_only'): ?>
            const checkedCorporates = $('.corporate-checkbox:checked').length;
            if (checkedCorporates === 0) {
                e.preventDefault();
                alert('Please select at least one corporate contributor before continuing.');
            }
            <?php endif; ?>
        });

        // Fix for modal close buttons
        // Handle close button clicks for Edit Corporate Modal
        $('#editCorporateModal .close, #editCorporateModal .btn-secondary').click(function() {
            $('#editCorporateModal').modal('hide');
        });
        
        // Handle close button clicks for Add Corporate Modal
        $('#addCorporateModal .close, #addCorporateModal .btn-secondary').click(function() {
            $('#addCorporateModal').modal('hide');
        });
    });
</script>

<?php
include 'inc/footer.php';
?>
