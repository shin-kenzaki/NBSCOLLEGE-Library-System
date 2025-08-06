<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user analytics from borrowings table instead of users table
$query = "SELECT 
          COUNT(CASE WHEN status IN ('Active', 'Overdue') THEN 1 END) as borrowed_books,
          COUNT(CASE WHEN status = 'Returned' THEN 1 END) as returned_books,
          COUNT(CASE WHEN status = 'Damaged' THEN 1 END) as damaged_books,
          COUNT(CASE WHEN status = 'Lost' THEN 1 END) as lost_books
          FROM borrowings 
          WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$analytics = $result->fetch_assoc();

// Fetch reservations
$reservation_query = "SELECT b.title, r.reserve_date, r.status 
                      FROM reservations r 
                      JOIN books b ON r.book_id = b.id 
                      WHERE r.user_id = ? AND (r.status = 'Pending' OR r.status = 'Ready')";
$reservation_stmt = $conn->prepare($reservation_query);
$reservation_stmt->bind_param('i', $user_id);
$reservation_stmt->execute();
$reservations = $reservation_stmt->get_result();

// Fetch cart items
$cart_query = "SELECT b.title, c.date 
               FROM cart c 
               JOIN books b ON c.book_id = b.id 
               WHERE c.user_id = ? AND c.status = 1";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param('i', $user_id);
$cart_stmt->execute();
$cart_items = $cart_stmt->get_result();

// Fetch recent borrowings, limit to 10 items
$history_query = "SELECT b.title, br.issue_date, br.return_date, br.status 
                  FROM borrowings br 
                  JOIN books b ON br.book_id = b.id 
                  WHERE br.user_id = ? AND br.status != 'Active'
                  ORDER BY br.issue_date DESC
                  LIMIT 10";
$history_stmt = $conn->prepare($history_query);
$history_stmt->bind_param('i', $user_id);
$history_stmt->execute();
$borrowing_history = $history_stmt->get_result();

// Fetch monthly borrowing history
$monthly_query = "SELECT 
    DATE_FORMAT(issue_date, '%b') as month,
    COUNT(*) as borrowed
    FROM borrowings 
    WHERE user_id = ? 
    AND issue_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    GROUP BY MONTH(issue_date)
    ORDER BY issue_date";
$monthly_stmt = $conn->prepare($monthly_query);
$monthly_stmt->bind_param('i', $user_id);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();

// Initialize arrays for chart data
$months = [];
$borrowed = [];

while ($row = $monthly_result->fetch_assoc()) {
    $months[] = $row['month'];
    $borrowed[] = $row['borrowed'];
}

// If no data, use last 12 months
if (empty($months)) {
    $months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    $borrowed = array_fill(0, 12, 0);
}

// Fetch daily borrowing history for the current month
$daily_query = "SELECT 
    DATE_FORMAT(issue_date, '%d') as day,
    COUNT(*) as borrowed
    FROM borrowings 
    WHERE user_id = ? 
    AND issue_date >= DATE_FORMAT(NOW() ,'%Y-%m-01')
    GROUP BY DAY(issue_date)
    ORDER BY issue_date";
$daily_stmt = $conn->prepare($daily_query);
$daily_stmt->bind_param('i', $user_id);
$daily_stmt->execute();
$daily_result = $daily_stmt->get_result();

// Initialize arrays for daily chart data
$days = [];
$daily_borrowed = [];

while ($row = $daily_result->fetch_assoc()) {
    $days[] = $row['day'];
    $daily_borrowed[] = $row['borrowed'];
}

// If no data, use days of the current month
if (empty($days)) {
    $days = range(1, date('t'));
    $daily_borrowed = array_fill(0, count($days), 0);
}

include '../user/inc/header.php';
?>

<!-- Main Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">My Dashboard</h1>
            <a href="reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
            </a>
        </div>

        <!-- Content Row - Activity Summary -->
        <div class="row">
            <!-- Borrowed Books Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Currently Borrowed</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= isset($analytics['borrowed_books']) ? $analytics['borrowed_books'] : 0 ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-book fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Returned Books Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Returned Books</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= isset($analytics['returned_books']) ? $analytics['returned_books'] : 0 ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Damaged Books Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Damaged Books</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= isset($analytics['damaged_books']) ? $analytics['damaged_books'] : 0 ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lost Books Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Lost Books</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= isset($analytics['lost_books']) ? $analytics['lost_books'] : 0 ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Analytics Section -->
        <div class="row">
            <!-- Pie Chart -->
            <div class="col-xl-4 col-lg-5 mb-4">
                <div class="card shadow mb-4">
                    <!-- Card Header - Dropdown -->
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Books Status Overview</h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                 aria-labelledby="dropdownMenuLink">
                                <div class="dropdown-header">Chart Options:</div>
                                <a class="dropdown-item" href="#">View Details</a>
                                <a class="dropdown-item" href="#">Download Data</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#">View All Activities</a>
                            </div>
                        </div>
                    </div>
                    <!-- Card Body -->
                    <div class="card-body">
                        <div class="chart-pie pt-4 pb-2">
                            <canvas id="myPieChart"></canvas>
                        </div>
                        <div class="mt-4 text-center small">
                            <span class="mr-2">
                                <i class="fas fa-circle text-primary"></i> Borrowed
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

            <!-- Line Chart -->
            <div class="col-xl-8 col-lg-7 mb-4">
                <div class="card shadow mb-4">
                    <!-- Card Header -->
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Borrowing Activity This Month</h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink2"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                 aria-labelledby="dropdownMenuLink2">
                                <div class="dropdown-header">Timeline Options:</div>
                                <a class="dropdown-item" href="#">This Week</a>
                                <a class="dropdown-item" href="#">This Month</a>
                                <a class="dropdown-item" href="#">This Year</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#">Export Data</a>
                            </div>
                        </div>
                    </div>
                    <!-- Card Body -->
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="myLineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Items Row (Reservations & Cart) -->
        <div class="row">
            <!-- Current Reservations Column -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-bookmark mr-1"></i> Current Reservations
                        </h6>
                        <a href="book_reservations.php" class="btn btn-sm btn-primary">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($reservations->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Reserve Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $reservations->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['reserve_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = ($row['status'] == 'Ready') ? 'success' : 'warning';
                                                    echo '<span class="badge badge-'.$status_class.'">'.$row['status'].'</span>';
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book-open fa-3x text-gray-300 mb-3"></i>
                                <p class="text-gray-600">No active reservations found.</p>
                                <a href="searchbook.php" class="btn btn-sm btn-primary">Browse Books</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Cart Items Column -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-shopping-cart mr-1"></i> Cart Items
                        </h6>
                        <a href="cart.php" class="btn btn-sm btn-primary">
                            View Cart
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($cart_items->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Added On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $cart_items->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-gray-300 mb-3"></i>
                                <p class="text-gray-600">Your cart is empty.</p>
                                <a href="searchbook.php" class="btn btn-sm btn-primary">Add Books</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Borrowings Section -->
        <div class="row">
            <div class="col-xl-12 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-history mr-1"></i> Recent Borrowing History
                        </h6>
                        <a href="borrowing_history.php" class="btn btn-sm btn-primary">
                            View Full History
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($borrowing_history->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Borrow Date</th>
                                            <th>Return Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $borrowing_history->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['issue_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['return_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_badge = '';
                                                    switch($row['status']) {
                                                        case 'Returned':
                                                            $status_badge = 'success';
                                                            break;
                                                        case 'Damaged':
                                                            $status_badge = 'warning';
                                                            break;
                                                        case 'Lost':
                                                            $status_badge = 'danger';
                                                            break;
                                                        case 'Overdue':
                                                            $status_badge = 'danger';
                                                            break;
                                                        default:
                                                            $status_badge = 'primary';
                                                    }
                                                    echo '<span class="badge badge-'.$status_badge.'">'.$row['status'].'</span>';
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-3x text-gray-300 mb-3"></i>
                                <p class="text-gray-600">No borrowing history found.</p>
                                <a href="searchbook.php" class="btn btn-sm btn-primary">Browse Books</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservation History Section -->
        <div class="row">
            <div class="col-xl-12 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-bookmark mr-1"></i> Reservation History
                        </h6>
                        <a href="reservation_history.php" class="btn btn-sm btn-primary">
                            View All Reservations
                        </a>
                    </div>
                    <div class="card-body">
                        <?php
                        // Fetch recent reservation history, limit to 10 items
                        $reservation_history_query = "SELECT 
                            r.id, b.title, r.reserve_date, r.status,
                            CASE 
                                WHEN r.status = 'Ready' THEN r.ready_date
                                WHEN r.status = 'Cancelled' THEN r.cancel_date
                                WHEN r.status = 'Issued' THEN r.issue_date
                                ELSE NULL
                            END AS action_date
                            FROM reservations r 
                            JOIN books b ON r.book_id = b.id 
                            WHERE r.user_id = ? 
                            ORDER BY r.reserve_date DESC
                            LIMIT 10";
                        $reservation_history_stmt = $conn->prepare($reservation_history_query);
                        $reservation_history_stmt->bind_param('i', $user_id);
                        $reservation_history_stmt->execute();
                        $reservation_history = $reservation_history_stmt->get_result();
                        ?>
                        
                        <?php if ($reservation_history->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Reserve Date</th>
                                            <th>Status Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $reservation_history->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['reserve_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($row['action_date']) {
                                                        echo date('M d, Y', strtotime($row['action_date']));
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $status_badge = '';
                                                    switch($row['status']) {
                                                        case 'Ready':
                                                            $status_badge = 'success';
                                                            break;
                                                        case 'Cancelled':
                                                            $status_badge = 'danger';
                                                            break;
                                                        case 'Issued':
                                                            $status_badge = 'primary';
                                                            break;
                                                        case 'Pending':
                                                            $status_badge = 'warning';
                                                            break;
                                                        default:
                                                            $status_badge = 'secondary';
                                                    }
                                                    echo '<span class="badge badge-'.$status_badge.'">'.$row['status'].'</span>';
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-bookmark fa-3x text-gray-300 mb-3"></i>
                                <p class="text-gray-600">No reservation history found.</p>
                                <a href="searchbook.php" class="btn btn-sm btn-primary">Browse Books</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>

<!-- Page level plugins -->
<script src="vendor/chart.js/Chart.min.js"></script>

<script>
// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

function number_format(number, decimals, dec_point, thousands_sep) {
    // ... number formatting function from admin dashboard ...
    number = (number + '').replace(',', '').replace(' ', '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function(n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

// Line Chart
var ctx = document.getElementById("myLineChart");
var myLineChart = new Chart(ctx, {
    type: "line",
    data: {
        labels: <?php echo json_encode($days); ?>,
        datasets: [{
            label: "Books Borrowed",
            lineTension: 0.3,
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
            data: <?php echo json_encode($daily_borrowed); ?>,
        }],
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
                    maxTicksLimit: 5,
                    padding: 10,
                    beginAtZero: true,
                    callback: function(value, index, values) {
                        return number_format(value);
                    }
                },
                gridLines: {
                    color: "rgb(234, 236, 244)",
                    zeroLineColor: "rgb(234, 236, 244)",
                    drawBorder: false,
                    borderDash: [2],
                    zeroLineBorderDash: [2]
                }
            }],
        },
        legend: {
            display: true,
            position: 'bottom'
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
                label: function(tooltipItem, chart) {
                    var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                    return datasetLabel + ': ' + number_format(tooltipItem.yLabel);
                }
            }
        }
    }
});

// Pie Chart
var ctx = document.getElementById("myPieChart");
var myPieChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ["Borrowed", "Returned", "Damaged", "Lost"],
        datasets: [{
            data: [
                <?php echo isset($analytics['borrowed_books']) ? $analytics['borrowed_books'] : 0; ?>,
                <?php echo isset($analytics['returned_books']) ? $analytics['returned_books'] : 0; ?>, 
                <?php echo isset($analytics['damaged_books']) ? $analytics['damaged_books'] : 0; ?>, 
                <?php echo isset($analytics['lost_books']) ? $analytics['lost_books'] : 0; ?>
            ],
            backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b'],
            hoverBackgroundColor: ['#2e59d9', '#17a673', '#f4b619', '#e02d1b'],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
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
            caretPadding: 10,
        },
        legend: {
            display: false
        },
        cutoutPercentage: 80,
    },
});
</script>