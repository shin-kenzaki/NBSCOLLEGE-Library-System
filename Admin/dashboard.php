<?php
session_start();
include '../db.php';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant', 'Encoder'])) {
    header("Location: index.php");
    exit();
}

// Fetch analytics data
$today = date('Y-m-d');

// Total active borrowings
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM borrowings WHERE status = 'Active'");
$row = mysqli_fetch_assoc($result);
$active_borrowings = $row['count'];

// Total overdue books
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM borrowings WHERE status = 'Overdue' AND due_date < '$today'");
$row = mysqli_fetch_assoc($result);
$overdue_books = $row['count'];

// Total reservations - Updated query
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM reservations WHERE status = 'Pending'");
$row = mysqli_fetch_assoc($result);
$pending_reservations = $row['count'];

// Total active users
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE status = 'Active' AND usertype = 'Student'");
$row = mysqli_fetch_assoc($result);
$active_users = $row['count'];

// Total pending fines
$result = mysqli_query($conn, "SELECT SUM(amount) as total FROM fines WHERE status = 'Unpaid'");
$row = mysqli_fetch_assoc($result);
$pending_fines = $row['total'] ?: 0;

// Total paid fines
$result = mysqli_query($conn, "SELECT SUM(amount) as total FROM fines WHERE status = 'Paid'");
$row = mysqli_fetch_assoc($result);
$paid_fines = $row['total'] ?: 0;

// Get book status distribution for pie chart
$result = mysqli_query($conn, "SELECT 
    SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
    SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN status = 'damaged' THEN 1 ELSE 0 END) as damaged
    FROM books");
$book_stats = mysqli_fetch_assoc($result);

// Add hidden inputs for pie chart data
echo "<input type='hidden' id='borrowed' value='" . $book_stats['borrowed'] . "'>";
echo "<input type='hidden' id='lost' value='" . $book_stats['lost'] . "'>";
echo "<input type='hidden' id='available' value='" . $book_stats['available'] . "'>";
echo "<input type='hidden' id='damaged' value='" . $book_stats['damaged'] . "'>";

// Get borrowings status distribution for doughnut chart
$result = mysqli_query($conn, "SELECT 
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned,
    SUM(CASE WHEN status = 'Damaged' THEN 1 ELSE 0 END) as damaged,
    SUM(CASE WHEN status = 'Lost' THEN 1 ELSE 0 END) as lost
    FROM borrowings");
$borrowings_stats = mysqli_fetch_assoc($result);

// Add hidden inputs for borrowings status data
echo "<input type='hidden' id='active' value='" . $borrowings_stats['active'] . "'>";
echo "<input type='hidden' id='returned' value='" . $borrowings_stats['returned'] . "'>";
echo "<input type='hidden' id='damaged_borrowings' value='" . $borrowings_stats['damaged'] . "'>";
echo "<input type='hidden' id='lost_borrowings' value='" . $borrowings_stats['lost'] . "'>";

// --- Enhanced Code for Borrowings Overview Chart ---
// Get current month borrowings: count borrowings per day
$firstDay = date('Y-m-01');
$lastDay  = date('Y-m-t');
$query = "SELECT DAY(issue_date) as day, COUNT(*) as count 
          FROM borrowings 
          WHERE issue_date BETWEEN '$firstDay' AND '$lastDay'
          GROUP BY DAY(issue_date)";
$result_borrowings = mysqli_query($conn, $query);
$borrowingsData = [];
while ($row = mysqli_fetch_assoc($result_borrowings)) {
   $borrowingsData[(int)$row['day']] = (int)$row['count'];
}

// Get previous month borrowings
$prevMonthFirstDay = date('Y-m-01', strtotime('-1 month'));
$prevMonthLastDay = date('Y-m-t', strtotime('-1 month'));
$query = "SELECT DAY(issue_date) as day, COUNT(*) as count 
          FROM borrowings 
          WHERE issue_date BETWEEN '$prevMonthFirstDay' AND '$prevMonthLastDay'
          GROUP BY DAY(issue_date)";
$result_prev_borrowings = mysqli_query($conn, $query);
$prevBorrowingsData = [];
while ($row = mysqli_fetch_assoc($result_prev_borrowings)) {
   $prevBorrowingsData[(int)$row['day']] = (int)$row['count'];
}

// Get same month last year
$lastYearFirstDay = date('Y-m-01', strtotime('-1 year'));
$lastYearLastDay = date('Y-m-t', strtotime('-1 year'));
$query = "SELECT DAY(issue_date) as day, COUNT(*) as count 
          FROM borrowings 
          WHERE issue_date BETWEEN '$lastYearFirstDay' AND '$lastYearLastDay'
          GROUP BY DAY(issue_date)";
$result_last_year_borrowings = mysqli_query($conn, $query);
$lastYearBorrowingsData = [];
while ($row = mysqli_fetch_assoc($result_last_year_borrowings)) {
   $lastYearBorrowingsData[(int)$row['day']] = (int)$row['count'];
}

// Get returned books this month
$query = "SELECT DAY(return_date) as day, COUNT(*) as count 
          FROM borrowings 
          WHERE return_date BETWEEN '$firstDay' AND '$lastDay'
          GROUP BY DAY(return_date)";
$result_returns = mysqli_query($conn, $query);
$returnsData = [];
while ($row = mysqli_fetch_assoc($result_returns)) {
   $returnsData[(int)$row['day']] = (int)$row['count'];
}

$daysInMonth = date('t');
$borrowingsLabels = [];
$borrowingsCounts = [];
$prevMonthCounts = [];
$lastYearCounts = [];
$returnsCounts = [];

for ($day = 1; $day <= $daysInMonth; $day++) {
    $borrowingsLabels[] = $day;
    $borrowingsCounts[] = isset($borrowingsData[$day]) ? $borrowingsData[$day] : 0;
    $prevMonthCounts[] = isset($prevBorrowingsData[$day]) ? $prevBorrowingsData[$day] : 0;
    $lastYearCounts[] = isset($lastYearBorrowingsData[$day]) ? $lastYearBorrowingsData[$day] : 0;
    $returnsCounts[] = isset($returnsData[$day]) ? $returnsData[$day] : 0;
}

// --- Enhanced Code for Books Overview Chart ---
// Get current month added books by category
$query = "SELECT DAY(b.date_added) as day, COUNT(*) as count 
          FROM books b
          WHERE b.date_added BETWEEN '$firstDay' AND '$lastDay'
          GROUP BY DAY(b.date_added)";
$result_books = mysqli_query($conn, $query);
$booksData = [];
while ($row = mysqli_fetch_assoc($result_books)) {
   $booksData[(int)$row['day']] = (int)$row['count'];
}

// Get books by subject category
$query = "SELECT 
            DAY(b.date_added) as day, 
            b.subject_category,
            COUNT(*) as count 
          FROM books b
          WHERE b.date_added BETWEEN '$firstDay' AND '$lastDay' 
            AND b.subject_category IS NOT NULL AND b.subject_category != ''
          GROUP BY DAY(b.date_added), b.subject_category";
$result_books_by_category = mysqli_query($conn, $query);
$booksByCategoryData = [];

// Initialize common categories we want to track
$categories = ['Topical', 'Fiction', 'Non-fiction', 'Reference', 'Academic'];
foreach ($categories as $category) {
    $booksByCategoryData[$category] = array_fill(1, $daysInMonth, 0);
}

while ($row = mysqli_fetch_assoc($result_books_by_category)) {
    $day = (int)$row['day'];
    $category = $row['subject_category'];
    $count = (int)$row['count'];
    
    if (isset($booksByCategoryData[$category])) {
        $booksByCategoryData[$category][$day] = $count;
    }
}

$booksLabels = [];
$booksCounts = [];
$booksByCategoryCounts = [];

for ($day = 1; $day <= $daysInMonth; $day++) {
    $booksLabels[] = $day;
    $booksCounts[] = isset($booksData[$day]) ? $booksData[$day] : 0;
}

foreach ($categories as $category) {
    $categoryData = [];
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $categoryData[] = isset($booksByCategoryData[$category][$day]) ? $booksByCategoryData[$category][$day] : 0;
    }
    $booksByCategoryCounts[$category] = $categoryData;
}

include '../admin/inc/header.php';
?>
            <!-- Main Content -->
            <div id="content" class="d-flex flex-column min-vh-100">
                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Library Dashboard</h1>
                        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i
                                class="fas fa-download fa-sm text-white-50"></i> Generate Report</a>
                    </div>

                    <style>
                        .stats-card {
                            transition: all 0.3s;
                            border-left: 4px solid;
                        }
                        .stats-card:hover {
                            transform: translateY(-5px);
                            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                        }
                        .stats-icon {
                            font-size: 2rem;
                            opacity: 0.8; /* Increased opacity for better visibility */
                        }
                        .stats-title {
                            font-size: 0.9rem;
                            font-weight: bold;
                            text-transform: uppercase;
                        }
                        .stats-number {
                            font-size: 1.5rem;
                            font-weight: bold;
                        }
                        .primary-card {
                            border-left-color: #4e73df;
                        }
                        .danger-card {
                            border-left-color: #e74a3b;
                        }
                        .info-card {
                            border-left-color: #36b9cc;
                        }
                        .warning-card {
                            border-left-color: #f6c23e;
                        }
                        .success-card {
                            border-left-color: #1cc88a;
                        }
                        .secondary-card {
                            border-left-color: #858796;
                        }
                        
                        /* Enhanced styling for dashboard cards and sections */
                        .section-header {
                            position: relative;
                            padding-bottom: 0.5rem;
                            margin-bottom: 1.5rem;
                        }
                        .section-header::after {
                            content: '';
                            position: absolute;
                            left: 0;
                            bottom: 0;
                            width: 50px;
                            height: 3px;
                            background: #4e73df;
                        }
                        .metrics-value {
                            font-size: 1.75rem;
                            font-weight: 700;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            width: 70px;
                            height: 70px;
                            border-radius: 50%;
                            color: white;
                        }
                        .action-card {
                            transition: all 0.3s ease;
                            border-radius: 0.5rem;
                            overflow: hidden;
                            border: none;
                        }
                        .action-card:hover {
                            transform: translateY(-5px);
                            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15);
                        }
                        .action-card .card-body {
                            padding: 1.5rem;
                            position: relative;
                            z-index: 1;
                        }
                        .action-card h5 {
                            font-size: 1.1rem;
                            font-weight: 700;
                        }
                        .action-card .btn {
                            border-radius: 50px;
                            padding: 0.75rem 1.25rem;
                            transition: all 0.2s;
                            font-weight: 600;
                        }
                        .action-card .btn:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                        }
                        .action-card .action-icon {
                            font-size: 2.5rem;
                            position: absolute;
                            bottom: -10px;
                            right: 10px;
                            opacity: 0.1;
                            transform: rotate(-15deg);
                            transition: all 0.3s;
                        }
                        .action-card:hover .action-icon {
                            transform: scale(1.2) rotate(0deg);
                            opacity: 0.15;
                        }
                        .border-top-primary { border-top: 4px solid #4e73df; }
                        .border-top-success { border-top: 4px solid #1cc88a; }
                        .border-top-info { border-top: 4px solid #36b9cc; }
                        .border-top-warning { border-top: 4px solid #f6c23e; }
                        .border-top-danger { border-top: 4px solid #e74a3b; }
                        .border-top-secondary { border-top: 4px solid #858796; }
                    </style>

                    <!-- Content Row 1 & 2 - Replace with Key Metrics Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4 class="text-gray-800 font-weight-bold section-header">
                                <i class="fas fa-chart-line mr-2"></i>Key Metrics
                            </h4>
                        </div>
                        
                        <!-- Active Borrowings Card -->
                        <div class="col-md-4 mb-4">
                            <div class="card shadow h-100 action-card border-top-primary">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="text-primary font-weight-bold mb-0">
                                            <i class="fas fa-book mr-2"></i>Active Borrowings
                                        </h5>
                                        <div class="metrics-value bg-primary">
                                            <?php echo $active_borrowings; ?>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-3">Currently borrowed books that have not been returned yet.</p>
                                    <a href="borrowed_books.php?status=Active" class="btn btn-primary btn-block">
                                        <i class="fas fa-search mr-2"></i>View All Borrowings
                                    </a>
                                    <i class="fas fa-book-reader action-icon text-primary"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Overdue Books Card -->
                        <div class="col-md-4 mb-4">
                            <div class="card shadow h-100 action-card border-top-danger">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="text-danger font-weight-bold mb-0">
                                            <i class="fas fa-clock mr-2"></i>Overdue Books
                                        </h5>
                                        <div class="metrics-value bg-danger">
                                            <?php echo $overdue_books; ?>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-3">Books that have passed their due date and need attention.</p>
                                    <a href="borrowed_books.php?status=Overdue" class="btn btn-danger btn-block">
                                        <i class="fas fa-exclamation-circle mr-2"></i>Manage Overdue Books
                                    </a>
                                    <i class="fas fa-calendar-times action-icon text-danger"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pending Reservations Card -->
                        <div class="col-md-4 mb-4">
                            <div class="card shadow h-100 action-card border-top-info">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="text-info font-weight-bold mb-0">
                                            <i class="fas fa-bookmark mr-2"></i>Pending Reservations
                                        </h5>
                                        <div class="metrics-value bg-info">
                                            <?php echo $pending_reservations; ?>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-3">Book reservations waiting to be processed by staff. </p>
                                    <a href="book_reservations.php" class="btn btn-info btn-block">
                                        <i class="fas fa-list-alt mr-2"></i>View Reservations
                                    </a>
                                    <i class="fas fa-clipboard-list action-icon text-info"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pending Fines Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card shadow h-100 action-card border-top-warning">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="text-warning font-weight-bold mb-0">
                                            <i class="fas fa-money-bill mr-2"></i>Pending Fines
                                        </h5>
                                        <div class="metrics-value bg-warning" style="width: auto; padding: 0 20px;">
                                            ₱<?php echo number_format($pending_fines, 2); ?>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-3">Total amount of unpaid fines from overdue, damaged, or lost books.</p>
                                    <a href="fines.php?status=Unpaid" class="btn btn-warning btn-block">
                                        <i class="fas fa-file-invoice mr-2"></i>Manage Unpaid Fines
                                    </a>
                                    <i class="fas fa-hand-holding-usd action-icon text-warning"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Paid Fines Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card shadow h-100 action-card border-top-success">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h5 class="text-success font-weight-bold mb-0">
                                            <i class="fas fa-money-check-alt mr-2"></i>Paid Fines
                                        </h5>
                                        <div class="metrics-value bg-success" style="width: auto; padding: 0 20px;">
                                            ₱<?php echo number_format($paid_fines, 2); ?>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-3">Total amount of collected fines that have been settled.</p>
                                    <a href="fines.php?status=Paid" class="btn btn-success btn-block">
                                        <i class="fas fa-history mr-2"></i>View Payment History
                                    </a>
                                    <i class="fas fa-coins action-icon text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4 class="text-gray-800 font-weight-bold section-header">
                                <i class="fas fa-bolt mr-2"></i>Quick Actions
                            </h4>
                        </div>
                        
                        <!-- Add User Option -->
                        <div class="col-md-4">
                            <div class="card shadow h-100 action-card border-top-info">
                                <div class="card-body">
                                    <h5 class="text-info font-weight-bold mb-4">
                                        <i class="fas fa-user-plus mr-2"></i>Add User
                                    </h5>
                                    <p class="text-muted mb-4">Register new students, faculty members, or library staff with access to the system.</p>
                                    <a href="users_list.php" class="btn btn-info btn-lg btn-block">
                                        <i class="fas fa-arrow-right mr-2"></i>Manage Users
                                    </a>
                                    <i class="fas fa-users action-icon text-info"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Borrow Book Option -->
                        <div class="col-md-4">
                            <div class="card shadow h-100 action-card border-top-secondary">
                                <div class="card-body">
                                    <h5 class="text-secondary font-weight-bold mb-4">
                                        <i class="fas fa-book-reader mr-2"></i>Borrow a Book
                                    </h5>
                                    <p class="text-muted mb-4">Process book borrowings for students and faculty with barcode scanning and due date selection.</p>
                                    <a href="book_borrowing.php" class="btn btn-secondary btn-lg btn-block">
                                        <i class="fas fa-arrow-right mr-2"></i>Start Borrowing
                                    </a>
                                    <i class="fas fa-exchange-alt action-icon text-secondary"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Return Book Option -->
                        <div class="col-md-4">
                            <div class="card shadow h-100 action-card border-top-warning">
                                <div class="card-body">
                                    <h5 class="text-warning font-weight-bold mb-4">
                                        <i class="fas fa-undo-alt mr-2"></i>Return Books
                                    </h5>
                                    <p class="text-muted mb-4">Process book returns, check for damages, and calculate fines for overdue materials.</p>
                                    <a href="borrowed_books.php" class="btn btn-warning btn-lg btn-block">
                                        <i class="fas fa-arrow-right mr-2"></i>Process Returns
                                    </a>
                                    <i class="fas fa-sign-in-alt action-icon text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Book Shortcuts Row -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4 class="text-primary font-weight-bold section-header">
                                <i class="fas fa-plus-circle mr-2"></i>Add New Book
                            </h4>
                        </div>
                        
                        <!-- Standard Form Option -->
                        <div class="col-md-6">
                            <div class="card shadow h-100 action-card border-top-success">
                                <div class="card-body">
                                    <h5 class="text-success font-weight-bold mb-4">
                                        <i class="fas fa-book mr-2"></i>Standard Form
                                    </h5>
                                    <p class="text-muted mb-4">Quick single-page form for adding books efficiently. Best for experienced users who need to add books rapidly.</p>
                                    <a href="add-book.php" class="btn btn-success btn-lg btn-block">
                                        <i class="fas fa-arrow-right mr-2"></i>Use Standard Form
                                    </a>
                                    <i class="fas fa-file-alt action-icon text-success"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step-by-Step Option -->
                        <div class="col-md-6">
                            <div class="card shadow h-100 action-card border-top-primary">
                                <div class="card-body">
                                    <h5 class="text-primary font-weight-bold mb-4">
                                        <i class="fas fa-list-ol mr-2"></i>Step-by-Step Guide
                                    </h5>
                                    <p class="text-muted mb-4">Guided multi-step process that walks you through adding books with detailed instructions at each stage.</p>
                                    <a href="step-by-step-add-book.php" class="btn btn-primary btn-lg btn-block">
                                        <i class="fas fa-arrow-right mr-2"></i>Use Step-by-Step
                                    </a>
                                    <i class="fas fa-tasks action-icon text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content Row -->

                    <div class="row">

                        <!-- Borrowings Overview Area Chart -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <!-- Card Header - Changed Title -->
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Borrowings Overview (<?= date('F Y'); ?>)</h6>
                                    <a href="reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                                        <i class="fas fa-chart-bar fa-sm text-white-50"></i> Reports
                                    </a>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="chart-area">
                                        <canvas id="myAreaChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pie Chart -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Book Status Distribution</h6>
                            
                                    <a href="export_books_statuses.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i
                                class="fas fa-download fa-sm text-white-50"></i> Generate Report</a>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="myPieChart"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-success"></i> Available
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-primary"></i> Borrowed
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-warning"></i> Damaged
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-danger"></i> Lost
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Borrowings Status Doughnut Chart -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Borrowings Status Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="myBorrowingsChart"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-primary"></i> Active
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-success"></i> Returned
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-warning"></i> Damaged
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-danger"></i> Lost
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Added Books Overview Area Chart -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <!-- Card Header - Changed Title -->
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Added Books Overview (<?= date('F Y'); ?>)</h6>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="chart-area">
                                        <canvas id="myBooksChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->

            <?php
            include '../Admin/inc/footer.php'
            ?>
            <!-- End of Footer -->



    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script>
    // Set default font configs
    Chart.defaults.global.defaultFontFamily = 'Nunito, -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    Chart.defaults.global.defaultFontColor = '#858796';

    // Get book stats from hidden inputs
    const bookStats = {
        borrowed: parseInt(document.getElementById('borrowed').value),
        lost: parseInt(document.getElementById('lost').value),
        available: parseInt(document.getElementById('available').value),
        damaged: parseInt(document.getElementById('damaged').value)
    };

    // Initialize Pie Chart
    var ctx = document.getElementById("myPieChart");
    var myPieChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ["Borrowed", "Lost", "Available", "Damaged"],
        datasets: [{
          data: [bookStats.borrowed, bookStats.lost, bookStats.available, bookStats.damaged],
          backgroundColor: ['#4e73df', '#e74a3b', '#1cc88a', '#FFA500'],
          hoverBackgroundColor: ['#2e59d9', '#be2617', '#17a673', '#cc8400'],
          hoverBorderColor: "rgba(234, 236, 244, 1)"
        }]
      },
      options: {
        maintainAspectRatio: false,
        tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
          caretPadding: 10
        },
        legend: { 
          display: false,
          labels: {
            fontFamily: 'Nunito, -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            fontColor: '#858796'
          }
        },
        cutoutPercentage: 80
      }
    });

    // Enhanced Area Chart Borrowings Overview
    const borrowingsLabels = <?= json_encode($borrowingsLabels); ?>;
    const borrowingsCounts = <?= json_encode($borrowingsCounts); ?>;
    const prevMonthCounts = <?= json_encode($prevMonthCounts); ?>;
    const lastYearCounts = <?= json_encode($lastYearCounts); ?>;
    const returnsCounts = <?= json_encode($returnsCounts); ?>;
    
    var ctxArea = document.getElementById("myAreaChart");
    var myAreaChart = new Chart(ctxArea, {
        type: 'line',
        data: {
            labels: borrowingsLabels,
            datasets: [
                {
                    label: 'Current Month Borrowings',
                    data: borrowingsCounts,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    fill: false
                },
                {
                    label: 'Last Month Borrowings',
                    data: prevMonthCounts,
                    backgroundColor: "rgba(28, 200, 138, 0.05)",
                    borderColor: "rgba(28, 200, 138, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(28, 200, 138, 1)",
                    pointBorderColor: "rgba(28, 200, 138, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(28, 200, 138, 1)",
                    pointHoverBorderColor: "rgba(28, 200, 138, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    fill: false
                },
                {
                    label: 'Last Year Same Month',
                    data: lastYearCounts,
                    backgroundColor: "rgba(246, 194, 62, 0.05)",
                    borderColor: "rgba(246, 194, 62, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(246, 194, 62, 1)",
                    pointBorderColor: "rgba(246, 194, 62, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(246, 194, 62, 1)",
                    pointHoverBorderColor: "rgba(246, 194, 62, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    fill: false
                },
                {
                    label: 'Returns This Month',
                    data: returnsCounts,
                    backgroundColor: "rgba(231, 74, 59, 0.05)",
                    borderColor: "rgba(231, 74, 59, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(231, 74, 59, 1)",
                    pointBorderColor: "rgba(231, 74, 59, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(231, 74, 59, 1)",
                    pointHoverBorderColor: "rgba(231, 74, 59, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    fill: false
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    time: {
                        unit: 'day'
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 31
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        // Adjust the max value dynamically if needed
                    },
                    gridLines: {
                        color: "rgba(234, 236, 244, 1)",
                        zeroLineColor: "rgba(234, 236, 244, 1)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }]
            },
            legend: {
                display: true,
                position: 'top'
            },
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                titleMarginBottom: 10,
                titleFontColor: '#6e707e',
                titleFontSize: 14,
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: true,
                intersect: false,
                mode: 'index',
                caretPadding: 10,
                callbacks: {
                    title: function(tooltipItems, chart) {
                        const dayOfMonth = tooltipItems[0].xLabel;
                        const month = new Date().getMonth(); // 0-11
                        const year = new Date().getFullYear();
                        const date = new Date(year, month, dayOfMonth);
                        return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                    },
                    label: function(tooltipItem, data) {
                        const label = data.datasets[tooltipItem.datasetIndex].label || '';
                        return `${label}: ${tooltipItem.yLabel}`;
                    }
                }
            }
        }
    });

    // Get borrowings stats from hidden inputs
    const borrowingsStats = {
        active: parseInt(document.getElementById('active').value),
        returned: parseInt(document.getElementById('returned').value),
        damaged: parseInt(document.getElementById('damaged_borrowings').value),
        lost: parseInt(document.getElementById('lost_borrowings').value)
    };

    // Initialize Borrowings Status Doughnut Chart
    var ctxBorrowings = document.getElementById("myBorrowingsChart");
    var myBorrowingsChart = new Chart(ctxBorrowings, {
      type: 'doughnut',
      data: {
        labels: ["Active", "Returned", "Damaged", "Lost"],
        datasets: [{
          data: [borrowingsStats.active, borrowingsStats.returned, borrowingsStats.damaged, borrowingsStats.lost],
          backgroundColor: ['#4e73df', '#1cc88a', '#FFA500', '#e74a3b'],
          hoverBackgroundColor: ['#2e59d9', '#17a673', '#cc8400', '#be2617'],
          hoverBorderColor: "rgba(234, 236, 244, 1)"
        }]
      },
      options: {
        maintainAspectRatio: false,
        tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
          caretPadding: 10
        },
        legend: { display: false },
        cutoutPercentage: 80
      }
    });

    // Enhanced Area Chart Added Books Overview
    const booksLabels = <?= json_encode($booksLabels); ?>;
    const booksCounts = <?= json_encode($booksCounts); ?>;
    const categoryTopical = <?= json_encode($booksByCategoryCounts['Topical'] ?? []); ?>;
    const categoryFiction = <?= json_encode($booksByCategoryCounts['Fiction'] ?? []); ?>;
    const categoryNonFiction = <?= json_encode($booksByCategoryCounts['Non-fiction'] ?? []); ?>;
    const categoryReference = <?= json_encode($booksByCategoryCounts['Reference'] ?? []); ?>;
    const categoryAcademic = <?= json_encode($booksByCategoryCounts['Academic'] ?? []); ?>;
    
    var ctxBooks = document.getElementById("myBooksChart");
    var myBooksChart = new Chart(ctxBooks, {
        type: 'line',
        data: {
            labels: booksLabels,
            datasets: [
                {
                    label: 'Total Added Books',
                    data: booksCounts,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    fill: false,
                    borderWidth: 4
                },
                {
                    label: 'Topical',
                    data: categoryTopical,
                    backgroundColor: "rgba(28, 200, 138, 0.05)",
                    borderColor: "rgba(28, 200, 138, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(28, 200, 138, 1)",
                    pointBorderColor: "rgba(28, 200, 138, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(28, 200, 138, 1)",
                    pointHoverBorderColor: "rgba(28, 200, 138, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    fill: false
                },
                {
                    label: 'Fiction',
                    data: categoryFiction,
                    backgroundColor: "rgba(246, 194, 62, 0.05)",
                    borderColor: "rgba(246, 194, 62, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(246, 194, 62, 1)",
                    pointBorderColor: "rgba(246, 194, 62, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(246, 194, 62, 1)",
                    pointHoverBorderColor: "rgba(246, 194, 62, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    fill: false
                },
                {
                    label: 'Non-fiction',
                    data: categoryNonFiction,
                    backgroundColor: "rgba(231, 74, 59, 0.05)",
                    borderColor: "rgba(231, 74, 59, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(231, 74, 59, 1)",
                    pointBorderColor: "rgba(231, 74, 59, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(231, 74, 59, 1)",
                    pointHoverBorderColor: "rgba(231, 74, 59, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    fill: false
                },
                {
                    label: 'Reference',
                    data: categoryReference,
                    backgroundColor: "rgba(54, 185, 204, 0.05)",
                    borderColor: "rgba(54, 185, 204, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(54, 185, 204, 1)",
                    pointBorderColor: "rgba(54, 185, 204, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(54, 185, 204, 1)",
                    pointHoverBorderColor: "rgba(54, 185, 204, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    fill: false
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    time: {
                        unit: 'day'
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 31
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        // Adjust the max value dynamically if needed
                    },
                    gridLines: {
                        color: "rgba(234, 236, 244, 1)",
                        zeroLineColor: "rgba(234, 236, 244, 1)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }]
            },
            legend: {
                display: true,
                position: 'top'
            },
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                titleMarginBottom: 10,
                titleFontColor: '#6e707e',
                titleFontSize: 14,
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: true,
                intersect: false,
                mode: 'index',
                caretPadding: 10,
                callbacks: {
                    title: function(tooltipItems, chart) {
                        const dayOfMonth = tooltipItems[0].xLabel;
                        const month = new Date().getMonth(); // 0-11
                        const year = new Date().getFullYear();
                        const date = new Date(year, month, dayOfMonth);
                        return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                    },
                    label: function(tooltipItem, data) {
                        const label = data.datasets[tooltipItem.datasetIndex].label || '';
                        return `${label}: ${tooltipItem.yLabel}`;
                    }
                }
            }
        }
    });
    </script>