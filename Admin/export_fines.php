<?php
require '../db.php';
require 'vendor/autoload.php';

$status = $_GET['status'] ?? 'Paid';

$query = "
    SELECT 
        f.id, f.type, f.amount,
        bk.title AS book_title,
        CONCAT(u.firstname, ' ', u.lastname) AS borrower_name,
        u.school_id
    FROM fines f
    JOIN borrowings b ON f.borrowing_id = b.id
    JOIN books bk ON b.book_id = bk.id
    JOIN users u ON b.user_id = u.id
    WHERE f.status = '$status'
    ORDER BY f.date DESC
";

$result = mysqli_query($conn, $query);

// Create new PDF document with landscape orientation
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Library System');
$pdf->SetTitle('Fines Export');
$pdf->SetSubject('Fines Export');
$pdf->SetKeywords('TCPDF, PDF, fines, export');

// Set default header data
$pdf->SetHeaderData('', 0, 'Fines Export', "Status: $status");

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Create table header with specified column widths
$html = '<h1>Fines Export</h1>';
$html .= '<table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th width="10%">ID Number</th>
                    <th width="20%">Borrower</th>
                    <th width="50%">Book</th>
                    <th width="10%">Type</th>
                    <th width="10%">Amount</th>
                </tr>
            </thead>
            <tbody>';

$totalAmount = 0;

// Populate data rows
while ($row = mysqli_fetch_assoc($result)) {
    $totalAmount += $row['amount'];
    $html .= '<tr>
                <td width="10%">' . htmlspecialchars($row['school_id']) . '</td>
                <td width="20%">' . htmlspecialchars($row['borrower_name']) . '</td>
                <td width="50%">' . htmlspecialchars($row['book_title']) . '</td>
                <td width="10%">' . htmlspecialchars($row['type']) . '</td>
                <td width="10%">' . number_format($row['amount'], 2) . '</td>
              </tr>';
}

$html .= '<tr>
            <td colspan="4" align="right"><strong>Total Amount:</strong></td>
            <td width="10%"><strong>' . number_format($totalAmount, 2) . '</strong></td>
          </tr>';

$html .= '</tbody></table>';

// Output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// Preview the PDF in the browser
$pdf->Output('Fines_Export_' . $status . '_' . date('Y-m-d') . '.pdf', 'I');
exit();
?>
