<?php
session_start();
include '../db.php';
include 'inc/header.php';

// Check if user is logged in with correct privileges
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Initialize the shortcut session if not exists
if (!isset($_SESSION['book_shortcut'])) {
    header("Location: add_book_shortcut.php");
    exit();
}

// Handle form submission to save writers
if (isset($_POST['save_writers'])) {
    $firstnames = $_POST['firstname'];
    $middle_inits = $_POST['middle_init'];
    $lastnames = $_POST['lastname'];

    $success = true;
    $valid_entries = 0;
    $selected_writer_id = null;

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
            // Writer already exists, use the first one
            if (!$selected_writer_id) {
                $row = $checkResult->fetch_assoc();
                $selected_writer_id = $row['id'];
            }
            continue;
        }

        $sql = "INSERT INTO writers (firstname, middle_init, lastname) VALUES ('$firstname', '$middle_init', '$lastname')";
        if ($conn->query($sql)) {
            $valid_entries++;
            if (!$selected_writer_id) {
                $selected_writer_id = $conn->insert_id;
            }
        } else {
            $success = false;
            break;
        }
    }

    if ($success && $valid_entries > 0) {
        // Use the first added/selected writer for the book
        $_SESSION['book_shortcut']['writer_id'] = $selected_writer_id;
        $_SESSION['book_shortcut']['steps_completed']['writer'] = true;
        
        echo "<script>alert('$valid_entries writer(s) saved successfully'); window.location.href='add_book_shortcut.php';</script>";
        exit;
    } elseif ($valid_entries === 0 && $selected_writer_id) {
        // Use the existing writer
        $_SESSION['book_shortcut']['writer_id'] = $selected_writer_id;
        $_SESSION['book_shortcut']['steps_completed']['writer'] = true;
        
        echo "<script>alert('Writer already exists and has been selected'); window.location.href='add_book_shortcut.php';</script>";
        exit;
    } elseif ($valid_entries === 0) {
        echo "<script>alert('No valid writers to save. Please provide both firstname and lastname.');</script>";
    } else {
        echo "<script>alert('Failed to save writers');</script>";
    }
}

// Handle form submission to save selected writers
if (isset($_POST['select_writers'])) {
    $selected_writers = isset($_POST['selected_writers']) ? $_POST['selected_writers'] : [];
    $writer_roles = isset($_POST['writer_roles']) ? $_POST['writer_roles'] : [];
    
    if (empty($selected_writers)) {
        echo "<script>alert('Please select at least one writer.');</script>";
    } else {
        // Store main author (first selected writer) in the session
        $main_author_id = reset($selected_writers);
        $_SESSION['book_shortcut']['writer_id'] = $main_author_id;
        
        // Store all selected writers and their roles
        $_SESSION['book_shortcut']['selected_writers'] = [];
        foreach ($selected_writers as $writer_id) {
            $role = isset($writer_roles[$writer_id]) ? $writer_roles[$writer_id] : 'Author';
            $_SESSION['book_shortcut']['selected_writers'][] = [
                'id' => $writer_id,
                'role' => $role
            ];
        }
        
        $_SESSION['book_shortcut']['steps_completed']['writer'] = true;
        
        // Move to the next step automatically
        $_SESSION['book_shortcut']['current_step'] = 2;
        
        echo "<script>
            alert('Writers selected successfully'); 
            window.location.href='add_book_shortcut.php';
        </script>";
        exit;
    }
}

// Get the search query if it exists - we'll keep this for URL parameters
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
?>

<!-- Main Content -->
<div id="content">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Select Writers for New Book</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <a href="add_book_shortcut.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Progress Form
                    </a>
                    <button class="btn btn-success btn-sm ml-2" data-toggle="modal" data-target="#addWriterModal">
                        <i class="fas fa-plus"></i> Add New Writer
                    </button>
                    <button type="button" id="saveSelections" class="btn btn-primary btn-sm ml-2">
                        <i class="fas fa-save"></i> Save Selected Writers
                    </button>
                </div>

                <div class="alert alert-info">
                    <p><strong>Instructions:</strong> Select writers for your book. Use the checkboxes to select writers and choose their roles from the dropdown. The first writer you select will be considered the main author.</p>
                </div>

                <!-- Replace the search form with a simple input field -->
                <div class="form-group">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="writerSearch" class="form-control" placeholder="Search writers..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                </div>

                <form id="writersSelectionForm" method="POST">
                    <!-- Writers Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="writersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="5%"><input type="checkbox" id="selectAll"></th>
                                    <th>ID</th>
                                    <th>First Name</th>
                                    <th>Middle Initial</th>
                                    <th>Last Name</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch ALL writers from database - client-side search will filter
                                $writersQuery = "SELECT * FROM writers ORDER BY lastname, firstname";
                                $writersResult = $conn->query($writersQuery);

                                if ($writersResult->num_rows > 0) {
                                    while ($writer = $writersResult->fetch_assoc()) {
                                        echo "<tr>
                                            <td><input type='checkbox' name='selected_writers[]' value='{$writer['id']}' class='writer-checkbox'></td>
                                            <td>{$writer['id']}</td>
                                            <td>{$writer['firstname']}</td>
                                            <td>{$writer['middle_init']}</td>
                                            <td>{$writer['lastname']}</td>
                                            <td>
                                                <select name='writer_roles[{$writer['id']}]' class='form-control role-select'>
                                                    <option value='Author'>Author</option>
                                                    <option value='Co-Author'>Co-Author</option>
                                                    <option value='Editor'>Editor</option>
                                                </select>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6'>No writers found matching your search criteria</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="select_writers" value="1">
                </form>
            </div>
        </div>
    </div>
</div>

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
                <form id="addWritersForm" method="POST" action="shortcut_writers.php">
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
                    <input type="hidden" name="save_writers" value="1">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveWriters">Save Writers</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable with search enabled
    var writersTable = $('#writersTable').DataTable({
        "paging": true,
        "ordering": true,
        "info": true,
        "searching": true, // Enable searching
        "pageLength": 10,
        "columnDefs": [
            { "orderable": false, "targets": [0, 5] } // Disable sorting on checkbox and role columns
        ],
        // Hide the default search box
        "dom": "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-5'i><'col-sm-7'p>>"
    });
    
    // Link our custom search box to DataTables search
    $('#writerSearch').on('keyup', function() {
        writersTable.search(this.value).draw();
    });
    
    // Set initial search value if provided
    if ($('#writerSearch').val()) {
        writersTable.search($('#writerSearch').val()).draw();
    }
    
    // Select/Deselect all writers
    $('#selectAll').change(function() {
        $('.writer-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Update select all checkbox when individual checkboxes change
    $(document).on('change', '.writer-checkbox', function() {
        var allChecked = $('.writer-checkbox:checked').length === $('.writer-checkbox').length;
        $('#selectAll').prop('checked', allChecked);
    });
    
    // Role select change handler - automatically check the checkbox when role is selected
    $('.role-select').change(function() {
        $(this).closest('tr').find('.writer-checkbox').prop('checked', true);
    });
    
    // Save selections button handler
    $('#saveSelections').click(function() {
        // Validate at least one writer is selected
        if ($('.writer-checkbox:checked').length === 0) {
            alert('Please select at least one writer.');
            return;
        }
        
        // Submit the form
        $('#writersSelectionForm').submit();
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
    
    // Save writers button handler
    $('#saveWriters').click(function() {
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
    
    // Pre-select writers and roles if they exist in session
    <?php if (isset($_SESSION['book_shortcut']['selected_writers'])): ?>
        <?php foreach ($_SESSION['book_shortcut']['selected_writers'] as $selected): ?>
            $('input[name="selected_writers[]"][value="<?php echo $selected['id']; ?>"]').prop('checked', true);
            $('select[name="writer_roles[<?php echo $selected['id']; ?>]"]').val('<?php echo $selected['role']; ?>');
        <?php endforeach; ?>
        // Update select all checkbox state
        var allChecked = $('.writer-checkbox:checked').length === $('.writer-checkbox').length;
        $('#selectAll').prop('checked', allChecked);
    <?php endif; ?>
});
</script>

<?php include 'inc/footer.php'; ?>
