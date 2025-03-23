<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Add transaction support check
$transactionSupported = true;
try {
    mysqli_begin_transaction($conn);
    mysqli_rollback($conn);
} catch (Exception $e) {
    $transactionSupported = false;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($transactionSupported) {
        mysqli_begin_transaction($conn);
    }
    
    try {
        $accessions = $_POST['accession'];
        $number_of_copies_array = $_POST['number_of_copies'];
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $preferred_title = mysqli_real_escape_string($conn, $_POST['preferred_title']);
        $parallel_title = mysqli_real_escape_string($conn, $_POST['parallel_title']);
        $summary = mysqli_real_escape_string($conn, $_POST['abstract']);
        $contents = mysqli_real_escape_string($conn, $_POST['notes']);
        
        // Process author, co-authors, and editors
        $author_ids = array_map(function($author) use ($conn) {
            return mysqli_real_escape_string($conn, $author);
        }, $_POST['author']);
        $co_authors_ids = isset($_POST['co_authors']) ? $_POST['co_authors'] : [];
        $editors_ids = isset($_POST['editors']) ? $_POST['editors'] : [];

        // Process publisher and publication date
        $publisher_name = mysqli_real_escape_string($conn, $_POST['publisher']);
        $publish_date = mysqli_real_escape_string($conn, $_POST['publish_date']);

        // Function to get writer ID if exists
        function getWriterId($conn, $name) {
            $name_parts = explode(' ', $name);
            $firstname = $name_parts[0];
            $lastname = end($name_parts);
            $middle_init = count($name_parts) > 2 ? $name_parts[1] : '';

            $check_writer_query = "SELECT id FROM writers WHERE firstname='$firstname' AND middle_init='$middle_init' AND lastname='$lastname'";
            $result = mysqli_query($conn, $check_writer_query);
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return $row['id'];
            } else {
                return false;
            }
        }

        // Function to insert publisher if not exists and return publisher ID
        function getPublisherId($conn, $name) {
            $check_publisher_query = "SELECT id FROM publishers WHERE publisher='$name'";
            $result = mysqli_query($conn, $check_publisher_query);
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return $row['id'];
            } else {
                $insert_publisher_query = "INSERT INTO publishers (publisher) VALUES ('$name')";
                if (mysqli_query($conn, $insert_publisher_query)) {
                    return mysqli_insert_id($conn);
                } else {
                    return false;
                }
            }
        }

        // Get publisher ID
        $publisher_id = getPublisherId($conn, $publisher_name);
        if (!$publisher_id) {
            $error_messages[] = "Error adding publisher: " . mysqli_error($conn);
        }

        // Handle file uploads
        $front_image = '';
        $back_image = '';
        if(isset($_FILES['front_image']) && $_FILES['front_image']['error'] == 0) {
            $front_image = 'uploads/' . basename($_FILES['front_image']['name']);
            move_uploaded_file($_FILES['front_image']['tmp_name'], $front_image);
        }
        if(isset($_FILES['back_image']) && $_FILES['back_image']['error'] == 0) {
            $back_image = 'uploads/' . basename($_FILES['back_image']['name']);
            move_uploaded_file($_FILES['back_image']['tmp_name'], $back_image);
        }

        $dimension = mysqli_real_escape_string($conn, $_POST['dimension']);
        $content_type = mysqli_real_escape_string($conn, $_POST['content_type']);
        $media_type = mysqli_real_escape_string($conn, $_POST['media_type']);
        $carrier_type = mysqli_real_escape_string($conn, $_POST['carrier_type']);
        $url = mysqli_real_escape_string($conn, $_POST['url']);
        $language = mysqli_real_escape_string($conn, $_POST['language']);
        $entered_by = $_POST['entered_by'];
        $date_added = $_POST['date_added'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $last_update = $_POST['last_update'];

        // Process total pages and supplementary contents separately
        $prefix_pages = mysqli_real_escape_string($conn, $_POST['prefix_pages']);
        $main_pages = mysqli_real_escape_string($conn, $_POST['main_pages']);
        $supplementary_content = isset($_POST['supplementary_content']) ? $_POST['supplementary_content'] : [];
        
        // Create total_pages without supplementary content
        $total_pages = trim("$prefix_pages $main_pages");
        
        // Prepare supplementary_contents for storage
        $supplementary_contents = !empty($supplementary_content) ? implode('; ', $supplementary_content) : '';

        $success_count = 0;
        $error_messages = array();
        $call_number_index = 0;
        $copy_number = 1; // Initialize copy_number

        // Process each accession group
        for ($i = 0; $i < count($accessions); $i++) {
            $base_accession = $accessions[$i];
            $copies_for_this_accession = (int)$number_of_copies_array[$i];
            $current_isbn = isset($_POST['isbn'][$i]) ? mysqli_real_escape_string($conn, $_POST['isbn'][$i]) : '';
            
            for ($j = 0; $j < $copies_for_this_accession; $j++) {
                $current_accession = $base_accession + $j;
                $current_call_number = isset($_POST['call_number'][$call_number_index]) ? 
                    mysqli_real_escape_string($conn, $_POST['call_number'][$call_number_index]) : '';
                $current_shelf_location = isset($_POST['shelf_locations'][$call_number_index]) ? 
                    mysqli_real_escape_string($conn, $_POST['shelf_locations'][$call_number_index]) : '';
                $call_number_index++;

                // Format the call number
                $formatted_call_number = $current_shelf_location . ' ' . $current_call_number . ' c' . $copy_number;

                // Check for duplicate accession
                $check_query = "SELECT * FROM books WHERE accession = '$current_accession'";
                $result = mysqli_query($conn, $check_query);
                
                if (mysqli_num_rows($result) > 0) {
                    $error_messages[] = "Accession number $current_accession already exists - skipping.";
                    continue;
                }

                // Get current copy number
                $copy_query = "SELECT MAX(copy_number) as max_copy FROM books WHERE title = '$title'";
                $copy_result = mysqli_query($conn, $copy_query);
                $copy_row = mysqli_fetch_assoc($copy_result);
                $copy_number = ($copy_row['max_copy'] !== null) ? $copy_row['max_copy'] + 1 : 1;

                // Process subject entries for this copy
                $subject_categories = isset($_POST['subject_categories']) ? $_POST['subject_categories'] : array();
                $subject_paragraphs = isset($_POST['subject_paragraphs']) ? $_POST['subject_paragraphs'] : array();

                // Combine all subject entries into strings for storage
                $all_categories = array();
                $all_details = array();

                for ($k = 0; $k < count($subject_categories); $k++) {
                    if (!empty($subject_categories[$k])) {
                        $all_categories[] = mysqli_real_escape_string($conn, $subject_categories[$k]);
                        $all_details[] = mysqli_real_escape_string($conn, $subject_paragraphs[$k]);
                    }
                }

                $subject_category = implode('; ', $all_categories);
                $subject_detail = implode('; ', $all_details);

                $query = "INSERT INTO books (
                    accession, title, preferred_title, parallel_title, 
                    subject_category, subject_detail,
                    summary, contents, front_image, back_image, 
                    dimension, series, volume, edition, 
                    copy_number, total_pages, supplementary_contents, ISBN, content_type, 
                    media_type, carrier_type, call_number, URL, 
                    language, shelf_location, entered_by, date_added, 
                    status, last_update
                ) VALUES (
                    '$current_accession', '$title', '$preferred_title', '$parallel_title',
                    '$subject_category', '$subject_detail',
                    '$summary', '$contents', '$front_image', '$back_image',
                    '$dimension', '$series', '$volume', '$edition',
                    $copy_number, '$total_pages', '$supplementary_contents', '$current_isbn', '$content_type',
                    '$media_type', '$carrier_type', '$formatted_call_number', '$url',
                    '$language', '$current_shelf_location', '$entered_by', '$date_added',
                    '$status', '$last_update'
                )";

                if (mysqli_query($conn, $query)) {
                    $success_count++;

                    // Insert into publications table
                    $book_id = mysqli_insert_id($conn);
                    $publication_query = "INSERT INTO publications (book_id, publisher_id, publish_date) VALUES ('$book_id', '$publisher_id', '$publish_date')";
                    if (!mysqli_query($conn, $publication_query)) {
                        $error_messages[] = "Error adding publication for book with accession $current_accession: " . mysqli_error($conn);
                    }

                    // Insert contributors in batches
                    $contributors = [];

                    // Add authors
                    foreach ($author_ids as $author_id) {
                        $contributors[] = "('$book_id', '$author_id', 'Author')";
                    }

                    // Add co-authors
                    foreach ($co_authors_ids as $co_author_id) {
                        $contributors[] = "('$book_id', '$co_author_id', 'Co-Author')";
                    }

                    // Add editors
                    foreach ($editors_ids as $editor_id) {
                        $contributors[] = "('$book_id', '$editor_id', 'Editor')";
                    }

                    // Insert all contributors in one query
                    if (!empty($contributors)) {
                        $contributor_query = "INSERT INTO contributors (book_id, writer_id, role) VALUES " . implode(', ', $contributors);
                        if (!mysqli_query($conn, $contributor_query)) {
                            $error_messages[] = "Error adding contributors for book with accession $current_accession: " . mysqli_error($conn);
                        }
                    }
                } else {
                    $error_messages[] = "Error adding book with accession $current_accession: " . mysqli_error($conn);
                }
            }
        }

        if ($transactionSupported) {
            mysqli_commit($conn);
        }
        $_SESSION['success_message'] = "Successfully added all " . $success_count . " books!";
        header("Location: book_list.php");
        exit();
    } catch (Exception $e) {
        if ($transactionSupported) {
            mysqli_rollback($conn);
        }
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: add-book.php");
        exit();
    }
}
?>
