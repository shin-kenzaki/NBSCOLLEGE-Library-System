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

$query = "SELECT * FROM borrowings WHERE issue_date BETWEEN '$startDate' AND '$endDate'";
$result = mysqli_query($conn, $query);

$spreadsheet = new Spreadsheet();

if ($exportType == 'last_year' || $exportType == 'current_year') {
    for ($month = 1; $month <= 12; $month++) {
        $sheet = $spreadsheet->createSheet($month - 1);
        $sheet->setTitle(date('F', mktime(0, 0, 0, $month, 10)));

        // Set header row
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Book ID');
        $sheet->setCellValue('C1', 'User ID');
        $sheet->setCellValue('D1', 'Issue Date');
        $sheet->setCellValue('E1', 'Due Date');
        $sheet->setCellValue('F1', 'Return Date');
        $sheet->setCellValue('G1', 'Status');

        // Populate data rows
        $rowNumber = 2;
        mysqli_data_seek($result, 0); // Reset result pointer
        while ($row = mysqli_fetch_assoc($result)) {
            $issueMonth = date('n', strtotime($row['issue_date']));
            if ($issueMonth == $month) {
                $sheet->setCellValue('A' . $rowNumber, $row['id']);
                $sheet->setCellValue('B' . $rowNumber, $row['book_id']);
                $sheet->setCellValue('C' . $rowNumber, $row['user_id']);
                $sheet->setCellValue('D' . $rowNumber, $row['issue_date']);
                $sheet->setCellValue('E' . $rowNumber, $row['due_date']);
                $sheet->setCellValue('F' . $rowNumber, $row['return_date']);
                $sheet->setCellValue('G' . $rowNumber, $row['status']);
                $rowNumber++;
            }
        }
    }
    $spreadsheet->removeSheetByIndex(0); // Remove the default sheet
} else {
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Borrowings Export');

    // Set header row
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Book ID');
    $sheet->setCellValue('C1', 'User ID');
    $sheet->setCellValue('D1', 'Issue Date');
    $sheet->setCellValue('E1', 'Due Date');
    $sheet->setCellValue('F1', 'Return Date');
    $sheet->setCellValue('G1', 'Status');

    // Populate data rows
    $rowNumber = 2;
    while ($row = mysqli_fetch_assoc($result)) {
        $sheet->setCellValue('A' . $rowNumber, $row['id']);
        $sheet->setCellValue('B' . $rowNumber, $row['book_id']);
        $sheet->setCellValue('C' . $rowNumber, $row['user_id']);
        $sheet->setCellValue('D' . $rowNumber, $row['issue_date']);
        $sheet->setCellValue('E' . $rowNumber, $row['due_date']);
        $sheet->setCellValue('F' . $rowNumber, $row['return_date']);
        $sheet->setCellValue('G' . $rowNumber, $row['status']);
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