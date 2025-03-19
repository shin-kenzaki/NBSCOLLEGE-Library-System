<?php
session_start();
include '../db.php';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin'])) {
    header("Location: index.php");
    exit();
}

// Get data for the past 7 days
$today = date('Y-m-d');
$past7Days = [];
$labels = [];

// Generate last 7 days dates
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $past7Days[] = $date;
    $labels[] = date('M d', strtotime("-$i days"));
}

// Get borrowings data for last 7 days - multiple metrics
$activeBorrowings = [];
$returnedBorrowings = [];
$damagedBorrowings = [];
$lostBorrowings = [];

foreach ($past7Days as $day) {
    // Active borrowings on this day
    $sql = "SELECT COUNT(*) AS count FROM borrowings WHERE DATE(issue_date) = '$day' AND status = 'Active'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $activeBorrowings[] = $row['count'];
    
    // Returned borrowings on this day
    $sql = "SELECT COUNT(*) AS count FROM borrowings WHERE DATE(return_date) = '$day' AND status = 'Returned'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $returnedBorrowings[] = $row['count'];
    
    // Damaged borrowings on this day
    $sql = "SELECT COUNT(*) AS count FROM borrowings WHERE DATE(report_date) = '$day' AND status = 'Damaged'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $damagedBorrowings[] = $row['count'];
    
    // Lost borrowings on this day
    $sql = "SELECT COUNT(*) AS count FROM borrowings WHERE DATE(report_date) = '$day' AND status = 'Lost'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $lostBorrowings[] = $row['count'];
}

// Get reservations data for last 7 days - multiple metrics
$pendingReservations = [];
$readyReservations = [];
$recievedReservations = [];
$cancelledReservations = [];

foreach ($past7Days as $day) {
    // Pending reservations made on this day
    $sql = "SELECT COUNT(*) AS count FROM reservations WHERE DATE(reserve_date) = '$day' AND status = 'Pending'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $pendingReservations[] = $row['count'];
    
    // Ready reservations made on this day
    $sql = "SELECT COUNT(*) AS count FROM reservations WHERE DATE(ready_date) = '$day' AND status = 'Ready'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $readyReservations[] = $row['count'];
    
     // Recieved reservations made on this day
    $sql = "SELECT COUNT(*) AS count FROM reservations WHERE DATE(recieved_date) = '$day' AND status = 'Recieved'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $recievedReservations[] = $row['count'];
    
    // Cancelled reservations made on this day
    $sql = "SELECT COUNT(*) AS count FROM reservations WHERE DATE(cancel_date) = '$day' AND status = 'Cancelled'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $cancelledReservations[] = $row['count'];
}

// Get users data for last 7 days - multiple metrics
$adminCounts = [];
$librarianCounts = [];
$assistantCounts = [];
$encoderCounts = [];
$studentCounts = [];
$facultyCounts = [];
$staffCounts = [];
$visitorCounts = [];

foreach ($past7Days as $day) {
    // Admin counts
    $sql = "SELECT COUNT(*) AS count FROM admins WHERE DATE(date_added) = '$day' AND role = 'Admin'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $adminCounts[] = $row['count'];

    // Librarian counts
    $sql = "SELECT COUNT(*) AS count FROM admins WHERE DATE(date_added) = '$day' AND role = 'Librarian'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $librarianCounts[] = $row['count'];

    // Assistant counts
    $sql = "SELECT COUNT(*) AS count FROM admins WHERE DATE(date_added) = '$day' AND role = 'Assistant'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $assistantCounts[] = $row['count'];

    // Encoder counts
    $sql = "SELECT COUNT(*) AS count FROM admins WHERE DATE(date_added) = '$day' AND role = 'Encoder'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $encoderCounts[] = $row['count'];
    
    // Student users added on this day
    $sql = "SELECT COUNT(*) AS count FROM users WHERE date_added = '$day' AND usertype = 'Student'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $studentCounts[] = $row['count'];
    
    // Faculty users added on this day
    $sql = "SELECT COUNT(*) AS count FROM users WHERE date_added = '$day' AND usertype = 'Faculty'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $facultyCounts[] = $row['count'];

    // Staff users added on this day
    $sql = "SELECT COUNT(*) AS count FROM users WHERE date_added = '$day' AND usertype = 'Staff'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $staffCounts[] = $row['count'];

    // Visitor users added on this day
    $sql = "SELECT COUNT(*) AS count FROM users WHERE date_added = '$day' AND usertype = 'Visitor'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $visitorCounts[] = $row['count'];
}

// Get books data for last 7 days - multiple metrics
$addedBooks = [];
foreach ($past7Days as $day) {
    // Count added books based on date_added
    $sql = "SELECT COUNT(*) AS count FROM books WHERE DATE(date_added) = '$day'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $addedBooks[] = $row['count'];
}

// Update Books JSON data with only the added books metric
$booksJSON = json_encode([
    'added' => $addedBooks
]);

// Get fines data for last 7 days - multiple metrics
$paidFines = [];
$unpaidFines = [];
foreach ($past7Days as $day) {
    // Paid fines added on this day
    $sql = "SELECT COUNT(*) AS count FROM fines WHERE DATE(date) = '$day' AND status = 'Paid'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $paidFines[] = $row['count'];
    
    // Unpaid fines added on this day
    $sql = "SELECT COUNT(*) AS count FROM fines WHERE DATE(date) = '$day' AND status = 'Unpaid'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $unpaidFines[] = $row['count'];
}

// Update fines JSON data with only paid and unpaid
$finesJSON = json_encode([
    'paid' => $paidFines,
    'unpaid' => $unpaidFines
]);

// Convert data arrays to JSON for JavaScript
$labelsJSON = json_encode($labels);

// Borrowings JSON data
$borrowingsJSON = json_encode([
    'active' => $activeBorrowings,
    'returned' => $returnedBorrowings,
    'damaged' => $damagedBorrowings,
    'lost' => $lostBorrowings
]);

// Reservations JSON data
$reservationsJSON = json_encode([
    'pending' => $pendingReservations,
    'ready' => $readyReservations,
    'recieved' => $recievedReservations,
    'cancelled' => $cancelledReservations
]);

// Users JSON data
$usersJSON = json_encode([
    'admin' => $adminCounts,
    'librarian' => $librarianCounts,
    'assistant' => $assistantCounts,
    'encoder' => $encoderCounts,
    'students' => $studentCounts,
    'faculty' => $facultyCounts,
    'staff' => $staffCounts,
    'visitor' => $visitorCounts
]);

// Books JSON data
$booksJSON = json_encode([
    'added' => $addedBooks
]);

// Fines JSON data
$finesJSON = json_encode([
    'paid' => $paidFines,
    'unpaid' => $unpaidFines
]);

include 'inc/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Reports</h1>

    <ul class="nav nav-tabs" id="myTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="borrowings-tab" data-toggle="tab" href="#borrowings" role="tab" aria-controls="borrowings" aria-selected="true">Borrowings</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="reservations-tab" data-toggle="tab" href="#reservations" role="tab" aria-controls="reservations" aria-selected="false">Reservations</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="users-tab" data-toggle="tab" href="#users" role="tab" aria-controls="users" aria-selected="false">Admins/Users</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="books-tab" data-toggle="tab" href="#books" role="tab" aria-controls="books" aria-selected="false">Books</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="fines-tab" data-toggle="tab" href="#fines" role="tab" aria-controls="fines" aria-selected="false">Fines</a>
        </li>
    </ul>

    <div class="tab-content" id="myTabsContent">
        <div class="tab-pane fade show active" id="borrowings" role="tabpanel" aria-labelledby="borrowings-tab">
            <!-- Borrowings Report Content -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Borrowings Report - Last 7 Days</h6>
                    <div>
                        <button class="btn btn-sm btn-primary" id="downloadBorrowingsReport">Download Report</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="borrowingsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="reservations" role="tabpanel" aria-labelledby="reservations-tab">
            <!-- Reservations Report Content -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Reservations Report - Last 7 Days</h6>
                    <div>
                        <button class="btn btn-sm btn-primary" id="downloadReservationsReport">Download Report</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="reservationsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
            <!-- Users Report Content -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Users Report - Last 7 Days</h6>
                    <div>
                        <button class="btn btn-sm btn-primary" id="downloadUsersReport">Download Report</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="usersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="books" role="tabpanel" aria-labelledby="books-tab">
            <!-- Books Report Content -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Books Report - Last 7 Days</h6>
                    <div>
                        <button class="btn btn-sm btn-primary" id="downloadBooksReport">Download Report</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="booksChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="fines" role="tabpanel" aria-labelledby="fines-tab">
            <!-- Fines Report Content -->
            <div class="card shadow mt-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Fines Report - Last 7 Days</h6>
                    <div>
                        <button class="btn btn-sm btn-primary" id="downloadFinesReport">Download Report</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 300px;">
                        <canvas id="finesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Updated Line chart configuration function for multiple datasets
function createMultiLineChart(canvasId, labels, datasets, title) {
    var ctx = document.getElementById(canvasId).getContext('2d');
    
    // Chart colors - fixed colors for borrowings
    const colors = {
        active: 'rgba(78, 115, 223, 1)',      // Primary (blue)
        returned: 'rgba(28, 200, 138, 1)',    // Success (green)
        damaged: 'rgba(246, 194, 62, 1)',     // Warning (yellow)
        lost: 'rgba(231, 74, 59, 1)',         // Danger (red)
        
        // Colors for other charts
        total: 'rgba(78, 115, 223, 1)',
        pending: 'rgba(246, 194, 62, 1)',
        ready: 'rgba(54, 185, 204, 1)',
        fulfilled: 'rgba(28, 200, 138, 1)',
        students: 'rgba(153, 102, 255, 1)',
        faculty: 'rgba(255, 159, 64, 1)',
        available: 'rgba(28, 200, 138, 1)',
        borrowed: 'rgba(78, 115, 223, 1)',
        paid: 'rgba(28, 200, 138, 1)',
        unpaid: 'rgba(231, 74, 59, 1)',
        amounts: 'rgba(54, 185, 204, 1)',
        overdue: 'rgba(231, 74, 59, 1)',
        recieved: 'rgba(28, 200, 138, 1)',
        cancelled: 'rgba(231, 74, 59, 1)',
        admin: 'rgba(78, 115, 223, 1)',
        librarian: 'rgba(28, 200, 138, 1)',
        assistant:  'rgba(246, 194, 62, 1)',
        encoder: 'rgba(54, 185, 204, 1)',
        staff: 'rgba(153, 102, 255, 1)',
        visitor: 'rgba(255, 159, 64, 1)'
    };
    
    // Create chart datasets array
    const chartDatasets = [];
    
    // Special handling for borrowings chart to ensure correct colors
    if (canvasId === 'borrowingsChart') {
        // Add datasets in specific order with specific colors
        if (datasets.active) {
            chartDatasets.push(createDataset('Active', datasets.active, colors.active));
        }
        if (datasets.returned) {
            chartDatasets.push(createDataset('Returned', datasets.returned, colors.returned));
        }
        if (datasets.damaged) {
            chartDatasets.push(createDataset('Damaged', datasets.damaged, colors.damaged));
        }
        if (datasets.lost) {
            chartDatasets.push(createDataset('Lost', datasets.lost, colors.lost));
        }
    } else if (canvasId === 'reservationsChart') {
        if (datasets.pending) {
            chartDatasets.push(createDataset('Pending', datasets.pending, colors.pending));
        }
        if (datasets.ready) {
            chartDatasets.push(createDataset('Ready', datasets.ready, colors.ready));
        }
         if (datasets.recieved) {
            chartDatasets.push(createDataset('Recieved', datasets.recieved, colors.recieved));
        }
        if (datasets.cancelled) {
            chartDatasets.push(createDataset('Cancelled', datasets.cancelled, colors.cancelled));
        }
    } else if (canvasId === 'usersChart') {
         if (datasets.admin) {
            chartDatasets.push(createDataset('Admin', datasets.admin, colors.admin));
        }
        if (datasets.librarian) {
            chartDatasets.push(createDataset('Librarian', datasets.librarian, colors.librarian));
        }
         if (datasets.assistant) {
            chartDatasets.push(createDataset('Assistant', datasets.assistant, colors.assistant));
        }
        if (datasets.encoder) {
            chartDatasets.push(createDataset('Encoder', datasets.encoder, colors.encoder));
        }
        if (datasets.students) {
            chartDatasets.push(createDataset('Students', datasets.students, colors.students));
        }
        if (datasets.faculty) {
            chartDatasets.push(createDataset('Faculty', datasets.faculty, colors.faculty));
        }
         if (datasets.staff) {
            chartDatasets.push(createDataset('Staff', datasets.staff, colors.staff));
        }
        if (datasets.visitor) {
            chartDatasets.push(createDataset('Visitor', datasets.visitor, colors.visitor));
        }
    } else if (canvasId === 'booksChart') {
        // ----- Modified Books Chart Section -----
        if (datasets.added) {
            chartDatasets.push(createDataset('Added Books', datasets.added, colors.total));
        }
    } else if (canvasId === 'finesChart') {
        // Only show Paid and Unpaid lines
        if (datasets.paid) {
            chartDatasets.push(createDataset('Paid Fines', datasets.paid, colors.paid));
        }
        if (datasets.unpaid) {
            chartDatasets.push(createDataset('Unpaid Fines', datasets.unpaid, colors.unpaid));
        }
    } else {
        // For other charts, use the general approach
        for (const [key, data] of Object.entries(datasets)) {
            // Capitalize first letter of key for label
            const label = key.charAt(0).toUpperCase() + key.slice(1);
            
            // Get color, use predefined if available or default to blue
            const color = colors[key] || 'rgba(78, 115, 223, 1)';
            
            chartDatasets.push(createDataset(label, data, color));
        }
    }
    
    function createDataset(label, data, color) {
        return {
            label: label,
            data: data,
            backgroundColor: color.replace("1)", "0.1)"),
            borderColor: color,
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: color,
            pointBorderColor: color,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: color,
            pointHitRadius: 10,
            pointBorderWidth: 2,
            tension: 0.3,
            fill: false
        };
    }
    
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: chartDatasets
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
                        maxTicksLimit: 7
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        precision: 0,
                        min: 0
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
            title: {
                display: title ? true : false,
                text: title || ''
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
                caretPadding: 10
            }
        }
    });
}

// Data from PHP
var chartLabels = <?= $labelsJSON ?>;
var borrowingsData = <?= $borrowingsJSON ?>;
var reservationsData = <?= $reservationsJSON ?>;
var usersData = <?= $usersJSON ?>;
var booksData = <?= $booksJSON ?>;
var finesData = <?= $finesJSON ?>;

// Create charts when document is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Create multi-line charts for each category
    createMultiLineChart('borrowingsChart', chartLabels, borrowingsData, 'Borrowings Activity - Last 7 Days');
    createMultiLineChart('reservationsChart', chartLabels, reservationsData, 'Reservations Activity - Last 7 Days');
    createMultiLineChart('usersChart', chartLabels, usersData, 'New Users Registration - Last 7 Days');
    createMultiLineChart('booksChart', chartLabels, booksData, 'Book Additions - Last 7 Days');
    createMultiLineChart('finesChart', chartLabels, finesData, 'Fines Activity - Last 7 Days');
});

// Handle tab switching to refresh charts
$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    // Resize charts when tab is shown
    window.dispatchEvent(new Event('resize'));
});
</script>

<?php
include 'inc/footer.php';
?>