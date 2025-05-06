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
        'current_step' => 3,
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
    if (isset($_POST['publisher_id']) && isset($_POST['publish_year'])) {
        $publisherId = intval($_POST['publisher_id']);
        $publishYear = $_POST['publish_year'];
        
        // Basic validation
        $errors = [];
        
        if (empty($publisherId)) {
            $errors[] = "Please select a publisher.";
        }
        
        if (empty($publishYear)) {
            $errors[] = "Please enter publication year.";
        } else if (!preg_match('/^\d{4}$/', $publishYear) || $publishYear < 1000 || $publishYear > date('Y') + 1) {
            $errors[] = "Please enter a valid 4-digit year (not greater than next year).";
        }
        
        if (empty($errors)) {
            // Update the session with selected publisher
            $_SESSION['book_shortcut']['publisher_id'] = $publisherId;
            $_SESSION['book_shortcut']['publish_year'] = $publishYear;
            
            // Mark the publisher step as completed
            $_SESSION['book_shortcut']['steps_completed']['publisher'] = true;

            // Go to next step (title)
            $_SESSION['book_shortcut']['current_step'] = 4;
            header("Location: step-by-step-add-book.php");
            exit();
        } else {
            // Store errors in session for display
            $_SESSION['message'] = implode('<br>', $errors);
            $_SESSION['message_type'] = "danger";
        }
    }
}

// Get all publishers
$query = "SELECT id, publisher, place FROM publishers ORDER BY publisher ASC";
$result = mysqli_query($conn, $query);
$publishers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $publishers[] = $row;
    }
}

// Get selected publisher and year from session (if exists)
$selectedPublisherId = $_SESSION['book_shortcut']['publisher_id'] ?? null;
$selectedPublishYear = $_SESSION['book_shortcut']['publish_year'] ?? '';

// Only include header after all potential redirects
include 'inc/header.php';

// Helper function to get publisher place from ID
function getPublisherPlace($publishers, $publisherId) {
    foreach ($publishers as $publisher) {
        if ($publisher['id'] == $publisherId) {
            return $publisher['place'];
        }
    }
    return '';
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Select Publisher</h1>
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
            <h6 class="m-0 font-weight-bold text-primary">Select Publisher for New Book</h6>
            <div>
                <a href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addPublisherModal">
                    <i class="fas fa-plus"></i> Add New Publisher
                </a>
            </div>
        </div>
        <div class="card-body">
            <form action="step-by-step-publishers.php" method="POST">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="publisher_id">Publisher <span class="text-danger">*</span></label>
                            <select class="form-control" id="publisher_id" name="publisher_id" required>
                                <option value="">Select Publisher</option>
                                <?php foreach ($publishers as $publisher): ?>
                                    <option value="<?= $publisher['id'] ?>" 
                                            data-place="<?= htmlspecialchars($publisher['place']) ?>"
                                            <?= $selectedPublisherId == $publisher['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($publisher['publisher']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="publish_year">Publication Year <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="publish_year" name="publish_year" 
                                   placeholder="YYYY" maxlength="4" pattern="[0-9]{4}" required
                                   value="<?= htmlspecialchars($selectedPublishYear) ?>">
                            <small class="text-muted">Enter 4-digit year (e.g., 2023)</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Place</label>
                            <div class="form-control-plaintext" id="publisher_place">
                                <?= !empty($selectedPublisherId) ? htmlspecialchars(getPublisherPlace($publishers, $selectedPublisherId)) : 'Not selected' ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Publisher List -->
                <div class="table-responsive">
                    <h5 class="mb-3">Available Publishers:</h5>
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%">Select</th>
                                <th width="45%">Publisher Name</th>
                                <th width="30%">Location</th>
                                <th width="20%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($publishers) > 0): ?>
                                <?php foreach ($publishers as $publisher): ?>
                                    <tr>
                                        <td class="text-center">
                                            <div class="custom-control custom-radio">
                                                <input type="radio" class="custom-control-input publisher-radio" 
                                                       id="publisher_<?= $publisher['id'] ?>" 
                                                       name="publisher_selection" 
                                                       value="<?= $publisher['id'] ?>"
                                                       data-name="<?= htmlspecialchars($publisher['publisher']) ?>"
                                                       data-place="<?= htmlspecialchars($publisher['place']) ?>"
                                                       <?= $selectedPublisherId == $publisher['id'] ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="publisher_<?= $publisher['id'] ?>"></label>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($publisher['publisher']) ?></td>
                                        <td><?= htmlspecialchars($publisher['place']) ?></td>
                                        <td class="text-center">
                                            <a href="#" class="btn btn-sm btn-info edit-publisher" 
                                               data-id="<?= $publisher['id'] ?>"
                                               data-name="<?= htmlspecialchars($publisher['publisher']) ?>"
                                               data-place="<?= htmlspecialchars($publisher['place']) ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-danger delete-publisher" 
                                               data-id="<?= $publisher['id'] ?>"
                                               data-name="<?= htmlspecialchars($publisher['publisher']) ?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No publishers found. Please add some using the button above.</td>
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

<!-- Add Publisher Modal -->
<div class="modal fade" id="addPublisherModal" tabindex="-1" role="dialog" aria-labelledby="addPublisherModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPublisherModalLabel">Add New Publisher</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="process/add_publisher.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="publisher_name">Publisher Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="publisher_name" name="publisher_name" required>
                    </div>
                    <div class="form-group">
                        <label for="publisher_place">Place <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="publisher_place_input" name="publisher_place" required>
                        <small class="text-muted">Example: New York, United States</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Publisher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Publisher Modal -->
<div class="modal fade" id="editPublisherModal" tabindex="-1" role="dialog" aria-labelledby="editPublisherModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPublisherModalLabel">Edit Publisher</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="process/edit_publisher.php" method="POST">
                <input type="hidden" name="publisher_id" id="edit_publisher_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_publisher_name">Publisher Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_publisher_name" name="publisher_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_publisher_place">Place <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_publisher_place" name="publisher_place" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Publisher</button>
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
            "order": [[1, "asc"]], // Sort by publisher name column
            "pageLength": 10
        });
        
        // Update publisher selection when radio button is clicked
        $('.publisher-radio').change(function() {
            const publisherId = $(this).val();
            const publisherPlace = $(this).data('place');
            
            $('#publisher_id').val(publisherId);
            $('#publisher_place').text(publisherPlace);
        });
        
        // Update publisher place display when dropdown changes
        $('#publisher_id').change(function() {
            const selectedOption = $(this).find('option:selected');
            const place = selectedOption.data('place') || 'Not selected';
            
            $('#publisher_place').text(place);
            
            // Update the corresponding radio button
            const publisherId = $(this).val();
            if (publisherId) {
                $(`#publisher_${publisherId}`).prop('checked', true);
            } else {
                $('.publisher-radio').prop('checked', false);
            }
        });
        
        // Handle edit publisher button clicks
        $('.edit-publisher').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const name = $(this).data('name');
            const place = $(this).data('place');
            
            // Populate the edit modal
            $('#edit_publisher_id').val(id);
            $('#edit_publisher_name').val(name);
            $('#edit_publisher_place').val(place);
            
            // Show the edit modal
            $('#editPublisherModal').modal('show');
        });
        
        // Handle delete publisher button clicks
        $('.delete-publisher').click(function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            // Confirm deletion
            if (confirm(`Are you sure you want to delete the publisher "${name}"?`)) {
                window.location.href = `process/delete_publisher.php?id=${id}`;
            }
        });
        
        // Form validation for continue button
        $('#continue-btn').click(function(e) {
            const publisherId = $('#publisher_id').val();
            const publishYear = $('#publish_year').val();
            
            if (!publisherId) {
                e.preventDefault();
                alert('Please select a publisher before continuing.');
                return false;
            }
            
            if (!publishYear || !publishYear.match(/^\d{4}$/)) {
                e.preventDefault();
                alert('Please enter a valid 4-digit publication year.');
                return false;
            }
            
            const year = parseInt(publishYear);
            const currentYear = new Date().getFullYear();
            
            if (year < 1000 || year > currentYear + 1) {
                e.preventDefault();
                alert(`Publication year must be a valid year (not greater than ${currentYear + 1}).`);
                return false;
            }
        });
        
        // Auto-focus publish year when publisher is selected
        $('#publisher_id').change(function() {
            if ($(this).val()) {
                $('#publish_year').focus();
            }
        });

        // Fix for modal close buttons
        // Handle close button clicks for Edit Publisher Modal
        $('#editPublisherModal .close, #editPublisherModal .btn-secondary').click(function() {
            $('#editPublisherModal').modal('hide');
        });
        
        // Handle close button clicks for Add Publisher Modal
        $('#addPublisherModal .close, #addPublisherModal .btn-secondary').click(function() {
            $('#addPublisherModal').modal('hide');
        });
    });
</script>

<?php
include 'inc/footer.php';
?>
