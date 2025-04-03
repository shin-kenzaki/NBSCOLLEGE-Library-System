<?php
session_start();
require_once '../db.php';

// Initialize variables
$message = '';
$status = '';
$user_data = null;

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $purpose = isset($_POST['purpose']) ? $_POST['purpose'] : 'Study'; // Get selected purpose
    
    // Validate student ID
    if (empty($student_id)) {
        $message = "Please enter your Student/Employee ID";
        $status = "error";
    } else {
        // Check if user exists in the physical_login_users table
        $sql = "SELECT * FROM physical_login_users WHERE student_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            
            // Check if there's already an active entry without an exit
            $check_sql = "SELECT * FROM library_visits 
                         WHERE student_number = ? AND status = 'ENTRY' 
                         ORDER BY time DESC LIMIT 1";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $student_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $message = "You already have an active entry. Please exit first.";
                $status = "warning";
            } else {
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
            }
            $check_stmt->close();
        } else {
            $message = "Invalid ID. Student/Employee not found in records.";
            $status = "error";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Entrance - NBSC Library</title>
    
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)), url('../Images/BG/library-background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            /* Remove vertical centering */
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
            /* Center horizontally only */
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
            color: #4e73df;
            text-align: center;
            font-weight: 800;
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            margin-bottom: 0.5rem;
        }
        
        .info-text {
            text-align: center;
            color: #5a5c69;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .clock-section {
            background-color: rgba(78, 115, 223, 0.1);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .clock {
            font-size: 2.5rem;
            color: #4e73df;
            font-weight: 700;
            margin-bottom: 0.25rem;
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
            background-color: rgba(78, 115, 223, 0.05);
            border-radius: 0.75rem;
            padding: 1rem;
            border-left: 4px solid #4e73df;
        }
        
        .form-control, .form-select {
            height: 60px;
            font-size: 1.5rem;
            text-align: center;
            border: 2px solid #4e73df;
            margin-bottom: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .form-select {
            padding-left: 1.5rem;
            background-position: right 1.5rem center;
        }
        
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
            border-color: #4e73df;
        }
        
        .btn-primary {
            font-size: 1.25rem;
            border-radius: 0.75rem;
            background-color: #4e73df;
            border-color: #4e73df;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .recent-entries {
            margin-top: 2rem;
            border-radius: 0.75rem;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .entries-header {
            background-color: #4e73df;
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
            background-color: rgba(78, 115, 223, 0.05);
            transition: all 0.2s ease;
        }
        
        .entry-item:hover {
            background-color: rgba(78, 115, 223, 0.1);
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
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #5a5c69;
        }
        
        .toggle-link.active {
            background-color: #4e73df;
            color: white;
        }
        
        .toggle-link:first-child {
            border-right: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .toggle-link:hover:not(.active) {
            background-color: rgba(78, 115, 223, 0.1);
        }
        
        .exit-link {
            background-color: rgba(231, 74, 59, 0.9);
        }
        
        .exit-link:hover {
            background-color: rgba(201, 48, 44, 1);
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
        
        /* Mobile adjustments */
        @media (max-width: 768px) {
            .login-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .clock {
                font-size: 2rem;
            }
            
            .form-control, .form-select {
                height: 50px;
                font-size: 1.25rem;
            }
            
            .btn-primary {
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
                    <a href="library_entrance.php" class="toggle-link active">
                        <i class="fas fa-sign-in-alt me-2"></i> Entrance
                    </a>
                    <a href="library_exit.php" class="toggle-link">
                        <i class="fas fa-sign-out-alt me-2"></i> Exit
                    </a>
                </div>
            </div>
            
            <div class="centered-content">
                <div class="logo-container">
                    <img src="inc/img/horizontal-nbs-logo.png" alt="NBSC Library Logo" class="img-fluid animate__animated animate__pulse animate__infinite animate__slower">
                </div>
                
                <h1 class="mb-2">NBSC LIBRARY</h1>
                <p class="info-text">Student & Faculty Entry Management System</p>
                
                <div class="form-section">
                    <div class="clock-section animate__animated animate__fadeIn">
                        <div class="clock" id="clock">00:00:00</div>
                        <div class="date" id="date">Loading date...</div>
                    </div>
                    
                    <div class="instruction animate__animated animate__fadeIn animate__delay-1s">
                        <i class="fas fa-info-circle me-2"></i>
                        Please scan or enter your Student/Employee ID to log your library entry
                    </div>
                    
                    <form method="POST" action="" id="entranceForm" class="animate__animated animate__fadeIn animate__delay-1s">
                        <div class="form-group">
                            <input type="text" class="form-control" id="student_id" name="student_id" 
                                   placeholder="Scan or Enter ID" autofocus autocomplete="off">
                        </div>
                        
                        <!-- Purpose dropdown -->
                        <div class="form-group mb-3">
                            <select class="form-select form-select-lg" name="purpose" id="purpose">
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
    </div>
    
    <div class="admin-links animate__animated animate__fadeIn animate__delay-3s">
        <a href="library_exit.php" class="admin-link exit-link">
            <i class="fas fa-sign-out-alt"></i> Exit
        </a>
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
            document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
            
            // Update date
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('date').textContent = now.toLocaleDateString('en-US', options);
        }
        
        // Update time every second
        setInterval(updateTime, 1000);
        updateTime(); // Initial call
        
        // Auto-submit when ID is scanned (usually ends with a return character)
        document.getElementById('student_id').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('entranceForm').submit();
            }
        });
        
        // Auto-focus the input field
        window.onload = function() {
            document.getElementById('student_id').focus();
            
            // Re-focus after 5 seconds if it loses focus
            setInterval(function() {
                document.getElementById('student_id').focus();
            }, 5000);
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
        
        // Load recent entries every 30 seconds
        setInterval(loadRecentEntries, 30000);
        loadRecentEntries(); // Initial load
        
        <?php if($status === 'success'): ?>
        // Show success message
        Swal.fire({
            title: 'Welcome!',
            text: '<?php echo $message; ?>',
            icon: 'success',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            document.getElementById('student_id').value = '';
            document.getElementById('student_id').focus();
            loadRecentEntries(); // Refresh the entries list
        });
        <?php elseif($status === 'error'): ?>
        // Show error message
        Swal.fire({
            title: 'Error',
            text: '<?php echo $message; ?>',
            icon: 'error',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            document.getElementById('student_id').value = '';
            document.getElementById('student_id').focus();
        });
        <?php endif; ?>
    </script>
</body>
</html>
