<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../db.php';
include '../admin/inc/header.php';

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
        $total_pages = $_POST['total_pages'] ?? array();
        $supplementary_contents = $_POST['supplementary_contents'] ?? array();
        $entered_by = $_POST['entered_by'] ?? array();
        $date_added = $_POST['date_added'] ?? array();

        // Common data for all copies
        $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
        $preferred_title = mysqli_real_escape_string($conn, $_POST['preferred_title'] ?? '');
        $parallel_title = mysqli_real_escape_string($conn, $_POST['parallel_title'] ?? '');
        $call_number = mysqli_real_escape_string($conn, $_POST['call_number'] ?? '');
        $language = mysqli_real_escape_string($conn, $_POST['language'] ?? '');
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? '');
        $abstract = mysqli_real_escape_string($conn, $_POST['abstract'] ?? '');
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        $dimension = mysqli_real_escape_string($conn, $_POST['dimension'] ?? '');
        $series = mysqli_real_escape_string($conn, $_POST['series'] ?? '');
        $volume = mysqli_real_escape_string($conn, $_POST['volume'] ?? '');
        $edition = mysqli_real_escape_string($conn, $_POST['edition'] ?? '');
        $url = mysqli_real_escape_string($conn, $_POST['url'] ?? '');
        $content_type = mysqli_real_escape_string($conn, $_POST['content_type'] ?? 'Text');
        $media_type = mysqli_real_escape_string($conn, $_POST['media_type'] ?? 'Print');
        $carrier_type = mysqli_real_escape_string($conn, $_POST['carrier_type'] ?? 'Book');
        $last_update = date('Y-m-d');

        // Safely access array elements
        $subject_category = '';
        if (isset($_POST['subject_categories']) && !empty($_POST['subject_categories'])) {
            $subject_category = mysqli_real_escape_string($conn, $_POST['subject_categories'][0]);
        }

        $subject_detail = '';
        if (isset($_POST['subject_paragraphs']) && !empty($_POST['subject_paragraphs'])) {
            $subject_detail = mysqli_real_escape_string($conn, $_POST['subject_paragraphs'][0]);
        }

        // Get admin info for update tracking
        $current_admin_id = $_SESSION['admin_id'];
        $update_date = date('Y-m-d');

        // Update each book copy
        foreach ($bookIds as $index => $bookId) {
            // Get original entered_by and date_added for this specific copy
            $original_data_query = "SELECT entered_by, date_added FROM books WHERE id = ?";
            $stmt = $conn->prepare($original_data_query);
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $original_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            // Safe array access with proper checks for each array
            $shelf_location = isset($shelf_locations[$index]) ? mysqli_real_escape_string($conn, $shelf_locations[$index]) : '';
            $call_number = isset($call_numbers[$index]) ? mysqli_real_escape_string($conn, $call_numbers[$index]) : '';
            $accession = isset($accessions[$index]) ? mysqli_real_escape_string($conn, $accessions[$index]) : '';
            
            // Use original data for entered_by and date_added
            $entered_by_value = $original_data['entered_by'];
            $date_added_value = $original_data['date_added'];
            
            // Get copy-specific values
            $url = isset($_POST['url']) ? mysqli_real_escape_string($conn, $_POST['url']) : '';
            $content_type = isset($_POST['content_type']) ? mysqli_real_escape_string($conn, $_POST['content_type']) : 'Text';
            $media_type = isset($_POST['media_type']) ? mysqli_real_escape_string($conn, $_POST['media_type']) : 'Print';
            $carrier_type = isset($_POST['carrier_type']) ? mysqli_real_escape_string($conn, $_POST['carrier_type']) : 'Book';
            $total_page = isset($total_pages[$index]) ? mysqli_real_escape_string($conn, $total_pages[$index]) : '';
            $supplementary_content = isset($supplementary_contents[$index]) ? mysqli_real_escape_string($conn, $supplementary_contents[$index]) : '';

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
                $series,
                $volume,
                $edition,
                $total_page,
                $supplementary_content,
                $ISBN,
                $content_type,
                $media_type,
                $carrier_type,
                $call_number,
                $url,
                $language,
                $shelf_location,
                $entered_by_value,    // Use preserved value for this specific copy
                $date_added_value,    // Use preserved value for this specific copy
                $status,
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

        // Update contributors - MODIFIED SECTION
        if (!empty($_POST['author'])) {
            foreach ($bookIds as $bookId) {
                // Remove existing contributors
                $delete_query = "DELETE FROM contributors WHERE book_id = ?";
                $stmt = $conn->prepare($delete_query);
                $stmt->bind_param("i", $bookId);
                $stmt->execute();

                // Add author
                $insert_author = "INSERT INTO contributors (book_id, writer_id, role) VALUES (?, ?, 'Author')";
                $stmt = $conn->prepare($insert_author);
                $stmt->bind_param("ii", $bookId, $_POST['author']);
                $stmt->execute();

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
                        <!-- Contributors section -->
                        <div class="form-group">
                            <label>Author</label>
                            <select class="form-control" name="author" required>
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
                        </div>
                        <div class="form-group">
                            <label>Co-Authors</label>
                            <select class="form-control" name="co_authors[]" multiple>
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
                        </div>
                        <div class="form-group">
                            <label>Editors</label>
                            <select class="form-control" name="editors[]" multiple>
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
                                    <input type="text" class="form-control" name="prefix_pages" placeholder="e.g. xvi">
                                    <small class="text-muted">Use roman numerals</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="small">Main Pages</label>
                                    <input type="text" class="form-control" name="main_pages" placeholder="e.g. 234a">
                                    <small class="text-muted">Can include letters (e.g. 123a, 456b)</small>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label class="small">Supplementary Contents</label>
                                    <select class="form-control" name="supplementary_content[]" multiple>
                                        <!-- Standard Library Terms -->
                                        <option value="includes bibliography">Includes bibliography</option>
                                        <option value="includes index">Includes index</option>
                                        <option value="includes glossary">Includes glossary</option>
                                        <option value="includes appendix">Includes appendix</option>
                                        <option value="includes notes">Includes notes</option>
                                        <option value="includes references">Includes references</option>
                                        
                                        <!-- Common Library Combinations -->
                                        <option value="includes bibliography index">Includes bibliography and index</option>
                                        <option value="includes bibliography notes">Includes bibliography and notes</option>
                                        <option value="includes bibliography references">Includes bibliography and references</option>
                                        <option value="includes index glossary">Includes index and glossary</option>
                                        <option value="includes appendices index">Includes appendices and index</option>
                                        <option value="includes bibliographical references">Includes bibliographical references</option>
                                        
                                        <!-- Standard Multi-component Options -->
                                        <option value="includes bibliography index glossary">Includes bibliography, index, and glossary</option>
                                        <option value="includes bibliography index notes">Includes bibliography, index, and notes</option>
                                        <option value="includes bibliography references index">Includes bibliography, references, and index</option>
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
                                                   // Keep only the middle parts (classification and cutter)
                                                   if (count($parts) > 3) {
                                                       array_shift($parts); // Remove location code
                                                       array_pop($parts);   // Remove copy number
                                                       array_pop($parts);   // Remove year
                                                   }
                                                   echo htmlspecialchars(implode(' ', $parts));
                                               ?>" required>
                                        <small class="text-muted">Enter only the classification number and author cutter (e.g., Z936.98 L39)</small>
                                        <input type="hidden" name="call_number" value="<?php echo htmlspecialchars($first_book['call_number'] ?? ''); ?>">
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
                                           value="<?php echo htmlspecialchars($first_book['entered_by'] ?? ''); ?>" readonly>
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
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select class="form-control" name="status">
                                        <option value="Available" <?php echo ($first_book['status'] ?? '') == 'Available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="Borrowed" <?php echo ($first_book['status'] ?? '') == 'Borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                                        <option value="Lost" <?php echo ($first_book['status'] ?? '') == 'Lost' ? 'selected' : ''; ?>>Lost</option>
                                    </select>
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
                                <div id="bookCopiesContainer">
                                    <?php foreach ($books as $index => $book): ?>
                                        <div class="row book-copy align-items-center mb-1">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Copy Number</label>
                                                    <input type="text" class="form-control" name="copy_number[]" value="<?php echo htmlspecialchars($book['copy_number']); ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Accession Number</label>
                                                    <input type="text" class="form-control" name="accession[]" value="<?php echo htmlspecialchars($book['accession']); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Shelf Location</label>
                                                    <select class="form-control shelf-location" name="shelf_location[]">
                                                        <option value="TR" <?php echo ($book['shelf_location'] == 'TR') ? 'selected' : ''; ?>>Teachers Reference</option>
                                                        <option value="FIL" <?php echo ($book['shelf_location'] == 'FIL') ? 'selected' : ''; ?>>Filipiniana</option>
                                                        <option value="CIR" <?php echo ($book['shelf_location'] == 'CIR') ? 'selected' : ''; ?>>Circulation</option>
                                                        <option value="REF" <?php echo ($book['shelf_location'] == 'REF') ? 'selected' : ''; ?>>Reference</option>
                                                        <option value="SC" <?php echo ($book['shelf_location'] == 'SC') ? 'selected' : ''; ?>>Special Collection</option>
                                                        <option value="BIO" <?php echo ($book['shelf_location'] == 'BIO') ? 'selected' : ''; ?>>Biography</option>
                                                        <option value="RES" <?php echo ($book['shelf_location'] == 'RES') ? 'selected' : ''; ?>>Reserve</option>
                                                        <option value="FIC" <?php echo ($book['shelf_location'] == 'FIC') ? 'selected' : ''; ?>>Fiction</option>
                                                    </select>
                                                    <input type="hidden" name="call_numbers[]" value="<?php echo htmlspecialchars($book['call_number']); ?>">
                                                    <div class="call-number-display small text-muted mt-1">
                                                        <?php echo htmlspecialchars($book['call_number']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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
                                    <label>Series</label>
                                    <input type="text" class="form-control" name="series">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Volume</label>
                                    <input type="text" class="form-control" name="volume">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Edition</label>
                                    <input type="text" class="form-control" name="edition">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ISBN</label>
                                    <input type="text" class="form-control" name="ISBN" 
                                           value="<?php echo htmlspecialchars($first_book['ISBN'] ?? ''); ?>" 
                                           placeholder="Enter ISBN number">
                                    <small class="text-muted">This ISBN will be applied to all copies</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>URL</label>
                                    <input type="text" class="form-control" name="url">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Content Type</label>
                                    <select class="form-control" name="content_type">
                                        <option value="Text">Text</option>
                                        <option value="Image">Image</option>
                                        <option value="Video">Video</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Content Type, Media Type, Carrier Type in One Row -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Media Type</label>
                                    <select class="form-control" name="media_type">
                                        <option value="Print">Print</option>
                                        <option value="Digital">Digital</option>
                                        <option value="Audio">Audio</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
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
            
            // Validate raw call number format (only classification and cutter)
            if (!/^[A-Z0-9]+(\.[0-9]+)?\s[A-Z][0-9]+$/.test(rawCallNumber)) {
                console.warn('Call number format should be like "Z936.98 L39"');
            }
            
            // Store complete call number in hidden input
            const formattedCallNumber = [
                shelfLocation,      // Location code (e.g., TR)
                rawCallNumber,      // Classification and cutter (e.g., Z936.98 L39)
                publishYear,        // Publication year
                `c${copyNum}`      // Copy number
            ].filter(Boolean).join(' ');
            
            // Update hidden input with complete call number
            const callNumberField = copy.querySelector('input[name="call_numbers[]"]');
            if (callNumberField) {
                callNumberField.value = formattedCallNumber;
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

        if (rawCallNumber) rawCallNumber.addEventListener('input', formatCallNumber);
        if (publishYear) publishYear.addEventListener('input', formatCallNumber);
        shelfLocations.forEach(select => {
            select.addEventListener('change', formatCallNumber);
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
</script>
