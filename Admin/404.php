<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Get the requested URL
$requested_url = htmlspecialchars($_SERVER['REQUEST_URI']);

// Include header
include 'inc/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- 404 Error Text -->
    <div class="text-center">
        <div class="error mx-auto" data-text="404">404</div>
        <p class="lead text-gray-800 mb-5">Page Not Found</p>
        <p class="text-gray-500 mb-0">The page you requested could not be found.</p>
        <p class="text-gray-500 mb-2">Requested URL: <?php echo $requested_url; ?></p>
        <a href="dashboard.php">&larr; Back to Dashboard</a>
    </div>

    <!-- Quick Navigation -->
    <div class="mt-4 text-center">
        <h6 class="text-gray-600">Quick Navigation</h6>
        <div class="btn-group mt-2" role="group">
            <a href="dashboard.php" class="btn btn-primary btn-sm">Dashboard</a>
            <a href="book_list.php" class="btn btn-info btn-sm">Books</a>
            <a href="users_list.php" class="btn btn-success btn-sm">Users</a>
            <a href="borrowed_books.php" class="btn btn-warning btn-sm">Borrowings</a>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<?php
// Include footer
include 'inc/footer.php';
?>
