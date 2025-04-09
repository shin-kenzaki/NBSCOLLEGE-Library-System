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
$courseFilter = isset($_GET['vcourse']) ? $_GET['vcourse'] : '';
$dateStart = isset($_GET['vdate_start']) ? $_GET['vdate_start'] : '';
$dateEnd = isset($_GET['vdate_end']) ? $_GET['vdate_end'] : '';
$userFilter = isset($_GET['vuser']) ? $_GET['vuser'] : '';
$purposeFilter = isset($_GET['vpurpose']) ? $_GET['vpurpose'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "";
$filterParams = [];

if ($courseFilter) {
    $whereClause .= $whereClause ? " AND u.department = '$courseFilter'" : "WHERE u.department = '$courseFilter'";
    $filterParams[] = "vcourse=$courseFilter";
}

if ($dateStart) {
    $whereClause .= $whereClause ? " AND DATE(lv.time) >= '$dateStart'" : "WHERE DATE(lv.time) >= '$dateStart'";
    $filterParams[] = "vdate_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= $whereClause ? " AND DATE(lv.time) <= '$dateEnd'" : "WHERE DATE(lv.time) <= '$dateEnd'";
    $filterParams[] = "vdate_end=$dateEnd";
}

if ($userFilter) {
    $whereClause .= $whereClause ? " AND (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')" : 
                               "WHERE (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')";
    $filterParams[] = "vuser=" . urlencode($userFilter);
}

if ($purposeFilter) {
    $whereClause .= $whereClause ? " AND lv.purpose LIKE '%$purposeFilter%'" : "WHERE lv.purpose LIKE '%$purposeFilter%'";
    $filterParams[] = "vpurpose=" . urlencode($purposeFilter);
}

// SQL query to fetch visits data with related information
$sql = "SELECT lv.id, lv.time, lv.purpose, lv.status, 
        u.school_id, u.firstname as user_firstname, u.lastname as user_lastname, 
        u.usertype, u.department
        FROM library_visits lv
        LEFT JOIN users u ON lv.student_number = u.school_id
        $whereClause
        ORDER BY lv.time DESC";

$result = mysqli_query($conn, $sql);

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set the column headers
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Visitor Name');
$sheet->setCellValue('C1', 'School ID');
$sheet->setCellValue('D1', 'User Type');
$sheet->setCellValue('E1', 'Department/Course');
$sheet->setCellValue('F1', 'Visit Time');
$sheet->setCellValue('G1', 'Purpose');
$sheet->setCellValue('H1', 'Status');

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
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

// Add the data rows
$row = 2;
while ($visit = mysqli_fetch_assoc($result)) {
    $visitorName = $visit['user_firstname'] . ' ' . $visit['user_lastname'];
    $statusText = $visit['status'] == 1 ? 'Active' : 'Closed';
    
    $sheet->setCellValue('A' . $row, $visit['id']);
    $sheet->setCellValue('B' . $row, $visitorName);
    $sheet->setCellValue('C' . $row, $visit['school_id']);
    $sheet->setCellValue('D' . $row, $visit['usertype']);
    $sheet->setCellValue('E' . $row, $visit['department']);
    $sheet->setCellValue('F' . $row, date('Y-m-d h:i A', strtotime($visit['time'])));
    $sheet->setCellValue('G' . $row, $visit['purpose']);
    $sheet->setCellValue('H' . $row, $statusText);
    
    // Style status cell
    $statusColor = $visit['status'] == 1 ? 'c3e6cb' : 'f8d7da'; // green for active, red for closed
    $sheet->getStyle('H' . $row)->applyFromArray([
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => $statusColor,
            ],
        ],
    ]);
    
    $row++;
}

// Auto-size columns
foreach (range('A', 'H') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Generate a descriptive filename based on filters
function generateDescriptiveFilename($courseFilter, $dateStart, $dateEnd, $userFilter, $purposeFilter) {
    $filenameParts = ['library_visits'];
    $currentDate = date('Y-m-d');
    
    // Add course to filename if filtered
    if ($courseFilter) {
        // Clean up course name for filename
        $cleanCourse = preg_replace('/[^a-zA-Z0-9]/', '_', $courseFilter);
        $filenameParts[] = 'course_' . substr($cleanCourse, 0, 20); // Limit length
    }
    
    // Add purpose to filename if filtered
    if ($purposeFilter) {
        // Clean up purpose for filename
        $cleanPurpose = preg_replace('/[^a-zA-Z0-9]/', '_', $purposeFilter);
        $filenameParts[] = 'purpose_' . substr($cleanPurpose, 0, 20); // Limit length
    }
    
    // Add user filter info if present
    if (!empty($userFilter)) {
        // Clean up user filter for filename
        $cleanUserFilter = preg_replace('/[^a-zA-Z0-9]/', '_', $userFilter);
        $filenameParts[] = 'user_' . substr($cleanUserFilter, 0, 20); // Limit length
    }
    
    // Add date range to filename if filtered
    if (!empty($dateStart) && !empty($dateEnd)) {
        $filenameParts[] = 'from_' . $dateStart . '_to_' . $dateEnd;
    } elseif (!empty($dateStart)) {
        $filenameParts[] = 'from_' . $dateStart;
    } elseif (!empty($dateEnd)) {
        $filenameParts[] = 'until_' . $dateEnd;
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
        $filename = 'library_visits_export_' . date('Y-m-d') . '.xlsx';
    }
    
    return $filename;
}

// Set filename with descriptive elements based on filters
$filename = generateDescriptiveFilename($courseFilter, $dateStart, $dateEnd, $userFilter, $purposeFilter);

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create Xlsx writer and output the file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
