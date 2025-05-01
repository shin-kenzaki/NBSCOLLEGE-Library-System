<?php
session_start();
include '../db.php';

// Check if user is logged in with correct privileges
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Initialize the shortcut session if not exists
if (!isset($_SESSION['book_shortcut'])) {
    header("Location: step-by-step-add-book.php");
    exit();
}

// Store the referrer information - check if we came from the form
if (!isset($_SESSION['return_to_form'])) {
    $_SESSION['return_to_form'] = (isset($_SERVER['HTTP_REFERER']) && 
                                  strpos($_SERVER['HTTP_REFERER'], 'step-by-step-add-book-form.php') !== false);
}

// Handle AJAX requests first, before any output
if (isset($_POST['ajax_select_writers'])) {
    header('Content-Type: application/json');
    
    try {
        if (!isset($_POST['selected_writers'])) {
            throw new Exception('No writers selected');
        }

        $selected_writers = isset($_POST['selected_writers']) ? $_POST['selected_writers'] : [];
        $writer_roles = isset($_POST['writer_roles']) ? $_POST['writer_roles'] : [];
        
        if (empty($selected_writers)) {
            $response['message'] = 'Please select at least one writer.';
        } else {
            // Store main author (first selected writer) in the session
            $main_author_id = reset($selected_writers);
            $_SESSION['book_shortcut']['writer_id'] = $main_author_id;
            
            // Store all selected writers and their roles
            $_SESSION['book_shortcut']['selected_writers'] = [];
            $writer_details_html = '<ul class="list-unstyled text-left">'; // Start HTML list
            
            foreach ($selected_writers as $writer_id) {
                $role = isset($writer_roles[$writer_id]) ? $writer_roles[$writer_id] : 'Author';
                $_SESSION['book_shortcut']['selected_writers'][] = [
                    'id' => $writer_id,
                    'role' => $role
                ];
                
                // Fetch writer name for the message
                $stmt = $conn->prepare("SELECT firstname, middle_init, lastname FROM writers WHERE id = ?");
                $stmt->bind_param("i", $writer_id);
                $stmt->execute();
                $writer_result = $stmt->get_result();
                if ($writer_result && $writer_result->num_rows > 0) {
                    $writer = $writer_result->fetch_assoc();
                    $writer_name = htmlspecialchars(trim($writer['firstname'] . ' ' . $writer['middle_init'] . ' ' . $writer['lastname']));
                    $writer_details_html .= '<li><span class="badge badge-'.($role == 'Author' ? 'primary' : ($role == 'Co-Author' ? 'info' : 'secondary')) . '">' . htmlspecialchars($role) . '</span> ' . $writer_name . '</li>';
                }
                $stmt->close();
            }
            $writer_details_html .= '</ul>'; // End HTML list
            
            $_SESSION['book_shortcut']['steps_completed']['writer'] = true;
            
            // Move to the next step automatically
            $_SESSION['book_shortcut']['current_step'] = 2;
            
            $response['success'] = true;
            // Update message to include HTML details
            $response['message'] = 'Writers selected successfully!';
            $response['details_html'] = $writer_details_html;
        }

        // Determine where to redirect based on session
        $redirect_page = $_SESSION['return_to_form'] ? 'step-by-step-add-book-form.php' : 'step-by-step-add-book.php';

        echo json_encode([
            'success' => true,
            'message' => $response['message'], // Use the updated message
            'details_html' => $response['details_html'] ?? '', // Pass the HTML details
            'redirect' => $redirect_page
        ]);
        
    } catch (Exception $e) {
        error_log("Writer selection error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Add CSS styles for checkbox cells using PHP
echo '<style>
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
</style>';

// Handle form submission to save writers
if (isset($_POST['save_writers'])) {
    $firstnames = $_POST['firstname'];
    $middle_inits = $_POST['middle_init'];
    $lastnames = $_POST['lastname'];
    $valid_entries = 0;
    $success = true;
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

    // Set a session flag for the alert type and message
    if ($success && $valid_entries > 0) {
        // Use the first added/selected writer for the book
        $_SESSION['book_shortcut']['writer_id'] = $selected_writer_id;
        $_SESSION['book_shortcut']['steps_completed']['writer'] = true;
        
        $_SESSION['writer_alert'] = [
            'type' => 'success',
            'message' => "$valid_entries writer(s) saved successfully"
        ];
    } elseif ($valid_entries === 0 && $selected_writer_id) {
        // Use the existing writer
        $_SESSION['book_shortcut']['writer_id'] = $selected_writer_id;
        $_SESSION['book_shortcut']['steps_completed']['writer'] = true;
        
        $_SESSION['writer_alert'] = [
            'type' => 'info',
            'message' => "Writer already exists and has been selected"
        ];
    } elseif ($valid_entries === 0) {
        $_SESSION['writer_alert'] = [
            'type' => 'warning',
            'message' => "No valid writers to save. Please provide both firstname and lastname."
        ];
    } else {
        $_SESSION['writer_alert'] = [
            'type' => 'error',
            'message' => "Failed to save writers"
        ];
    }
}

// Handle traditional form submission for non-AJAX fallback
if (isset($_POST['select_writers'])) {
    $selected_writers = isset($_POST['selected_writers']) ? $_POST['selected_writers'] : [];
    $writer_roles = isset($_POST['writer_roles']) ? $_POST['writer_roles'] : [];
    
    if (empty($selected_writers)) {
        $_SESSION['writer_alert'] = [
            'type' => 'warning',
            'message' => 'Please select at least one writer.'
        ];
    } else {
        // Store main author (first selected writer) in the session
        $main_author_id = reset($selected_writers);
        $_SESSION['book_shortcut']['writer_id'] = $main_author_id;
        
        // Store all selected writers and their roles
        $_SESSION['book_shortcut']['selected_writers'] = [];
        $writer_details_html = '<ul class="list-unstyled text-left">'; // Start HTML list
        
        foreach ($selected_writers as $writer_id) {
            $role = isset($writer_roles[$writer_id]) ? $writer_roles[$writer_id] : 'Author';
            $_SESSION['book_shortcut']['selected_writers'][] = [
                'id' => $writer_id,
                'role' => $role
            ];
            
            // Fetch writer name for the message
            $stmt = $conn->prepare("SELECT firstname, middle_init, lastname FROM writers WHERE id = ?");
            $stmt->bind_param("i", $writer_id);
            $stmt->execute();
            $writer_result = $stmt->get_result();
            if ($writer_result && $writer_result->num_rows > 0) {
                $writer = $writer_result->fetch_assoc();
                $writer_name = htmlspecialchars(trim($writer['firstname'] . ' ' . $writer['middle_init'] . ' ' . $writer['lastname']));
                $writer_details_html .= '<li><span class="badge badge-'.($role == 'Author' ? 'primary' : ($role == 'Co-Author' ? 'info' : 'secondary')) . '">' . htmlspecialchars($role) . '</span> ' . $writer_name . '</li>';
            }
            $stmt->close();
        }
        $writer_details_html .= '</ul>'; // End HTML list
        
        $_SESSION['book_shortcut']['steps_completed']['writer'] = true;
        
        // Move to the next step automatically
        $_SESSION['book_shortcut']['current_step'] = 2;
        
        // Store detailed HTML message in session alert
        $_SESSION['writer_alert'] = [
            'type' => 'success',
            'message' => 'Writers selected successfully!',
            'details_html' => $writer_details_html // Store HTML details
        ];
        
        // Redirect based on where we came from (form or progress)
        $redirect_page = $_SESSION['return_to_form'] ? 'step-by-step-add-book-form.php' : 'step-by-step-add-book.php';
        header("Location: $redirect_page");
        exit;
    }
}

// Get the search query if it exists - we'll keep this for URL parameters
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Include the header AFTER all potential redirects
include 'inc/header.php';
?>
<!-- Add SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Main Content -->
<div id="content">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Select Writers for New Book</h6>
                <button type="button" id="saveSelections" class="btn btn-primary btn-sm">
                    <i class="fas fa-save"></i> Save Selected Writers
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <a href="step-by-step-add-book.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Progress Form
                    </a>
                    <button class="btn btn-success btn-sm ml-2" data-toggle="modal" data-target="#addWriterModal">
                        <i class="fas fa-plus"></i> Add New Writer
                    </button>
                    <button type="button" id="deleteSelected" class="btn btn-danger btn-sm ml-2">
                        <i class="fas fa-trash"></i> Delete Selected Writers
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
                                    <th class="text-center checkbox-cell" width="50px">Select</th>
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
                                            <td class='text-center align-middle checkbox-cell'><input type='checkbox' name='selected_writers[]' value='{$writer['id']}' class='writer-checkbox'></td>
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
    <!-- Modal content remains unchanged -->
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addWriterModalLabel">Add Writers</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addWritersForm" method="POST" action="step-by-step-writers.php">
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
    // Display SweetAlert based on session data
    <?php if(isset($_SESSION['writer_alert'])): ?>
        Swal.fire({
            icon: '<?php echo $_SESSION['writer_alert']['type']; ?>',
            title: '<?php echo $_SESSION['writer_alert']['message']; ?>',
            // Use html property if details_html exists
            <?php if(isset($_SESSION['writer_alert']['details_html'])): ?>
            html: '<?php echo addslashes($_SESSION['writer_alert']['details_html']); ?>',
            <?php endif; ?>,
            showConfirmButton: true,
            timer: 5000 // Increased timer for detailed view
        });
        <?php 
        // Clear the alert from session after displaying
        unset($_SESSION['writer_alert']); 
        ?>
    <?php endif; ?>

    // Initialize DataTable with search enabled
    var writersTable = $('#writersTable').DataTable({
        "paging": true,
        "ordering": true,
        "info": true,
        "searching": true, // Enable searching
        "pageLength": 10,
        "columnDefs": [
            { 
                "orderable": false, 
                "targets": [0, 4], // Updated target indices
                "searchable": false, // Disable search for checkbox and role columns
                "className": "text-center", // Center align all these columns
                "createdCell": function (td, cellData, rowData, row, col) {
                    if (col === 0) { // Only apply to checkbox column
                        $(td).addClass('checkbox-cell');
                    }
                }
            }
        ],
        // Hide the default search box
        "dom": "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        "order": [[1, 'asc']] // Updated sort column index
    });
    
    // Link our custom search box to DataTables search
    $('#writerSearch').on('keyup', function() {
        writersTable.search(this.value).draw();
    });
    
    // Set initial search value if provided
    if ($('#writerSearch').val()) {
        writersTable.search($('#writerSearch').val()).draw();
    }
    
    // Remove select/unselect all functionality
    
    // Role select change handler - automatically check the checkbox when role is selected
    $('.role-select').change(function() {
        $(this).closest('tr').find('.writer-checkbox').prop('checked', true);
    });
    
    // Save selections button handler
    $('#saveSelections').click(function() {
        // Validate at least one writer is selected
        if ($('.writer-checkbox:checked').length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Selection Required',
                text: 'Please select at least one writer.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        // Use SweetAlert2 for confirmation
        Swal.fire({
            title: 'Save Selected Writers',
            text: 'Are you sure you want to save these writers and continue?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, save them!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Collect form data
                var formData = new FormData();
                formData.append('ajax_select_writers', '1');
                
                // Add selected writers
                $('.writer-checkbox:checked').each(function() {
                    formData.append('selected_writers[]', $(this).val());
                });
                
                // Add writer roles
                $('.writer-checkbox:checked').each(function() {
                    var writerID = $(this).val();
                    var role = $('select[name="writer_roles[' + writerID + ']"]').val();
                    formData.append('writer_roles[' + writerID + ']', role);
                });
                
                // Submit via AJAX
                $.ajax({
                    url: 'step-by-step-writers.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        try {
                            // Ensure response is properly parsed
                            if (typeof response === 'string') {
                                response = JSON.parse(response);
                            }
                            
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: response.message,
                                    // Use html property for detailed message
                                    html: response.details_html || '', 
                                    showConfirmButton: true, // Show the OK button
                                    confirmButtonColor: '#3085d6',
                                    confirmButtonText: 'OK'
                                }).then((result) => {
                                    // Navigate only when the OK button is clicked
                                    if (result.isConfirmed && response.redirect) { 
                                        window.location.href = response.redirect;
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'An error occurred while saving',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        } catch (e) {
                            console.error('JSON Parse Error:', e);
                            Swal.fire({
                                icon: 'error',
                                title: 'Response Error',
                                text: 'Invalid response from server',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        console.log('Response:', xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Connection Error',
                            text: 'Failed to save writers. Please check the console for details.',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                });
            }
        });
    });
    
    // Delete selected writers button handler
    $('#deleteSelected').click(function() {
        var selectedCount = $('.writer-checkbox:checked').length;
        
        if (selectedCount === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Selection Required',
                text: 'Please select at least one writer to delete.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        Swal.fire({
            title: 'Confirm Deletion',
            text: 'Are you sure you want to delete ' + selectedCount + ' selected writer(s)?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create a form to submit the selected writers for deletion
                var form = $('<form></form>').attr('method', 'post').attr('action', 'delete_writers.php');
                
                // Add each selected writer ID to the form
                $('.writer-checkbox:checked').each(function() {
                    form.append($('<input>').attr('type', 'hidden').attr('name', 'delete_writers[]').val($(this).val()));
                });
                
                // Add CSRF token or any other required fields here if needed
                
                // Append form to body, submit it, and remove it
                $('body').append(form);
                form.submit();
                form.remove();
            }
        });
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
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Remove',
                text: 'At least one writer entry must remain.',
                confirmButtonColor: '#3085d6'
            });
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
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please provide at least one writer with both firstname and lastname.',
                confirmButtonColor: '#3085d6'
            });
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
    <?php endif; ?>
});

// Make the entire checkbox cell clickable
$(document).on('click', '.checkbox-cell', function(e) {
    // Prevent triggering if clicking directly on the checkbox
    if (e.target.type !== 'checkbox') {
        const checkbox = $(this).find('input[type="checkbox"]');
        checkbox.prop('checked', !checkbox.prop('checked'));
        checkbox.trigger('change'); // Trigger change event to update the select all checkbox
    }
});
</script>

<?php include 'inc/footer.php'; ?>
