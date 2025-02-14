<?php
// Start output buffering
ob_start();

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Get book ID before includes
$bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

// Database connection first
include '../db.php';

// Process form submission before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // First, get the current book data before updating
    $getCurrentBook = "SELECT * FROM books WHERE id = ?";
    $stmt = $conn->prepare($getCurrentBook);
    $stmt->bind_param('i', $bookId);
    $stmt->execute();
    $oldBookData = $stmt->get_result()->fetch_assoc();

    // Get the new data from POST
    $accession = $_POST['accession'];
    $title = $_POST['title'];
    $preferredTitle = $_POST['preferred_title'];
    $parallelTitle = $_POST['parallel_title'];
    $height = $_POST['height'];
    $width = $_POST['width'];
    $totalPages = $_POST['total_pages'];
    $callNumber = $_POST['call_number'];
    $copyNumber = $_POST['copy_number'];
    $language = $_POST['language'];
    $shelfLocation = $_POST['shelf_location'];
    $enteredBy = $_POST['entered_by'];
    $dateAdded = $_POST['date_added'];
    $status = $_POST['status'];
    $lastUpdate = $_POST['last_update'];
    $series = $_POST['series'];
    $volume = $_POST['volume'];
    $edition = $_POST['edition'];
    $isbn = $_POST['isbn'];
    $url = $_POST['url'];
    $contentType = $_POST['content_type'];
    $mediaType = $_POST['media_type'];
    $carrierType = $_POST['carrier_type'];

    // Handle image uploads
    $frontImage = !empty($_FILES['front_image']['name']) ? $_FILES['front_image']['name'] : $oldBookData['front_image'];
    $backImage = !empty($_FILES['back_image']['name']) ? $_FILES['back_image']['name'] : $oldBookData['back_image'];

    // Upload new images if provided
    if (!empty($_FILES['front_image']['name'])) {
        move_uploaded_file($_FILES['front_image']['tmp_name'], "../uploads/" . $frontImage);
    }
    if (!empty($_FILES['back_image']['name'])) {
        move_uploaded_file($_FILES['back_image']['tmp_name'], "../uploads/" . $backImage);
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update the current book first
        $query = "UPDATE books SET 
                  accession = ?, title = ?, preferred_title = ?, parallel_title = ?, 
                  front_image = ?, 
                  back_image = ?, 
                  height = ?, width = ?, total_pages = ?, 
                  call_number = ?, copy_number = ?, language = ?, shelf_location = ?, 
                  entered_by = ?, date_added = ?, status = ?, last_update = NOW(), 
                  series = ?, volume = ?, edition = ?, ISBN = ?, URL = ?, 
                  content_type = ?, media_type = ?, carrier_type = ? 
                  WHERE id = ?";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssssssiiisiissssssssssssi', 
            $accession, $title, $preferredTitle, $parallelTitle, 
            $frontImage, $backImage, 
            $height, $width, $totalPages, $callNumber, $copyNumber, 
            $language, $shelfLocation, $enteredBy, $dateAdded, $status, 
            $series, $volume, $edition, $isbn, $url, 
            $contentType, $mediaType, $carrierType, $bookId
        );
        $stmt->execute();

        // Now update all similar books (excluding the current book)
        $updateSimilarBooks = "UPDATE books SET 
                             title = ?,
                             preferred_title = ?,
                             parallel_title = ?,
                             series = ?,
                             volume = ?,
                             edition = ?,
                             ISBN = ?,
                             content_type = ?,
                             media_type = ?,
                             carrier_type = ?,
                             last_update = NOW()
                             WHERE title = ? 
                             AND id != ?";

        $stmt = $conn->prepare($updateSimilarBooks);
        $stmt->bind_param('sssssssssssi', 
            $title, $preferredTitle, $parallelTitle,
            $series, $volume, $edition, $isbn,
            $contentType, $mediaType, $carrierType,
            $oldBookData['title'], // Use the old title to find similar books
            $bookId
        );
        $stmt->execute();
        
        $affectedRows = $stmt->affected_rows;

        $conn->commit();
        $_SESSION['success_message'] = "Book updated successfully! {$affectedRows} similar books were also updated.";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error updating books: " . $e->getMessage();
    }

    // Clear output buffer and redirect
    ob_end_clean();
    header("Location: book_list.php");
    exit();
}

// Now include header since we're sure we won't redirect
include '../admin/inc/header.php';

$query = "SELECT * FROM books WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $bookId);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    echo "Book not found!";
    exit();
}
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <form id="bookForm" action="update_book.php?book_id=<?php echo $bookId; ?>" method="POST" enctype="multipart/form-data">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-2 text-gray-800">Update Book</h1>
                <button type="submit" class="btn btn-success">Update Book</button>
            </div>

            <div class="row">
                <div class="col-xl-12 col-lg-7">
                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs" id="formTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#title-proper" role="tab">Title Proper</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#description" role="tab">Description</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#local-info" role="tab">Local Information</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#publication" role="tab">Publication</a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="formTabsContent">
                        <!-- Title Proper Tab -->
                        <div class="tab-pane fade show active" id="title-proper" role="tabpanel">
                            <h4>Title Proper</h4>
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Preferred Title</label>
                                <input type="text" class="form-control" name="preferred_title" value="<?php echo htmlspecialchars($book['preferred_title']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Parallel Title</label>
                                <input type="text" class="form-control" name="parallel_title" value="<?php echo htmlspecialchars($book['parallel_title']); ?>">
                            </div>
                        </div>

                        <!-- Description Tab -->
                        <div class="tab-pane fade" id="description" role="tabpanel">
                            <h4>Description</h4>
                            <div class="form-group">
                                <label>Front Image</label>
                                <input type="file" class="form-control" name="front_image">
                                <?php if (!empty($book['front_image'])): ?>
                                    <img src="../uploads/<?php echo $book['front_image']; ?>" alt="Front Image" width="50">
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Back Image</label>
                                <input type="file" class="form-control" name="back_image">
                                <?php if (!empty($book['back_image'])): ?>
                                    <img src="../uploads/<?php echo $book['back_image']; ?>" alt="Back Image" width="50">
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Height (cm)</label>
                                <input type="number" class="form-control" name="height" value="<?php echo htmlspecialchars($book['height']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Width (cm)</label>
                                <input type="number" class="form-control" name="width" value="<?php echo htmlspecialchars($book['width']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Total Pages</label>
                                <input type="number" class="form-control" name="total_pages" value="<?php echo htmlspecialchars($book['total_pages']); ?>">
                            </div>
                        </div>

                        <!-- Local Information Tab -->
                        <div class="tab-pane fade" id="local-info" role="tabpanel">
                            <h4>Local Information</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Accession</label>
                                        <input type="text" class="form-control" name="accession" value="<?php echo htmlspecialchars($book['accession']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Call Number</label>
                                        <input type="text" class="form-control" name="call_number" value="<?php echo htmlspecialchars($book['call_number']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Copy Number</label>
                                        <input type="number" class="form-control" name="copy_number" value="<?php echo htmlspecialchars($book['copy_number']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Language</label>
                                        <select class="form-control" name="language">
                                            <option value="English" <?php echo $book['language'] == 'English' ? 'selected' : ''; ?>>English</option>
                                            <option value="Spanish" <?php echo $book['language'] == 'Spanish' ? 'selected' : ''; ?>>Spanish</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Shelf Location</label>
                                        <select class="form-control" name="shelf_location">
                                            <option value="A1" <?php echo $book['shelf_location'] == 'A1' ? 'selected' : ''; ?>>A1</option>
                                            <option value="B2" <?php echo $book['shelf_location'] == 'B2' ? 'selected' : ''; ?>>B2</option>
                                            <option value="C3" <?php echo $book['shelf_location'] == 'C3' ? 'selected' : ''; ?>>C3</option>
                                            <option value="D4" <?php echo $book['shelf_location'] == 'D4' ? 'selected' : ''; ?>>D4</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Entered By</label>
                                        <input type="text" class="form-control" name="entered_by" value="<?php echo htmlspecialchars($book['entered_by']); ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Date Added</label>
                                        <input type="text" class="form-control" name="date_added" value="<?php echo htmlspecialchars($book['date_added']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select class="form-control" name="status">
                                            <option value="inshelf" <?php echo $book['status'] == 'inshelf' ? 'selected' : ''; ?>>In Shelf</option>
                                            <option value="borrowed" <?php echo $book['status'] == 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                                            <option value="lost" <?php echo $book['status'] == 'lost' ? 'selected' : ''; ?>>Lost</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Last Update</label>
                                        <input type="text" class="form-control" name="last_update" value="<?php echo htmlspecialchars($book['last_update']); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end local information -->

                        <!-- Publication Tab -->
                        <div class="tab-pane fade" id="publication" role="tabpanel">
                            <h4>Publication</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Series</label>
                                        <input type="text" class="form-control" name="series" value="<?php echo htmlspecialchars($book['series']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Volume</label>
                                        <input type="text" class="form-control" name="volume" value="<?php echo htmlspecialchars($book['volume']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Edition</label>
                                        <input type="text" class="form-control" name="edition" value="<?php echo htmlspecialchars($book['edition']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ISBN</label>
                                        <input type="text" class="form-control" name="isbn" value="<?php echo htmlspecialchars($book['ISBN']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>URL</label>
                                        <input type="text" class="form-control" name="url" value="<?php echo htmlspecialchars($book['URL']); ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Content Type</label>
                                        <select class="form-control" name="content_type">
                                            <option value="Text" <?php echo $book['content_type'] == 'Text' ? 'selected' : ''; ?>>Text</option>
                                            <option value="Image" <?php echo $book['content_type'] == 'Image' ? 'selected' : ''; ?>>Image</option>
                                            <option value="Video" <?php echo $book['content_type'] == 'Video' ? 'selected' : ''; ?>>Video</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Media Type</label>
                                        <select class="form-control" name="media_type">
                                            <option value="Print" <?php echo $book['media_type'] == 'Print' ? 'selected' : ''; ?>>Print</option>
                                            <option value="Digital" <?php echo $book['media_type'] == 'Digital' ? 'selected' : ''; ?>>Digital</option>
                                            <option value="Audio" <?php echo $book['media_type'] == 'Audio' ? 'selected' : ''; ?>>Audio</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Carrier Type</label>
                                        <select class="form-control" name="carrier_type">
                                            <option value="Book" <?php echo $book['carrier_type'] == 'Book' ? 'selected' : ''; ?>>Book</option>
                                            <option value="CD" <?php echo $book['carrier_type'] == 'CD' ? 'selected' : ''; ?>>CD</option>
                                            <option value="USB" <?php echo $book['carrier_type'] == 'USB' ? 'selected' : ''; ?>>USB</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end publication -->
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../admin/inc/footer.php'; ?>

<!-- Bootstrap and JS -->
<script src="inc/js/demo/chart-area-demo.js"></script>
<script src="inc/js/demo/chart-pie-demo.js"></script>
<script src="inc/js/demo/chart-bar-demo.js"></script>

<!-- SCRIPT FOR TAB -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    var triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'));
    triggerTabList.forEach(function(triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl);
        triggerEl.addEventListener("click", function(event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });
});
</script>