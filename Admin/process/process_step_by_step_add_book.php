<?php
session_start();
include '../../db.php';

// Check if user is logged in with appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: ../index.php");
    exit();
}

// Ensure we have book_shortcut session data
if (!isset($_SESSION['book_shortcut'])) {
    $_SESSION['message'] = "Error: Missing book information.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../step-by-step-add-book.php");
    exit();
}

// Check if all required steps are completed
$steps_completed = $_SESSION['book_shortcut']['steps_completed'];
if (!$steps_completed['writer'] || !$steps_completed['corporate'] || !$steps_completed['publisher'] || !$steps_completed['title']) {
    $_SESSION['message'] = "Error: Please complete all required steps before submitting.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../step-by-step-add-book.php");
    exit();
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Get data from session
    $title = $_SESSION['book_shortcut']['book_title'];
    $publisherId = $_SESSION['book_shortcut']['publisher_id'];
    $publishYear = $_SESSION['book_shortcut']['publish_year'];
    $selectedWriters = $_SESSION['book_shortcut']['selected_writers'] ?? [];
    $selectedCorporates = $_SESSION['book_shortcut']['selected_corporates'] ?? [];
    
    // Basic validation
    if (empty($title)) {
        throw new Exception("Book title cannot be empty");
    }
    
    if (empty($publisherId) || empty($publishYear)) {
        throw new Exception("Publisher and publication year must be specified");
    }
    
    // If using individual_only, require at least one writer
    if (isset($_SESSION['book_shortcut']['contributor_type']) && 
        $_SESSION['book_shortcut']['contributor_type'] === 'individual_only' && 
        empty($selectedWriters)) {
        throw new Exception("Please select at least one writer for this book");
    }
    
    // If using corporate_only, require at least one corporate
    if (isset($_SESSION['book_shortcut']['contributor_type']) && 
        $_SESSION['book_shortcut']['contributor_type'] === 'corporate_only' && 
        empty($selectedCorporates)) {
        throw new Exception("Please select at least one corporate contributor for this book");
    }
    
    // Insert the book record
    $bookQuery = "INSERT INTO books (title, date_added, status, entered_by) 
                  VALUES (?, CURDATE(), 'Available', ?)";
    $stmt = $conn->prepare($bookQuery);
    $stmt->bind_param("si", $title, $_SESSION['admin_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Error adding book: " . $conn->error);
    }
    
    $bookId = $conn->insert_id;
    
    // Insert publication information
    $pubQuery = "INSERT INTO publications (book_id, publisher_id, publish_date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($pubQuery);
    $stmt->bind_param("iis", $bookId, $publisherId, $publishYear);
    
    if (!$stmt->execute()) {
        throw new Exception("Error adding publication information: " . $conn->error);
    }
    
    // Insert individual contributors
    if (!empty($selectedWriters)) {
        foreach ($selectedWriters as $writer) {
            $contribQuery = "INSERT INTO contributors (book_id, writer_id, role) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($contribQuery);
            $stmt->bind_param("iis", $bookId, $writer['id'], $writer['role']);
            
            if (!$stmt->execute()) {
                throw new Exception("Error adding writer contributor: " . $conn->error);
            }
        }
    }
    
    // Insert corporate contributors
    if (!empty($selectedCorporates)) {
        foreach ($selectedCorporates as $corporate) {
            $corpContribQuery = "INSERT INTO corporate_contributors (book_id, corporate_id, role) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($corpContribQuery);
            $stmt->bind_param("iis", $bookId, $corporate['id'], $corporate['role']);
            
            if (!$stmt->execute()) {
                throw new Exception("Error adding corporate contributor: " . $conn->error);
            }
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Set success flag
    $_SESSION['book_shortcut_success'] = true;
    $_SESSION['success_message'] = "Book \"$title\" added successfully!";
    
    // Clear the book shortcut session
    unset($_SESSION['book_shortcut']);
    
    // Redirect back to step-by-step page
    header("Location: ../step-by-step-add-book.php");
    exit();
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    
    // Redirect back to step-by-step page
    header("Location: ../step-by-step-add-book.php");
    exit();
}
?>
