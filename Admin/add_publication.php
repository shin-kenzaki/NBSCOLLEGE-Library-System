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

// Get the selected publisher IDs from the query parameters
$selectedPublisherIds = isset($_GET['selected_publisher_ids']) ? $_GET['selected_publisher_ids'] : [];

// Store selected publisher IDs in session
$_SESSION['selected_publisher_ids'] = $selectedPublisherIds;

// Get the current date
$currentDate = date('Y-m-d');
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Add Publication</h6>
            </div>
            <div class="card-body">
                <!-- Search Form -->
                <form method="GET" action="add_publication.php" id="searchForm">
                    <?php foreach ($bookIds as $bookId): ?>
                        <input type="hidden" name="book_ids[]" value="<?php echo htmlspecialchars($bookId); ?>">
                    <?php endforeach; ?>
                    <?php foreach ($selectedPublisherIds as $publisherId): ?>
                        <input type="hidden" name="selected_publisher_ids[]" value="<?php echo htmlspecialchars($publisherId); ?>">
                    <?php endforeach; ?>
                    <div class="input-group mb-3">
                        <input type="text" name="search" class="form-control" placeholder="Search Publishers..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </div>
                </form>
                <form method="POST" action="process_add_publication.php">
                    <?php foreach ($bookIds as $bookId): ?>
                        <input type="hidden" name="book_ids[]" value="<?php echo htmlspecialchars($bookId); ?>">
                    <?php endforeach; ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="publishersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Select</th>
                                    <th>ID</th>
                                    <th>Publisher</th>
                                    <th>Place of Publication</th>
                                    <th>Date of Publication</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch publishers from database
                                $publishersQuery = "SELECT * FROM publishers";
                                if (!empty($searchQuery)) {
                                    $publishersQuery .= " WHERE publisher LIKE '%$searchQuery%' OR place LIKE '%$searchQuery%'";
                                }
                                $publishersResult = $conn->query($publishersQuery);

                                $selectedPublishers = [];
                                if (!empty($selectedPublisherIds)) {
                                    $selectedPublishersQuery = "SELECT * FROM publishers WHERE id IN (" . implode(',', array_map('intval', $selectedPublisherIds)) . ")";
                                    $selectedPublishersResult = $conn->query($selectedPublishersQuery);
                                    while ($publisher = $selectedPublishersResult->fetch_assoc()) {
                                        $selectedPublishers[$publisher['id']] = $publisher;
                                    }
                                }

                                if ($publishersResult->num_rows > 0) {
                                    while ($publisher = $publishersResult->fetch_assoc()) {
                                        $selected = in_array($publisher['id'], $selectedPublisherIds) ? " checked" : "";
                                        echo "<tr>
                                            <td><input type='radio' class='selectPublisher' name='publisher_ids[]' value='{$publisher['id']}'{$selected}></td>
                                            <td>{$publisher['id']}</td>
                                            <td>{$publisher['publisher']}</td>
                                            <td>{$publisher['place']}</td>
                                            <td><input type='date' name='publish_dates[]' class='form-control' value='{$currentDate}' required></td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5'>No publishers found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Selected Publications</button>
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
    var selectedPublisherIds = <?php echo json_encode(isset($_SESSION['selected_publisher_ids']) ? $_SESSION['selected_publisher_ids'] : []); ?>;

    // Function to update the selected publisher IDs in the session
    function updateSelectedPublisherIds() {
        selectedPublisherIds = [];
        var publisherSelected = false;

        $('.selectPublisher:checked').each(function() {
            var publisherId = $(this).val();

            if (publisherSelected) {
                alert('Only one publisher can be selected per book.');
                $(this).prop('checked', false);
                return;
            }

            publisherSelected = true;

            if (!selectedPublisherIds.includes(publisherId)) {
                selectedPublisherIds.push(publisherId);
            }
        });

        console.log(selectedPublisherIds); // For debugging purposes

        // Store selected publisher IDs in session
        $.post('selected_publishers.php', {
            selectedPublisherIds: selectedPublisherIds
        }, function(response) {
            console.log(response); // For debugging purposes
        }, 'json');
    }

    $('.selectPublisher').click(function() {
        updateSelectedPublisherIds();
    });

    // Handle search form submission
    $('form#searchForm').submit(function(event) {
        event.preventDefault();
        var searchQuery = $('input[name="search"]').val();
        var selectedPublisherIds = $('input[name="selected_publisher_ids[]"]').map(function() {
            return $(this).val();
        }).get();

        $.ajax({
            url: 'fetch_publishers.php',
            type: 'GET',
            data: {
                search: searchQuery,
                selected_publisher_ids: selectedPublisherIds
            },
            success: function(response) {
                $('#publishersTable tbody').html(response);
                updateSelectedPublisherIds(); // Update the selected publisher IDs after search results are loaded
            }
        });
    });

    // Restore the selected state on page load
    function restoreSelectedState() {
        $('.selectPublisher').each(function() {
            var publisherId = $(this).val();
            if (selectedPublisherIds.includes(publisherId)) {
                $(this).prop('checked', true);
            }
        });
    }

    restoreSelectedState();
});
</script>