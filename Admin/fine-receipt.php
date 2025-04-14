<?php
require('fpdf/fpdf.php');
include('../db.php');

class PDF extends FPDF {
    function CheckPageBreak($h) {
        if ($this->GetY() + $h > $this->GetPageHeight() - 22) {
            $this->AddPage();
        }
    }

    function SetDash($black = false, $white = false) {
        if ($black && $white) {
            $s = sprintf('[%.3F %.3F] 0 d', $black, $white);
        } else {
            $s = '[] 0 d';
        }
        $this->_out($s);
    }

    function generateReceipt($borrower, $usertype, $school_id, $result, $copyType) {
        $pageWidth = $this->GetPageWidth() - 40; // Adjust for left and right margins
        $imageWidth = 40;
        $xPos = ($pageWidth - $imageWidth) / 2 + 20; // Adjust for left margin
        $this->Image('C:\\xampp\\htdocs\\Library-System\\Admin\\inc\\img\\horizontal-nbs-logo.png', $xPos, $this->GetY(), $imageWidth);

        // Copy Type Header
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(20, $this->GetY()); // Position at the left margin, aligned with the image
        $this->Cell(0, 5, strtoupper($copyType), 0, 1, 'L');
        $this->Ln(15);

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, 'NBS COLLEGE LIBRARY', 0, 1, 'C');
        $this->Ln(5);

        $receiptDate = date("m/d/Y");
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, 'LIBRARY FINE RECEIPT', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, $receiptDate, 0, 1, 'C');
        $this->Ln(0);

        // Borrower Details
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(71, 5, 'Details', 0, 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(130, 5, 'ID Number: ' . $school_id, 0, 0);
        $this->Cell(19, 5, 'User Type: ', 0, 0);
        $this->Cell(50, 5, $usertype, 0, 1);

        $this->Cell(130, 5, "Borrower's Name: " . $borrower, 0, 0);
        $this->Cell(19, 5, '', 0, 0);
        $this->Cell(50, 5, '', 0, 1);
        $this->Ln(1);

        // Table Header
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(170, 0, '', 'T', 1, 'C'); // Top border for the table
        $this->Cell(70, 10, 'Title', 0, 0, 'L');
        $this->Cell(30, 10, 'Accession', 0, 0, 'L'); // Add Accession column
        $this->Cell(30, 10, 'Fine Type', 0, 0, 'L');
        $this->Cell(20, 10, 'Status', 0, 0, 'L');
        $this->Cell(30, 10, 'Amount', 0, 1, 'L');

        $this->SetFont('Arial', '', 8);

        $totalAmount = 0; // Initialize total amount

        while ($row = mysqli_fetch_assoc($result)) {
            $yPos = $this->GetY();

            $titleWidth = 70;
            $lineHeight = 3;
            $numLines = ceil($this->GetStringWidth($row['book_title']) / $titleWidth);
            $rowHeight = max($numLines * $lineHeight, 6);

            $this->CheckPageBreak($rowHeight);

            $bookTitle = strlen($row['book_title']) > 40 ? substr($row['book_title'], 0, 40) . '...' : $row['book_title'];

            $xPos = $this->GetX();
            $this->MultiCell($titleWidth, $lineHeight, $bookTitle, 0, 'L');

            $this->SetXY($xPos + $titleWidth, $yPos);

            $this->Cell(30, 3.5, $row['accession'], 0, 0, 'L'); // Add Accession
            $this->Cell(30, 3.5, $row['type'], 0, 0, 'L');
            $this->Cell(20, 3.5, $row['status'], 0, 0, 'L'); // Status
            $this->Cell(30, 3.5, number_format($row['amount'], 2), 0, 1, 'L'); // Amount

            // Add the amount to the total
            $totalAmount += $row['amount'];
        }
        $this->Ln(3);
        $this->Cell(170, 0, '', 'T', 1, 'C'); // Bottom border for the table

        // Display Total Amount
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(130, 6, '', 0, 0);
        $this->Cell(19, 6, 'Total:', 0, 0, 'R');
        $this->Cell(30, 6, number_format($totalAmount, 2), 0, 1, 'L');

        // Signature Section
        $this->Ln(1);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, '_______________________', 0, 1, 'R');
        $this->Cell(0, 0, 'Librarian Signature', 0, 1, 'R');
    }
}

if (isset($_POST['fine_ids'])) {
    $fine_ids = $_POST['fine_ids'];
    $fine_ids = array_map('intval', $fine_ids); // Sanitize input

    // Fetch fines and borrower details
    $sql = "SELECT f.type, f.amount, f.status, f.invoice_sale, bk.title AS book_title, bk.accession, u.school_id,
                   CONCAT(u.firstname, ' ', u.lastname) AS borrower, u.usertype
            FROM fines f
            JOIN borrowings b ON f.borrowing_id = b.id
            JOIN books bk ON b.book_id = bk.id
            JOIN users u ON b.user_id = u.id
            WHERE f.id IN (" . implode(',', $fine_ids) . ")";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $borrower = $row['borrower'];
        $usertype = $row['usertype'];
        $school_id = $row['school_id'];

        mysqli_data_seek($result, 0); // Reset result pointer

        $pdf = new PDF('P', 'mm', 'A4');
        $pdf->SetLeftMargin(20); // Set left margin
        $pdf->SetRightMargin(20); // Set right margin
        $pdf->AddPage();

        // Generate Borrower Copy
        $pdf->generateReceipt($borrower, $usertype, $school_id, $result, 'Borrower Copy');

        // Add a dashed line to separate the copies
        $middleY = $pdf->GetPageHeight() / 2;
        $pdf->SetY($middleY - 5);
        $pdf->SetLineWidth(0);
        $pdf->SetDash(1, 1); // Set dashed line
        $pdf->Line(20, $pdf->GetY(), $pdf->GetPageWidth() - 20, $pdf->GetY());
        $pdf->SetDash(); // Reset to solid line
        $pdf->SetY($middleY + 5);

        // Generate Librarian Copy
        mysqli_data_seek($result, 0);
        $pdf->generateReceipt($borrower, $usertype, $school_id, $result, 'Librarian Copy');

        // Generate PDF Filename
        $borrowerLastName = explode(' ', $borrower);
        $borrowerLastName = end($borrowerLastName); // Get the last part as the Last Name
        $currentDate = date('Y-m-d'); // Format: YYYY-MM-DD
        $pdfFilename = $borrowerLastName . ' - Fine Receipt (' . $currentDate . ').pdf';

        $pdf->Output('', $pdfFilename);
    } else {
        echo "<script>alert('No fines found for the selected IDs.'); window.close();</script>";
        exit();
    }
}
?>