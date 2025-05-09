<?php
session_start();
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = intval($_POST['book_id']);
    $corporate_id = intval($_POST['corporate_id']);
    $role = trim($conn->real_escape_string($_POST['role']));

    // Validate required fields
    if (empty($book_id) || empty($corporate_id) || empty($role)) {
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: corporate_contributors.php");
        exit();
    }

    // Check if the book exists
    $checkBookQuery = "SELECT id FROM books WHERE id = ?";
    $stmt = $conn->prepare($checkBookQuery);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $bookResult = $stmt->get_result();
    if ($bookResult->num_rows === 0) {
        $_SESSION['error_message'] = "The selected book does not exist.";
        header("Location: corporate_contributors.php");
        exit();
    }

    // Check if the corporate exists
    $checkCorporateQuery = "SELECT id FROM corporates WHERE id = ?";
    $stmt = $conn->prepare($checkCorporateQuery);
    $stmt->bind_param("i", $corporate_id);
    $stmt->execute();
    $corporateResult = $stmt->get_result();
    if ($corporateResult->num_rows === 0) {
        $_SESSION['error_message'] = "The selected corporate does not exist.";
        header("Location: corporate_contributors.php");
        exit();
    }

    // Check if this corporate contributor already exists
    $checkDuplicateQuery = "SELECT id FROM corporate_contributors WHERE book_id = ? AND corporate_id = ? AND role = ?";
    $stmt = $conn->prepare($checkDuplicateQuery);
    $stmt->bind_param("iis", $book_id, $corporate_id, $role);
    $stmt->execute();
    $duplicateResult = $stmt->get_result();
    if ($duplicateResult->num_rows > 0) {
        $_SESSION['error_message'] = "This corporate contributor already exists for the selected book.";
        header("Location: corporate_contributors.php");
        exit();
    }

    // Insert the corporate contributor
    $insertQuery = "INSERT INTO corporate_contributors (book_id, corporate_id, role) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iis", $book_id, $corporate_id, $role);
    
    if ($stmt->execute()) {
        // Get corporate and book names for the success message
        $getBooksQuery = "SELECT title FROM books WHERE id = ?";
        $stmt = $conn->prepare($getBooksQuery);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $bookName = $stmt->get_result()->fetch_assoc()['title'];
        
        $getCorporateQuery = "SELECT name FROM corporates WHERE id = ?";
        $stmt = $conn->prepare($getCorporateQuery);
        $stmt->bind_param("i", $corporate_id);
        $stmt->execute();
        $corporateName = $stmt->get_result()->fetch_assoc()['name'];
        
        $_SESSION['success_message'] = "Corporate contributor \"$corporateName\" with role \"$role\" added successfully to \"$bookName\".";
    } else {
        $_SESSION['error_message'] = "Failed to add corporate contributor: " . $conn->error;
    }

    header("Location: corporate_contributors.php");
    exit();
} else {
    header("Location: corporate_contributors.php");
    exit();
}
?>
