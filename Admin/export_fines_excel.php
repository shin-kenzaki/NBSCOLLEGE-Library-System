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
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$userFilter = isset($_GET['user']) ? $_GET['user'] : '';
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= $whereClause ? " AND f.status = '$statusFilter'" : "WHERE f.status = '$statusFilter'";
    $filterParams[] = "status=$statusFilter";
}

if ($dateStart) {
    $whereClause .= $whereClause ? " AND f.date >= '$dateStart'" : "WHERE f.date >= '$dateStart'";
    $filterParams[] = "date_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= $whereClause ? " AND f.date <= '$dateEnd'" : "WHERE f.date <= '$dateEnd'";
    $filterParams[] = "date_end=$dateEnd";
}

if ($userFilter) {
    $whereClause .= $whereClause ? " AND (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')" : 
                               "WHERE (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')";
    $filterParams[] = "user=" . urlencode($userFilter);
}

if ($typeFilter) {
    $whereClause .= $whereClause ? " AND f.type = '$typeFilter'" : "WHERE f.type = '$typeFilter'";
    $filterParams[] = "type=$typeFilter";
}

// SQL query to fetch fines data with related information
$sql = "SELECT f.id, f.type, f.amount, f.status, f.date, f.payment_date, 
        b.id as borrowing_id, b.issue_date, b.due_date,
        u.school_id, u.firstname as user_firstname, u.lastname as user_lastname, u.usertype,
        bk.accession, bk.title
        FROM fines f
        LEFT JOIN borrowings b ON f.borrowing_id = b.id
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN books bk ON b.book_id = bk.id
        $whereClause
        ORDER BY f.date DESC";

$result = mysqli_query($conn, $sql);

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set the column headers
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Borrower');
$sheet->setCellValue('C1', 'School ID');
$sheet->setCellValue('D1', 'Book Title');
$sheet->setCellValue('E1', 'Accession');
$sheet->setCellValue('F1', 'Type');
$sheet->setCellValue('G1', 'Amount');
$sheet->setCellValue('H1', 'Status');
$sheet->setCellValue('I1', 'Date');
$sheet->setCellValue('J1', 'Payment Date');
$sheet->setCellValue('K1', 'Issue Date');
$sheet->setCellValue('L1', 'Due Date');

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
$sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

// Add the data rows
$row = 2;
while ($fine = mysqli_fetch_assoc($result)) {
    $borrowerName = $fine['user_firstname'] . ' ' . $fine['user_lastname'];
    
    $sheet->setCellValue('A' . $row, $fine['id']);
    $sheet->setCellValue('B' . $row, $borrowerName);
    $sheet->setCellValue('C' . $row, $fine['school_id']);
    $sheet->setCellValue('D' . $row, $fine['title']);
    $sheet->setCellValue('E' . $row, $fine['accession']);
    $sheet->setCellValue('F' . $row, $fine['type']);
    $sheet->setCellValue('G' . $row, $fine['amount']);
    $sheet->setCellValue('H' . $row, $fine['status']);
    $sheet->setCellValue('I' . $row, $fine['date']);
    $sheet->setCellValue('J' . $row, $fine['payment_date'] ?: 'N/A');
    $sheet->setCellValue('K' . $row, $fine['issue_date']);
    $sheet->setCellValue('L' . $row, $fine['due_date']);
    
    // Style cells with status color
    $statusColor = 'FFFFFF'; // Default white
    switch ($fine['status']) {
        case 'Paid':
            $statusColor = 'c3e6cb'; // Light green
            break;
        case 'Unpaid':
            $statusColor = 'f8d7da'; // Light red
            break;
    }
    
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
foreach (range('A', 'L') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Generate a descriptive filename based on filters
function generateDescriptiveFilename($statusFilter, $dateStart, $dateEnd, $userFilter, $typeFilter) {
    $filenameParts = ['fines'];
    $currentDate = date('Y-m-d');
    
    // Add type to filename if filtered (moved before status)
    if ($typeFilter) {
        $filenameParts[] = strtolower($typeFilter);
    }
    
    // Add status to filename if filtered
    if ($statusFilter) {
        $filenameParts[] = strtolower($statusFilter);
    }
    
    // Add user filter info if present
    if (!empty($userFilter)) {
        // Clean up user filter for filename
        $cleanUserFilter = preg_replace('/[^a-zA-Z0-9]/', '_', $userFilter);
        $filenameParts[] = 'by_' . substr($cleanUserFilter, 0, 20); // Limit length
    }
    
    // Add date range to filename if filtered - with "to" between dates
    // Replace "period" with "from" for date ranges
    if (!empty($dateStart) && !empty($dateEnd)) {
        $filenameParts[] = 'from_' . $dateStart . '_to_' . $dateEnd;
    } elseif (!empty($dateStart)) {
        $filenameParts[] = 'from_' . $dateStart . '_to_' . $currentDate;
    } elseif (!empty($dateEnd)) {
        $filenameParts[] = 'until_' . $dateEnd;
    }
    
    // If no specific filters applied, indicate this is a complete report
    if (count($filenameParts) === 1) {
        $filenameParts[] = 'all_records';
    }
    
    // Join parts with underscores and add extension (no longer adding current date at the end)
    $filename = implode('_', $filenameParts) . '.xlsx';
    
    // Make sure filename isn't too long (max 255 chars is safe for most filesystems)
    if (strlen($filename) > 200) {
        // If too long, use a simplified name
        $filename = 'fines_export_' . date('Y-m-d') . '.xlsx';
    }
    
    return $filename;
}

// Set filename with descriptive elements based on filters
$filename = generateDescriptiveFilename($statusFilter, $dateStart, $dateEnd, $userFilter, $typeFilter);

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create Xlsx writer and output the file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
