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

// Handle form submission for adding new publisher
if (isset($_POST['save_publisher'])) {
    $publisher = $conn->real_escape_string($_POST['publisher']);
    $place = $conn->real_escape_string($_POST['place']);
    
    // Check if publisher already exists
    $check_sql = "SELECT id FROM publishers WHERE publisher = '$publisher' AND place = '$place'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        $_SESSION['book_shortcut']['publisher_id'] = $row['id'];
        $_SESSION['book_shortcut']['steps_completed']['publisher'] = true;
        echo "<script>alert('Publisher already exists and has been selected'); window.location.href='add_book_shortcut.php';</script>";
        exit;
    }
    
    $sql = "INSERT INTO publishers (publisher, place) VALUES ('$publisher', '$place')";
    if ($conn->query($sql)) {
        $_SESSION['book_shortcut']['publisher_id'] = $conn->insert_id;
        $_SESSION['book_shortcut']['steps_completed']['publisher'] = true;
        echo "<script>alert('Publisher added successfully'); window.location.href='add_book_shortcut.php';</script>";
        exit;
    } else {
        echo "<script>alert('Failed to add publisher');</script>";
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
        
        echo "<script>alert('Publisher selected successfully'); window.location.href='add_book_shortcut.php';</script>";
        exit;
    } else {
        echo "<script>alert('Please select a publisher.');</script>";
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
            alert('Publisher and publication year saved successfully!');
            window.location.href = 'add_book_shortcut.php';
        </script>";
        exit;
    } else {
        echo "<script>alert('Please select a publisher.');</script>";
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
                <div class="alert alert-info">
                    <p><strong>Instructions:</strong> Select a publisher and set the publication year. You can either use the selected publisher immediately or save the selection for later.</p>
                </div>
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
                                    <th width="5%" class="text-center">Select</th>
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
                                            <td class='text-center align-middle'>
                                                <input type='radio' name='publisher_id' value='{$row['id']}' {$selected}>
                                            </td>
                                            <td>{$row['publisher']}</td>
                                            <td class='text-center'>{$row['place']}</td>
                                            <td class='text-center'>
                                                <input type='number' name='publish_year[{$row['id']}]' 
                                                    class='form-control mx-auto' min='1900' max='{$current_year}' 
                                                    value='" . ($selected ? ($_SESSION['book_shortcut']['publish_year'] ?? $current_year) : $current_year) . "'
                                                    style='width: 100px;'>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center'>No publishers found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Remove duplicate buttons here - the top buttons will handle all actions -->
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
                <h5 class="modal-title" id="addPublisherModalLabel">Add New Publisher</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Publisher Name</label>
                        <input type="text" class="form-control" name="publisher" required>
                    </div>
                    <div class="form-group">
                        <label>Place</label>
                        <input type="text" class="form-control" name="place" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="save_publisher" class="btn btn-primary">Save Publisher</button>
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
            { "orderable": false, "targets": [0, 3] } // Disable sorting on checkbox and year columns
        ],
        // Hide the default search box
        "dom": "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-5'i><'col-sm-7'p>>"
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
            alert('Please select a publisher.');
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

    // Remove the auto-save handler for year input fields
    // $('input[name^="publish_year"]').change(function() {
    //     if ($('input[name="publisher_id"]:checked').length) {
    //         $('#saveSelections').click();
    //     }
    // });
});
</script>

<?php include 'inc/footer.php'; ?>
