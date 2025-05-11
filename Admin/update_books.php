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

// Fetch writers for the dropdown
$writers_query = "SELECT id, CONCAT(firstname, ' ', middle_init, ' ', lastname) AS name FROM writers";
$writers_result = mysqli_query($conn, $writers_query);
$writers = [];
while ($row = mysqli_fetch_assoc($writers_result)) {
    $writers[] = $row;
}

// Fetch publishers for the dropdown
$publishers_query = "SELECT id, publisher, place FROM publishers";
$publishers_result = mysqli_query($conn, $publishers_query);
$publishers = [];
while ($row = mysqli_fetch_assoc($publishers_result)) {
    $publishers[] = $row;
}

// Fetch corporations for the dropdown
$corporates_query = "SELECT id, name, type FROM corporates ORDER BY name";
$corporates_result = mysqli_query($conn, $corporates_query);
$corporates = [];
while ($row = mysqli_fetch_assoc($corporates_result)) {
    $corporates[] = $row;
}

// Only keep the main subject options array
$subject_options = array(
    "Topical",
    "Personal",
    "Corporate",
    "Geographical"
);

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

        // Handle image removal requests
        if (isset($_POST['remove_front_image']) && $_POST['remove_front_image'] == 1) {
            // Get current front image paths before removal
            $get_images_query = "SELECT id, front_image FROM books WHERE id IN (" . implode(',', $bookIds) . ") AND front_image IS NOT NULL";
            $image_result = $conn->query($get_images_query);
            while ($row = $image_result->fetch_assoc()) {
                if (!empty($row['front_image'])) {
                    // Debug info
                    error_log("Attempting to delete front image: " . $row['front_image'] . " for book ID " . $row['id']);
                    
                    $deleted = deleteImageFile($row['front_image']);
                    if (!$deleted) {
                        error_log("Failed to delete front image: " . $row['front_image']);
                    }
                }
            }
            
            // Update all books in the batch to remove front image
            $remove_front_image = "UPDATE books SET front_image = NULL WHERE id IN (" . implode(',', $bookIds) . ")";
            $conn->query($remove_front_image);
        }
        
        if (isset($_POST['remove_back_image']) && $_POST['remove_back_image'] == 1) {
            // Get current back image paths before removal
            $get_images_query = "SELECT id, back_image FROM books WHERE id IN (" . implode(',', $bookIds) . ") AND back_image IS NOT NULL";
            $image_result = $conn->query($get_images_query);
            while ($row = $image_result->fetch_assoc()) {
                if (!empty($row['back_image'])) {
                    // Debug info
                    error_log("Attempting to delete back image: " . $row['back_image'] . " for book ID " . $row['id']);
                    
                    $deleted = deleteImageFile($row['back_image']);
                    if (!$deleted) {
                        error_log("Failed to delete back image: " . $row['back_image']);
                    }
                }
            }
            
            // Update all books in the batch to remove back image
            $remove_back_image = "UPDATE books SET back_image = NULL WHERE id IN (" . implode(',', $bookIds) . ")";
            $conn->query($remove_back_image);
        }

        // Process front image upload if provided
        if (!empty($_FILES['front_image']['name'])) {
            // Get current front image paths before replacement
            $get_images_query = "SELECT id, front_image FROM books WHERE id IN (" . implode(',', $bookIds) . ") AND front_image IS NOT NULL";
            $image_result = $conn->query($get_images_query);
            $old_images = [];
            while ($row = $image_result->fetch_assoc()) {
                if (!empty($row['front_image'])) {
                    $old_images[] = [
                        'id' => $row['id'],
                        'path' => $row['front_image']
                    ];
                }
            }
            
            $frontImageFileName = processImageUpload('front_image', $bookIds[0]);
            
            if ($frontImageFileName) {
                // Update all books in the batch with the same front image
                $update_front_image = "UPDATE books SET front_image = ? WHERE id IN (" . implode(',', $bookIds) . ")";
                $stmt = $conn->prepare($update_front_image);
                $stmt->bind_param("s", $frontImageFileName);
                $stmt->execute();
                
                // Delete old images after successful update
                foreach ($old_images as $old_image) {
                    // Debug info
                    error_log("Attempting to delete old front image: " . $old_image['path'] . " for book ID " . $old_image['id']);
                    
                    $deleted = deleteImageFile($old_image['path']);
                    if (!$deleted) {
                        error_log("Failed to delete old front image: " . $old_image['path']);
                    }
                }
            }
        }
        
        // Process back image upload if provided
        if (!empty($_FILES['back_image']['name'])) {
            // Get current back image paths before replacement
            $get_images_query = "SELECT id, back_image FROM books WHERE id IN (" . implode(',', $bookIds) . ") AND back_image IS NOT NULL";
            $image_result = $conn->query($get_images_query);
            $old_images = [];
            while ($row = $image_result->fetch_assoc()) {
                if (!empty($row['back_image'])) {
                    $old_images[] = [
                        'id' => $row['id'],
                        'path' => $row['back_image']
                    ];
                }
            }
            
            $backImageFileName = processImageUpload('back_image', $bookIds[0]);
            
            if ($backImageFileName) {
                // Update all books in the batch with the same back image
                $update_back_image = "UPDATE books SET back_image = ? WHERE id IN (" . implode(',', $bookIds) . ")";
                $stmt = $conn->prepare($update_back_image);
                $stmt->bind_param("s", $backImageFileName);
                $stmt->execute();
                
                // Delete old images after successful update
                foreach ($old_images as $old_image) {
                    // Debug info
                    error_log("Attempting to delete old back image: " . $old_image['path'] . " for book ID " . $old_image['id']);
                    
                    $deleted = deleteImageFile($old_image['path']);
                    if (!$deleted) {
                        error_log("Failed to delete old back image: " . $old_image['path']);
                    }
                }
            }
        }

        // Update general book information (applied to all books in the batch)
        $title = $_POST['title'] ?? '';
        $preferred_title = $_POST['preferred_title'] ?? '';
        $parallel_title = $_POST['parallel_title'] ?? '';
        $subject_category = $_POST['subject_category'] ?? '';
        $program = $_POST['program'] ?? '';
        $subject_detail = $_POST['subject_detail'] ?? '';
        $summary = $_POST['summary'] ?? '';
        $contents = $_POST['contents'] ?? '';
        $dimension = $_POST['dimension'] ?? '';
        $total_pages = $_POST['total_pages'] ?? '';
        $content_type = $_POST['content_type'] ?? '';
        $media_type = $_POST['media_type'] ?? '';
        $carrier_type = $_POST['carrier_type'] ?? '';
        $language = $_POST['language'] ?? '';
        $url = $_POST['URL'] ?? '';
        $isbn = $_POST['ISBN'] ?? '';
        
        // Process supplementary contents (selected multiple options)
        $supplementary_contents = '';
        if (isset($_POST['supplementary_contents']) && is_array($_POST['supplementary_contents'])) {
            $supplementary_contents = "includes " . implode(', ', $_POST['supplementary_contents']);
        }
        
        // Update general information for all books in the batch
        $update_general = "UPDATE books SET 
            title = ?,
            preferred_title = ?,
            parallel_title = ?,
            subject_category = ?,
            program = ?, 
            subject_detail = ?,
            summary = ?,
            contents = ?,
            dimension = ?,
            total_pages = ?,
            supplementary_contents = ?,
            content_type = ?,
            media_type = ?,
            carrier_type = ?,
            language = ?,
            URL = ?,
            ISBN = ?,
            updated_by = ?,
            last_update = CURRENT_DATE
            WHERE id IN (" . implode(',', $bookIds) . ")";
        
        $stmt = $conn->prepare($update_general);
        $admin_id = $_SESSION['admin_id']; // Get current admin ID from session
        $stmt->bind_param("sssssssssssssssssi", 
            $title, $preferred_title, $parallel_title, 
            $subject_category, $program, $subject_detail,
            $summary, $contents, $dimension, $total_pages,
            $supplementary_contents, $content_type, $media_type,
            $carrier_type, $language, $url, $isbn, $admin_id
        );
        $stmt->execute();
        
        // Update individual copy information
        $copy_numbers = $_POST['copy_number'];
        $individual_series = $_POST['individual_series'];
        $individual_volumes = $_POST['individual_volume'];
        $individual_parts = $_POST['individual_part'];
        $individual_editions = $_POST['individual_edition'];
        $individual_call_numbers = $_POST['individual_call_number'];
        $statuses = $_POST['status'];
        $shelf_locations = $_POST['shelf_location'];
        
        // Prepare statement for updating individual books
        $update_individual = "UPDATE books SET 
            accession = ?,
            copy_number = ?,
            series = ?,
            volume = ?,
            part = ?,
            edition = ?,
            call_number = ?,
            status = ?,
            shelf_location = ?
            WHERE id = ?";
        
        $stmt = $conn->prepare($update_individual);
        
        for ($i = 0; $i < count($bookIds); $i++) {
            $stmt->bind_param("sisssssssi", 
                $accessions[$i],
                $copy_numbers[$i],
                $individual_series[$i],
                $individual_volumes[$i],
                $individual_parts[$i],
                $individual_editions[$i],
                $individual_call_numbers[$i],
                $statuses[$i],
                $shelf_locations[$i],
                $bookIds[$i]
            );
            $stmt->execute();
        }

        // Update publication information
        $publisher_id = $_POST['publisher_id'] ?? null;
        $publish_date = $_POST['publish_date'] ?? null;
        
        if (!empty($publisher_id)) {
            // Check if publication record exists for each book
            foreach ($bookIds as $bookId) {
                $check_pub_query = "SELECT id FROM publications WHERE book_id = ?";
                $stmt = $conn->prepare($check_pub_query);
                $stmt->bind_param("i", $bookId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update existing publication
                    $update_pub = "UPDATE publications SET publisher_id = ?, publish_date = ? WHERE book_id = ?";
                    $stmt = $conn->prepare($update_pub);
                    $stmt->bind_param("isi", $publisher_id, $publish_date, $bookId);
                    $stmt->execute();
                } else {
                    // Insert new publication record
                    $insert_pub = "INSERT INTO publications (book_id, publisher_id, publish_date) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($insert_pub);
                    $stmt->bind_param("iis", $bookId, $publisher_id, $publish_date);
                    $stmt->execute();
                }
            }
        }

        // Update contributors
        foreach ($bookIds as $bookId) {
            // Remove existing contributors
            $delete_query = "DELETE FROM contributors WHERE book_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $bookId);
            $stmt->execute();

            // Remove existing corporate contributors
            $delete_corp_query = "DELETE FROM corporate_contributors WHERE book_id = ?";
            $stmt = $conn->prepare($delete_corp_query);
            $stmt->bind_param("i", $bookId);
            $stmt->execute();

            // Add individual contributors
            if (!empty($_POST['contributor_ids']) && !empty($_POST['contributor_roles'])) {
                $insert_contributor = "INSERT INTO contributors (book_id, writer_id, role) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($insert_contributor);

                for ($i = 0; $i < count($_POST['contributor_ids']); $i++) {
                    if (!empty($_POST['contributor_ids'][$i]) && !empty($_POST['contributor_roles'][$i])) {
                        $contributorId = $_POST['contributor_ids'][$i];
                        $role = $_POST['contributor_roles'][$i];
                        $stmt->bind_param("iis", $bookId, $contributorId, $role);
                        $stmt->execute();
                    }
                }
            }

            // Add corporate contributors
            if (!empty($_POST['corporate_ids']) && !empty($_POST['corporate_roles'])) {
                $insert_corp_contributor = "INSERT INTO corporate_contributors (book_id, corporate_id, role) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($insert_corp_contributor);

                for ($i = 0; $i < count($_POST['corporate_ids']); $i++) {
                    if (!empty($_POST['corporate_ids'][$i]) && !empty($_POST['corporate_roles'][$i])) {
                        $corporateId = $_POST['corporate_ids'][$i];
                        $role = $_POST['corporate_roles'][$i];
                        $stmt->bind_param("iis", $bookId, $corporateId, $role);
                        $stmt->execute();
                    }
                }
            }
        }

        $conn->commit();
        echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Books updated successfully!',
                    confirmButtonColor: '#4e73df',
                    confirmButtonText: 'View Books'
                }).then((result) => {
                    window.location.href = 'book_list.php';
                });
              </script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error updating books: " . $e->getMessage() . "',
                    confirmButtonColor: '#d33'
                });
              </script>";
    }
}

// Function to delete image file from filesystem - update this function with better error handling and logging
function deleteImageFile($imagePath) {
    if (!empty($imagePath)) {
        $fullPath = "../" . $imagePath;
        if (file_exists($fullPath) && is_file($fullPath)) {
            if (unlink($fullPath)) {
                // Log success if needed
                return true;
            } else {
                // Handle deletion failure
                error_log("Failed to delete image file: " . $fullPath);
                return false;
            }
        } else {
            // File doesn't exist
            error_log("File doesn't exist or is not a file: " . $fullPath);
            return false;
        }
    }
    return false;
}

// Function to process image upload
function processImageUpload($fieldName, $bookId) {
    $targetDir = "../Images/book-image/";
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileExtension = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
    $newFileName = $fieldName . '_' . $bookId . '_' . time() . '.' . $fileExtension;
    $targetFile = $targetDir . $newFileName;
    
    // Check if image file is a actual image
    $check = getimagesize($_FILES[$fieldName]["tmp_name"]);
    if ($check === false) {
        return false;
    }
    
    // Check file size (limit to 5MB)
    if ($_FILES[$fieldName]["size"] > 5000000) {
        return false;
    }
    
    // Allow certain file formats
    $allowedExtensions = array("jpg", "jpeg", "png", "gif");
    if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
        return false;
    }
    
    if (move_uploaded_file($_FILES[$fieldName]["tmp_name"], $targetFile)) {
        return "Images/book-image/" . $newFileName; // Store relative path in database
    } else {
        return false;
    }
}

// Get book title from URL parameter and first book ID from the range
$title = isset($_GET['title']) ? $_GET['title'] : '';
$id_range = isset($_GET['id_range']) ? $_GET['id_range'] : '';

// Get accession range from URL parameter
$accession_range = isset($_GET['accession_range']) ? $_GET['accession_range'] : '';

// Initialize $books as empty array to prevent undefined variable error
$books = [];
$first_book = null;

// Parse and validate the accession range
if (!empty($accession_range)) {
    $accession_parts = explode(',', $accession_range);
    $accession_array = [];

    foreach ($accession_parts as $part) {
        $part = trim($part);
        if (strpos($part, '-') !== false) {
            list($start, $end) = explode('-', $part);
            $start = (int)trim($start);
            $end = (int)trim($end);
            if ($start <= $end) {
                $accession_array = array_merge($accession_array, range($start, $end));
            }
        } else {
            if (is_numeric(trim($part))) {
                $accession_array[] = (int)trim($part);
            }
        }
    }

    $accession_array = array_unique($accession_array);
    sort($accession_array);

    if (!empty($accession_array)) {
        $placeholders = implode(',', array_fill(0, count($accession_array), '?'));
        $book_query = "SELECT * FROM books WHERE accession IN ($placeholders)";
        $stmt = $conn->prepare($book_query);
        $types = str_repeat('i', count($accession_array));
        $stmt->bind_param($types, ...$accession_array);
        $stmt->execute();
        $books_result = $stmt->get_result();
        $books = $books_result->fetch_all(MYSQLI_ASSOC);

        if (!empty($books)) {
            $first_book = $books[0];
        } else {
            echo "<script>
                    alert('No books found for the specified accession range: " . htmlspecialchars($accession_range) . "');
                    window.location.href = 'book_list.php';
                  </script>";
            exit();
        }
    } else {
        echo "<script>
                alert('Invalid accession range: " . htmlspecialchars($accession_range) . "');
                window.location.href = 'book_list.php';
              </script>";
        exit();
    }
}

// Fetch contributors for the selected books
$bookIds = isset($books) ? array_column($books, 'id') : [];
$contributors = [];
$corporate_contributors = [];
if (!empty($bookIds)) {
    $placeholders = implode(',', array_fill(0, count($bookIds), '?'));
    $contributors_query = "SELECT book_id, writer_id, role FROM contributors WHERE book_id IN ($placeholders)";
    $stmt = $conn->prepare($contributors_query);
    $stmt->bind_param(str_repeat('i', count($bookIds)), ...$bookIds);
    $stmt->execute();
    $contributors_result = $stmt->get_result();
    while ($row = $contributors_result->fetch_assoc()) {
        $contributors[$row['book_id']][] = $row;
    }

    $corp_contributors_query = "SELECT book_id, corporate_id, role FROM corporate_contributors WHERE book_id IN ($placeholders)";
    $stmt = $conn->prepare($corp_contributors_query);
    $stmt->bind_param(str_repeat('i', count($bookIds)), ...$bookIds);
    $stmt->execute();
    $corp_contributors_result = $stmt->get_result();
    while ($row = $corp_contributors_result->fetch_assoc()) {
        $corporate_contributors[$row['book_id']][] = $row;
    }
}
?>

<!-- Main Content -->
<div id="content-wrapper" class="d-flex flex-column min-vh-100">
    <div id="content" class="flex-grow-1">
        <div class="container-fluid">
            <form id="bookForm" action="" method="POST" enctype="multipart/form-data" class="h-100"
                  onkeydown="return event.key != 'Enter';">
                <input type="hidden" name="existing_front_image" value="<?php echo htmlspecialchars($first_book['front_image'] ?? ''); ?>">
                <input type="hidden" name="existing_back_image" value="<?php echo htmlspecialchars($first_book['back_image'] ?? ''); ?>">
                <!-- Add hidden fields to track image removal -->
                <input type="hidden" name="remove_front_image" id="remove_front_image" value="0">
                <input type="hidden" name="remove_back_image" id="remove_back_image" value="0">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-2 text-gray-800">Update Books (<?php echo count($books); ?> copies)</h1>
                    <div>
                        <button type="submit" name="submit" class="btn btn-success me-2">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#instructionsModal">
                            <i class="fas fa-question-circle"></i> Instructions
                        </button>
                    </div>
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
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i> Main title of the book.
                            </small>
                        </div>
                        <div class="form-group">
                            <label>Preferred Title</label>
                            <input type="text" class="form-control" name="preferred_title" value="<?php echo htmlspecialchars($first_book['preferred_title'] ?? ''); ?>">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i> Alternative title, if applicable.
                            </small>
                        </div>
                        <div class="form-group">
                            <label>Parallel Title</label>
                            <input type="text" class="form-control" name="parallel_title" value="<?php echo htmlspecialchars($first_book['parallel_title'] ?? ''); ?>">
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i> Title in another language.
                            </small>
                        </div>

                        <!-- Contributors section - Updated layout -->
                        <div class="form-group mt-4">
                            <h5 class="mb-3">Contributors</h5>
                            <div class="row">
                                <!-- Individual Contributors Section -->
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0">Individual Contributors</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="input-group mb-3">
                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                <input type="text" class="form-control" id="individualContributorsSearch" 
                                                       placeholder="Search contributors...">
                                                <button class="btn btn-outline-secondary" type="button" id="clearIndividualSearch">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <!-- Add New Individual Contributor Button -->
                                                <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#addIndividualContributorModal">
                                                    <i class="fas fa-plus"></i> New
                                                </button>
                                            </div>

                                            <!-- Search Results -->
                                            <div id="individualSearchResults" class="search-results mb-3" style="max-height: 200px; overflow-y: auto; display: none;">
                                                <div class="list-group">
                                                    <!-- Search results will be dynamically populated here -->
                                                </div>
                                            </div>

                                            <h6 class="mb-2">Selected Contributors</h6>
                                            <div id="individualContributorsContainer">
                                                <?php
                                                $bookContributors = [];
                                                if (!empty($books) && isset($contributors[$books[0]['id']])) {
                                                    $bookContributors = $contributors[$books[0]['id']];
                                                }

                                                if (!empty($bookContributors)) {
                                                    foreach ($bookContributors as $index => $contributor) {
                                                        $writerInfo = null;
                                                        foreach ($writers as $writer) {
                                                            if ($writer['id'] == $contributor['writer_id']) {
                                                                $writerInfo = $writer;
                                                                break;
                                                            }
                                                        }
                                                        if ($writerInfo) {
                                                            // Get role badge class based on contributor role
                                                            $roleBadgeClass = 'bg-primary';
                                                            switch($contributor['role']) {
                                                                case 'Author': $roleBadgeClass = 'bg-primary'; break;
                                                                case 'Co-Author': $roleBadgeClass = 'bg-success'; break;
                                                                case 'Editor': $roleBadgeClass = 'bg-info'; break;
                                                                case 'Translator': $roleBadgeClass = 'bg-warning'; break;
                                                                case 'Illustrator': $roleBadgeClass = 'bg-secondary'; break;
                                                            }
                                                ?>
                                                <div class="contributor-row d-flex align-items-center mb-2 border rounded p-2">
                                                    <div class="flex-grow-1">
                                                        <span class="badge <?php echo $roleBadgeClass; ?> text-white me-2"><?php echo htmlspecialchars($contributor['role']); ?></span>
                                                        <?php echo htmlspecialchars($writerInfo['name']); ?>
                                                        <input type="hidden" name="contributor_ids[]" value="<?php echo $contributor['writer_id']; ?>">
                                                    </div>
                                                    <select class="form-control mx-2 role-select" name="contributor_roles[]" style="width: auto;"
                                                            onchange="updateRoleBadge(this)">
                                                        <option value="Author" <?php echo ($contributor['role'] == 'Author') ? 'selected' : ''; ?>>Author</option>
                                                        <option value="Co-Author" <?php echo ($contributor['role'] == 'Co-Author') ? 'selected' : ''; ?>>Co-Author</option>
                                                        <option value="Editor" <?php echo ($contributor['role'] == 'Editor') ? 'selected' : ''; ?>>Editor</option>
                                                        <option value="Translator" <?php echo ($contributor['role'] == 'Translator') ? 'selected' : ''; ?>>Translator</option>
                                                        <option value="Illustrator" <?php echo ($contributor['role'] == 'Illustrator') ? 'selected' : ''; ?>>Illustrator</option>
                                                    </select>
                                                    <button type="button" class="btn btn-danger btn-sm remove-contributor">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <?php
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <div class="form-text text-muted mt-2">
                                                <i class="fas fa-info-circle mr-1"></i> Search and click on contributors to add them.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Corporate Contributors Section -->
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header bg-secondary text-white">
                                            <h6 class="mb-0">Corporate Contributors</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="input-group mb-3">
                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                <input type="text" class="form-control" id="corporateContributorsSearch" 
                                                       placeholder="Search corporate bodies...">
                                                <button class="btn btn-outline-secondary" type="button" id="clearCorporateSearch">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <!-- Add New Corporate Contributor Button -->
                                                <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#addCorporateContributorModal">
                                                    <i class="fas fa-plus"></i> New
                                                </button>
                                            </div>

                                            <!-- Search Results -->
                                            <div id="corporateSearchResults" class="search-results mb-3" style="max-height: 200px; overflow-y: auto; display: none;">
                                                <div class="list-group">
                                                    <!-- Search results will be dynamically populated here -->
                                                </div>
                                            </div>

                                            <h6 class="mb-2">Selected Corporate Bodies</h6>
                                            <div id="corporateContributorsContainer">
                                                <?php
                                                $bookCorpContributors = [];
                                                if (!empty($books) && isset($corporate_contributors[$books[0]['id']])) {
                                                    $bookCorpContributors = $corporate_contributors[$books[0]['id']];
                                                }

                                                if (!empty($bookCorpContributors)) {
                                                    foreach ($bookCorpContributors as $index => $corp_contributor) {
                                                        $corpInfo = null;
                                                        foreach ($corporates as $corporate) {
                                                            if ($corporate['id'] == $corp_contributor['corporate_id']) {
                                                                $corpInfo = $corporate;
                                                                break;
                                                            }
                                                        }
                                                        if ($corpInfo) {
                                                            // Get role badge class based on corporate contributor role
                                                            $roleBadgeClass = 'bg-primary';
                                                            switch($corp_contributor['role']) {
                                                                case 'Corporate Author': $roleBadgeClass = 'bg-primary'; break;
                                                                case 'Corporate Contributor': $roleBadgeClass = 'bg-success'; break;
                                                                case 'Publisher': $roleBadgeClass = 'bg-info'; break;
                                                                case 'Distributor': $roleBadgeClass = 'bg-warning'; break;
                                                                case 'Sponsor': $roleBadgeClass = 'bg-secondary'; break;
                                                                case 'Funding Body': $roleBadgeClass = 'bg-dark'; break;
                                                                case 'Research Institution': $roleBadgeClass = 'bg-light'; break;
                                                            }
                                                ?>
                                                <div class="corporate-row d-flex align-items-center mb-2 border rounded p-2">
                                                    <div class="flex-grow-1">
                                                        <span class="badge <?php echo $roleBadgeClass; ?> <?php echo ($roleBadgeClass === 'bg-light') ? 'text-dark' : 'text-white'; ?> me-2"><?php echo htmlspecialchars($corp_contributor['role']); ?></span>
                                                        <?php echo htmlspecialchars($corpInfo['name']) . ' (' . htmlspecialchars($corpInfo['type']) . ')'; ?>
                                                        <input type="hidden" name="corporate_ids[]" value="<?php echo $corp_contributor['corporate_id']; ?>">
                                                    </div>
                                                    <select class="form-control mx-2 role-select" name="corporate_roles[]" style="width: auto;"
                                                            onchange="updateRoleBadge(this)">
                                                        <option value="Corporate Author" <?php echo ($corp_contributor['role'] == 'Corporate Author') ? 'selected' : ''; ?>>Corporate Author</option>
                                                        <option value="Corporate Contributor" <?php echo ($corp_contributor['role'] == 'Corporate Contributor') ? 'selected' : ''; ?>>Corporate Contributor</option>
                                                        <option value="Publisher" <?php echo ($corp_contributor['role'] == 'Publisher') ? 'selected' : ''; ?>>Publisher</option>
                                                        <option value="Distributor" <?php echo ($corp_contributor['role'] == 'Distributor') ? 'selected' : ''; ?>>Distributor</option>
                                                        <option value="Sponsor" <?php echo ($corp_contributor['role'] == 'Sponsor') ? 'selected' : ''; ?>>Sponsor</option>
                                                        <option value="Funding Body" <?php echo ($corp_contributor['role'] == 'Funding Body') ? 'selected' : ''; ?>>Funding Body</option>
                                                        <option value="Research Institution" <?php echo ($corp_contributor['role'] == 'Research Institution') ? 'selected' : ''; ?>>Research Institution</option>
                                                    </select>
                                                    <button type="button" class="btn btn-danger btn-sm remove-corporate">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <?php
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <div class="form-text text-muted mt-2">
                                                <i class="fas fa-info-circle mr-1"></i> Search and click on corporate bodies to add them.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subject Entry Tab -->
                    <div class="tab-pane fade" id="subject-entry" role="tabpanel">
                        <h4>Access Point</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Subject Category</label>
                                    <select class="form-control" name="subject_category">
                                        <option value="">None</option>
                                        <?php foreach ($subject_options as $option): ?>
                                            <option value="<?php echo $option; ?>" <?php if(isset($first_book['subject_category']) && $first_book['subject_category'] == $option) echo 'selected'; ?>><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Program Association</label>
                                    <select class="form-control" name="program">
                                        <option value="">None</option>
                                        <option value="General Education" <?php if(isset($first_book['program']) && $first_book['program'] == 'General Education') echo 'selected'; ?>>General Education</option>
                                        <option value="Computer Science" <?php if(isset($first_book['program']) && $first_book['program'] == 'Computer Science') echo 'selected'; ?>>Computer Science</option>
                                        <option value="Accountancy" <?php if(isset($first_book['program']) && $first_book['program'] == 'Accountancy') echo 'selected'; ?>>Accountancy</option>
                                        <option value="Entrepreneurship" <?php if(isset($first_book['program']) && $first_book['program'] == 'Entrepreneurship') echo 'selected'; ?>>Entrepreneurship</option>
                                        <option value="Accountancy Information System" <?php if(isset($first_book['program']) && $first_book['program'] == 'Accountancy Information System') echo 'selected'; ?>>Accountancy Information System</option>
                                        <option value="Tourism Management" <?php if(isset($first_book['program']) && $first_book['program'] == 'Tourism Management') echo 'selected'; ?>>Tourism Management</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Academic program associated with this material.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Subject Details</label>
                            <textarea class="form-control" name="subject_detail" rows="5"><?php echo htmlspecialchars($first_book['subject_detail'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i> Detailed subject information, enter multiple subjects separated by semicolons.
                            </small>
                        </div>
                    </div>

                    <!-- Abstracts Tab -->
                    <div class="tab-pane fade" id="abstracts" role="tabpanel">
                        <h4>Abstracts & Contents</h4>
                        <div class="form-group">
                            <label>Summary</label>
                            <textarea class="form-control" name="summary" rows="5"><?php echo htmlspecialchars($first_book['summary'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i> Brief description or summary of the book's content.
                            </small>
                        </div>
                        <div class="form-group">
                            <label>Content Notes</label>
                            <textarea class="form-control" name="contents" rows="5"><?php echo htmlspecialchars($first_book['contents'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i> Table of contents or chapter listings.
                            </small>
                        </div>
                    </div>

                    <!-- Description Tab -->
                    <div class="tab-pane fade" id="description" role="tabpanel">
                        <h4>Physical Description</h4>
                        <div class="row">
                            <!-- Front Cover Image -->
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="m-0">Front Cover Image</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="image-upload-container" id="frontCoverContainer">
                                            <div class="drop-zone" id="frontCoverDropZone">
                                                <span class="drop-zone__prompt">
                                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i><br>
                                                    Drop front cover image here or click to upload<br>
                                                    <small>Supports: JPG, JPEG, PNG, GIF (max 5MB)</small>
                                                </span>
                                                <input type="file" name="front_image" id="frontCoverInput" class="drop-zone__input" accept="image/*">
                                                <div class="drop-zone__thumb d-none" id="frontCoverThumb">
                                                    <div class="drop-zone__thumb-overlay">
                                                        <button type="button" class="btn btn-sm btn-danger remove-image" data-target="front">
                                                            <i class="fas fa-trash-alt"></i> Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if (!empty($first_book['front_image'])): ?>
                                                <div class="mt-3" id="current-front-image">
                                                    <p><strong>Current Image:</strong></p>
                                                    <div class="current-image-container">
                                                        <img src="../<?php echo htmlspecialchars($first_book['front_image']); ?>" 
                                                             alt="Current Front Cover" class="img-thumbnail" style="max-height: 150px;">
                                                        <div class="mt-2">
                                                            <button type="button" class="btn btn-danger btn-sm remove-current-image" data-target="front">
                                                                <i class="fas fa-trash-alt"></i> Remove Current Image
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Back Cover Image -->
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-secondary text-white">
                                        <h5 class="m-0">Back Cover Image</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="image-upload-container" id="backCoverContainer">
                                            <div class="drop-zone" id="backCoverDropZone">
                                                <span class="drop-zone__prompt">
                                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i><br>
                                                    Drop back cover image here or click to upload<br>
                                                    <small>Supports: JPG, JPEG, PNG, GIF (max 5MB)</small>
                                                </span>
                                                <input type="file" name="back_image" id="backCoverInput" class="drop-zone__input" accept="image/*">
                                                <div class="drop-zone__thumb d-none" id="backCoverThumb">
                                                    <div class="drop-zone__thumb-overlay">
                                                        <button type="button" class="btn btn-sm btn-danger remove-image" data-target="back">
                                                            <i class="fas fa-trash-alt"></i> Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if (!empty($first_book['back_image'])): ?>
                                                <div class="mt-3" id="current-back-image">
                                                    <p><strong>Current Image:</strong></p>
                                                    <div class="current-image-container">
                                                        <img src="../<?php echo htmlspecialchars($first_book['back_image']); ?>" 
                                                             alt="Current Back Cover" class="img-thumbnail" style="max-height: 150px;">
                                                        <div class="mt-2">
                                                            <button type="button" class="btn btn-danger btn-sm remove-current-image" data-target="back">
                                                                <i class="fas fa-trash-alt"></i> Remove Current Image
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Total Pages</label>
                                    <input type="text" class="form-control" name="total_pages" value="<?php echo htmlspecialchars($first_book['total_pages'] ?? ''); ?>" placeholder="e.g., xiii, 234 p. or 234 p. or 10, [5] p.">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Total number of pages, including Roman numerals for preliminary pages (e.g., xiii) followed by main content pages with "p." suffix.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Dimensions</label>
                                    <input type="text" class="form-control" name="dimension" value="<?php echo htmlspecialchars($first_book['dimension'] ?? ''); ?>" placeholder="e.g., 24 cm or 21 x 27 cm">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Physical size of the book in centimeters (cm). Specify height only (24 cm) or width x height (21 x 27 cm).
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Supplementary Contents</label>
                            <?php 
                            // Parse existing supplementary contents to identify selected options
                            $selectedSupplements = [];
                            if (!empty($first_book['supplementary_contents'])) {
                                $contentStr = strtolower($first_book['supplementary_contents']);
                                $supplements = [
                                    'Appendix', 'Bibliography', 'Glossary', 'Index', 
                                    'Illustrations', 'Maps', 'Tables'
                                ];
                                
                                foreach ($supplements as $supplement) {
                                    if (strpos($contentStr, strtolower($supplement)) !== false) {
                                        $selectedSupplements[] = $supplement;
                                    }
                                }
                            }
                            ?>
                            <select class="form-control" name="supplementary_contents[]" multiple size="7">
                                <option value="Appendix" <?php if(in_array('Appendix', $selectedSupplements)) echo 'selected'; ?>>Appendix</option>
                                <option value="Bibliography" <?php if(in_array('Bibliography', $selectedSupplements)) echo 'selected'; ?>>Bibliography</option>
                                <option value="Glossary" <?php if(in_array('Glossary', $selectedSupplements)) echo 'selected'; ?>>Glossary</option>
                                <option value="Index" <?php if(in_array('Index', $selectedSupplements)) echo 'selected'; ?>>Index</option>
                                <option value="Illustrations" <?php if(in_array('Illustrations', $selectedSupplements)) echo 'selected'; ?>>Illustrations</option>
                                <option value="Maps" <?php if(in_array('Maps', $selectedSupplements)) echo 'selected'; ?>>Maps</option>
                                <option value="Tables" <?php if(in_array('Tables', $selectedSupplements)) echo 'selected'; ?>>Tables</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i> Hold Ctrl/Cmd to select multiple items.
                            </small>
                        </div>
                    </div>

                    <!-- Local Information Tab -->
                    <div class="tab-pane fade" id="local-info" role="tabpanel">
                        <h4>Local Information</h4>
                        
                        <!-- Add Audit Information Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0">Audit Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Entered By</label>
                                            <?php
                                            $entered_by_name = "Unknown";
                                            if (!empty($first_book['entered_by'])) {
                                                $admin_query = "SELECT CONCAT(firstname, ' ', lastname) as name, employee_id FROM admins WHERE employee_id = ?";
                                                $stmt = $conn->prepare($admin_query);
                                                $stmt->bind_param("i", $first_book['entered_by']);
                                                $stmt->execute();
                                                $admin_result = $stmt->get_result();
                                                if ($admin_row = $admin_result->fetch_assoc()) {
                                                    $entered_by_name = $admin_row['name']; // Display only the name
                                                }
                                            }
                                            ?>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($entered_by_name); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Date Added</label>
                                            <input type="text" class="form-control" value="<?php echo ($first_book['date_added']) ? date('F d, Y', strtotime($first_book['date_added'])) : 'N/A'; ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Last Updated By</label>
                                            <?php
                                            $updated_by_name = "Not updated yet";
                                            if (!empty($first_book['updated_by'])) {
                                                $admin_query = "SELECT CONCAT(firstname, ' ', lastname) as name, employee_id FROM admins WHERE employee_id = ?";
                                                $stmt = $conn->prepare($admin_query);
                                                $stmt->bind_param("i", $first_book['updated_by']);
                                                $stmt->execute();
                                                $admin_result = $stmt->get_result();
                                                if ($admin_row = $admin_result->fetch_assoc()) {
                                                    $updated_by_name = $admin_row['name']; // Display only the name
                                                }
                                            }
                                            ?>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($updated_by_name); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Last Update</label>
                                            <input type="text" class="form-control" value="<?php echo ($first_book['last_update']) ? date('F d, Y', strtotime($first_book['last_update'])) : 'N/A'; ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>Individual Copy Information</h5>
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto; margin-bottom: 20px;">
                            <table class="table table-bordered table-striped individual-copy-table">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>Accession</th>
                                        <th>Copy Number</th>
                                        <th>Series</th>
                                        <th>Volume</th>
                                        <th>Part</th>
                                        <th>Edition</th>
                                        <th style="min-width: 250px; width: 250px;">Call Number</th>
                                        <th style="min-width: 150px; width: 150px;">Status</th>
                                        <th style="min-width: 180px; width: 180px;">Shelf Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $i => $book): ?>
                                    <tr>
                                        <td>
                                            <input type="text" class="form-control" name="accession[]" value="<?php echo htmlspecialchars($book['accession'] ?? ''); ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" name="copy_number[]" value="<?php echo htmlspecialchars($book['copy_number'] ?? ''); ?>" min="1">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="individual_series[]" value="<?php echo htmlspecialchars($book['series'] ?? ''); ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="individual_volume[]" value="<?php echo htmlspecialchars($book['volume'] ?? ''); ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="individual_part[]" value="<?php echo htmlspecialchars($book['part'] ?? ''); ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="individual_edition[]" value="<?php echo htmlspecialchars($book['edition'] ?? ''); ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="individual_call_number[]" value="<?php echo htmlspecialchars($book['call_number'] ?? ''); ?>">
                                        </td>
                                        <td>
                                            <select class="form-control" name="status[]">
                                                <option value="Available" <?php if($book['status'] == 'Available') echo 'selected'; ?>>Available</option>
                                                <option value="Borrowed" <?php if($book['status'] == 'Borrowed') echo 'selected'; ?>>Borrowed</option>
                                                <option value="On Reserve" <?php if($book['status'] == 'On Reserve') echo 'selected'; ?>>On Reserve</option>
                                                <option value="Lost" <?php if($book['status'] == 'Lost') echo 'selected'; ?>>Lost</option>
                                                <option value="Damaged" <?php if($book['status'] == 'Damaged') echo 'selected'; ?>>Damaged</option>
                                                <option value="Under Repair" <?php if($book['status'] == 'Under Repair') echo 'selected'; ?>>Under Repair</option>
                                                <option value="In Process" <?php if($book['status'] == 'In Process') echo 'selected'; ?>>In Process</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-control" name="shelf_location[]">
                                                <option value="TR" <?php if($book['shelf_location'] == 'TR') echo 'selected'; ?>>Teachers Reference</option>
                                                <option value="FIL" <?php if($book['shelf_location'] == 'FIL') echo 'selected'; ?>>Filipiniana</option>
                                                <option value="CIR" <?php if($book['shelf_location'] == 'CIR') echo 'selected'; ?>>Circulation</option>
                                                <option value="REF" <?php if($book['shelf_location'] == 'REF') echo 'selected'; ?>>Reference</option>
                                                <option value="SC" <?php if($book['shelf_location'] == 'SC') echo 'selected'; ?>>Special Collection</option>
                                                <option value="BIO" <?php if($book['shelf_location'] == 'BIO') echo 'selected'; ?>>Biography</option>
                                                <option value="RES" <?php if($book['shelf_location'] == 'RES') echo 'selected'; ?>>Reserve</option>
                                                <option value="FIC" <?php if($book['shelf_location'] == 'FIC') echo 'selected'; ?>>Fiction</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Publication Tab -->
                    <div class="tab-pane fade" id="publication" role="tabpanel">
                        <h4>Publication Information</h4>
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Publisher Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Publisher</label>
                                            <?php 
                                            // Get publisher_id from the first book's publications
                                            $publisher_id = null;
                                            $publisher_name = "";
                                            if (!empty($first_book['id'])) {
                                                $pub_query = "SELECT p.publisher_id, pub.publisher FROM publications p 
                                                              JOIN publishers pub ON p.publisher_id = pub.id 
                                                              WHERE p.book_id = ?";
                                                $stmt = $conn->prepare($pub_query);
                                                $stmt->bind_param("i", $first_book['id']);
                                                $stmt->execute();
                                                $pub_result = $stmt->get_result();
                                                if ($pub_row = $pub_result->fetch_assoc()) {
                                                    $publisher_id = $pub_row['publisher_id'];
                                                    $publisher_name = $pub_row['publisher'];
                                                }
                                            }
                                            ?>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                <input type="text" class="form-control" id="publisherSearch" 
                                                       placeholder="Search publishers..." 
                                                       value="<?php echo htmlspecialchars($publisher_name); ?>">
                                                <button class="btn btn-outline-secondary" type="button" id="clearPublisherSearch">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <!-- Add New Publisher Button -->
                                                <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#addPublisherModal">
                                                    <i class="fas fa-plus"></i> New
                                                </button>
                                            </div>
                                            <!-- Hidden input to store the selected publisher ID -->
                                            <input type="hidden" name="publisher_id" id="publisher_id_input" value="<?php echo $publisher_id; ?>">
                                            
                                            <!-- Search Results -->
                                            <div id="publisherSearchResults" class="search-results mt-2" style="max-height: 200px; overflow-y: auto; display: none;">
                                                <div class="list-group">
                                                    <!-- Search results will be dynamically populated here -->
                                                </div>
                                            </div>
                                            
                                            <!-- Selected Publisher Display -->
                                            <div id="selectedPublisherContainer" class="mt-2 <?php echo empty($publisher_id) ? 'd-none' : ''; ?>">
                                                <div class="selected-publisher border rounded p-2 bg-light">
                                                    <span id="selectedPublisherName"><?php echo htmlspecialchars($publisher_name); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Publication Year</label>
                                            <?php 
                                            // Get publish_date from the first book's publications
                                            $publish_date = null;
                                            if (!empty($first_book['id'])) {
                                                $pub_query = "SELECT publish_date FROM publications WHERE book_id = ?";
                                                $stmt = $conn->prepare($pub_query);
                                                $stmt->bind_param("i", $first_book['id']);
                                                $stmt->execute();
                                                $pub_result = $stmt->get_result();
                                                if ($pub_row = $pub_result->fetch_assoc()) {
                                                    $publish_date = $pub_row['publish_date'];
                                                }
                                            }
                                            ?>
                                            <input type="text" class="form-control" name="publish_date" value="<?php echo htmlspecialchars($publish_date ?? ''); ?>" placeholder="YYYY">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Content Type</label>
                                    <select class="form-control" name="content_type">
                                        <option value="Text" <?php echo ($first_book['content_type'] == 'Text' || empty($first_book['content_type'])) ? 'selected' : ''; ?>>Text</option>
                                        <option value="Image" <?php echo ($first_book['content_type'] == 'Image') ? 'selected' : ''; ?>>Image</option>
                                        <option value="Video" <?php echo ($first_book['content_type'] == 'Video') ? 'selected' : ''; ?>>Video</option>
                                        <option value="Audio" <?php echo ($first_book['content_type'] == 'Audio') ? 'selected' : ''; ?>>Audio</option>
                                        <option value="Multimedia" <?php echo ($first_book['content_type'] == 'Multimedia') ? 'selected' : ''; ?>>Multimedia</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Media Type</label>
                                    <select class="form-control" name="media_type">
                                        <option value="Print" <?php echo ($first_book['media_type'] == 'Print' || empty($first_book['media_type'])) ? 'selected' : ''; ?>>Print</option>
                                        <option value="Digital" <?php echo ($first_book['media_type'] == 'Digital') ? 'selected' : ''; ?>>Digital</option>
                                        <option value="Audio" <?php echo ($first_book['media_type'] == 'Audio') ? 'selected' : ''; ?>>Audio</option>
                                        <option value="Video" <?php echo ($first_book['media_type'] == 'Video') ? 'selected' : ''; ?>>Video</option>
                                        <option value="Microform" <?php echo ($first_book['media_type'] == 'Microform') ? 'selected' : ''; ?>>Microform</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Carrier Type</label>
                                    <select class="form-control" name="carrier_type">
                                        <option value="Book" <?php echo ($first_book['carrier_type'] == 'Book' || empty($first_book['carrier_type'])) ? 'selected' : ''; ?>>Book</option>
                                        <option value="CD" <?php echo ($first_book['carrier_type'] == 'CD') ? 'selected' : ''; ?>>CD</option>
                                        <option value="DVD" <?php echo ($first_book['carrier_type'] == 'DVD') ? 'selected' : ''; ?>>DVD</option>
                                        <option value="USB" <?php echo ($first_book['carrier_type'] == 'USB') ? 'selected' : ''; ?>>USB</option>
                                        <option value="Online Resource" <?php echo ($first_book['carrier_type'] == 'Online Resource') ? 'selected' : ''; ?>>Online Resource</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Language</label>
                                    <select class="form-control" name="language">
                                        <option value="English" <?php echo ($first_book['language'] == 'English' || empty($first_book['language'])) ? 'selected' : ''; ?>>English</option>
                                        <option value="Filipino" <?php echo ($first_book['language'] == 'Filipino') ? 'selected' : ''; ?>>Filipino</option>
                                        <option value="Spanish" <?php echo ($first_book['language'] == 'Spanish') ? 'selected' : ''; ?>>Spanish</option>
                                        <option value="French" <?php echo ($first_book['language'] == 'French') ? 'selected' : ''; ?>>French</option>
                                        <option value="Chinese" <?php echo ($first_book['language'] == 'Chinese') ? 'selected' : ''; ?>>Chinese</option>
                                        <option value="Japanese" <?php echo ($first_book['language'] == 'Japanese') ? 'selected' : ''; ?>>Japanese</option>
                                        <option value="Multiple" <?php echo ($first_book['language'] == 'Multiple') ? 'selected' : ''; ?>>Multiple Languages</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Primary language of the content.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>URL</label>
                                    <input type="text" class="form-control" name="URL" value="<?php echo htmlspecialchars($first_book['URL'] ?? ''); ?>" placeholder="https://...">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> Link to digital version if available.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ISBN</label>
                                    <input type="text" class="form-control" name="ISBN" value="<?php echo htmlspecialchars($first_book['ISBN'] ?? ''); ?>" placeholder="e.g., 9780123456789">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> International Standard Book Number (10 or 13 digits).
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Individual Contributor Modal -->
    <div class="modal fade" id="addIndividualContributorModal" tabindex="-1" aria-labelledby="addIndividualContributorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addIndividualContributorModalLabel">Add New Individual Contributor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addIndividualContributorForm">
                        <div class="mb-3">
                            <label for="individualFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="individualFirstName" name="firstname" required>
                        </div>
                        <div class="mb-3">
                            <label for="individualMiddleInit" class="form-label">Middle Initial</label>
                            <input type="text" class="form-control" id="individualMiddleInit" name="middle_init">
                            <small class="text-muted">Optional. Include period if applicable (e.g., "P.")</small>
                        </div>
                        <div class="mb-3">
                            <label for="individualLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="individualLastName" name="lastname" required>
                        </div>
                        <div id="individualContributorFormAlert"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveIndividualContributor">Save Contributor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Corporate Contributor Modal -->
    <div class="modal fade" id="addCorporateContributorModal" tabindex="-1" aria-labelledby="addCorporateContributorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="addCorporateContributorModalLabel">Add New Corporate Contributor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCorporateContributorForm">
                        <div class="mb-3">
                            <label for="corporateName" class="form-label">Corporate Name</label>
                            <input type="text" class="form-control" id="corporateName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="corporateType" class="form-label">Type</label>
                            <select class="form-control" id="corporateType" name="type" required>
                                <option value="">Select type...</option>
                                <option value="Government Institution">Government Institution</option>
                                <option value="University">University</option>
                                <option value="University Press">University Press</option>
                                <option value="Research Institute">Research Institute</option>
                                <option value="Non-profit Organization">Non-profit Organization</option>
                                <option value="Corporation">Corporation</option>
                                <option value="Professional Association">Professional Association</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="corporateLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="corporateLocation" name="location">
                            <small class="text-muted">Optional. City, Country format recommended.</small>
                        </div>
                        <div class="mb-3">
                            <label for="corporateDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="corporateDescription" name="description" rows="3"></textarea>
                            <small class="text-muted">Optional. Brief description of the organization.</small>
                        </div>
                        <div id="corporateContributorFormAlert"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCorporateContributor">Save Corporate Body</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Publisher Modal -->
    <div class="modal fade" id="addPublisherModal" tabindex="-1" aria-labelledby="addPublisherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addPublisherModalLabel">Add New Publisher</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addPublisherForm">
                        <div class="mb-3">
                            <label for="publisherName" class="form-label">Publisher Name</label>
                            <input type="text" class="form-control" id="publisherName" name="publisher" required>
                        </div>
                        <div class="mb-3">
                            <label for="publisherPlace" class="form-label">Place</label>
                            <input type="text" class="form-control" id="publisherPlace" name="place" required>
                            <small class="text-muted">City, Country format recommended (e.g., Manila, Philippines)</small>
                        </div>
                        <div id="publisherFormAlert"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePublisher">Save Publisher</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../admin/inc/footer.php'; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Define writer data array for search
    const writers = [
        <?php foreach ($writers as $writer): ?>
        {
            id: <?php echo $writer['id']; ?>,
            name: "<?php echo htmlspecialchars(addslashes($writer['name'])); ?>"
        },
        <?php endforeach; ?>
    ];

    // Define corporate data array for search
    const corporates = [
        <?php foreach ($corporates as $corporate): ?>
        {
            id: <?php echo $corporate['id']; ?>,
            name: "<?php echo htmlspecialchars(addslashes($corporate['name'])); ?>",
            type: "<?php echo htmlspecialchars(addslashes($corporate['type'])); ?>"
        },
        <?php endforeach; ?>
    ];

    // Define publishers data array for search
    const publishers = [
        <?php foreach ($publishers as $publisher): ?>
        {
            id: <?php echo $publisher['id']; ?>,
            name: "<?php echo htmlspecialchars(addslashes($publisher['publisher'])); ?>",
            place: "<?php echo htmlspecialchars(addslashes($publisher['place'])); ?>"
        },
        <?php endforeach; ?>
    ];

    // Publisher search functionality
    const publisherSearchInput = document.getElementById('publisherSearch');
    const publisherSearchResults = document.getElementById('publisherSearchResults');
    const publisherResultsList = publisherSearchResults.querySelector('.list-group');
    const selectedPublisherContainer = document.getElementById('selectedPublisherContainer');
    const selectedPublisherName = document.getElementById('selectedPublisherName');
    const publisherIdInput = document.getElementById('publisher_id_input');
    
    // Function to select a publisher
    function selectPublisher(id, name) {
        publisherIdInput.value = id;
        selectedPublisherName.textContent = name;
        publisherSearchInput.value = name;
        selectedPublisherContainer.classList.remove('d-none');
        publisherSearchResults.style.display = 'none';
    }
    
    // Clear publisher selection
    document.getElementById('clearPublisherSearch').addEventListener('click', function() {
        publisherSearchInput.value = '';
        publisherIdInput.value = '';
        selectedPublisherContainer.classList.add('d-none');
        publisherSearchResults.style.display = 'none';
    });
    
    // Publisher search input handler
    publisherSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        if (searchTerm.length < 1) {
            publisherSearchResults.style.display = 'none';
            return;
        }
        
        // Filter publishers based on search term
        const filteredPublishers = publishers.filter(publisher => 
            publisher.name.toLowerCase().includes(searchTerm) || 
            publisher.place.toLowerCase().includes(searchTerm)
        );
        
        // Clear previous results
        publisherResultsList.innerHTML = '';
        
        if (filteredPublishers.length === 0) {
            publisherResultsList.innerHTML = '<div class="list-group-item">No publishers found</div>';
        } else {
            // Add results to the list
            filteredPublishers.forEach(publisher => {
                const resultItem = document.createElement('a');
                resultItem.className = 'list-group-item list-group-item-action';
                resultItem.href = '#';
                resultItem.textContent = `${publisher.name} (${publisher.place})`;
                resultItem.dataset.id = publisher.id;
                resultItem.dataset.name = publisher.name;
                
                resultItem.addEventListener('click', function(e) {
                    e.preventDefault();
                    selectPublisher(this.dataset.id, this.dataset.name);
                });
                
                publisherResultsList.appendChild(resultItem);
            });
        }
        
        publisherSearchResults.style.display = 'block';
    });
    
    // Close publisher search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!publisherSearchInput.contains(e.target) && !publisherSearchResults.contains(e.target)) {
            publisherSearchResults.style.display = 'none';
        }
    });

    // Handle Publisher Form Submission
    document.getElementById('savePublisher').addEventListener('click', function() {
        const form = document.getElementById('addPublisherForm');
        const formData = new FormData(form);
        const alertBox = document.getElementById('publisherFormAlert');
        
        // Basic validation
        const name = formData.get('publisher').trim();
        const place = formData.get('place').trim();
        
        if (!name || !place) {
            alertBox.innerHTML = `<div class="alert alert-danger">Publisher name and place are required.</div>`;
            return;
        }
        
        // Disable the button and show loading state
        const saveButton = this;
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        
        // Send AJAX request to save the new publisher
        fetch('../ajax/add_publisher.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Add the new publisher to the publishers array
                publishers.push({
                    id: data.publisher_id,
                    name: data.publisher_name,
                    place: data.publisher_place
                });
                
                // Select the new publisher
                selectPublisher(data.publisher_id, data.publisher_name);
                
                // Reset the form and close the modal
                form.reset();
                $('#addPublisherModal').modal('hide');
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Publisher added successfully and selected for this book.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alertBox.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            alertBox.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        })
        .finally(() => {
            // Re-enable the button
            saveButton.disabled = false;
            saveButton.innerHTML = 'Save Publisher';
        });
    });
    
    // Clear form alerts when modal is closed
    $('#addPublisherModal').on('hidden.bs.modal', function() {
        document.getElementById('publisherFormAlert').innerHTML = '';
        document.getElementById('addPublisherForm').reset();
    });

    // Search functionality for individual contributors
    const individualSearchInput = document.getElementById('individualContributorsSearch');
    const individualSearchResults = document.getElementById('individualSearchResults');
    const individualResultsList = individualSearchResults.querySelector('.list-group');
    
    individualSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        if (searchTerm.length < 1) {
            individualSearchResults.style.display = 'none';
            return;
        }
        
        // Filter writers based on search term
        const filteredWriters = writers.filter(writer => 
            writer.name.toLowerCase().includes(searchTerm)
        );
        
        // Clear previous results
        individualResultsList.innerHTML = '';
        
        if (filteredWriters.length === 0) {
            individualResultsList.innerHTML = '<div class="list-group-item">No results found</div>';
        } else {
            // Add results to the list
            filteredWriters.forEach(writer => {
                const resultItem = document.createElement('a');
                resultItem.className = 'list-group-item list-group-item-action';
                resultItem.href = '#';
                resultItem.textContent = writer.name;
                resultItem.dataset.id = writer.id;
                
                resultItem.addEventListener('click', function(e) {
                    e.preventDefault();
                    addIndividualContributor(writer.id, writer.name);
                    individualSearchInput.value = '';
                    individualSearchResults.style.display = 'none';
                });
                
                individualResultsList.appendChild(resultItem);
            });
        }
        
        individualSearchResults.style.display = 'block';
    });
    
    // Clear individual search
    document.getElementById('clearIndividualSearch').addEventListener('click', function() {
        individualSearchInput.value = '';
        individualSearchResults.style.display = 'none';
    });

    // Add individual contributor function
    function addIndividualContributor(id, name) {
        const container = document.getElementById('individualContributorsContainer');
        
        // Check if contributor already exists
        const existingContributors = container.querySelectorAll('input[name="contributor_ids[]"]');
        for (let i = 0; i < existingContributors.length; i++) {
            if (existingContributors[i].value == id) {
                alert('This contributor is already added.');
                return;
            }
        }
        
        const newRow = document.createElement('div');
        newRow.className = 'contributor-row d-flex align-items-center mb-2 border rounded p-2';
        
        const nameDiv = document.createElement('div');
        nameDiv.className = 'flex-grow-1';
        
        // Create role badge
        const roleBadge = document.createElement('span');
        roleBadge.className = 'badge bg-primary text-white me-2';
        roleBadge.textContent = 'Author'; // Default role
        nameDiv.appendChild(roleBadge);
        
        // Add contributor name
        const nameText = document.createTextNode(name);
        nameDiv.appendChild(nameText);
        
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'contributor_ids[]';
        hiddenInput.value = id;
        nameDiv.appendChild(hiddenInput);
        
        const roleSelect = document.createElement('select');
        roleSelect.className = 'form-control mx-2 role-select';
        roleSelect.name = 'contributor_roles[]';
        roleSelect.style.width = 'auto';
        roleSelect.setAttribute('onchange', 'updateRoleBadge(this)');
        
        const roles = ["Author", "Co-Author", "Editor", "Translator", "Illustrator"];
        roles.forEach(role => {
            const roleOption = document.createElement('option');
            roleOption.value = role;
            roleOption.textContent = role;
            roleSelect.appendChild(roleOption);
        });
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-danger btn-sm remove-contributor';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        
        removeBtn.addEventListener('click', function() {
            container.removeChild(newRow);
        });
        
        newRow.appendChild(nameDiv);
        newRow.appendChild(roleSelect);
        newRow.appendChild(removeBtn);
        
        container.appendChild(newRow);
    }

    // Search functionality for corporate contributors
    const corporateSearchInput = document.getElementById('corporateContributorsSearch');
    const corporateSearchResults = document.getElementById('corporateSearchResults');
    const corporateResultsList = corporateSearchResults.querySelector('.list-group');
    
    corporateSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        if (searchTerm.length < 1) {
            corporateSearchResults.style.display = 'none';
            return;
        }
        
        // Filter corporates based on search term
        const filteredCorporates = corporates.filter(corp => 
            corp.name.toLowerCase().includes(searchTerm) || 
            corp.type.toLowerCase().includes(searchTerm)
        );
        
        // Clear previous results
        corporateResultsList.innerHTML = '';
        
        if (filteredCorporates.length === 0) {
            corporateResultsList.innerHTML = '<div class="list-group-item">No results found</div>';
        } else {
            // Add results to the list
            filteredCorporates.forEach(corp => {
                const resultItem = document.createElement('a');
                resultItem.className = 'list-group-item list-group-item-action';
                resultItem.href = '#';
                resultItem.textContent = `${corp.name} (${corp.type})`;
                resultItem.dataset.id = corp.id;
                
                resultItem.addEventListener('click', function(e) {
                    e.preventDefault();
                    addCorporateContributor(corp.id, corp.name, corp.type);
                    corporateSearchInput.value = '';
                    corporateSearchResults.style.display = 'none';
                });
                
                corporateResultsList.appendChild(resultItem);
            });
        }
        
        corporateSearchResults.style.display = 'block';
    });
    
    // Clear corporate search
    document.getElementById('clearCorporateSearch').addEventListener('click', function() {
        corporateSearchInput.value = '';
        corporateSearchResults.style.display = 'none';
    });

    // Add corporate contributor function
    function addCorporateContributor(id, name, type) {
        const container = document.getElementById('corporateContributorsContainer');
        
        // Check if corporate contributor already exists
        const existingContributors = container.querySelectorAll('input[name="corporate_ids[]"]');
        for (let i = 0; i < existingContributors.length; i++) {
            if (existingContributors[i].value == id) {
                alert('This corporate body is already added.');
                return;
            }
        }
        
        const newRow = document.createElement('div');
        newRow.className = 'corporate-row d-flex align-items-center mb-2 border rounded p-2';
        
        const nameDiv = document.createElement('div');
        nameDiv.className = 'flex-grow-1';
        
        // Create role badge
        const roleBadge = document.createElement('span');
        roleBadge.className = 'badge bg-primary text-white me-2';
        roleBadge.textContent = 'Corporate Author'; // Default role
        nameDiv.appendChild(roleBadge);
        
        // Add corporate name and type
        const nameText = document.createTextNode(`${name} (${type})`);
        nameDiv.appendChild(nameText);
        
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'corporate_ids[]';
        hiddenInput.value = id;
        nameDiv.appendChild(hiddenInput);
        
        const roleSelect = document.createElement('select');
        roleSelect.className = 'form-control mx-2 role-select';
        roleSelect.name = 'corporate_roles[]';
        roleSelect.style.width = 'auto';
        roleSelect.setAttribute('onchange', 'updateRoleBadge(this)');
        
        const roles = ["Corporate Author", "Corporate Contributor", "Publisher", "Distributor", "Sponsor", "Funding Body", "Research Institution"];
        roles.forEach(role => {
            const roleOption = document.createElement('option');
            roleOption.value = role;
            roleOption.textContent = role;
            roleSelect.appendChild(roleOption);
        });
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-danger btn-sm remove-corporate';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        
        removeBtn.addEventListener('click', function() {
            container.removeChild(newRow);
        });
        
        newRow.appendChild(nameDiv);
        newRow.appendChild(roleSelect);
        newRow.appendChild(removeBtn);
        
        container.appendChild(newRow);
    }

    // Handle Individual Contributor Form Submission
    document.getElementById('saveIndividualContributor').addEventListener('click', function() {
        const form = document.getElementById('addIndividualContributorForm');
        const formData = new FormData(form);
        const alertBox = document.getElementById('individualContributorFormAlert');
        
        // Basic validation
        const firstname = formData.get('firstname').trim();
        const lastname = formData.get('lastname').trim();
        
        if (!firstname || !lastname) {
            alertBox.innerHTML = `<div class="alert alert-danger">First name and last name are required.</div>`;
            return;
        }
        
        // Disable the button and show loading state
        const saveButton = this;
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        
        // Send AJAX request to save the new contributor
        fetch('../ajax/add_writer.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Add the new writer to the writers array
                writers.push({
                    id: data.writer_id,
                    name: data.writer_name
                });
                
                // Add the new contributor to the selected contributors
                addIndividualContributor(data.writer_id, data.writer_name);
                
                // Reset the form and close the modal - FIX: Use jQuery to close the modal
                form.reset();
                $('#addIndividualContributorModal').modal('hide');
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Contributor added successfully and selected for this book.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alertBox.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            alertBox.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        })
        .finally(() => {
            // Re-enable the button
            saveButton.disabled = false;
            saveButton.innerHTML = 'Save Contributor';
        });
    });
    
    // Handle Corporate Contributor Form Submission
    document.getElementById('saveCorporateContributor').addEventListener('click', function() {
        const form = document.getElementById('addCorporateContributorForm');
        const formData = new FormData(form);
        const alertBox = document.getElementById('corporateContributorFormAlert');
        
        // Basic validation
        const name = formData.get('name').trim();
        const type = formData.get('type');
        
        if (!name || !type) {
            alertBox.innerHTML = `<div class="alert alert-danger">Name and type are required.</div>`;
            return;
        }
        
        // Disable the button and show loading state
        const saveButton = this;
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        
        // Send AJAX request to save the new corporate contributor
        fetch('../ajax/add_corporate.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Add the new corporate to the corporates array
                corporates.push({
                    id: data.corporate_id,
                    name: data.name,
                    type: data.type
                });
                
                // Add the new corporate contributor to the selected contributors
                addCorporateContributor(data.corporate_id, data.name, data.type);
                
                // Reset the form and close the modal - FIX: Use jQuery to close the modal
                form.reset();
                $('#addCorporateContributorModal').modal('hide');
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Corporate body added successfully and selected for this book.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alertBox.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            alertBox.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        })
        .finally(() => {
            // Re-enable the button
            saveButton.disabled = false;
            saveButton.innerHTML = 'Save Corporate Body';
        });
    });
    
    // Clear form alerts when modal is closed - Fix: Use jQuery events for Bootstrap 4 modals
    $('#addIndividualContributorModal').on('hidden.bs.modal', function() {
        document.getElementById('individualContributorFormAlert').innerHTML = '';
        document.getElementById('addIndividualContributorForm').reset();
    });
    
    $('#addCorporateContributorModal').on('hidden.bs.modal', function() {
        document.getElementById('corporateContributorFormAlert').innerHTML = '';
        document.getElementById('addCorporateContributorForm').reset();
    });

    // Setup event listeners for existing remove buttons
    document.querySelectorAll(".remove-contributor").forEach(button => {
        button.addEventListener("click", function() {
            const row = this.closest('.contributor-row');
            row.parentElement.removeChild(row);
        });
    });

    document.querySelectorAll(".remove-corporate").forEach(button => {
        button.addEventListener("click", function() {
            const row = this.closest('.corporate-row');
            row.parentElement.removeChild(row);
        });
    });

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!individualSearchInput.contains(e.target) && !individualSearchResults.contains(e.target)) {
            individualSearchResults.style.display = 'none';
        }
        
        if (!corporateSearchInput.contains(e.target) && !corporateSearchResults.contains(e.target)) {
            corporateSearchResults.style.display = 'none';
        }
    });

    // Drag and drop image upload functionality
    document.querySelectorAll(".drop-zone__input").forEach(inputElement => {
        const dropZoneElement = inputElement.closest(".drop-zone");
        const targetId = inputElement.id === "frontCoverInput" ? "frontCoverThumb" : "backCoverThumb";
        const thumbElement = document.getElementById(targetId);

        // Click to select file
        dropZoneElement.addEventListener("click", e => {
            // Don't trigger if clicking on the remove button
            if (e.target.closest('.remove-image')) {
                return;
            }
            inputElement.click();
        });

        // Change selected file
        inputElement.addEventListener("change", e => {
            if (inputElement.files.length) {
                updateThumbnail(dropZoneElement, thumbElement, inputElement.files[0]);
            }
        });

        // Drag over event
        dropZoneElement.addEventListener("dragover", e => {
            e.preventDefault();
            dropZoneElement.classList.add("drop-zone--over");
        });

        // Drag leave event
        ["dragleave", "dragend"].forEach(type => {
            dropZoneElement.addEventListener(type, e => {
                dropZoneElement.classList.remove("drop-zone--over");
            });
        });

        // Drop event
        dropZoneElement.addEventListener("drop", e => {
            e.preventDefault();
            
            if (e.dataTransfer.files.length) {
                inputElement.files = e.dataTransfer.files;
                updateThumbnail(dropZoneElement, thumbElement, e.dataTransfer.files[0]);
            }
            
            dropZoneElement.classList.remove("drop-zone--over");
        });
    });

    // Function to update thumbnail
    function updateThumbnail(dropZoneElement, thumbElement, file) {
        // Show thumbnail for image files
        if (file.type.startsWith("image/")) {
            const reader = new FileReader();
            
            reader.readAsDataURL(file);
            reader.onload = () => {
                thumbElement.style.backgroundImage = `url('${reader.result}')`;
                thumbElement.setAttribute("data-label", file.name);
                thumbElement.classList.remove("d-none");
                
                // Hide the prompt text when showing thumbnail
                const promptElement = dropZoneElement.querySelector(".drop-zone__prompt");
                if (promptElement) {
                    promptElement.style.display = "none";
                }
            };
        } else {
            // Not an image file - show error or default thumbnail
            thumbElement.style.backgroundImage = null;
            thumbElement.setAttribute("data-label", "Not a valid image");
        }
    }

    // Remove selected image
    document.querySelectorAll(".remove-image").forEach(button => {
        button.addEventListener("click", e => {
            const target = e.currentTarget.getAttribute("data-target");
            const inputId = target === "front" ? "frontCoverInput" : "backCoverInput";
            const thumbId = target === "front" ? "frontCoverThumb" : "backCoverThumb";
            const dropZoneId = target === "front" ? "frontCoverDropZone" : "backCoverDropZone";
            
            // Clear the file input
            const input = document.getElementById(inputId);
            input.value = "";
            
            // Hide the thumbnail
            const thumb = document.getElementById(thumbId);
            thumb.classList.add("d-none");
            thumb.style.backgroundImage = "";
            
            // Show the prompt text again
            const dropZone = document.getElementById(dropZoneId);
            const prompt = dropZone.querySelector(".drop-zone__prompt");
            if (prompt) {
                prompt.style.display = "block";
            }
        });
    });

    // Remove current image functionality
    document.querySelectorAll(".remove-current-image").forEach(button => {
        button.addEventListener("click", e => {
            const target = e.currentTarget.getAttribute("data-target");
            
            // Show confirmation dialog
            Swal.fire({
                title: 'Remove image?',
                text: "This will remove the current image from all copies of this book.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Set the hidden field value to indicate removal
                    if (target === "front") {
                        document.getElementById("remove_front_image").value = "1";
                        document.getElementById("current-front-image").style.display = "none";
                    } else {
                        document.getElementById("remove_back_image").value = "1";
                        document.getElementById("current-back-image").style.display = "none";
                    }
                    
                    // Show a success message
                    Swal.fire(
                        'Marked for removal',
                        'The image will be removed when you save changes.',
                        'success'
                    );
                }
            });
        });
    });

    // Style for search results
    const style = document.createElement('style');
    style.textContent = `
        .search-results {
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }
        .list-group-item-action:hover {
            background-color: #f8f9fa;
        }
        .contributor-row, .corporate-row {
            transition: background-color 0.2s;
        }
        .contributor-row:hover, .corporate-row:hover {
            background-color: #f8f9fa;
        }
    `;
    document.head.appendChild(style);
});

// Function to update the role badge when the role dropdown changes
function updateRoleBadge(selectElement) {
    const role = selectElement.value;
    const row = selectElement.closest('.contributor-row, .corporate-row');
    const badge = row.querySelector('.badge');
    
    // Remove existing color classes
    badge.classList.remove(
        'bg-primary', 'bg-success', 'bg-info', 
        'bg-warning', 'bg-secondary', 'bg-dark', 
        'bg-light', 'text-dark', 'text-white'
    );
    
    // Assign new color class based on role
    badge.textContent = role;
    
    // Set appropriate color based on role
    if (role === 'Author' || role === 'Corporate Author') {
        badge.classList.add('bg-primary', 'text-white');
    } else if (role === 'Co-Author' || role === 'Corporate Contributor') {
        badge.classList.add('bg-success', 'text-white');
    } else if (role === 'Editor' || role === 'Publisher') {
        badge.classList.add('bg-info', 'text-white');
    } else if (role === 'Translator' || role === 'Distributor') {
        badge.classList.add('bg-warning', 'text-white');
    } else if (role === 'Illustrator' || role === 'Sponsor') {
        badge.classList.add('bg-secondary', 'text-white');
    } else if (role === 'Funding Body') {
        badge.classList.add('bg-dark', 'text-white');
    } else if (role === 'Research Institution') {
        badge.classList.add('bg-light', 'text-dark');
    }
}
</script>

<style>
/* Drag and drop image upload styles */
.drop-zone {
    max-width: 100%;
    height: 200px;
    padding: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    cursor: pointer;
    color: #666;
    border: 2px dashed #ccc;
    border-radius: 10px;
    position: relative;
    transition: all 0.3s ease;
}

.drop-zone:hover {
    border-color: #4e73df;
    background-color: #f8f9fc;
}

.drop-zone.drop-zone--over {
    border-color: #4e73df;
    background-color: #eef1ff;
}

.drop-zone__input {
    display: none;
}

.drop-zone__thumb {
    width: 100%;
    height: 100%;
    border-radius: 10px;
    overflow: hidden;
    background-color: #f8f9fc;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    position: relative;
}

.drop-zone__thumb::after {
    content: attr(data-label);
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 5px 0;
    color: #fff;
    background-color: rgba(0, 0, 0, 0.5);
    font-size: 14px;
    text-align: center;
}

.drop-zone__thumb-overlay {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 5px;
}

.current-image-container {
    margin-top: 10px;
    background-color: #f8f9fc;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #e3e6f0;
    text-align: center;
}
</style>
