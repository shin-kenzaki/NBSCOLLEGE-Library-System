<?php
session_start();
include '../db.php';
include 'inc/header.php';

// Check if user is logged in with correct privileges
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Add CSS styles for checkbox and radio cells using PHP
echo '<style>
    .checkbox-cell, .radio-cell {
        cursor: pointer;
        text-align: center;
        vertical-align: middle;
        width: 50px !important; /* Fixed width for uniformity */
    }
    .checkbox-cell:hover, .radio-cell:hover {
        background-color: rgba(0, 123, 255, 0.1);
    }
    .checkbox-cell input[type="checkbox"], .radio-cell input[type="radio"] {
        margin: 0 auto;
        display: block;
    }
</style>';

// Handle form submission for adding new publishers
if (isset($_POST['save_publishers'])) {
    $publishers = $_POST['publisher'] ?? [];
    $places = $_POST['place'] ?? [];
    
    $success = true;
    $valid_entries = 0;
    $selected_publisher_id = null;
    
    for ($i = 0; $i < count($publishers); $i++) {
        $publisher = trim($conn->real_escape_string($publishers[$i]));
        $place = trim($conn->real_escape_string($places[$i]));
        
        // Skip entries without publisher or place
        if (empty($publisher) || empty($place)) {
            continue;
        }
        
        // Check if the publisher already exists
        $check_sql = "SELECT id FROM publishers WHERE publisher = '$publisher' AND place = '$place'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            // Publisher already exists, use the first one
            if (!$selected_publisher_id) {
                $row = $check_result->fetch_assoc();
                $selected_publisher_id = $row['id'];
            }
            continue;
        }
        
        $sql = "INSERT INTO publishers (publisher, place) VALUES ('$publisher', '$place')";
        if ($conn->query($sql)) {
            $valid_entries++;
            if (!$selected_publisher_id) {
                $selected_publisher_id = $conn->insert_id;
            }
        } else {
            $success = false;
            break;
        }
    }
    
    if ($success && $valid_entries > 0) {
        // Use the first added/selected publisher for the book
        $_SESSION['book_shortcut']['publisher_id'] = $selected_publisher_id;
        $_SESSION['book_shortcut']['steps_completed']['publisher'] = true;
        
        echo "<script>
            Swal.fire({
                title: 'Success!',
                text: '$valid_entries publisher(s) added successfully',
                icon: 'success',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        </script>";
    } elseif ($valid_entries === 0 && $selected_publisher_id) {
        // Use the existing publisher
        $_SESSION['book_shortcut']['publisher_id'] = $selected_publisher_id;
        $_SESSION['book_shortcut']['steps_completed']['publisher'] = true;
        
        echo "<script>
            Swal.fire({
                title: 'Publisher Already Exists',
                text: 'This publisher has been selected for your book',
                icon: 'info',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        </script>";
    } elseif ($valid_entries === 0) {
        echo "<script>
            Swal.fire({
                title: 'Warning',
                text: 'No valid publishers to save. Please provide both publisher name and place.',
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                title: 'Error!',
                text: 'Failed to add publishers',
                icon: 'error',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        </script>";
    }
}

// Handle form submission for selecting existing publisher
if (isset($_POST['select_publisher'])) {
    if (!empty($_POST['publisher_id'])) {
        $publisher_id = (int)$_POST['publisher_id'];
        $publish_year = isset($_POST['publish_year'][$publisher_id]) ? (int)$_POST['publish_year'][$publisher_id] : date('Y');
        
        $_SESSION['book_shortcut']['publisher_id'] = $publisher_id;
        $_SESSION['book_shortcut']['publish_year'] = $publish_year;
        $_SESSION['book_shortcut']['steps_completed']['publisher'] = true;
        
        echo "<script>
            Swal.fire({
                title: 'Success!',
                text: 'Publisher selected successfully',
                icon: 'success',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                title: 'Warning',
                text: 'Please select a publisher.',
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        </script>";
    }
}

// Add this after the existing form submission handlers:
if (isset($_POST['save_selection'])) {
    if (!empty($_POST['publisher_id'])) {
        $publisher_id = (int)$_POST['publisher_id'];
        $publish_year = isset($_POST['publish_year'][$publisher_id]) ? (int)$_POST['publish_year'][$publisher_id] : date('Y');
        
        $_SESSION['book_shortcut']['publisher_id'] = $publisher_id;
        $_SESSION['book_shortcut']['publish_year'] = $publish_year;
        $_SESSION['book_shortcut']['steps_completed']['publisher'] = true;
        
        // Move to the next step automatically
        $_SESSION['book_shortcut']['current_step'] = 3;
        
        echo "<script>
            Swal.fire({
                title: 'Success!',
                text: 'Publisher and publication year saved successfully!',
                icon: 'success',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href='add_book_shortcut.php';
                }
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                title: 'Warning',
                text: 'Please select a publisher.',
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        </script>";
    }
}

// Add this after your existing form submission handlers:

// Handle publisher deletion
if (isset($_POST['delete_publishers'])) {
    $publishers = $_POST['delete_publishers'] ?? [];
    $deleted = 0;
    
    if (!empty($publishers)) {
        foreach ($publishers as $publisher_id) {
            $publisher_id = (int)$publisher_id;
            
            // Check if the publisher is in use by any books in the publications table
            $check_sql = "SELECT COUNT(*) as pub_count FROM publications WHERE publisher_id = $publisher_id";
            $check_result = $conn->query($check_sql);
            
            if (!$check_result) {
                // Query failed, skip this publisher to be safe
                continue;
            }
            
            $row = $check_result->fetch_assoc();
            
            if ($row['pub_count'] > 0) {
                // Publisher is in use, cannot delete
                continue;
            }
            
            // Publisher not in use, can delete
            $delete_sql = "DELETE FROM publishers WHERE id = $publisher_id";
            if ($conn->query($delete_sql)) {
                $deleted++;
            }
        }
        
        if ($deleted > 0) {
            echo "<script>
                Swal.fire({
                    title: 'Success!',
                    text: '$deleted publisher(s) deleted successfully',
                    icon: 'success',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            </script>";
        } else {
            echo "<script>
                Swal.fire({
                    title: 'Warning',
                    text: 'Could not delete publishers. They may be in use by existing books.',
                    icon: 'warning',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
    }
}

// Get the search query if it exists - keep for URL parameter compatibility
$searchQuery = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
?>

<!-- Main Content -->
<div id="content">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Select Publisher for New Book</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <a href="add_book_shortcut.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Progress Form
                    </a>
                    <button class="btn btn-success btn-sm ml-2" data-toggle="modal" data-target="#addPublisherModal">
                        <i class="fas fa-plus"></i> Add New Publisher
                    </button>
                    <button type="button" id="saveSelections" class="btn btn-primary btn-sm ml-2">
                        <i class="fas fa-save"></i> Save Selected Publisher
                    </button>
                    <button type="button" id="deleteSelected" class="btn btn-danger btn-sm ml-2">
                        <i class="fas fa-trash"></i> Delete Selected Publishers
                    </button>
                </div>

                <div class="alert alert-info">
                    <p><strong>Instructions:</strong> Select a publisher and set the publication year. You can either use the selected publisher immediately or save the selection for later.</p>
                </div>

                <!-- Replace search form with real-time search input -->
                <div class="form-group">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="publisherSearch" class="form-control" placeholder="Search publishers..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                </div>

                <!-- Publishers Table -->
                <form method="POST" id="selectPublisherForm">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="publishersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="text-center checkbox-cell" width="50px"><input type="checkbox" id="selectAll"></th>
                                    <th class="text-center radio-cell" width="50px">Select</th>
                                    <th class="text-center">Publisher</th>
                                    <th class="text-center">Place</th>
                                    <th class="text-center">Year of Publication</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch ALL publishers from database - client-side search will filter
                                $query = "SELECT * FROM publishers ORDER BY publisher";
                                $result = $conn->query($query);

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $selected = (isset($_SESSION['book_shortcut']['publisher_id']) && $_SESSION['book_shortcut']['publisher_id'] == $row['id']) ? 'checked' : '';
                                        $current_year = date('Y');
                                        echo "<tr>
                                            <td class='text-center align-middle checkbox-cell' data-id='{$row['id']}'>
                                                <input type='checkbox' name='publisher_ids[]' value='{$row['id']}' class='publisher-checkbox'>
                                            </td>
                                            <td class='text-center align-middle radio-cell' data-id='{$row['id']}'>
                                                <input type='radio' name='publisher_id' value='{$row['id']}' {$selected}>
                                            </td>
                                            <td>{$row['publisher']}</td>
                                            <td class='text-center'>{$row['place']}</td>
                                            <td class='text-center'>
                                                <input type='number' name='publish_year[{$row['id']}]' 
                                                    class='form-control mx-auto' min='1900' max='{$current_year}' 
                                                    value='" . ($selected ? ($_SESSION['book_shortcut']['publish_year'] ?? $current_year) : $current_year) . "'>
                                            </td>
                                        </tr>";
                                    }
                                                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
            <form id="addPublishersForm" method="POST">
                <div class="modal-body">
                    <div id="publishersContainer">
                        <div class="publisher-entry mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="form-group">
                                        <label>Publisher Name</label>
                                        <input type="text" class="form-control" name="publisher[]" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Place</label>
                                        <input type="text" class="form-control" name="place[]" required>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-danger ml-2 remove-publisher" style="height: 38px;">×</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" id="addMorePublishers">Add More Publishers</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="save_publishers" class="btn btn-primary">Save Publishers</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable with search enabled
    var publishersTable = $('#publishersTable').DataTable({
        "pageLength": 10,
        "searching": true,  // Enable searching
        "ordering": true,   // Enable column sorting
        "info": true,       // Show info (Showing X of Y entries)
        "paging": true,     // Enable pagination
        "columnDefs": [
            { "orderable": false, "targets": [0, 1, 4], "searchable": false } // Disable sorting on checkbox, radio, and year columns
        ],
        // Hide the default search box
        "dom": "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        "order": [[2, 'asc']] // Default sort by publisher name (column 2)
    });
    
    // Link our custom search box to DataTables search
    $('#publisherSearch').on('keyup', function() {
        publishersTable.search(this.value).draw();
    });
    
    // Set initial search value if provided
    if ($('#publisherSearch').val()) {
        publishersTable.search($('#publisherSearch').val()).draw();
    }

    // Save selections button handler
    $('#saveSelections').click(function() {
        // Validate at least one publisher is selected
        if (!$('input[name="publisher_id"]:checked').length) {
            Swal.fire({
                title: 'Warning',
                text: 'Please select a publisher.',
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        // Submit the form with save_selection
        $('<input>').attr({
            type: 'hidden',
            name: 'save_selection',
            value: '1'
        }).appendTo('#selectPublisherForm');
        
        $('#selectPublisherForm').submit();
    });

    // Remove the handler to automatically save when year is changed
    // $('input[name^="publish_year"]').change(function() {
    //     if ($('input[name="publisher_id"]:checked').length) {
    //         $('#saveSelections').click();
    //     }
    // });

    // Add this to your existing JavaScript block

    // Select/Deselect all publishers
    $('#selectAll').change(function() {
        $('.publisher-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Update select all checkbox when individual checkboxes change
    $(document).on('change', '.publisher-checkbox', function() {
        var allChecked = $('.publisher-checkbox:checked').length === $('.publisher-checkbox').length;
        $('#selectAll').prop('checked', allChecked);
    });
    
    // Delete selected publishers button handler
    $('#deleteSelected').click(function() {
        var selectedCount = $('.publisher-checkbox:checked').length;
        
        if (selectedCount === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Selection Required',
                text: 'Please select at least one publisher to delete.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        Swal.fire({
            title: 'Confirm Deletion',
            text: 'Are you sure you want to delete ' + selectedCount + ' selected publisher(s)?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create a form to submit the selected publishers for deletion
                var form = $('<form></form>').attr('method', 'post').attr('action', '');
                
                // Add each selected publisher ID to the form
                $('.publisher-checkbox:checked').each(function() {
                    form.append($('<input>').attr('type', 'hidden').attr('name', 'delete_publishers[]').val($(this).val()));
                });
                
                // Append form to body, submit it, and remove it
                $('body').append(form);
                form.submit();
                form.remove();
            }
        });
    });

    // Add more publishers functionality
    $('#addMorePublishers').click(function() {
        var publisherEntry = `
            <div class="publisher-entry mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="form-group">
                            <label>Publisher Name</label>
                            <input type="text" class="form-control" name="publisher[]" required>
                        </div>
                        <div class="form-group">
                            <label>Place</label>
                            <input type="text" class="form-control" name="place[]" required>
                        </div>
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
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Remove',
                text: 'At least one publisher entry must remain.',
                confirmButtonColor: '#3085d6'
            });
        }
    });
    
    // Add this to your existing JavaScript block

    // Make the entire checkbox cell clickable
    $(document).on('click', '.checkbox-cell', function(e) {
        // Prevent triggering if clicking directly on the checkbox
        if (e.target.type !== 'checkbox') {
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            checkbox.trigger('change'); // Trigger change event to update the select all checkbox
        }
    });

    // Make the entire radio cell clickable
    $(document).on('click', '.radio-cell', function(e) {
        // Prevent triggering if clicking directly on the radio
        if (e.target.type !== 'radio') {
            $(this).find('input[type="radio"]').prop('checked', true);
        }
    });
});
</script>

<?php include 'inc/footer.php'; ?>
