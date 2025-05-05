<?php
// This file processes the book form submission

if (!isset($_SESSION)) {
    session_start();
}

// Include database connection if not already included
if (!isset($conn)) {
    include '../../db.php';
}

// Add transaction support check
$transactionSupported = true;
try {
    mysqli_begin_transaction($conn);
    mysqli_rollback($conn);
} catch (Exception $e) {
    $transactionSupported = false;
}

// Check if the form was submitted
if (isset($_POST['submit'])) {
    // Validate required fields with more detailed checking
    $required_fields = [
        'title' => 'Book Title', 
        'accession' => 'Accession Number'
    ];
    
    $errors = [];
    
    foreach ($required_fields as $field => $display_name) {
        if (empty($_POST[$field])) {
            $errors[] = "$display_name is required";
        }
    }
    
    // Validate author information
    if (empty($_POST['author']) && (!isset($_POST['authors']) || empty($_POST['authors']))) {
        $errors[] = "At least one author must be selected";
    }
    
    // Validate publisher information from session
    if (empty($_SESSION['book_shortcut']['publisher_id'])) {
        $errors[] = "Publisher information is missing. Please go back and select a publisher";
    }
    
    // Validate accession numbers format
    if (!empty($_POST['accession']) && is_array($_POST['accession'])) {
        foreach ($_POST['accession'] as $i => $accession) {
            if (empty($accession)) {
                $errors[] = "Accession number for group " . ($i + 1) . " is required";
            } else {
                // Check for duplicate accession in database
                $check_query = "SELECT accession FROM books WHERE accession = '" . mysqli_real_escape_string($conn, $accession) . "'";
                $result = mysqli_query($conn, $check_query);
                if (mysqli_num_rows($result) > 0) {
                    $errors[] = "Accession number '$accession' already exists in the database";
                }
            }
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['error_message'] = 'Please fix the following errors:<ul><li>' . implode('</li><li>', $errors) . '</li></ul>';
        // Return without redirection, letting the form handle the error display
        return;
    }
    
    // Process the form if validation passes
    try {
        // Start a transaction if supported
        if ($transactionSupported) {
            mysqli_begin_transaction($conn);
        }

        // Initialize variables for images and first book id
        $front_image = '';
        $back_image = '';
        $first_book_id = null;
        $imageFolder = '../Images/book-image/';

        // Ensure the folder exists
        if (!is_dir($imageFolder)) {
            mkdir($imageFolder, 0777, true);
        }

        // Process all book insertions first to get the first book ID
        $successful_inserts = 0;
        $all_book_ids = [];
        
        // Process each accession group
        if (isset($_POST['accession']) && is_array($_POST['accession'])) {
            // Get the first author ID for backward compatibility
            $author_id = isset($_POST['author']) ? intval($_POST['author']) : 0;
            // Get all author IDs from the authors array if it exists
            $authors_ids = isset($_POST['authors']) && is_array($_POST['authors']) ? $_POST['authors'] : [];
            // If no authors array but we have a single author, use that
            if (empty($authors_ids) && $author_id > 0) {
                $authors_ids = [$author_id];
            }
            
            $book_title = mysqli_real_escape_string($conn, $_POST['title']);
            
            // Process dimension field - add cm² if it's only a number, otherwise add (cm)
            $dimension = mysqli_real_escape_string($conn, $_POST['dimension'] ?? '');
            if (!empty($dimension)) {
                $dimension = trim($dimension);
                // Check if it's just a number (single part)
                if (is_numeric($dimension)) {
                    $dimension .= ' cm²';
                } 
                // Check if it has multiple parts (contains x, * or spaces)
                else if (strpos($dimension, 'x') !== false || strpos($dimension, '*') !== false || strpos($dimension, ' ') !== false) {
                    // Add (cm) if not already present with a unit
                    if (!preg_match('/\(cm\)$|\s+cm$|\s+cm²$/', $dimension)) {
                        $dimension .= ' (cm)';
                    }
                } 
                // For other formats without units, add (cm)
                else if (!preg_match('/\(cm\)$|\s+cm$|\s+cm²$/', $dimension)) {
                    $dimension .= ' (cm)';
                }
            }
            
            // Extract subject and program before the copy loop
            $subject_categories = $_POST['subject_categories'] ?? [];
            $programs = $_POST['program'] ?? [];
            $subject_details = $_POST['subject_paragraphs'] ?? [];

            // Combine multiple categories, programs and details if present
            $combined_subject_category = '';
            $combined_program = '';
            $combined_subject_detail = '';

            foreach ($subject_categories as $i => $category) {
                if (!empty($category)) {
                    $category = mysqli_real_escape_string($conn, $category);
                    $program = mysqli_real_escape_string($conn, $programs[$i] ?? '');
                    $detail = mysqli_real_escape_string($conn, $subject_details[$i] ?? '');
                    
                    $combined_subject_category .= (!empty($combined_subject_category) ? '; ' : '') . $category;
                    $combined_program .= (!empty($combined_program) ? '; ' : '') . $program;
                    $combined_subject_detail .= (!empty($combined_subject_detail) ? '; ' : '') . $detail;
                }
            }

            // Get employee_id from session instead of admin_id
            $entered_by = isset($_POST['entered_by']) ? $_POST['entered_by'] : $_SESSION['admin_employee_id'];
            // Use same value for updated_by field from session
            $updated_by = $_SESSION['admin_employee_id'];

            for ($i = 0; $i < count($_POST['accession']); $i++) {
                $base_accession = $_POST['accession'][$i];
                $copies = intval($_POST['number_of_copies'][$i] ?? 1);
                $isbn = mysqli_real_escape_string($conn, $_POST['isbn'][$i] ?? '');
                $series = mysqli_real_escape_string($conn, $_POST['series'][$i] ?? '');
                $volume = mysqli_real_escape_string($conn, $_POST['volume'][$i] ?? '');
                $part = mysqli_real_escape_string($conn, $_POST['part'][$i]); // Add part field
                $edition = mysqli_real_escape_string($conn, $_POST['edition'][$i] ?? '');
                
                // Get shelf locations and call numbers
                $call_numbers = $_POST['call_number'] ?? [];
                $shelf_locations = $_POST['shelf_locations'] ?? [];
                $copy_numbers = $_POST['copy_number'] ?? []; // Get copy numbers
                
                // Process copies
                $current_index = 0;
                
                for ($copy = 0; $copy < $copies; $copy++) {
                    // Handle accession number appropriately
                    $accession_str = calculateAccession($base_accession, $copy); // Ensure leading zeroes
                    // Store the accession string directly without converting to integer
                    $accession = mysqli_real_escape_string($conn, $accession_str);

                    // Check if accession number already exists
                    $check_query = "SELECT * FROM books WHERE accession = '$accession'";
                    $result = mysqli_query($conn, $check_query);

                    if (mysqli_num_rows($result) > 0) {
                        $error_messages[] = "Accession number $accession already exists - skipping.";
                        continue;
                    }
                    
                    // Get all the necessary fields for insertion
                    $title = mysqli_real_escape_string($conn, $_POST['title']);
                    $preferred_title = mysqli_real_escape_string($conn, $_POST['preferred_title'] ?? '');
                    $parallel_title = mysqli_real_escape_string($conn, $_POST['parallel_title'] ?? '');
                    $summary = mysqli_real_escape_string($conn, $_POST['abstract'] ?? '');
                    $contents = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
                    $dimension = mysqli_real_escape_string($conn, $_POST['dimension'] ?? '');
                    $total_pages = mysqli_real_escape_string($conn, trim(($_POST['prefix_pages'] ?? '') . ' ' . ($_POST['main_pages'] ?? '')));
                    $supplementary_contents = mysqli_real_escape_string($conn, $_POST['supplementary_contents'] ?? '');
                    $content_type = mysqli_real_escape_string($conn, $_POST['content_type'] ?? '');
                    $media_type = mysqli_real_escape_string($conn, $_POST['media_type'] ?? '');
                    $carrier_type = mysqli_real_escape_string($conn, $_POST['carrier_type'] ?? '');
                    $url = mysqli_real_escape_string($conn, $_POST['url'] ?? '');
                    $language = mysqli_real_escape_string($conn, $_POST['language'] ?? '');
                    $date_added = mysqli_real_escape_string($conn, $_POST['date_added'] ?? date('Y-m-d'));
                    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Available');
                    $last_update = mysqli_real_escape_string($conn, $_POST['last_update'] ?? date('Y-m-d'));
                    
                    // Get shelf location and call number for this copy
                    $shelf_location = isset($shelf_locations[$copy]) ? mysqli_real_escape_string($conn, $shelf_locations[$copy]) : '';
                    $call_number = isset($call_numbers[$copy]) ? mysqli_real_escape_string($conn, $call_numbers[$copy]) : '';
                    $copy_number = isset($copy_numbers[$copy]) ? intval($copy_numbers[$copy]) : ($copy + 1);
                    
                    // Format the call number with shelf location
                    $formatted_call_number = $shelf_location . ' ' . $call_number;
                    if (!empty($copy_number)) {
                        $formatted_call_number .= ' c.' . $copy_number;
                    }

                    // If volume exists, insert it before copy number
                    if (!empty($volume)) {
                        $parts = explode(' c.', $formatted_call_number);
                        $formatted_call_number = $parts[0] . ' vol.' . $volume;
                        
                        // Add part if present
                        if (!empty($part)) {
                            $formatted_call_number .= ' pt.' . $part;
                        }
                        
                        // Add back the copy number
                        if (isset($parts[1])) {
                            $formatted_call_number .= ' c.' . $parts[1];
                        }
                    }

                    // Insert into books table with the properly formatted call number
                    $insert_book_query = "INSERT INTO books (
                        accession, title, preferred_title, parallel_title, 
                        summary, contents, dimension, series, volume, part, edition,
                        copy_number, total_pages, supplementary_contents, ISBN, content_type, 
                        media_type, carrier_type, call_number, URL, language, shelf_location, 
                        entered_by, date_added, status, updated_by, last_update, subject_category, program, subject_detail, front_image, back_image
                    ) VALUES (
                        '$accession', '$title', '$preferred_title', '$parallel_title',
                        '$summary', '$contents', '$dimension', '$series', '$volume', '$part', '$edition',
                        '$copy_number', '$total_pages', '$supplementary_contents', '$isbn', '$content_type',
                        '$media_type', '$carrier_type', '$formatted_call_number', '$url',
                        '$language', '$shelf_location', '$entered_by', '$date_added',
                        '$status', '$updated_by', '$last_update', '$combined_subject_category', '$combined_program', '$combined_subject_detail', '$front_image', '$back_image'
                    )";

                    // Insert into books table first to get the ID
                    if (!mysqli_query($conn, $insert_book_query)) {
                        throw new Exception("Error inserting book data: " . mysqli_error($conn));
                    }
                    
                    $successful_inserts++;
                    
                    $book_id = mysqli_insert_id($conn);
                    if ($first_book_id === null) {
                        $first_book_id = $book_id;
                    }
                    $all_book_ids[] = $book_id;
                }
            }
        }

        // After getting first_book_id, process images
        if ($first_book_id) {
            // Process front image
            if (isset($_FILES['front_image']) && $_FILES['front_image']['error'] === UPLOAD_ERR_OK) {
                $extension = strtolower(pathinfo($_FILES['front_image']['name'], PATHINFO_EXTENSION));
                $frontImageName = $first_book_id . '_front.' . $extension;
                $frontImagePath = $imageFolder . $frontImageName;

                if (move_uploaded_file($_FILES['front_image']['tmp_name'], $frontImagePath)) {
                    $front_image = 'Images/book-image/' . $frontImageName;
                    
                    // Update all books with the same front image path
                    $update_front_image = "UPDATE books SET front_image = '$front_image' WHERE id IN (" . implode(',', $all_book_ids) . ")";
                    if (!mysqli_query($conn, $update_front_image)) {
                        throw new Exception("Failed to update front image for all copies.");
                    }
                } else {
                    throw new Exception("Failed to upload front image.");
                }
            }

            // Process back image
            if (isset($_FILES['back_image']) && $_FILES['back_image']['error'] === UPLOAD_ERR_OK) {
                $extension = strtolower(pathinfo($_FILES['back_image']['name'], PATHINFO_EXTENSION));
                $backImageName = $first_book_id . '_back.' . $extension;
                $backImagePath = $imageFolder . $backImageName;

                if (move_uploaded_file($_FILES['back_image']['tmp_name'], $backImagePath)) {
                    $back_image = 'Images/book-image/' . $backImageName;
                    
                    // Update all books with the same back image path
                    $update_back_image = "UPDATE books SET back_image = '$back_image' WHERE id IN (" . implode(',', $all_book_ids) . ")";
                    if (!mysqli_query($conn, $update_back_image)) {
                        throw new Exception("Failed to update back image for all copies.");
                    }
                } else {
                    throw new Exception("Failed to upload back image.");
                }
            }
        }

        // Commit the transaction if supported
        if ($transactionSupported) {
            mysqli_commit($conn);
        }
        
        // Store book title and count in session for display on book_list.php
        $_SESSION['success_message'] = "Book added successfully!";
        $_SESSION['added_book_title'] = $book_title;
        $_SESSION['added_book_copies'] = $successful_inserts;
        
        // Add entry to the updates table
        $admin_id = $_SESSION['admin_employee_id'];
        $admin_firstname = $_SESSION['admin_firstname'];
        $admin_lastname = $_SESSION['admin_lastname'];
        $update_message = "Admin $admin_firstname $admin_lastname added \"$book_title\" with $successful_inserts copies";
        $update_title = "Admin Added New Book";
        $update_query = "INSERT INTO updates (user_id, role, title, message, update) 
                         VALUES ('$admin_id', 'Admin', '$update_title', '$update_message', NOW())";
        mysqli_query($conn, $update_query);
        
        // Change redirect to book_list.php
        header("Location: ../book_list.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback the transaction if supported
        if ($transactionSupported) {
            mysqli_rollback($conn);
        }
        
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        // Don't redirect, let the form handle the error display
        return;
    }
}

// Helper function to calculate accession number with increment
function calculateAccession($baseAccession, $increment) {
    if (!$baseAccession) return '';

    // Handle formats like "2023-0001" or "2023-001" or just "0001"
    $match = preg_match('/^(.*?)(\d+)$/', $baseAccession, $matches);
    if (!$match) return $baseAccession;
    
    $prefix = $matches[1]; // Everything before the number
    $num = intval($matches[2]); // The number part
    $width = strlen($matches[2]); // Original width of the number
    
    // Calculate new number and pad with zeros to maintain original width
    $newNum = ($num + $increment);
    $newNumStr = str_pad($newNum, $width, '0', STR_PAD_LEFT);
    
    return $prefix . $newNumStr;
}
?>
