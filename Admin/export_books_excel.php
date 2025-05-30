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
$programFilter = isset($_GET['program']) ? $_GET['program'] : '';
$subjectCategoryFilter = isset($_GET['subject_category']) ? $_GET['subject_category'] : '';
$titleFilter = isset($_GET['title']) ? $_GET['title'] : '';
$locationFilter = isset($_GET['location']) ? $_GET['location'] : '';

// Build the SQL WHERE clause for filtering
$whereClause = "";
$filterParams = [];

if ($statusFilter) {
    $whereClause .= $whereClause ? " AND b.status = '$statusFilter'" : "WHERE b.status = '$statusFilter'";
    $filterParams[] = "status=$statusFilter";
}

if ($programFilter) {
    $whereClause .= $whereClause ? " AND b.program = '$programFilter'" : "WHERE b.program = '$programFilter'";
    $filterParams[] = "program=$programFilter";
}

if ($subjectCategoryFilter) {
    $whereClause .= $whereClause ? " AND b.subject_category = '$subjectCategoryFilter'" : "WHERE b.subject_category = '$subjectCategoryFilter'";
    $filterParams[] = "subject_category=$subjectCategoryFilter";
}

if ($titleFilter) {
    $whereClause .= $whereClause ? " AND (b.title LIKE '%$titleFilter%' OR b.accession LIKE '%$titleFilter%')" : 
                               "WHERE (b.title LIKE '%$titleFilter%' OR b.accession LIKE '%$titleFilter%')";
    $filterParams[] = "title=" . urlencode($titleFilter);
}

if ($locationFilter) {
    $whereClause .= $whereClause ? " AND b.shelf_location LIKE '%$locationFilter%'" : "WHERE b.shelf_location LIKE '%$locationFilter%'";
    $filterParams[] = "location=" . urlencode($locationFilter);
}

// SQL query to fetch data - fixed to join with writers, contributors, publications and publishers tables
$sql = "SELECT b.id, b.accession, b.title, 
        CONCAT(w.firstname, IF(w.middle_init != '', CONCAT(' ', w.middle_init, ' '), ' '), w.lastname) AS author,
        p.publish_date AS publication_year, pub.publisher, 
        b.ISBN as isbn, b.subject_category as category, b.status, 
        b.shelf_location, b.date_added
        FROM books b
        LEFT JOIN contributors c ON b.id = c.book_id AND c.role = 'Author'
        LEFT JOIN writers w ON c.writer_id = w.id
        LEFT JOIN publications p ON b.id = p.book_id
        LEFT JOIN publishers pub ON p.publisher_id = pub.id
        $whereClause
        GROUP BY b.id
        ORDER BY b.date_added DESC";

$result = mysqli_query($conn, $sql);

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set the column headers
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Accession Number');
$sheet->setCellValue('C1', 'Title');
$sheet->setCellValue('D1', 'Author');
$sheet->setCellValue('E1', 'Publication Year');
$sheet->setCellValue('F1', 'Publisher');
$sheet->setCellValue('G1', 'ISBN');
$sheet->setCellValue('H1', 'Category');
$sheet->setCellValue('I1', 'Status');
$sheet->setCellValue('J1', 'Shelf Location');
$sheet->setCellValue('K1', 'Date Added');

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
$sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

// Add the data rows
$row = 2;
while ($book = mysqli_fetch_assoc($result)) {
    $sheet->setCellValue('A' . $row, $book['id']);
    $sheet->setCellValue('B' . $row, $book['accession']);
    $sheet->setCellValue('C' . $row, $book['title']);
    $sheet->setCellValue('D' . $row, $book['author'] ?: 'Unknown');
    $sheet->setCellValue('E' . $row, $book['publication_year']);
    $sheet->setCellValue('F' . $row, $book['publisher']);
    $sheet->setCellValue('G' . $row, $book['isbn']);
    $sheet->setCellValue('H' . $row, $book['category']);
    $sheet->setCellValue('I' . $row, $book['status']);
    $sheet->setCellValue('J' . $row, $book['shelf_location']);
    $sheet->setCellValue('K' . $row, date('Y-m-d', strtotime($book['date_added'])));
    
    // Style cells with status color
    $statusColor = 'FFFFFF'; // Default white
    switch ($book['status']) {
        case 'Available':
            $statusColor = 'c3e6cb'; // Light green
            break;
        case 'Borrowed':
            $statusColor = 'bee5eb'; // Light blue
            break; 
        case 'Reserved':
            $statusColor = 'ffeeba'; // Light yellow
            break;
        case 'Damaged':
            $statusColor = 'f8d7da'; // Light red
            break;
        case 'Lost':
            $statusColor = 'f5c6cb'; // Light red
            break;
    }
    
    $sheet->getStyle('I' . $row)->applyFromArray([
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
foreach (range('A', 'K') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Generate a descriptive filename based on filters
function generateDescriptiveFilename($statusFilter, $programFilter, $subjectCategoryFilter, $locationFilter) {
    $filenameParts = ['books'];
    $onlyStatusFilter = false;
    $statusAndLocationFilter = false;
    $statusLocationAndProgramFilter = false;
    $currentDate = date('Y-m-d');
    
    // First, determine the filter combinations
    if ($statusFilter && empty($programFilter) && empty($subjectCategoryFilter) && empty($locationFilter)) {
        $onlyStatusFilter = true;
    } else if ($statusFilter && empty($programFilter) && empty($subjectCategoryFilter) && !empty($locationFilter)) {
        $statusAndLocationFilter = true;
    } else if ($statusFilter && !empty($locationFilter) && (!empty($programFilter) || !empty($subjectCategoryFilter))) {
        $statusLocationAndProgramFilter = true;
    }
    
    // Add status to filename if filtered
    if ($statusFilter) {
        $filenameParts[] = strtolower($statusFilter);
    }
    
    // Add program to filename if filtered
    if ($programFilter) {
        $cleanProgram = preg_replace('/[^a-zA-Z0-9]/', '_', $programFilter);
        $filenameParts[] = 'program_' . $cleanProgram;
    }
    
    // Add subject category to filename if filtered
    if ($subjectCategoryFilter) {
        $cleanCategory = preg_replace('/[^a-zA-Z0-9]/', '_', $subjectCategoryFilter);
        $filenameParts[] = 'category_' . $cleanCategory;
    }
    
    // Add location to filename if filtered
    if ($locationFilter) {
        $cleanLocation = preg_replace('/[^a-zA-Z0-9]/', '_', $locationFilter);
        $filenameParts[] = 'location_' . $cleanLocation;
    }
    
    // If no specific filters applied, indicate this is a complete inventory
    if (count($filenameParts) === 1) {
        $filenameParts[0] = 'all_books_inventory';
    }
    
    // Determine if we should include a timestamp
    $skipTimestamp = $onlyStatusFilter || $statusAndLocationFilter || $statusLocationAndProgramFilter;
    
    // Add date for uniqueness - with or without time portion
    if (!$skipTimestamp) {
        $filenameParts[] = date('Y-m-d_H-i-s');
    }
    
    // Join parts with underscores and add extension
    $filename = implode('_', $filenameParts) . '.xlsx';
    
    // Make sure filename isn't too long (max 255 chars is safe for most filesystems)
    if (strlen($filename) > 200) {
        // If too long, use a simplified name
        $filename = 'books_export_' . date('Y-m-d') . '.xlsx';
    }
    
    return $filename;
}

// Set filename with descriptive elements based on filters
$filename = generateDescriptiveFilename($statusFilter, $programFilter, $subjectCategoryFilter, $locationFilter);

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create Xlsx writer and output the file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
