<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php'; // Database connection

// Get the search query if it exists
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Book List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <!-- Search Form -->
                    <form method="GET" action="book_list.php" id="searchForm">
                        <div class="input-group mb-3">
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div> 
                        </div>
                        <!-- Add Contributors Icons -->
                        <div id="addContributorsIcons" class="d-none">
                            <button class="btn btn-success btn-sm mx-1" id="addContributorsPerson"><i class="fas fa-user-plus"></i> Add Contributors</button>
                            <button class="btn btn-success btn-sm mx-1"><i class="fas fa-building"></i> Add Publisher</button>
                        </div>
                    </form>
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Accession</th>
                                <th>Title</th>
                                <th>Preferred Title</th>
                                <th>Parallel Title</th>
                                <th>Front Image</th>
                                <th>Back Image</th>
                                <th>Height</th>
                                <th>Width</th>
                                <th>Total Pages</th>
                                <th>Call Number</th>
                                <th>Copy Number</th>
                                <th>Language</th>
                                <th>Shelf Location</th>
                                <th>Entered By</th>
                                <th>Date Added</th>
                                <th>Status</th>
                                <th>Last Update</th>
                                <th>Series</th>
                                <th>Volume</th>
                                <th>Edition</th>
                                <th>Content Type</th>
                                <th>Media Type</th>
                                <th>Carrier Type</th>
                                <th>ISBN</th>
                                <th>URL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch books from database
                            $query = "SELECT * FROM books";
                            if (!empty($searchQuery)) {
                                $query .= " WHERE title LIKE '%$searchQuery%' OR preferred_title LIKE '%$searchQuery%' OR parallel_title LIKE '%$searchQuery%' OR ISBN LIKE '%$searchQuery%'";
                            }
                            $query .= " ORDER BY id DESC";
                            $result = $conn->query($query);

                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td><input type='checkbox' class='selectRow'></td>
                                    <td>{$row['id']}</td>
                                    <td>{$row['accession']}</td>
                                    <td>{$row['title']}</td>
                                    <td>{$row['preferred_title']}</td>
                                    <td>{$row['parallel_title']}</td>
                                    <td>";
                                if (!empty($row['front_image'])) {
                                    echo "<img src='../inc/book-image/{$row['front_image']}' alt='Front Image' width='50'>";
                                } else {
                                    echo "No Image";
                                }
                                echo "</td>
                                    <td>";
                                if (!empty($row['back_image'])) {
                                    echo "<img src='../inc/book-image/{$row['back_image']}' alt='Back Image' width='50'>";
                                } else {
                                    echo "No Image";
                                }
                                echo "</td>
                                    <td>{$row['height']}</td>
                                    <td>{$row['width']}</td>
                                    <td>{$row['total_pages']}</td>
                                    <td>{$row['call_number']}</td>
                                    <td>{$row['copy_number']}</td>
                                    <td>{$row['language']}</td>
                                    <td>{$row['shelf_location']}</td>
                                    <td>{$row['entered_by']}</td>
                                    <td>{$row['date_added']}</td>
                                    <td>{$row['status']}</td>
                                    <td>{$row['last_update']}</td>
                                    <td>{$row['series']}</td>
                                    <td>{$row['volume']}</td>
                                    <td>{$row['edition']}</td>
                                    <td>{$row['content_type']}</td>
                                    <td>{$row['media_type']}</td>
                                    <td>{$row['carrier_type']}</td>
                                    <td>{$row['ISBN']}</td>
                                    <td>";
                                echo !empty($row['URL']) ? "<a href='{$row['URL']}' target='_blank'>View</a>" : "N/A";
                                echo "</td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- /.container-fluid -->
</div>
<!-- End of Main Content -->

<!-- Add Contributors Modal -->
<div class="modal fade" id="addContributorsModal" tabindex="-1" role="dialog" aria-labelledby="addContributorsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addContributorsModalLabel">Add Contributors</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Search Form -->
                <form id="searchContributorsForm" method="GET" action="book_list.php">
                    <div class="input-group mb-3">
                        <input type="text" id="searchContributors" name="search_contributors" class="form-control" placeholder="Search Contributors...">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </div>
                </form>
                <!-- Contributors List -->
                <div class="table-responsive">
                    <table class="table table-bordered" id="contributorsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Middle Initial</th>
                                <th>Last Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch contributors from database
                            $contributorsQuery = "SELECT * FROM writers";
                            if (isset($_GET['search_contributors']) && !empty($_GET['search_contributors'])) {
                                $searchContributors = $conn->real_escape_string($_GET['search_contributors']);
                                $contributorsQuery .= " WHERE firstname LIKE '%$searchContributors%' OR middle_init LIKE '%$searchContributors%' OR lastname LIKE '%$searchContributors%'";
                            }
                            $contributorsResult = $conn->query($contributorsQuery);

                            while ($contributor = $contributorsResult->fetch_assoc()) {
                                echo "<tr>
                                    <td>{$contributor['id']}</td>
                                    <td>{$contributor['firstname']}</td>
                                    <td>{$contributor['middle_init']}</td>
                                    <td>{$contributor['lastname']}</td>
                                    <td><button class='btn btn-success btn-sm addContributor' data-id='{$contributor['id']}'>Add</button></td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include '../Admin/inc/footer.php' ?>
<!-- End of Footer -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script>
$(document).ready(function () {
    var selectedBookIds = [];

    // Select/Deselect all checkboxes
    $('#selectAll').click(function() {
        $('.selectRow').prop('checked', this.checked);
        updateSelectedBookIds();
        toggleAddContributorsIcons();
    });

    $('.selectRow').click(function() {
        if ($('.selectRow:checked').length == $('.selectRow').length) {
            $('#selectAll').prop('checked', true);
        } else {
            $('#selectAll').prop('checked', false);
        }
        updateSelectedBookIds();
        toggleAddContributorsIcons();
    });

    function updateSelectedBookIds() {
        selectedBookIds = [];
        $('.selectRow:checked').each(function() {
            selectedBookIds.push($(this).closest('tr').find('td:nth-child(2)').text());
        });
        console.log(selectedBookIds); // For debugging purposes
    }

    function toggleAddContributorsIcons() {
        if ($('.selectRow:checked').length > 0) {
            $('#addContributorsIcons').removeClass('d-none');
        } else {
            $('#addContributorsIcons').addClass('d-none');
        }
    }

    // Redirect to add_contributors.php when "Add Contributors (Person)" button is clicked
    $('#addContributorsPerson').click(function (e) {
        e.preventDefault();
        var queryString = selectedBookIds.map(id => `book_ids[]=${id}`).join('&');
        window.location.href = `add_contributors.php?${queryString}`;
    });

    // Add contributor to selected books
    $(document).on('click', '.addContributor', function() {
        var contributorId = $(this).data('id');
        var bookId = selectedBookIds[0]; // Assuming only one book is selected at a time
        var role = prompt("Enter role (Author, Co-Author, Editor):", "Author");

        if (role) {
            $.post('process_add_contributors.php', {
                book_id: bookId,
                writer_id: contributorId,
                role: role
            }, function(response) {
                alert('Contributor added successfully!');
                location.reload();
            });
        }
    });

    // Handle search form submission
    $('form').submit(function(event) {
        event.preventDefault();
        var searchQuery = $('input[name="search"]').val();

        $.ajax({
            url: 'fetch_books.php',
            type: 'GET',
            data: {
                search: searchQuery
            },
            success: function(response) {
                $('#dataTable tbody').html(response);
            }
        });
    });
});
</script>
