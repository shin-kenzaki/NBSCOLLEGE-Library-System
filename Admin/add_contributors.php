<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
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

?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Add Contributors</h6>
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
                                        echo "<tr>
                                            <td><input type='checkbox' class='selectWriter' name='writer_ids[]' value='{$writer['id']}'" . (in_array($writer['id'], $selectedWriterIds) ? " checked" : "") . "></td>
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

<script>
$(document).ready(function () {
    var selectedWriterIds = <?php echo json_encode(isset($_SESSION['selectedWriterIds']) ? $_SESSION['selectedWriterIds'] : []); ?>;

    // Function to update the selected writer IDs in the session
    function updateSelectedWriterIds() {
        selectedWriterIds = [];
        $('.selectWriter:checked').each(function() {
            var writerId = $(this).val();
            if (!selectedWriterIds.includes(writerId)) {
                selectedWriterIds.push(writerId);
            }
        });
        console.log(selectedWriterIds); // For debugging purposes

        // Store selected writer IDs in session
        $.post('selected_writers.php', {
            selectedWriterIds: selectedWriterIds
        }, function(response) {
            console.log(response); // For debugging purposes
        }, 'json');
    }

    // Select/Deselect all checkboxes
    $('#selectAllWriters').click(function() {
        $('.selectWriter').prop('checked', this.checked);
        updateSelectedWriterIds();
    });

    $('.selectWriter').click(function() {
        updateSelectedWriterIds();
    });

    // Handle search form submission
    $('form#searchForm').submit(function(event) {
        event.preventDefault();
        var searchQuery = $('input[name="search"]').val();
        var selectedWriterIds = $('input[name="selected_writer_ids[]"]').map(function() {
            return $(this).val();
        }).get();

        $.ajax({
            url: 'fetch_writers.php',
            type: 'GET',
            data: {
                search: searchQuery,
                selected_writer_ids: selectedWriterIds
            },
            success: function(response) {
                $('#writersTable tbody').html(response);
                updateSelectedWriterIds(); // Update the selected writer IDs after search results are loaded
            }
        });
    });

    // Restore the selected state on page load
    function restoreSelectedState() {
        $('.selectWriter').each(function() {
            var writerId = $(this).val();
            if (selectedWriterIds.includes(writerId)) {
                $(this).prop('checked', true);
            }
        });
    }

    restoreSelectedState();
});
</script>