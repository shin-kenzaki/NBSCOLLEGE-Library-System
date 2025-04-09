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
$bookFilter = isset($_GET['book']) ? $_GET['book'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= $whereClause ? " AND r.status = '$statusFilter'" : "WHERE r.status = '$statusFilter'";
    $filterParams[] = "status=$statusFilter";
}

if ($dateStart) {
    $whereClause .= $whereClause ? " AND r.reserve_date >= '$dateStart'" : "WHERE r.reserve_date >= '$dateStart'";
    $filterParams[] = "date_start=$dateStart";
}

if ($dateEnd) {
    $whereClause .= $whereClause ? " AND r.reserve_date <= '$dateEnd'" : "WHERE r.reserve_date <= '$dateEnd'";
    $filterParams[] = "date_end=$dateEnd";
}

if ($userFilter) {
    $whereClause .= $whereClause ? " AND (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')" : 
                               "WHERE (u.firstname LIKE '%$userFilter%' OR u.lastname LIKE '%$userFilter%' OR u.school_id LIKE '%$userFilter%')";
    $filterParams[] = "user=" . urlencode($userFilter);
}

if ($bookFilter) {
    $whereClause .= $whereClause ? " AND (bk.title LIKE '%$bookFilter%' OR bk.accession LIKE '%$bookFilter%')" : 
                               "WHERE (bk.title LIKE '%$bookFilter%' OR bk.accession LIKE '%$bookFilter%')";
    $filterParams[] = "book=" . urlencode($bookFilter);
}

// SQL query to fetch reservations data with related information
$sql = "SELECT r.id, r.status, r.reserve_date, r.ready_date, r.recieved_date, r.cancel_date,
        u.school_id, u.firstname as user_firstname, u.lastname as user_lastname, u.usertype,
        bk.accession, bk.title,
        a1.firstname as ready_admin_firstname, a1.lastname as ready_admin_lastname, a1.role as ready_admin_role,
        a2.firstname as issued_admin_firstname, a2.lastname as issued_admin_lastname, a2.role as issued_admin_role
        FROM reservations r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN books bk ON r.book_id = bk.id
        LEFT JOIN admins a1 ON r.ready_by = a1.id
        LEFT JOIN admins a2 ON r.issued_by = a2.id
        $whereClause
        ORDER BY r.reserve_date DESC";

$result = mysqli_query($conn, $sql);

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set the column headers
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'User Name');
$sheet->setCellValue('C1', 'School ID');
$sheet->setCellValue('D1', 'User Type');
$sheet->setCellValue('E1', 'Book Title');
$sheet->setCellValue('F1', 'Accession');
$sheet->setCellValue('G1', 'Status');
$sheet->setCellValue('H1', 'Reserve Date');
$sheet->setCellValue('I1', 'Ready Date');
$sheet->setCellValue('J1', 'Recieved Date');
$sheet->setCellValue('K1', 'Cancel Date');
$sheet->setCellValue('L1', 'Ready By');
$sheet->setCellValue('M1', 'Issued By');

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
$sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

// Add the data rows
$row = 2;
while ($reservation = mysqli_fetch_assoc($result)) {
    $userName = $reservation['user_firstname'] . ' ' . $reservation['user_lastname'];
    $readyBy = $reservation['ready_admin_firstname'] ? $reservation['ready_admin_firstname'] . ' ' . $reservation['ready_admin_lastname'] : 'N/A';
    $issuedBy = $reservation['issued_admin_firstname'] ? $reservation['issued_admin_firstname'] . ' ' . $reservation['issued_admin_lastname'] : 'N/A';
    
    $sheet->setCellValue('A' . $row, $reservation['id']);
    $sheet->setCellValue('B' . $row, $userName);
    $sheet->setCellValue('C' . $row, $reservation['school_id']);
    $sheet->setCellValue('D' . $row, $reservation['usertype']);
    $sheet->setCellValue('E' . $row, $reservation['title']);
    $sheet->setCellValue('F' . $row, $reservation['accession']);
    $sheet->setCellValue('G' . $row, $reservation['status']);
    $sheet->setCellValue('H' . $row, date('Y-m-d', strtotime($reservation['reserve_date'])));
    $sheet->setCellValue('I' . $row, $reservation['ready_date'] ? date('Y-m-d', strtotime($reservation['ready_date'])) : 'N/A');
    $sheet->setCellValue('J' . $row, $reservation['recieved_date'] ? date('Y-m-d', strtotime($reservation['recieved_date'])) : 'N/A');
    $sheet->setCellValue('K' . $row, $reservation['cancel_date'] ? date('Y-m-d', strtotime($reservation['cancel_date'])) : 'N/A');
    $sheet->setCellValue('L' . $row, $readyBy);
    $sheet->setCellValue('M' . $row, $issuedBy);
    
    // Style cells with status color
    $statusColor = 'FFFFFF'; // Default white
    switch ($reservation['status']) {
        case 'Pending':
            $statusColor = 'ffeeba'; // Light yellow
            break;
        case 'Ready':
            $statusColor = 'bee5eb'; // Light blue
            break;
        case 'Recieved':
            $statusColor = 'c3e6cb'; // Light green
            break;
        case 'Cancelled':
            $statusColor = 'f8d7da'; // Light red
            break;
    }
    
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

// Auto-size columns
foreach (range('A', 'M') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Generate a descriptive filename based on filters
function generateDescriptiveFilename($statusFilter, $dateStart, $dateEnd, $userFilter, $bookFilter) {
    $filenameParts = ['reservations'];
    $currentDate = date('Y-m-d');
    
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
    
    // Add book filter info if present
    if (!empty($bookFilter)) {
        // Clean up book filter for filename
        $cleanBookFilter = preg_replace('/[^a-zA-Z0-9]/', '_', $bookFilter);
        $filenameParts[] = 'book_' . substr($cleanBookFilter, 0, 20); // Limit length
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
        $filename = 'reservations_export_' . date('Y-m-d') . '.xlsx';
    }
    
    return $filename;
}

// Set filename with descriptive elements based on filters
$filename = generateDescriptiveFilename($statusFilter, $dateStart, $dateEnd, $userFilter, $bookFilter);

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create Xlsx writer and output the file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
