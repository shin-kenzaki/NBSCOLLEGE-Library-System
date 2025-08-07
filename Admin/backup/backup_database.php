<?php
// Load configuration
require_once 'backup_config.php';
require_once DB_CONFIG_PATH;

// Functions for backup operations
class DatabaseBackup {
    private $conn;
    private $db_name;
    private $db_user;
    private $db_pass;
    private $db_host;
    private $backup_dir;
    private $logs_dir;
    private $max_backups;
    private $retention_days;
    private $log_file;
    private $error_message = '';
    private $success_message = '';
    private $backup_file = '';

    public function __construct() {
        // Include db.php directly to ensure variables are available
        require_once DB_CONFIG_PATH;
        global $conn, $hostname, $username, $password, $database;
        
        // Use global database connection from db.php
        $this->conn = $conn;
        $this->db_name = $database;
        $this->db_user = $username;
        $this->db_pass = $password;
        $this->db_host = $hostname;
        
        $this->backup_dir = BACKUP_DIR;
        $this->logs_dir = LOGS_DIR;
        $this->max_backups = MAX_BACKUPS;
        $this->retention_days = RETENTION_DAYS;
        
        // Create log file name with date
        $date = date('Y-m-d');
        $this->log_file = $this->logs_dir . "/backup_log_{$date}.txt";
        
        // Log database connection information for debugging
        $this->log("Database connection initialized: host={$this->db_host}, db={$this->db_name}, user={$this->db_user}");
    }
    
    // Log messages to file
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
        return $log_message;
    }
    
    // Check if exec() function is available
    private function isExecEnabled() {
        $disabled = explode(',', ini_get('disable_functions'));
        return !in_array('exec', $disabled) && function_exists('exec');
    }
    
    // Generate SQL using PHP's native functions
    private function generateSqlUsingPdo() {
        try {
            $this->log("Using PDO for database export (fallback method)");
            
            $dsn = "mysql:host={$this->db_host};dbname={$this->db_name};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->db_user, $this->db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Start SQL file with header
            $sql = "-- Library System Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Server: {$this->db_host}\n";
            $sql .= "-- Database: {$this->db_name}\n\n";
            
            // Get all tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $table) {
                $this->log("Backing up table: $table");
                
                // Table structure
                $sql .= "-- Table structure for table `$table`\n";
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                
                $createStmt = $pdo->query("SHOW CREATE TABLE `$table`");
                $createTable = $createStmt->fetch();
                $sql .= $createTable['Create Table'] . ";\n\n";
                
                // Table data
                $stmt = $pdo->query("SELECT * FROM `$table`");
                $rows = $stmt->fetchAll();
                
                if (count($rows) > 0) {
                    $sql .= "-- Dumping data for table `$table`\n";
                    $sql .= "INSERT INTO `$table` VALUES\n";
                    
                    $values = [];
                    foreach ($rows as $row) {
                        $rowValues = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $rowValues[] = 'NULL';
                            } else {
                                $rowValues[] = $pdo->quote($value);
                            }
                        }
                        $values[] = '(' . implode(', ', $rowValues) . ')';
                    }
                    
                    $sql .= implode(",\n", $values) . ";\n\n";
                }
            }
            
            return $sql;
        } catch (Exception $e) {
            $this->log("PDO backup method failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Perform the backup
    public function backup() {
        $this->log("Starting database backup process...");
        $this->log("Using mysqldump path: " . MYSQLDUMP_PATH);
        
        try {
            // Create backup filename with timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $backup_filename = "library_backup_{$timestamp}.sql";
            $backup_file_path = $this->backup_dir . '/' . $backup_filename;
            
            // Check if ZIP is available
            $zip_available = class_exists('ZipArchive');
            $use_compression = $zip_available;
            
            if (!$zip_available) {
                if (CREATE_UNCOMPRESSED_BACKUP_IF_NO_ZIP) {
                    $this->log("INFO: ZIP extension not available. Creating uncompressed backup (fallback mode).");
                } else {
                    throw new Exception("ZIP extension is required but not available, and uncompressed backup creation is disabled");
                }
            } else {
                $this->log("ZIP extension available. Backup will be compressed.");
            }
            
            $this->log("Backup file path: $backup_file_path");
            
            // Check if exec() is available for using mysqldump
            if ($this->isExecEnabled()) {
                $this->log("Using mysqldump command for database export");
                
                // Build the mysqldump command
                $escapedPassword = str_replace("'", "\\'", $this->db_pass); // Escape single quotes
                
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Windows-specific command format
                    $command = sprintf(
                        '"%s" --host="%s" --user="%s" --password="%s" --databases %s --result-file="%s" --add-drop-table --skip-lock-tables --single-transaction',
                        MYSQLDUMP_PATH,
                        $this->db_host,
                        $this->db_user,
                        $escapedPassword,
                        $this->db_name,
                        $backup_file_path
                    );
                } else {
                    // Linux/Mac command format
                    $command = sprintf(
                        '%s --host=%s --user=%s --password=\'%s\' --databases %s --result-file=%s --add-drop-table --skip-lock-tables --single-transaction',
                        MYSQLDUMP_PATH,
                        $this->db_host,
                        $this->db_user,
                        $escapedPassword,
                        $this->db_name,
                        $backup_file_path
                    );
                }
                
                // Log command for debugging (remove password for security)
                $safe_command = preg_replace('/(--password=)([^\s]+)/', '$1*****', $command);
                $this->log("Executing command: $safe_command");
                
                // Execute command and capture output
                $output = [];
                $return_var = 0;
                exec($command . " 2>&1", $output, $return_var);
                
                if ($return_var !== 0) {
                    $this->log("mysqldump failed with error code: $return_var");
                    $this->log("Output: " . print_r($output, true));
                    
                    // Try alternative approach if exec() fails
                    if (USE_PDO_BACKUP_FALLBACK) {
                        $this->log("Attempting backup using PDO fallback method");
                        $sql = $this->generateSqlUsingPdo();
                        file_put_contents($backup_file_path, $sql);
                        $this->log("PDO backup method completed");
                    } else {
                        throw new Exception("mysqldump command failed: " . implode("\n", $output));
                    }
                }
            } else {
                // If exec() is disabled, use PDO method
                $this->log("exec() function is disabled. Using PDO method instead");
                if (USE_PDO_BACKUP_FALLBACK) {
                    $sql = $this->generateSqlUsingPdo();
                    file_put_contents($backup_file_path, $sql);
                    $this->log("PDO backup method completed");
                } else {
                    throw new Exception("exec() function is disabled and PDO fallback is disabled");
                }
            }
            
            // Check if the backup file was created successfully
            if (!file_exists($backup_file_path) || filesize($backup_file_path) === 0) {
                throw new Exception("Backup file was not created or is empty");
            }
            
            $this->log("Database exported successfully to $backup_filename");
            
            // Handle compression if available
            if ($use_compression) {
                $compressed_backup_filename = "library_backup_{$timestamp}.zip";
                $compressed_backup_path = $this->backup_dir . '/' . $compressed_backup_filename;
                
                // Compress the SQL file
                $zip = new ZipArchive();
                if ($zip->open($compressed_backup_path, ZipArchive::CREATE) === TRUE) {
                    $zip->addFile($backup_file_path, $backup_filename);
                    $zip->close();
                    
                    // Delete the uncompressed SQL file
                    unlink($backup_file_path);
                    
                    $this->log("Backup compressed successfully to $compressed_backup_filename");
                    $this->backup_file = $compressed_backup_path;
                    $this->success_message = "Database backup completed successfully. File: $compressed_backup_filename";
                    $final_filename = $compressed_backup_filename;
                    $final_path = $compressed_backup_path;
                } else {
                    throw new Exception("Failed to create zip archive");
                }
            } else {
                // Use uncompressed backup
                $this->log("Backup saved as uncompressed SQL file: $backup_filename");
                $this->backup_file = $backup_file_path;
                $this->success_message = "Database backup completed successfully (uncompressed). File: $backup_filename";
                $final_filename = $backup_filename;
                $final_path = $backup_file_path;
            }
            
            // Apply retention policy
            $this->applyRetentionPolicy();
            
            // Send notification
            if (SEND_EMAIL_NOTIFICATIONS) {
                $this->sendNotification(true);
            }
            
            return [
                'success' => true,
                'filename' => $final_filename,
                'path' => $final_path,
                'message' => $this->success_message
            ];
            
        } catch (Exception $e) {
            $this->error_message = "Backup failed: " . $e->getMessage();
            $this->log("ERROR: " . $this->error_message);
            
            // Send failure notification
            if (SEND_EMAIL_NOTIFICATIONS) {
                $this->sendNotification(false);
            }
            
            return [
                'success' => false,
                'message' => $this->error_message
            ];
        }
    }
    
    // Apply retention policy - delete old backups
    private function applyRetentionPolicy() {
        $this->log("Applying retention policy...");
        
        // Get all backup files (both .zip and .sql)
        $backup_files = array_merge(
            glob($this->backup_dir . '/library_backup_*.zip'),
            glob($this->backup_dir . '/library_backup_*.sql')
        );
        
        // Sort by modification time (newest first)
        usort($backup_files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Keep only MAX_BACKUPS most recent files
        if (count($backup_files) > $this->max_backups) {
            $this->log("Found " . count($backup_files) . " backups, keeping only " . $this->max_backups . " most recent");
            
            for ($i = $this->max_backups; $i < count($backup_files); $i++) {
                // Check if the file is older than retention days
                if (time() - filemtime($backup_files[$i]) > $this->retention_days * 86400) {
                    $filename = basename($backup_files[$i]);
                    if (unlink($backup_files[$i])) {
                        $this->log("Deleted old backup: $filename");
                    } else {
                        $this->log("Failed to delete old backup: $filename");
                    }
                }
            }
        } else {
            $this->log("Found " . count($backup_files) . " backups, no need to apply retention policy yet");
        }
        
        // Update the backup status file
        $this->updateBackupStatusFile();
    }
    
    // New method to ensure backup status is always up to date
    private function updateBackupStatusFile() {
        $status_file = $this->logs_dir . '/backup_status.json';
        
        // Get current status or create a new one
        $status = [];
        if (file_exists($status_file)) {
            $status_content = file_get_contents($status_file);
            if (!empty($status_content)) {
                $decoded = json_decode($status_content, true);
                if (is_array($decoded)) {
                    $status = $decoded;
                }
            }
        }
        
        // Get backup files for status update (both .zip and .sql)
        $backup_files = array_merge(
            glob($this->backup_dir . '/library_backup_*.zip'),
            glob($this->backup_dir . '/library_backup_*.sql')
        );
        
        if (!empty($backup_files)) {
            // Sort by modification time (newest first)
            usort($backup_files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            // Get the newest backup details
            $newest_backup = $backup_files[0];
            $backup_date = date('Y-m-d', filemtime($newest_backup));
            $backup_timestamp = date('Y-m-d H:i:s', filemtime($newest_backup));
            
            // Update the status information
            $status['last_run'] = $backup_timestamp;
            $status['last_status'] = 'success';
            $status['last_successful_backup'] = $backup_date;
            $status['last_successful_file'] = basename($newest_backup);
            $status['last_successful_size'] = filesize($newest_backup);
            $status['last_message'] = 'Backup file: ' . basename($newest_backup);
        }
        
        // Save the updated status
        file_put_contents($status_file, json_encode($status, JSON_PRETTY_PRINT));
        $this->log("Backup status file updated");
    }
    
    // Send email notification
    private function sendNotification($success = true) {
        $to = ADMIN_EMAIL;
        $subject = $success ? EMAIL_SUBJECT_SUCCESS : EMAIL_SUBJECT_FAILURE;
        $from = EMAIL_FROM;
        
        // Create email headers
        $headers = 'From: ' . $from . "\r\n" .
            'Reply-To: ' . $from . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
        
        // Create email content
        if ($success) {
            $message = "Library System Database Backup Completed Successfully\n\n";
            $message .= "Backup Time: " . date('Y-m-d H:i:s') . "\n";
            $message .= "Backup File: " . basename($this->backup_file) . "\n";
            $message .= "File Size: " . round(filesize($this->backup_file) / 1024 / 1024, 2) . " MB\n\n";
            $message .= "This is an automated message from your Library System Backup Service.";
        } else {
            $message = "⚠️ IMPORTANT: Library System Database Backup FAILED ⚠️\n\n";
            $message .= "Backup Attempt Time: " . date('Y-m-d H:i:s') . "\n";
            $message .= "Error Message: " . $this->error_message . "\n\n";
            $message .= "Please check the backup system and ensure your database is being backed up properly.\n";
            $message .= "Check the log file for more details: " . basename($this->log_file) . "\n\n";
            $message .= "This is an automated message from your Library System Backup Service.";
        }
        
        // Send the email
        $mail_sent = @mail($to, $subject, $message, $headers);
        
        if ($mail_sent) {
            $this->log("Notification email sent to $to");
        } else {
            $this->log("Failed to send notification email");
        }
    }
    
    // Get all available backups
    public function getBackups() {
        $backups = [];
        // Get both .zip and .sql backup files
        $backup_files = array_merge(
            glob($this->backup_dir . '/library_backup_*.zip'),
            glob($this->backup_dir . '/library_backup_*.sql')
        );
        
        // Sort by modification time (newest first)
        usort($backup_files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        foreach ($backup_files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'type' => pathinfo($file, PATHINFO_EXTENSION) // 'zip' or 'sql'
            ];
        }
        
        return $backups;
    }
    
    // Delete a specific backup file
    public function deleteBackup($filename) {
        $file_path = $this->backup_dir . '/' . basename($filename);
        
        if (file_exists($file_path) && unlink($file_path)) {
            $this->log("Backup deleted manually: " . basename($filename));
            return true;
        }
        
        return false;
    }
    
    // Download a specific backup file
    public function downloadBackup($filename) {
        $file_path = $this->backup_dir . '/' . basename($filename);
        
        if (file_exists($file_path)) {
            $this->log("Backup downloaded: " . basename($filename));
            
            // Determine content type based on file extension
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $content_type = ($extension === 'zip') ? 'application/zip' : 'application/sql';
            
            // Set headers and output file
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $content_type);
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        }
        
        return false;
    }
}

// If this script is called directly, perform backup
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
    $backup = new DatabaseBackup();
    $result = $backup->backup();
    
    if (php_sapi_name() === 'cli') {
        echo $result['success'] ? "Backup successful: " . $result['message'] . PHP_EOL : "Backup failed: " . $result['message'] . PHP_EOL;
    } else {
        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
?>
