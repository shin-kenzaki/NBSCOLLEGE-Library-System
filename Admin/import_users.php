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
            
            if ($importCount > 0) {
                $status = 'success';
                $message = "Successfully imported $importCount records.";
                if ($errorCount > 0) {
                    $message .= " $errorCount records had errors.";
                }
                
                // Store success message in session and redirect
                $_SESSION['import_success'] = $message;
                header("Location: manage_library_users.php");
                exit();
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

/**
 * Process CSV file
 *
 * @param string $filePath Path to the CSV file
 * @param mysqli $conn Database connection
 * @return array Results of the import process
 */
function processCSV($filePath, $conn) {
    $success = 0;
    $error = 0;
    $errors = [];
    
    // Open the CSV file
    if (($handle = fopen($filePath, "r")) !== FALSE) {
        // Read the header row
        $header = fgetcsv($handle, 1000, ",");
        
        // Check if the header matches our expected format
        $expectedHeader = ['Student Number', 'Course', 'Year', 'Firstname', 'Middle Initial', 'Lastname', 'Gender'];
        $headerMatch = true;
        
        // Check if all expected columns exist
        foreach ($expectedHeader as $index => $column) {
            if (!isset($header[$index]) || strtolower(trim($header[$index])) !== strtolower($column)) {
                $headerMatch = false;
                break;
            }
        }
        
        if (!$headerMatch) {
            return ['success' => 0, 'error' => 1, 'errors' => ["CSV header does not match the expected format. Please ensure your file has these columns: " . implode(", ", $expectedHeader)]];
        }
        
        // Process each row
        $rowNum = 1; // Start from 1 to account for header
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rowNum++;
            
            // Check if we have enough columns
            if (count($data) < 7) {
                $error++;
                $errors[] = "Row $rowNum: Not enough columns. Expected 7, got " . count($data);
                continue;
            }
            
            // Extract data
            $studentNumber = trim($data[0]);
            $course = trim($data[1]);
            $year = trim($data[2]);
            $firstname = trim($data[3]);
            $middleInit = trim($data[4]);
            $lastname = trim($data[5]);
            $gender = trim($data[6]);
            
            // Basic validation
            if (empty($studentNumber) || !is_numeric($studentNumber)) {
                $error++;
                $errors[] = "Row $rowNum: Invalid student number. Must be numeric.";
                continue;
            }
            
            if (empty($firstname) || empty($lastname)) {
                $error++;
                $errors[] = "Row $rowNum: First name and last name are required.";
                continue;
            }
            
            // Check if student already exists
            $checkSql = "SELECT id FROM physical_login_users WHERE student_number = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $studentNumber);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                // Update existing record
                $updateSql = "UPDATE physical_login_users 
                              SET course = ?, year = ?, firstname = ?, middle_init = ?, lastname = ?, gender = ? 
                              WHERE student_number = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ssssssi", $course, $year, $firstname, $middleInit, $lastname, $gender, $studentNumber);
                
                if ($updateStmt->execute()) {
                    $success++;
                } else {
                    $error++;
                    $errors[] = "Row $rowNum: Error updating record: " . $conn->error;
                }
                $updateStmt->close();
            } else {
                // Insert new record
                $insertSql = "INSERT INTO physical_login_users (student_number, course, year, firstname, middle_init, lastname, gender) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("issssss", $studentNumber, $course, $year, $firstname, $middleInit, $lastname, $gender);
                
                if ($insertStmt->execute()) {
                    $success++;
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
        'errors' => $errors
    ];
}

/**
 * Process XLSX file
 *
 * @param string $filePath Path to the XLSX file
 * @param mysqli $conn Database connection
 * @return array Results of the import process
 */
function processXLSX($filePath, $conn) {
    $success = 0;
    $error = 0;
    $errors = [];
    
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
        $expectedHeader = ['Student Number', 'Course', 'Year', 'Firstname', 'Middle Initial', 'Lastname', 'Gender'];
        $headerMatch = true;
        
        for ($col = 1; $col <= count($expectedHeader); $col++) {
            // Fix: Use cellExists and getCell instead of getCellByColumnAndRow
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
            // Fix: Use proper cell access method for each column
            $colA = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1) . $row;
            $colB = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2) . $row;
            $colC = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3) . $row;
            $colD = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4) . $row;
            $colE = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5) . $row;
            $colF = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(6) . $row;
            $colG = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(7) . $row;
            
            $studentNumber = trim($worksheet->cellExists($colA) ? $worksheet->getCell($colA)->getValue() : '');
            $course = trim($worksheet->cellExists($colB) ? $worksheet->getCell($colB)->getValue() : '');
            $year = trim($worksheet->cellExists($colC) ? $worksheet->getCell($colC)->getValue() : '');
            $firstname = trim($worksheet->cellExists($colD) ? $worksheet->getCell($colD)->getValue() : '');
            $middleInit = trim($worksheet->cellExists($colE) ? $worksheet->getCell($colE)->getValue() : '');
            $lastname = trim($worksheet->cellExists($colF) ? $worksheet->getCell($colF)->getValue() : '');
            $gender = trim($worksheet->cellExists($colG) ? $worksheet->getCell($colG)->getValue() : '');
            
            // Skip empty rows
            if (empty($studentNumber) && empty($firstname) && empty($lastname)) {
                continue;
            }
            
            // Basic validation
            if (empty($studentNumber) || !is_numeric($studentNumber)) {
                $error++;
                $errors[] = "Row $row: Invalid student number. Must be numeric.";
                continue;
            }
            
            if (empty($firstname) || empty($lastname)) {
                $error++;
                $errors[] = "Row $row: First name and last name are required.";
                continue;
            }
            
            // Check if student already exists
            $checkSql = "SELECT id FROM physical_login_users WHERE student_number = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $studentNumber);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                // Update existing record
                $updateSql = "UPDATE physical_login_users 
                              SET course = ?, year = ?, firstname = ?, middle_init = ?, lastname = ?, gender = ? 
                              WHERE student_number = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ssssssi", $course, $year, $firstname, $middleInit, $lastname, $gender, $studentNumber);
                
                if ($updateStmt->execute()) {
                    $success++;
                } else {
                    $error++;
                    $errors[] = "Row $row: Error updating record: " . $conn->error;
                }
                $updateStmt->close();
            } else {
                // Insert new record
                $insertSql = "INSERT INTO physical_login_users (student_number, course, year, firstname, middle_init, lastname, gender) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("issssss", $studentNumber, $course, $year, $firstname, $middleInit, $lastname, $gender);
                
                if ($insertStmt->execute()) {
                    $success++;
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
        'errors' => $errors
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Users - NBSC Library</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="Admin/img/nbslogo.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="Admin/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('Images/BG/library-background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Nunito', sans-serif;
            padding: 2rem 0;
        }
        
        .page-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
        }
        
        h1 {
            color: #4e73df;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .import-steps {
            background-color: rgba(78, 115, 223, 0.1);
            border-left: 4px solid #4e73df;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 0 5px 5px 0;
        }
        
        .import-steps ol {
            margin-bottom: 0;
            padding-left: 1.5rem;
        }
        
        .import-steps li {
            margin-bottom: 0.5rem;
        }
        
        .import-steps li:last-child {
            margin-bottom: 0;
        }
        
        .custom-file-label {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .table-errors {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #4e73df;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
            color: #2e59d9;
        }
        
        .sample-header {
            font-family: monospace;
            background-color: #f8f9fc;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #d1d3e2;
            margin: 15px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container page-container">
        <h1>Import Users</h1>
        
        <?php if ($status === 'success'): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php elseif ($status === 'error'): ?>
            <div class="alert alert-danger">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="import-steps">
            <h5>Instructions:</h5>
            <ol>
                <li>Prepare your Excel (XLSX) or CSV file with the following columns:
                    <div class="sample-header">
                        Student Number, Course, Year, Firstname, Middle Initial, Lastname, Gender
                    </div>
                </li>
                <li>Ensure the first row contains the column headers exactly as shown above</li>
                <li>For CSV files, use comma (,) as the delimiter</li>
                <li>Select your file using the form below and click "Import"</li>
            </ol>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="import_file" class="form-label">Select File (XLSX or CSV)</label>
                <input class="form-control" type="file" id="import_file" name="import_file" accept=".xlsx,.csv" required>
                <div class="form-text">
                    Maximum file size: 5MB
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fas fa-file-import me-2"></i> Import
                </button>
                <a href="manage_library_users.php" class="btn btn-secondary flex-grow-1">
                    <i class="fas fa-times me-2"></i> Cancel
                </a>
            </div>
        </form>
        
        <?php if ($errorCount > 0): ?>
            <div class="mt-4">
                <h5>Import Errors (<?php echo $errorCount; ?>):</h5>
                <div class="table-errors">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($errors as $key => $error): ?>
                                <tr>
                                    <td><?php echo $key + 1; ?></td>
                                    <td><?php echo $error; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="library_entrance.php" class="back-link">
                <i class="fas fa-arrow-left me-1"></i>Display Library Entrance
            </a>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
