<?php
session_start();
include '../db.php';

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Student', 'Faculty', 'Staff', 'Visitor'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user analytics from users table
$query = "SELECT borrowed_books, returned_books, damaged_books, lost_books 
          FROM users 
          WHERE id = ?";
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
        <!-- User Analytics Section -->
        <div class="row">
            <!-- Pie Chart -->
            <div class="col-xl-4 col-lg-5 mb-4">
                <div class="card shadow mb-4">
                    <!-- Card Header - Dropdown -->
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Books Status Overview</h6>
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
                        <h6 class="m-0 font-weight-bold text-primary">Books Overview Timeline</h6>
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

        <!-- Reservations Section -->
        <div class="row">
            <div class="col-xl-12 col-md-12 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <h6 class="m-0 font-weight-bold text-primary">Current Reservations</h6>
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
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
                                            <td><?php echo date('Y-m-d h:i A', strtotime($row['reserve_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart Section -->
        <div class="row">
            <div class="col-xl-12 col-md-12 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <h6 class="m-0 font-weight-bold text-primary">Active Cart Items</h6>
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Date Added</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $cart_items->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td><?php echo date('Y-m-d h:i A', strtotime($row['date'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Borrowings Section -->
        <div class="row">
            <div class="col-xl-12 col-md-12 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Borrowings</h6>
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
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
                                            <td><?php echo date('Y-m-d', strtotime($row['issue_date'])); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['return_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
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

// Replace the line chart JavaScript with:
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

// Pie Chart Example
var ctx = document.getElementById("myPieChart");
var myPieChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ["Borrowed", "Returned", "Damaged", "Lost"],
        datasets: [{
            data: [<?php echo $analytics['borrowed_books']; ?>,
                   <?php echo $analytics['returned_books']; ?>, 
                   <?php echo $analytics['damaged_books']; ?>, 
                   <?php echo $analytics['lost_books']; ?>],
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