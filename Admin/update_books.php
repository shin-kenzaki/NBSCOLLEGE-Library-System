<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../db.php';
include '../admin/inc/header.php';

// Add SweetAlert2 CDN in the header section
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

// Fetch admin names and roles for the dropdown
$admins_query = "SELECT id, CONCAT(firstname, ' ', lastname) AS name, role FROM admins";
$admins_result = mysqli_query($conn, $admins_query);
$admins = [];
while ($row = mysqli_fetch_assoc($admins_result)) {
    $admins[] = $row;
}

// Process form submission
if (isset($_POST['submit'])) {
    try {
        $conn->begin_transaction();

        // Validate accession numbers first
        $bookIds = $_POST['book_ids'];
        $accessions = $_POST['accession'];
        $hasError = false;
        $errorMessage = '';

        // Check each accession number
        foreach ($accessions as $index => $accession) {
            $bookId = $bookIds[$index];
            
            // Check if accession exists in other books
            $check_query = "SELECT id FROM books WHERE accession = ? AND id != ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ii", $accession, $bookId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $hasError = true;
                $errorMessage = "Accession number $accession is already in use by another book.";
                break;
            }
        }

        if ($hasError) {
            throw new Exception($errorMessage);
        }

        // Get arrays of book data
        $shelf_locations = $_POST['shelf_location'] ?? array();
        $call_numbers = $_POST['call_numbers'] ?? array();
        $front_images = $_POST['front_image'] ?? array();
        $back_images = $_POST['back_image'] ?? array();
        $dimensions = $_POST['dimension'] ?? array();
        $series = $_POST['series'] ?? array();
        $volumes = $_POST['volume'] ?? array();
        $editions = $_POST['edition'] ?? array();
        $urls = $_POST['url'] ?? array();
        $content_types = $_POST['content_type'] ?? array();
        $media_types = $_POST['media_type'] ?? array();
        $carrier_types = $_POST['carrier_type'] ?? array();
        
        // Fix: Handle total_pages variable properly
        // Get prefix and main pages to combine into total_pages
        $prefix_pages = $_POST['prefix_pages'] ?? '';
        $main_pages = $_POST['main_pages'] ?? '';
        
        // Process supplementary contents
        $supplementary_content = isset($_POST['supplementary_content']) && is_array($_POST['supplementary_content']) ? 
            implode(', ', $_POST['supplementary_content']) : '';
        
        $entered_by = $_POST['entered_by'] ?? array();
        $date_added = $_POST['date_added'] ?? array();
        $statuses = $_POST['statuses'] ?? array();

        // Common data for all copies - make sure we're handling strings properly
        $title = mysqli_real_escape_string($conn, is_array($_POST['title']) ? $_POST['title'][0] : ($_POST['title'] ?? ''));
        $preferred_title = mysqli_real_escape_string($conn, is_array($_POST['preferred_title']) ? $_POST['preferred_title'][0] : ($_POST['preferred_title'] ?? ''));
        $parallel_title = mysqli_real_escape_string($conn, is_array($_POST['parallel_title']) ? $_POST['parallel_title'][0] : ($_POST['parallel_title'] ?? ''));
        $call_number = mysqli_real_escape_string($conn, isset($_POST['call_numbers']) && is_array($_POST['call_numbers']) && !empty($_POST['call_numbers']) ? $_POST['call_numbers'][0] : '');
        $language = mysqli_real_escape_string($conn, is_array($_POST['language']) ? $_POST['language'][0] : ($_POST['language'] ?? ''));

        // Properly handle status with default value
        $status = isset($_POST['status']) ? (is_array($_POST['status']) ? $_POST['status'][0] : $_POST['status']) : 'Available';

        $abstract = mysqli_real_escape_string($conn, is_array($_POST['abstract']) ? $_POST['abstract'][0] : ($_POST['abstract'] ?? ''));
        $notes = mysqli_real_escape_string($conn, is_array($_POST['notes']) ? $_POST['notes'][0] : ($_POST['notes'] ?? ''));
        $dimension = mysqli_real_escape_string($conn, is_array($_POST['dimension']) ? $_POST['dimension'][0] : ($_POST['dimension'] ?? ''));
        $series = mysqli_real_escape_string($conn, is_array($_POST['series']) ? $_POST['series'][0] : ($_POST['series'] ?? ''));
        $volume = mysqli_real_escape_string($conn, is_array($_POST['volume']) ? $_POST['volume'][0] : ($_POST['volume'] ?? ''));
        $edition = mysqli_real_escape_string($conn, is_array($_POST['edition']) ? $_POST['edition'][0] : ($_POST['edition'] ?? ''));
        $url = mysqli_real_escape_string($conn, is_array($_POST['url']) ? $_POST['url'][0] : ($_POST['url'] ?? ''));
        $content_type = mysqli_real_escape_string($conn, is_array($_POST['content_type']) ? $_POST['content_type'][0] : ($_POST['content_type'] ?? 'Text'));
        $media_type = mysqli_real_escape_string($conn, is_array($_POST['media_type']) ? $_POST['media_type'][0] : ($_POST['media_type'] ?? 'Print'));
        $carrier_type = mysqli_real_escape_string($conn, is_array($_POST['carrier_type']) ? $_POST['carrier_type'][0] : ($_POST['carrier_type'] ?? 'Book'));
        $last_update = date('Y-m-d');

        // Safely access array elements for subject category and detail
        $subject_category = '';
        if (isset($_POST['subject_categories']) && is_array($_POST['subject_categories']) && !empty($_POST['subject_categories'])) {
            $subject_category = mysqli_real_escape_string($conn, $_POST['subject_categories'][0]);
        } elseif (isset($_POST['subject_categories']) && is_string($_POST['subject_categories'])) {
            $subject_category = mysqli_real_escape_string($conn, $_POST['subject_categories']);
        }

        $subject_detail = '';
        if (isset($_POST['subject_paragraphs']) && is_array($_POST['subject_paragraphs']) && !empty($_POST['subject_paragraphs'])) {
            $subject_detail = mysqli_real_escape_string($conn, $_POST['subject_paragraphs'][0]);
        } elseif (isset($_POST['subject_paragraphs']) && is_string($_POST['subject_paragraphs'])) {
            $subject_detail = mysqli_real_escape_string($conn, $_POST['subject_paragraphs']);
        }

        // Handle ISBN field - might be an array or a string
        $ISBN = '';
        if (isset($_POST['ISBN']) && is_array($_POST['ISBN'])) {
            $ISBN = !empty($_POST['ISBN'][0]) ? mysqli_real_escape_string($conn, $_POST['ISBN'][0]) : '';
        } elseif (isset($_POST['ISBN']) && is_string($_POST['ISBN'])) {
            $ISBN = mysqli_real_escape_string($conn, $_POST['ISBN']);
        }

        // Get admin info for update tracking
        $current_admin_id = $_SESSION['admin_id'];
        $update_date = date('Y-m-d');

        // Properly handle status with default value
        $status = isset($_POST['status']) ? (is_array($_POST['status']) ? $_POST['status'][0] : $_POST['status']) : 'Available';

        // Update each book copy
        foreach ($bookIds as $index => $bookId) {
            // Get original entered_by, date_added, and status for this specific copy
            $original_data_query = "SELECT entered_by, date_added, status FROM books WHERE id = ?";
            $stmt = $conn->prepare($original_data_query);
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $original_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            // Preserve original status with proper checks
            $preserved_status = isset($original_data['status']) ? $original_data['status'] : 'Available';
            
            // Safe array access with proper checks for each array
            $shelf_location = isset($shelf_locations[$index]) ? mysqli_real_escape_string($conn, $shelf_locations[$index]) : '';
            $call_number = isset($call_numbers[$index]) ? mysqli_real_escape_string($conn, $call_numbers[$index]) : '';
            $accession = isset($accessions[$index]) ? mysqli_real_escape_string($conn, $accessions[$index]) : '';
            
            // Get individual series, volume, edition values from form inputs for each copy
            $series_value = isset($series[$index]) ? mysqli_real_escape_string($conn, $series[$index]) : '';
            $volume_value = isset($volumes[$index]) ? mysqli_real_escape_string($conn, $volumes[$index]) : '';
            $edition_value = isset($editions[$index]) ? mysqli_real_escape_string($conn, $editions[$index]) : '';
            
            $entered_by_value = isset($original_data['entered_by']) ? $original_data['entered_by'] : '';
            $date_added_value = isset($original_data['date_added']) ? $original_data['date_added'] : date('Y-m-d');

            // Copy-specific values
            $url = isset($_POST['url']) && is_string($_POST['url']) ? mysqli_real_escape_string($conn, $_POST['url']) : '';
            $content_type = isset($_POST['content_type']) && is_string($_POST['content_type']) ? mysqli_real_escape_string($conn, $_POST['content_type']) : 'Text';
            $media_type = isset($_POST['media_type']) && is_string($_POST['media_type']) ? mysqli_real_escape_string($conn, $_POST['media_type']) : 'Print';
            $carrier_type = isset($_POST['carrier_type']) && is_string($_POST['carrier_type']) ? mysqli_real_escape_string($conn, $_POST['carrier_type']) : 'Book';
            
            // Calculate total pages from prefix and main
            $total_page = '';
            if (!empty($prefix_pages) && !empty($main_pages)) {
                $total_page = $prefix_pages . ' ' . $main_pages;
            } elseif (!empty($prefix_pages)) {
                $total_page = $prefix_pages;
            } elseif (!empty($main_pages)) {
                $total_page = $main_pages;
            }
            
            // Use same supplementary content for all copies
            $supplementary_content_value = mysqli_real_escape_string($conn, $supplementary_content);

            $update_query = "UPDATE books SET 
                title = ?, 
                preferred_title = ?, 
                parallel_title = ?,
                subject_category = ?,
                subject_detail = ?,
                summary = ?,
                contents = ?,
                front_image = ?,
                back_image = ?,
                dimension = ?,
                series = ?,
                volume = ?,
                edition = ?,
                total_pages = ?,
                supplementary_contents = ?,
                ISBN = ?,
                content_type = ?,
                media_type = ?,
                carrier_type = ?,
                call_number = ?,
                URL = ?,
                language = ?,
                shelf_location = ?,
                entered_by = ?,
                date_added = ?,
                status = ?,
                updated_by = ?,
                last_update = ?,
                accession = ?
                WHERE id = ?";
                
            $stmt = $conn->prepare($update_query);
            
            // Bind parameters with copy-specific values including preserved entered_by/date_added
            $stmt->bind_param("sssssssssssssssssssssssssssssi", 
                $title, 
                $preferred_title, 
                $parallel_title,
                $subject_category,
                $subject_detail,
                $abstract,
                $notes,
                $front_image,
                $back_image,
                $dimension,
                $series_value,     // Use individual series value for this copy
                $volume_value,     // Use individual volume value for this copy
                $edition_value,    // Use individual edition value for this copy
                $total_page,
                $supplementary_content_value,
                $ISBN,
                $content_type,
                $media_type,
                $carrier_type,
                $call_number,
                $url,
                $language,
                $shelf_location,
                $entered_by_value,
                $date_added_value,
                $preserved_status,
                $current_admin_id,
                $update_date,
                $accession,
                $bookId
            );

            // Execute the update for this copy
            if (!$stmt->execute()) {
                throw new Exception("Error updating book copy (ID: $bookId): " . $stmt->error);
            }
            
            $stmt->close();
        }

        // Update publications for each book
        foreach ($bookIds as $bookId) {
            // Get publisher ID from name
            $publisher_name = mysqli_real_escape_string($conn, $_POST['publisher'] ?? '');
            $publisher_id = null;
            
            if (!empty($publisher_name)) {
                // Check if publisher exists
                $check_publisher = "SELECT id FROM publishers WHERE publisher = ?";
                $stmt = $conn->prepare($check_publisher);
                $stmt->bind_param("s", $publisher_name);
                $stmt->execute();
                $publisher_result = $stmt->get_result();
                
                if ($publisher_result->num_rows > 0) {
                    // Publisher exists, get ID
                    $publisher_id = $publisher_result->fetch_assoc()['id'];
                } else {
                    // Publisher doesn't exist, create new (with default place for now)
                    $new_publisher = "INSERT INTO publishers (publisher, place) VALUES (?, 'Unknown')";
                    $stmt = $conn->prepare($new_publisher);
                    $stmt->bind_param("s", $publisher_name);
                    $stmt->execute();
                    $publisher_id = $conn->insert_id;
                }
            }
            
            // Get publish date
            $publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
            
            if ($publisher_id !== null || $publish_date !== null) {
                // Check if publication entry exists for this book
                $check_pub = "SELECT id FROM publications WHERE book_id = ?";
                $stmt = $conn->prepare($check_pub);
                $stmt->bind_param("i", $bookId);
                $stmt->execute();
                $pub_result = $stmt->get_result();
                
                if ($pub_result->num_rows > 0) {
                    // Publication exists, update it
                    $pub_id = $pub_result->fetch_assoc()['id'];
                    
                    // Build the update query based on which fields we have
                    if ($publisher_id !== null && $publish_date !== null) {
                        $update_pub = "UPDATE publications SET publisher_id = ?, publish_date = ? WHERE id = ?";
                        $stmt = $conn->prepare($update_pub);
                        $stmt->bind_param("isi", $publisher_id, $publish_date, $pub_id);
                    } elseif ($publisher_id !== null) {
                        $update_pub = "UPDATE publications SET publisher_id = ? WHERE id = ?";
                        $stmt = $conn->prepare($update_pub);
                        $stmt->bind_param("ii", $publisher_id, $pub_id);
                    } elseif ($publish_date !== null) {
                        $update_pub = "UPDATE publications SET publish_date = ? WHERE id = ?";
                        $stmt = $conn->prepare($update_pub);
                        $stmt->bind_param("si", $publish_date, $pub_id);
                    }
                    
                    if (isset($update_pub)) {
                        $stmt->execute();
                    }
                } else {
                    // No publication entry exists, create one if we have both publisher and year
                    if ($publisher_id !== null) {
                        $insert_pub = "INSERT INTO publications (book_id, publisher_id, publish_date) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($insert_pub);
                        $stmt->bind_param("iis", $bookId, $publisher_id, $publish_date);
                        $stmt->execute();
                    }
                }
            }
        }

        // Update contributors - MODIFIED SECTION
        if (!empty($_POST['author']) || !empty($_POST['author'][0])) {
            foreach ($bookIds as $bookId) {
                // Remove existing contributors
                $delete_query = "DELETE FROM contributors WHERE book_id = ?";
                $stmt = $conn->prepare($delete_query);
                $stmt->bind_param("i", $bookId);
                $stmt->execute();

                // Add authors
                $insert_author = "INSERT INTO contributors (book_id, writer_id, role) VALUES (?, ?, 'Author')";
                $stmt = $conn->prepare($insert_author);
                
                if (is_array($_POST['author'])) {
                    foreach ($_POST['author'] as $authorId) {
                        if (!empty($authorId)) {
                            $stmt->bind_param("ii", $bookId, $authorId);
                            $stmt->execute();
                        }
                    }
                } else {
                    $stmt->bind_param("ii", $bookId, $_POST['author']);
                    $stmt->execute();
                }

                // Add co-authors if any
                if (!empty($_POST['co_authors']) && is_array($_POST['co_authors'])) {
                    $insert_coauthor = "INSERT INTO contributors (book_id, writer_id, role) VALUES (?, ?, 'Co-Author')";
                    $stmt = $conn->prepare($insert_coauthor);
                    
                    foreach ($_POST['co_authors'] as $coAuthorId) {
                        if (!empty($coAuthorId)) {
                            $stmt->bind_param("ii", $bookId, $coAuthorId);
                            $stmt->execute();
                        }
                    }
                }

                // Add editors if any
                if (!empty($_POST['editors']) && is_array($_POST['editors'])) {
                    $insert_editor = "INSERT INTO contributors (book_id, writer_id, role) VALUES (?, ?, 'Editor')";
                    $stmt = $conn->prepare($insert_editor);
                    
                    foreach ($_POST['editors'] as $editorId) {
                        if (!empty($editorId) && (!isset($_POST['co_authors']) || !in_array($editorId, $_POST['co_authors']))) {
                            $stmt->bind_param("ii", $bookId, $editorId);
                            $stmt->execute();
                        }
                    }
                }
            }
        }

        $conn->commit();
        echo "<script>
                alert('Books updated successfully!');
                window.location.href = 'book_list.php';
              </script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>
                alert('Error updating books: " . $e->getMessage() . "');
              </script>";
    }
}

// Get book title from URL parameter and first book ID from the range
$title = isset($_GET['title']) ? $_GET['title'] : '';
$id_range = isset($_GET['id_range']) ? explode('-', $_GET['id_range']) : [];
$first_book_id = !empty($id_range) ? trim($id_range[0]) : null;

// Fetch first book's information
if ($first_book_id) {
    $book_query = "SELECT * FROM books WHERE id = ?";
    $stmt = $conn->prepare($book_query);
    $stmt->bind_param("i", $first_book_id);
    $stmt->execute();
    $first_book_result = $stmt->get_result();
    $first_book = $first_book_result->fetch_assoc();

    // Fetch all books with the same title
    if ($first_book) {
        $books_query = "SELECT * FROM books WHERE title = ?";
        $stmt = $conn->prepare($books_query);
        $stmt->bind_param("s", $first_book['title']);
        $stmt->execute();
        $books_result = $stmt->get_result();
        $books = $books_result->fetch_all(MYSQLI_ASSOC);

        // Fetch contributors for the first book
        $contributors_query = "SELECT w.*, c.role 
                             FROM writers w 
                             JOIN contributors c ON w.id = c.writer_id 
                             WHERE c.book_id = ?";
        $stmt = $conn->prepare($contributors_query);
        $stmt->bind_param("i", $first_book_id);
        $stmt->execute();
        $contributors_result = $stmt->get_result();
        $contributors = $contributors_result->fetch_all(MYSQLI_ASSOC);

        // Fetch publication info for the first book
        $publication_query = "SELECT p.*, pub.id as pub_id, pub.publisher, pub.place 
                             FROM publications p 
                             JOIN publishers pub ON p.publisher_id = pub.id 
                             WHERE p.book_id = ?";
        $stmt->prepare($publication_query);
        $stmt->bind_param("i", $first_book_id);
        $stmt->execute();
        $publication = $stmt->get_result()->fetch_assoc();
    }
}

// Fetch writers for the dropdown
$writers_query = "SELECT id, CONCAT(firstname, ' ', middle_init, ' ', lastname) AS name FROM writers";
$writers_result = mysqli_query($conn, $writers_query);
$writers = [];
while ($row = mysqli_fetch_assoc($writers_result)) {
    $writers[] = $row;
}

// Fetch publishers for the dropdown
$publishers_query = "SELECT id, publisher FROM publishers";
$publishers_result = mysqli_query($conn, $publishers_query);
$publishers = [];
while ($row = mysqli_fetch_assoc($publishers_result)) {
    $publishers[] = $row;
}

// Only keep the main subject options array
$subject_options = array(
    "Topical",
    "Personal",
    "Corporate",
    "Geographical"
);
?>

<!-- Main Content -->
<div id="content-wrapper" class="d-flex flex-column min-vh-100">
    <div id="content" class="flex-grow-1">
        <div class="container-fluid">
            <form id="bookForm" action="" method="POST" enctype="multipart/form-data" class="h-100" 
                  onkeydown="return event.key != 'Enter';">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-2 text-gray-800">Update Books (<?php echo count($books); ?> copies)</h1>
                    <button type="submit" name="submit" class="btn btn-primary">Update Books</button>
                </div>

                <!-- Hidden input for book IDs -->
                <?php foreach ($books as $book): ?>
                    <input type="hidden" name="book_ids[]" value="<?php echo $book['id']; ?>">
                <?php endforeach; ?>

                <!-- Hidden input for statuses -->
                <?php foreach ($books as $book): ?>
                    <input type="hidden" name="statuses[]" value="<?php echo htmlspecialchars($book['status'] ?? 'Available'); ?>">
                <?php endforeach; ?>

                <!-- Tab Navigation -->
                <ul class="nav nav-tabs" id="formTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#title-proper" role="tab">Title Proper</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#subject-entry" role="tab">Access Point</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#abstracts" role="tab">Abstracts</a>
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
                            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($first_book['title'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Preferred Title</label>
                            <input type="text" class="form-control" name="preferred_title" value="<?php echo htmlspecialchars($first_book['preferred_title'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Parallel Title</label>
                            <input type="text" class="form-control" name="parallel_title" value="<?php echo htmlspecialchars($first_book['parallel_title'] ?? ''); ?>">
                        </div>
                        
                        <!-- Contributors section - Updated layout -->
                        <div class="form-group mt-4">
                            <label class="mb-2">Contributors</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Author</label>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                                        <input type="text" class="form-control contributor-search" 
                                               placeholder="Search authors..." data-target="authorSelect">
                                    </div>
                                    <select class="form-control" name="author[]" id="authorSelect" multiple>
                                        <option value="">Select Author</option>
                                        <?php foreach ($writers as $writer): 
                                            $isAuthor = false;
                                            foreach ($contributors as $contributor) {
                                                if ($contributor['id'] == $writer['id'] && $contributor['role'] == 'Author') {
                                                    $isAuthor = true;
                                                    break;
                                                }
                                            }
                                        ?>
                                            <option value="<?php echo $writer['id']; ?>" <?php echo $isAuthor ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($writer['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple items</small>
                                    <div id="authorPreview" class="mt-2 p-2 border rounded bg-light"></div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label>Co-Authors</label>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                                        <input type="text" class="form-control contributor-search" 
                                               placeholder="Search co-authors..." data-target="coAuthorSelect">
                                    </div>
                                    <select class="form-control" name="co_authors[]" id="coAuthorSelect" multiple>
                                        <?php foreach ($writers as $writer): 
                                            $isCoAuthor = false;
                                            foreach ($contributors as $contributor) {
                                                if ($contributor['id'] == $writer['id'] && $contributor['role'] == 'Co-Author') {
                                                    $isCoAuthor = true;
                                                    break;
                                                }
                                            }
                                        ?>
                                            <option value="<?php echo htmlspecialchars($writer['id']); ?>" <?php echo $isCoAuthor ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($writer['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple items</small>
                                    <div id="coAuthorPreview" class="mt-2 p-2 border rounded bg-light"></div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label>Editors</label>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                                        <input type="text" class="form-control contributor-search" 
                                               placeholder="Search editors..." data-target="editorSelect">
                                    </div>
                                    <select class="form-control" name="editors[]" id="editorSelect" multiple>
                                        <?php foreach ($writers as $writer): 
                                            $isEditor = false;
                                            foreach ($contributors as $contributor) {
                                                if ($contributor['id'] == $writer['id'] && $contributor['role'] == 'Editor') {
                                                    $isEditor = true;
                                                    break;
                                                }
                                            }
                                        ?>
                                            <option value="<?php echo htmlspecialchars($writer['id']); ?>" <?php echo $isEditor ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($writer['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple items</small>
                                    <div id="editorPreview" class="mt-2 p-2 border rounded bg-light"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Access Point Tab -->
                    <div class="tab-pane fade" id="subject-entry" role="tabpanel">
                        <h4>Access Point</h4>
                        <div id="subjectEntriesContainer">
                            <div class="subject-entry-group mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Subject Category</label>
                                            <select class="form-control subject-category" name="subject_categories[]">
                                                <option value="">Select Subject Category</option>
                                                <?php foreach ($subject_options as $subject): ?>
                                                    <option value="<?php echo htmlspecialchars($subject); ?>" 
                                                        <?php echo ($first_book['subject_category'] == $subject) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($subject); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Details</label>
                                            <textarea class="form-control" name="subject_paragraphs[]" 
                                                rows="3" placeholder="Enter additional details about this subject"><?php 
                                                echo htmlspecialchars($first_book['subject_detail'] ?? ''); 
                                            ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Abstracts Tab -->
                    <div class="tab-pane fade" id="abstracts" role="tabpanel">
                        <h4>Abstracts</h4>
                        <div class="form-group">
                            <label>Summary/Abstract</label>
                            <textarea class="form-control" name="abstract" rows="6" 
                                placeholder="Enter a summary or abstract of the book"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Contents</label>
                            <textarea class="form-control" name="notes" rows="4" 
                                placeholder="Enter the table of contents or chapter list"></textarea>
                        </div>
                    </div>

                    <!-- Description Tab -->
                    <div class="tab-pane fade" id="description" role="tabpanel">
                        <h4>Description</h4>
                        <div class="form-group">
                            <label>Front Image</label>
                            <input type="file" class="form-control" name="front_image">
                        </div>
                        <div class="form-group">
                            <label>Back Image</label>
                            <input type="file" class="form-control" name="back_image">
                        </div>
                        <div class="form-group">
                            <label>Dimension (cm)</label>
                            <input type="number" step="0.01" class="form-control" name="dimension">
                        </div>
                        <div class="form-group">
                            <label>Pages</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="small">Prefix (Roman)</label>
                                    <input type="text" class="form-control" name="prefix_pages" placeholder="e.g. xvi" 
                                           value="<?php 
                                               $pages = $first_book['total_pages'] ?? '';
                                               $parts = explode(' ', $pages); // Changed from comma to space
                                               echo htmlspecialchars($parts[0] ?? ''); 
                                           ?>">
                                    <small class="text-muted">Use roman numerals</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="small">Main Pages</label>
                                    <input type="text" class="form-control" name="main_pages" placeholder="e.g. 234a"
                                           value="<?php 
                                               $pages = $first_book['total_pages'] ?? '';
                                               $parts = explode(' ', $pages); // Changed from comma to space
                                               echo htmlspecialchars($parts[1] ?? ''); 
                                           ?>">
                                    <small class="text-muted">Can include letters (e.g. 123a, 456b)</small>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label class="small">Supplementary Contents</label>
                                    <select class="form-control" name="supplementary_content[]" multiple>
                                        <?php
                                        // Create array of supplementary content options
                                        $supplementary_options = [
                                            "includes bibliography",
                                            "includes index",
                                            "includes glossary",
                                            "includes appendix",
                                            "includes notes",
                                            "includes references",
                                            "includes bibliography index",
                                            "includes bibliography notes",
                                            "includes bibliography references",
                                            "includes index glossary",
                                            "includes appendices index",
                                            "includes bibliographical references",
                                            "includes bibliography index glossary",
                                            "includes bibliography index notes",
                                            "includes bibliography references index"
                                        ];
                                        
                                        // Get existing supplementary contents as array
                                        $existing_supplementary = explode(', ', $first_book['supplementary_contents'] ?? '');
                                        
                                        // Output each option with selected state if applicable
                                        foreach ($supplementary_options as $option) {
                                            $selected = in_array($option, $existing_supplementary) ? 'selected' : '';
                                            echo "<option value=\"" . htmlspecialchars($option) . "\" $selected>" . 
                                                 htmlspecialchars(ucfirst($option)) . "</option>";
                                        }
                                        ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple items</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Local Information Tab -->
                    <div class="tab-pane fade" id="local-info" role="tabpanel">
                        <h4 class="mt-3 mb-1 w-100">Local Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Modified call numbers section -->
                                <div class="form-group mt-4">
                                    <div id="callNumberContainer">
                                        <label>Classification Number and Author Cutter</label>
                                        <input type="text" class="form-control" name="raw_call_number" 
                                               placeholder="e.g. Z936.98 L39"
                                               value="<?php 
                                                   $call_number = $first_book['call_number'] ?? '';
                                                   
                                                   // Extract only classification and cutter number
                                                   $parts = explode(' ', $call_number);
                                                   
                                                   // Check if we have enough parts to process
                                                   if (count($parts) >= 4) {
                                                       // Remove first part (location code)
                                                       array_shift($parts);
                                                       
                                                       // Remove last two parts (year and copy number)
                                                       array_pop($parts); // Remove last part (copy number)
                                                       array_pop($parts); // Remove second last part (year)
                                                       
                                                       // Join the remaining parts which should be classification and cutter
                                                       echo htmlspecialchars(implode(' ', $parts));
                                                   } else {
                                                       // If there are not enough parts, don't display anything
                                                       echo '';
                                                   }
                                               ?>">
                                        <small class="text-muted">Enter only the classification number and author cutter (e.g., Z936.98 L39) or leave empty if not applicable</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mt-4">
                                    <label>Language</label>
                                    <select class="form-control" name="language">
                                        <option value="English" <?php echo ($first_book['language'] ?? '') == 'English' ? 'selected' : ''; ?>>English</option>
                                        <option value="Spanish" <?php echo ($first_book['language'] ?? '') == 'Spanish' ? 'selected' : ''; ?>>Spanish</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Original Entry By</label>
                                    <input type="text" class="form-control" 
                                           value="<?php 
                                               $entered_by_id = $first_book['entered_by'] ?? '';
                                               $entered_by_name = '';
                                               foreach ($admins as $admin) {
                                                   if ($admin['id'] == $entered_by_id) {
                                                       $entered_by_name = $admin['name'] . ' (' . $admin['role'] . ')';
                                                       break;
                                                   }
                                               }
                                               echo htmlspecialchars($entered_by_name);
                                           ?>" readonly>
                                    <input type="hidden" name="entered_by[]" 
                                           value="<?php echo htmlspecialchars($first_book['entered_by'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Original Entry Date</label>
                                    <input type="text" class="form-control" name="date_added[]" 
                                           value="<?php echo htmlspecialchars($first_book['date_added'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Last Updated By</label>
                                    <?php
                                    // Get updater details if available
                                    $updater_name = 'Not yet updated';
                                    if (!empty($first_book['updated_by'])) {
                                        $updater_query = "SELECT CONCAT(firstname, ' ', lastname) as full_name, role 
                                                        FROM admins WHERE id = ?";
                                        $stmt = $conn->prepare($updater_query);
                                        $stmt->bind_param("i", $first_book['updated_by']);
                                        $stmt->execute();
                                        $updater_result = $stmt->get_result();
                                        if ($updater_data = $updater_result->fetch_assoc()) {
                                            $updater_name = $updater_data['full_name'] . ' (' . $updater_data['role'] . ')';
                                        }
                                    }
                                    ?>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($updater_name); ?>" readonly>
                                    <input type="hidden" name="updated_by" value="<?php echo $_SESSION['admin_id']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Last Update</label>
                                    <input type="text" class="form-control" name="last_update" 
                                           value="<?php echo date('Y-m-d'); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <!-- Display Copy Numbers and Accessions -->
                                <h5>Book Copies</h5>
                                <div id="bookCopiesContainer" class="table-responsive" style="overflow-x: auto;">
                                    <table class="table table-bordered text-center" style="min-width: 1100px;">
                                        <thead>
                                            <tr>
                                                <th>Copy Number</th>
                                                <th>Accession Number</th>
                                                <th>Shelf Location</th>
                                                <th>Call Number</th>
                                                <th>Series</th>
                                                <th>Volume</th>
                                                <th>Edition</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($books as $index => $book): ?>
                                                <tr class="book-copy" data-book-id="<?php echo $book['id']; ?>">
                                                    <td>
                                                        <input type="text" class="form-control text-center" name="copy_number[]" value="<?php echo htmlspecialchars($book['copy_number']); ?>" readonly>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control text-center" name="accession[]" value="<?php echo htmlspecialchars($book['accession']); ?>">
                                                    </td>
                                                    <td>
                                                        <select class="form-control text-center shelf-location" name="shelf_location[]">
                                                            <option value="TR" <?php echo ($book['shelf_location'] == 'TR') ? 'selected' : ''; ?>>Teachers Reference</option>
                                                            <option value="FIL" <?php echo ($book['shelf_location'] == 'FIL') ? 'selected' : ''; ?>>Filipiniana</option>
                                                            <option value="CIR" <?php echo ($book['shelf_location'] == 'CIR') ? 'selected' : ''; ?>>Circulation</option>
                                                            <option value="REF" <?php echo ($book['shelf_location'] == 'REF') ? 'selected' : ''; ?>>Reference</option>
                                                            <option value="SC" <?php echo ($book['shelf_location'] == 'SC') ? 'selected' : ''; ?>>Special Collection</option>
                                                            <option value="BIO" <?php echo ($book['shelf_location'] == 'BIO') ? 'selected' : ''; ?>>Biography</option>
                                                            <option value="RES" <?php echo ($book['shelf_location'] == 'RES') ? 'selected' : ''; ?>>Reserve</option>
                                                            <option value="FIC" <?php echo ($book['shelf_location'] == 'FIC') ? 'selected' : ''; ?>>Fiction</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control text-center" name="call_numbers[]" value="<?php echo htmlspecialchars($book['call_number']); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control text-center" name="series[]" value="<?php echo htmlspecialchars($book['series']); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control text-center" name="volume[]" value="<?php echo htmlspecialchars($book['volume']); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control text-center" name="edition[]" value="<?php echo htmlspecialchars($book['edition']); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control text-center" 
                                                               value="<?php echo htmlspecialchars($book['status'] ?? 'Available'); ?>" readonly>
                                                        <input type="hidden" name="statuses[]" 
                                                               value="<?php echo htmlspecialchars($book['status'] ?? 'Available'); ?>">
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-outline-danger btn-sm delete-copy" title="Delete this copy">
                                                            <i class="fa fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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
                                    <label>Publisher</label>
                                    <select class="form-control" name="publisher">
                                        <option value="">Select Publisher</option>
                                        <?php foreach ($publishers as $publisher): ?>
                                            <option value="<?php echo htmlspecialchars($publisher['publisher']); ?>"
                                            <?php if (isset($publication['publisher']) && $publication['publisher'] == $publisher['publisher']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($publisher['publisher']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Year of Publication</label>
                                    <input type="number" class="form-control" name="publish_date" placeholder="e.g., 2023" 
                                           value="<?php echo htmlspecialchars($publication['publish_date'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ISBN</label>
                                    <input type="text" class="form-control" name="ISBN" 
                                           value="<?php echo htmlspecialchars($first_book['ISBN'] ?? ''); ?>" 
                                           placeholder="Enter ISBN number">
                                    <small class="text-muted">This ISBN will be applied to all copies</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>URL</label>
                                    <input type="text" class="form-control" name="url">
                                </div>
                            </div>
                        </div>

                        <!-- Content Type, Media Type, Carrier Type in One Row -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Content Type</label>
                                    <select class="form-control" name="content_type">
                                        <option value="Text">Text</option>
                                        <option value="Image">Image</option>
                                        <option value="Video">Video</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Media Type</label>
                                    <select class="form-control" name="media_type">
                                        <option value="Print">Print</option>
                                        <option value="Digital">Digital</option>
                                        <option value="Audio">Audio</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Carrier Type</label>
                                    <select class="form-control" name="carrier_type">
                                        <option value="Book">Book</option>
                                        <option value="CD">CD</option>
                                        <option value="USB">USB</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </form>
        </div>
    </div>
    <?php include '../admin/inc/footer.php'; ?>
</div>

<!-- Basic Tab Functionality Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Bootstrap 5 tab initialization
    const triggerTabList = [].slice.call(document.querySelectorAll('#formTabs a'));
    triggerTabList.forEach(function(triggerEl) {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', function(event) {
            event.preventDefault();
            
            // Remove active class from all tabs and panes
            triggerTabList.forEach(tab => {
                tab.classList.remove('active');
                const tabPane = document.querySelector(tab.getAttribute('href'));
                if (tabPane) {
                    tabPane.classList.remove('show', 'active');
                }
            });
            
            // Activate clicked tab and its pane
            this.classList.add('active');
            const targetPane = document.querySelector(this.getAttribute('href'));
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
            
            // Show tab
            tabTrigger.show();
        });
    });
    
    // Ensure first tab is active on page load
    const firstTab = document.querySelector('#formTabs a:first-child');
    if (firstTab) {
        const firstTabTrigger = new bootstrap.Tab(firstTab);
        firstTabTrigger.show();
    }

    // Updated formatCallNumber function
    function formatCallNumber() {
        const rawCallNumber = document.querySelector('input[name="raw_call_number"]').value.trim();
        const publishYear = document.querySelector('input[name="publish_date"]').value;
        const copies = document.querySelectorAll('.book-copy');
        
        copies.forEach((copy) => {
            const copyNumberInput = copy.querySelector('input[name="copy_number[]"]');
            const copyNum = copyNumberInput.value.replace(/^c/, '');
            const shelfLocation = copy.querySelector('select[name="shelf_location[]"]').value;
            const volumeInput = copy.querySelector('input[name="volume[]"]');
            const volumeText = volumeInput && volumeInput.value.trim() ? `vol${volumeInput.value.trim()} ` : '';
            
            // Validate raw call number format (only classification and cutter)
            if (!/^[A-Z0-9]+(\.[0-9]+)?\s[A-Z][0-9]+$/.test(rawCallNumber)) {
                console.warn('Call number format should be like "Z936.98 L39"');
            }
            
            // Store complete call number in hidden input
            const formattedCallNumber = [
                shelfLocation,         // Location code (e.g., TR)
                rawCallNumber,         // Classification and cutter (e.g., Z936.98 L39)
                publishYear            // Publication year
            ].filter(Boolean).join(' ');
            
            // Append volume number if present
            let finalCallNumber = formattedCallNumber;
            if (volumeText) {
                finalCallNumber += ` ${volumeText}`;
            }
            
            // Append copy number
            finalCallNumber += ` c${copyNum}`;
            
            // Update hidden input with complete call number
            const callNumberField = copy.querySelector('input[name="call_numbers[]"]');
            if (callNumberField) {
                callNumberField.value = finalCallNumber;
            }

            // Display only classification and cutter in the display element
            const displayElement = copy.querySelector('.call-number-display');
            if (displayElement) {
                displayElement.textContent = rawCallNumber; // Only show classification and cutter
            }
        });
    }

    // Single, unified updateCopyNumbers function
    function updateCopyNumbers() {
        formatCallNumber(); // Just update the call numbers without changing copy numbers
    }

    // Set up event listeners once
    function setupEventListeners() {
        const rawCallNumber = document.querySelector('input[name="raw_call_number"]');
        const publishYear = document.querySelector('input[name="publish_date"]');
        const shelfLocations = document.querySelectorAll('select[name="shelf_location[]"]');
        const volumeInputs = document.querySelectorAll('input[name="volume[]"]');

        if (rawCallNumber) rawCallNumber.addEventListener('input', formatCallNumber);
        if (publishYear) publishYear.addEventListener('input', formatCallNumber);
        
        shelfLocations.forEach(select => {
            select.addEventListener('change', formatCallNumber);
        });
        
        // Add event listeners to all volume inputs
        volumeInputs.forEach(input => {
            input.addEventListener('input', function() {
                formatCallNumber(); // Update call numbers when volume changes
            });
        });
    }

    // Initialize on page load
    setupEventListeners();
    updateCopyNumbers();

    // Replace the existing shelf location event listeners with this new implementation
    const shelfLocationSelects = document.querySelectorAll('select[name="shelf_location[]"]');
    shelfLocationSelects.forEach((select, index) => {
        select.addEventListener('change', function() {
            const newValue = this.value;
            const totalSelects = shelfLocationSelects.length;

            // If changing the first copy, update all subsequent copies
            if (index === 0) {
                for (let i = 1; i < totalSelects; i++) {
                    shelfLocationSelects[i].value = newValue;
                }
            } else {
                // For any other copy, only update copies that come after it
                for (let i = index + 1; i < totalSelects; i++) {
                    shelfLocationSelects[i].value = newValue;
                }
                // Copies before this one remain unchanged
            }

            // Trigger call number update after changing shelf locations
            formatCallNumber();
        });
    });
});

// Add accession number validation
document.addEventListener('DOMContentLoaded', function() {
    const accessionInputs = document.querySelectorAll('input[name="accession[]"]');
    
    accessionInputs.forEach(input => {
        input.addEventListener('change', async function() {
            const bookId = this.closest('.book-copy').querySelector('input[name="book_ids[]"]').value;
            const accession = this.value;
            
            try {
                const response = await fetch('validate_accession.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        accession: accession,
                        bookId: bookId
                    })
                });
                
                const data = await response.json();
                
                if (!data.valid) {
                    alert('This accession number is already in use by another book.');
                    this.value = this.defaultValue; // Reset to original value
                    this.focus();
                } else {
                    this.defaultValue = this.value; // Update original value if valid
                }
            } catch (error) {
                console.error('Error validating accession:', error);
            }
        });
    });
});

// Add this at the start of your existing script
document.addEventListener('DOMContentLoaded', function() {
    // Prevent form submission on enter key
    document.getElementById('bookForm').addEventListener('keypress', function(e) {
        if (e.keyCode === 13 || e.key === 'Enter') {
            e.preventDefault();
            return false;
        }
    });

    // Only allow form submission via the submit button
    document.getElementById('bookForm').onsubmit = function(e) {
        if (e.submitter && e.submitter.type === 'submit') {
            return validateForm(e);
        }
        e.preventDefault();
        return false;
    };

    // ...rest of your existing script...
});

// Replace both existing delete event listener blocks with this single implementation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-copy').forEach(function(button) {
        button.addEventListener('click', async function() {
            const bookCopyRow = this.closest('.book-copy');
            const bookId = bookCopyRow.getAttribute('data-book-id');
            const bookTitle = document.querySelector('input[name="title"]').value;
            
            // Use SweetAlert2 for the confirmation dialog
            const result = await Swal.fire({
                title: 'Delete Confirmation',
                text: 'Are you sure you want to delete this copy?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch('delete_copy.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ bookId: bookId })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        bookCopyRow.remove();
                        
                        // Check if this was the last copy
                        const remainingCopies = document.querySelectorAll('.book-copy').length;
                        if (remainingCopies === 0) {
                            await Swal.fire({
                                title: 'Book Deleted',
                                text: `All copies of "${bookTitle}" have been deleted.`,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                            // Redirect to book list page
                            window.location.href = 'book_list.php';
                        }
                    } else {
                        await Swal.fire({
                            title: 'Error',
                            text: data.error || 'Failed to delete the copy.',
                            icon: 'error'
                        });
                    }
                } catch (error) {
                    console.error('Deletion error:', error);
                    await Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while deleting the copy.',
                        icon: 'error'
                    });
                }
            }
        });
    });
});

// Add preview function for total pages
document.addEventListener('DOMContentLoaded', function() {
    // Get page input elements
    const prefixPagesInput = document.querySelector('input[name="prefix_pages"]');
    const mainPagesInput = document.querySelector('input[name="main_pages"]');
    
    // Create a display element for previewing total pages
    const pageContainer = document.querySelector('.form-group label:contains("Pages")').closest('.form-group');
    const previewElement = document.createElement('div');
    previewElement.className = 'alert alert-info mt-2';
    previewElement.style.display = 'none';
    previewElement.innerHTML = '<strong>Total pages will appear as:</strong> <span id="pagesPreview"></span>';
    pageContainer.appendChild(previewElement);
    
    // Update preview function
    function updatePagesPreview() {
        const prefix = prefixPagesInput.value.trim();
        const main = mainPagesInput.value.trim();
        const previewSpan = document.getElementById('pagesPreview');
        
        if (prefix && main) {
            previewSpan.textContent = `${prefix} ${main}`; // Changed from comma to space
            previewElement.style.display = 'block';
        } else if (prefix) {
            previewSpan.textContent = prefix;
            previewElement.style.display = 'block';
        } else if (main) {
            previewSpan.textContent = main;
            previewElement.style.display = 'block';
        } else {
            previewElement.style.display = 'none';
        }
    }
    
    // Add event listeners
    if (prefixPagesInput) prefixPagesInput.addEventListener('input', updatePagesPreview);
    if (mainPagesInput) mainPagesInput.addEventListener('input', updatePagesPreview);
    
    // Initialize preview
    updatePagesPreview();
});

// Preview functionality for contributors
function updateContributorPreviews() {
    const authorSelect = document.getElementById('authorSelect');
    const coAuthorSelect = document.getElementById('coAuthorSelect');
    const editorSelect = document.getElementById('editorSelect');
    const authorPreview = document.getElementById('authorPreview');
    const coAuthorPreview = document.getElementById('coAuthorPreview');
    const editorPreview = document.getElementById('editorPreview');

    // Update author preview
    let authorHtml = '';
    for (const option of authorSelect.selectedOptions) {
        if (option.value) {
            authorHtml += `<span class="badge bg-primary text-white me-1 mb-1" data-id="${option.value}" data-role="author">
                           ${option.text} <i class="fa fa-times ms-1" style="cursor:pointer;" onclick="removeContributor(this)"></i></span>`;
        }
    }
    authorPreview.innerHTML = authorHtml || '<em class="text-muted">No authors selected</em>';

    // Update co-author preview
    let coAuthorHtml = '';
    for (const option of coAuthorSelect.selectedOptions) {
        if (option.value) {
            coAuthorHtml += `<span class="badge bg-secondary text-white me-1 mb-1" data-id="${option.value}" data-role="co-author">
                            ${option.text} <i class="fa fa-times ms-1" style="cursor:pointer;" onclick="removeContributor(this)"></i></span>`;
        }
    }
    coAuthorPreview.innerHTML = coAuthorHtml || '<em class="text-muted">No co-authors selected</em>';

    // Update editor preview
    let editorHtml = '';
    for (const option of editorSelect.selectedOptions) {
        if (option.value) {
            editorHtml += `<span class="badge bg-info text-white me-1 mb-1" data-id="${option.value}" data-role="editor">
                          ${option.text} <i class="fa fa-times ms-1" style="cursor:pointer;" onclick="removeContributor(this)"></i></span>`;
        }
    }
    editorPreview.innerHTML = editorHtml || '<em class="text-muted">No editors selected</em>';
}

// Function to remove contributor when X is clicked
function removeContributor(element) {
    const badge = element.parentElement;
    const id = badge.dataset.id;
    const role = badge.dataset.role;
    
    let selectId;
    if (role === 'author') {
        selectId = 'authorSelect';
    } else if (role === 'co-author') {
        selectId = 'coAuthorSelect';
    } else if (role === 'editor') {
        selectId = 'editorSelect';
    }
    
    const select = document.getElementById(selectId);
    if (select) {
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value === id) {
                select.options[i].selected = false;
                break;
            }
        }
        // Trigger change event to update preview
        select.dispatchEvent(new Event('change'));
    }
}

// Set up event listeners for contributor select changes
const contributorSelects = ['authorSelect', 'coAuthorSelect', 'editorSelect'];
contributorSelects.forEach(id => {
    const select = document.getElementById(id);
    if (select) {
        select.addEventListener('change', updateContributorPreviews);
    }
});

// Initialize previews
document.addEventListener('DOMContentLoaded', function() {
    updateContributorPreviews();
});

// Add search functionality for contributor dropdowns
document.addEventListener('DOMContentLoaded', function() {
    // Filter function for contributor search
    const searchContributors = function() {
        const searchInput = this;
        const searchTerm = searchInput.value.toLowerCase();
        const selectId = searchInput.dataset.target;
        const selectElement = document.getElementById(selectId);
        
        // Store selected options to maintain them
        const selectedOptions = [];
        for (const option of selectElement.selectedOptions) {
            selectedOptions.push(option.value);
        }
        
        // Loop through all options to filter
        for (let i = 0; i < selectElement.options.length; i++) {
            const option = selectElement.options[i];
            const optionText = option.textContent.toLowerCase();
            
            // Skip the empty "Select" option
            if (option.value === "") continue;
            
            // Check if option text contains search term
            if (optionText.includes(searchTerm)) {
                option.style.display = "";
            } else {
                option.style.display = "none";
            }
        }
        
        // Restore selected options
        for (let i = 0; i < selectElement.options.length; i++) {
            if (selectedOptions.includes(selectElement.options[i].value)) {
                selectElement.options[i].selected = true;
            }
        }
    };
    
    // Add event listeners to search inputs
    const searchInputs = document.querySelectorAll('.contributor-search');
    searchInputs.forEach(input => {
        input.addEventListener('input', searchContributors);
    });
    
    // Function to clear search and reset dropdown
    const clearSearch = function(selectId) {
        const searchInput = document.querySelector(`[data-target="${selectId}"]`);
        if (searchInput) {
            searchInput.value = '';
            const event = new Event('input');
            searchInput.dispatchEvent(event);
        }
    };
    
    // Clear search when dropdown changes to ensure all options are visible for next interaction
    document.getElementById('authorSelect').addEventListener('change', function() {
        clearSearch('authorSelect');
    });
    
    document.getElementById('coAuthorSelect').addEventListener('change', function() {
        clearSearch('coAuthorSelect');
    });
    
    document.getElementById('editorSelect').addEventListener('change', function() {
        clearSearch('editorSelect');
    });
});

</script>
