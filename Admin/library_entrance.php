<?php
session_start();
require_once '../db.php';

// Initialize variables
$message = '';
$status = '';
$user_data = null;
$action = isset($_POST['action']) ? $_POST['action'] : 'entrance';

// Function to check the user's recent activity
function getRecentActivity($conn, $student_id)
{
    $sql = "SELECT status FROM library_visits 
            WHERE student_number = ? 
            ORDER BY time DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recent_activity = $result->fetch_assoc();
    $stmt->close();
    return $recent_activity ? $recent_activity['status'] : null;
}

// Process entrance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action == 'entrance') {
    $student_id = $_POST['student_id'];
    $purpose = isset($_POST['purpose']) ? $_POST['purpose'] : 'Study'; // Get selected purpose

    // Validate student ID
    if (empty($student_id)) {
        $message = "Please enter your Student/Employee ID";
        $status = "error";
    } else {
        $recent_activity = getRecentActivity($conn, $student_id);

        if ($recent_activity === '1') { // Recent activity is entrance
            $message = "You already have an active entrance. Please exit first.";
            $status = "warning";
        } else {
            // Check if user exists in the users table
            $sql = "SELECT * FROM users WHERE school_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user_data = $result->fetch_assoc();

                // Log the library entrance with selected purpose
                $log_sql = "INSERT INTO library_visits (student_number, time, status, purpose) 
                           VALUES (?, NOW(), 1, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("is", $student_id, $purpose);

                if ($log_stmt->execute()) {
                    $message = "Welcome to the library, " . $user_data['firstname'] . " " . $user_data['lastname'] . "!";
                    $status = "success";
                } else {
                    $message = "Error logging your entry. Please try again.";
                    $status = "error";
                }
                $log_stmt->close();
            } else {
                $message = "Invalid ID. Student/Employee not found in records.";
                $status = "error";
            }
            $stmt->close();
        }
    }
}

// Process exit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action == 'exit') {
    $student_id = $_POST['student_id'];

    // Validate student ID
    if (empty($student_id)) {
        $message = "Please enter your Student/Employee ID";
        $status = "error";
    } else {
        $recent_activity = getRecentActivity($conn, $student_id);

        if ($recent_activity === '0') { // Recent activity is exit
            $message = "You already have an active exit. Please enter first.";
            $status = "warning";
        } else {
            // Check if user exists in the database
            $sql = "SELECT * FROM users WHERE school_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user_data = $result->fetch_assoc();

                // Instead of updating existing record, insert a new EXIT record with status 0
                $insert_sql = "INSERT INTO library_visits (student_number, time, status, purpose) 
                               VALUES (?, NOW(), 0, 'Exit')";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("i", $student_id);

                if ($insert_stmt->execute()) {
                    $message = "Thank you for visiting, " . $user_data['firstname'] . " " . $user_data['lastname'] . "!";
                    $status = "success";
                } else {
                    $message = "Error logging your exit. Please try again.";
                    $status = "error";
                }
                $insert_stmt->close();
            } else {
                $message = "Invalid ID. Student/Employee not found in records.";
                $status = "error";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Access - NBSC Library</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../Admin/img/nbslogo.png">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="../Admin/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)), url('../Images/BG/library-background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: block;
            margin: 0;
            padding: 1rem;
            font-family: 'Nunito', sans-serif;
        }

        .login-container {
            max-width: 1000px;
            width: 100%;
            padding: 2rem;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            margin: 2rem auto;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo-container img {
            max-height: 100px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
            transition: transform 0.3s ease;
        }

        .logo-container img:hover {
            transform: scale(1.05);
        }

        h1 {
            text-align: center;
            font-weight: 800;
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            margin-bottom: 0.5rem;
        }

        h1.entrance-title {
            color: #4e73df;
        }

        h1.exit-title {
            color: #e74a3b;
        }

        .info-text {
            text-align: center;
            color: #5a5c69;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .clock-section {
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .entrance-section .clock-section {
            background-color: rgba(78, 115, 223, 0.1);
        }

        .exit-section .clock-section {
            background-color: rgba(231, 74, 59, 0.1);
        }

        .clock {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .entrance-section .clock {
            color: #4e73df;
        }

        .exit-section .clock {
            color: #e74a3b;
        }

        .date {
            font-size: 1.2rem;
            color: #5a5c69;
        }

        .instruction {
            text-align: center;
            font-size: 1.1rem;
            color: #5a5c69;
            margin-bottom: 1.5rem;
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .entrance-section .instruction {
            background-color: rgba(78, 115, 223, 0.05);
            border-left: 4px solid #4e73df;
        }

        .exit-section .instruction {
            background-color: rgba(231, 74, 59, 0.05);
            border-left: 4px solid #e74a3b;
        }

        .form-control,
        .form-select {
            height: 60px;
            font-size: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .entrance-section .form-control,
        .entrance-section .form-select {
            border: 2px solid #4e73df;
        }

        .exit-section .form-control {
            border: 2px solid #e74a3b;
        }

        .form-select {
            padding-left: 1.5rem;
            background-position: right 1.5rem center;
        }

        .entrance-section .form-control:focus,
        .entrance-section .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
            border-color: #4e73df;
        }

        .exit-section .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(231, 74, 59, 0.25);
            border-color: #e74a3b;
        }

        .btn {
            font-size: 1.25rem;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }

        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }

        .btn-danger {
            background-color: #e74a3b;
            border-color: #e74a3b;
        }

        .btn-danger:hover {
            background-color: #c23321;
            border-color: #bd2130;
        }

        .recent-entries {
            margin-top: 2rem;
            border-radius: 0.75rem;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .entrance-section .entries-header {
            background-color: #4e73df;
        }

        .exit-section .entries-header {
            background-color: #e74a3b;
        }

        .entries-header {
            color: white;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .entry-list {
            max-height: 300px;
            overflow-y: auto;
            padding: 0.5rem 1rem;
        }

        .entry-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .entrance-section .entry-item {
            background-color: rgba(78, 115, 223, 0.05);
        }

        .entrance-section .entry-item:hover {
            background-color: rgba(78, 115, 223, 0.1);
            transform: translateX(5px);
        }

        .exit-section .entry-item {
            background-color: rgba(231, 74, 59, 0.05);
        }

        .exit-section .entry-item:hover {
            background-color: rgba(231, 74, 59, 0.1);
            transform: translateX(5px);
        }

        .entry-name {
            font-weight: 600;
            color: #5a5c69;
        }

        .entry-time {
            color: #858796;
            font-size: 0.9rem;
        }

        .entry-id {
            color: #858796;
            font-size: 0.9rem;
            font-family: monospace;
        }

        .admin-links {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }

        .admin-link {
            padding: 0.75rem 1.25rem;
            background-color: rgba(78, 115, 223, 0.9);
            color: white;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .admin-link:hover {
            background-color: rgba(46, 89, 217, 1);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .admin-link i {
            margin-right: 0.5rem;
        }

        .toggle-links {
            display: flex;
            margin-bottom: 1.5rem;
            background-color: #eaecf4;
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .toggle-link {
            flex: 1;
            text-align: center;
            padding: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #5a5c69;
            cursor: pointer;
        }

        .toggle-link.active {
            color: white;
        }

        .toggle-link.entrance.active {
            background-color: #4e73df;
        }

        .toggle-link.exit.active {
            background-color: #e74a3b;
        }

        .toggle-link:first-child {
            border-right: 1px solid rgba(0, 0, 0, 0.1);
        }

        .toggle-link.entrance:hover:not(.active) {
            background-color: rgba(78, 115, 223, 0.1);
        }

        .toggle-link.exit:hover:not(.active) {
            background-color: rgba(231, 74, 59, 0.1);
        }

        /* Centering improvements */
        .centered-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .form-section {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
        }

        .toggle-container {
            max-width: 450px;
            width: 100%;
            margin: 0 auto 1.5rem auto;
        }

        .entries-container {
            max-width: 800px;
            width: 100%;
            margin: 2rem auto 0 auto;
        }

        /* Initially hide the exit section */
        .exit-section {
            display: none;
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            .login-container {
                padding: 1.5rem;
                margin: 1rem;
            }

            .clock {
                font-size: 2rem;
            }

            .form-control,
            .form-select {
                height: 50px;
                font-size: 1.25rem;
            }

            .btn {
                font-size: 1.1rem;
                padding: 0.6rem 1.2rem;
            }

            .admin-links {
                flex-direction: column;
                bottom: 10px;
                right: 10px;
                gap: 5px;
            }

            .admin-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="login-container animate__animated animate__fadeIn">
            <div class="toggle-container">
                <div class="toggle-links">
                    <div class="toggle-link entrance active" id="entranceTab">
                        <i class="fas fa-sign-in-alt me-2"></i> Entrance
                    </div>
                    <div class="toggle-link exit" id="exitTab">
                        <i class="fas fa-sign-out-alt me-2"></i> Exit
                    </div>
                </div>
            </div>

            <!-- Entrance Section -->
            <div class="entrance-section" id="entranceSection">
                <div class="centered-content">
                    <div class="logo-container">
                        <img src="inc/img/horizontal-nbs-logo.png" alt="NBSC Library Logo" class="img-fluid animate__animated animate__pulse animate__infinite animate__slower">
                    </div>

                    <h1 class="mb-2 entrance-title">NBSC LIBRARY</h1>
                    <p class="info-text">Student & Faculty Entry Management System</p>

                    <div class="form-section">
                        <div class="clock-section animate__animated animate__fadeIn">
                            <div class="clock entrance-clock" id="entranceClock">00:00:00</div>
                            <div class="date" id="entranceDate">Loading date...</div>
                        </div>

                        <div class="instruction animate__animated animate__fadeIn animate__delay-1s">
                            <i class="fas fa-info-circle me-2"></i>
                            Please scan or enter your Student/Employee ID to log your library entry
                        </div>

                        <form method="POST" action="" id="entranceForm" class="animate__animated animate__fadeIn animate__delay-1s">
                            <input type="hidden" name="action" value="entrance">
                            <div class="form-group">
                                <input type="text" class="form-control" id="entrance_student_id" name="student_id"
                                    placeholder="Scan or Enter ID" autocomplete="off">
                            </div>

                            <!-- Purpose dropdown -->
                            <div class="form-group mb-3">
                                <select class="form-select form-select-lg" name="purpose" id="purpose">
                                    <option value="Computer Use">Computer Use</option>
                                    <option value="Access WiFi">Access WiFi</option>
                                    <option value="Borrow/Return Books">Borrow/Return Books</option>
                                    <option value="Clearance">Clearance</option>
                                    <option value="Group Meeting/Discussion">Group Meeting/Discussion</option>
                                    <option value="Online Class">Online Class</option>
                                    <option value="Pass Time/Rest">Pass Time/Rest</option>
                                    <option value="Read Books">Read Books</option>
                                    <option value="Research">Research</option>
                                    <option value="Review">Review</option>
                                    <option value="Seatwork">Seatwork</option>
                                    <option value="Study" selected>Study</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt me-2"></i> Enter Library
                            </button>
                        </form>
                    </div>

                    <div class="entries-container">
                        <div class="recent-entries animate__animated animate__fadeIn animate__delay-2s">
                            <div class="entries-header">
                                <i class="fas fa-history me-2"></i> Recent Entries
                            </div>
                            <div class="entry-list" id="recentEntries">
                                <!-- Recent entries will be loaded here -->
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exit Section -->
            <div class="exit-section" id="exitSection">
                <div class="centered-content">
                    <div class="logo-container">
                        <img src="inc/img/horizontal-nbs-logo.png" alt="NBSC Library Logo" class="img-fluid animate__animated animate__pulse animate__infinite animate__slower">
                    </div>

                    <h1 class="mb-2 exit-title">LIBRARY EXIT</h1>
                    <p class="info-text">Student & Faculty Exit Management System</p>

                    <div class="form-section">
                        <div class="clock-section animate__animated animate__fadeIn">
                            <div class="clock exit-clock" id="exitClock">00:00:00</div>
                            <div class="date" id="exitDate">Loading date...</div>
                        </div>

                        <div class="instruction animate__animated animate__fadeIn animate__delay-1s">
                            <i class="fas fa-info-circle me-2"></i>
                            Please scan or enter your Student/Employee ID to log your library exit
                        </div>

                        <form method="POST" action="" id="exitForm" class="animate__animated animate__fadeIn animate__delay-1s">
                            <input type="hidden" name="action" value="exit">
                            <div class="form-group">
                                <input type="text" class="form-control" id="exit_student_id" name="student_id"
                                    placeholder="Scan or Enter ID" autocomplete="off">
                            </div>
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-sign-out-alt me-2"></i> Exit Library
                            </button>
                        </form>
                    </div>

                    <div class="entries-container">
                        <div class="recent-entries animate__animated animate__fadeIn animate__delay-2s">
                            <div class="entries-header">
                                <i class="fas fa-history me-2"></i> Recent Exits
                            </div>
                            <div class="entry-list" id="recentExits">
                                <!-- Recent exits will be loaded here -->
                                <div class="text-center py-4">
                                    <div class="spinner-border text-danger" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-links animate__animated animate__fadeIn animate__delay-3s">
        <a href="index.php" class="admin-link">
            <i class="fas fa-user-shield"></i> Admin Login
        </a>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Update clock and date
        function updateTime() {
            const now = new Date();

            // Update clock
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            // Update both clocks
            document.getElementById('entranceClock').textContent = `${hours}:${minutes}:${seconds}`;
            document.getElementById('exitClock').textContent = `${hours}:${minutes}:${seconds}`;

            // Update date
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const dateString = now.toLocaleDateString('en-US', options);
            document.getElementById('entranceDate').textContent = dateString;
            document.getElementById('exitDate').textContent = dateString;
        }

        // Update time every second
        setInterval(updateTime, 1000);
        updateTime(); // Initial call

        // Toggle between entrance and exit tabs
        document.getElementById('entranceTab').addEventListener('click', function() {
            document.getElementById('entranceSection').style.display = 'block';
            document.getElementById('exitSection').style.display = 'none';
            this.classList.add('active');
            document.getElementById('exitTab').classList.remove('active');
            document.getElementById('entrance_student_id').focus();
            loadRecentEntries(); // Refresh entries
        });

        document.getElementById('exitTab').addEventListener('click', function() {
            document.getElementById('entranceSection').style.display = 'none';
            document.getElementById('exitSection').style.display = 'block';
            this.classList.add('active');
            document.getElementById('entranceTab').classList.remove('active');
            document.getElementById('exit_student_id').focus();
            loadRecentExits(); // Refresh exits
        });

        // Auto-submit when ID is scanned
        document.getElementById('entrance_student_id').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('entranceForm').submit();
            }
        });

        document.getElementById('exit_student_id').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('exitForm').submit();
            }
        });

        // Auto-focus based on active tab
        function focusActiveInput() {
            if (document.getElementById('entranceSection').style.display !== 'none') {
                document.getElementById('entrance_student_id').focus();
            } else {
                document.getElementById('exit_student_id').focus();
            }
        }

        // Initial focus and periodic refocus
        window.onload = function() {
            focusActiveInput();

            // Re-focus after 5 seconds if it loses focus
            setInterval(focusActiveInput, 5000);
        };

        // Function to load recent entries
        function loadRecentEntries() {
            fetch('get_recent_entries.php')
                .then(response => response.json())
                .then(data => {
                    const entriesContainer = document.getElementById('recentEntries');

                    if (data.success && data.entries && data.entries.length > 0) {
                        entriesContainer.innerHTML = '';

                        data.entries.forEach(entry => {
                            const entryTime = new Date(entry.entry_time);
                            const timeString = entryTime.toLocaleTimeString('en-US', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                            const entryItem = document.createElement('div');
                            entryItem.className = 'entry-item animate__animated animate__fadeInRight';

                            entryItem.innerHTML = `
                                <div>
                                    <span class="entry-name">${entry.firstname} ${entry.lastname}</span>
                                    <br>
                                    <span class="entry-id">${entry.student_id}</span>
                                    <br>
                                    <small class="text-muted">${entry.purpose || 'Study'}</small>
                                </div>
                                <div class="entry-time">
                                    <i class="far fa-clock me-1"></i>${timeString}
                                </div>
                            `;

                            entriesContainer.appendChild(entryItem);
                        });
                    } else {
                        entriesContainer.innerHTML = '<p class="text-center py-4">No recent entries</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading entries:', error);
                    document.getElementById('recentEntries').innerHTML =
                        '<p class="text-center text-danger py-4">Error loading entries</p>';
                });
        }

        // Function to load recent exits
        function loadRecentExits() {
            fetch('get_recent_exits.php')
                .then(response => response.json())
                .then(data => {
                    const exitsContainer = document.getElementById('recentExits');

                    if (data.success && data.exits && data.exits.length > 0) {
                        exitsContainer.innerHTML = '';

                        data.exits.forEach(exit => {
                            const exitTime = new Date(exit.exit_time);
                            const timeString = exitTime.toLocaleTimeString('en-US', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                            const entryTime = new Date(exit.entry_time);
                            const entryTimeString = entryTime.toLocaleTimeString('en-US', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                            // Calculate duration in minutes
                            const durationMs = exitTime - entryTime;
                            const durationMinutes = Math.floor(durationMs / 60000);
                            const durationHours = Math.floor(durationMinutes / 60);
                            const remainingMinutes = durationMinutes % 60;

                            let durationString = '';
                            if (durationHours > 0) {
                                durationString = `${durationHours}h ${remainingMinutes}m`;
                            } else {
                                durationString = `${durationMinutes}m`;
                            }

                            const exitItem = document.createElement('div');
                            exitItem.className = 'entry-item animate__animated animate__fadeInRight';

                            exitItem.innerHTML = `
                                <div>
                                    <span class="entry-name">${exit.firstname} ${exit.lastname}</span>
                                    <br>
                                    <span class="entry-id">${exit.student_id}</span>
                                </div>
                                <div class="entry-time">
                                    <i class="far fa-clock me-1"></i>${timeString}
                                    <br>
                                    <small>Duration: ${durationString}</small>
                                </div>
                            `;

                            exitsContainer.appendChild(exitItem);
                        });
                    } else {
                        exitsContainer.innerHTML = '<p class="text-center py-4">No recent exits</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading exits:', error);
                    document.getElementById('recentExits').innerHTML =
                        '<p class="text-center text-danger py-4">Error loading exit data</p>';
                });
        }

        // Load initial data and refresh periodically
        loadRecentEntries();
        loadRecentExits();
        setInterval(loadRecentEntries, 30000);
        setInterval(loadRecentExits, 30000);

        <?php if ($status === 'success'): ?>
            // Show success message with appropriate icon and input focus based on action
            Swal.fire({
                title: '<?php echo $action === 'entrance' ? 'Welcome!' : 'Goodbye!'; ?>',
                text: '<?php echo $message; ?>',
                icon: 'success',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => {
                if ('<?php echo $action; ?>' === 'entrance') {
                    document.getElementById('entrance_student_id').value = '';
                    document.getElementById('entrance_student_id').focus();
                    loadRecentEntries();
                } else {
                    document.getElementById('exit_student_id').value = '';
                    document.getElementById('exit_student_id').focus();
                    loadRecentExits();
                }
            });
        <?php elseif ($status === 'error' || $status === 'warning'): ?>
            // Show error message with appropriate focus based on action
            Swal.fire({
                title: '<?php echo $status === 'warning' ? 'Warning' : 'Error'; ?>',
                text: '<?php echo $message; ?>',
                icon: '<?php echo $status; ?>',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => {
                if ('<?php echo $action; ?>' === 'entrance') {
                    document.getElementById('entrance_student_id').value = '';
                    document.getElementById('entrance_student_id').focus();
                } else {
                    document.getElementById('exit_student_id').value = '';
                    document.getElementById('exit_student_id').focus();
                }
            });
        <?php endif; ?>

        // Set the correct tab active based on the form that was submitted
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            if ('<?php echo $action; ?>' === 'exit') {
                document.getElementById('exitTab').click();
            } else {
                document.getElementById('entranceTab').click();
            }
        <?php endif; ?>
    </script>
</body>

</html>