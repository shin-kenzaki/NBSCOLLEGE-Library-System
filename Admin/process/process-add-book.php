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
        
        // Process each accession group
        if (isset($_POST['accession']) && is_array($_POST['accession'])) {
            $author_id = intval($_POST['author'][0]);
            
            for ($i = 0; $i < count($_POST['accession']); $i++) {
                $base_accession = $_POST['accession'][$i];
                $copies = intval($_POST['number_of_copies'][$i] ?? 1);
                $isbn = mysqli_real_escape_string($conn, $_POST['isbn'][$i] ?? '');
                $series = mysqli_real_escape_string($conn, $_POST['series'][$i] ?? '');
                $volume = mysqli_real_escape_string($conn, $_POST['volume'][$i] ?? '');
                $edition = mysqli_real_escape_string($conn, $_POST['edition'][$i] ?? '');
                
                // Get shelf locations and call numbers
                $call_numbers = $_POST['call_number'] ?? [];
                $shelf_locations = $_POST['shelf_locations'] ?? [];
                $copy_numbers = $_POST['copy_number'] ?? [];

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
                        // Extract only the numeric part if the accession contains non-numeric characters
                        preg_match('/(\d+)/', $accession_str, $matches);
                        $accession = isset($matches[0]) ? intval($matches[0]) : 0;
                    }
                    
                    // Make sure we have a valid accession number
                    if ($accession <= 0) {
                        throw new Exception("Invalid accession number: " . $accession_str);
                    }
                    
                    $title = mysqli_real_escape_string($conn, $_POST['title']);
                    $preferred_title = mysqli_real_escape_string($conn, $_POST['preferred_title'] ?? '');
                    $parallel_title = mysqli_real_escape_string($conn, $_POST['parallel_title'] ?? '');
                    
                    // Fields that match database structure
                    $summary = mysqli_real_escape_string($conn, $_POST['abstract'] ?? '');
                    $contents = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
                    $dimension = mysqli_real_escape_string($conn, $_POST['dimension'] ?? '');
                    
                    // Get copy number from input field if exists, else use incremental counter
                    // We're now using the explicitly provided copy numbers
                    $copy_number = isset($copy_numbers[$current_index]) ? intval($copy_numbers[$current_index]) : ($copy + 1);
                    $total_pages = mysqli_real_escape_string($conn, $_POST['main_pages'] ?? '');
                    $supplementary_contents = isset($_POST['supplementary_content']) ? 
                        mysqli_real_escape_string($conn, implode(', ', $_POST['supplementary_content'])) : '';
                    
                    $content_type = mysqli_real_escape_string($conn, $_POST['content_type'] ?? 'text');
                    $media_type = mysqli_real_escape_string($conn, $_POST['media_type'] ?? 'unmediated');
                    $carrier_type = mysqli_real_escape_string($conn, $_POST['carrier_type'] ?? 'volume');
                    $language = mysqli_real_escape_string($conn, $_POST['language'] ?? 'eng');
                    $url = mysqli_real_escape_string($conn, $_POST['url'] ?? '');
                    
                    // Get call number and shelf location for this copy
                    $call_number = isset($call_numbers[$current_index]) ? 
                        mysqli_real_escape_string($conn, $call_numbers[$current_index]) : '';
                    $shelf_location = isset($shelf_locations[$current_index]) ? 
                        mysqli_real_escape_string($conn, $shelf_locations[$current_index]) : 'CIR';
                    
                    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Available');
                    $entered_by = intval($_SESSION['admin_id']);
                    $date_added = date('Y-m-d');
                    $last_update = date('Y-m-d');
                    
                    // Insert into books table
                    $insert_book_query = "INSERT INTO books (
                        accession, title, preferred_title, parallel_title, 
                        summary, contents, dimension, series, volume, edition, copy_number,
                        total_pages, supplementary_contents, ISBN, content_type, media_type, 
                        carrier_type, call_number, URL, language, shelf_location, 
                        entered_by, date_added, status, last_update
                    ) VALUES (
                        '$accession', '$title', '$preferred_title', '$parallel_title', 
                        '$summary', '$contents', '$dimension', '$series', '$volume', '$edition', '$copy_number',
                        '$total_pages', '$supplementary_contents', '$isbn', '$content_type', '$media_type', 
                        '$carrier_type', '$call_number', '$url', '$language', '$shelf_location', 
                        '$entered_by', '$date_added', '$status', '$last_update'
                    )";
                    
                    if (!mysqli_query($conn, $insert_book_query)) {
                        throw new Exception("Error inserting book: " . mysqli_error($conn));
                    }
                    
                    $book_id = mysqli_insert_id($conn);
                    
                    // 2. Insert contributors (authors)
                    $insert_contributor_query = "INSERT INTO contributors (book_id, writer_id, role) 
                        VALUES ('$book_id', '$author_id', 'Author')";
                    
                    if (!mysqli_query($conn, $insert_contributor_query)) {
                        throw new Exception("Error inserting author: " . mysqli_error($conn));
                    }
                    
                    // 3. Insert co-authors if any
                    if (isset($_POST['co_authors']) && is_array($_POST['co_authors'])) {
                        foreach ($_POST['co_authors'] as $co_author) {
                            $co_author_id = intval($co_author);
                            $insert_co_author_query = "INSERT INTO contributors (book_id, writer_id, role) 
                                VALUES ('$book_id', '$co_author_id', 'Co-Author')";
                            
                            if (!mysqli_query($conn, $insert_co_author_query)) {
                                throw new Exception("Error inserting co-author: " . mysqli_error($conn));
                            }
                        }
                    }
                    
                    // 4. Insert editors if any
                    if (isset($_POST['editors']) && is_array($_POST['editors'])) {
                        foreach ($_POST['editors'] as $editor) {
                            $editor_id = intval($editor);
                            $insert_editor_query = "INSERT INTO contributors (book_id, writer_id, role) 
                                VALUES ('$book_id', '$editor_id', 'Editor')";
                            
                            if (!mysqli_query($conn, $insert_editor_query)) {
                                throw new Exception("Error inserting editor: " . mysqli_error($conn));
                            }
                        }
                    }
                    
                    // 5. Insert publication information
                    $publisher_id = 0;
                    $publisher_name = mysqli_real_escape_string($conn, $_POST['publisher']);
                    $publish_date = mysqli_real_escape_string($conn, $_POST['publish_date']);
                    
                    // Find publisher ID based on name
                    $publisher_query = "SELECT id FROM publishers WHERE publisher = '$publisher_name'";
                    $publisher_result = mysqli_query($conn, $publisher_query);
                    
                    if ($publisher_result && mysqli_num_rows($publisher_result) > 0) {
                        $publisher_row = mysqli_fetch_assoc($publisher_result);
                        $publisher_id = $publisher_row['id'];
                    }
                    
                    if ($publisher_id > 0) {
                        $insert_publication_query = "INSERT INTO publications (book_id, publisher_id, publish_date) 
                            VALUES ('$book_id', '$publisher_id', '$publish_date')";
                        
                        if (!mysqli_query($conn, $insert_publication_query)) {
                            throw new Exception("Error inserting publication info: " . mysqli_error($conn));
                        }
                    }
                    
                    // 6. Process subject entries (only for the first copy if we have subject data)
                    if ($copy === 0 && isset($_POST['subject_categories']) && is_array($_POST['subject_categories'])) {
                        // First copy - update the subject information directly on the book record
                        if (!empty($_POST['subject_categories'][0])) {
                            $category = mysqli_real_escape_string($conn, $_POST['subject_categories'][0]);
                            $detail = mysqli_real_escape_string($conn, $_POST['subject_paragraphs'][0] ?? '');
                            
                            $update_subject_query = "UPDATE books SET subject_category = '$category', subject_detail = '$detail'
                                WHERE id = '$book_id'";
                                
                            if (!mysqli_query($conn, $update_subject_query)) {
                                throw new Exception("Error updating subject: " . mysqli_error($conn));
                            }
                        }
                    }
                    
                    // 7. Handle file uploads for book images (only for the first copy)
                    if ($copy === 0) {
                        if (isset($_FILES['front_image']) && $_FILES['front_image']['size'] > 0) {
                            $target_dir = "../uploads/book_covers/";
                            if (!is_dir($target_dir)) {
                                mkdir($target_dir, 0777, true);
                            }
                            
                            $front_image_name = $book_id . "_front." . pathinfo($_FILES['front_image']['name'], PATHINFO_EXTENSION);
                            $target_file = $target_dir . $front_image_name;
                            
                            if (move_uploaded_file($_FILES['front_image']['tmp_name'], $target_file)) {
                                $update_front_image = "UPDATE books SET front_image = '$front_image_name' WHERE id = '$book_id'";
                                mysqli_query($conn, $update_front_image);
                            }
                        }
                        
                        if (isset($_FILES['back_image']) && $_FILES['back_image']['size'] > 0) {
                            $target_dir = "../uploads/book_covers/";
                            if (!is_dir($target_dir)) {
                                mkdir($target_dir, 0777, true);
                            }
                            
                            $back_image_name = $book_id . "_back." . pathinfo($_FILES['back_image']['name'], PATHINFO_EXTENSION);
                            $target_file = $target_dir . $back_image_name;
                            
                            if (move_uploaded_file($_FILES['back_image']['tmp_name'], $target_file)) {
                                $update_back_image = "UPDATE books SET back_image = '$back_image_name' WHERE id = '$book_id'";
                                mysqli_query($conn, $update_back_image);
                            }
                        }
                    }
                    
                    $current_index++;
                }
            }
        }
        
        // Commit the transaction if supported
        if ($transactionSupported) {
            mysqli_commit($conn);
        }
        
        $_SESSION['success_message'] = "Book(s) added successfully!";
        
        // Set a session flag to trigger form reset on the next page load
        $_SESSION['reset_book_form'] = true;
        
        // Change redirect to book_list.php
        header("Location: ../admin/book_list.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback the transaction if supported
        if ($transactionSupported) {
            mysqli_rollback($conn);
        }
        
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
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
