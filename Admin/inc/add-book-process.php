<?php
session_start(); // Start session
include '../../db.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $accession = $_POST['accession']; 
    $title = $_POST['title'];
    $preferred_title = $_POST['preferred_title'] ?? null;
    $parallel_title = $_POST['parallel_title'] ?? null;
    
    // Handle optional fields
    $series = !empty($_POST['series']) ? $_POST['series'] : null;
    $volume = !empty($_POST['volume']) ? $_POST['volume'] : null;
    $edition = !empty($_POST['edition']) ? $_POST['edition'] : null;
    $URL = !empty($_POST['URL']) ? $_POST['URL'] : null;
    
    $height = $_POST['height'];
    $width = $_POST['width'];
    $total_pages = $_POST['total_pages'];
    $ISBN = $_POST['ISBN'];
    $content_type = $_POST['content_type'];
    $media_type = $_POST['media_type'];
    $carrier_type = $_POST['carrier_type'];
    $language = $_POST['language'];
    $shelf_location = $_POST['shelf_location'];
    $entered_by = $_POST['entered_by'];
    $date_added = $_POST['date_added'];
    $status = $_POST['status'];
    $last_update = $_POST['last_update'];
    $copies = $_POST['copies']; // Number of copies to add

    // Fetch the latest ID from the database if the given ID is not provided
    if (empty($id)) {
        $result = $conn->query("SELECT MAX(id) AS max_id FROM books");
        $row = $result->fetch_assoc();
        $id = $row['max_id'] ? $row['max_id'] + 1 : 1; // If no books exist, start from 1
    }

    // Handle file uploads safely
    $target_dir = "../uploads/books/";
    
    $front_image = !empty($_FILES['front_image']['name']) ? $_FILES['front_image']['name'] : null;
    $back_image = !empty($_FILES['back_image']['name']) ? $_FILES['back_image']['name'] : null;

    if ($front_image) {
        move_uploaded_file($_FILES['front_image']['tmp_name'], $target_dir . $front_image);
    }

    if ($back_image) {
        move_uploaded_file($_FILES['back_image']['tmp_name'], $target_dir . $back_image);
    }

    // Prepare SQL query
    $sql = "INSERT INTO books (accession, title, preferred_title, parallel_title, front_image, back_image, height, width, series, volume, edition, copy_number, total_pages, ISBN, content_type, media_type, carrier_type, URL, language, shelf_location, entered_by, date_added, status, last_update) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        for ($i = 0; $i < $copies; $i++) {
            $current_accession = $accession + $i; // Increment ID for each copy
            $copy_number = $i + 1; // Copy number starts from 1

            $stmt->bind_param("ssssssddsisssissssssssss", 
                $current_accession, $title, $preferred_title, $parallel_title, 
                $front_image, $back_image, 
                $height, $width, 
                $series, $volume, $edition, $copy_number, 
                $total_pages, $ISBN, 
                $content_type, $media_type, $carrier_type, 
                $URL, $language, $shelf_location, 
                $entered_by, $date_added, $status, $last_update
            );

            if (!$stmt->execute()) {
                echo "Error inserting book copy $copy_number: " . $stmt->error;
                exit;
            }
        }

        echo "Books added successfully.";
        header("Location: ../add-book.php");
    } else {
        echo "Error in preparing the statement.";
    }

    $stmt->close();
    $conn->close();
}
?>
