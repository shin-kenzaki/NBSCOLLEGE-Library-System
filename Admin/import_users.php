<?php
session_start();
require_once '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables
$message = '';
$status = '';
$importCount = 0;
$errorCount = 0;
$errors = [];
$importedUsers = [];

// Function to send email to users
function sendUserEmail($email, $schoolId, $password, $firstname, $lastname)
{
    $mail = require __DIR__ . '/mailer.php'; // Include the PHPMailer instance

    try {
        $mail->setFrom('cevangelista2021@student.nbscollege.edu.ph', 'Library System');
        $mail->addAddress($email);
        $mail->Subject = 'NBS College Library System - Account Created';
        $mail->Body = "
            <p>Dear $firstname $lastname,</p>
            <p>We are pleased to inform you that your account has been successfully created in the NBS College Library System. Below are your login credentials:</p>
            <p><strong>ID Number:</strong> $schoolId</p>
            <p><strong>Password:</strong> $password</p>
            <p>Please visit the library PC to log in and change your password immediately for security purposes.</p>
            <p>Note: This is an auto-generated email. Please do not reply to this email address.</p>
            <p>Thank you for using the NBS College Library System.</p>
            <p>Best regards,</p>
            <p><strong>NBS College Library System Team</strong></p>
        ";
        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent to $email. Error: {$mail->ErrorInfo}");
    }
}

// Process import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    // Check for errors
    if ($fileError === 0) {
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['xlsx', 'csv'];

        if (in_array($fileExt, $allowedExtensions)) {
            // Process the file based on its type
            if ($fileExt === 'csv') {
                // Process CSV file
                $results = processCSV($fileTmpName, $conn);
            } else {
                // Process XLSX file using PhpSpreadsheet
                $results = processXLSX($fileTmpName, $conn);
            }

            $importCount = $results['success'];
            $errorCount = $results['error'];
            $errors = $results['errors'];
            $importedUsers = $results['imported_users'] ?? [];

            if ($importCount > 0) {
                $status = 'success';
                $message = "Successfully imported $importCount records.";
                if ($errorCount > 0) {
                    $message .= " $errorCount records had errors.";
                }
            } else {
                $status = 'error';
                $message = "No records were imported. Please check your file format.";
                if ($errorCount > 0) {
                    $message .= " $errorCount records had errors.";
                }
            }
        } else {
            $status = 'error';
            $message = "Invalid file type. Please upload a CSV or XLSX file.";
        }
    } else {
        $status = 'error';
        $message = "Error uploading file. Please try again.";
    }
}

function processCSV($filePath, $conn)
{
    $success = 0;
    $error = 0;
    $errors = [];
    $importedUsers = [];

    if (($handle = fopen($filePath, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ",");
        $expectedHeader = ['School_ID', 'Firstname', 'Middle_Initial', 'Lastname', 'Email', 'Contact_No', 'Department', 'Usertype', 'Fullname'];

        // Validate header
        $headerValid = true;
        foreach ($expectedHeader as $index => $column) {
            if (!isset($header[$index]) || strtolower(trim($header[$index])) !== strtolower($column)) {
                $headerValid = false;
                break;
            }
        }

        if (!$headerValid) {
            return ['success' => 0, 'error' => 1, 'errors' => ["CSV header does not match the expected format. Expected: " . implode(", ", $expectedHeader)]];
        }

        $rowNum = 1;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rowNum++;
            if (count($data) < count($expectedHeader)) {
                $error++;
                $errors[] = "Row $rowNum: Not enough columns. Expected " . count($expectedHeader) . ", got " . count($data);
                continue;
            }

            // Map data from CSV format
            $schoolId = trim($data[0]);
            $firstname = trim($data[1]);
            $middleInit = trim($data[2]);
            $lastname = trim($data[3]);
            $email = !empty(trim($data[4])) && trim($data[4]) !== 'N/A' ? trim($data[4]) : null;
            $contactNo = !empty(trim($data[5])) && trim($data[5]) !== 'N/A' ? trim($data[5]) : '';
            $department = trim($data[6]);
            $usertype = trim($data[7]);
            // Fullname is in data[8] but we don't need it since we have the individual name parts

            // Convert 'N/A' to empty strings for name fields
            $firstname = ($firstname === 'N/A') ? '' : $firstname;
            $middleInit = ($middleInit === 'N/A') ? '' : $middleInit;
            $lastname = ($lastname === 'N/A') ? '' : $lastname;

            // Skip empty rows
            if (empty($schoolId) && empty($firstname) && empty($lastname)) {
                continue;
            }

            // Basic validation
            if (empty($schoolId) || !is_numeric($schoolId)) {
                $error++;
                $errors[] = "Row $rowNum: Invalid ID number. Must be numeric.";
                continue;
            }

            if (empty($firstname) || empty($lastname)) {
                $error++;
                $errors[] = "Row $rowNum: First name and last name are required.";
                continue;
            }

            if (empty($usertype)) {
                $error++;
                $errors[] = "Row $rowNum: Usertype is required.";
                continue;
            }

            // Generate email if not provided
            if ($email === null) {
                $firstnameLetter = strtolower(substr($firstname, 0, 1));
                $lastnameForEmail = strtolower(str_replace(' ', '', $lastname));
                $yearFromId = '20' . substr($schoolId, 0, 2);
                $email = $firstnameLetter . $lastnameForEmail . $yearFromId . "@student.nbscollege.edu.ph";
            }

            // Generate random password
            $password = generateStrongPassword(12);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Set default values
            $user_image = '../Images/Profile/default-avatar.jpg';
            $address = '';
            $id_type = '';
            $id_image = '/upload/default-id.png';
            $status = '1'; // Active

            // Check if user already exists
            $checkSql = "SELECT id FROM users WHERE school_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $schoolId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                // Update existing record
                $updateSql = "UPDATE users SET firstname = ?, middle_init = ?, lastname = ?, email = ?, contact_no = ?, department = ?, usertype = ? WHERE school_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("sssssssi", $firstname, $middleInit, $lastname, $email, $contactNo, $department, $usertype, $schoolId);

                if ($updateStmt->execute()) {
                    $success++;
                } else {
                    $error++;
                    $errors[] = "Row $rowNum: Error updating record: " . $conn->error;
                }
                $updateStmt->close();
            } else {
                // Insert new record
                $insertSql = "INSERT INTO users (school_id, firstname, middle_init, lastname, email, password, contact_no, user_image, department, usertype, address, id_type, id_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("issssssssssssi", $schoolId, $firstname, $middleInit, $lastname, $email, $hashed_password, $contactNo, $user_image, $department, $usertype, $address, $id_type, $id_image, $status);

                if ($insertStmt->execute()) {
                    $success++;
                    $importedUsers[] = [
                        'id' => $schoolId,
                        'name' => "$firstname $middleInit $lastname",
                        'email' => $email,
                        'password' => $password, // Plain text password for email
                        'usertype' => $usertype,
                        'department' => $department
                    ];

                    // Comment out email sending
                    // sendUserEmail($email, $schoolId, $password, $firstname, $lastname);
                } else {
                    $error++;
                    $errors[] = "Row $rowNum: Error inserting record: " . $conn->error;
                }
                $insertStmt->close();
            }

            $checkStmt->close();
        }
        fclose($handle);
    } else {
        $error++;
        $errors[] = "Failed to open CSV file.";
    }

    return [
        'success' => $success,
        'error' => $error,
        'errors' => $errors,
        'imported_users' => $importedUsers
    ];
}


/**
 * Process XLSX file
 *
 * @param string $filePath Path to the XLSX file
 * @param mysqli $conn Database connection
 * @return array Results of the import process
 */
function processXLSX($filePath, $conn)
{
    $success = 0;
    $error = 0;
    $errors = [];
    $importedUsers = [];

    // Check if PhpSpreadsheet is installed
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        // Try to include it from vendor directory if available
        $vendorPath = __DIR__ . '/vendor/autoload.php';
        if (file_exists($vendorPath)) {
            require_once $vendorPath;
        } else {
            return [
                'success' => 0,
                'error' => 1,
                'errors' => ["PhpSpreadsheet library is not installed. Please install it using Composer or use CSV format instead."]
            ];
        }
    }

    try {
        // Load the spreadsheet
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Check the header row (row 1)
        $expectedHeader = ['School_ID', 'Firstname', 'Middle_Initial', 'Lastname', 'Email', 'Contact_No', 'Department', 'Usertype', 'Fullname'];
        $headerMatch = true;

        for ($col = 1; $col <= count($expectedHeader); $col++) {
            $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
            if (!$worksheet->cellExists($cellCoordinate)) {
                $headerMatch = false;
                break;
            }
            $cellValue = $worksheet->getCell($cellCoordinate)->getValue();
            if (strtolower(trim($cellValue)) !== strtolower($expectedHeader[$col - 1])) {
                $headerMatch = false;
                break;
            }
        }

        if (!$headerMatch) {
            return ['success' => 0, 'error' => 1, 'errors' => ["XLSX header does not match the expected format. Please ensure your file has these columns: " . implode(", ", $expectedHeader)]];
        }

        // Get the highest row number
        $highestRow = $worksheet->getHighestRow();

        // Process each row starting from row 2 (after header)
        for ($row = 2; $row <= $highestRow; $row++) {
            // Get cell values from each column
            $schoolId = trim($worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1) . $row)->getValue());
            $firstname = trim($worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2) . $row)->getValue());
            $middleInit = trim($worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3) . $row)->getValue());
            $lastname = trim($worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4) . $row)->getValue());
            $email = trim($worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5) . $row)->getValue());
            $contactNo = trim($worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(6) . $row)->getValue());
            $department = trim($worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(7) . $row)->getValue());
            $usertype = trim($worksheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(8) . $row)->getValue());
            // Fullname is in column 9 but we don't need it since we have the individual name parts

            // Use N/A as empty for names and contact fields
            $firstname = ($firstname === 'N/A') ? '' : $firstname;
            $middleInit = ($middleInit === 'N/A') ? '' : $middleInit;
            $lastname = ($lastname === 'N/A') ? '' : $lastname;
            $email = ($email === 'N/A') ? '' : $email;
            $contactNo = ($contactNo === 'N/A') ? '' : $contactNo;

            // Skip empty rows
            if (empty($schoolId) && empty($firstname) && empty($lastname)) {
                continue;
            }

            // Basic validation
            if (empty($schoolId) || !is_numeric($schoolId)) {
                $error++;
                $errors[] = "Row $row: Invalid ID number. Must be numeric.";
                continue;
            }

            if (empty($firstname) || empty($lastname)) {
                $error++;
                $errors[] = "Row $row: First name and last name are required.";
                continue;
            }

            if (empty($usertype)) {
                $error++;
                $errors[] = "Row $row: Usertype is required.";
                continue;
            }

            // Generate email if not provided
            if (empty($email)) {
                $firstnameLetter = strtolower(substr($firstname, 0, 1));
                $lastnameForEmail = strtolower(str_replace(' ', '', $lastname));
                $yearFromId = '20' . substr($schoolId, 0, 2);
                $email = $firstnameLetter . $lastnameForEmail . $yearFromId . "@student.nbscollege.edu.ph";
            }

            // Generate random password
            $password = generateStrongPassword(12);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Set default values
            $user_image = '../Images/Profile/default-avatar.jpg';
            $address = '';
            $id_type = '';
            $id_image = '/upload/default-id.png';
            $status = '1'; // Active

            // Check if user already exists
            $checkSql = "SELECT id FROM users WHERE school_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $schoolId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                // Update existing record
                $updateSql = "UPDATE users SET firstname = ?, middle_init = ?, lastname = ?, email = ?, contact_no = ?, department = ?, usertype = ? WHERE school_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param(
                    "sssssi",
                    $firstname,
                    $middleInit,
                    $lastname,
                    $department,
                    $usertype,
                    $schoolId
                );

                if ($updateStmt->execute()) {
                    $success++;
                } else {
                    $error++;
                    $errors[] = "Row $row: Error updating record: " . $conn->error;
                }
                $updateStmt->close();
            } else {
                // Insert new record
                $insertSql = "INSERT INTO users (
                    school_id, firstname, middle_init, lastname, 
                    email, password, contact_no, user_image,
                    department, usertype, address, id_type,
                    id_image, status
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?
                )";

                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param(
                    "issssssssssssi",
                    $schoolId,
                    $firstname,
                    $middleInit,
                    $lastname,
                    $email,
                    $hashed_password,
                    $contact_no,
                    $user_image,
                    $department,
                    $usertype,
                    $address,
                    $id_type,
                    $id_image,
                    $status
                );

                if ($insertStmt->execute()) {
                    $success++;
                    $importedUsers[] = [
                        'id' => $schoolId,
                        'name' => "$firstname $middleInit $lastname",
                        'email' => $email,
                        'password' => $password,
                        'usertype' => $usertype,
                        'department' => $department
                    ];

                    // Send email to the user
                    sendUserEmail($email, $schoolId, $password, $firstname, $lastname);
                } else {
                    $error++;
                    $errors[] = "Row $row: Error inserting record: " . $conn->error;
                }
                $insertStmt->close();
            }

            $checkStmt->close();
        }
    } catch (Exception $e) {
        $error++;
        $errors[] = "Error processing XLSX file: " . $e->getMessage();
    }

    return [
        'success' => $success,
        'error' => $error,
        'errors' => $errors,
        'imported_users' => $importedUsers
    ];
}

/**
 * Generate a strong random password
 *
 * @param int $length Length of the password
 * @return string The generated password
 */
function generateStrongPassword($length = 12)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Users from CSV/Excel - NBSC Library</title>

    <!-- Use the same header includes as import_books.php -->
    <?php include '../admin/inc/header.php'; ?>

    <style>
        /* Enhanced File Upload Styling */
        .file-upload-container {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
        }

        .file-upload-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background-color: #f8f9fc;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 180px;
        }

        .file-upload-area:hover, .file-upload-area.drag-over {
            border-color: #4e73df;
            background-color: rgba(78, 115, 223, 0.05);
        }

        .file-upload-area .upload-icon {
            font-size: 2rem;
            color: #4e73df;
            margin-bottom: 10px;
        }

        .file-upload-area .upload-text {
            color: #6e707e;
            margin-bottom: 10px;
        }

        .file-upload-area .upload-hint {
            font-size: 0.8rem;
            color: #858796;
        }

        .file-upload-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-preview-container {
            display: none;
            margin-top: 15px;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .file-preview-container.show {
            display: block;
        }

        .csv-preview {
            padding: 15px;
            background-color: #f8f9fc;
            max-height: 200px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }

        .csv-preview-header {
            background-color: #4e73df;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
        }

        .file-info {
            padding: 10px 15px;
            background: #f8f9fc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e3e6f0;
        }

        .file-info .file-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 70%;
            font-weight: bold;
        }

        .file-info .file-size {
            color: #858796;
        }

        .file-info .file-icon {
            margin-right: 10px;
            color: #4e73df;
        }

        .file-actions {
            display: flex;
            padding: 10px 15px;
            border-top: 1px solid #e3e6f0;
            background-color: #f8f9fc;
        }

        .file-remove {
            color: #e74a3b;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .file-remove:hover {
            color: #be3128;
        }

        .file-validate {
            color: #1cc88a;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            margin-left: auto;
            transition: all 0.2s ease;
        }

        .file-validate:hover {
            color: #169a6e;
        }

        /* Progress Bar Animation */
        @keyframes progress-bar-stripes {
            from { background-position: 1rem 0; }
            to { background-position: 0 0; }
        }

        .progress-bar {
            transition: width 0.4s ease;
        }
        
        .progress-bar.complete {
            background-color: #1cc88a !important;
            transition: background-color 0.5s ease;
        }

        /* Loading Overlay Styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .loading-content {
            width: 90%;
            max-width: 500px;
        }
        
        .processing-log-container {
            margin-bottom: 1rem;
        }
        
        #processingInfo {
            font-family: monospace;
            font-size: 0.85rem;
            line-height: 1.5;
        }
        
        #processingInfo div {
            margin-bottom: 0.25rem;
            padding: 0.25rem 0;
            border-bottom: 1px dotted #e0e0e0;
        }
        
        #processingInfo div:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>
    <!-- Main Content -->
    <div id="content" class="d-flex flex-column min-vh-100">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Import Users from CSV/Excel</h1>
                <a href="users_list.php" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>

            <!-- File Format Instructions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">CSV/Excel File Format</h6>
                </div>
                <div class="card-body">
                    <p>The file should have the following columns:</p>
                    <div class="row">
                        <div class="col-md-6">
                            <ol>
                                <li>School_ID</li>
                                <li>Firstname</li>
                                <li>Middle_Initial</li>
                                <li>Lastname</li>
                                <li>Email</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <ol start="6">
                                <li>Contact_No</li>
                                <li>Department</li>
                                <li>Usertype</li>
                                <li>Fullname</li>
                            </ol>
                        </div>
                    </div>
                    <p><strong>Example:</strong></p>
                    <div class="csv-example">
                        School_ID,Firstname,Middle_Initial,Lastname,Email,Contact_No,Department,Usertype,Fullname
                        210078,Kenneth,P.,Bonaagua,N/A,N/A,Computer Science,Student,"BONAAGUA, KENNETH"
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> Note: 
                        <ul>
                            <li>The first row must contain the exact column names as shown above.</li>
                            <li>For empty fields, use N/A or leave blank.</li>
                            <li>Email and Contact_No are optional - system will generate email addresses for empty fields.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Enhanced Upload Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Upload File</h6>
                </div>
                <div class="card-body">
                    <form id="csvUploadForm" action="" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="import_file">Select CSV or Excel File:</label>
                            
                            <!-- Enhanced File Upload Container -->
                            <div class="file-upload-container">
                                <div class="file-upload-area">
                                    <i class="fas fa-file-upload upload-icon"></i>
                                    <div class="upload-text">Drag & drop your CSV or Excel file here</div>
                                    <div class="upload-hint">or click to browse files</div>
                                </div>
                                <input type="file" class="file-upload-input" id="import_file" name="import_file" accept=".csv,.xlsx" required>
                                <div class="invalid-feedback">Please select a valid CSV or Excel file.</div>
                                
                                <!-- File Preview Container -->
                                <div class="file-preview-container">
                                    <div class="csv-preview-header">
                                        File Preview
                                    </div>
                                    <div class="csv-preview" id="csvPreview">
                                        <!-- File preview content will be shown here -->
                                    </div>
                                    <div class="file-info">
                                        <div>
                                            <i class="fas fa-file-csv file-icon"></i>
                                            <span class="file-name">No file selected</span>
                                        </div>
                                        <span class="file-size">0 KB</span>
                                    </div>
                                    <div class="file-actions">
                                        <div class="file-remove">
                                            <i class="fas fa-trash-alt mr-1"></i> Remove
                                        </div>
                                        <div class="file-validate">
                                            <i class="fas fa-check-circle mr-1"></i> Validate Structure
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <small class="form-text text-muted mt-2">Please upload a valid CSV or Excel file with the correct format.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-import"></i> Upload and Import
                        </button>
                    </form>
                </div>
            </div>

            <!-- Import Results & Loading Overlay - Similar to import_books.php -->
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Import Results</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle"></i> Errors:</h5>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="import-summary">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Summary:</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body">
                                                    <h5 class="card-title">Successfully Imported</h5>
                                                    <p class="card-text display-4"><?php echo $importCount; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-danger text-white">
                                                <div class="card-body">
                                                    <h5 class="card-title">Errors</h5>
                                                    <p class="card-text display-4"><?php echo $errorCount; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($importCount > 0): ?>
                            <div class="mt-4">
                                <a href="users_list.php" class="btn btn-success">
                                    <i class="fas fa-list"></i> View Users List
                                </a>
                                <a href="import_users.php" class="btn btn-primary ml-2">
                                    <i class="fas fa-upload"></i> Import More Users
                                </a>
                                <button id="exportUsersBtn" class="btn btn-info ml-2">
                                    <i class="fas fa-file-export"></i> Export Credentials
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Table of Imported Users with Credentials -->
                <?php if (!empty($importedUsers)): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Imported Users</h6>
                            <span class="badge badge-pill badge-success"><?php echo count($importedUsers); ?> Users</span>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> The passwords shown below are temporary. Users should change them upon first login. Please save this information before leaving the page!
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="importedUsersTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Department</th>
                                            <th>User Type</th>
                                            <th>Password</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($importedUsers as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['department']); ?></td>
                                                <td><?php echo htmlspecialchars($user['usertype']); ?></td>
                                                <td>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control form-control-sm password-field" value="<?php echo htmlspecialchars($user['password']); ?>" readonly>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary copy-password" data-password="<?php echo htmlspecialchars($user['password']); ?>">
                                                        <i class="fas fa-copy"></i> Copy
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                        $(document).ready(function() {
                            // Initialize DataTable
                            $('#importedUsersTable').DataTable({
                                "pageLength": 10,
                                "order": [[0, "asc"]],
                                "language": {
                                    "search": "_INPUT_",
                                    "searchPlaceholder": "Search imported users..."
                                }
                            });
                            
                            // Copy password to clipboard
                            $('.copy-password').on('click', function() {
                                const password = $(this).data('password');
                                const tempInput = document.createElement('input');
                                document.body.appendChild(tempInput);
                                tempInput.value = password;
                                tempInput.select();
                                document.execCommand('copy');
                                document.body.removeChild(tempInput);
                                
                                // Show tooltip or notification
                                Swal.fire({
                                    title: 'Copied!',
                                    text: 'Password copied to clipboard',
                                    icon: 'success',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000
                                });
                            });
                            
                            // Export credentials as CSV
                            $('#exportUsersBtn').on('click', function() {
                                // Create CSV content
                                const csvContent = "ID,Name,Email,Department,User Type,Password\n";
                                const rows = [];
                                
                                // Add each user to the CSV
                                <?php foreach ($importedUsers as $user): ?>
                                rows.push([
                                    "<?php echo addslashes($user['id']); ?>",
                                    "<?php echo addslashes($user['name']); ?>",
                                    "<?php echo addslashes($user['email']); ?>",
                                    "<?php echo addslashes($user['department']); ?>",
                                    "<?php echo addslashes($user['usertype']); ?>",
                                    "<?php echo addslashes($user['password']); ?>"
                                ].join(","));
                                <?php endforeach; ?>
                                
                                // Create and download the CSV file
                                const csv = csvContent + rows.join("\n");
                                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                                const url = URL.createObjectURL(blob);
                                const link = document.createElement('a');
                                link.setAttribute('href', url);
                                link.setAttribute('download', 'imported_users_credentials.csv');
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                            });
                        });
                    </script>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Loading Overlay for CSV Processing -->
            <div id="loadingOverlay" class="loading-overlay" style="display: none;">
                <div class="loading-content">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="m-0"><i class="fas fa-sync fa-spin me-2"></i> Processing User Import</h5>
                        </div>
                        <div class="card-body">
                            <div class="progress mb-4" style="height: 25px;">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" 
                                     aria-valuemax="100">0%</div>
                            </div>
                            
                            <p id="progressText" class="text-center mb-3">Initializing import process...</p>
                            
                            <div class="processing-log-container">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="m-0 font-weight-bold">Processing Log</h6>
                                    </div>
                                    <div class="card-body p-2" style="height: 150px; overflow-y: auto;">
                                        <div id="processingInfo" class="small"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add SheetJS (for Excel export) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <!-- Add jsPDF for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

    <script>
        function copyPassword(password) {
            const tempInput = document.createElement('input');
            tempInput.value = password;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            // Show a tooltip or notification
            showToast('Password copied to clipboard');
        }

        function showToast(message) {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.textContent = message;
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
            toast.style.color = 'white';
            toast.style.padding = '10px 20px';
            toast.style.borderRadius = '5px';
            toast.style.zIndex = '9999';

            // Add to document
            document.body.appendChild(toast);

            // Remove after timeout
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 500);
            }, 2000);
        }

        function exportToExcel() {
            const table = document.getElementById('users-password-table');
            const wb = XLSX.utils.table_to_book(table, {
                sheet: "Imported Users"
            });
            XLSX.writeFile(wb, 'imported_users_' + new Date().toISOString().slice(0, 10) + '.xlsx');

            showToast('Exported to Excel successfully');
        }

        function exportToPDF() {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF('landscape');

            // Set document properties
            doc.setProperties({
                title: 'Imported Users - NBSC Library System',
                subject: 'User Credentials',
                author: 'NBSC Library System',
                creator: 'NBSC Library System'
            });

            // Get page dimensions
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const marginLeft = 10;
            const marginRight = 10;
            const availableWidth = pageWidth - marginLeft - marginRight;

            // Draw a colored header background for the entire width of the page
            doc.setFillColor(78, 115, 223);
            doc.rect(0, 0, pageWidth, 20, 'F');

            // Add title across full width of the page with proper styling
            doc.setFontSize(18);
            doc.setFont(undefined, 'bold');
            doc.setTextColor(255, 255, 255); // White text on blue background
            doc.text('NBSC Library - Imported Users', pageWidth / 2, 14, {
                align: 'center'
            });

            // Add date and info text
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.setTextColor(0, 0, 0);
            doc.text('Generated on ' + new Date().toLocaleString(), pageWidth / 2, 25, {
                align: 'center'
            });
            doc.setTextColor(255, 0, 0);
            doc.text('IMPORTANT: Store these credentials in a secure location.', pageWidth / 2, 30, {
                align: 'center'
            });
            doc.setTextColor(0, 0, 0);

            // Create data for table
            const table = document.getElementById('users-password-table');
            const tableData = [];

            // Get headers
            const headers = [];
            table.querySelectorAll('thead th').forEach((th, index) => {
                // Skip the 'Action' column (last column)
                if (index < 6) {
                    headers.push(th.textContent.trim());
                }
            });

            // Get rows
            table.querySelectorAll('tbody tr').forEach(tr => {
                const row = [];
                tr.querySelectorAll('td').forEach((td, index) => {
                    // Skip the 'Action' column (last column)
                    if (index < 6) {
                        // For password column (index 5), get the text from the span
                        if (index === 5) {
                            const passwordField = td.querySelector('.password-field');
                            if (passwordField) {
                                row.push(passwordField.textContent.trim());
                            } else {
                                row.push('');
                            }
                        } else {
                            row.push(td.textContent.trim());
                        }
                    }
                });
                tableData.push(row);
            });

            // Calculate proportional column widths that use the full page width
            // Set column width proportions (total should be 1)
            const colProportions = [0.08, 0.22, 0.30, 0.15, 0.10, 0.15];
            const colWidths = {};

            colProportions.forEach((proportion, index) => {
                colWidths[index] = availableWidth * proportion;
            });

            // Add table to the PDF using autoTable plugin
            doc.autoTable({
                head: [headers],
                body: tableData,
                startY: 35, // Increased to make room for the header
                theme: 'striped',
                margin: {
                    left: marginLeft,
                    right: marginRight
                },
                styles: {
                    fontSize: 8,
                    cellPadding: 2,
                    overflow: 'linebreak',
                    halign: 'left'
                },
                headStyles: {
                    fillColor: [78, 115, 223],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                columnStyles: {
                    0: {
                        cellWidth: colWidths[0]
                    }, // ID
                    1: {
                        cellWidth: colWidths[1]
                    }, // Name
                    2: {
                        cellWidth: colWidths[2]
                    }, // Email
                    3: {
                        cellWidth: colWidths[3]
                    }, // Department
                    4: {
                        cellWidth: colWidths[4]
                    }, // User Type
                    5: {
                        cellWidth: colWidths[5]
                    } // Password
                },
                alternateRowStyles: {
                    fillColor: [240, 240, 240]
                }
            });

            // Add footer note
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(100);
                doc.text(
                    'Page ' + i + ' of ' + pageCount,
                    doc.internal.pageSize.getWidth() / 2,
                    doc.internal.pageSize.getHeight() - 10, {
                        align: 'center'
                    }
                );
                doc.text(
                    'Confidential - For authorized personnel only',
                    doc.internal.pageSize.getWidth() / 2,
                    doc.internal.pageSize.getHeight() - 5, {
                        align: 'center'
                    }
                );
            }

            // Save the PDF
            doc.save('imported_users_' + new Date().toISOString().slice(0, 10) + '.pdf');

            showToast('Exported to PDF successfully');
        }

        function copyAllPasswords() {
            const table = document.getElementById('users-password-table');
            const rows = table.querySelectorAll('tbody tr');

            let text = "ID\tName\tEmail\tDepartment\tUser Type\tPassword\n";

            rows.forEach(row => {
                const columns = row.querySelectorAll('td');
                text += columns[0].textContent + "\t"; // ID
                text += columns[1].textContent + "\t"; // Name
                text += columns[2].textContent + "\t"; // Email
                text += columns[3].textContent + "\t"; // Department
                text += columns[4].textContent + "\t"; // User Type
                text += columns[5].textContent.trim() + "\n"; // Password
            });

            const tempInput = document.createElement('textarea');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            showToast('All user data copied to clipboard');
        }
    </script>
</body>

</html>