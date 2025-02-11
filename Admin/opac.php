<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../db.php'; // Database connection

// Get the book ID from the query parameters
$bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

if ($bookId > 0) {
    // Fetch book details from the database
    $query = "SELECT * FROM books WHERE id = $bookId";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
    } else {
        $error = "Book not found.";
    }

    // Fetch contributors from the database
    $contributorsQuery = "SELECT c.*, w.firstname, w.middle_init, w.lastname FROM contributors c JOIN writers w ON c.writer_id = w.id WHERE c.book_id = $bookId";
    $contributorsResult = $conn->query($contributorsQuery);
    $contributors = [];
    while ($row = $contributorsResult->fetch_assoc()) {
        $contributors[] = $row;
    }

    // Fetch publications from the database
    $publicationsQuery = "SELECT p.*, pub.company, pub.place FROM publications p JOIN publishers pub ON p.publisher_id = pub.id WHERE p.book_id = $bookId";
    $publicationsResult = $conn->query($publicationsQuery);
    $publications = [];
    while ($row = $publicationsResult->fetch_assoc()) {
        $publications[] = $row;
    }
} else {
    $error = "Invalid book ID.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OPAC - Book Details</title>
    <style>
        .book-details {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .book-details h2 {
            margin-top: 0;
        }
        .book-details img {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .book-details p {
            margin: 5px 0;
        }
        .book-details .label {
            font-weight: bold;
        }
        .book-details .row {
            display: flex;
            justify-content: space-between;
        }
        .book-details .row p {
            flex: 1;
            margin: 5px 10px;
        }
    </style>
</head>
<body>
    <?php include '../admin/inc/header.php'; ?>

    <!-- Main Content -->
    <div id="content" class="d-flex flex-column min-vh-100">
        <div class="container-fluid">
            <div class="book-details">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php else: ?>
                    <h2><?php echo htmlspecialchars($book['title']); ?></h2>
                    <?php if (!empty($book['front_image'])): ?>
                        <img src="../inc/book-image/<?php echo htmlspecialchars($book['front_image']); ?>" alt="Front Image">
                    <?php endif; ?>
                    <?php if (!empty($book['back_image'])): ?>
                        <img src="../inc/book-image/<?php echo htmlspecialchars($book['back_image']); ?>" alt="Back Image">
                    <?php endif; ?>
                    <div class="row">
                        <p><span class="label">Accession:</span> <?php echo htmlspecialchars($book['accession']); ?></p>
                        <p><span class="label">Preferred Title:</span> <?php echo htmlspecialchars($book['preferred_title']); ?></p>
                    </div>
                    <div class="row">
                        <p><span class="label">Parallel Title:</span> <?php echo htmlspecialchars($book['parallel_title']); ?></p>
                        <p><span class="label">Height:</span> <?php echo htmlspecialchars($book['height']); ?></p>
                    </div>
                    <div class="row">
                        <p><span class="label">Width:</span> <?php echo htmlspecialchars($book['width']); ?></p>
                        <p><span class="label">Total Pages:</span> <?php echo htmlspecialchars($book['total_pages']); ?></p>
                    </div>
                    <div class="row">
                        <p><span class="label">Call Number:</span> <?php echo htmlspecialchars($book['call_number']); ?></p>
                        <p><span class="label">Copy Number:</span> <?php echo htmlspecialchars($book['copy_number']); ?></p>
                    </div>
                    <div class="row">
                        <p><span class="label">Language:</span> <?php echo htmlspecialchars($book['language']); ?></p>
                        <p><span class="label">Shelf Location:</span> <?php echo htmlspecialchars($book['shelf_location']); ?></p>
                    </div>
                    <div class="row">
                        <p><span class="label">Entered By:</span> <?php echo htmlspecialchars($book['entered_by']); ?></p>
                        <p><span class="label">Date Added:</span> <?php echo htmlspecialchars($book['date_added']); ?></p>
                    </div>
                    <div class="row">
                        <p><span class="label">Status:</span> <?php echo htmlspecialchars($book['status']); ?></p>
                        <p><span class="label">Last Update:</span> <?php echo htmlspecialchars($book['last_update']); ?></p>
                    </div>
                    <div class="row">
                        <p><span class="label">Series:</span> <?php echo htmlspecialchars($book['series']); ?></p>
                        <p><span class="label">Volume:</span> <?php echo htmlspecialchars($book['volume']); ?></p>
                    </div>
                    <div class="row">
                        <p><span class="label">Edition:</span> <?php echo htmlspecialchars($book['edition']); ?></p>
                        <p><span class="label">Content Type:</span> <?php echo htmlspecialchars($book['content_type']); ?></p>
                    </div>
                    <div class="row">
                        <p><span class="label">Media Type:</span> <?php echo htmlspecialchars($book['media_type']); ?></p>
                        <p><span class="label">Carrier Type:</span> <?php echo htmlspecialchars($book['carrier_type']); ?></p>
                    </div>
                    <div class="row">
                        <p><span class="label">ISBN:</span> <?php echo htmlspecialchars($book['ISBN']); ?></p>
                        <p><span class="label">URL:</span> <?php echo !empty($book['URL']) ? "<a href='" . htmlspecialchars($book['URL']) . "' target='_blank'>View</a>" : "N/A"; ?></p>
                    </div>

                    <!-- Display Contributors -->
                    <h3>Contributors</h3>
                    <?php if (!empty($contributors)): ?>
                        <ul>
                            <?php foreach ($contributors as $contributor): ?>
                                <li><?php echo htmlspecialchars($contributor['firstname'] . ' ' . $contributor['middle_init'] . ' ' . $contributor['lastname'] . ' (' . $contributor['role'] . ')'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No contributors found.</p>
                    <?php endif; ?>

                    <!-- Display Publications -->
                    <h3>Publications</h3>
                    <?php if (!empty($publications)): ?>
                        <ul>
                            <?php foreach ($publications as $publication): ?>
                                <li><?php echo htmlspecialchars($publication['company'] . ' (' . $publication['place'] . ') - ' . $publication['publish_date']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No publications found.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- End of Main Content -->

    <!-- Footer -->
    <?php include '../Admin/inc/footer.php' ?>
    <!-- End of Footer -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
</body>
</html>