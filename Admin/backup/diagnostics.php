<?php
// Backup System Diagnostics Tool
// This file helps diagnose issues with the backup system

// Load configuration
require_once 'backup_config.php';

function checkDatabaseConnection() {
    try {
        // Get database configuration file path
        $db_config_path = DB_CONFIG_PATH;
        
        // Check if file exists
        if (!file_exists($db_config_path)) {
            return [
                'status' => 'error',
                'message' => 'Database configuration file not found at: ' . $db_config_path,
                'details' => [
                    'path' => $db_config_path
                ]
            ];
        }
        
        // Extract variables directly from the db.php file
        require $db_config_path;
        
        // Check if required variables exist after including the file
        if (!isset($hostname) || !isset($username) || !isset($database)) {
            return [
                'status' => 'error',
                'message' => 'Database configuration variables not found in config file',
                'details' => [
                    'host' => isset($hostname) ? $hostname : 'Not defined',
                    'username' => isset($username) ? $username : 'Not defined',
                    'database' => isset($database) ? $database : 'Not defined',
                    'connection' => isset($conn) ? 'Exists' : 'Not defined'
                ]
            ];
        }
        
        // If connection object already exists from including the file, use it
        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            return [
                'status' => 'success',
                'message' => 'Database connection successful',
                'details' => [
                    'server' => $conn->server_info,
                    'host' => $hostname,
                    'database' => $database,
                    'username' => $username,
                    'connection_id' => $conn->thread_id
                ]
            ];
        } 
        // Otherwise try to create a new connection
        else {
            $test_conn = new mysqli($hostname, $username, $password, $database);
            
            if ($test_conn->connect_error) {
                return [
                    'status' => 'error',
                    'message' => 'Database connection failed: ' . $test_conn->connect_error,
                    'details' => [
                        'host' => $hostname,
                        'database' => $database,
                        'error_code' => $test_conn->connect_errno,
                        'error_message' => $test_conn->connect_error
                    ]
                ];
            }
            
            return [
                'status' => 'success',
                'message' => 'Database connection successful (new connection)',
                'details' => [
                    'server' => $test_conn->server_info,
                    'host' => $hostname,
                    'database' => $database,
                    'username' => $username,
                    'connection_id' => $test_conn->thread_id
                ]
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Exception when checking database connection: ' . $e->getMessage(),
            'details' => [
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ];
    }
}

function checkMysqldumpPath() {
    $mysqldump_path = MYSQLDUMP_PATH;
    
    // Check if the file exists
    $path_exists = file_exists($mysqldump_path);
    
    // Try to execute mysqldump to check if it works
    $exec_enabled = function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')));
    $mysqldump_works = false;
    $version = 'Unknown';
    
    if ($exec_enabled) {
        exec("\"$mysqldump_path\" --version 2>&1", $output, $return_var);
        if ($return_var === 0) {
            $mysqldump_works = true;
            $version = implode("\n", $output);
        }
    }
    
    return [
        'status' => ($path_exists && $mysqldump_works) ? 'success' : 'error',
        'message' => ($path_exists && $mysqldump_works) ? 
                     'mysqldump executable found and working' : 
                     'mysqldump executable not found or not working',
        'details' => [
            'path' => $mysqldump_path,
            'path_exists' => $path_exists ? 'Yes' : 'No',
            'exec_enabled' => $exec_enabled ? 'Yes' : 'No',
            'mysqldump_works' => $mysqldump_works ? 'Yes' : 'No',
            'version' => $version
        ]
    ];
}

function checkDirectoryPermissions() {
    $backup_dir = BACKUP_DIR;
    $logs_dir = LOGS_DIR;
    
    $backup_dir_exists = is_dir($backup_dir);
    $logs_dir_exists = is_dir($logs_dir);
    
    $backup_dir_writable = is_writable($backup_dir);
    $logs_dir_writable = is_writable($logs_dir);
    
    return [
        'status' => ($backup_dir_exists && $logs_dir_exists && $backup_dir_writable && $logs_dir_writable) ? 
                   'success' : 'error',
        'message' => ($backup_dir_exists && $logs_dir_exists && $backup_dir_writable && $logs_dir_writable) ?
                    'Directory permissions look good' : 'Issues with directory permissions',
        'details' => [
            'backup_dir' => [
                'path' => $backup_dir,
                'exists' => $backup_dir_exists ? 'Yes' : 'No',
                'writable' => $backup_dir_writable ? 'Yes' : 'No'
            ],
            'logs_dir' => [
                'path' => $logs_dir,
                'exists' => $logs_dir_exists ? 'Yes' : 'No',
                'writable' => $logs_dir_writable ? 'Yes' : 'No'
            ]
        ]
    ];
}

function checkPDOAvailability() {
    $pdo_available = class_exists('PDO');
    $mysql_driver_available = false;
    
    if ($pdo_available) {
        $drivers = PDO::getAvailableDrivers();
        $mysql_driver_available = in_array('mysql', $drivers);
    }
    
    return [
        'status' => ($pdo_available && $mysql_driver_available) ? 'success' : 'error',
        'message' => ($pdo_available && $mysql_driver_available) ?
                    'PDO with MySQL driver is available' : 'Issues with PDO availability',
        'details' => [
            'pdo_available' => $pdo_available ? 'Yes' : 'No',
            'mysql_driver_available' => $mysql_driver_available ? 'Yes' : 'No',
            'available_drivers' => $pdo_available ? implode(', ', PDO::getAvailableDrivers()) : 'None'
        ]
    ];
}

function checkZipSupport() {
    $zip_enabled = class_exists('ZipArchive');
    
    return [
        'status' => $zip_enabled ? 'success' : 'error',
        'message' => $zip_enabled ? 'ZIP support is enabled' : 'ZIP support is not available',
        'details' => [
            'zip_enabled' => $zip_enabled ? 'Yes' : 'No'
        ]
    ];
}

function checkPHPSettings() {
    return [
        'status' => 'info',
        'message' => 'PHP Configuration Information',
        'details' => [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . ' seconds',
            'disable_functions' => ini_get('disable_functions') ?: 'None',
            'safe_mode' => ini_get('safe_mode') ? 'On' : 'Off',
            'open_basedir' => ini_get('open_basedir') ?: 'Not restricted'
        ]
    ];
}

// Run all checks
$checks = [
    'database' => checkDatabaseConnection(),
    'mysqldump' => checkMysqldumpPath(),
    'directories' => checkDirectoryPermissions(),
    'pdo' => checkPDOAvailability(),
    'zip' => checkZipSupport(),
    'php' => checkPHPSettings()
];

// Change the output section at the bottom
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // If AJAX request, return JSON
    header('Content-Type: application/json');
    echo json_encode($checks);
    exit;
}

// For direct access, redirect back to backup manager
header('Location: ../backup_manager.php');
exit;
?>
