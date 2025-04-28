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

    // Additional validation for author to ensure it contains at least one valid ID
    if (isset($_POST['author']) && is_array($_POST['author'])) {
        $has_valid_author = false;
        foreach ($_POST['author'] as $author_id) {
            if (!empty($author_id) && intval($author_id) > 0) {
                $has_valid_author = true;
                break;
            }
        }

        if (!$has_valid_author) {
            $errors[] = 'At least one valid author must be selected';
        }
    }

    if (!empty($errors)) {
        $_SESSION['error_message'] = 'Please fix the following errors: ' . implode(', ', $errors);
        header("Location: ../Admin/add-book.php");
        exit();
    }

    // Variables to track for update logging
    $total_copies_added = 0;
    $book_titles = [];

    // Initialize counter for successful insertions and store book title
    $successful_inserts = 0;
    $book_title = mysqli_real_escape_string($conn, $_POST['title']);

    // --- Add: Track all inserted accession numbers for image and supplementary content update ---
    $inserted_accessions = [];
    $supplementary_contents_for_update = '';

    // Process the form if validation passes
    try {
        // Start a transaction if supported
        if ($transactionSupported) {
            mysqli_begin_transaction($conn);
        }

        // Process each accession group
        if (isset($_POST['accession']) && is_array($_POST['accession'])) {
            $current_index = 0; // Track overall index across all groups

            // Extract subject fields from form submission
            $subject_category = mysqli_real_escape_string($conn, $_POST['subject_category'] ?? '');
            $subject_detail = mysqli_real_escape_string($conn, $_POST['subject_detail'] ?? '');

            foreach ($_POST['accession'] as $i => $base_accession) {
                $copies = intval($_POST['number_of_copies'][$i] ?? 1);
                $isbn = mysqli_real_escape_string($conn, $_POST['isbn'][$i] ?? '');
                $series = mysqli_real_escape_string($conn, $_POST['series'][$i] ?? '');
                $volume = mysqli_real_escape_string($conn, $_POST['volume'][$i] ?? '');
                $part = mysqli_real_escape_string($conn, $_POST['part'][$i] ?? ''); // Add the part field
                $edition = mysqli_real_escape_string($conn, $_POST['edition'][$i] ?? '');

                // Track the total copies being added
                $total_copies_added += $copies;

                // Get shelf locations and call numbers
                $call_numbers = $_POST['call_number'] ?? [];
                $shelf_locations = $_POST['shelf_locations'] ?? [];
                $copy_numbers = $_POST['copy_number'] ?? [];

                // Process copies for this accession group
                for ($copy = 0; $copy < $copies; $copy++) {
                    // Handle accession number appropriately
                    $accession_str = calculateAccession($base_accession, $copy);
                    $accession = mysqli_real_escape_string($conn, $accession_str);

                    // Make sure we have a valid accession string
                    if (empty($accession)) {
                        throw new Exception("Invalid accession number: " . $accession_str);
                    }

                    $title = mysqli_real_escape_string($conn, $_POST['title']);
                    // Add title to our tracking array if not already there
                    if (!in_array($title, $book_titles)) {
                        $book_titles[] = $title;
                    }

                    $preferred_title = mysqli_real_escape_string($conn, $_POST['preferred_title'] ?? '');
                    $parallel_title = mysqli_real_escape_string($conn, $_POST['parallel_title'] ?? '');

                    // Fields that match database structure
                    $summary = mysqli_real_escape_string($conn, $_POST['abstract'] ?? '');
                    $contents = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
                    $dimension = mysqli_real_escape_string($conn, $_POST['dimension'] ?? '');

                    // --- CHANGED: Use the exact copy number from the Copy Number fields ---
                    // Get copy number from input field if it exists and is valid, else use default
                    $copy_number = isset($copy_numbers[$current_index]) && !empty($copy_numbers[$current_index])
                        ? intval($copy_numbers[$current_index])
                        : ($current_index + 1); // Fallback to sequential numbering

                    // Ensure copy number is at least 1
                    $copy_number = max(1, $copy_number);

                    // Format total_pages by combining prefix pages and main pages
                    $prefix_pages = isset($_POST['prefix_pages']) ? trim($_POST['prefix_pages']) : '';
                    $main_pages = isset($_POST['main_pages']) ? trim($_POST['main_pages']) : '';
                    $total_pages = trim($prefix_pages . " " . $main_pages); // Combine and trim spaces

                    // Remove " pages" or "pages" from the end if present
                    $total_pages = preg_replace('/ ?pages$/i', '', $total_pages);

                    // Format supplementary_contents based on selected items
                    $supplementary_contents = '';
                    if (isset($_POST['supplementary_content']) && is_array($_POST['supplementary_content']) && count($_POST['supplementary_content']) > 0) {
                        $items = array_map(function($item) use ($conn) {
                            return mysqli_real_escape_string($conn, $item);
                        }, $_POST['supplementary_content']);

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
                    // Save for later update
                    $supplementary_contents_for_update = $supplementary_contents;

                    $content_type = mysqli_real_escape_string($conn, $_POST['content_type'] ?? 'text');
                    $media_type = mysqli_real_escape_string($conn, $_POST['media_type'] ?? 'unmediated');
                    $carrier_type = mysqli_real_escape_string($conn, $_POST['carrier_type'] ?? 'volume');
                    $language = mysqli_real_escape_string($conn, $_POST['language'] ?? 'eng');
                    $url = mysqli_real_escape_string($conn, $_POST['url'] ?? '');

                    // Get call number and use formatted version if available
                    $call_number = isset($call_numbers[$current_index]) ?
                        mysqli_real_escape_string($conn, $call_numbers[$current_index]) : '';

                    // Check if we have a formatted call number from the data attribute
                    if (isset($_POST['formatted_call_numbers']) && isset($_POST['formatted_call_numbers'][$current_index])) {
                        $formatted_call_number = mysqli_real_escape_string($conn, $_POST['formatted_call_numbers'][$current_index]);
                        if (!empty($formatted_call_number)) {
                            $call_number = $formatted_call_number;
                        }
                    }

                    // Get shelf location for this copy
                    $shelf_location = isset($shelf_locations[$current_index]) ?
                        mysqli_real_escape_string($conn, $shelf_locations[$current_index]) : 'CIR';

                    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Available');
                    
                    // CORRECTED: Use admin_employee_id instead of employee_id
                    $entered_by = intval($_SESSION['admin_employee_id']);
                    $date_added = date('Y-m-d H:i:s'); 
                    $last_update = date('Y-m-d H:i:s'); 

                    // Insert into books table
                    $insert_book_query = "INSERT INTO books (
                        accession, title, preferred_title, parallel_title,
                        summary, contents, dimension, series, volume, part, edition, copy_number,
                        total_pages, supplementary_contents, ISBN, content_type, media_type,
                        carrier_type, call_number, URL, language, shelf_location,
                        entered_by, date_added, status, last_update, subject_category, subject_detail
                    ) VALUES (
                        '$accession', '$title', '$preferred_title', '$parallel_title',
                        '$summary', '$contents', '$dimension', '$series', '$volume', '$part', '$edition', '$copy_number',
                        '$total_pages', '$supplementary_contents', '$isbn', '$content_type', '$media_type',
                        '$carrier_type', '$call_number', '$url', '$language', '$shelf_location',
                        '$entered_by', '$date_added', '$status', '$last_update', '$subject_category', '$subject_detail'
                    )";

                    if (!mysqli_query($conn, $insert_book_query)) {
                        throw new Exception("Error inserting book: " . mysqli_error($conn));
                    }

                    $successful_inserts++; // Increment the counter for successful insertions

                    $book_id = mysqli_insert_id($conn);

                    // --- Add: Track accession string for image and supplementary content update ---
                    $inserted_accessions[] = $accession_str;

                    // 2. Insert contributors (authors)
                    if (isset($_POST['author']) && is_array($_POST['author'])) {
                        $author_added = false;
                        foreach ($_POST['author'] as $author_id) {
                            $author_id = intval($author_id);
                            if ($author_id > 0) {
                                $insert_contributor_query = "INSERT INTO contributors (book_id, writer_id, role)
                                    VALUES ('$book_id', '$author_id', 'Author')";

                                if (!mysqli_query($conn, $insert_contributor_query)) {
                                    throw new Exception("Error inserting author: " . mysqli_error($conn));
                                }
                                $author_added = true;
                            }
                        }

                        if (!$author_added) {
                            throw new Exception("No valid author was added to the book.");
                        }
                    } else {
                        throw new Exception("Author information is missing.");
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
                    $publisher_name = '';
                    $publish_date = '';

                    // Check if publisher is a numeric ID or a text name
                    if (isset($_POST['publisher']) && !empty($_POST['publisher'])) {
                        if (is_array($_POST['publisher'])) {
                            // Handle array of publishers (one per accession group)
                            $group_index = min($i, count($_POST['publisher']) - 1); // Use correct index with bounds check
                            $publisher_value = $_POST['publisher'][$group_index];
                        } else {
                            $publisher_value = $_POST['publisher'];
                        }

                        // Clean the publisher value
                        $publisher_value = mysqli_real_escape_string($conn, $publisher_value);

                        // Check if the publisher value is numeric (ID) or text (name)
                        if (is_numeric($publisher_value)) {
                            // It's an ID, use it directly
                            $publisher_id = intval($publisher_value);

                            // Get the publisher name for logging
                            $publisher_name_query = "SELECT publisher FROM publishers WHERE id = '$publisher_id'";
                            $publisher_name_result = mysqli_query($conn, $publisher_name_query);
                            if ($publisher_name_result && mysqli_num_rows($publisher_name_result) > 0) {
                                $publisher_row = mysqli_fetch_assoc($publisher_name_result);
                                $publisher_name = $publisher_row['publisher'];
                            } else {
                                $publisher_name = "Unknown (ID: $publisher_id)";
                                // Publisher ID not found, which is strange - log it
                                error_log("Warning: Publisher with ID $publisher_id not found in database");
                            }
                        } else {
                            // It's a name, look it up
                            $publisher_name = mysqli_real_escape_string($conn, $publisher_value);
                            $publisher_query = "SELECT id FROM publishers WHERE publisher = ?";
                            $stmt = mysqli_prepare($conn, $publisher_query);
                            mysqli_stmt_bind_param($stmt, "s", $publisher_name);
                            mysqli_stmt_execute($stmt);
                            $publisher_result = mysqli_stmt_get_result($stmt);

                            if ($publisher_result && mysqli_num_rows($publisher_result) > 0) {
                                $publisher_row = mysqli_fetch_assoc($publisher_result);
                                $publisher_id = $publisher_row['id'];
                            } else {
                                // Publisher not found, log a warning
                                error_log("Warning: Publisher '$publisher_name' not found in database");

                                // Option: Insert the publisher if it doesn't exist
                                $insert_publisher = "INSERT INTO publishers (publisher, place) VALUES (?, 'Unknown')";
                                $stmt = mysqli_prepare($conn, $insert_publisher);
                                mysqli_stmt_bind_param($stmt, "s", $publisher_name);

                                if (mysqli_stmt_execute($stmt)) {
                                    $publisher_id = mysqli_insert_id($conn);
                                    error_log("Created new publisher with ID: $publisher_id");
                                }
                            }
                        }
                    }

                    // Handle publish date
                    if (isset($_POST['publish_date'])) {
                        if (is_array($_POST['publish_date'])) {
                            $group_index = min($i, count($_POST['publish_date']) - 1);
                            $publish_date = mysqli_real_escape_string($conn, $_POST['publish_date'][$group_index]);
                        } else {
                            $publish_date = mysqli_real_escape_string($conn, $_POST['publish_date']);
                        }
                    }

                    // Debug logging to help track issues
                    error_log("Adding publication: Book ID=$book_id, Publisher ID=$publisher_id, Publisher Name=$publisher_name, Publish Date=$publish_date");

                    // Insert publication info - attempt insertion even with publisher_id = 0
                    if (empty($publish_date)) {
                        $insert_publication_query = "INSERT INTO publications (book_id, publisher_id)
                            VALUES ('$book_id', '$publisher_id')";
                    } else {
                        $insert_publication_query = "INSERT INTO publications (book_id, publisher_id, publish_date)
                            VALUES ('$book_id', '$publisher_id', '$publish_date')";
                    }

                    try {
                        if (!mysqli_query($conn, $insert_publication_query)) {
                            $error = mysqli_error($conn);
                            error_log("Error inserting publication info for book ID $book_id: $error");

                            // Check if this is a foreign key constraint error
                            if (strpos($error, 'foreign key constraint') !== false) {
                                // Try to add without publisher_id
                                $fallback_query = "INSERT INTO publications (book_id, publish_date) VALUES ('$book_id', '$publish_date')";
                                if (!mysqli_query($conn, $fallback_query)) {
                                    error_log("Failed fallback publication insertion: " . mysqli_error($conn));
                                } else {
                                    error_log("Successful fallback publication insertion without publisher ID");
                                }
                            }
                        } else {
                            error_log("Successfully inserted publication record for book ID $book_id");
                        }
                    } catch (Exception $e) {
                        error_log("Exception in publication insertion: " . $e->getMessage());
                    }

                    // 6. Process subject entries - insert for all copies
                    if (isset($_POST['subject_categories']) && is_array($_POST['subject_categories'])) {
                        foreach ($_POST['subject_categories'] as $i => $category) {
                            if (!empty($category)) {
                                $category = mysqli_real_escape_string($conn, $category);
                                $program = mysqli_real_escape_string($conn, $_POST['program'][$i] ?? '');
                                $detail = mysqli_real_escape_string($conn, $_POST['subject_paragraphs'][$i] ?? '');

                                $update_subject_query = "UPDATE books SET
                                    subject_category = CASE
                                        WHEN subject_category IS NULL OR subject_category = ''
                                        THEN '$category'
                                        ELSE CONCAT(subject_category, '; ', '$category')
                                    END,
                                    program = CASE
                                        WHEN program IS NULL OR program = ''
                                        THEN '$program'
                                        ELSE CONCAT(program, '; ', '$program')
                                    END,
                                    subject_detail = CASE
                                        WHEN subject_detail IS NULL OR subject_detail = ''
                                        THEN '$detail'
                                        ELSE CONCAT(subject_detail, '; ', '$detail')
                                    END,
                                    updated_by = '$entered_by',
                                    last_update = NOW()
                                    WHERE id = '$book_id'";

                                if (!mysqli_query($conn, $update_subject_query)) {
                                    throw new Exception("Error updating subject: " . mysqli_error($conn));
                                }
                            }
                        }
                    }

                    $current_index++; // <-- increment for each copy processed
                }
            }
        }

        // ---   Move image and supplementary_contents update logic here, after all books are inserted ---
        if (!empty($inserted_accessions)) {
            $accession_list = array_map(function($a) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, $a) . "'";
            }, $inserted_accessions);
            $accession_in = implode(',', $accession_list);

            // Handle front image
            if (isset($_FILES['front_image']) && $_FILES['front_image']['size'] > 0) {
                $target_dir = "../Images/book-image/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $first_book_id_query = "SELECT id FROM books WHERE accession IN ($accession_in) ORDER BY id ASC LIMIT 1";
                $first_book_id_result = mysqli_query($conn, $first_book_id_query);
                $first_book_id = null;
                if ($first_book_id_result && mysqli_num_rows($first_book_id_result) > 0) {
                    $row = mysqli_fetch_assoc($first_book_id_result);
                    $first_book_id = $row['id'];
                }
                if ($first_book_id) {
                    $front_image_name = $first_book_id . "_front." . pathinfo($_FILES['front_image']['name'], PATHINFO_EXTENSION);
                    $target_file = $target_dir . $front_image_name;
                    if (move_uploaded_file($_FILES['front_image']['tmp_name'], $target_file)) {
                        $front_image_path = "Images/book-image/" . $front_image_name;
                        // Update all inserted accessions with the same front image
                        // CHANGED: Also update the updated_by field using employee_id
                        $employee_id = intval($_SESSION['admin_employee_id']);
                        $update_front_image = "UPDATE books SET 
                            front_image = '$front_image_path', 
                            updated_by = '$employee_id',
                            last_update = NOW() 
                            WHERE accession IN ($accession_in)";
                        mysqli_query($conn, $update_front_image);
                    }
                }
            }

            // Handle back image
            if (isset($_FILES['back_image']) && $_FILES['back_image']['size'] > 0) {
                $target_dir = "../Images/book-image/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $first_book_id_query = "SELECT id FROM books WHERE accession IN ($accession_in) ORDER BY id ASC LIMIT 1";
                $first_book_id_result = mysqli_query($conn, $first_book_id_query);
                $first_book_id = null;
                if ($first_book_id_result && mysqli_num_rows($first_book_id_result) > 0) {
                    $row = mysqli_fetch_assoc($first_book_id_result);
                    $first_book_id = $row['id'];
                }
                if ($first_book_id) {
                    $back_image_name = $first_book_id . "_back." . pathinfo($_FILES['back_image']['name'], PATHINFO_EXTENSION);
                    $target_file = $target_dir . $back_image_name;
                    if (move_uploaded_file($_FILES['back_image']['tmp_name'], $target_file)) {
                        $back_image_path = "Images/book-image/" . $back_image_name;
                        // Update all inserted accessions with the same back image
                        // CHANGED: Also update the updated_by field using employee_id
                        $employee_id = intval($_SESSION['admin_employee_id']);
                        $update_back_image = "UPDATE books SET 
                            back_image = '$back_image_path',
                            updated_by = '$employee_id',
                            last_update = NOW()
                            WHERE accession IN ($accession_in)";
                        mysqli_query($conn, $update_back_image);
                    }
                }
            }

            // Update supplementary_contents for all inserted accessions
            if (!empty($supplementary_contents_for_update)) {
                // CHANGED: Also update the updated_by field using employee_id
                $employee_id = intval($_SESSION['admin_employee_id']);
                $update_supp = "UPDATE books SET 
                    supplementary_contents = '$supplementary_contents_for_update',
                    updated_by = '$employee_id',
                    last_update = NOW()
                    WHERE accession IN ($accession_in)";
                mysqli_query($conn, $update_supp);
            }
        }

        // Commit the transaction if supported
        if ($transactionSupported) {
            mysqli_commit($conn);
        }

        // Insert a single update entry for the book addition
        // CORRECTED: Use admin_employee_id instead of employee_id
        $admin_id = $_SESSION['admin_employee_id'];
        $admin_role = $_SESSION['role'];

        // Get admin's name from the database
        // CORRECTED: Use admin_employee_id to search in the admins table
        $admin_query = "SELECT CONCAT(firstname, ' ', lastname) as fullname FROM admins WHERE employee_id = '$admin_id'";
        $admin_result = mysqli_query($conn, $admin_query);
        $admin_name = "Admin";

        if ($admin_result && mysqli_num_rows($admin_result) > 0) {
            $admin_row = mysqli_fetch_assoc($admin_result);
            $admin_name = $admin_row['fullname'];
        }

        // Create the update message without adding quotes directly in the string
        $book_title_text = count($book_titles) > 1 ? "multiple books" : $book_titles[0];

        // Build the base message first
        $update_title = "$admin_role Added New Book";
        $message_text = "$admin_role $admin_name added ";

        // Add the book title with appropriate formatting but without quotes in the string itself
        if (count($book_titles) > 1) {
            $message_text .= "multiple books";
        } else {
            $message_text .= "\"" . $book_titles[0] . "\""; // Use double quotes instead
        }

        // Complete the message
        $message_text .= " with $total_copies_added copies";

        // Properly escape the entire message for SQL insertion
        $update_title = mysqli_real_escape_string($conn, $update_title);
        $update_message = mysqli_real_escape_string($conn, $message_text);
        $current_timestamp = date('Y-m-d H:i:s');

        // Insert into updates table - using employee_id
        $insert_update_query = "INSERT INTO updates (user_id, role, title, message, `update`)
            VALUES ('$admin_id', '$admin_role', '$update_title', '$update_message', '$current_timestamp')";

        mysqli_query($conn, $insert_update_query);

        $_SESSION['success_message'] = "Book(s) added successfully! Title: " . implode(', ', $book_titles) . " | Total Copies: $total_copies_added";

        // Store book title and count in session for display on book_list.php
        $_SESSION['added_book_title'] = $book_title;
        $_SESSION['added_book_copies'] = $successful_inserts;

        // Set a session flag to trigger form reset on the next page load
        $_SESSION['reset_book_form'] = true;

        // Change redirect to book_list.php
        header("Location: ../Admin/book_list.php");
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
    $width = strlen($matches[2]); // Original width of the number to preserve leading zeros

    // Calculate new number and ensure it maintains the same width with leading zeros
    $newNum = str_pad($num + $increment, $width, '0', STR_PAD_LEFT);

    return $prefix . $newNum;
}
?>
