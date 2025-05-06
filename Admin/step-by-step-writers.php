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
        'current_step' => 1,
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
    if (isset($_POST['writer_ids']) && is_array($_POST['writer_ids'])) {
        $selectedWriters = [];
        $writerRoles = $_POST['writer_roles'] ?? [];

        foreach ($_POST['writer_ids'] as $key => $writerId) {
            if (!empty($writerId)) {
                $role = isset($writerRoles[$key]) ? $writerRoles[$key] : 'Author';
                $selectedWriters[] = [
                    'id' => $writerId,
                    'role' => $role
                ];
            }
        }

        // Update the session with selected writers
        $_SESSION['book_shortcut']['selected_writers'] = $selectedWriters;
        
        // Mark the writer step as completed
        $_SESSION['book_shortcut']['steps_completed']['writer'] = true;

        // Go to next step (corporate)
        $_SESSION['book_shortcut']['current_step'] = 2;
        header("Location: step-by-step-add-book.php");
        exit();
    }

    // Handle skipping writers
    if (isset($_POST['skip_writer'])) {
        $_SESSION['book_shortcut']['selected_writers'] = [];
        $_SESSION['book_shortcut']['steps_completed']['writer'] = true;
        $_SESSION['book_shortcut']['current_step'] = 2;
        header("Location: step-by-step-add-book.php");
        exit();
    }
}

// Clear writers if we're returning to this step
if (isset($_SESSION['return_to_form']) && $_SESSION['return_to_form']) {
    $_SESSION['book_shortcut']['selected_writers'] = [];
}

// Get all writers
$query = "SELECT id, CONCAT(firstname, ' ', middle_init, ' ', lastname) AS name FROM writers ORDER BY lastname ASC";
$result = mysqli_query($conn, $query);
$writers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $writers[] = $row;
    }
}

// Define writer roles
$writerRoles = [
    'Author', 
    'Co-Author', 
    'Editor', 
    'Illustrator', 
    'Translator'
];

include 'inc/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Select Writers</h1>
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
            <h6 class="m-0 font-weight-bold text-primary">Select Writers for New Book</h6>
            <div>
                <a href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addWriterModal">
                    <i class="fas fa-plus"></i> Add New Writer
                </a>
                <?php if (isset($_SESSION['book_shortcut']['contributor_type']) && $_SESSION['book_shortcut']['contributor_type'] !== 'individual_only'): ?>
                <form method="POST" class="d-inline">
                    <button type="submit" name="skip_writer" class="btn btn-sm btn-secondary">
                        <i class="fas fa-forward"></i> Skip This Step
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <form action="step-by-step-writers.php" method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%">Select</th>
                                <th width="40%">Writer Name</th>
                                <th width="25%">Role</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($writers) > 0): ?>
                                <?php foreach ($writers as $writer): ?>
                                    <?php
                                    $isSelected = false;
                                    $selectedRole = 'Author';
                                    
                                    // Check if this writer is already selected
                                    if (!empty($_SESSION['book_shortcut']['selected_writers'])) {
                                        foreach ($_SESSION['book_shortcut']['selected_writers'] as $selected) {
                                            if ($selected['id'] == $writer['id']) {
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
                                                <input type="checkbox" class="custom-control-input writer-checkbox" 
                                                       id="writer_<?= $writer['id'] ?>" 
                                                       name="writer_ids[]" 
                                                       value="<?= $writer['id'] ?>" 
                                                       <?= $isSelected ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="writer_<?= $writer['id'] ?>"></label>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($writer['name']) ?></td>
                                        <td>
                                            <select class="form-control form-control-sm" 
                                                    name="writer_roles[]" 
                                                    <?= !$isSelected ? 'disabled' : '' ?>>
                                                <?php foreach ($writerRoles as $role): ?>
                                                    <option value="<?= $role ?>" <?= $selectedRole === $role ? 'selected' : '' ?>>
                                                        <?= $role ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <a href="#" class="btn btn-sm btn-info edit-writer" 
                                               data-id="<?= $writer['id'] ?>"
                                               data-name="<?= htmlspecialchars($writer['name']) ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-danger delete-writer" 
                                               data-id="<?= $writer['id'] ?>"
                                               data-name="<?= htmlspecialchars($writer['name']) ?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No writers found. Please add some using the button above.</td>
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

<!-- Add Writer Modal -->
<div class="modal fade" id="addWriterModal" tabindex="-1" role="dialog" aria-labelledby="addWriterModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addWriterModalLabel">Add New Writer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="process/add_writer.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="firstname">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="firstname" name="firstname" required>
                    </div>
                    <div class="form-group">
                        <label for="middle_init">Middle Initial</label>
                        <input type="text" class="form-control" id="middle_init" name="middle_init">
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="lastname" name="lastname" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Writer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Writer Modal -->
<div class="modal fade" id="editWriterModal" tabindex="-1" role="dialog" aria-labelledby="editWriterModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editWriterModalLabel">Edit Writer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="process/edit_writer.php" method="POST">
                <input type="hidden" name="writer_id" id="edit_writer_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_firstname">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_firstname" name="firstname" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_middle_init">Middle Initial</label>
                        <input type="text" class="form-control" id="edit_middle_init" name="middle_init">
                    </div>
                    <div class="form-group">
                        <label for="edit_lastname">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_lastname" name="lastname" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Writer</button>
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
        $('.writer-checkbox').change(function() {
            const roleSelect = $(this).closest('tr').find('select');
            if ($(this).is(':checked')) {
                roleSelect.prop('disabled', false);
            } else {
                roleSelect.prop('disabled', true);
            }
        });
        
        // Handle edit writer button clicks
        $('.edit-writer').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            
            // Fetch writer details via AJAX
            $.ajax({
                url: 'process/get_writer.php',
                type: 'GET',
                data: {id: id},
                dataType: 'json',
                success: function(writer) {
                    // Populate the edit modal
                    $('#edit_writer_id').val(writer.id);
                    $('#edit_firstname').val(writer.firstname);
                    $('#edit_middle_init').val(writer.middle_init);
                    $('#edit_lastname').val(writer.lastname);
                    
                    // Show the edit modal
                    $('#editWriterModal').modal('show');
                },
                error: function(xhr, status, error) {
                    alert('Error fetching writer details: ' + error);
                }
            });
        });
        
        // Handle delete writer button clicks
        $('.delete-writer').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            // Confirm deletion
            if (confirm(`Are you sure you want to delete the writer "${name}"?`)) {
                window.location.href = `process/delete_writer.php?id=${id}`;
            }
        });
        
        // Form validation for continue button
        $('#continue-btn').click(function(e) {
            // If using individual_only contributor type, require at least one writer
            <?php if (isset($_SESSION['book_shortcut']['contributor_type']) && $_SESSION['book_shortcut']['contributor_type'] === 'individual_only'): ?>
            const checkedWriters = $('.writer-checkbox:checked').length;
            if (checkedWriters === 0) {
                e.preventDefault();
                alert('Please select at least one writer before continuing.');
            }
            <?php endif; ?>
        });

        // Fix for modal close buttons
        // Handle close button clicks for Edit Writer Modal
        $('#editWriterModal .close, #editWriterModal .btn-secondary').click(function() {
            $('#editWriterModal').modal('hide');
        });
        
        // Handle close button clicks for Add Writer Modal
        $('#addWriterModal .close, #addWriterModal .btn-secondary').click(function() {
            $('#addWriterModal').modal('hide');
        });
    });
</script>

<?php
include 'inc/footer.php';
?>
