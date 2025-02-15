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

// Change current date to current year
$currentYear = date('Y');
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Add Publication</h6>
                <small class="text-muted">
                    (<?php echo count($bookIds); ?> books selected)
                </small>
            </div>
            <div class="card-body">
                <!-- Search Form -->
                <form method="GET" action="add_publication.php" id="searchForm">
                    <?php foreach ($bookIds as $bookId): ?>
                        <input type="hidden" name="book_ids[]" value="<?php echo htmlspecialchars($bookId); ?>">
                    <?php endforeach; ?>
                    <div class="input-group mb-3">
                        <input type="text" name="search" class="form-control" placeholder="Search Publishers..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </div>
                </form>
                <form method="POST" action="process_add_publication.php" id="publicationForm">
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
                                        $selected = isset($selectedPublishers[$publisher['id']]) ? " checked" : "";
                                        echo "<tr>
                                            <td><input type='radio' class='selectPublisher' name='publisher_ids[]' value='{$publisher['id']}' data-publisher-id='{$publisher['id']}'{$selected}></td>
                                            <td>{$publisher['id']}</td>
                                            <td>{$publisher['publisher']}</td>
                                            <td>{$publisher['place']}</td>
                                            <td><input type='number' name='publish_dates[{$publisher['id']}]' class='form-control' value='{$currentYear}' min='1800' max='{$currentYear}' required></td>
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
        $('.selectPublisher:checked').each(function() {
            selectedPublisherIds.push($(this).data('publisher-id').toString());
        });

        // Store selected publisher IDs in session
        $.post('selected_publishers.php', {
            selectedPublisherIds: selectedPublisherIds
        }, function(response) {
            console.log('Updated publishers:', selectedPublisherIds);
        });
    }

    // Update radio button click handler to work with cell click
    $(document).on('click', 'td:first-child', function(e) {
        // If the click was directly on the radio button, don't execute this handler
        if (e.target.type === 'radio') return;
        
        // Find the radio button within this cell and select it
        var radio = $(this).find('.selectPublisher');
        radio.prop('checked', true);
        updateSelectedPublisherIds();
    });

    // Keep the original radio button click handler for direct clicks
    $(document).on('click', '.selectPublisher', function(e) {
        // Stop propagation to prevent the td click handler from firing
        e.stopPropagation();
        updateSelectedPublisherIds();
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

    // Handle form submission
    $('#publicationForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!$('.selectPublisher:checked').length) {
            alert('Please select a publisher');
            return;
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                alert(response.message);
                if (response.success) {
                    window.location.href = 'publications_list.php';
                }
            },
            error: function() {
                alert('An error occurred while processing your request.');
            }
        });
    });
});
</script>