<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle AJAX checkout request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titles = $_POST['titles'];
    $date = date('Y-m-d H:i:s');
    $response = ['success' => false, 'message' => 'Failed to checkout selected items.'];

    try {
        foreach ($titles as $title) {
            // Check if the user already has this book reserved
            $query = "SELECT COUNT(*) as count 
                     FROM reservations r
                     JOIN books b ON r.book_id = b.id 
                     WHERE r.user_id = ? 
                     AND b.title = ? 
                     AND r.cancel_date IS NULL 
                     AND r.recieved_date IS NULL";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            $stmt->bind_param('is', $user_id, $title);
            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }
            $result = $stmt->get_result();
            $reservation = $result->fetch_assoc();
            if ($reservation['count'] > 0) {
                throw new Exception("You already have an active reservation for: " . $title);
            }

            // Get book ID by title
            $query = "SELECT id FROM books WHERE title = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            $stmt->bind_param('s', $title);
            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }
            $result = $stmt->get_result();
            if (!$result) {
                throw new Exception("Get result failed: " . $stmt->error);
            }
            $book = $result->fetch_assoc();
            if (!$book) {
                throw new Exception("Book not found: " . $title);
            }
            $book_id = $book['id'];

            // Insert into reservations table
            $query = "INSERT INTO reservations (user_id, book_id, reserve_date, cancel_date, recieved_date, status) VALUES (?, ?, ?, NULL, NULL, 'Pending')";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            $stmt->bind_param('iis', $user_id, $book_id, $date);
            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }

            // Update cart status to inactive
            $query = "UPDATE cart SET status = 0 WHERE user_id = ? AND book_id = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            $stmt->bind_param('ii', $user_id, $book_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }
        }
        $response['success'] = true;
        $response['message'] = 'Books checked out successfully.';
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Get reservation history
$historyQuery = "SELECT r.id, b.title, r.reserve_date, r.cancel_date, r.recieved_date 
                FROM reservations r 
                JOIN books b ON r.book_id = b.id 
                WHERE r.user_id = ? AND (r.cancel_date IS NOT NULL OR r.recieved_date IS NOT NULL)";
$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bind_param('i', $user_id);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();

// Get current cart items
$cartQuery = "SELECT b.title, c.date, 
             (SELECT CONCAT(w.firstname, ' ', w.lastname) 
              FROM contributors con 
              JOIN writers w ON con.writer_id = w.id 
              WHERE con.book_id = b.id AND con.role = 'Author' 
              ORDER BY con.id LIMIT 1) as author 
             FROM cart c 
             JOIN books b ON c.book_id = b.id 
             WHERE c.user_id = ? AND c.status = 1";
$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bind_param('i', $user_id);
$cartStmt->execute();
$cartResult = $cartStmt->get_result();

include 'inc/header.php';
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#cart">Cart</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#history">Reservation History</a>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content">
            <!-- Cart Tab -->
            <div id="cart" class="tab-pane active">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Cart</h6>
                        <div>
                            <span id="selectedCount">(0 items selected)</span>
                            <button class="btn btn-primary btn-sm" id="checkout">Checkout</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Cart Table -->
                        <div class="table-responsive">
                            <!-- ...existing cart table code... -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Tab -->
            <div id="history" class="tab-pane fade">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Reservation History</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <!-- ...existing history table code... -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>

<!-- ...existing JavaScript code... -->
