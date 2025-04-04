<?php
session_start();
require_once '../db.php';

// Initialize variables
$message = '';
$status = '';
$user_data = null;

// Process exit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    
    // Validate student ID
    if (empty($student_id)) {
        $message = "Please enter your Student/Employee ID";
        $status = "error";
    } else {
        // Check if user exists in the database
        $sql = "SELECT * FROM users WHERE school_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            
            // Check if there's an open entry record (with status 1)
            $check_sql = "SELECT id FROM library_visits 
                         WHERE student_number = ? AND status = 1 
                         ORDER BY time DESC LIMIT 1";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $student_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
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
                // No open entry found
                $message = "No active library entry found for your ID. Please check with the librarian.";
                $status = "error";
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
    <title>Library Exit - NBSC Library</title>
    
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
            color: #e74a3b; /* Using red for exit instead of blue */
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
            background-color: rgba(231, 74, 59, 0.1);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .clock {
            font-size: 2.5rem;
            color: #e74a3b;
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
            background-color: rgba(231, 74, 59, 0.05);
            border-radius: 0.75rem;
            padding: 1rem;
            border-left: 4px solid #e74a3b;
        }
        
        .form-control {
            height: 60px;
            font-size: 1.5rem;
            text-align: center;
            border: 2px solid #e74a3b;
            margin-bottom: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(231, 74, 59, 0.25);
            border-color: #e74a3b;
        }
        
        .btn-danger {
            font-size: 1.25rem;
            border-radius: 0.75rem;
            background-color: #e74a3b;
            border-color: #e74a3b;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .btn-danger:hover {
            background-color: #c23321;
            border-color: #bd2130;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-danger:active {
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
            background-color: #e74a3b;
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
            background-color: rgba(231, 74, 59, 0.05);
            transition: all 0.2s ease;
        }
        
        .entry-item:hover {
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
        
        .entrance-link {
            background-color: rgba(78, 115, 223, 0.9);
        }
        
        .entrance-link:hover {
            background-color: rgba(46, 89, 217, 1);
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
            background-color: #e74a3b;
            color: white;
        }
        
        .toggle-link:first-child {
            border-right: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .toggle-link:hover:not(.active) {
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
        
        /* Mobile adjustments */
        @media (max-width: 768px) {
            .login-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .clock {
                font-size: 2rem;
            }
            
            .form-control {
                height: 50px;
                font-size: 1.25rem;
            }
            
            .btn-danger {
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
                    <a href="library_entrance.php" class="toggle-link">
                        <i class="fas fa-sign-in-alt me-2"></i> Entrance
                    </a>
                    <a href="library_exit.php" class="toggle-link active">
                        <i class="fas fa-sign-out-alt me-2"></i> Exit
                    </a>
                </div>
            </div>
            
            <div class="centered-content">
                <div class="logo-container">
                    <img src="inc/img/horizontal-nbs-logo.png" alt="NBSC Library Logo" class="img-fluid animate__animated animate__pulse animate__infinite animate__slower">
                </div>
                
                <h1 class="mb-2">LIBRARY EXIT</h1>
                <p class="info-text">Student & Faculty Exit Management System</p>
                
                <div class="form-section">
                    <div class="clock-section animate__animated animate__fadeIn">
                        <div class="clock" id="clock">00:00:00</div>
                        <div class="date" id="date">Loading date...</div>
                    </div>
                    
                    <div class="instruction animate__animated animate__fadeIn animate__delay-1s">
                        <i class="fas fa-info-circle me-2"></i>
                        Please scan or enter your Student/Employee ID to log your library exit
                    </div>
                    
                    <form method="POST" action="" id="exitForm" class="animate__animated animate__fadeIn animate__delay-1s">
                        <div class="form-group">
                            <input type="text" class="form-control" id="student_id" name="student_id" 
                                   placeholder="Scan or Enter ID" autofocus autocomplete="off">
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
    
    <div class="admin-links animate__animated animate__fadeIn animate__delay-3s">
        <a href="library_entrance.php" class="admin-link entrance-link">
            <i class="fas fa-sign-in-alt"></i> Entrance
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
                document.getElementById('exitForm').submit();
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
        
        // Load recent exits every 30 seconds
        setInterval(loadRecentExits, 30000);
        loadRecentExits(); // Initial load
        
        <?php if($status === 'success'): ?>
        // Show success message
        Swal.fire({
            title: 'Goodbye!',
            text: '<?php echo $message; ?>',
            icon: 'success',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            document.getElementById('student_id').value = '';
            document.getElementById('student_id').focus();
            loadRecentExits(); // Refresh the exits list
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
