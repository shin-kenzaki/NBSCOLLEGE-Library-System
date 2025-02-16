<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Strict role check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Encoder') {
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
            <h1 class="h3 mb-0 text-gray-800">Encoder Dashboard</h1>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Add Book Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Add New Book</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <a href="add_book.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Add Book
                                    </a>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-book fa-2x text-gray-300"></i>
                            </div>
                        </div>
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
