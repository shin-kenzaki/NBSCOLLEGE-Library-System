<?php
// Backup scheduler - can be triggered by cron, scheduled task, or manual execution
require_once 'backup_config.php';
require_once 'backup_database.php';

// Security check - verify access key if accessed via web
if (php_sapi_name() !== 'cli') {
    // Check if this is a scheduled task with valid access key
    $provided_key = isset($_GET['access_key']) ? $_GET['access_key'] : '';
    
    if ($provided_key !== BACKUP_ACCESS_KEY) {
        header('HTTP/1.1 403 Forbidden');
        echo "Access denied.";
        exit;
    }
}

// Function to check if we need to run the backup today
function shouldRunBackupToday() {
    $log_dir = LOGS_DIR;
    $today = date('Y-m-d');
    $log_file = $log_dir . "/backup_status.json";
    
    // If status file doesn't exist or can't be read, assume we need to run backup
    if (!file_exists($log_file)) {
        return true;
    }
    
    $status = json_decode(file_get_contents($log_file), true);
    
    // If last successful backup was not today and it's the designated weekly backup day (Sunday)
    if (date('w') == 0 && (!isset($status['last_successful_backup']) || $status['last_successful_backup'] !== $today)) {
        return true;
    }
    
    // If forced backup is requested
    if (isset($_GET['force']) && $_GET['force'] == 1) {
        return true;
    }
    
    return false;
}

// Function to update the backup status file
function updateBackupStatus($success, $message = '') {
    $log_dir = LOGS_DIR;
    $log_file = $log_dir . "/backup_status.json";
    
    $status = [
        'last_run' => date('Y-m-d H:i:s'),
        'last_status' => $success ? 'success' : 'failed'
    ];
    
    if ($success) {
        $status['last_successful_backup'] = date('Y-m-d');
    }
    
    if ($message) {
        $status['last_message'] = $message;
    }
    
    // If file exists, merge with existing data
    if (file_exists($log_file)) {
        $existing_status = json_decode(file_get_contents($log_file), true);
        if (is_array($existing_status)) {
            $status = array_merge($existing_status, $status);
        }
    }
    
    file_put_contents($log_file, json_encode($status, JSON_PRETTY_PRINT));
}

// Main execution
$output = [];

// Check if we should run backup today
if (shouldRunBackupToday()) {
    $backup = new DatabaseBackup();
    $result = $backup->backup();
    
    // Update status file
    updateBackupStatus($result['success'], $result['message'] ?? '');
    
    $output = [
        'timestamp' => date('Y-m-d H:i:s'),
        'success' => $result['success'],
        'message' => $result['message'] ?? ''
    ];
} else {
    $output = [
        'timestamp' => date('Y-m-d H:i:s'),
        'success' => true,
        'message' => 'No backup needed today'
    ];
}

// Output results
if (php_sapi_name() === 'cli') {
    echo $output['message'] . PHP_EOL;
} else {
    header('Content-Type: application/json');
    echo json_encode($output);
}
?>
