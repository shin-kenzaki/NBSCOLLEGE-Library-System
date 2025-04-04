<?php
session_start();
require_once '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant'])) {
    header("Location: index.php");
    exit();
}

// Handle direct export requests (for advanced export options)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $format = $_POST['format'] ?? 'xlsx';
    $filters = [
        'usertype' => $_POST['usertype'] ?? '',
        'department' => $_POST['department'] ?? '',
        'status' => $_POST['status'] ?? '',
        'date_from' => $_POST['date_from'] ?? '',
        'date_to' => $_POST['date_to'] ?? '',
    ];
    
    // Build query with filters
    $query = "SELECT u.*, 
        (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status IN ('Borrowed', 'Overdue')) AS borrowed_books,
        (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status = 'Returned') AS returned_books,
        (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status = 'Damaged') AS damaged_books,
        (SELECT COUNT(*) FROM borrowings WHERE user_id = u.id AND status = 'Lost') AS lost_books
        FROM users u WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (!empty($filters['usertype'])) {
        $query .= " AND usertype = ?";
        $params[] = $filters['usertype'];
        $types .= "s";
    }
    
    if (!empty($filters['department'])) {
        $query .= " AND department = ?";
        $params[] = $filters['department'];
        $types .= "s";
    }
    
    if ($filters['status'] !== '') {
        $query .= " AND status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (!empty($filters['date_from'])) {
        $query .= " AND date_added >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $query .= " AND date_added <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    $query .= " ORDER BY date_added DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $status = '';
        switch ($row['status']) {
            case '1': $status = 'Active'; break;
            case '2': $status = 'Banned'; break;
            case '3': $status = 'Disabled'; break;
            default: $status = 'Unknown';
        }
        
        $users[] = [
            'ID' => $row['id'],
            'School ID' => $row['school_id'],
            'Name' => $row['firstname'] . ' ' . ($row['middle_init'] ? $row['middle_init'] . ' ' : '') . $row['lastname'],
            'Email' => $row['email'],
            'Department' => $row['department'],
            'User Type' => $row['usertype'],
            'Borrowed Books' => $row['borrowed_books'],
            'Returned Books' => $row['returned_books'],
            'Damaged Books' => $row['damaged_books'],
            'Lost Books' => $row['lost_books'],
            'Status' => $status,
            'Date Added' => $row['date_added'],
            'Last Update' => $row['last_update'],
            'Password' => $row['password']
        ];
    }
    
    // Export based on requested format
    switch ($format) {
        case 'csv':
            exportCSV($users);
            break;
        case 'pdf':
            exportPDF($users);
            break;
        default:
            exportExcel($users);
    }
    
    exit();
}

// Function to export as Excel
function exportExcel($data) {
    require 'vendor/autoload.php'; // Make sure you have PhpSpreadsheet installed
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add headers
    if (!empty($data)) {
        $col = 1;
        foreach (array_keys($data[0]) as $header) {
            // Fix: Use the coordinate-based cell method instead of setCellValueByColumnAndRow
            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
            $sheet->setCellValue($cellCoord, $header);
            $col++;
        }
        
        // Add data
        $row = 2;
        foreach ($data as $item) {
            $col = 1;
            foreach ($item as $value) {
                // Fix: Use the coordinate-based cell method instead of setCellValueByColumnAndRow
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                $sheet->setCellValue($cellCoord, $value);
                $col++;
            }
            $row++;
        }
    }
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="users_export_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
}

// Function to export as CSV
function exportCSV($data) {
    // Set headers for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="users_export_' . date('Y-m-d') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
}

// Function to export as PDF
function exportPDF($data) {
    require 'vendor/autoload.php'; // Make sure you have TCPDF installed
    
    // Create new PDF document
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('NBSC Library System');
    $pdf->SetTitle('Users Export');
    $pdf->SetSubject('Users List');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, 'NBSC Library System', 'Users Export - Generated on ' . date('Y-m-d H:i:s'));
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage('L', 'A4');
    
    // Create table headers
    if (!empty($data)) {
        $html = '<table border="1" cellpadding="3"><tr>';
        foreach (array_keys($data[0]) as $header) {
            $html .= '<th style="font-weight:bold;">' . $header . '</th>';
        }
        $html .= '</tr>';
        
        // Add data rows
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // Print HTML table
        $pdf->writeHTML($html, true, false, true, false, '');
    }
    
    // Output PDF
    $pdf->Output('users_export_' . date('Y-m-d') . '.pdf', 'D');
}

include '../admin/inc/header.php';
?>

<!-- Begin Page Content -->
<div id="content" class="d-flex flex-column min-vh-100">
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Export Users</h6>
                <div>
                    <a href="users_list.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Users List
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p><i class="fas fa-info-circle"></i> Use the form below to export users with specific filters. Leave filters empty to export all users.</p>
                </div>
                
                <form method="POST" action="" id="exportForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>User Type</label>
                                <select name="usertype" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="Student">Student</option>
                                    <option value="Faculty">Faculty</option>
                                    <option value="Staff">Staff</option>
                                    <option value="Visitor">Visitor</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department" class="form-control">
                                    <option value="">All Departments</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Accounting Information System">Accounting Information System</option>
                                    <option value="Accountancy">Accountancy</option>
                                    <option value="Entrepreneurship">Entrepreneurship</option>
                                    <option value="Tourism Management">Tourism Management</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="1">Active</option>
                                    <option value="2">Banned</option>
                                    <option value="3">Disabled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date Added (From)</label>
                                <input type="date" name="date_from" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date Added (To)</label>
                                <input type="date" name="date_to" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12 text-center">
                            <div class="btn-group">
                                <button type="submit" name="format" value="xlsx" class="btn btn-primary">
                                    <i class="fas fa-file-excel"></i> Export to Excel
                                </button>
                                <button type="submit" name="format" value="csv" class="btn btn-success">
                                    <i class="fas fa-file-csv"></i> Export to CSV
                                </button>
                                <button type="submit" name="format" value="pdf" class="btn btn-danger">
                                    <i class="fas fa-file-pdf"></i> Export to PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Page Content -->

<script>
$(document).ready(function() {
    $('#exportForm').on('submit', function() {
        // Show loading message
        Swal.fire({
            title: 'Generating Export...',
            html: 'Please wait while we generate your export file.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    });
});
</script>

<?php include('../admin/inc/footer.php'); ?>
