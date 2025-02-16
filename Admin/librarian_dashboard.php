<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Strict role check
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Librarian' && $_SESSION['role'] !== 'Assistant')) {
    $_SESSION = array();
    session_destroy();
    header("Location: index.php");
    exit();
}

include '../admin/inc/header.php';
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Library Management Dashboard</h1>
            <a href="reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
            </a>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Total Books Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Books</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <a href="manage_books.php" class="text-dark">
                                        <?php 
                                        // Add your book count query here
                                        echo "2,500"; 
                                        ?>
                                    </a>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-books fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Borrowed Books Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Books Borrowed</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <a href="borrowed_books.php" class="text-dark">
                                        <?php echo "150"; ?>
                                    </a>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-book-reader fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Books Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Overdue Books</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <a href="overdue_books.php" class="text-dark">
                                        <?php echo "25"; ?>
                                    </a>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reserved Books Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Reserved Books</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <a href="reserved_books.php" class="text-dark">
                                        <?php echo "45"; ?>
                                    </a>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-bookmark fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Book Activities</h6>
                    </div>
                    <div class="card-body">
                        <!-- Add your recent activities table or list here -->
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- /.container-fluid -->
</div>
<!-- End of Main Content -->

<?php include '../Admin/inc/footer.php'; ?>

<script src="inc/js/demo/chart-area-demo.js"></script>
<script src="inc/js/demo/chart-pie-demo.js"></script>