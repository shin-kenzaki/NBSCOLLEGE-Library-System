<?php
session_start();
require '../db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access");
}

// Filters from GET
$vcourse = $_GET['vcourse'] ?? '';
$vpurpose = $_GET['vpurpose'] ?? '';
$vuser = $_GET['vuser'] ?? '';
$vdateStart = $_GET['vdate_start'] ?? '';
$vdateEnd = $_GET['vdate_end'] ?? '';

// WHERE clause builder
$where = "WHERE lv.purpose != 'Exit'";
if ($vcourse) $where .= " AND u.department = '" . mysqli_real_escape_string($conn, $vcourse) . "'";
if ($vpurpose) $where .= " AND lv.purpose = '" . mysqli_real_escape_string($conn, $vpurpose) . "'";
if ($vuser) {
    $vuser = mysqli_real_escape_string($conn, $vuser);
    $where .= " AND (lv.student_number LIKE '%$vuser%' OR CONCAT(u.firstname, ' ', u.lastname) LIKE '%$vuser%')";
}
if ($vdateStart) $where .= " AND DATE(lv.time) >= '" . mysqli_real_escape_string($conn, $vdateStart) . "'";
if ($vdateEnd) $where .= " AND DATE(lv.time) <= '" . mysqli_real_escape_string($conn, $vdateEnd) . "'";

// Main query
$sql = "
    SELECT lv.id, lv.student_number, lv.time AS time_entry, lv.purpose, lv.status,
           u.department, u.firstname, u.lastname,
           (
                SELECT time FROM library_visits
                WHERE student_number = lv.student_number AND status = '0' AND time > lv.time
                ORDER BY time ASC LIMIT 1
           ) AS time_exit,
           SEC_TO_TIME(
               TIMESTAMPDIFF(SECOND,
                   lv.time,
                   (
                       SELECT time FROM library_visits
                       WHERE student_number = lv.student_number AND status = '0' AND time > lv.time
                       ORDER BY time ASC LIMIT 1
                   )
               )
           ) AS duration
    FROM library_visits lv
    LEFT JOIN users u ON lv.student_number = u.school_id
    $where
    ORDER BY lv.time DESC
";

$result = mysqli_query($conn, $sql);

// Spreadsheet setup
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Library Visits');

// Headers
$headers = ['ID', 'Student Number', 'Name', 'Course/Department', 'Time In', 'Time Out', 'Duration', 'Purpose'];
$sheet->fromArray($headers, NULL, 'A1');

// Style the header
$headerRange = 'A1:H1';
$sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF'); // White text
$sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
      ->getStartColor()->setRGB('0070C0'); // Blue fill



// Data rows
$rowNum = 2;
while ($row = mysqli_fetch_assoc($result)) {
    $name = $row['firstname'] . ' ' . $row['lastname'];
    $timeEntry = date('Y-m-d h:i A', strtotime($row['time_entry']));
    $timeExit = $row['time_exit'] ? date('Y-m-d h:i A', strtotime($row['time_exit'])) : '-';
    $duration = $row['duration'] ?? '-';
    $sheet->setCellValue("A{$rowNum}", $row['id']);
    $sheet->setCellValue("B{$rowNum}", $row['student_number']);
    $sheet->setCellValue("C{$rowNum}", $name);
    $sheet->setCellValue("D{$rowNum}", $row['department']);
    $sheet->setCellValue("E{$rowNum}", $timeEntry);
    $sheet->setCellValue("F{$rowNum}", $timeExit);
    $sheet->setCellValue("G{$rowNum}", $duration);
    $sheet->setCellValue("H{$rowNum}", $row['purpose']);
    $rowNum++;
}


// Auto-size
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}


// Output Excel
$filename = 'Library_Visits_' . date('Y-m-d_H-i') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
