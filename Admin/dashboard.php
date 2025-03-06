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
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM borrowings WHERE status = 'Active' AND due_date < '$today'");
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

// --- New Code for Borrowings Overview Chart ---
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
$daysInMonth = date('t');
$borrowingsLabels = [];
$borrowingsCounts = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $borrowingsLabels[] = $day;
    $borrowingsCounts[] = isset($borrowingsData[$day]) ? $borrowingsData[$day] : 0;
}

// Get current month added books: count books added per day
$query = "SELECT DAY(date_added) as day, COUNT(*) as count 
          FROM books 
          WHERE date_added BETWEEN '$firstDay' AND '$lastDay'
          GROUP BY DAY(date_added)";
$result_books = mysqli_query($conn, $query);
$booksData = [];
while ($row = mysqli_fetch_assoc($result_books)) {
   $booksData[(int)$row['day']] = (int)$row['count'];
}
$booksLabels = [];
$booksCounts = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $booksLabels[] = $day;
    $booksCounts[] = isset($booksData[$day]) ? $booksData[$day] : 0;
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

                    <!-- Content Row 1 -->
                    <div class="row">

                        <!-- Active Borrowings Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="borrowed_books.php" style="text-decoration: none;">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Active Borrowings</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_borrowings; ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-book fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Overdue Books Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="borrowed_books.php" style="text-decoration: none;">
                                <div class="card border-left-danger shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                    Overdue Books</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overdue_books; ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Pending Reservations Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="book_reservations.php" style="text-decoration: none;">
                                <div class="card border-left-info shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                    Pending Reservations</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_reservations; ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-bookmark fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Pending Fines Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="fines.php" style="text-decoration: none;">
                                <div class="card border-left-warning shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    Pending Fines</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($pending_fines, 2); ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-money-bill fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Content Row 2 -->
                    <div class="row">

                        <!-- Paid Fines Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="fines.php" style="text-decoration: none;">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Paid Fines</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($paid_fines, 2); ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-money-check-alt fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Add User Shortcut Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="users_list.php" style="text-decoration: none;">
                                <div class="card border-left-info shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                    Add User</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">Shortcut</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Book Borrowing Shortcut Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="book_borrowing.php" style="text-decoration: none;">
                                <div class="card border-left-secondary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                                    Borrow a Book</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">Shortcut</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-book-reader fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        
                        <!-- Add Book Shortcut Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Add Book</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">Shortcut</div>
                                            <div class="mt-2">
                                                <a href="add-book.php" class="btn btn-sm btn-primary mr-1">Standard</a>
                                                <a href="add_book_shortcut.php" class="btn btn-sm btn-success">Quick Add</a>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-plus fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
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
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                            <a class="dropdown-item" href="export_borrowings.php?type=previous_month">Export Previous Month</a>
                                            <a class="dropdown-item" href="export_borrowings.php?type=current_month">Export Current Month</a>
                                            <a class="dropdown-item" href="export_borrowings.php?type=last_year">Export Previous Year</a>
                                            <a class="dropdown-item" href="export_borrowings.php?type=current_year">Export Current Year</a>
                                        </div>
                                    </div>
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

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Content Column -->
                        <div class="col-lg-6 mb-4">

                            <!-- Project Card Example -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Projects</h6>
                                </div>
                                <div class="card-body">
                                    <h4 class="small font-weight-bold">Server Migration <span
                                            class="float-right">20%</span></h4>
                                    <div class="progress mb-4">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 20%"
                                            aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <h4 class="small font-weight-bold">Sales Tracking <span
                                            class="float-right">40%</span></h4>
                                    <div class="progress mb-4">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 40%"
                                            aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <h4 class="small font-weight-bold">Customer Database <span
                                            class="float-right">60%</span></h4>
                                    <div class="progress mb-4">
                                        <div class="progress-bar" role="progressbar" style="width: 60%"
                                            aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <h4 class="small font-weight-bold">Payout Details <span
                                            class="float-right">80%</span></h4>
                                    <div class="progress mb-4">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 80%"
                                            aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <h4 class="small font-weight-bold">Account Setup <span
                                            class="float-right">Complete!</span></h4>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"
                                            aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Color System -->
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-primary text-white shadow">
                                        <div class="card-body">
                                            Primary
                                            <div class="text-white-50 small">#4e73df</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-success text-white shadow">
                                        <div class="card-body">
                                            Success
                                            <div class="text-white-50 small">#1cc88a</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-info text-white shadow">
                                        <div class="card-body">
                                            Info
                                            <div class="text-white-50 small">#36b9cc</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-warning text-white shadow">
                                        <div class="card-body">
                                            Warning
                                            <div class="text-white-50 small">#f6c23e</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-danger text-white shadow">
                                        <div class="card-body">
                                            Danger
                                            <div class="text-white-50 small">#e74a3b</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-secondary text-white shadow">
                                        <div class="card-body">
                                            Secondary
                                            <div class="text-white-50 small">#858796</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-light text-black shadow">
                                        <div class="card-body">
                                            Light
                                            <div class="text-black-50 small">#f8f9fc</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="card bg-dark text-white shadow">
                                        <div class="card-body">
                                            Dark
                                            <div class="text-white-50 small">#5a5c69</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="col-lg-6 mb-4">

                            <!-- Illustrations -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Illustrations</h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center">
                                        <img class="img-fluid px-3 px-sm-4 mt-3 mb-4" style="width: 25rem;"
                                            src="img/undraw_posting_photo.svg" alt="...">
                                    </div>
                                    <p>Add some quality, svg illustrations to your project courtesy of <a
                                            target="_blank" rel="nofollow" href="https://undraw.co/">unDraw</a>, a
                                        constantly updated collection of beautiful svg images that you can use
                                        completely free and without attribution!</p>
                                    <a target="_blank" rel="nofollow" href="https://undraw.co/">Browse Illustrations on
                                        unDraw &rarr;</a>
                                </div>
                            </div>

                            <!-- Approach -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Development Approach</h6>
                                </div>
                                <div class="card-body">
                                    <p>SB Admin 2 makes extensive use of Bootstrap 4 utility classes in order to reduce
                                        CSS bloat and poor page performance. Custom CSS classes are used to create
                                        custom components and custom utility classes.</p>
                                    <p class="mb-0">Before working with this theme, you should become familiar with the
                                        Bootstrap framework, especially the utility classes.</p>
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
        labels: ["Borrowed", "Overdue", "Available", "Damaged"],
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
        legend: { display: false },
        cutoutPercentage: 80
      }
    });

    // Area Chart Borrowings Overview
    const borrowingsLabels = <?= json_encode($borrowingsLabels); ?>;
    const borrowingsCounts = <?= json_encode($borrowingsCounts); ?>;
    
    var ctxArea = document.getElementById("myAreaChart");
    var myAreaChart = new Chart(ctxArea, {
        type: 'line',
        data: {
            labels: borrowingsLabels,
            datasets: [{
                label: 'Daily Borrowings',
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
                pointBorderWidth: 2
            }]
        },
        options: {
            maintainAspectRatio: false,
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
                display: false
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
                displayColors: false,
                intersect: false,
                mode: 'index',
                caretPadding: 10,
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

    // Area Chart Added Books Overview
    const booksLabels = <?= json_encode($booksLabels); ?>;
    const booksCounts = <?= json_encode($booksCounts); ?>;
    
    var ctxBooks = document.getElementById("myBooksChart");
    var myBooksChart = new Chart(ctxBooks, {
        type: 'line',
        data: {
            labels: booksLabels,
            datasets: [{
                label: 'Daily Added Books',
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
                pointBorderWidth: 2
            }]
        },
        options: {
            maintainAspectRatio: false,
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
                display: false
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
                displayColors: false,
                intersect: false,
                mode: 'index',
                caretPadding: 10,
            }
        }
    });

    document.getElementById('exportButton').addEventListener('click', function() {
        const exportType = prompt('Enter export type (current_month, previous_month, last_year, current_year):', 'current_month');
        if (exportType) {
            window.location.href = `export_borrowings.php?type=${exportType}`;
        }
    });
    </script>