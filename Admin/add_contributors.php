<?php
session_start();

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php'; // Database connection

// Get the selected book IDs from the query parameters
$bookIds = isset($_GET['book_ids']) ? $_GET['book_ids'] : [];

// Store selected book IDs in session
$_SESSION['selected_book_ids'] = $bookIds;

// Get the search query if it exists
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Get the selected writer IDs from the query parameters
$selectedWriterIds = isset($_GET['selected_writer_ids']) ? $_GET['selected_writer_ids'] : [];

// Store selected writer IDs in session
$_SESSION['selected_writer_ids'] = $selectedWriterIds;

// After database connection, add this:
$bookDetails = [];
if (!empty($bookIds)) {
    $bookQuery = "SELECT id, title FROM books WHERE id IN (" . implode(',', array_map('intval', $bookIds)) . ")";
    $bookResult = $conn->query($bookQuery);
    while ($book = $bookResult->fetch_assoc()) {
        $bookDetails[$book['id']] = $book['title'];
    }
}

?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="m-0 font-weight-bold text-primary">Add Contributors</h6>
                        <small class="text-muted">
                            (<?php echo count($bookIds); ?> books selected)
                        </small>
                    </div>
                    <small class="text-muted" id="selectedWritersCount">(0 writers selected)</small>
                </div>
            </div>
            <div class="card-body">
                <!-- Search Form -->
                <form method="GET" action="add_contributors.php" id="searchForm">
                    <?php foreach ($bookIds as $bookId): ?>
                        <input type="hidden" name="book_ids[]" value="<?php echo htmlspecialchars($bookId); ?>">
                    <?php endforeach; ?>
                    <?php foreach ($selectedWriterIds as $writerId): ?>
                        <input type="hidden" name="selected_writer_ids[]" value="<?php echo htmlspecialchars($writerId); ?>">
                    <?php endforeach; ?>
                    <div class="input-group mb-3">
                        <input type="text" name="search" class="form-control" placeholder="Search Writers..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </div>
                </form>
                <form method="POST" action="process_add_contributors.php">
                    <?php foreach ($bookIds as $bookId): ?>
                        <input type="hidden" name="book_ids[]" value="<?php echo htmlspecialchars($bookId); ?>">
                    <?php endforeach; ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="writersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllWriters"></th>
                                    <th>ID</th>
                                    <th>First Name</th>
                                    <th>Middle Initial</th>
                                    <th>Last Name</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch writers from database
                                $writersQuery = "SELECT * FROM writers";
                                if (!empty($searchQuery)) {
                                    $writersQuery .= " WHERE firstname LIKE '%$searchQuery%' OR middle_init LIKE '%$searchQuery%' OR lastname LIKE '%$searchQuery%'";
                                }
                                $writersResult = $conn->query($writersQuery);

                                $selectedWriters = [];
                                if (!empty($selectedWriterIds)) {
                                    $selectedWritersQuery = "SELECT * FROM writers WHERE id IN (" . implode(',', array_map('intval', $selectedWriterIds)) . ")";
                                    $selectedWritersResult = $conn->query($selectedWritersQuery);
                                    while ($writer = $selectedWritersResult->fetch_assoc()) {
                                        $selectedWriters[$writer['id']] = $writer;
                                    }
                                }

                                if ($writersResult->num_rows > 0) {
                                    while ($writer = $writersResult->fetch_assoc()) {
                                        $selectedWriters[$writer['id']] = $writer;
                                    }
                                }

                                if (!empty($selectedWriters)) {
                                    foreach ($selectedWriters as $writer) {
                                        $isChecked = in_array($writer['id'], $selectedWriterIds) ? " checked" : "";
                                        echo "<tr>
                                            <td><input type='checkbox' class='selectWriter' name='writer_ids[]' value='{$writer['id']}' data-writer-id='{$writer['id']}'{$isChecked}></td>
                                            <td>{$writer['id']}</td>
                                            <td>{$writer['firstname']}</td>
                                            <td>{$writer['middle_init']}</td>
                                            <td>{$writer['lastname']}</td>
                                            <td>
                                                <select name='roles[]' class='form-control'>
                                                    <option value='Author'>Author</option>
                                                    <option value='Co-Author'>Co-Author</option>
                                                    <option value='Editor'>Editor</option>
                                                </select>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                        echo "<tr><td colspan='6'>No writers found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Selected Contributors</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Main Content -->

<!-- Footer -->
<?php include '../Admin/inc/footer.php' ?>
<!-- End of Footer -->

<!-- Add this hidden input after the search form -->
<input type="hidden" id="bookDetails" value="<?php echo htmlspecialchars(json_encode($bookDetails)); ?>">

<script>
$(document).ready(function () {
    var selectedWriterIds = <?php echo json_encode(isset($_SESSION['selected_writer_ids']) ? $_SESSION['selected_writer_ids'] : []); ?>;
    var bookDetails = JSON.parse($('#bookDetails').val());
    var isInitialLoad = true; // Add this flag

    function showAssignmentAlert() {
        // Skip alert on initial page load
        if (isInitialLoad) {
            isInitialLoad = false;
            return;
        }

        let assignments = {};
        $('.selectWriter:checked').each(function() {
            let writerName = $(this).closest('tr').find('td:eq(2)').text() + ' ' + 
                            $(this).closest('tr').find('td:eq(4)').text();
            let role = $(this).closest('tr').find('select[name="roles[]"]').val();
            
            if (!assignments[writerName]) {
                assignments[writerName] = {};
            }
            if (!assignments[writerName][role]) {
                assignments[writerName][role] = [];
            }
            assignments[writerName][role] = Object.keys(bookDetails);
        });

        let message = "Assignment Summary:\n\n";
        for (let writer in assignments) {
            for (let role in assignments[writer]) {
                message += `Assigned "${writer}" to ${assignments[writer][role].length} books as ${role}\n`;
                message += `Books: ${assignments[writer][role].map(id => bookDetails[id]).join(', ')}\n\n`;
            }
        }
        
        alert(message);
    }

    // Function to update the selected writer IDs in the session
    function updateSelectedWriterIds() {
        selectedWriterIds = [];
        var authorSelected = false;

        $('.selectWriter:checked').each(function() {
            var writerId = $(this).data('writer-id').toString();
            var role = $(this).closest('tr').find('select[name="roles[]"]').val();

            if (role === 'Author') {
                if (authorSelected) {
                    alert('Only one author can be selected per book.');
                    $(this).prop('checked', false);
                    return false;
                }
                authorSelected = true;
            }

            selectedWriterIds.push(writerId);
        });

        // Update the counter display immediately
        $('#selectedWritersCount').text(`(${selectedWriterIds.length} writers selected)`);

        // Store selected writer IDs in session
        $.post('selected_writers.php', {
            selectedWriterIds: selectedWriterIds
        });

        // Update select all checkbox state
        var totalWriters = $('.selectWriter').length;
        var selectedWriters = $('.selectWriter:checked').length;
        $('#selectAllWriters').prop('checked', totalWriters > 0 && totalWriters === selectedWriters);

        // Only show alert if it's not the initial load
        if (!isInitialLoad) {
            showAssignmentAlert();
        }
    }

    // Select/Deselect all checkboxes
    $('#selectAllWriters').click(function() {
        if(this.checked) {
            var canSelectAll = true;
            var authorCount = 0;
            $('.selectWriter').each(function() {
                var role = $(this).closest('tr').find('select[name="roles[]"]').val();
                if(role === 'Author') authorCount++;
                if(authorCount > 1) {
                    canSelectAll = false;
                    return false;
                }
            });

            if(!canSelectAll) {
                alert('Cannot select all - multiple authors detected');
                this.checked = false;
                return;
            }
        }
        $('.selectWriter').prop('checked', this.checked);
        updateSelectedWriterIds();
    });

    // Handle individual checkbox changes
    $(document).on('change', '.selectWriter', function() {
        updateSelectedWriterIds();
    });

    // Handle role changes
    $(document).on('change', 'select[name="roles[]"]', function() {
        updateSelectedWriterIds();
    });

    // Restore the selected state on page load
    function restoreSelectedState() {
        $('.selectWriter').each(function() {
            var writerId = $(this).data('writer-id').toString();
            if (selectedWriterIds.includes(writerId)) {
                $(this).prop('checked', true);
            }
        });
        updateSelectedWriterIds();
    }

    // Initialize selections
    restoreSelectedState();

    // Update checkbox click handler to work with cell click
    $(document).on('click', 'td:first-child', function(e) {
        // If the click was directly on the checkbox, don't execute this handler
        if (e.target.type === 'checkbox') return;
        
        // Find the checkbox within this cell and toggle it
        var checkbox = $(this).find('.selectWriter');
        
        // Check if selecting would create multiple authors
        if (!checkbox.prop('checked')) {
            var role = $(this).closest('tr').find('select[name="roles[]"]').val();
            if (role === 'Author') {
                var authorCount = $('.selectWriter:checked').filter(function() {
                    return $(this).closest('tr').find('select[name="roles[]"]').val() === 'Author';
                }).length;
                if (authorCount >= 1) {
                    alert('Only one author can be selected per book.');
                    return;
                }
            }
        }
        
        checkbox.prop('checked', !checkbox.prop('checked'));
        updateSelectedWriterIds();
    });

    // Keep the original checkbox click handler for direct checkbox clicks
    $(document).on('click', '.selectWriter', function(e) {
        // Stop propagation to prevent the td click handler from firing
        e.stopPropagation();
        updateSelectedWriterIds();
    });

    // Update header cell click handler
    $(document).on('click', 'thead th:first-child', function(e) {
        // If the click was directly on the checkbox, don't execute this handler
        if (e.target.type === 'checkbox') return;
        
        // Find and click the checkbox
        var checkbox = $('#selectAllWriters');
        checkbox.prop('checked', !checkbox.prop('checked'));
        
        if(checkbox.prop('checked')) {
            var canSelectAll = true;
            var authorCount = 0;
            $('.selectWriter').each(function() {
                var role = $(this).closest('tr').find('select[name="roles[]"]').val();
                if(role === 'Author') authorCount++;
                if(authorCount > 1) {
                    canSelectAll = false;
                    return false;
                }
            });

            if(!canSelectAll) {
                alert('Cannot select all - multiple authors detected');
                checkbox.prop('checked', false);
                return;
            }
        }
        $('.selectWriter').prop('checked', checkbox.prop('checked'));
        updateSelectedWriterIds();
    });
});
</script>