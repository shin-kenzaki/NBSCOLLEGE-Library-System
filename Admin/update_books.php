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
        $supplementary_contents = '';
        if (isset($_POST['supplementary_content']) && is_array($_POST['supplementary_content']) && count($_POST['supplementary_content']) > 0) {
            $items = array_map('trim', $_POST['supplementary_content']);
            $count = count($items);
            if ($count === 1) {
                $supplementary_contents = "includes " . $items[0];
            } elseif ($count === 2) {
                $supplementary_contents = "includes " . $items[0] . " and " . $items[1];
            } else {
                $last_item = array_pop($items);
                $supplementary_contents = "includes " . implode(', ', $items) . ", and " . $last_item;
            }
        } else {
            $supplementary_contents = '';
        }

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
        $current_admin_id = $_SESSION['admin_employee_id']; 
        $update_date = date('Y-m-d');

        // Properly handle status with default value
        $status = isset($_POST['status']) ? (is_array($_POST['status']) ? $_POST['status'][0] : $_POST['status']) : 'Available';

        // Get program value - safely handle string or array
        $program = '';
        if (isset($_POST['program']) && is_array($_POST['program'])) {
            $program = mysqli_real_escape_string($conn, $_POST['program'][0]);
        } elseif (isset($_POST['program']) && is_string($_POST['program'])) {
            $program = mysqli_real_escape_string($conn, $_POST['program']);
        }

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
            $series_value = isset($series[0]) ? mysqli_real_escape_string($conn, $series[0]) : ''; // Use the same series value for all copies
            $volume_value = isset($volumes[$index]) ? mysqli_real_escape_string($conn, $volumes[$index]) : '';
            $part_value = isset($_POST['part'][$index]) ? mysqli_real_escape_string($conn, $_POST['part'][$index]) : ''; // Add this line to get part value
            $edition_value = isset($editions[$index]) ? mysqli_real_escape_string($conn, $editions[$index]) : '';

            // Fix: Properly capture copy number value
            $copy_number = isset($_POST['copy_number'][$index]) ? intval($_POST['copy_number'][$index]) : 1;

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
            $supplementary_content_value = mysqli_real_escape_string($conn, $supplementary_contents);

            $status_value = isset($statuses[$index]) ? mysqli_real_escape_string($conn, $statuses[$index]) : 'Available';

            $update_query = "UPDATE books SET
                title = ?,
                preferred_title = ?,
                parallel_title = ?,
                subject_category = ?,
                subject_detail = ?,
                program = ?, -- Added program field
                summary = ?,
                contents = ?,
                front_image = ?,
                back_image = ?,
                dimension = ?,
                series = ?, -- Update series for all copies
                volume = ?,
                part = ?,
                edition = ?,
                copy_number = ?,
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
                status = ?, -- Ensure status is updated
                updated_by = ?,
                last_update = ?,
                accession = ?
                WHERE id = ?";

            $stmt = $conn->prepare($update_query);

            // Bind parameters with copy-specific values including preserved entered_by/date_added
            $stmt->bind_param("ssssssssssssssssssssssssssssssi",
                $title,
                $preferred_title,
                $parallel_title,
                $subject_category,
                $subject_detail,
                $program, // Added program parameter
                $abstract,
                $notes,
                $front_image,
                $back_image,
                $dimension,
                $series_value, // Use the same series value for all copies
                $volume_value,     // Use individual volume value for this copy
                $part_value,       // Add part value in binding
                $edition_value,    // Use individual edition value for this copy
                $copy_number,      // Include copy_number in binding
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
                $status_value, // Bind the status value
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


        // Define the folder for storing images
        $imageFolder = '../Images/book-image/';

        // Ensure the folder exists
        if (!is_dir($imageFolder)) {
            mkdir($imageFolder, 0777, true);
        }

        // Handle image uploads
        $frontImagePath = '';
        $backImagePath = '';

        // Handle image uploads
$frontImagePath = '';
$backImagePath = '';

// Process front image
if (isset($_FILES['front_image']) && $_FILES['front_image']['error'] === UPLOAD_ERR_OK) {
    $frontImageTmpName = $_FILES['front_image']['tmp_name'];
    $frontImageName = uniqid('front_', true) . '_' . basename($_FILES['front_image']['name']);
    $frontImagePath = $imageFolder . $frontImageName;

    // Move the uploaded file to the target folder
    if (!move_uploaded_file($frontImageTmpName, $frontImagePath)) {
        throw new Exception("Failed to upload front image.");
    }

    // Convert to relative path for database storage
    $frontImagePath = 'Images/book-image/' . $frontImageName;
} else {
    // Use the existing front image if no new image is uploaded
    $frontImagePath = $_POST['existing_front_image'] ?? '';
}

// Process back image
if (isset($_FILES['back_image']) && $_FILES['back_image']['error'] === UPLOAD_ERR_OK) {
    $backImageTmpName = $_FILES['back_image']['tmp_name'];
    $backImageName = uniqid('back_', true) . '_' . basename($_FILES['back_image']['name']);
    $backImagePath = $imageFolder . $backImageName;

    // Move the uploaded file to the target folder
    if (!move_uploaded_file($backImageTmpName, $backImagePath)) {
        throw new Exception("Failed to upload back image.");
    }

    // Convert to relative path for database storage
    $backImagePath = 'Images/book-image/' . $backImageName;
} else {
    // Use the existing back image if no new image is uploaded
    $backImagePath = $_POST['existing_back_image'] ?? '';
}

// Update the database with the image paths
$update_query = "UPDATE books SET
    front_image = ?,
    back_image = ?
    WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ssi", $frontImagePath, $backImagePath, $bookId);

if (!$stmt->execute()) {
    throw new Exception("Error updating book images: " . $stmt->error);
}

        // Update each book copy
        foreach ($_POST['book_ids'] as $index => $bookId) {
            $shelf_location = $_POST['shelf_location'][$index] ?? '';
            $call_number = $_POST['call_numbers'][$index] ?? '';
            $accession = $_POST['accession'][$index] ?? '';

            $update_query = "UPDATE books SET
                front_image = ?,
                back_image = ?,
                shelf_location = ?,
                call_number = ?,
                accession = ?
                WHERE id = ?";

            $stmt = $conn->prepare($update_query);
            $stmt->bind_param(
                "sssssi",
                $frontImagePath,
                $backImagePath,
                $shelf_location,
                $call_number,
                $accession,
                $bookId
            );

            if (!$stmt->execute()) {
                throw new Exception("Error updating book copy (ID: $bookId): " . $stmt->error);
            }

            $stmt->close();
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

// Handle the "Add Copies" functionality in the PHP code
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['num_copies']) && isset($_POST['modal_action']) && $_POST['modal_action'] === 'add_copies') {
    $numCopiesToAdd = intval($_POST['num_copies']);
    $firstBookId = intval($_POST['book_id']);

    // Fetch the first book's details
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $firstBookId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<script>alert('Book not found.'); window.location.href = 'update_books.php';</script>";
        exit();
    }

    $firstBook = $result->fetch_assoc();

    // Get the current max copy number and accession for this title
    $stmt = $conn->prepare("SELECT MAX(copy_number) as max_copy, MAX(accession) as max_accession FROM books WHERE title = ?");
    $stmt->bind_param("s", $firstBook['title']);
    $stmt->execute();
    $maxInfo = $stmt->get_result()->fetch_assoc();
    $currentCopy = $maxInfo['max_copy'] ?: 0;
    $currentAccession = $maxInfo['max_accession'] ?: 0;

    $successCount = 0;

    for ($i = 1; $i <= $numCopiesToAdd; $i++) {
        $newCopyNumber = $currentCopy + $i;
        $newAccession = $currentAccession + $i;

        $query = "INSERT INTO books (
            accession, title, preferred_title, parallel_title, subject_category,
            subject_detail, summary, contents, front_image, back_image,
            dimension, series, volume, edition, copy_number, total_pages,
            supplementary_contents, ISBN, content_type, media_type, carrier_type,
            call_number, URL, language, shelf_location, entered_by, date_added,
            status, last_update
        ) SELECT
            ?, title, preferred_title, parallel_title, subject_category,
            subject_detail, summary, contents, front_image, back_image,
            dimension, series, volume, edition, ?,
            total_pages, supplementary_contents, ISBN, content_type, media_type, carrier_type,
            call_number, URL, language, shelf_location, entered_by, ?, 'Available', ?
        FROM books WHERE id = ?";

        $currentDate = date('Y-m-d');
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iissi", $newAccession, $newCopyNumber, $currentDate, $currentDate, $firstBookId);

        if ($stmt->execute()) {
            $successCount++;
        }
    }

    echo "<script>alert('Successfully added $successCount copies with status \"Available\".'); window.location.href = 'update_books.php';</script>";
    exit();
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
    echo "<script>console.log('Parsing accession range: " . htmlspecialchars($accession_range) . "');</script>";

    // Split the accession range into individual accessions or ranges
    $accession_parts = explode(',', $accession_range);
    $accession_array = [];

    foreach ($accession_parts as $part) {
        $part = trim($part); // Trim whitespace
        echo "<script>console.log('Processing part: " . htmlspecialchars($part) . "');</script>";

        if (strpos($part, '-') !== false) {
            // Handle range (e.g., "1-3")
            list($start, $end) = explode('-', $part);
            $start = (int)trim($start);
            $end = (int)trim($end);
            echo "<script>console.log('Range detected: " . $start . " to " . $end . "');</script>";

            // Ensure start is less than end
            if ($start <= $end) {
                $accession_array = array_merge($accession_array, range($start, $end));
            }
        } else {
            // Handle single accession
            if (is_numeric(trim($part))) {
                $accession_array[] = (int)trim($part);
                echo "<script>console.log('Single accession added: " . (int)trim($part) . "');</script>";
            }
        }
    }

    // Remove duplicates and sort the array
    $accession_array = array_unique($accession_array);
    sort($accession_array);

    echo "<script>console.log('Final accession array: [" . implode(", ", $accession_array) . "]');</script>";

    // Only proceed if we have accession numbers to process
    if (!empty($accession_array)) {
        // Fetch books with the specified accessions
        $placeholders = implode(',', array_fill(0, count($accession_array), '?'));
        $book_query = "SELECT * FROM books WHERE accession IN ($placeholders)";
        $stmt = $conn->prepare($book_query);

        // Dynamically create the type string for bind_param
        $types = str_repeat('i', count($accession_array));
        $stmt->bind_param($types, ...$accession_array);

        $stmt->execute();
        $books_result = $stmt->get_result();
        $books = $books_result->fetch_all(MYSQLI_ASSOC);

        echo "<script>console.log('Books found: " . count($books) . "');</script>";

        // Fetch the first book for shared data
        if (!empty($books)) {
            $first_book = $books[0];
        } else {
            // Handle case where no books match the accession range
            echo "<script>
                    alert('No books found for the specified accession range: " . htmlspecialchars($accession_range) . "');
                    window.location.href = 'book_list.php';
                  </script>";
            exit();
        }
    } else {
        // Handle invalid accession range
        echo "<script>
                alert('Invalid accession range: " . htmlspecialchars($accession_range) . "');
                window.location.href = 'book_list.php';
              </script>";
        exit();
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

// Fetch contributors for the selected books
$bookIds = isset($books) ? array_column($books, 'id') : [];
$contributors = [];
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
}

// Fetch publication details for the first book
$publication = [];
if ($first_book) {
    $publication_query = "SELECT p.publish_date, pub.publisher
                          FROM publications p
                          LEFT JOIN publishers pub ON p.publisher_id = pub.id
                          WHERE p.book_id = ?";
    $stmt = $conn->prepare($publication_query);
    $stmt->bind_param("i", $first_book['id']);
    $stmt->execute();
    $publication_result = $stmt->get_result();
    $publication = $publication_result->fetch_assoc();
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
                                            foreach ($contributors as $bookContributors) {
                                                foreach ($bookContributors as $contributor) {
                                                    if ($contributor['writer_id'] == $writer['id'] && $contributor['role'] == 'Author') {
                                                        $isAuthor = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        ?>
                                            <option value="<?php echo $writer['id']; ?>" <?php echo $isAuthor ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($writer['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple items</small>
                                    <div class="mt-2 border rounded bg-light" id="authorPreview"></div>
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
                                            foreach ($contributors as $bookContributors) {
                                                foreach ($bookContributors as $contributor) {
                                                    if ($contributor['writer_id'] == $writer['id'] && $contributor['role'] == 'Co-Author') {
                                                        $isCoAuthor = true;
                                                        break;
                                                    }
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
                                            foreach ($contributors as $bookContributors) {
                                                foreach ($bookContributors as $contributor) {
                                                    if ($contributor['writer_id'] == $writer['id'] && $contributor['role'] == 'Editor') {
                                                        $isEditor = true;
                                                        break;
                                                    }
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
                                    <div class="col-md-4">
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
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Program</label>
                                            <select class="form-control" name="program">
                                                <option value="">Select Program</option>
                                                <option value="Accountancy" <?php echo ($first_book['program'] == 'Accountancy') ? 'selected' : ''; ?>>Accountancy</option>
                                                <option value="Computer Science" <?php echo ($first_book['program'] == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                                                <option value="Entrepreneurship" <?php echo ($first_book['program'] == 'Entrepreneurship') ? 'selected' : ''; ?>>Entrepreneurship</option>
                                                <option value="Tourism Management" <?php echo ($first_book['program'] == 'Tourism Management') ? 'selected' : ''; ?>>Tourism Management</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
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
                                placeholder="Enter a summary or abstract of the book"><?php echo htmlspecialchars($first_book['summary'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Contents</label>
                            <textarea class="form-control" name="notes" rows="4"
                                placeholder="Enter the table of contents or chapter list"><?php echo htmlspecialchars($first_book['contents'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Description Tab -->
                    <div class="tab-pane fade" id="description" role="tabpanel">
                        <h4>Description</h4>

                        <!-- Enhanced Front/Back Image Selection -->
                        <div class="row mb-4">
                        <!-- Front Image -->
                        <div class="col-md-6">
                            <label class="mb-2">Front Image</label>
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="image-preview mb-3 d-flex justify-content-center align-items-center flex-column">
                                        <?php if (!empty($first_book['front_image']) && file_exists('../' . $first_book['front_image'])): ?>
                                            <img src="<?php echo htmlspecialchars('../' . $first_book['front_image']); ?>"
                                                alt="Book Front" id="frontImagePreview"
                                                style="max-height: 200px; max-width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                                        <?php else: ?>
                                            <div class="text-center py-4 bg-light rounded" id="frontImagePreviewPlaceholder">
                                                <i class="fas fa-book fa-3x mb-2 text-secondary"></i>
                                                <p class="text-muted">No front image available</p>
                                            </div>
                                            <img src="" alt="Book Front" id="frontImagePreview"
                                                style="max-height: 200px; max-width: 100%; display: none; border: 1px solid #ddd; border-radius: 4px;">
                                        <?php endif; ?>
                                    </div>
                                    <div id="frontAspectPreviews" class="d-flex justify-content-center gap-2 mb-2"></div>
                                    <div class="small text-muted" id="frontImageDimensions"></div>
                                    <div class="d-flex justify-content-center">
                                        <button type="button" class="btn btn-outline-primary me-2" onclick="document.getElementById('inputFrontImage').click();">
                                            <i class="fas fa-upload"></i> Choose File
                                        </button>

                                    </div>
                                    <input class="d-none" id="inputFrontImage" type="file" name="front_image" accept="image/*"
                                        onchange="previewImage(this, 'frontImagePreview', 'frontImagePreviewPlaceholder');">
                                </div>
                            </div>
                        </div>

                        <!-- Back Image -->
                        <div class="col-md-6">
                            <label class="mb-2">Back Image</label>
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="image-preview mb-3 d-flex justify-content-center align-items-center flex-column">
                                        <?php if (!empty($first_book['back_image']) && file_exists('../' . $first_book['back_image'])): ?>
                                            <img src="<?php echo htmlspecialchars('../' . $first_book['back_image']); ?>"
                                                alt="Book Back" id="backImagePreview"
                                                style="max-height: 200px; max-width: 100%; border: 1px solid #ddd; border-radius: 4px;">
                                        <?php else: ?>
                                            <div class="text-center py-4 bg-light rounded" id="backImagePreviewPlaceholder">
                                                <i class="fas fa-book-open fa-3x mb-2 text-secondary"></i>
                                                <p class="text-muted">No back image available</p>
                                            </div>
                                            <img src="" alt="Book Back" id="backImagePreview"
                                                style="max-height: 200px; max-width: 100%; display: none; border: 1px solid #ddd; border-radius: 4px;">
                                        <?php endif; ?>
                                    </div>
                                    <div id="backAspectPreviews" class="d-flex justify-content-center gap-2 mb-2"></div>
                                    <div class="small text-muted" id="backImageDimensions"></div>
                                    <div class="d-flex justify-content-center">
                                        <button type="button" class="btn btn-outline-primary me-2" onclick="document.getElementById('inputBackImage').click();">
                                            <i class="fas fa-upload"></i> Choose File
                                        </button>

                                    </div>
                                    <input class="d-none" id="inputBackImage" type="file" name="back_image" accept="image/*"
                                        onchange="previewImage(this, 'backImagePreview', 'backImagePreviewPlaceholder');">
                                </div>
                            </div>
                        </div>
                        </div>

                        <div class="form-group">
                            <label>Dimension (cm)</label>
                            <input type="text" class="form-control" name="dimension" 
                                value="<?php echo htmlspecialchars($first_book['dimension'] ?? ''); ?>"
                                placeholder="e.g., 23 x 24, 23 * 24, or 24 cm²"
                                pattern="^\d+(\s*[x*]\s*\d+)?(\s*cm²)?$"
                                title="Enter dimensions like '23 x 24', '23 * 24', or '24 cm²'">
                            <small class="text-muted">Format: width x height (e.g., 23 x 24) or size squared (e.g., 24 cm²)</small>
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
                                <div class="col-md-4">
                                    <label class="small">Supplementary Contents</label>
                                    <select class="form-control" name="supplementary_content[]" multiple>
                                        <?php
                                        // Parse supplementary contents from first book
                                        $supplementary = $first_book['supplementary_contents'] ?? '';
                                        $supItems = [];

                                        // Extract items from format "includes X, Y, and Z"
                                        if (!empty($supplementary)) {
                                            $supplementary = str_replace('includes ', '', $supplementary);
                                            $supplementary = str_replace(' and ', ', ', $supplementary);
                                            $supItems = array_map('trim', explode(',', $supplementary));
                                        }
                                        ?>
                                        <option value="Appendix" <?php echo (in_array('Appendix', $supItems)) ? 'selected' : ''; ?>>Appendix</option>
                                        <option value="Bibliography" <?php echo (in_array('Bibliography', $supItems)) ? 'selected' : ''; ?>>Bibliography</option>
                                        <option value="Glossary" <?php echo (in_array('Glossary', $supItems)) ? 'selected' : ''; ?>>Glossary</option>
                                        <option value="Index" <?php echo (in_array('Index', $supItems)) ? 'selected' : ''; ?>>Index</option>
                                        <option value="Illustrations" <?php echo (in_array('Illustrations', $supItems)) ? 'selected' : ''; ?>>Illustrations</option>
                                        <option value="Maps" <?php echo (in_array('Maps', $supItems)) ? 'selected' : ''; ?>>Maps</option>
                                        <option value="Tables" <?php echo (in_array('Tables', $supItems)) ? 'selected' : ''; ?>>Tables</option>
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
                                <!-- Removed the call numbers section -->
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
                                                $admin_query = "SELECT CONCAT(firstname, ' ', lastname) AS name, role FROM admins WHERE employee_id = ?";
                                                $stmt = $conn->prepare($admin_query);
                                                $stmt->bind_param("i", $entered_by_id);
                                                $stmt->execute();
                                                $admin_result = $stmt->get_result();
                                                if ($admin_data = $admin_result->fetch_assoc()) {
                                                    $entered_by_name = $admin_data['name'] . ' (' . $admin_data['role'] . ')';
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
                                        $updater_query = "SELECT CONCAT(firstname, ' ', lastname) as full_name, role, employee_id
                                                        FROM admins WHERE employee_id = ?";
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
                                <!-- Book Copies Section -->
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                        <h6 class="m-0 font-weight-bold text-primary">Book List</h6>
                                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addCopiesModal">
                                            <i class="fas fa-plus"></i> Add Copies
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="bookCopiesContainer" class="table-responsive" style="overflow-x: auto;">
                                            <table class="table table-bordered text-center" style="min-width: 1100px;">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 120px; white-space: nowrap;">Copy Number</th>
                                                        <th style="width: 150px; white-space: nowrap;">Accession Number</th>
                                                        <th style="width: 150px; white-space: nowrap;">Shelf Location</th>
                                                        <th style="width: 300px; white-space: nowrap;">Call Number<br><small class="text-muted">Format: [Location] [Class] [Cutter] c[Year] [Vol] [Part] [Copy]</small></th>
                                                        <th style="width: 120px; white-space: nowrap;">Series</th>
                                                        <th style="width: 120px; white-space: nowrap;">Volume</th>
                                                        <th style="width: 120px; white-space: nowrap;">Edition</th>
                                                        <th style="width: 120px; white-space: nowrap;">Part</th>
                                                        <th style="width: 200px; white-space: nowrap;">Status</th>
                                                        <th style="width: 100px; white-space: nowrap;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($books as $book): ?>
                                                        <tr class="book-copy" data-book-id="<?php echo $book['id']; ?>">
                                                            <td>
                                                                <input type="text" class="form-control text-center" name="copy_number[]" value="<?php echo htmlspecialchars($book['copy_number']); ?>">
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
                                                                <input type="text" class="form-control text-center" name="part[]" value="<?php echo htmlspecialchars($book['part']); ?>">
                                                            </td>
                                                            <td>
                                                                <select class="form-control text-center" name="statuses[]">
                                                                    <option value="Available" <?php echo ($book['status'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                                                                    <option value="Borrowed" <?php echo ($book['status'] == 'Borrowed') ? 'selected' : ''; ?>>Borrowed</option>
                                                                    <option value="Lost" <?php echo ($book['status'] == 'Lost') ? 'selected' : ''; ?>>Lost</option>
                                                                    <option value="Damaged" <?php echo ($book['status'] == 'Damaged') ? 'selected' : ''; ?>>Damaged</option>
                                                                    <option value="Reserved" <?php echo ($book['status'] == 'Reserved') ? 'selected' : ''; ?>>Reserved</option>
                                                                    <option value="Processing" <?php echo ($book['status'] == 'Processing') ? 'selected' : ''; ?>>Processing</option>                                                                                                                                 </select>
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
                                    <input type="text" class="form-control" name="url" value="<?php echo htmlspecialchars($first_book['URL'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Content Type, Media Type, Carrier Type in One Row -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Content Type</label>
                                    <select class="form-control" name="content_type">
                                        <option value="Text" <?php echo ($first_book['content_type'] ?? '') == 'Text' ? 'selected' : ''; ?>>Text</option>
                                        <option value="Image" <?php echo ($first_book['content_type'] ?? '') == 'Image' ? 'selected' : ''; ?>>Image</option>
                                        <option value="Video" <?php echo ($first_book['content_type'] ?? '') == 'Video' ? 'selected' : ''; ?>>Video</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Media Type</label>
                                    <select class="form-control" name="media_type">
                                        <option value="Print" <?php echo ($first_book['media_type'] ?? '') == 'Print' ? 'selected' : ''; ?>>Print</option>
                                        <option value="Digital" <?php echo ($first_book['media_type'] ?? '') == 'Digital' ? 'selected' : ''; ?>>Digital</option>
                                        <option value="Audio" <?php echo ($first_book['media_type'] ?? '') == 'Audio' ? 'selected' : ''; ?>>Audio</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Carrier Type</label>
                                    <select class="form-control" name="carrier_type">
                                        <option value="Book" <?php echo ($first_book['carrier_type'] ?? '') == 'Book' ? 'selected' : ''; ?>>Book</option>
                                        <option value="CD" <?php echo ($first_book['carrier_type'] ?? '') == 'CD' ? 'selected' : ''; ?>>CD</option>
                                        <option value="USB" <?php echo ($first_book['carrier_type'] ?? '') == 'USB' ? 'selected' : ''; ?>>USB</option>
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

<!-- Add the modal for "Add Copies" -->
<div class="modal fade" id="addCopiesModal" tabindex="-1" aria-labelledby="addCopiesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="process-add-copies.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCopiesModalLabel">Add More Copies</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Add additional copies of <strong><?php echo htmlspecialchars($first_book['title'] ?? ''); ?></strong></p>
                    <div class="form-group">
                        <label for="num_copies">Number of copies to add:</label>
                        <input type="number" class="form-control" id="num_copies" name="num_copies" min="1" value="1" required>
                    </div>
                    <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($first_book['id'] ?? ''); ?>">
                    <input type="hidden" name="accession" value="<?php echo htmlspecialchars($first_book['accession'] ?? ''); ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Copies</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Instructions Modal -->
<div class="modal fade" id="instructionsModal" tabindex="-1" role="dialog" aria-labelledby="instructionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="instructionsModalLabel">
                    <i class="fas fa-info-circle mr-2"></i>How to Update Books
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>This page allows you to update information for all selected book copies simultaneously.</li>
                            <li>Any changes to common fields will apply to all copies.</li>
                            <li>You can modify accession numbers and statuses individually for each copy.</li>
                        </ol>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">Tips for Updating</h6>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li><strong>Call Numbers</strong>: Ensure proper formatting with classification, cutter, and copy number.</li>
                            <li><strong>Accession Numbers</strong>: Each accession number must be unique in the system.</li>
                            <li><strong>Contributors</strong>: You can update authors, co-authors, and editors - changes apply to all copies.</li>
                            <li><strong>Status</strong>: You can set the status of each copy individually (Available, Borrowed, etc.).</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Basic Tab Functionality Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Bootstrap 5 tab initialization
    const triggerTabList = [].slice.call(document.querySelectorAll('#formTabs a'));
    triggerTabList.forEach(function(triggerEl) {
        triggerEl.addEventListener('click', function(event) {
            event.preventDefault();
            const tab = new bootstrap.Tab(triggerEl);
            tab.show();
        });
    });

    // Ensure first tab is active on page load
    const firstTab = document.querySelector('#formTabs a:first-child');
    if (firstTab) {
        const firstTabTrigger = new bootstrap.Tab(firstTab);
        firstTabTrigger.show();
    }

    // Enhanced function to automatically update call numbers when fields change
    function updateCallNumber(copyElement) {
        // Get all the elements for this specific row
        const callNumberField = copyElement.querySelector('input[name="call_numbers[]"]');
        const shelfLocation = copyElement.querySelector('select[name="shelf_location[]"]').value;
        const volumeInput = copyElement.querySelector('input[name="volume[]"]').value.trim();
        const partInput = copyElement.querySelector('input[name="part[]"]').value.trim();
        const copyNumber = copyElement.querySelector('input[name="copy_number[]"]').value.trim();
        const publishYear = document.querySelector('input[name="publish_date"]')?.value || '';

        if (!callNumberField) return;

        // Extract classifier and cutter from current call number
        let currentCallNum = callNumberField.value;
        let classifierCutter = '';

        // Parse current call number to find classifier and cutter portions (e.g., "Z936.98 L39")
        const parts = currentCallNum.split(' ');

        // First, try to detect the Location + Class + Cutter pattern
        for (let i = 0; i < parts.length - 1; i++) {
            // Look for patterns like "Z936.98 L39" where first part is class and second is cutter
            if (i > 0 && /^[A-Z0-9]+(\.[0-9]+)?$/.test(parts[i]) && /^[A-Z][0-9]+/.test(parts[i+1])) {
                classifierCutter = `${parts[i]} ${parts[i+1]}`;
                break;
            }
        }

        // If we couldn't find it but have at least 3 parts, assume parts[1] and parts[2] are classifier and cutter
        if (!classifierCutter && parts.length >= 3) {
            // Try to use the middle part of the call number as the classifier+cutter
            classifierCutter = `${parts[1]} ${parts[2]}`;
        }

        // Build new call number components
        const components = [];

        // Start with shelf location
        if (shelfLocation) {
            components.push(shelfLocation);
        }

        // Add classifier and cutter if available
        if (classifierCutter) {
            components.push(classifierCutter);
        }

        // Add publication year if available (with "c" prefix for copyright)
        if (publishYear) {
            components.push(`c${publishYear}`);
        }

        // Add volume if available
        if (volumeInput) {
            components.push(`v.${volumeInput}`);
        }

        // Add part if available
        if (partInput) {
            components.push(`pt.${partInput}`);
        }

        // Add copy number if available
        if (copyNumber) {
            components.push(`c.${copyNumber}`);
        }

        // Update the call number field with new formatted call number
        if (components.length > 1) {
            callNumberField.value = components.join(' ');
        }
    }

    // Setup event listeners for fields that affect call numbers
    function setupCallNumberEventListeners() {
        const bookCopies = document.querySelectorAll('.book-copy');

        bookCopies.forEach(copy => {
            // Get all relevant fields that should trigger call number updates
            const shelfLocation = copy.querySelector('select[name="shelf_location[]"]');
            const volumeInput = copy.querySelector('input[name="volume[]"]');
            const partInput = copy.querySelector('input[name="part[]"]');
            const copyNumberInput = copy.querySelector('input[name="copy_number[]"]');
            const publishYear = document.querySelector('input[name="publish_date"]');

            // Add event listeners to each field to update the call number when they change
            if (shelfLocation) {
                                shelfLocation.addEventListener('change', () => updateCallNumber(copy));
            }

            if (volumeInput) {
                volumeInput.addEventListener('input', () => updateCallNumber(copy));
            }

            if (partInput) {
                partInput.addEventListener('input', () => updateCallNumber(copy));
            }

            if (copyNumberInput) {
                copyNumberInput.addEventListener('input', () => updateCallNumber(copy));
            }
        });

        // Publication year update affects all call numbers
        const publishYearInput = document.querySelector('input[name="publish_date"]');
        if (publishYearInput) {
            publishYearInput.addEventListener('input', () => {
                document.querySelectorAll('.book-copy').forEach(copy => {
                    updateCallNumber(copy);
                });
            });
        }
    }

    // Initialize call number event listeners
    setupCallNumberEventListeners();

    // Update all call numbers once on page load to ensure consistency
    setTimeout(() => {
        document.querySelectorAll('.book-copy').forEach(copy => {
            updateCallNumber(copy);
        });
    }, 500);

    // Handle shelf location propagation (keep existing behavior)
    const shelfLocationSelects = document.querySelectorAll('select[name="shelf_location[]"]');
    shelfLocationSelects.forEach((select, index) => {
        select.addEventListener('change', function() {
            const newValue = this.value;
            const totalSelects = shelfLocationSelects.length;

            // If changing the first copy, update all subsequent copies
            if (index === 0) {
                for (let i = 1; i < totalSelects; i++) {
                    shelfLocationSelects[i].value = newValue;
                    // Also update the call number for these rows
                    updateCallNumber(shelfLocationSelects[i].closest('.book-copy'));
                }
            } else {
                // For any other copy, only update copies that come after it
                for (let i = index + 1; i < totalSelects; i++) {
                    shelfLocationSelects[i].value = newValue;
                    // Also update the call number for these rows
                    updateCallNumber(shelfLocationSelects[i].closest('.book-copy'));
                }
            }
        });
    });
});

// Add tooltip library for call number suggestions
document.addEventListener('DOMContentLoaded', function() {
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/@popperjs/core@2';
    script.onload = function() {
        const tippyScript = document.createElement('script');
        tippyScript.src = 'https://unpkg.com/tippy.js@6';
        document.head.appendChild(tippyScript);
    };
    document.head.appendChild(script);

    // Add confirmation when manually editing call numbers
    document.querySelectorAll('input[name="call_numbers[]"]').forEach(input => {
        input.addEventListener('focus', function() {
            this.dataset.originalValue = this.value;
        });

        input.addEventListener('blur', function() {
            if (this.value !== this.dataset.originalValue) {
                // Only show confirmation if the value actually changed
                if (confirm("You've manually edited the call number. Would you like to keep this manual edit instead of auto-formatting?")) {
                    this.dataset.manuallyEdited = "true";
                } else {
                    // If they don't want to keep it, revert to auto-formatted
                    const bookCopy = this.closest('.book-copy');
                    updateCallNumber(bookCopy);
                }
            }
        });
    });
});

// Image preview functionality
function previewImage(input, previewId, placeholderId) {
    const preview = document.getElementById(previewId);
    const placeholder = document.getElementById(placeholderId);
    const fileNameDisplay = (input.id === 'inputFrontImage')
        ? document.getElementById('frontFileNameDisplay')
        : document.getElementById('backFileNameDisplay');
    const dimId = (input.id === 'inputFrontImage') ? 'frontImageDimensions' : 'backImageDimensions';
    const aspectId = (input.id === 'inputFrontImage') ? 'frontAspectPreviews' : 'backAspectPreviews';
    const dim = document.getElementById(dimId);

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
            if (fileNameDisplay) fileNameDisplay.textContent = 'Selected: ' + input.files[0].name;
            preview.onload = function() {
                if (dim) dim.textContent = `Full size: ${preview.naturalWidth} x ${preview.naturalHeight} px`;
                updateAspectPreviews(previewId, aspectId);
            };
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '';
        preview.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
        if (fileNameDisplay) fileNameDisplay.textContent = 'No file selected';
        if (dim) dim.textContent = '';
        const aspectContainer = document.getElementById(aspectId);
        if (aspectContainer) aspectContainer.innerHTML = '';
    }
}

// Clear image functionality
function clearImage(type) {
    if (type === 'front') {
        // Clear front image
        const preview = document.getElementById('frontImagePreview');
        const placeholder = document.getElementById('frontImagePreviewPlaceholder');
        const fileInput = document.getElementById('inputFrontImage');
        const fileNameDisplay = document.getElementById('frontFileNameDisplay');
        const dimId = 'frontImageDimensions';
        const aspectId = 'frontAspectPreviews';

        preview.src = '';
        preview.style.display = 'none';
        if (placeholder) {
            placeholder.style.display = 'block';
        } else {
            // Create placeholder if it doesn't exist
            const container = preview.parentElement;
            const newPlaceholder = document.createElement('div');
            newPlaceholder.id = 'frontImagePreviewPlaceholder';
            newPlaceholder.className = 'text-center py-4 bg-light rounded';
            newPlaceholder.innerHTML = '<i class="fas fa-book fa-3x mb-2 text-secondary"></i><p class="text-muted">No front image available</p>';
            container.insertBefore(newPlaceholder, preview);
        }

        // Clear the file input
        fileInput.value = '';
        fileNameDisplay.textContent = 'No file selected';

        // Clear dimensions and aspect previews
        const dim = document.getElementById(dimId);
        const aspectContainer = document.getElementById(aspectId);
        if (dim) dim.textContent = '';
        if (aspectContainer) aspectContainer.innerHTML = '';

        // Add a hidden input to tell the server to remove the image
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'remove_front_image';
        hiddenInput.value = '1';
        fileInput.parentElement.appendChild(hiddenInput);

    } else if (type === 'back') {
        // Clear back image
        const preview = document.getElementById('backImagePreview');
        const placeholder = document.getElementById('backImagePreviewPlaceholder');
        const fileInput = document.getElementById('inputBackImage');
        const fileNameDisplay = document.getElementById('backFileNameDisplay');
        const dimId = 'backImageDimensions';
        const aspectId = 'backAspectPreviews';

        preview.src = '';
        preview.style.display = 'none';
        if (placeholder) {
            placeholder.style.display = 'block';
        } else {
            // Create placeholder if it doesn't exist
            const container = preview.parentElement;
            const newPlaceholder = document.createElement('div');
            newPlaceholder.id = 'backImagePreviewPlaceholder';
            newPlaceholder.className = 'text-center py-4 bg-light rounded';
            newPlaceholder.innerHTML = '<i class="fas fa-book-open fa-3x mb-2 text-secondary"></i><p class="text-muted">No back image available</p>';
            container.insertBefore(newPlaceholder, preview);
        }

        // Clear the file input
        fileInput.value = '';
        fileNameDisplay.textContent = 'No file selected';

        // Clear dimensions and aspect previews
        const dim = document.getElementById(dimId);
        const aspectContainer = document.getElementById(aspectId);
        if (dim) dim.textContent = '';
        if (aspectContainer) aspectContainer.innerHTML = '';

        // Add a hidden input to tell the server to remove the image
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'remove_back_image';
        hiddenInput.value = '1';
        fileInput.parentElement.appendChild(hiddenInput);
    }
}

// Aspect ratio preview functionality
function createAspectPreview(imgSrc, aspect, label) {
    const [w, h] = aspect;
    const container = document.createElement('div');
    container.style.width = '96px';
    container.style.height = `${96 * h / w}px`;
    container.style.overflow = 'hidden';
    container.style.position = 'relative';
    container.style.background = '#f8f9fa';
    container.style.border = '1px solid #ddd';
    container.style.borderRadius = '4px';
    container.style.display = 'flex';
    container.style.alignItems = 'center';
    container.style.justifyContent = 'center';
    container.title = label;

    const img = document.createElement('img');
    img.src = imgSrc;
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = 'cover';
    img.alt = label + ' preview';

    container.appendChild(img);

    const lbl = document.createElement('div');
    lbl.textContent = label;
    lbl.style.fontSize = '11px';
    lbl.style.textAlign = 'center';
    lbl.style.position = 'absolute';
    lbl.style.bottom = '-18px';
    lbl.style.left = '50%';
    lbl.style.transform = 'translateX(-50%)';
    lbl.style.color = '#888';
    container.appendChild(lbl);

    return container;
}

function updateAspectPreviews(imgId, aspectContainerId) {
    const img = document.getElementById(imgId);
    const aspectContainer = document.getElementById(aspectContainerId);
    if (!aspectContainer) return;
    aspectContainer.innerHTML = '';
    if (!img || !img.src || img.style.display === 'none') return;

    if (!img.complete) {
        img.onload = () => updateAspectPreviews(imgId, aspectContainerId);
        return;
    }

    aspectContainer.appendChild(createAspectPreview(img.src, [4,3], '4:3'));
    aspectContainer.appendChild(createAspectPreview(img.src, [1,1], '1:1'));
    aspectContainer.appendChild(createAspectPreview(img.src, [16,9], '16:9'));
}

function displayImageDimensions(imgId, dimId) {
    const img = document.getElementById(imgId);
    const dim = document.getElementById(dimId);
    if (!img || !dim) return;
    if (img.src && img.style.display !== 'none') {
        if (img.complete) {
            dim.textContent = `Full size: ${img.naturalWidth} x ${img.naturalHeight} px`;
        } else {
            img.onload = function() {
                dim.textContent = `Full size: ${img.naturalWidth} x ${img.naturalHeight} px`;
            };
        }
    } else {
        dim.textContent = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    displayImageDimensions('frontImagePreview', 'frontImageDimensions');
    displayImageDimensions('backImagePreview', 'backImageDimensions');
    updateAspectPreviews('frontImagePreview', 'frontAspectPreviews');
    updateAspectPreviews('backImagePreview', 'backAspectPreviews');
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dimensionInput = document.querySelector('input[name="dimension"]');
    
    dimensionInput.addEventListener('input', function(e) {
        let value = e.target.value.trim();
        
        // Convert * to x if used
        value = value.replace(/\*/g, 'x');
        
        // Clean up spaces around x
        value = value.replace(/\s*x\s*/g, ' x ');
        
        // Format "cm squared" or "cm2" to cm²
        value = value.replace(/cm\s*squared|cm2/i, 'cm²');
        
        // Update the input value with formatted version
        e.target.value = value;
    });
    
    dimensionInput.addEventListener('blur', function(e) {
        let value = e.target.value.trim();
        
        // Add "cm" if dimensions are provided without unit
        if (value.match(/^\d+\s*x\s*\d+$/)) {
            e.target.value = value + ' cm';
        }
        
        // Convert any remaining "squared" notation to ²
        if (value.includes('cm squared')) {
            e.target.value = value.replace('cm squared', 'cm²');
        }
    });
});
</script>
