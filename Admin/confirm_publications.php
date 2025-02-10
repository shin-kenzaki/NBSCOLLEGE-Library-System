<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
include '../db.php'; // Database connection

// Get the selected book IDs and publisher IDs from the POST request
$bookIds = isset($_POST['book_ids']) ? $_POST['book_ids'] : [];
$selectedPublisherIds = isset($_POST['selected_publisher_ids']) ? $_POST['selected_publisher_ids'] : [];

// Store selected book IDs and publisher IDs in session
$_SESSION['selected_book_ids'] = $bookIds;
$_SESSION['selected_publisher_ids'] = $selectedPublisherIds;

?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Confirm Publications</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="add_publication.php">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="confirmPublicationsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Book ID</th>
                                    <th>Publisher ID</th>
                                    <th>Publish Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($bookIds as $bookId) {
                                    foreach ($selectedPublisherIds as $publisherId) {
                                        echo "<tr>
                                            <td>{$bookId}</td>
                                            <td>{$publisherId}</td>
                                            <td><input type='date' class='form-control' name='publish_dates[{$bookId}_{$publisherId}]' required></td>
                                        </tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary">Confirm and Add Publications</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Main Content -->

<!-- Footer -->
<?php include '../Admin/inc/footer.php' ?>
<!-- End of Footer -->