<?php
require '../db.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$spreadsheet->setActiveSheetIndex(0);

// Remove the default sheet
$spreadsheet->removeSheetByIndex(0);

// Function to fetch books by status and populate the sheet
function fetchBooksByStatus($conn, $spreadsheet, $status, $sheetTitle) {
    $query = "SELECT books.*, 
              entered.firstname AS entered_by_firstname, 
              entered.lastname AS entered_by_lastname,
              updated.firstname AS updated_by_firstname,
              updated.lastname AS updated_by_lastname
              FROM books 
              LEFT JOIN admins AS entered ON books.entered_by = entered.id
              LEFT JOIN admins AS updated ON books.updated_by = updated.id
              WHERE books.status = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $status);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        die('Error: ' . $conn->error);
    }

    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle($sheetTitle);

    // Fetch column names
    $fields = $result->fetch_fields();
    $columnIndex = 'A';
    foreach ($fields as $field) {
        $sheet->setCellValue($columnIndex . '1', $field->name);
        $columnIndex++;
    }

    $rowNum = 2;
    while ($row = $result->fetch_assoc()) {
        $columnIndex = 'A';
        foreach ($row as $key => $cell) {
            if ($key == 'entered_by_firstname') {
                $sheet->setCellValue($columnIndex . $rowNum, $row['entered_by_firstname'] . ' ' . $row['entered_by_lastname']);
            } elseif ($key == 'updated_by_firstname') {
                $sheet->setCellValue($columnIndex . $rowNum, $row['updated_by_firstname'] . ' ' . $row['updated_by_lastname']);
            } else if ($key != 'entered_by_lastname' && $key != 'updated_by_lastname'){
                 $sheet->setCellValue($columnIndex . $rowNum, $cell);
            }
           
            $columnIndex++;
        }
        $rowNum++;
    }

    // Auto-fit columns
    foreach (range('A', --$columnIndex) as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }
    $stmt->close();
}

// Fetch available books
fetchBooksByStatus($conn, $spreadsheet, 'Available', 'Available Books');

// Fetch damaged books
fetchBooksByStatus($conn, $spreadsheet, 'Damaged', 'Damaged Books');

// Fetch lost books
fetchBooksByStatus($conn, $spreadsheet, 'Lost', 'Lost Books');

// Set active sheet to the first sheet
$spreadsheet->setActiveSheetIndex(0);

// Generate the file
$writer = new Xlsx($spreadsheet);
$filename = 'Book_Status_Report_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');

// Close the database connection
$conn->close();
exit();
?>
