<?php
session_start();
include '../db.php';

// Check if the user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// Load backup classes
require_once 'backup/backup_config.php';
require_once 'backup/backup_database.php';

// Create backup instance
$backupManager = new DatabaseBackup();

// Handle actions
$message = '';
$error = '';

// Check for session messages first
if (isset($_SESSION['backup_success'])) {
    $message = $_SESSION['backup_success'];
    unset($_SESSION['backup_success']);
}

if (isset($_SESSION['backup_error'])) {
    $error = $_SESSION['backup_error'];
    unset($_SESSION['backup_error']);
}

// Process actions with redirection to prevent duplicate operations on refresh
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'backup':
            $result = $backupManager->backup();
            if ($result['success']) {
                $_SESSION['backup_success'] = $result['message'];
            } else {
                $_SESSION['backup_error'] = $result['message'];
            }
            // Redirect to clear the action parameter
            header("Location: backup_manager.php");
            exit;
            break;
            
        case 'delete':
            if (isset($_GET['file'])) {
                $filename = $_GET['file'];
                if ($backupManager->deleteBackup($filename)) {
                    $_SESSION['backup_success'] = "Backup file \"$filename\" deleted successfully.";
                } else {
                    $_SESSION['backup_error'] = "Failed to delete backup file \"$filename\".";
                }
                header("Location: backup_manager.php");
                exit;
            }
            break;
            
        case 'download':
            if (isset($_GET['file'])) {
                $backupManager->downloadBackup($_GET['file']);
                // Script execution ends in downloadBackup()
            }
            break;
    }
}

// Handle test mode with redirection
if (isset($_GET['test']) && $_GET['test'] === 'true') {
    // Run a test backup when requested
    $backupManager = new DatabaseBackup();
    $result = $backupManager->backup();
    
    if ($result['success']) {
        $_SESSION['backup_success'] = $result['message'];
    } else {
        $_SESSION['backup_error'] = $result['message'];
    }
    
    // Redirect to clear the test parameter
    header("Location: backup_manager.php");
    exit;
}

// Handle bulk action requests
if (isset($_POST['bulk_action']) && isset($_POST['selected_files'])) {
    $selectedFiles = $_POST['selected_files'];
    $action = $_POST['bulk_action'];
    
    if (empty($selectedFiles)) {
        $_SESSION['error_message'] = "No backups selected for action.";
    } else {
        switch ($action) {
            case 'delete':
                $deleteCount = 0;
                $deleted_backups_details = [];
                
                foreach ($selectedFiles as $filename) {
                    $filePath = BACKUP_DIR . '/' . basename($filename);
                    if (file_exists($filePath) && unlink($filePath)) {
                        $deleteCount++;
                        $deleted_backups_details[] = $filename;
                    }
                }
                
                if ($deleteCount > 0) {
                    $_SESSION['success_message'] = "$deleteCount backup(s) deleted successfully.";
                    $_SESSION['deleted_backups_details'] = $deleted_backups_details;
                } else {
                    $_SESSION['error_message'] = "Failed to delete selected backups.";
                }
                break;
        }
    }
    
    // Redirect to refresh the page
    header("Location: backup_manager.php");
    exit;
}

// Get list of available backups
$backups = $backupManager->getBackups();

// Check if backup directory is writable
$backup_dir_writable = is_writable(BACKUP_DIR);

// Calculate backup stats
$total_backups = count($backups);
$total_size = array_sum(array_column($backups, 'size'));

// Get scheduled status
$schedule_status = [];
$status_file = LOGS_DIR . "/backup_status.json";
if (file_exists($status_file)) {
    $schedule_status = json_decode(file_get_contents($status_file), true);
}

// If we have backups but the status file doesn't show any successful backups,
// update the status information based on the existing backups
if ($total_backups > 0 && (!isset($schedule_status['last_successful_backup']) || empty($schedule_status['last_successful_backup']))) {
    // Sort backups by date (newest first)
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Get the date of the most recent backup
    $most_recent_backup = $backups[0];
    $backup_date = date('Y-m-d', strtotime($most_recent_backup['date']));
    
    // Update the status information
    $schedule_status['last_successful_backup'] = $backup_date;
    $schedule_status['last_status'] = 'success';
    $schedule_status['last_run'] = $most_recent_backup['date'];
    $schedule_status['last_message'] = 'Backup file: ' . $most_recent_backup['filename'];
    
    // Save the updated status
    file_put_contents($status_file, json_encode($schedule_status, JSON_PRETTY_PRINT));
}

// Include header
include '../admin/inc/header.php';

// Diagnostics tool availability
$diagnosticsAvailable = true;
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Database Backup Manager</h1>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        <?php if (strpos($message, 'uncompressed') !== false): ?>
        <div class="mt-2">
            <small><i class="fas fa-info-circle"></i> <strong>Note:</strong> This backup was created as an uncompressed SQL file because ZIP compression is not available on this server.</small>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <h5><i class="fas fa-exclamation-circle"></i> Backup Error</h5>
        <p><?php echo $error; ?></p>
        <div class="mt-3">
            <strong>Possible solutions:</strong>
            <ul class="mb-0 mt-2">
                <li>Check if mysqldump is installed and accessible (path: <?php echo MYSQLDUMP_PATH; ?>)</li>
                <li>Verify database credentials in the db.php file</li>
                <li>Make sure backup directories have write permissions</li>
                <li>Check if PHP has permission to execute system commands</li>
                <li>Run the <a href="#" class="alert-link" id="runDiagnosticsLink">diagnostics tool</a> for detailed troubleshooting</li>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <?php 
    // Check if ZIP is not available and show info message
    $zip_available = class_exists('ZipArchive');
    if (!$zip_available && defined('CREATE_UNCOMPRESSED_BACKUP_IF_NO_ZIP') && CREATE_UNCOMPRESSED_BACKUP_IF_NO_ZIP): 
    ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> <strong>Information:</strong> ZIP compression is not available on this server. Backups will be created as uncompressed SQL files, which may be larger but are still fully functional.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <?php if (!$backup_dir_writable): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> Warning: The backup directory is not writable. Please check the permissions.
    </div>
    <?php endif; ?>

    <!-- Dashboard Cards -->
    <div class="row mb-4">
        <!-- Backup Status Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Last Backup Status</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php if (isset($schedule_status['last_status'])): ?>
                                    <?php if ($schedule_status['last_status'] === 'success'): ?>
                                        <span class="text-success">Success</span>
                                    <?php else: ?>
                                        <span class="text-danger">Failed</span>
                                    <?php endif; ?>
                                <?php elseif ($total_backups > 0): ?>
                                    <span class="text-success">Success</span>
                                <?php else: ?>
                                    <span class="text-muted">No backups yet</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last Backup Date -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Last Successful Backup</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php if (isset($schedule_status['last_successful_backup'])): ?>
                                    <?php echo date('M d, Y', strtotime($schedule_status['last_successful_backup'])); ?>
                                <?php elseif ($total_backups > 0 && isset($backups[0]['date'])): ?>
                                    <?php echo date('M d, Y', strtotime($backups[0]['date'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Backups -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Backups</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_backups; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-save fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Storage Used -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Storage Used</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo round($total_size / 1024 / 1024, 2); ?> MB
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hdd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Tools Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Backup Tools</h6>
        </div>
        <div class="card-body">
            <p class="mb-4">Use these tools to manage your database backups. Regular backups help protect your data from loss.</p>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="m-0 font-weight-bold">Create Backup</h6>
                        </div>
                        <div class="card-body">
                            <p>Create a new backup of the database right now.</p>
                            <a href="?action=backup" class="btn btn-primary" onclick="return confirm('Are you sure you want to create a new backup?');">
                                <i class="fas fa-plus-circle mr-2"></i>Create Backup Now
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="m-0 font-weight-bold">Scheduled Backups</h6>
                        </div>
                        <div class="card-body">
                            <p>Automatic backups are scheduled to run weekly on Sunday.</p>
                            <p class="mb-0"><strong>Next scheduled backup:</strong> 
                                <?php 
                                $today = date('w'); 
                                $days_to_sunday = $today == 0 ? 7 : 7 - $today;
                                echo date('F j, Y', time() + $days_to_sunday * 86400); 
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How to Set Up Windows Task Scheduler -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="m-0 font-weight-bold">Setting Up Automatic Backups</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i> To set up automatic weekly backups, follow these instructions to configure a scheduled task.
                    </div>
                    
                    <h6 class="font-weight-bold">Option 1: Windows Task Scheduler</h6>
                    <ol>
                        <li>Open Windows Task Scheduler (search for "Task Scheduler" in the Start menu)</li>
                        <li>Click "Create Basic Task" and name it "Library Database Backup"</li>
                        <li>Select "Weekly" and choose Sunday as the day to run</li>
                        <li>Select "Start a program" as the action</li>
                        <li>
                            <p>For the program/script, enter the path to PHP executable:</p>
                            <div class="bg-light p-2 text-monospace">C:\xampp\php\php.exe</div>
                        </li>
                        <li>
                            <p>For arguments, enter the full path to the scheduler script:</p>
                            <div class="bg-light p-2 text-monospace">-f "C:\xampp\htdocs\Library-System\Admin\backup\backup_scheduler.php"</div>
                        </li>
                        <li>Complete the wizard and your automatic backup is configured</li>
                    </ol>
                    
                    <h6 class="font-weight-bold mt-4">Option 2: URL-based Trigger (requires server to be publicly accessible)</h6>
                    <ol>
                        <li>Set up a third-party service like cron-job.org to call this URL weekly:</li>
                        <div class="bg-light p-2 text-monospace"><?php 
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];
                            echo $protocol . "://" . $host . "/Library-System/Admin/backup/backup_scheduler.php?access_key=" . BACKUP_ACCESS_KEY;
                        ?></div>
                        <li>Set the schedule for every Sunday</li>
                    </ol>
                </div>
            </div>

            <!-- Add Diagnostics Tool Link -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="m-0 font-weight-bold">Backup Diagnostics</h6>
                </div>
                <div class="card-body">
                    <p>If you're experiencing issues with backups, use the diagnostics tool to identify and resolve problems.</p>
                    <a href="#" class="btn btn-info" id="runDiagnostics">
                        <i class="fas fa-stethoscope mr-2"></i>Run Diagnostics
                    </a>
                    <a href="?test=true" class="btn btn-warning ml-2">
                        <i class="fas fa-vial mr-2"></i>Test Backup Process
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Backups Section -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Available Backups</h6>
            <button id="deleteSelectedBtn" class="btn btn-outline-danger btn-sm" disabled>
                Delete Selected (<span id="selectedDeleteCount">0</span>)
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($backups)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-database fa-4x text-gray-300 mb-4"></i>
                    <p class="mb-0">No backup files found. Create your first backup now.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="backupsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="text-align: center;" id="checkboxHeader">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th style="text-align: center;">Filename</th>
                                <th style="text-align: center;">Date Created</th>
                                <th style="text-align: center;">Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                                <tr data-file="<?php echo htmlspecialchars($backup['filename']); ?>">
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($backup['filename']); ?>
                                        <?php if (isset($backup['type']) && $backup['type'] === 'sql'): ?>
                                            <span class="badge badge-info ml-2" title="Uncompressed SQL file">SQL</span>
                                        <?php elseif (isset($backup['type']) && $backup['type'] === 'zip'): ?>
                                            <span class="badge badge-success ml-2" title="Compressed ZIP file">ZIP</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;"><?php echo date('M d, Y g:i A', strtotime($backup['date'])); ?></td>
                                    <td style="text-align: center;"><?php echo round($backup['size'] / 1024 / 1024, 2); ?> MB</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Backup Logs Section -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Backup Logs</h6>
            <button class="btn btn-outline-secondary btn-sm" type="button" data-toggle="collapse" data-target="#logsCollapse" aria-expanded="false" aria-controls="logsCollapse">
                Show/Hide Logs
            </button>
        </div>
        <div class="collapse" id="logsCollapse">
            <div class="card-body" style="max-height:400px;overflow:auto;">
                <?php
                // Display the most recent 5 log files from LOGS_DIR
                $log_files = glob(LOGS_DIR . '/backup_log_*.txt');
                usort($log_files, function($a, $b) { return filemtime($b) - filemtime($a); });
                if (empty($log_files)) {
                    echo '<div class="text-muted">No backup logs found.</div>';
                } else {
                    $max_logs = 5;
                    foreach (array_slice($log_files, 0, $max_logs) as $log_file) {
                        $log_date = basename($log_file, '.txt');
                        echo '<div class="mb-3">';
                        echo '<h6 class="font-weight-bold mb-2">' . htmlspecialchars($log_date) . '</h6>';
                        echo '<pre class="bg-light p-2 rounded" style="font-size:0.95em;">' . htmlspecialchars(file_get_contents($log_file)) . '</pre>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <div id="contextMenu" class="dropdown-menu context-menu" style="display: none; position: absolute;">
        <a class="dropdown-item" href="#" id="contextMenuDownload">
            <i class="fas fa-download text-primary"></i> Download Backup
        </a>
        <a class="dropdown-item" href="#" id="contextMenuDelete">
            <i class="fas fa-trash text-danger"></i> Delete Backup
        </a>
    </div>

    <!-- Diagnostics Modal -->
    <div class="modal fade" id="diagnosticsModal" tabindex="-1" role="dialog" aria-labelledby="diagnosticsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="diagnosticsModalLabel">Backup System Diagnostics</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="diagnosticsResults">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<?php include '../admin/inc/footer.php'; ?>

<script>
$(document).ready(function() {
    var selectedFiles = [];
    
    // Handle select all checkbox
    $('#selectAll').on('change', function () {
        var isChecked = $(this).prop('checked');
        $('.row-checkbox').prop('checked', isChecked);
        selectedFiles = isChecked ? $('.row-checkbox').map(function () { return $(this).val(); }).get() : [];
        updateDeleteButton();
    });

    // Handle individual checkbox changes
    $('#backupsTable tbody').on('change', '.row-checkbox', function () {
        var filename = $(this).val();
        if ($(this).prop('checked')) {
            if (!selectedFiles.includes(filename)) selectedFiles.push(filename);
        } else {
            selectedFiles = selectedFiles.filter(item => item !== filename);
        }
        $('#selectAll').prop('checked', $('.row-checkbox:checked').length === $('.row-checkbox').length);
        updateDeleteButton();
    });

    // Update delete button state and count
    function updateDeleteButton() {
        const count = selectedFiles.length;
        $('#selectedDeleteCount').text(count);
        $('#deleteSelectedBtn').prop('disabled', count === 0);
    }

    // Handle bulk delete button click
    $('#deleteSelectedBtn').on('click', function () {
        if (selectedFiles.length === 0) return;

        Swal.fire({
            title: 'Confirm Deletion',
            html: `Are you sure you want to delete <strong>${selectedFiles.length}</strong> selected backup(s)?<br><br>
                   <span class="text-danger">This action cannot be undone!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Create a form to submit the selected files
                var form = $('<form>', {
                    'method': 'post',
                    'action': 'backup_manager.php'
                }).append(
                    $('<input>', {
                        'type': 'hidden',
                        'name': 'bulk_action',
                        'value': 'delete'
                    })
                );
                
                // Add each selected file to the form
                selectedFiles.forEach(function(file) {
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'selected_files[]',
                        'value': file
                    }));
                });
                
                // Append form to body and submit
                $('body').append(form);
                form.submit();
            }
        });
    });

    // Initialize DataTables for backup files with matching styling from publisher_list.php
    var table = $('#backupsTable').DataTable({
        "dom": "<'row mb-3'<'col-sm-6'l><'col-sm-6 d-flex justify-content-end'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-5'i><'col-sm-7 d-flex justify-content-end'p>>",
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "responsive": true,
        "scrollX": true,
        "order": [[1, "desc"]], // Sort by date created (newest first)
        "columnDefs": [
            { "orderable": false, "targets": 0 } // Disable sorting for checkbox column
        ],
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search...",
            "lengthMenu": "Show _MENU_ entries",
            "zeroRecords": "No backups found",
            "info": "Showing _START_ to _END_ of _TOTAL_ backups",
            "infoEmpty": "Showing 0 to 0 of 0 backups",
            "infoFiltered": "(filtered from _MAX_ total backups)"
        }
    });

    var contextMenuTargetFile = null;

    // Context menu setup - matched with publisher_list.php
    $('#backupsTable tbody').on('contextmenu', 'tr', function (e) {
        e.preventDefault();
        
        contextMenuTargetFile = $(this).data('file');
        
        // Position the context menu at the cursor position
        $('#contextMenu').css({
            top: e.pageY + 'px',
            left: e.pageX + 'px'
        }).show();
        
        // Highlight the selected row
        $('#backupsTable tbody tr').removeClass('table-active');
        $(this).addClass('table-active');
    });

    // Hide context menu when clicking elsewhere
    $(document).on('click', function () {
        $('#contextMenu').hide();
        $('#backupsTable tbody tr').removeClass('table-active');
    });

    // Handle context menu actions - matched with publisher_list.php
    $('#contextMenuDownload').on('click', function (e) {
        e.preventDefault();
        if (contextMenuTargetFile) {
            window.location.href = '?action=download&file=' + encodeURIComponent(contextMenuTargetFile);
        }
    });

    $('#contextMenuDelete').on('click', function (e) {
        e.preventDefault();
        if (contextMenuTargetFile) {
            Swal.fire({
                title: 'Confirm Deletion',
                html: `Are you sure you want to delete backup <strong>${contextMenuTargetFile}</strong>?<br><br>
                       <span class="text-danger">This action cannot be undone!</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?action=delete&file=' + encodeURIComponent(contextMenuTargetFile);
                }
            });
        }
    });

    // Double-click row for quick download
    $('#backupsTable tbody').on('dblclick', 'tr', function() {
        const filename = $(this).data('file');
        if (filename) {
            window.location.href = '?action=download&file=' + encodeURIComponent(filename);
        }
    });

    // Add a confirmation dialog when "All" option is selected
    $('#backupsTable').on('length.dt', function(e, settings, len) {
        if (len === -1) {
            Swal.fire({
                title: 'Display All Entries?',
                text: "Are you sure you want to display all entries? This may cause performance issues.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, display all!'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    // If the user cancels, reset the page length to the previous value
                    $('#backupsTable').DataTable().page.len(settings._iDisplayLength).draw();
                }
            });
        }
    });

    // Handle diagnostics button click
    $('#runDiagnostics, #runDiagnosticsLink').on('click', function(e) {
        e.preventDefault();
        
        // Show the modal
        $('#diagnosticsModal').modal('show');
        
        // Run diagnostics via AJAX
        $.ajax({
            url: 'backup/diagnostics.php',
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                let html = '';
                
                // Process each check
                for (const [name, check] of Object.entries(data)) {
                    html += `
                        <div class="check ${check.status} mb-3">
                            <h5 class="d-flex align-items-center">
                                ${name.charAt(0).toUpperCase() + name.slice(1)} Check
                                <span class="badge badge-${getBadgeClass(check.status)} ml-2">
                                    ${check.status.toUpperCase()}
                                </span>
                            </h5>
                            <p>${check.message}</p>`;
                    
                    if (check.details) {
                        html += '<div class="details bg-light p-3 rounded">';
                        html += '<h6>Details:</h6>';
                        html += formatDetails(check.details);
                        html += '</div>';
                    }
                    
                    html += '</div>';
                }
                
                $('#diagnosticsResults').html(html);
            },
            error: function() {
                $('#diagnosticsResults').html(`
                    <div class="alert alert-danger">
                        Error running diagnostics. Please try again.
                    </div>
                `);
            }
        });
    });
    
    // Helper function to get Bootstrap badge class
    function getBadgeClass(status) {
        switch(status) {
            case 'success': return 'success';
            case 'error': return 'danger';
            case 'info': return 'info';
            default: return 'secondary';
        }
    }
    
    // Helper function to format details recursively
    function formatDetails(details) {
        let html = '<ul class="list-unstyled mb-0">';
        for (const [key, value] of Object.entries(details)) {
            if (typeof value === 'object') {
                html += `<li><strong>${formatKey(key)}:</strong>${formatDetails(value)}</li>`;
            } else {
                html += `<li><strong>${formatKey(key)}:</strong> ${value}</li>`;
            }
        }
        html += '</ul>';
        return html;
    }
    
    // Helper function to format keys
    function formatKey(key) {
        return key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    }
});
</script>

<!-- Add these styles -->
<style>
.check {
    border-left: 4px solid #ddd;
    padding-left: 1rem;
}
.check.success { border-color: #28a745; }
.check.error { border-color: #dc3545; }
.check.info { border-color: #17a2b8; }
.details { font-size: 0.9rem; }
</style>

