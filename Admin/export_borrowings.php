<?php
require '../db.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$exportType = $_GET['type'] ?? 'current_month';

switch ($exportType) {
    case 'previous_month':
        $startDate = date('Y-m-01', strtotime('first day of last month'));
        $endDate = date('Y-m-t', strtotime('last day of last month'));
        $filename = 'Borrowings_Overview_' . date('F_Y', strtotime('last month')) . '.xlsx';
        break;
    case 'last_year':
        $startDate = date('Y-01-01', strtotime('first day of January last year'));
        $endDate = date('Y-12-31', strtotime('last day of December last year'));
        $filename = 'Borrowings_Overview_' . date('Y', strtotime('last year')) . '.xlsx';
        break;
    case 'current_year':
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        $filename = 'Borrowings_Overview_' . date('Y') . '.xlsx';
        break;
    case 'current_month':
    default:
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        $filename = 'Borrowings_Overview_' . date('F_Y') . '.xlsx';
        break;
}

$query = "
    SELECT 
        borrowings.*, 
        books.title AS book_title, 
        CAST(books.accession AS UNSIGNED) AS book_accession, 
        CONCAT(users.firstname, ' ', COALESCE(users.middle_init, ''), ' ', users.lastname) AS user_fullname,
        CAST(users.id AS UNSIGNED) AS user_id,
        CAST(borrowings.book_id AS UNSIGNED) AS book_id
    FROM borrowings 
    JOIN books ON borrowings.book_id = books.id 
    JOIN users ON borrowings.user_id = users.id";
    
if ($exportType == 'previous_month' || $exportType == 'current_month') {
    $query .= " WHERE borrowings.issue_date BETWEEN '$startDate' AND '$endDate'";
}

$query .= " ORDER BY user_id DESC";

$result = mysqli_query($conn, $query);

$spreadsheet = new Spreadsheet();

if ($exportType == 'last_year' || $exportType == 'current_year') {
    for ($month = 1; $month <= 12; $month++) {
        $sheet = $spreadsheet->createSheet($month - 1);
        $sheet->setTitle(date('F', mktime(0, 0, 0, $month, 10)));

        // Set header row
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Book ID');
        $sheet->setCellValue('C1', 'Book Title');
        $sheet->setCellValue('D1', 'Book Accession');
        $sheet->setCellValue('E1', 'User ID');
        $sheet->setCellValue('F1', 'User Fullname');
        $sheet->setCellValue('G1', 'Issue Date');
        $sheet->setCellValue('H1', 'Due Date');
        $sheet->setCellValue('I1', 'Return Date');
        $sheet->setCellValue('J1', 'Status');

        // Populate data rows
        $rowNumber = 2;
        $year = ($exportType == 'last_year') ? date('Y', strtotime('last year')) : date('Y');
        $startDate = date($year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01');
        $endDate = date($year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-t');

        $month_query = "SELECT 
        borrowings.*, 
        books.title AS book_title, 
        CAST(books.accession AS UNSIGNED) AS book_accession, 
        CONCAT(users.firstname, ' ', COALESCE(users.middle_init, ''), ' ', users.lastname) AS user_fullname,
        CAST(users.id AS UNSIGNED) AS user_id,
        CAST(borrowings.book_id AS UNSIGNED) AS book_id
    FROM borrowings 
    JOIN books ON borrowings.book_id = books.id 
    JOIN users ON borrowings.user_id = users.id WHERE borrowings.issue_date BETWEEN '$startDate' AND '$endDate' ORDER BY user_id ASC";
        $month_result = mysqli_query($conn, $month_query);

        while ($row = mysqli_fetch_assoc($month_result)) {
            $issueMonth = date('n', strtotime($row['issue_date']));
            if ($issueMonth == $month) {
                $sheet->setCellValue('A' . $rowNumber, $row['id']);
                $sheet->setCellValue('B' . $rowNumber, $row['book_id']);
                $sheet->setCellValue('C' . $rowNumber, $row['book_title']);
                $sheet->setCellValue('D' . $rowNumber, $row['book_accession']);
                $sheet->setCellValue('E' . $rowNumber, $row['user_id']);
                $sheet->setCellValue('F' . $rowNumber, $row['user_fullname']);
                $sheet->setCellValue('G' . $rowNumber, $row['issue_date']);
                $sheet->setCellValue('H' . $rowNumber, $row['due_date']);
                $sheet->setCellValue('I' . $rowNumber, $row['return_date']);
                $sheet->setCellValue('J' . $rowNumber, $row['status']);
                $rowNumber++;
            }
        }
    }
} else {$sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Borrowings Export');

    // Set header row
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Book ID');
    $sheet->setCellValue('C1', 'Book Title');
    $sheet->setCellValue('D1', 'Book Accession');
    $sheet->setCellValue('E1', 'User ID');
    $sheet->setCellValue('F1', 'User Fullname');
    $sheet->setCellValue('G1', 'Issue Date');
    $sheet->setCellValue('H1', 'Due Date');
    $sheet->setCellValue('I1', 'Return Date');
    $sheet->setCellValue('J1', 'Status');

    // Populate data rows
    $rowNumber = 2;
    while ($row = mysqli_fetch_assoc($result)) {
        $sheet->setCellValue('A' . $rowNumber, $row['id']);
        $sheet->setCellValue('B' . $rowNumber, $row['book_id']);
        $sheet->setCellValue('C' . $rowNumber, $row['book_title']);
        $sheet->setCellValue('D' . $rowNumber, $row['book_accession']);
        $sheet->setCellValue('E' . $rowNumber, $row['user_id']);
        $sheet->setCellValue('F' . $rowNumber, $row['user_fullname']);
        $sheet->setCellValue('G' . $rowNumber, $row['issue_date']);
        $sheet->setCellValue('H' . $rowNumber, $row['due_date']);
        $sheet->setCellValue('I' . $rowNumber, $row['return_date']);
        $sheet->setCellValue('J' . $rowNumber, $row['status']);
        $rowNumber++;
    }
}

$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit();
?>