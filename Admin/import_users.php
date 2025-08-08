<?php
session_start();
require_once '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

ini_set('max_execution_time', 0);    // No timeout
ini_set('set_time_limit', 0);
ini_set('upload_max_filesize', '0'); // Unlimited file size
ini_set('post_max_size', '0');       // Unlimited POST data
ini_set('memory_limit', '-1');       // Unlimited memory
ini_set('max_input_time', -1);       // Unlimited input parsing time
ini_set('max_file_uploads', 1000);   // Allow up to 1000 files

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
        $mail->setFrom('library@nbscollege.edu.ph', 'Library System');
        $mail->addAddress($email);
        $mail->Subject = 'NBS College Library System - Account Created';
        $mail->Body = "
            <p>Dear $firstname $lastname,</p>
            <p>We are pleased to inform you that your account has been successfully created in the NBS College Library System. Below are your login credentials:</p>
            <p><strong>ID Number:</strong> $schoolId</p>
            <p><strong>Password:</strong> $password</p>
            <p>Please visit the library PC to log in and change your password immediately for security purposes. You may also access our library system through our school wifi at: <a href='http://192.168.8.26/library-system'>192.168.8.26/library-system/user/</a></p>
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
        $allowedExtensions = ['csv'];

        if (in_array($fileExt, $allowedExtensions)) {
            // Process the file based on its type
            if ($fileExt === 'csv') {
                // Process CSV file
                $results = processCSV($fileTmpName, $conn);
            } else {
                $errors[] = 'Only .csv files are allowed.';
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
        $expectedHeader = ['ID', 'Firstname', 'Middle Initial', 'Lastname', 'Gender', 'Department', 'Usertype'];

        // Validate header
        foreach ($expectedHeader as $index => $column) {
            if (!isset($header[$index]) || strtolower(trim($header[$index])) !== strtolower($column)) {
                return ['success' => 0, 'error' => 1, 'errors' => ["CSV header does not match the expected format."]];
            }
        }

        $rowNum = 1;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rowNum++;
            if (count($data) < 7) {
                $error++;
                $errors[] = "Row $rowNum: Not enough columns. Expected 7, got " . count($data);
                continue;
            }

            $schoolId = trim($data[0]);
            $firstname = trim($data[1]);
            $middleInit = trim($data[2]);
            $lastname = trim($data[3]);
            $gender = trim($data[4]);
            $department = trim($data[5]);
            $usertype = trim($data[6]);

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

            // Generate email and password
            $firstnameLetter = strtolower(substr($firstname, 0, 1));
            $lastnameForEmail = strtolower(str_replace(' ', '', $lastname));
            $yearFromId = '20' . substr($schoolId, 0, 2);

            // Combine all first names (no spaces)
            $firstnameNoSpaces = strtolower(str_replace(' ', '', $firstname));
            $lastnameNoSpaces = strtolower(str_replace(' ', '', $lastname));

            $email = '';

            if (strtolower($usertype) === 'student') {
                $email = $firstnameLetter . $lastnameNoSpaces . $yearFromId . "@student.nbscollege.edu.ph";
            } else {
                $email = $firstnameNoSpaces . '.' . $lastnameNoSpaces . "@nbscollege.edu.ph";
            }

            $password = generateStrongPassword(12);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);


            // Set default values
            $contact_no = '';
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
                $updateSql = "UPDATE users SET firstname = ?, middle_init = ?, lastname = ?, department = ?, usertype = ? WHERE school_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("sssssi", $firstname, $middleInit, $lastname, $department, $usertype, $schoolId);

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
                $insertStmt->bind_param("issssssssssssi", $schoolId, $firstname, $middleInit, $lastname, $email, $hashed_password, $contact_no, $user_image, $department, $usertype, $address, $id_type, $id_image, $status);

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

                    // Send email to the user
                    sendUserEmail($email, $schoolId, $password, $firstname, $lastname);
                    // $logMessage = "Simulated sending email to: $email (School ID: $schoolId, Password: $password)" . PHP_EOL;
                    // file_put_contents("email_simulation_log.txt", $logMessage, FILE_APPEND);
                    sleep(1);
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

        .password-table {
            margin-top: 1.5rem;
            overflow-x: auto;
        }

        .password-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .password-table th,
        .password-table td {
            padding: 8px 12px;
            border: 1px solid #e3e6f0;
            text-align: left;
        }

        .password-table th {
            background-color: #f8f9fc;
            color: #4e73df;
            font-weight: 600;
        }

        .password-table tr:nth-child(even) {
            background-color: #f8f9fc;
        }

        .password-table tr:hover {
            background-color: rgba(78, 115, 223, 0.1);
        }

        .copy-button {
            background-color: #4e73df;
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .copy-button:hover {
            background-color: #2e59d9;
        }

        .password-field {
            font-family: monospace;
            background-color: #f8f9fe;
            padding: 3px 8px;
            border-radius: 3px;
            border: 1px solid #e3e6f0;
            display: inline-block;
            min-width: 120px;
        }

        .export-buttons {
            margin-top: 1rem;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .export-btn {
            background-color: #1cc88a;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 8px 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .export-btn:hover {
            background-color: #169b6b;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
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

            <?php if (!empty($importedUsers)): ?>
                <div class="alert alert-info">
                    <h5 class="mb-3">Important:</h5>
                    <p>The following passwords have been generated for the imported users. Please save this information as it will not be displayed again.</p>
                    <p><strong>Tip:</strong> You can use the "Export to Excel" or "Copy All" buttons to save this information for your records.</p>
                </div>

                <div class="password-table">
                    <table id="users-password-table">
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
                                    <td><span class="password-field"><?php echo htmlspecialchars($user['password']); ?></span></td>
                                    <td>
                                        <button type="button" class="copy-button" onclick="copyPassword('<?php echo htmlspecialchars($user['password']); ?>')">
                                            Copy
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="export-buttons">
                    <button type="button" class="export-btn" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                    <button type="button" class="export-btn" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                    <button type="button" class="export-btn" onclick="copyAllPasswords()">
                        <i class="fas fa-copy"></i> Copy All
                    </button>
                    <a href="users_list.php" class="export-btn">
                        <i class="fas fa-users"></i> Go to Users List
                    </a>
                </div>
            <?php else: ?>
                <div class="d-grid gap-2 d-md-flex justify-content-center mt-3">
                    <a href="users_list.php" class="btn btn-primary">
                        <i class="fas fa-users"></i> Go to Users List
                    </a>
                </div>
            <?php endif; ?>

        <?php elseif ($status === 'error'): ?>
            <div class="alert alert-danger">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$status): ?>
            <div class="import-steps">
                <h5>Instructions:</h5>
                <ol>
                    <li>Prepare your Excel CSV file with the following columns:
                        <div class="sample-header">
                            ID, Firstname, Middle Initial, Lastname, Gender, Department, Usertype
                        </div>
                    </li>
                    <li>Ensure the first row contains the column headers exactly as shown above</li>
                    <li>The "ID" column will be used as the school_id in the system</li>
                    <li>The "Department" should be one of: Computer Science, Accounting Information System, Accountancy, Entrepreneurship, Tourism Management</li>
                    <li>The "Usertype" field should be one of: Student, Faculty, Staff, or Visitor</li>
                    <li>For CSV files, use comma (,) as the delimiter</li>
                    <li>Select your file using the form below and click "Import"</li>
                    <li>A system-generated email and password will be created for new users</li>
                </ol>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="import_file" class="form-label">Select File (CSV)</label>
                    <input class="form-control" type="file" id="import_file" name="import_file" accept=".csv" required>
                </div>

                <div class="d-grid gap-2 d-md-flex">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-file-import me-2"></i> Import
                    </button>
                    <a href="users_list.php" class="btn btn-secondary flex-grow-1">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                </div>
            </form>
        <?php endif; ?>

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

        <?php if (!$status): ?>
            <div class="text-center mt-4">
                <a href="library_entrance.php" class="back-link">
                    <i class="fas fa-arrow-left me-1"></i>Display Library Entrance
                </a>
                &nbsp;|&nbsp;
                <a href="users_list.php" class="back-link">
                    <i class="fas fa-users me-1"></i>Back to Users List
                </a>
            </div>
        <?php endif; ?>
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