<?php
session_start();
include '../db.php';
require 'vendor/autoload.php'; // Make sure PhpSpreadsheet is installed

// Check if the user is logged in and has the appropriate admin role
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin'])) {
    die("Unauthorized access");
}

// Import PhpSpreadsheet classes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Get filter parameters
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$searchFilter = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Determine if we're filtering for admin roles or user types
$isAdmin = false;
$adminRoles = ['Admin', 'Librarian', 'Assistant', 'Encoder'];
$userTypes = ['Student', 'Faculty', 'Staff', 'Visitor'];

if (in_array($roleFilter, $adminRoles)) {
    $isAdmin = true;
} 

// Setup variables for tracking rows
$row = 1;

// If we're including admins data or no specific role filter is set
if ($isAdmin || empty($roleFilter)) {
    // Build query for admins
    $adminWhereClause = "";
    if ($roleFilter && in_array($roleFilter, $adminRoles)) {
        $adminWhereClause .= $adminWhereClause ? " AND role = '$roleFilter'" : "WHERE role = '$roleFilter'";
    }
    
    if ($dateStart) {
        $adminWhereClause .= $adminWhereClause ? " AND date_added >= '$dateStart'" : "WHERE date_added >= '$dateStart'";
    }
    
    if ($dateEnd) {
        $adminWhereClause .= $adminWhereClause ? " AND date_added <= '$dateEnd'" : "WHERE date_added <= '$dateEnd'";
    }
    
    if ($searchFilter) {
        $adminWhereClause .= $adminWhereClause ? 
            " AND (firstname LIKE '%$searchFilter%' OR lastname LIKE '%$searchFilter%' OR employee_id LIKE '%$searchFilter%' OR email LIKE '%$searchFilter%')" : 
            "WHERE (firstname LIKE '%$searchFilter%' OR lastname LIKE '%$searchFilter%' OR employee_id LIKE '%$searchFilter%' OR email LIKE '%$searchFilter%')";
    }
    
    if ($statusFilter !== '') {
        $adminWhereClause .= $adminWhereClause ? " AND status = '$statusFilter'" : "WHERE status = '$statusFilter'";
    }
    
    // Query to fetch admin data
    $adminSql = "SELECT id, employee_id, firstname, middle_init, lastname, email, role, date_added, status 
                FROM admins 
                $adminWhereClause 
                ORDER BY date_added DESC";
    
    $adminResult = mysqli_query($conn, $adminSql);
    
    // Set the column headers for admins
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Employee ID');
    $sheet->setCellValue('C1', 'Name');
    $sheet->setCellValue('D1', 'Email');
    $sheet->setCellValue('E1', 'Role');
    $sheet->setCellValue('F1', 'Date Added');
    $sheet->setCellValue('G1', 'Status');
    
    // Format the header row
    $headerStyle = [
        'font' => [
            'bold' => true,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => '4e73df', // Primary color
            ],
        ],
        'font' => [
            'color' => [
                'rgb' => 'FFFFFF', // White text
            ],
        ],
    ];
    $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
    
    // Add the data rows for admins
    $row = 2;
    while ($admin = mysqli_fetch_assoc($adminResult)) {
        $fullName = $admin['firstname'] . ($admin['middle_init'] ? ' ' . $admin['middle_init'] . ' ' : ' ') . $admin['lastname'];
        $statusText = $admin['status'] == '1' ? 'Active' : 'Inactive';
        
        $sheet->setCellValue('A' . $row, $admin['id']);
        $sheet->setCellValue('B' . $row, $admin['employee_id']);
        $sheet->setCellValue('C' . $row, $fullName);
        $sheet->setCellValue('D' . $row, $admin['email']);
        $sheet->setCellValue('E' . $row, $admin['role']);
        $sheet->setCellValue('F' . $row, $admin['date_added']);
        $sheet->setCellValue('G' . $row, $statusText);
        
        // Style status cell
        $statusColor = $admin['status'] == '1' ? 'c3e6cb' : 'f8d7da'; // green for active, red for inactive
        $sheet->getStyle('G' . $row)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => $statusColor,
                ],
            ],
        ]);
        
        $row++;
    }
}

// Only add user data if not specifically filtering for admin roles or if filtering for user types
if (!$isAdmin || empty($roleFilter)) {
    // Build query for users
    $userWhereClause = "";
    if ($roleFilter && in_array($roleFilter, $userTypes)) {
        $userWhereClause .= $userWhereClause ? " AND usertype = '$roleFilter'" : "WHERE usertype = '$roleFilter'";
    }
    
    if ($dateStart) {
        $userWhereClause .= $userWhereClause ? " AND date_added >= '$dateStart'" : "WHERE date_added >= '$dateStart'";
    }
    
    if ($dateEnd) {
        $userWhereClause .= $userWhereClause ? " AND date_added <= '$dateEnd'" : "WHERE date_added <= '$dateEnd'";
    }
    
    if ($searchFilter) {
        $userWhereClause .= $userWhereClause ? 
            " AND (firstname LIKE '%$searchFilter%' OR lastname LIKE '%$searchFilter%' OR school_id LIKE '%$searchFilter%' OR email LIKE '%$searchFilter%')" : 
            "WHERE (firstname LIKE '%$searchFilter%' OR lastname LIKE '%$searchFilter%' OR school_id LIKE '%$searchFilter%' OR email LIKE '%$searchFilter%')";
    }
    
    if ($statusFilter !== '') {
        $userWhereClause .= $userWhereClause ? " AND status = '$statusFilter'" : "WHERE status = '$statusFilter'";
    }
    
    // Query to fetch user data
    $userSql = "SELECT id, school_id, firstname, middle_init, lastname, email, usertype, contact_no, 
                borrowed_books, returned_books, damaged_books, lost_books, date_added, status 
                FROM users 
                $userWhereClause 
                ORDER BY date_added DESC";
    
    $userResult = mysqli_query($conn, $userSql);
    
    // If we're only showing users, start at row 1, otherwise start after admins
    if ($isAdmin) {
        // Add a separator row
        $sheet->setCellValue('A' . $row, 'USERS DATA');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row++;
        
        // Set user column headers
        $sheet->setCellValue('A' . $row, 'ID');
        $sheet->setCellValue('B' . $row, 'School ID');
        $sheet->setCellValue('C' . $row, 'Name');
        $sheet->setCellValue('D' . $row, 'Email');
        $sheet->setCellValue('E' . $row, 'User Type');
        $sheet->setCellValue('F' . $row, 'Date Added');
        $sheet->setCellValue('G' . $row, 'Status');
        $sheet->setCellValue('H' . $row, 'Contact No.');
        $sheet->setCellValue('I' . $row, 'Borrowed Books');
        $sheet->setCellValue('J' . $row, 'Returned Books');
        $sheet->setCellValue('K' . $row, 'Damaged Books');
        $sheet->setCellValue('L' . $row, 'Lost Books');
        
        // Format the header row for users
        $sheet->getStyle('A' . $row . ':L' . $row)->applyFromArray($headerStyle);
        $row++;
    } else {
        // Set user column headers starting at row 1
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'School ID');
        $sheet->setCellValue('C1', 'Name');
        $sheet->setCellValue('D1', 'Email');
        $sheet->setCellValue('E1', 'User Type');
        $sheet->setCellValue('F1', 'Date Added');
        $sheet->setCellValue('G1', 'Status');
        $sheet->setCellValue('H1', 'Contact No.');
        $sheet->setCellValue('I1', 'Borrowed Books');
        $sheet->setCellValue('J1', 'Returned Books');
        $sheet->setCellValue('K1', 'Damaged Books');
        $sheet->setCellValue('L1', 'Lost Books');
        
        // Format the header row for users
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
        $row = 2;
    }
    
    // Add the data rows for users
    while ($user = mysqli_fetch_assoc($userResult)) {
        $fullName = $user['firstname'] . ($user['middle_init'] ? ' ' . $user['middle_init'] . ' ' : ' ') . $user['lastname'];
        $statusText = $user['status'] == '1' ? 'Active' : 'Inactive';
        
        $sheet->setCellValue('A' . $row, $user['id']);
        $sheet->setCellValue('B' . $row, $user['school_id']);
        $sheet->setCellValue('C' . $row, $fullName);
        $sheet->setCellValue('D' . $row, $user['email']);
        $sheet->setCellValue('E' . $row, $user['usertype']);
        $sheet->setCellValue('F' . $row, $user['date_added']);
        $sheet->setCellValue('G' . $row, $statusText);
        $sheet->setCellValue('H' . $row, $user['contact_no'] ?: 'N/A');
        $sheet->setCellValue('I' . $row, $user['borrowed_books']);
        $sheet->setCellValue('J' . $row, $user['returned_books']);
        $sheet->setCellValue('K' . $row, $user['damaged_books']);
        $sheet->setCellValue('L' . $row, $user['lost_books']);
        
        // Style status cell
        $statusColor = $user['status'] == '1' ? 'c3e6cb' : 'f8d7da'; // green for active, red for inactive
        $sheet->getStyle('G' . $row)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => $statusColor,
                ],
            ],
        ]);
        
        $row++;
    }
}

// Auto-size columns
foreach (range('A', 'L') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Generate a descriptive filename based on filters
function generateDescriptiveFilename($roleFilter, $dateStart, $dateEnd, $searchFilter, $statusFilter) {
    $filenameParts = ['users'];
    $currentDate = date('Y-m-d');
    
    // Add role to filename if filtered
    if ($roleFilter) {
        $filenameParts[] = strtolower($roleFilter);
    }
    
    // Add search filter info if present
    if (!empty($searchFilter)) {
        // Clean up search filter for filename
        $cleanSearchFilter = preg_replace('/[^a-zA-Z0-9]/', '_', $searchFilter);
        $filenameParts[] = 'search_' . substr($cleanSearchFilter, 0, 20); // Limit length
    }
    
    // Add date range to filename if filtered
    if (!empty($dateStart) && !empty($dateEnd)) {
        $filenameParts[] = 'period_' . $dateStart . '_to_' . $dateEnd;
    } elseif (!empty($dateStart)) {
        $filenameParts[] = 'from_' . $dateStart;
    } elseif (!empty($dateEnd)) {
        $filenameParts[] = 'until_' . $dateEnd;
    }
    
    // Add status info to filename if filtered
    if ($statusFilter !== '') {
        $filenameParts[] = $statusFilter === '1' ? 'active' : 'inactive';
    }
    
    // If no specific filters applied, indicate this is a complete report
    if (count($filenameParts) === 1) {
        $filenameParts[] = 'all_records';
    }
    
    // Add date for uniqueness
    $filenameParts[] = date('Y-m-d');
    
    // Join parts with underscores and add extension
    $filename = implode('_', $filenameParts) . '.xlsx';
    
    // Make sure filename isn't too long (max 255 chars is safe for most filesystems)
    if (strlen($filename) > 200) {
        // If too long, use a simplified name
        $filename = 'users_export_' . date('Y-m-d') . '.xlsx';
    }
    
    return $filename;
}

// Set filename with descriptive elements based on filters
$filename = generateDescriptiveFilename($roleFilter, $dateStart, $dateEnd, $searchFilter, $statusFilter);

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create Xlsx writer and output the file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
