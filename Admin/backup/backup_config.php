<?php
// Backup Configuration Settings

// Database connection information (will use the main db.php file)
define('DB_CONFIG_PATH', dirname(__FILE__, 3) . '/db.php');

// Paths and directories
define('BACKUP_DIR', dirname(__FILE__) . '/backups'); // Where backups will be stored
define('LOGS_DIR', dirname(__FILE__) . '/logs'); // Where backup logs will be stored

// Auto-detect mysqldump path based on common XAMPP locations
function detectMysqldumpPath() {
    $possible_paths = [
        'C:/xampp/mysql/bin/mysqldump.exe',
        'C:/xampp/bin/mysql/bin/mysqldump.exe', // Alternative XAMPP structure
        'C:/xampp/mysql/bin/mysqldump',
        '/xampp/mysql/bin/mysqldump',
        '/usr/bin/mysqldump', // Linux/Mac
        '/usr/local/bin/mysqldump', // Linux/Mac alternative
        'mysqldump' // Use if in system PATH
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    // Check if mysqldump is in PATH without full path
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - use where command
        $output = [];
        exec('where mysqldump', $output, $return_var);
        if ($return_var === 0 && !empty($output[0])) {
            return $output[0];
        }
    } else {
        // Linux/Mac - use which command
        $output = [];
        exec('which mysqldump', $output, $return_var);
        if ($return_var === 0 && !empty($output[0])) {
            return $output[0];
        }
    }
    
    return 'mysqldump'; // Default as fallback, assuming it's in PATH
}

// Default to auto-detected path, can be overridden manually if needed
define('MYSQLDUMP_PATH', detectMysqldumpPath());

// Retention settings
define('MAX_BACKUPS', 12); // Maximum number of backups to keep (12 weeks = ~3 months)
define('RETENTION_DAYS', 90); // Delete backups older than X days

// Email notification settings
define('SEND_EMAIL_NOTIFICATIONS', true);
define('ADMIN_EMAIL', 'admin@example.com'); // Change this to your admin email
define('EMAIL_FROM', 'librarybackup@nbscollege.edu.ph');
define('EMAIL_SUBJECT_SUCCESS', 'Library System Backup Successful');
define('EMAIL_SUBJECT_FAILURE', 'Library System Backup Failed - ACTION REQUIRED');

// Security
define('BACKUP_ACCESS_KEY', 'change_this_to_a_secure_random_string'); // Used for scheduled task access

// Use PHP's native PDO backup if exec() isn't available
define('USE_PDO_BACKUP_FALLBACK', true);

// Create uncompressed backups if ZIP is not available
define('CREATE_UNCOMPRESSED_BACKUP_IF_NO_ZIP', true);

// Create necessary directories if they don't exist
if (!file_exists(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

if (!file_exists(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0755, true);
}

// Create .htaccess to protect backup directory
$htaccess_content = "Deny from all\n";
$htaccess_file = BACKUP_DIR . '/.htaccess';
if (!file_exists($htaccess_file)) {
    file_put_contents($htaccess_file, $htaccess_content);
}

// Create index.html to prevent directory listing
$index_content = "<html><head><title>Access Denied</title></head><body><h1>Access Denied</h1></body></html>";
$index_file = BACKUP_DIR . '/index.html';
if (!file_exists($index_file)) {
    file_put_contents($index_file, $index_content);
}

// Do the same for logs directory
$logs_htaccess_file = LOGS_DIR . '/.htaccess';
if (!file_exists($logs_htaccess_file)) {
    file_put_contents($logs_htaccess_file, $htaccess_content);
}

$logs_index_file = LOGS_DIR . '/index.html';
if (!file_exists($logs_index_file)) {
    file_put_contents($logs_index_file, $index_content);
}
?>
