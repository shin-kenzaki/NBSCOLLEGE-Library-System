<?php
session_start();
include('../db.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $book_ids = isset($_POST['book_id']) ? $_POST['book_id'] : [];
    
    // Validate inputs
    if ($user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
        exit();
    }
    
    if (empty($book_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'No books selected']);
        exit();
    }
    
    // Check user type - if student, apply the 3-book limit
    $user_query = "SELECT usertype, borrowed_books FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit();
    }
    
    // If the user is a student, check the book limit
    if ($user['usertype'] == 'Student') {
        // Get current active borrowings and reservations count
        $count_query = "SELECT 
                        (SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'Active') +
                        (SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status IN ('Pending', 'Ready')) as total_books";
        $stmt = $conn->prepare($count_query);
        $stmt->bind_param('ii', $user_id, $user_id);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $total = $count_result->fetch_assoc();
        
        // Calculate if adding these books would exceed the limit
        $new_total = $total['total_books'] + count($book_ids);
        
        if ($new_total > 3) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Students can only borrow a maximum of 3 books. This student already has ' . 
                             $total['total_books'] . ' books borrowed or reserved.'
            ]);
            exit();
        }
        
        // Check if the student has any overdue books
        $overdue_query = "SELECT COUNT(*) as overdue_count FROM borrowings 
                          WHERE user_id = ? AND status = 'Overdue'";
        $stmt = $conn->prepare($overdue_query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $overdue_result = $stmt->get_result();
        $overdue = $overdue_result->fetch_assoc();
        
        if ($overdue['overdue_count'] > 0) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'This student has ' . $overdue['overdue_count'] . ' overdue book(s). ' .
                             'All overdue books must be returned before borrowing additional books.'
            ]);
            exit();
        }
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // For each book
        foreach ($book_ids as $book_id) {
            $book_id = intval($book_id);
            
            // Check if book is available
            $book_query = "SELECT status, shelf_location FROM books WHERE id = ?";
            $stmt = $conn->prepare($book_query);
            $stmt->bind_param('i', $book_id);
            $stmt->execute();
            $book_result = $stmt->get_result();
            $book = $book_result->fetch_assoc();
            
            if (!$book || $book['status'] !== 'Available') {
                throw new Exception("Book ID $book_id is not available for borrowing");
            }
            
            // Determine loan period based on shelf location
            $allowed_days = 7; // Default
            if ($book['shelf_location'] == 'RES') {
                $allowed_days = 1;
            } elseif ($book['shelf_location'] == 'REF') {
                $allowed_days = 0;
            }
            
            // Insert borrowing record
            $borrow_query = "INSERT INTO borrowings 
                            (user_id, book_id, status, issue_date, issued_by, due_date)
                            VALUES (?, ?, 'Active', NOW(), ?, DATE_ADD(NOW(), INTERVAL ? DAY))";
            $stmt = $conn->prepare($borrow_query);
            $stmt->bind_param('iiii', $user_id, $book_id, $admin_id, $allowed_days);
            $stmt->execute();
            
            // Update book status
            $update_book = "UPDATE books SET status = 'Borrowed' WHERE id = ?";
            $stmt = $conn->prepare($update_book);
            $stmt->bind_param('i', $book_id);
            $stmt->execute();
        }
        
        // Update user's borrowed books count
        $update_user = "UPDATE users SET 
                        borrowed_books = borrowed_books + ?,
                        last_update = CURDATE()
                        WHERE id = ?";
        $book_count = count($book_ids);
        $stmt = $conn->prepare($update_user);
        $stmt->bind_param('ii', $book_count, $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'status' => 'success', 
            'message' => count($book_ids) . ' book(s) have been successfully borrowed'
        ]);
        
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
