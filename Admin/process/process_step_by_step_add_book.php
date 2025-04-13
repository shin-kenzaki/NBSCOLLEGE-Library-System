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
    // Validate required fields
    $required_fields = [
        'title', 
        'publisher', 
        'publish_date', 
        'author', 
        'accession'
    ];
    
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['error_message'] = 'Please fix the following errors: ' . implode(', ', $errors);
        return; // Stop processing if there are validation errors
    }
    
    // Process the form if validation passes
    try {
        // Start a transaction if supported
        if ($transactionSupported) {
            mysqli_begin_transaction($conn);
        }
        
        // Initialize counter for successful insertions
        $successful_inserts = 0;
        $book_title = '';
        
        // Process each accession group
        if (isset($_POST['accession']) && is_array($_POST['accession'])) {
            $author_id = intval($_POST['author'][0]);
            $book_title = mysqli_real_escape_string($conn, $_POST['title']);
            
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
                    // 1. Insert basic book information
                    // Fixed: Handle accession number appropriately
                    $accession_str = calculateAccession($base_accession, $copy);
                    // Extract only numbers from the accession string if it contains non-numeric characters
                    if (is_numeric($accession_str)) {
                        $accession = intval($accession_str);
                    } else {
                        // Handle non-numeric accession
                    }
                    
                    // Make sure we have a valid accession number
                    if ($accession <= 0) {
                        // Handle invalid accession
                    }
                    
                    $title = mysqli_real_escape_string($conn, $_POST['title']);
                    $preferred_title = mysqli_real_escape_string($conn, $_POST['preferred_title'] ?? '');
                    $parallel_title = mysqli_real_escape_string($conn, $_POST['parallel_title'] ?? '');
                    
                    // Fields that match database structure
                    $summary = mysqli_real_escape_string($conn, $_POST['abstract'] ?? '');
                    $contents = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
                    $dimension = mysqli_real_escape_string($conn, $_POST['dimension'] ?? '');
                    
                    // Get copy number from form input, prioritizing user-entered values
                    $copy_number = isset($copy_numbers[$current_index]) ? 
                        intval($copy_numbers[$current_index]) : ($copy + 1);
                    
                    $total_pages = mysqli_real_escape_string($conn, $_POST['main_pages'] ?? '');
                    $supplementary_contents = isset($_POST['supplementary_content']) ? 
                        implode('; ', $_POST['supplementary_content']) : '';
                    
                    $content_type = mysqli_real_escape_string($conn, $_POST['content_type'] ?? 'text');
                    $media_type = mysqli_real_escape_string($conn, $_POST['media_type'] ?? 'unmediated');
                    $carrier_type = mysqli_real_escape_string($conn, $_POST['carrier_type'] ?? 'volume');
                    $language = mysqli_real_escape_string($conn, $_POST['language'] ?? 'eng');
                    $url = mysqli_real_escape_string($conn, $_POST['url'] ?? '');
                    
                    // Get call number and shelf location for this copy
                    $call_number = isset($call_numbers[$current_index]) ? 
                        mysqli_real_escape_string($conn, $call_numbers[$current_index]) : '';
                    $shelf_location = isset($shelf_locations[$current_index]) ? 
                        mysqli_real_escape_string($conn, $shelf_locations[$current_index]) : '';
                    
                    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Available');
                    $entered_by = intval($_SESSION['admin_id']);
                    $date_added = date('Y-m-d');
                    $last_update = date('Y-m-d');
                    
                    // Format the call number with proper spacing and user-defined copy number
                    $formatted_call_number = '';
                    if (!empty($call_number) && !empty($shelf_location)) {
                        $publish_year = $_SESSION['book_shortcut']['publish_year'] ?? '';
                        $volume_text = !empty($volume) ? " vol.$volume" : '';
                        $part_text = !empty($part) ? " pt.$part" : '';
                        
                        $formatted_call_number = trim("$shelf_location $call_number" . 
                                                    (!empty($publish_year) ? " $publish_year" : '') . 
                                                    "$volume_text$part_text c.$copy_number");
                    }
                    
                    // Insert into books table with the properly formatted call number
                    $insert_book_query = "INSERT INTO books (
                        accession, title, preferred_title, parallel_title, 
                        summary, contents, dimension, series, volume, part, edition,
                        copy_number, total_pages, supplementary_contents, ISBN, content_type, 
                        media_type, carrier_type, call_number, URL, 
                        language, shelf_location, entered_by, date_added, 
                        status, last_update
                    ) VALUES (
                        '$accession', '$title', '$preferred_title', '$parallel_title',
                        '$summary', '$contents', '$dimension', '$series', '$volume', '$part', '$edition',
                        '$copy_number', '$total_pages', '$supplementary_contents', '$isbn', '$content_type',
                        '$media_type', '$carrier_type', '$formatted_call_number', '$url',
                        '$language', '$shelf_location', '$entered_by', '$date_added',
                        '$status', '$last_update'
                    )";
                    
                    if (!mysqli_query($conn, $insert_book_query)) {
                        throw new Exception("Error inserting book data: " . mysqli_error($conn));
                    }
                    
                    // Increment successful inserts counter
                    $successful_inserts++;
                    
                    $book_id = mysqli_insert_id($conn);
                    
                    // 2. Insert contributors (authors)
                    $insert_contributor_query = "INSERT INTO contributors (book_id, writer_id, role) 
                        VALUES ('$book_id', '$author_id', 'Author')";
                    
                    if (!mysqli_query($conn, $insert_contributor_query)) {
                        throw new Exception("Error adding author: " . mysqli_error($conn));
                    }
                    
                    // 3. Insert co-authors if any
                    if (isset($_POST['co_authors']) && is_array($_POST['co_authors'])) {
                        foreach ($_POST['co_authors'] as $co_author_id) {
                            $co_author_id = intval($co_author_id);
                            if ($co_author_id > 0) {
                                $insert_co_author_query = "INSERT INTO contributors (book_id, writer_id, role) 
                                    VALUES ('$book_id', '$co_author_id', 'Co-Author')";
                                
                                if (!mysqli_query($conn, $insert_co_author_query)) {
                                    throw new Exception("Error adding co-author: " . mysqli_error($conn));
                                }
                            }
                        }
                    }
                    
                    // 4. Insert editors if any
                    if (isset($_POST['editors']) && is_array($_POST['editors'])) {
                        foreach ($_POST['editors'] as $editor_id) {
                            $editor_id = intval($editor_id);
                            if ($editor_id > 0) {
                                $insert_editor_query = "INSERT INTO contributors (book_id, writer_id, role) 
                                    VALUES ('$book_id', '$editor_id', 'Editor')";
                                
                                if (!mysqli_query($conn, $insert_editor_query)) {
                                    throw new Exception("Error adding editor: " . mysqli_error($conn));
                                }
                            }
                        }
                    }
                    
                    // 5. Insert publication information
                    $publisher_id = $_SESSION['book_shortcut']['publisher_id'] ?? 0;
                    $publish_date = $_SESSION['book_shortcut']['publish_year'] ?? '';
                    
                    if ($publisher_id > 0 && !empty($publish_date)) {
                        $insert_publication_query = "INSERT INTO publications (book_id, publisher_id, publish_date) 
                            VALUES ('$book_id', '$publisher_id', '$publish_date')";
                        
                        if (!mysqli_query($conn, $insert_publication_query)) {
                            throw new Exception("Error adding publication info: " . mysqli_error($conn));
                        }
                    }
                    
                    // Increment the current index to move to the next call_number and shelf_location
                    $current_index++;
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
        
        // Change redirect to book_list.php
        header("Location: ../book_list.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback the transaction if supported
        if ($transactionSupported) {
            mysqli_rollback($conn);
        }
        
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: ../step-by-step-add-book-form.php");
        exit();
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
